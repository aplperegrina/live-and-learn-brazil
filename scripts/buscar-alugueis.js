#!/usr/bin/env node
/* ==========================================================================
   Live & Learn Brazil — dashboard imobiliário
   Busca as séries de aluguel que não existem no SGS do Banco Central e grava
   realestate/dados/dados-alugueis.json:
   - IVAR (FGV IBRE): variação mensal e acumulado em 12 meses;
   - Índice FipeZAP de Locação Residencial (Fipe), cidade de São Paulo: preço
     médio do aluguel por m², total e por número de dormitórios (1 a 4 ou
     mais), variação em 12 meses e rentabilidade do aluguel.

   Node 20 ou mais novo, fetch nativo, nenhuma dependência.
   Uso:  node scripts/buscar-alugueis.js

   Regras (as de buscar-dados.js, adaptadas a duas fontes mensais):
   - 3 tentativas por fonte, com espera crescente entre elas;
   - fonte que falhar não derruba a outra: as séries dela ficam com os dados
     do arquivo anterior, com a data original;
   - o arquivo só é regravado quando alguma série muda (são dados mensais; na
     maioria dos dias não há nada a commitar);
   - cada série leva histórico, último valor e a DATA do último valor;
   - grava em arquivo temporário e renomeia: nunca deixa um JSON pela metade;
   - termina com erro (código 1) se uma fonte falhou ou se o último dado de
     uma fonte tem 3 meses ou mais de atraso. O erro vem DEPOIS de gravar o
     que deu certo, para o passo aparecer como falho no GitHub Actions sem
     perder a outra fonte.

   Observações medidas nas fontes (12/11/2026):
   - IVAR: a FGV mantém uma página por ano (…/ivar-alugueis-residenciais-AAAA)
     com uma tabela "Mês de referência | Evolução Mensal | Acumulado 12 meses"
     que mostra só 13 meses. Por isso são lidas a página do ano anterior e a
     do ano corrente, por cima do histórico já gravado;
   - o ano que ainda não tem página (janeiro, antes da primeira divulgação)
     redireciona para a lista de notícias com HTTP 200: página sem tabela não
     é falha, desde que a outra página traga a tabela;
   - o acumulado em 12 meses publicado bate com a composição das 12 variações
     mensais (diferença máxima de 0,03 ponto em 12 meses conferidos). O script
     refaz essa conta: se não fechar, a tabela mudou e nada é aproveitado;
   - FipeZap: a planilha de séries históricas (xlsx, ~4,7 MB) tem uma aba por
     cidade. As colunas são achadas pelos cabeçalhos (Imóveis residenciais →
     Locação → Preço médio → Total, 1D … 4D), não pela letra. Variações e
     rentabilidade vêm em fração (0,0064 = 0,64%) e a data é número de série
     do Excel. A venda é publicada antes da locação, então a última linha
     pode ter só venda: vale a última linha com preço de locação;
   - a planilha aceita If-Modified-Since: sem mudança, responde 304 e não é
     baixada de novo;
   - a Fipe publica os preços como média móvel de três meses (nota da própria
     planilha) e não separa estúdio: a quebra é 1, 2, 3 e 4 ou mais dormitórios;
   - o xlsx é um zip; a leitura usa só node:zlib.
   ========================================================================== */

'use strict';

const fs = require('node:fs');
const path = require('node:path');
const zlib = require('node:zlib');

const URL_IVAR = 'https://portal.fgv.br/noticias/ivar-alugueis-residenciais-{ano}';
const URL_FIPEZAP = 'https://downloads.fipe.org.br/indices/fipezap/fipezap-serieshistoricas.xlsx';
const ABA_FIPEZAP = 'São Paulo';

