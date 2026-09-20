/* ==========================================================================
   Live & Learn Brazil — dados do dashboard imobiliário de São Paulo

   Os números vivem em src/data/dados-sgs.json, gravado todo dia útil por
   scripts/buscar-dados.js (GitHub Actions, 7h de Brasília). Este módulo só
   carrega o JSON e o transforma nas séries que cada gráfico precisa.
   Nenhum número fica escrito nos componentes nem aqui.

   Toda leitura exibida leva a DATA da observação (ultimo.data), nunca a data
   de hoje; o JSON também traz atualizadoEm, a data em que foi buscado.
   ========================================================================== */

const URL_DADOS = new URL('./dados-sgs.json', import.meta.url);
const MESES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const JANELA = 24; /* meses mostrados nos gráficos de linha */

/** Baixa o JSON sem deixar o navegador servir uma cópia velha. */
export async function carregarDados() {
  const resposta = await fetch(URL_DADOS, { cache: 'no-cache' });
  if (!resposta.ok) throw new Error(`dados-sgs.json: HTTP ${resposta.status}`);
  return resposta.json();
}

/* ---- formatação de datas (só para exibir) ---- */
export const rotuloDia = iso => `${+iso.slice(8, 10)} ${MESES[+iso.slice(5, 7) - 1]} ${iso.slice(0, 4)}`;
export const rotuloMes = iso => `${MESES[+iso.slice(5, 7) - 1]} ${iso.slice(0, 4)}`;
export const rotuloObservacao = (serie, iso) => (serie.periodicidade === 'diaria' ? rotuloDia(iso) : rotuloMes(iso));
/** Data-hora ISO (UTC) da busca → dia no fuso de Brasília, ex.: "19 Sep 2026". */
export function rotuloBusca(isoDataHora) {
  const p = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Sao_Paulo', year: 'numeric', month: '2-digit', day: '2-digit' })
    .formatToParts(new Date(isoDataHora));
  const g = t => p.find(x => x.type === t).value;
  return rotuloDia(`${g('year')}-${g('month')}-${g('day')}`);
}

/* ---- formatos de valor ---- */
export const fmtPct = v => v.toFixed(2) + '%';
export const fmtPct1 = v => (v > 0 ? '+' : '') + v.toFixed(2) + '%';
export const fmtIndice = v => v.toFixed(1);
export const fmtCambio = v => 'R$ ' + v.toFixed(3);
/* rótulos de eixo: menos casas, para não competir com o dado */
const eixoPct = v => +v.toFixed(2) + '%';
const eixoPctSinal = v => (v > 0 ? '+' : '') + +v.toFixed(2) + '%';
const eixoInteiro = v => String(Math.round(v));
const eixoCambio = v => 'R$ ' + v.toFixed(2);

/* ---- transformações ---- */

/** Último valor de cada mês (AAAA-MM → valor) a partir de um histórico ordenado. */
function porMes(historico, transformar = v => v) {
  const mapa = new Map();
  for (const [data, valor] of historico) mapa.set(data.slice(0, 7), transformar(valor));
  return mapa;
}

/** Os N meses (AAAA-MM) que terminam em `ultimoMes`, do mais antigo ao mais recente. */
function janelaDeMeses(ultimoMes, n) {
  let [ano, mes] = ultimoMes.split('-').map(Number);
  const meses = [];
  for (let i = 0; i < n; i++) {
    meses.unshift(`${ano}-${String(mes).padStart(2, '0')}`);
    mes--; if (mes === 0) { mes = 12; ano--; }
  }
  return meses;
}

/** Posição fracionária de uma data dentro da janela de meses (0 = início do 1º mês). */
function posicaoNaJanela(iso, meses) {
  const i = meses.indexOf(iso.slice(0, 7));
  if (i < 0) return null;
  const dia = +iso.slice(8, 10);
  const diasNoMes = new Date(+iso.slice(0, 4), +iso.slice(5, 7), 0).getDate();
  return i + (dia - 1) / diasNoMes;
}

/** CDI vem em % ao dia; anualiza em base 252 dias úteis. */
const anualizar = taxaDia => +(((1 + taxaDia / 100) ** 252 - 1) * 100).toFixed(2);

/** Descrição comum de uma série para chave, tabela e leitor de tela. */
function base(fonte, id, nome, nomeCurto, cor, unidade, transformar = v => v) {
  return {
    id, nome, nomeCurto, cor, unidade,
    codigoSgs: fonte.codigo,
    fonte: `BCB/SGS ${fonte.codigo}, ${fonte.nome}`,
    ultimo: { valor: +transformar(fonte.ultimo.valor).toFixed(3), data: fonte.ultimo.data, rotulo: rotuloObservacao(fonte, fonte.ultimo.data) },
  };
}

