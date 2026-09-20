#!/usr/bin/env node
/* ==========================================================================
   Live & Learn Brazil — dashboard imobiliário
   Busca as séries do SGS (Banco Central) e grava src/data/dados-sgs.json.

   Node 20 ou mais novo, fetch nativo, nenhuma dependência.
   Uso:  node scripts/buscar-dados.js

   Regras:
   - 3 tentativas por série, com espera crescente entre elas;
   - série que falhar é ignorada e não derruba as outras (se houver um
     arquivo anterior, a série falhada mantém os dados de lá);
   - se menos de 6 séries voltarem, lança erro e NÃO grava nada;
   - cada série leva histórico, último valor e a DATA do último valor;
   - atualizadoEm fica no topo do arquivo.

   Observações aprendidas na API (setembro/2026):
   - a série 432 (meta Selic) vem preenchida até a data do próximo Copom,
     então observações com data futura são descartadas;
   - algumas séries (20623) chegam em ordem decrescente: tudo é reordenado;
   - a TR (226) traz o campo dataFim; só data e valor são guardados.
   ========================================================================== */

'use strict';

const fs = require('node:fs');
const path = require('node:path');

/* Duas formas de consulta. Medido em 19/09/2026: a forma "ultimos/{n}" devolve
   HTTP 400 para n acima de 20, o que não cobre 24 meses de dado diário. Por
   isso a consulta principal é por intervalo de datas (últimos MESES_DE_HISTORICO
   meses) e "ultimos/20" fica como reserva, caso o intervalo falhe. */
const API_INTERVALO = 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.{codigo}/dados?formato=json&dataInicial={inicio}&dataFinal={fim}';
const API_ULTIMOS = 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.{codigo}/dados/ultimos/{n}?formato=json';
const ULTIMOS_MAXIMO = 20;
const MESES_DE_HISTORICO = 26;

const SAIDA = path.resolve(__dirname, '..', 'src', 'data', 'dados-sgs.json');
const MINIMO_DE_SERIES = 6;
const TENTATIVAS = 3;
const ESPERAS_MS = [3000, 8000];      /* entre a 1ª e a 2ª, entre a 2ª e a 3ª */
const TIMEOUT_MS = 30000;

/* Só documenta o tamanho esperado; a consulta por intervalo não usa `n`. */
const DIARIA = 600, MENSAL = 40;

/* Códigos conferidos na API em 19/09/2026 (valores e periodicidade plausíveis).
   Os nomes seguem o brief; onde a API não bate com o nome esperado, há CONFERIR. */
const SERIES = [
  { id: 'selic',        codigo: 432,   nome: 'Selic, meta definida pelo Copom',                       unidade: '% a.a.',     periodicidade: 'diaria', n: DIARIA },
  { id: 'cdi',          codigo: 12,    nome: 'CDI, taxa diária',                                      unidade: '% a.d.',     periodicidade: 'diaria', n: DIARIA },
  { id: 'tr',           codigo: 226,   nome: 'TR, taxa referencial diária',                           unidade: '% a.m.',     periodicidade: 'diaria', n: DIARIA },
  { id: 'ptax',         codigo: 1,     nome: 'Dólar PTAX, venda',                                     unidade: 'BRL por USD', periodicidade: 'diaria', n: DIARIA },
  { id: 'ipca',         codigo: 433,   nome: 'IPCA, variação mensal',                                 unidade: '% a.m.',     periodicidade: 'mensal', n: MENSAL },
  { id: 'ipca12m',      codigo: 13522, nome: 'IPCA, acumulado em 12 meses',                           unidade: '% 12m',      periodicidade: 'mensal', n: MENSAL },
  { id: 'igpm',         codigo: 189,   nome: 'IGP-M, variação mensal',                                unidade: '% a.m.',     periodicidade: 'mensal', n: MENSAL },
  { id: 'incc',         codigo: 192,   nome: 'INCC, variação mensal',                                 unidade: '% a.m.',     periodicidade: 'mensal', n: MENSAL },
  { id: 'ivgr',         codigo: 21340, nome: 'IVG-R, índice de valores de garantia de imóveis residenciais', unidade: 'índice', periodicidade: 'mensal', n: MENSAL },
  /* CONFERIR no visualizador do SGS: deve ser a taxa média PF, financiamento imobiliário com taxas reguladas. */
  { id: 'jurosImob',    codigo: 20770, nome: 'Juros médios, financiamento imobiliário PF',            unidade: '% a.a.',     periodicidade: 'mensal', n: MENSAL },
  /* CONFERIR: a API devolve ~20 (R$ bilhões/mês?), incompatível com um saldo (~R$ 1 trilhão).
     Pode ser concessão mensal, não saldo. As séries de crédito já foram renumeradas uma vez. */
  { id: 'saldoCredito', codigo: 20623, nome: 'Crédito imobiliário PF (ver CONFERIR no script)',       unidade: 'R$ bilhões', periodicidade: 'mensal', n: MENSAL },
  { id: 'ibcbr',        codigo: 24363, nome: 'IBC-Br, índice de atividade econômica',                 unidade: 'índice',     periodicidade: 'mensal', n: MENSAL },
];

const dormir = ms => new Promise(r => setTimeout(r, ms));

/* "Hoje" no fuso de Brasília ("aaaa-mm-dd"): à noite, o UTC já virou o dia seguinte,
   e a 432 traria a meta datada de amanhã. */
const hojeIso = (() => {
  const p = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Sao_Paulo', year: 'numeric', month: '2-digit', day: '2-digit' })
    .formatToParts(new Date());
  const g = t => p.find(x => x.type === t).value;
  return `${g('year')}-${g('month')}-${g('day')}`;
})();