const SAIDA = path.resolve(__dirname, '..', 'realestate', 'dados', 'dados-alugueis.json');
const MESES_DE_HISTORICO = 26;     /* como em buscar-dados.js: 24 meses de gráfico + folga */
const MINIMO_IVAR = 6;             /* menos linhas que isso nas tabelas: a página mudou de formato */
const TOLERANCIA_IVAR = 0.3;       /* pontos percentuais entre o acumulado publicado e o composto */
const MESES_DE_ATRASO = 3;         /* as duas fontes saem no mês seguinte ao de referência: 3 meses é dado parado */
const TENTATIVAS = 3;
const ESPERAS_MS = [3000, 8000];   /* entre a 1ª e a 2ª, entre a 2ª e a 3ª */
const TIMEOUT_MS = 60000;          /* a planilha tem quase 5 MB */
const AGENTE = 'liveandlearnbrazil.com dashboard (GitHub Actions)';

const FONTE_IVAR = 'FGV IBRE, Índice de Variação de Aluguéis Residenciais (IVAR)';
const FONTE_FIPEZAP = 'Fipe, Índice FipeZAP de Locação Residencial, séries históricas';

const IDS_IVAR = ['ivar', 'ivar12m'];
const IDS_FIPEZAP = ['aluguelSp', 'aluguelSp1d', 'aluguelSp2d', 'aluguelSp3d', 'aluguelSp4d', 'aluguelSp12m', 'rentabilidadeSp'];

const dormir = ms => new Promise(r => setTimeout(r, ms));

/* "Hoje" no fuso de Brasília ("aaaa-mm-dd"), como em buscar-dados.js. */
const hojeIso = (() => {
  const p = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Sao_Paulo', year: 'numeric', month: '2-digit', day: '2-digit' })
    .formatToParts(new Date());
  const g = t => p.find(x => x.type === t).value;
  return `${g('year')}-${g('month')}-${g('day')}`;
})();

/** Meses contados a partir do ano zero, para medir distâncias: "2026-10-01" → 24322. */
const indiceDoMes = iso => Number(iso.slice(0, 4)) * 12 + Number(iso.slice(5, 7));
const arredondar = (v, casas = 2) => +v.toFixed(casas);
const emOrdem = (a, b) => (a[0] < b[0] ? -1 : 1);

/** Até TENTATIVAS vezes, com espera entre elas. Devolve o resultado de `fn` ou null se todas falharem. */
async function comTentativas(rotulo, fn) {
  for (let i = 0; i < TENTATIVAS; i++) {
    try {
      return await fn();
    } catch (erro) {
      const ultima = i === TENTATIVAS - 1;
      console.log(`  ${ultima ? 'FALHOU' : 'erro '} ${rotulo} (tentativa ${i + 1}/${TENTATIVAS}): ${erro.message}`);
      if (!ultima) await dormir(ESPERAS_MS[i] ?? ESPERAS_MS[ESPERAS_MS.length - 1]);
    }
  }
  return null;
}

/* --------------------------------------------------------------------------
   IVAR (FGV IBRE)
   -------------------------------------------------------------------------- */
const MESES_PT = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