/** Série mensal alinhada à janela: um valor por mês ou null. */
function mensal(fonte, meses, transformar) {
  const mapa = porMes(fonte.historico, transformar);
  return meses.map(m => (mapa.has(m) ? +mapa.get(m).toFixed(3) : null));
}

/* ==========================================================================
   Construtores, um por gráfico. Todos devolvem null se faltar série.
   ========================================================================== */

/** 1. Twenty-four months of rates: Selic, CDI, IPCA 12m e juros de financiamento (linhas com área). */
export function montarCustoDoDinheiro(dados) {
  const s = dados.series || {};
  if (!s.selic || !s.cdi || !s.ipca12m || !s.jurosImob) return null;
  const meses = janelaDeMeses(s.selic.ultimo.data.slice(0, 7), JANELA);
  const serie = (fonte, id, nome, nomeCurto, cor, unidade, transformar, area, largura) =>
    ({ ...base(fonte, id, nome, nomeCurto, cor, unidade, transformar), valores: mensal(fonte, meses, transformar), area, largura });
  return {
    id: 'juros',
    meses,
    desdeZero: true,
    formato: fmtPct,
    formatoEixo: eixoPct,
    descricao: 'Benchmark rates, percent per year, month-end values',
    /* ordem de desenho: a primeira fica embaixo, a última por cima */
    series: [
      serie(s.cdi, 'cdi', 'CDI', 'CDI', 'amarelo', '% a.a.', anualizar, false, 2),
      serie(s.jurosImob, 'financiamento', 'Taxa de financiamento imobiliário', 'Mortgage', 'verde', '% a.a.', undefined, true, 2),
      serie(s.ipca12m, 'ipca', 'IPCA', 'IPCA 12m', 'coral', '% 12m', undefined, true, 2),
      serie(s.selic, 'selic', 'Selic', 'Selic', 'ardosia', '% a.a.', undefined, true, 2.5),
    ],
  };
}

/** 2. Monthly change: IPCA, IGP-M e INCC, últimos 6 meses (barras divergentes). */
export function montarVariacaoMensal(dados) {
  const s = dados.series || {};
  if (!s.ipca || !s.igpm || !s.incc) return null;
  const ultimo = [s.ipca, s.igpm, s.incc].map(f => f.ultimo.data.slice(0, 7)).sort().pop();
  const meses = janelaDeMeses(ultimo, 6);
  const serie = (fonte, id, nome, nomeCurto, cor) => ({ ...base(fonte, id, nome, nomeCurto, cor, '% a.m.'), valores: mensal(fonte, meses) });
  return {
    id: 'variacao',
    meses,
    formato: fmtPct1,
    formatoEixo: eixoPctSinal,
    descricao: 'Monthly change of three price indices, percent',
    series: [
      serie(s.ipca, 'ipca', 'IPCA', 'IPCA', 'ardosia'),
      serie(s.igpm, 'igpm', 'IGP-M', 'IGP-M', 'verde'),
      serie(s.incc, 'incc', 'INCC', 'INCC', 'amarelo'),
    ],
  };
}

/** 3. Where today sits: três gauges, todos calculados de séries reais. */
export function montarGauges(dados) {
  const s = dados.series || {};
  if (!s.selic || !s.ipca12m) return null;
  const itens = [];

  const dez = s.selic.dezAnos;
  if (dez && dez.meses && dez.meses.length >= 24) {
    const atual = s.selic.ultimo.valor;
    const abaixo = dez.meses.filter(([, v]) => v < atual).length;
    const fracao = abaixo / dez.meses.length;
    const valores = dez.meses.map(([, v]) => v);
    itens.push({
      id: 'percentil', rotulo: 'Selic against the last ten years', valor: `${Math.round(fracao * 100)}%`, fracao, cor: 'coral',
      curto: `Above ${Math.round(fracao * 100)}% of the last ten years`,
      detalhe: `Higher than ${Math.round(fracao * 100)}% of month-ends since ${rotuloMes(dez.desde + '-01')} (range ${fmtPct(Math.min(...valores))} to ${fmtPct(Math.max(...valores))}). Selic ${fmtPct(atual)} on ${rotuloDia(s.selic.ultimo.data)}.`,
    });
  }

  const real = s.selic.ultimo.valor - s.ipca12m.ultimo.valor;
  itens.push({
    id: 'juro-real', rotulo: 'Real interest rate', valor: fmtPct(real), fracao: Math.max(0, Math.min(1, real / 12)), cor: 'ardosia',
    curto: `Selic ${fmtPct(s.selic.ultimo.valor)} minus IPCA ${fmtPct(s.ipca12m.ultimo.valor)}`,
    detalhe: `Selic ${fmtPct(s.selic.ultimo.valor)} (${rotuloDia(s.selic.ultimo.data)}) minus IPCA 12m ${fmtPct(s.ipca12m.ultimo.valor)} (${rotuloMes(s.ipca12m.ultimo.data)}). Arc full at 12 points.`,
  });

  const teto = 4.5, piso = 1.5;
  const ipca = s.ipca12m.ultimo.valor;
  itens.push({
    id: 'meta', rotulo: 'Inflation against the target ceiling', valor: fmtPct(ipca), fracao: Math.max(0, Math.min(1, ipca / teto)), cor: ipca > teto || ipca < piso ? 'coral' : 'verde',
    curto: `${rotuloMes(s.ipca12m.ultimo.data)}; ceiling ${+teto.toFixed(1)}%, target 3%`,
    detalhe: `IPCA 12m ${fmtPct(ipca)} in ${rotuloMes(s.ipca12m.ultimo.data)}; the Central Bank targets 3% with a tolerance band of ${fmtPct(piso)} to ${fmtPct(teto)}. Arc full at the ${fmtPct(teto)} ceiling.`,
  });

  return { id: 'gauges', descricao: 'Three gauges: Selic percentile, real interest rate and inflation against the target ceiling', itens };
}

