/* ==========================================================================
   Live & Learn Brazil — dados do dashboard imobiliário de São Paulo

   Os números vivem em dois arquivos de realestate/dados, gravados todo dia
   útil pelo GitHub Actions (7h de Brasília):
   - dados-sgs.json: séries do Banco Central (scripts/buscar-dados.js);
   - dados-alugueis.json: IVAR, da FGV, e FipeZap de São Paulo, da Fipe
     (scripts/buscar-alugueis.js).
   Este módulo só carrega os JSON e os transforma nas séries que cada gráfico
   precisa. Nenhum número fica escrito nos componentes nem aqui.

   Toda leitura exibida leva a DATA da observação (ultimo.data), nunca a data
   de hoje; os JSON também trazem atualizadoEm, a data em que foram buscados.
   ========================================================================== */

const URL_DADOS = new URL('../../dados/dados-sgs.json', import.meta.url);
const URL_ALUGUEIS = new URL('../../dados/dados-alugueis.json', import.meta.url);
const MESES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const JANELA = 24; /* meses mostrados nos gráficos de linha */

/** Baixa o JSON sem deixar o navegador servir uma cópia velha. */
export async function carregarDados() {
  const resposta = await fetch(URL_DADOS, { cache: 'no-cache' });
  if (!resposta.ok) throw new Error(`dados-sgs.json: HTTP ${resposta.status}`);
  return resposta.json();
}