/** Texto de uma célula de tabela HTML, sem tags nem espaços duros. */
const textoDaCelula = html => html.replace(/<[^>]*>/g, ' ').replace(/&nbsp;|&#160;| /g, ' ').replace(/\s+/g, ' ').trim();

/** "-0,01%" → -0.01 (aceita o sinal de menos tipográfico). Texto vazio ou estranho → NaN. */
function numeroBr(texto) {
  const limpo = String(texto ?? '').replace(/[−–]/g, '-').replace(/[\s%]/g, '').replace(/\./g, '').replace(',', '.');
  return limpo === '' ? NaN : Number(limpo);
}

/** Tabela "Mês de referência | Evolução Mensal | Acumulado 12 meses" → Map("2026-10-01" → [mensal, doze]). */
function lerTabelaIvar(html) {
  const meses = new Map();
  let colunaMensal = 1, colunaDoze = 2;
  for (const [, linha] of html.matchAll(/<tr\b[^>]*>([\s\S]*?)<\/tr>/gi)) {
    const celulas = [...linha.matchAll(/<t[dh]\b[^>]*>([\s\S]*?)<\/t[dh]>/gi)].map(m => textoDaCelula(m[1]));
    if (celulas.length < 3) continue;
    /* linha de cabeçalho: confirma em que coluna está cada medida */
    const iMensal = celulas.findIndex(c => /mensal/i.test(c)), iDoze = celulas.findIndex(c => /12\s*meses/i.test(c));
    if (iMensal > 0 && iDoze > 0 && iMensal !== iDoze) { colunaMensal = iMensal; colunaDoze = iDoze; continue; }
    const mes = /^([a-zç]{3})\/(\d{2})$/i.exec(celulas[0]);
    if (!mes) continue;
    const i = MESES_PT.indexOf(mes[1].toLowerCase());
    const mensal = numeroBr(celulas[colunaMensal]), doze = numeroBr(celulas[colunaDoze]);
    if (i < 0 || !Number.isFinite(mensal) || !Number.isFinite(doze)) continue;
    if (Math.abs(mensal) > 15 || Math.abs(doze) > 60) continue;     /* fora de qualquer leitura plausível */
    meses.set(`20${mes[2]}-${String(i + 1).padStart(2, '0')}-01`, [mensal, doze]);
  }
  return meses;
}

/** Diferença, em pontos, entre o acumulado em 12 meses publicado no último mês e a composição
    das 12 variações mensais. null quando faltam meses para refazer a conta. */
function diferencaDoAcumulado(ordenado) {
  const doze = ordenado.slice(-12);
  if (doze.length < 12 || indiceDoMes(doze[11][0]) - indiceDoMes(doze[0][0]) !== 11) return null;   /* buraco na série */
  const composto = (doze.reduce((p, [, v]) => p * (1 + v[0] / 100), 1) - 1) * 100;
  return Math.abs(composto - doze[11][1][1]);
}

async function baixarPaginaIvar(ano) {
  const url = URL_IVAR.replace('{ano}', ano);
  const resposta = await fetch(url, { headers: { accept: 'text/html', 'user-agent': AGENTE }, signal: AbortSignal.timeout(TIMEOUT_MS) });
  if (resposta.status === 404 || resposta.status === 410) return { url, meses: new Map() };   /* o ano ainda não tem página */
  if (!resposta.ok) throw new Error(`HTTP ${resposta.status}`);
  return { url, meses: lerTabelaIvar(await resposta.text()) };
}

/** Lê as páginas do ano anterior e do ano corrente, por cima do histórico já gravado.
    Devolve as duas séries e a página lida, ou null se a fonte falhou. */
async function baixarIvar(anterior) {
  const anoAtual = Number(hojeIso.slice(0, 4));
  const meses = new Map();
  const dozeGravado = new Map(anterior?.series?.ivar12m?.historico ?? []);
  for (const [data, mensal] of anterior?.series?.ivar?.historico ?? []) {
    if (dozeGravado.has(data)) meses.set(data, [mensal, dozeGravado.get(data)]);
  }

  let lidos = 0, pagina = null;
  for (const ano of [anoAtual - 1, anoAtual]) {          /* o ano corrente por último: os valores dele prevalecem */
    const resultado = await comTentativas(`ivar ${ano}     FGV`, () => baixarPaginaIvar(ano));
    if (!resultado) return null;
    if (resultado.meses.size) pagina = resultado.url;
    lidos += resultado.meses.size;
    for (const [data, valores] of resultado.meses) meses.set(data, valores);
  }
  if (lidos < MINIMO_IVAR) {
    console.log(`  FALHOU ivar          FGV: só ${lidos} meses nas tabelas (mínimo ${MINIMO_IVAR}); a página pode ter mudado de formato`);
    return null;
  }

  const ordenado = [...meses.entries()].filter(([data]) => data <= hojeIso).sort(emOrdem).slice(-MESES_DE_HISTORICO);
  const diferenca = diferencaDoAcumulado(ordenado);
  if (diferenca !== null && diferenca > TOLERANCIA_IVAR) {
    console.log(`  FALHOU ivar          FGV: o acumulado em 12 meses publicado difere ${diferenca.toFixed(2)} ponto da composição das variações mensais; a tabela pode ter mudado`);
    return null;
  }
  const [data, [mensal, doze]] = ordenado[ordenado.length - 1];
  console.log(`  ok   ivar          FGV    ${ordenado.length} meses, último ${data} = ${mensal} (12 meses: ${doze})`);
  const base = { fonte: FONTE_IVAR, periodicidade: 'mensal' };
  return {
    pagina,
    series: {
      ivar:    { nome: 'IVAR, variação mensal',       unidade: '% a.m.', ...base, historico: ordenado.map(([d, v]) => [d, v[0]]) },
      ivar12m: { nome: 'IVAR, acumulado em 12 meses', unidade: '% 12m',  ...base, historico: ordenado.map(([d, v]) => [d, v[1]]) },
    },
  };
}

/* --------------------------------------------------------------------------
   Índice FipeZAP de Locação Residencial (Fipe), aba São Paulo
   -------------------------------------------------------------------------- */

/** Devolve, em texto, um arquivo de dentro do zip (o xlsx) já em memória. */
function lerDoZip(zip, nome) {
  /* o diretório central fica no fim do arquivo; procura a assinatura de trás para a frente */
  let fim = -1;
  for (let i = zip.length - 22; i >= Math.max(0, zip.length - 65557); i--) {
    if (zip.readUInt32LE(i) === 0x06054b50) { fim = i; break; }
  }
  if (fim < 0) throw new Error('o arquivo baixado não é um zip');
  const entradas = zip.readUInt16LE(fim + 10);
  let p = zip.readUInt32LE(fim + 16);
  for (let n = 0; n < entradas; n++) {
    if (p + 46 > zip.length || zip.readUInt32LE(p) !== 0x02014b50) throw new Error('diretório do zip corrompido');
    const metodo = zip.readUInt16LE(p + 10);
    const tamanho = zip.readUInt32LE(p + 20);
    const tamNome = zip.readUInt16LE(p + 28), tamExtra = zip.readUInt16LE(p + 30), tamComentario = zip.readUInt16LE(p + 32);
    const local = zip.readUInt32LE(p + 42);
    if (zip.toString('utf8', p + 46, p + 46 + tamNome) === nome) {
      const inicio = local + 30 + zip.readUInt16LE(local + 26) + zip.readUInt16LE(local + 28);
      const dados = zip.subarray(inicio, inicio + tamanho);
      if (metodo === 0) return dados.toString('utf8');
      if (metodo === 8) return zlib.inflateRawSync(dados).toString('utf8');
      throw new Error(`método de compressão ${metodo} não suportado em ${nome}`);
    }
    p += 46 + tamNome + tamExtra + tamComentario;
  }
  throw new Error(`${nome} não existe dentro do xlsx`);
}

const decodificarXml = t => t.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&apos;/g, "'")
  .replace(/&#x([0-9a-f]+);/gi, (_, n) => String.fromCodePoint(parseInt(n, 16))).replace(/&#(\d+);/g, (_, n) => String.fromCodePoint(+n))
  .replace(/&amp;/g, '&');
/** Texto de um <si> ou <is>: os trechos <t>, na ordem. */
const textoDoXml = xml => decodificarXml([...xml.matchAll(/<t\b[^>]*>([\s\S]*?)<\/t>/g)].map(t => t[1]).join(''));
const atributo = (tag, nome) => { const m = new RegExp(`\\b${nome}="([^"]*)"`).exec(tag); return m ? decodificarXml(m[1]) : null; };
const tagsDe = (xml, nome) => [...xml.matchAll(new RegExp(`<${nome}\\b[^>]*>`, 'g'))].map(m => m[0]);
/** "A" → 1, "Z" → 26, "AA" → 27. */
const numeroDaColuna = letras => [...letras].reduce((n, c) => n * 26 + c.charCodeAt(0) - 64, 0);

/** Lê uma aba do xlsx: Map(número da linha → Map(número da coluna → número ou texto)). */
function lerAba(zip, nomeDaAba) {
  const aba = tagsDe(lerDoZip(zip, 'xl/workbook.xml'), 'sheet').find(tag => atributo(tag, 'name') === nomeDaAba);
  if (!aba) throw new Error(`aba "${nomeDaAba}" não encontrada na planilha`);
  const idDaAba = atributo(aba, 'r:id') ?? (/\b\w+:id="([^"]*)"/.exec(aba) ?? [])[1];
  const relacao = tagsDe(lerDoZip(zip, 'xl/_rels/workbook.xml.rels'), 'Relationship').find(tag => atributo(tag, 'Id') === idDaAba);
  if (!relacao) throw new Error(`arquivo da aba "${nomeDaAba}" não encontrado`);
  const alvo = atributo(relacao, 'Target');
  const strings = [...lerDoZip(zip, 'xl/sharedStrings.xml').matchAll(/<si\b[^>]*?(?:\/>|>([\s\S]*?)<\/si>)/g)].map(m => textoDoXml(m[1] ?? ''));

  const linhas = new Map();
  const xml = lerDoZip(zip, alvo.startsWith('/') ? alvo.slice(1) : `xl/${alvo}`);
  for (const [, atributos, dentro] of xml.matchAll(/<c\b([^>]*?)(?:\/>|>([\s\S]*?)<\/c>)/g)) {
    const posicao = /\br="([A-Z]+)(\d+)"/.exec(atributos);
    if (!posicao || dentro === undefined) continue;
    const tipo = atributo(atributos, 't');
    const v = (/<v\b[^>]*>([\s\S]*?)<\/v>/.exec(dentro) ?? [])[1];
    let valor;
    if (tipo === 'inlineStr') valor = textoDoXml(dentro);
    else if (v === undefined) continue;
    else if (tipo === 's') valor = strings[Number(v)];
    else if (tipo === 'str' || tipo === 'e' || tipo === 'b') valor = decodificarXml(v);   /* texto de fórmula, erro, booleano */
    else valor = Number(v);
    if (valor === undefined || (typeof valor === 'number' && !Number.isFinite(valor))) continue;
    const linha = Number(posicao[2]);
    if (!linhas.has(linha)) linhas.set(linha, new Map());
    linhas.get(linha).set(numeroDaColuna(posicao[1]), valor);
  }
  return linhas;
}