/** 4. Collateral values, construction costs and inflation, indexed to 100 at the start of the window. */
export function montarValoresIndexados(dados) {
  const s = dados.series || {};
  if (!s.ivgr || !s.incc || !s.ipca) return null;
  const meses = janelaDeMeses(s.ipca.ultimo.data.slice(0, 7), JANELA);

  /* IVG-R é nível: reindexa ao primeiro mês com valor na janela */
  const ivgr = mensal(s.ivgr, meses);
  const referencia = ivgr.find(v => v != null);
  const ivgrIndex = ivgr.map(v => (v == null || referencia == null ? null : +((v / referencia) * 100).toFixed(2)));

  /* INCC e IPCA são variações mensais: acumula a partir de 100 no primeiro mês */
  const acumular = fonte => {
    const mapa = porMes(fonte.historico);
    let nivel = 100; let comecou = false;
    return meses.map((m, i) => {
      if (i === 0) { comecou = mapa.has(m); return comecou ? 100 : null; }
      if (!mapa.has(m) || !comecou) return null;
      nivel = nivel * (1 + mapa.get(m) / 100);
      return +nivel.toFixed(2);
    });
  };

  const serie = (fonte, id, nome, nomeCurto, cor, valores, area, largura) =>
    ({ ...base(fonte, id, nome, nomeCurto, cor, 'index'), valores, area, largura, ultimo: { ...base(fonte, id, nome, nomeCurto, cor, '').ultimo, valor: [...valores].reverse().find(v => v != null) } });
  return {
    id: 'valores',
    meses,
    desdeZero: false,
    formato: fmtIndice,
    formatoEixo: eixoInteiro,
    descricao: `Index, ${rotuloMes(meses[0] + '-01')} = 100`,
    series: [
      serie(s.ipca, 'ipca-acum', 'IPCA acumulado', 'IPCA', 'coral', acumular(s.ipca), false, 2),
      serie(s.incc, 'incc-acum', 'INCC acumulado', 'INCC', 'verde', acumular(s.incc), false, 2),
      serie(s.ivgr, 'ivgr', 'IVG-R', 'IVG-R', 'ardosia', ivgrIndex, true, 2.5),
    ],
  };
}

/** 5. USD/BRL PTAX, diário, 24 meses (linha com área; pontos em posição fracionária). */
export function montarCambio(dados) {
  const s = dados.series || {};
  if (!s.ptax) return null;
  const meses = janelaDeMeses(s.ptax.ultimo.data.slice(0, 7), JANELA);
  const pontos = s.ptax.historico
    .map(([data, v]) => [posicaoNaJanela(data, meses), v])
    .filter(([x]) => x != null);
  return {
    id: 'cambio',
    meses,
    desdeZero: false,
    formato: fmtCambio,
    formatoEixo: eixoCambio,
    descricao: 'Reais per US dollar, PTAX selling rate, daily',
    series: [{ ...base(s.ptax, 'ptax', 'PTAX', 'USD/BRL', 'ardosia', 'BRL'), pontos, area: true, largura: 2 }],
  };
}

/** 6. IBC-Br, atividade econômica, 24 meses (linha). */
export function montarAtividade(dados) {
  const s = dados.series || {};
  if (!s.ibcbr) return null;
  const meses = janelaDeMeses(s.ibcbr.ultimo.data.slice(0, 7), JANELA);
  return {
    id: 'atividade',
    meses,
    desdeZero: false,
    formato: fmtIndice,
    formatoEixo: eixoInteiro,
    descricao: 'IBC-Br economic activity index, seasonally adjusted',
    series: [{ ...base(s.ibcbr, 'ibcbr', 'IBC-Br', 'IBC-Br', 'verde-esc', 'index'), valores: mensal(s.ibcbr, meses), area: true, largura: 2.2 }],
  };
}