/** "dd/mm/aaaa" → "aaaa-mm-dd" */
function isoDe(dataBr) {
  const m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(String(dataBr).trim());
  return m ? `${m[3]}-${m[2]}-${m[1]}` : null;
}

/** "aaaa-mm-dd" → "dd/mm/aaaa", o formato que a API pede nos parâmetros. */
const brDe = iso => `${iso.slice(8, 10)}/${iso.slice(5, 7)}/${iso.slice(0, 4)}`;

/** Primeiro dia do mês, MESES_DE_HISTORICO meses atrás, em "aaaa-mm-dd". */
function inicioDoHistorico() {
  const d = new Date(hojeIso + 'T00:00:00Z');
  d.setUTCMonth(d.getUTCMonth() - MESES_DE_HISTORICO, 1);
  return d.toISOString().slice(0, 10);
}

/** URL da tentativa: intervalo de datas nas primeiras, "ultimos/20" na última, como reserva. */
function urlDa(serie, tentativa) {
  if (tentativa < TENTATIVAS - 1) {
    return API_INTERVALO.replace('{codigo}', serie.codigo)
      .replace('{inicio}', brDe(inicioDoHistorico())).replace('{fim}', brDe(hojeIso));
  }
  return API_ULTIMOS.replace('{codigo}', serie.codigo).replace('{n}', ULTIMOS_MAXIMO);
}

/** Uma tentativa: baixa, valida e normaliza uma série. Lança em qualquer problema. */
async function baixarUmaVez(serie, tentativa) {
  const url = urlDa(serie, tentativa);
  const resposta = await fetch(url, {
    headers: { accept: 'application/json', 'user-agent': 'liveandlearnbrazil.com dashboard (GitHub Actions)' },
    signal: AbortSignal.timeout(TIMEOUT_MS),
  });
  if (!resposta.ok) throw new Error(`HTTP ${resposta.status}`);
  const bruto = await resposta.json();
  if (!Array.isArray(bruto) || bruto.length === 0) throw new Error('resposta vazia ou fora do formato');

  const porData = new Map();
  for (const linha of bruto) {
    const data = isoDe(linha.data);
    const valor = Number(String(linha.valor).replace(',', '.'));
    if (!data || !Number.isFinite(valor)) continue;
    if (data > hojeIso) continue;                /* 432 vem preenchida até o próximo Copom */
    porData.set(data, valor);                    /* datas repetidas: fica a última */
  }
  const historico = [...porData.entries()].sort((a, b) => (a[0] < b[0] ? -1 : 1));
  if (historico.length === 0) throw new Error('nenhuma observação válida');

  const [data, valor] = historico[historico.length - 1];
  return {
    codigo: serie.codigo,
    nome: serie.nome,
    unidade: serie.unidade,
    periodicidade: serie.periodicidade,
    obtidoEm: new Date().toISOString(),
    ultimo: { data, valor },
    historico,
  };
}

/** Até TENTATIVAS vezes, com espera entre elas. Devolve null se todas falharem. */
async function baixar(serie) {
  for (let i = 0; i < TENTATIVAS; i++) {
    try {
      const dados = await baixarUmaVez(serie, i);
      console.log(`  ok   ${serie.id.padEnd(13)} SGS ${String(serie.codigo).padEnd(6)} ${dados.historico.length} obs, último ${dados.ultimo.data} = ${dados.ultimo.valor}`);
      return dados;
    } catch (erro) {
      const ultima = i === TENTATIVAS - 1;
      console.log(`  ${ultima ? 'FALHOU' : 'erro '} ${serie.id.padEnd(13)} SGS ${serie.codigo} (tentativa ${i + 1}/${TENTATIVAS}): ${erro.message}`);
      if (!ultima) await dormir(ESPERAS_MS[i] ?? ESPERAS_MS[ESPERAS_MS.length - 1]);
    }
  }
  return null;
}

function lerAnterior() {
  try { return JSON.parse(fs.readFileSync(SAIDA, 'utf8')); } catch { return null; }
}

async function principal() {
  console.log(`Buscando ${SERIES.length} séries do SGS (${hojeIso})`);
  const anterior = lerAnterior();
  const series = {};
  let novas = 0;

  for (const serie of SERIES) {
    const dados = await baixar(serie);
    if (dados) { series[serie.id] = dados; novas++; continue; }
    const antiga = anterior?.series?.[serie.id];
    if (antiga) {
      series[serie.id] = antiga;
      console.log(`  mantida ${serie.id}: dados do arquivo anterior (obtidos em ${antiga.obtidoEm ?? '?'})`);
    }
  }

  if (novas < MINIMO_DE_SERIES) {
    throw new Error(`só ${novas} de ${SERIES.length} séries responderam (mínimo ${MINIMO_DE_SERIES}); nada foi gravado, o arquivo anterior fica como está`);
  }

  const saida = {
    atualizadoEm: new Date().toISOString(),
    fonte: 'Banco Central do Brasil, Sistema Gerenciador de Séries Temporais (api.bcb.gov.br)',
    seriesObtidas: novas,
    seriesTotal: SERIES.length,
    series,
  };

  /* grava em arquivo temporário e renomeia: nunca deixa um JSON pela metade */
  fs.mkdirSync(path.dirname(SAIDA), { recursive: true });
  const temporario = SAIDA + '.tmp';
  fs.writeFileSync(temporario, JSON.stringify(saida, null, 1) + '\n');
  fs.renameSync(temporario, SAIDA);
  console.log(`Gravado ${path.relative(process.cwd(), SAIDA)}: ${novas}/${SERIES.length} séries novas, atualizadoEm ${saida.atualizadoEm}`);
}

principal().catch(erro => {
  console.error(`ERRO: ${erro.message}`);
  process.exit(1);
});