/** Número de série do Excel → "aaaa-mm-01" (as datas da planilha são sempre o dia 1º do mês). */
const mesDoExcel = serie => new Date(Date.UTC(1899, 11, 30) + Math.round(serie) * 86400000).toISOString().slice(0, 8) + '01';

const QUEBRAS = ['Total', '1D', '2D', '3D', '4D'];

/** Acha as colunas pelos cabeçalhos e devolve os meses com locação residencial publicada, em ordem. */
function lerLocacao(linhas) {
  const texto = (linha, coluna) => { const v = linhas.get(linha)?.get(coluna); return typeof v === 'string' ? v.trim() : ''; };
  const numeros = [...linhas.keys()].sort((a, b) => a - b);
  const colunasDa = linha => [...(linhas.get(linha)?.keys() ?? [])].sort((a, b) => a - b);

  /* quatro linhas de cabeçalho: grupo (residenciais ou comerciais), bloco (Venda, Locação,
     Rentabilidade), medida (Preço médio, Var. em 12 meses…) e quebra (Data, Total, 1D…) */
  const linhaQuebra = numeros.find(n => texto(n, 2) === 'Data');
  if (!linhaQuebra || linhaQuebra < 4) throw new Error('linha de cabeçalho ("Data" na coluna B) não encontrada');
  const linhaGrupo = linhaQuebra - 3, linhaBloco = linhaQuebra - 2, linhaMedida = linhaQuebra - 1;

  /** Coluna em que começa um bloco dos imóveis residenciais e coluna em que começa o bloco seguinte. */
  const bloco = prefixo => {
    const colunas = colunasDa(linhaBloco);
    const inicio = colunas.find(c => texto(linhaBloco, c).startsWith(prefixo) && texto(linhaGrupo, c).startsWith('Imóveis residenciais'));
    if (!inicio) throw new Error(`bloco "${prefixo}" dos imóveis residenciais não encontrado`);
    return [inicio, colunas.find(c => c > inicio) ?? Infinity];
  };
  const [inicioLocacao, fimLocacao] = bloco('Locação');
  const [colunaRentabilidade] = bloco('Rentabilidade');
  const medida = prefixo => {
    const c = colunasDa(linhaMedida).find(k => k >= inicioLocacao && k < fimLocacao && texto(linhaMedida, k).startsWith(prefixo));
    if (!c) throw new Error(`coluna "${prefixo}" da locação não encontrada`);
    return c;
  };
  const colunaPreco = medida('Preço médio'), colunaDoze = medida('Var. em 12 meses');
  if (!QUEBRAS.every((rotulo, i) => texto(linhaQuebra, colunaPreco + i) === rotulo)) throw new Error('as colunas de preço da locação não são mais Total, 1D, 2D, 3D e 4D');
  if (texto(linhaQuebra, colunaDoze) !== 'Total' || texto(linhaQuebra, colunaRentabilidade) !== 'Total') throw new Error('as colunas "Total" de variação e de rentabilidade mudaram de lugar');

  const meses = new Map();
  for (const numero of numeros) {
    if (numero <= linhaQuebra) continue;
    const celulas = linhas.get(numero);
    const serie = celulas.get(2);
    const precos = QUEBRAS.map((_, i) => celulas.get(colunaPreco + i));
    if (typeof serie !== 'number' || precos.some(v => typeof v !== 'number')) continue;    /* mês sem locação publicada */
    const data = mesDoExcel(serie);
    if (data < '2000-01-01' || data > hojeIso) continue;
    if (precos.some(v => !(v > 1 && v < 1000))) throw new Error(`preço de locação fora do plausível na linha ${numero} (${data})`);
    const doze = celulas.get(colunaDoze), rentabilidade = celulas.get(colunaRentabilidade);
    meses.set(data, {
      data,
      precos: precos.map(v => arredondar(v)),
      doze: typeof doze === 'number' && Math.abs(doze) < 1 ? arredondar(doze * 100) : null,
      /* taxa mensal × 12, como no informe da Fipe */
      rentabilidade: typeof rentabilidade === 'number' && rentabilidade > 0 && rentabilidade < 0.03 ? arredondar(rentabilidade * 12 * 100) : null,
    });
  }
  return [...meses.values()].sort((a, b) => (a.data < b.data ? -1 : 1));
}

