/* ==========================================================================
   Painel do dashboard: carrega os dois JSON uma vez, monta a configuração de
   cada gráfico (js/marketData.js) e chama o componente certo para cada tile
   marcado com data-grafico.

   Em cada tile, a chave de séries também mostra a última leitura com a data
   da própria observação; nada de número sem data ao lado.

   Versão dos módulos: o servidor guarda .js por 7 dias no navegador. Por isso
   todo import leva ?v=AAAAMMDD, o mesmo valor em todos os arquivos desta
   pasta e na tag <script> do index.html. Ao mudar qualquer módulo, troque o
   valor em todos de uma vez; senão quem já visitou a página mistura módulo
   novo com módulo velho.
   ========================================================================== */

import {
  carregarDados, carregarAlugueis, rotuloBusca, rotuloMes, fmtPct, fmtPct1, fmtIndice, fmtCambio,
  montarCustoDoDinheiro, montarVariacaoMensal, montarGauges, montarValoresIndexados, montarCambio, montarAtividade,
  montarIndexadores, montarAluguel, montarIvar, montarCustoDeConstrucao,
} from './marketData.js?v=20261113';
import { preencherTabela } from './animacao.js?v=20261113';
import { desenharLinhas } from './linhas.js?v=20261113';
import { desenharBarras } from './barras-divergentes.js?v=20261113';
import { desenharGauges } from './gauges.js?v=20261113';
import { desenharColunas } from './colunas.js?v=20261113';
import { desenharBarrasH } from './barras-horizontais.js?v=20261113';

/* origem: de que arquivo o gráfico lê. Sem origem, é o do Banco Central (sgs). */
const GRAFICOS = {
  juros:       { montar: montarCustoDoDinheiro,   desenhar: desenharLinhas,  formatoChave: fmtPct },
  variacao:    { montar: montarVariacaoMensal,    desenhar: desenharBarras,  formatoChave: fmtPct1 },
  gauges:      { montar: montarGauges,            desenhar: desenharGauges },
  valores:     { montar: montarValoresIndexados,  desenhar: desenharLinhas,  formatoChave: fmtIndice },
  cambio:      { montar: montarCambio,            desenhar: desenharLinhas,  formatoChave: fmtCambio },
  atividade:   { montar: montarAtividade,         desenhar: desenharLinhas,  formatoChave: fmtIndice },
  indexadores: { montar: montarIndexadores,       desenhar: desenharLinhas,  formatoChave: fmtPct },
  aluguel:     { montar: montarAluguel,           desenhar: desenharBarrasH, origem: 'alugueis' },
  ivar:        { montar: montarIvar,              desenhar: desenharBarras,  formatoChave: fmtPct1, origem: 'alugueis' },
  construcao:  { montar: montarCustoDeConstrucao, desenhar: desenharColunas, formatoChave: fmtPct },
};

const AVISOS = {
  sgs: 'The Central Bank data could not be loaded right now. Try again in a few minutes.',
  alugueis: 'This reading could not be loaded right now. Try again in a few minutes.',
};

function indisponivel(card, raiz, origem) {
  raiz.innerHTML = `<p class="grafico-indisponivel">${AVISOS[origem] || AVISOS.sgs}</p>`;
  card.querySelectorAll('[data-valor]').forEach(e => { e.textContent = '—'; });
  card.querySelectorAll('[data-data]').forEach(e => { e.textContent = 'no data'; });
}

/** Chave de séries: preenche valor e data da última observação de cada série,
    e das leituras avulsas (cfg.chaves), que já chegam formatadas. */
