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

   Observações medidas na API (setembro/2026):
   - "ultimos/{n}" devolve HTTP 400 para n acima de 20, o que não cobre 24
     meses de dado diário; a consulta principal é por intervalo de datas e
     "ultimos/20" fica como reserva na última tentativa;
   - a janela máxima de uma série diária é de 10 anos (HTTP 406 acima disso);
   - a série 432 (meta Selic) vem preenchida até a data do próximo Copom,
     então observações com data futura (no fuso de Brasília) são descartadas;
   - algumas séries (20623) chegam em ordem decrescente: tudo é reordenado;
   - a TR (226) traz o campo dataFim; só data e valor são guardados.
   ========================================================================== */

'use strict';

const fs = require('node:fs');
const path = require('node:path');

const API_INTERVALO = 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.{codigo}/dados?formato=json&dataInicial={inicio}&dataFinal={fim}';
const API_ULTIMOS = 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.{codigo}/dados/ultimos/{n}?formato=json';
const ULTIMOS_MAXIMO = 20;
const MESES_DE_HISTORICO = 26;     /* 24 meses de gráfico + folga para séries atrasadas */
const ANOS_SELIC_LONGA = 10;       /* percentil da Selic: um valor por mês, 10 anos (limite da API) */

const SAIDA = path.resolve(__dirname, '..', 'src', 'data', 'dados-sgs.json');
const MINIMO_DE_SERIES = 6;
const TENTATIVAS = 3;
const ESPERAS_MS = [3000, 8000];   /* entre a 1ª e a 2ª, entre a 2ª e a 3ª */
const TIMEOUT_MS = 30000;

/* Códigos conferidos na API em 19/09/2026 (valores e periodicidade plausíveis).
   Os nomes seguem o brief; onde a API não bate com o nome esperado, há CONFERIR. */
const SERIES = [
  { id: 'selic',        codigo: 432,   nome: 'Selic, meta definida pelo Copom',                        unidade: '% a.a.',      periodicidade: 'diaria' },
  { id: 'cdi',          codigo: 12,    nome: 'CDI, taxa diária',                                       unidade: '% a.d.',      periodicidade: 'diaria' },
  { id: 'tr',           codigo: 226,   nome: 'TR, taxa referencial diária',                            unidade: '% a.m.',      periodicidade: 'diaria' },
  { id: 'ptax',         codigo: 1,     nome: 'Dólar PTAX, venda',                                      unidade: 'BRL por USD', periodicidade: 'diaria' },
  { id: 'ipca',         codigo: 433,   nome: 'IPCA, variação mensal',                                  unidade: '% a.m.',      periodicidade: 'mensal' },
  { id: 'ipca12m',      codigo: 13522, nome: 'IPCA, acumulado em 12 meses',                            unidade: '% 12m',       periodicidade: 'mensal' },
  { id: 'igpm',         codigo: 189,   nome: 'IGP-M, variação mensal',                                 unidade: '% a.m.',      periodicidade: 'mensal' },
  { id: 'incc',         codigo: 192,   nome: 'INCC, variação mensal',                                  unidade: '% a.m.',      periodicidade: 'mensal' },
  { id: 'ivgr',         codigo: 21340, nome: 'IVG-R, índice de valores de garantia de imóveis residenciais', unidade: 'índice', periodicidade: 'mensal' },
  /* CONFERIR no visualizador do SGS: deve ser a taxa média PF, financiamento imobiliário com taxas reguladas. */
  { id: 'jurosImob',    codigo: 20770, nome: 'Juros médios, financiamento imobiliário PF',             unidade: '% a.a.',      periodicidade: 'mensal' },
  /* CONFERIR: a API devolve ~20 (R$ bilhões/mês?), incompatível com um saldo (~R$ 1 trilhão).
     Pode ser concessão mensal, não saldo. As séries de crédito já foram renumeradas uma vez. */
  { id: 'saldoCredito', codigo: 20623, nome: 'Crédito imobiliário PF (ver CONFERIR no script)',        unidade: 'R$ bilhões',  periodicidade: 'mensal' },
  { id: 'ibcbr',        codigo: 24363, nome: 'IBC-Br, índice de atividade econômica',                  unidade: 'índice',      periodicidade: 'mensal' },
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

/** Primeiro dia do mês, `meses` meses atrás, em "aaaa-mm-dd". */
function mesesAtras(meses) {
  const d = new Date(hojeIso + 'T00:00:00Z');
  d.setUTCMonth(d.getUTCMonth() - meses, 1);
  return d.toISOString().slice(0, 10);
}
/** `anos` anos atrás mais dois dias, para ficar dentro da janela de 10 anos da API. */
function anosAtras(anos) {
  const d = new Date(hojeIso + 'T00:00:00Z');
  d.setUTCFullYear(d.getUTCFullYear() - anos);
  d.setUTCDate(d.getUTCDate() + 2);
  return d.toISOString().slice(0, 10);
}

const urlIntervalo = (codigo, inicioIso) =>
  API_INTERVALO.replace('{codigo}', codigo).replace('{inicio}', brDe(inicioIso)).replace('{fim}', brDe(hojeIso));
const urlUltimos = codigo => API_ULTIMOS.replace('{codigo}', codigo).replace('{n}', ULTIMOS_MAXIMO);

/** Sábado ou domingo? (a 432 é preenchida em dias de calendário; a leitura deve ser de dia útil) */
const fimDeSemana = iso => { const d = new Date(iso + 'T12:00:00Z').getUTCDay(); return d === 0 || d === 6; };

/** Baixa uma URL da API e devolve o histórico normalizado: [[aaaa-mm-dd, valor], ...] em ordem.
    `somenteDiasUteis` descarta sábados e domingos (só faz sentido nas séries diárias). */
async function baixarHistorico(url, somenteDiasUteis = false) {
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
    if (somenteDiasUteis && fimDeSemana(data)) continue;
    porData.set(data, valor);                    /* datas repetidas: fica a última */
  }
  const historico = [...porData.entries()].sort((a, b) => (a[0] < b[0] ? -1 : 1));
  if (historico.length === 0) throw new Error('nenhuma observação válida');
  return historico;
}