/** Baixa a planilha (se mudou desde `modificadaEm`) e monta as séries de São Paulo.
    Devolve { igual: true } quando o servidor responde 304, ou null se a fonte falhou. */
async function baixarFipezap(modificadaEm) {
  const resultado = await comTentativas('fipezap       Fipe', async () => {
    const headers = { 'user-agent': AGENTE };
    if (modificadaEm) headers['if-modified-since'] = modificadaEm;
    const resposta = await fetch(URL_FIPEZAP, { headers, signal: AbortSignal.timeout(TIMEOUT_MS) });
    if (resposta.status === 304) return { igual: true };
    if (!resposta.ok) throw new Error(`HTTP ${resposta.status}`);
    const dados = lerLocacao(lerAba(Buffer.from(await resposta.arrayBuffer()), ABA_FIPEZAP)).slice(-MESES_DE_HISTORICO);
    if (dados.length < 12) throw new Error(`só ${dados.length} meses de locação na aba ${ABA_FIPEZAP}`);
    return { dados, modificadaEm: resposta.headers.get('last-modified') };
  });
  if (!resultado) return null;
  if (resultado.igual) {
    console.log(`  igual fipezap      Fipe   planilha sem mudança desde ${modificadaEm}`);
    return resultado;
  }
  const { dados } = resultado;
  const ultimo = dados[dados.length - 1];
  console.log(`  ok   fipezap       Fipe   ${dados.length} meses, último ${ultimo.data} = ${ultimo.precos[0]} R$/m² (1D ${ultimo.precos[1]}, 2D ${ultimo.precos[2]}, 3D ${ultimo.precos[3]}, 4D ${ultimo.precos[4]})`);
  const base = { fonte: FONTE_FIPEZAP, periodicidade: 'mensal' };
  const preco = (i, quebra) => ({
    nome: `FipeZap, aluguel residencial em São Paulo, preço médio${quebra}`, unidade: 'R$/m² por mês', ...base,
    historico: dados.map(d => [d.data, d.precos[i]]),
  });
  const medida = (campo, nome, unidade) => ({ nome, unidade, ...base, historico: dados.filter(d => d[campo] != null).map(d => [d.data, d[campo]]) });
  return {
    modificadaEm: resultado.modificadaEm,
    series: {
      aluguelSp:       preco(0, ''),
      aluguelSp1d:     preco(1, ', 1 dormitório'),
      aluguelSp2d:     preco(2, ', 2 dormitórios'),
      aluguelSp3d:     preco(3, ', 3 dormitórios'),
      aluguelSp4d:     preco(4, ', 4 ou mais dormitórios'),
      aluguelSp12m:    medida('doze', 'FipeZap, aluguel residencial em São Paulo, variação em 12 meses', '% 12m'),
      rentabilidadeSp: medida('rentabilidade', 'FipeZap, rentabilidade do aluguel residencial em São Paulo (taxa mensal × 12)', '% a.a.'),
    },
  };
}