function preencherChave(card, cfg, formato) {
  const escrever = (id, valor, rotulo) => {
    const item = card.querySelector(`[data-serie="${id}"]`);
    if (!item) return;
    const elValor = item.querySelector('[data-valor]'), elData = item.querySelector('[data-data]');
    if (elValor && valor != null) elValor.textContent = valor;
    if (elData && rotulo) elData.textContent = rotulo;
  };
  for (const s of cfg.series || []) {
    if (s.ultimo) escrever(s.id, s.ultimo.valor != null ? formato(s.ultimo.valor) : null, s.ultimo.rotulo);
  }
  for (const c of cfg.chaves || []) escrever(c.id, c.valor, c.rotulo);
  /* item da chave que ficou sem leitura: diz isso, em vez de mostrar um rótulo sozinho */
  card.querySelectorAll('[data-serie]').forEach(item => {
    const elValor = item.querySelector('[data-valor]'), elData = item.querySelector('[data-data]');
    if (!elValor || elValor.textContent) return;
    elValor.textContent = '—';
    if (elData) elData.textContent = 'no data';
  });
}

/** Descrição do gráfico para leitor de tela, com as últimas leituras e datas. */
function rotuloAcessivel(cfg, formato) {
  if (cfg.itens) return `${cfg.descricao}. ${cfg.itens.map(g => `${g.rotulo}: ${g.valor}. ${g.detalhe}`).join(' ')}`;
  const periodo = cfg.meses ? `${rotuloMes(cfg.meses[0] + '-01')} to ${rotuloMes(cfg.meses[cfg.meses.length - 1] + '-01')}` : '';
  const leituras = cfg.series.map(s => `${s.nomeCurto} ${formato(s.ultimo.valor)} on ${s.ultimo.rotulo}`).join('; ');
  return `${cfg.descricao}, ${periodo}. Latest: ${leituras}${cfg.complemento ? `; ${cfg.complemento}` : ''}.`;
}

/** Um tile: espera o arquivo de que ele depende, monta e desenha. Qualquer falha vira aviso no próprio tile. */
async function montarTile(raiz, pedidos) {
  const card = raiz.closest('.tile') || document;
  const def = GRAFICOS[raiz.dataset.grafico];
  const origem = (def && def.origem) || 'sgs';
  try {
    const dados = def ? await pedidos[origem] : null;
    const cfg = dados ? def.montar(dados) : null;
    if (!cfg) { indisponivel(card, raiz, origem); return; }

    const formato = def.formatoChave || cfg.formato || fmtPct;
    if (!cfg.rotuloAcessivel) cfg.rotuloAcessivel = rotuloAcessivel(cfg, formato);
    if (cfg.itens) {
      /* gauges: lista com o detalhe de cada leitura */
      for (const g of cfg.itens) g.detalheCurto = g.curto || g.detalhe.split('. ')[0];
      const lista = card.querySelector('[data-gauges]');
      if (lista) lista.innerHTML = cfg.itens.map(g => `<li><b>${g.rotulo}</b>: ${g.detalhe}</li>`).join('');
    } else if (cfg.categorias) {
      /* barras por categoria: lista com o valor de cada uma */
      const lista = card.querySelector('[data-lista]');
      if (lista) lista.innerHTML = cfg.categorias.map(g => `<li><b>${g.rotulo}</b>: ${g.detalhe}</li>`).join('');
      preencherChave(card, cfg, formato);
    } else {
      preencherChave(card, cfg, formato);
      preencherTabela(card.querySelector('[data-tabela]'), cfg.meses, cfg.series, rotuloMes, formato);
    }
    def.desenhar(raiz, cfg);
  } catch (erro) {
    console.error(erro);
    indisponivel(card, raiz, origem);
  }
}

function montarPainel() {
  /* cada arquivo é pedido uma vez; se falhar, os tiles que dependem dele mostram o aviso */
  const pedir = carregar => carregar().catch(erro => { console.error(erro); return null; });
  const pedidos = { sgs: pedir(carregarDados), alugueis: pedir(carregarAlugueis) };

  const carimbo = document.querySelector('[data-atualizado]');
  if (carimbo) pedidos.sgs.then(dados => { carimbo.textContent = dados ? `Data fetched ${rotuloBusca(dados.atualizadoEm)}` : 'Data unavailable'; });

  for (const raiz of document.querySelectorAll('[data-grafico]')) montarTile(raiz, pedidos);
}

montarPainel();