/** O mesmo para as séries de aluguel (IVAR e FipeZap), que têm arquivo próprio. */
export async function carregarAlugueis() {
  const resposta = await fetch(URL_ALUGUEIS, { cache: 'no-cache' });
  if (!resposta.ok) throw new Error(`dados-alugueis.json: HTTP ${resposta.status}`);
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
export const fmtReais = v => 'R$ ' + v.toFixed(2);
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

/** Variação mensal (histórico [[aaaa-mm-dd, %]]) → acumulado em 12 meses, por mês (AAAA-MM → %).
    Um mês só entra se os 11 anteriores existem e são seguidos. */
function acumulado12(historico) {
  const mensais = [...porMes(historico).entries()];
  const mapa = new Map();
  for (let i = 11; i < mensais.length; i++) {
    if (janelaDeMeses(mensais[i][0], 12)[0] !== mensais[i - 11][0]) continue;   /* buraco na série */
    let fator = 1;
    for (let k = i - 11; k <= i; k++) fator *= 1 + mensais[k][1] / 100;
    mapa.set(mensais[i][0], +((fator - 1) * 100).toFixed(2));
  }
  return mapa;
}

/** Valores de um mapa (AAAA-MM → valor) alinhados à janela: um por mês ou null. */
const alinhar = (mapa, meses) => meses.map(m => (mapa.has(m) ? mapa.get(m) : null));

/** Última leitura não nula de uma série alinhada à janela, com o mês dela. */
function ultimaLeitura(valores, meses) {
  for (let i = valores.length - 1; i >= 0; i--) {
    if (valores[i] != null) return { valor: valores[i], data: meses[i] + '-01', rotulo: rotuloMes(meses[i] + '-01') };
  }
  return null;
}

/* ==========================================================================
   Construtores, um por gráfico. Todos devolvem null se faltar série.
   Os seis primeiros leem dados-sgs.json; os de aluguel, dados-alugueis.json.
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

/** 7. Lease indexers: IPCA e IGP-M acumulados em 12 meses, 24 meses (linhas).
    O IPCA em 12 meses vem pronto do SGS (13522); o do IGP-M é composto da série mensal (189),
    que por isso é buscada com mais histórico que as outras (ver scripts/buscar-dados.js). */
export function montarIndexadores(dados) {
  const s = dados.series || {};
  if (!s.igpm || !s.ipca12m) return null;
  const meses = janelaDeMeses(s.igpm.ultimo.data.slice(0, 7), JANELA);
  const vIgpm = alinhar(acumulado12(s.igpm.historico), meses);
  const vIpca = mensal(s.ipca12m, meses);
  const uIgpm = ultimaLeitura(vIgpm, meses), uIpca = ultimaLeitura(vIpca, meses);
  if (!uIgpm || !uIpca) return null;
  return {
    id: 'indexadores',
    meses,
    desdeZero: false,
    formato: fmtPct,
    formatoEixo: eixoPct,
    descricao: 'IPCA and IGP-M accumulated in 12 months, percent',
    /* ordem de desenho: a primeira fica embaixo, a última por cima */
    series: [
      {
        id: 'igpm12', nome: 'IGP-M acumulado em 12 meses', nomeCurto: 'IGP-M', cor: 'verde', unidade: '% 12m',
        codigoSgs: s.igpm.codigo, fonte: `BCB/SGS ${s.igpm.codigo}, ${s.igpm.nome}, compounded over 12 months`,
        valores: vIgpm, ultimo: uIgpm, area: false, largura: 2,
      },
      { ...base(s.ipca12m, 'ipca12', 'IPCA acumulado em 12 meses', 'IPCA', 'ardosia', '% 12m'), valores: vIpca, ultimo: uIpca, area: true, largura: 2.5 },
    ],
  };
}

/** 8. Asking rents by bedrooms: FipeZap (Fipe), cidade de São Paulo, R$ por m² ao mês
    (barras horizontais), com a média da cidade como linha de referência. */
export function montarAluguel(alugueis) {
  const s = alugueis.series || {};
  const quebras = [['aluguelSp1d', '1 bedroom'], ['aluguelSp2d', '2 bedrooms'], ['aluguelSp3d', '3 bedrooms'], ['aluguelSp4d', '4 or more']];
  if (!s.aluguelSp || quebras.some(([id]) => !s[id])) return null;
  const mes = s.aluguelSp.ultimo.data;
  if (quebras.some(([id]) => s[id].ultimo.data !== mes)) return null;   /* leituras de meses diferentes não se comparam */
  const quando = rotuloMes(mes);
  const media = s.aluguelSp.ultimo.valor;
  const doze = s.aluguelSp12m && s.aluguelSp12m.ultimo.data === mes ? s.aluguelSp12m.ultimo.valor : null;
  const categorias = quebras.map(([id, rotulo]) => ({ rotulo, valor: s[id].ultimo.valor, detalhe: `${fmtReais(s[id].ultimo.valor)} per m² a month, ${quando}` }));
  const descricao = 'Asking rent per square metre a month in the city of São Paulo, by number of bedrooms';
  return {
    id: 'aluguel',
    cor: 'verde',
    formato: fmtReais,
    descricao,
    categorias,
    referencia: { rotulo: 'All sizes', valor: media },
    /* leituras da chave que não são barras: a média da cidade e a variação em 12 meses */
    chaves: [
      { id: 'aluguel-media', valor: fmtReais(media), rotulo: quando },
      ...(doze == null ? [] : [{ id: 'aluguel-12', valor: fmtPct(doze), rotulo: quando }]),
    ],
    rotuloAcessivel: `${descricao}, ${quando}. ${categorias.map(g => `${g.rotulo}: ${fmtReais(g.valor)}`).join('; ')}. City average for all sizes: ${fmtReais(media)}${doze == null ? '' : `, ${fmtPct(doze)} in 12 months`}.`,
  };
}

/** 9. Rents under contract: IVAR (FGV), variação mensal dos últimos 6 meses (barras divergentes),
    com o acumulado em 12 meses na chave. */
export function montarIvar(alugueis) {
  const s = alugueis.series || {};
  if (!s.ivar) return null;
  const mes = s.ivar.ultimo.data;
  const meses = janelaDeMeses(mes.slice(0, 7), 6);
  const quando = rotuloMes(mes);
  const doze = s.ivar12m ? porMes(s.ivar12m.historico).get(mes.slice(0, 7)) : null;
  return {
    id: 'ivar',
    meses,
    formato: fmtPct1,
    formatoEixo: eixoPctSinal,
    descricao: 'IVAR, change on the previous month, percent',
    series: [{
      id: 'ivar', nome: 'IVAR', nomeCurto: 'IVAR', cor: 'ardosia', unidade: '% a.m.', fonte: s.ivar.fonte,
      valores: mensal(s.ivar, meses), ultimo: { valor: s.ivar.ultimo.valor, data: mes, rotulo: quando },
    }],
    chaves: doze == null ? [] : [{ id: 'ivar12', valor: fmtPct(doze), rotulo: quando }],
    complemento: doze == null ? '' : `${fmtPct(doze)} in 12 months`,
  };
}

/** 10. Building costs: INCC acumulado em 12 meses, 13 meses (colunas). Composto da série mensal (192). */
export function montarCustoDeConstrucao(dados) {
  const s = dados.series || {};
  if (!s.incc) return null;
  const meses = janelaDeMeses(s.incc.ultimo.data.slice(0, 7), 13);
  const valores = alinhar(acumulado12(s.incc.historico), meses);
  const ultimo = ultimaLeitura(valores, meses);
  if (!ultimo) return null;
  return {
    id: 'construcao',
    meses,
    formato: fmtPct,
    formatoEixo: eixoPct,
    descricao: 'INCC accumulated in 12 months, percent',
    series: [{
      id: 'incc12', nome: 'INCC acumulado em 12 meses', nomeCurto: 'INCC', cor: 'verde-esc', unidade: '% 12m',
      codigoSgs: s.incc.codigo, fonte: `BCB/SGS ${s.incc.codigo}, ${s.incc.nome}, compounded over 12 months`,
      valores, ultimo,
    }],
  };
}