/* --------------------------------------------------------------------------
   Montagem do arquivo
   -------------------------------------------------------------------------- */
function lerAnterior() {
  try { return JSON.parse(fs.readFileSync(SAIDA, 'utf8')); } catch { return null; }
}

async function principal() {
  console.log(`Buscando IVAR (FGV) e FipeZap (Fipe) (${hojeIso})`);
  const anterior = lerAnterior();
  const agora = new Date().toISOString();
  const series = {};
  const problemas = [];
  let mudou = false;

  /** Guarda as séries novas; série idêntica à do arquivo anterior mantém o obtidoEm original. */
  const guardar = novas => {
    for (const [id, serie] of Object.entries(novas)) {
      if (!serie.historico.length) continue;
      const [data, valor] = serie.historico[serie.historico.length - 1];
      const { historico, ...resto } = serie;
      const completa = { ...resto, obtidoEm: agora, ultimo: { data, valor }, historico };
      const antiga = anterior?.series?.[id];
      const igual = antiga && JSON.stringify({ ...antiga, obtidoEm: null }) === JSON.stringify({ ...completa, obtidoEm: null });
      series[id] = igual ? antiga : completa;
      if (!igual) mudou = true;
    }
  };
  /** Fica com as séries do arquivo anterior, como estão. */
  const manter = (ids, rotulo) => {
    const mantidas = ids.filter(id => anterior?.series?.[id]);
    for (const id of mantidas) series[id] = anterior.series[id];
    if (rotulo) console.log(mantidas.length ? `  mantida ${rotulo}: dados do arquivo anterior (obtidos em ${series[mantidas[0]].obtidoEm ?? '?'})` : `  sem dados de ${rotulo}: não há arquivo anterior`);
  };

  /* IVAR */
  let paginaIvar = anterior?.fontes?.ivar?.pagina ?? null;
  const ivar = await baixarIvar(anterior);
  if (!ivar) { manter(IDS_IVAR, 'ivar'); problemas.push('a página do IVAR (FGV) não respondeu ou mudou de formato'); }
  else {
    guardar(ivar.series);
    if (ivar.pagina !== paginaIvar) { paginaIvar = ivar.pagina; mudou = true; }
  }

  /* FipeZap: só pede a planilha de novo se as séries dela estão todas no arquivo anterior */
  let modificadaEm = anterior?.fontes?.fipezap?.modificadaEm ?? null;
  const temFipezap = IDS_FIPEZAP.every(id => anterior?.series?.[id]);
  const fipezap = await baixarFipezap(temFipezap ? modificadaEm : null);
  if (!fipezap) { manter(IDS_FIPEZAP, 'fipezap'); problemas.push('a planilha do FipeZap (Fipe) não respondeu ou mudou de formato'); }
  else if (fipezap.igual) manter(IDS_FIPEZAP);
  else {
    guardar(fipezap.series);
    if (fipezap.modificadaEm !== modificadaEm) { modificadaEm = fipezap.modificadaEm; mudou = true; }
  }

  /* dado parado: a fonte responde, mas o último mês ficou para trás (endereço novo, série encerrada…) */
  for (const [id, rotulo] of [['ivar', 'IVAR'], ['aluguelSp', 'FipeZap']]) {
    const ultimo = series[id]?.ultimo?.data;
    if (ultimo && indiceDoMes(hojeIso) - indiceDoMes(ultimo) >= MESES_DE_ATRASO) {
      problemas.push(`o último dado do ${rotulo} é de ${ultimo.slice(0, 7)}; já deveria haver mês mais novo publicado`);
    }
  }

  const total = IDS_IVAR.length + IDS_FIPEZAP.length;
  const presentes = Object.keys(series).length;
  if (presentes === 0) {
    console.log('Nada foi gravado: nenhuma série obtida e não há arquivo anterior');
  } else if (!mudou) {
    console.log(`Sem novidade: ${path.relative(process.cwd(), SAIDA)} fica como está (${presentes}/${total} séries, atualizadoEm ${anterior?.atualizadoEm ?? '?'})`);
  } else {
    const saida = {
      atualizadoEm: agora,
      fontes: {
        ivar: { nome: FONTE_IVAR, pagina: paginaIvar },
        fipezap: {
          nome: FONTE_FIPEZAP, planilha: URL_FIPEZAP, aba: ABA_FIPEZAP, modificadaEm,
          nota: 'Os preços são médias móveis de três meses (nota da planilha). 1D a 4D = número de dormitórios; 4D inclui 4 ou mais.',
        },
      },
      series,
    };
    /* grava em arquivo temporário e renomeia: nunca deixa um JSON pela metade */
    fs.mkdirSync(path.dirname(SAIDA), { recursive: true });
    const temporario = SAIDA + '.tmp';
    fs.writeFileSync(temporario, JSON.stringify(saida, null, 1) + '\n');
    fs.renameSync(temporario, SAIDA);
    console.log(`Gravado ${path.relative(process.cwd(), SAIDA)}: ${presentes}/${total} séries, atualizadoEm ${saida.atualizadoEm}`);
  }

  if (problemas.length) throw new Error(`${problemas.join('; ')}. O site continua com o último dado bom dessas séries.`);
}

if (require.main === module) {
  principal().catch(erro => {
    console.error(`ERRO: ${erro.message}`);
    process.exit(1);
  });
} else {
  module.exports = { lerTabelaIvar, diferencaDoAcumulado, lerDoZip, lerAba, lerLocacao, principal, SAIDA };
}