/** Até TENTATIVAS vezes, com espera entre elas. `urlDa(i)` escolhe a URL da tentativa i. Devolve null se todas falharem. */
async function comTentativas(rotulo, urlDa, somenteDiasUteis = false) {
  for (let i = 0; i < TENTATIVAS; i++) {
    try {
      return await baixarHistorico(urlDa(i), somenteDiasUteis);
    } catch (erro) {
      const ultima = i === TENTATIVAS - 1;
      console.log(`  ${ultima ? 'FALHOU' : 'erro '} ${rotulo} (tentativa ${i + 1}/${TENTATIVAS}): ${erro.message}`);
      if (!ultima) await dormir(ESPERAS_MS[i] ?? ESPERAS_MS[ESPERAS_MS.length - 1]);
    }
  }
  return null;
}

/** Uma série completa: intervalo de datas nas primeiras tentativas, "ultimos/20" na última. */
async function baixarSerie(serie) {
  const rotulo = `${serie.id.padEnd(13)} SGS ${String(serie.codigo).padEnd(6)}`;
  const historico = await comTentativas(rotulo, i =>
    (i < TENTATIVAS - 1 ? urlIntervalo(serie.codigo, mesesAtras(MESES_DE_HISTORICO)) : urlUltimos(serie.codigo)),
    serie.periodicidade === 'diaria');
  if (!historico) return null;
  const [data, valor] = historico[historico.length - 1];
  console.log(`  ok   ${rotulo} ${historico.length} obs, último ${data} = ${valor}`);
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

/** Selic de 10 anos, reduzida ao último valor de cada mês, para o percentil.
    Dez anos de uma série diária numa consulta só estouram o tempo da API (timeout e 502
    em 20/09/2026), então a busca é feita em janelas de dois anos, uma a uma. */
async function baixarSelicLonga() {
  const porMes = new Map();
  const inicioTotal = anosAtras(ANOS_SELIC_LONGA);
  let inicio = inicioTotal;
  while (inicio <= hojeIso) {
    const d = new Date(inicio + 'T00:00:00Z');
    d.setUTCFullYear(d.getUTCFullYear() + 2);
    const fimJanela = d.toISOString().slice(0, 10) < hojeIso ? d.toISOString().slice(0, 10) : hojeIso;
    const rotulo = `selic (${inicio} a ${fimJanela}) SGS 432`;
    const url = API_INTERVALO.replace('{codigo}', 432).replace('{inicio}', brDe(inicio)).replace('{fim}', brDe(fimJanela));
    const historico = await comTentativas(rotulo, () => url, true);
    if (!historico) return null;                       /* uma janela falhou: sem histórico longo desta vez */
    for (const [data, valor] of historico) porMes.set(data.slice(0, 7), valor);
    d.setUTCDate(d.getUTCDate() + 1);
    inicio = d.toISOString().slice(0, 10);
  }
  const rotulo = `selic (10 anos) SGS 432   `;
  const meses = [...porMes.entries()];
  console.log(`  ok   ${rotulo} ${meses.length} meses, de ${meses[0][0]} a ${meses[meses.length - 1][0]}`);
  return { desde: meses[0][0], ate: meses[meses.length - 1][0], obtidoEm: new Date().toISOString(), meses };
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
    const dados = await baixarSerie(serie);
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

  /* histórico longo da Selic (não conta para o mínimo; se falhar, fica o anterior) */
  if (series.selic) {
    const longa = await baixarSelicLonga();
    if (longa) series.selic.dezAnos = longa;
    else if (anterior?.series?.selic?.dezAnos) {
      series.selic.dezAnos = anterior.series.selic.dezAnos;
      console.log('  mantida selic (10 anos): dados do arquivo anterior');
    }
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
