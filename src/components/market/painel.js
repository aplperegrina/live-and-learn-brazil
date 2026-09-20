/* ==========================================================================
   Painel do dashboard: carrega o JSON uma vez, monta a configuração de cada
   gráfico (src/data/marketData.js) e chama o componente certo para cada
   tile marcado com data-grafico.

   Em cada tile, a chave de séries também mostra a última leitura com a data
   da própria observação; nada de número sem data ao lado.
   ========================================================================== */

import {
  carregarDados, rotuloBusca, rotuloMes, fmtPct, fmtIndice, fmtCambio,
  montarCustoDoDinheiro, montarVariacaoMensal, montarGauges, montarValoresIndexados, montarCambio, montarAtividade,
} from '../../data/marketData.js';
import { preencherTabela } from './animacao.js';
import { desenharLinhas } from './linhas.js';
import { desenharBarras } from './barras-divergentes.js';
import { desenharGauges } from './gauges.js';

const GRAFICOS = {
  juros:     { montar: montarCustoDoDinheiro, desenhar: desenharLinhas, formatoChave: fmtPct },
  variacao:  { montar: montarVariacaoMensal,  desenhar: desenharBarras, formatoChave: v => (v > 0 ? '+' : '') + v.toFixed(2) + '%' },
  gauges:    { montar: montarGauges,          desenhar: desenharGauges },
  valores:   { montar: montarValoresIndexados, desenhar: desenharLinhas, formatoChave: fmtIndice },
  cambio:    { montar: montarCambio,          desenhar: desenharLinhas, formatoChave: fmtCambio },
  atividade: { montar: montarAtividade,       desenhar: desenharLinhas, formatoChave: fmtIndice },
};

function indisponivel(card, raiz) {
  raiz.innerHTML = '<p class="grafico-indisponivel">The Central Bank data could not be loaded right now. Try again in a few minutes.</p>';
  card.querySelectorAll('[data-valor]').forEach(e => { e.textContent = '—'; });
  card.querySelectorAll('[data-data]').forEach(e => { e.textContent = 'no data'; });
}

/** Chave de séries: preenche valor e data da última observação de cada série. */
function preencherChave(card, cfg, formato) {
  for (const s of cfg.series || []) {
    const item = card.querySelector(`[data-serie="${s.id}"]`);
    if (!item) continue;
    const valor = item.querySelector('[data-valor]'), data = item.querySelector('[data-data]');
    if (valor && s.ultimo && s.ultimo.valor != null) valor.textContent = formato(s.ultimo.valor);
    if (data && s.ultimo) data.textContent = s.ultimo.rotulo;
  }
}

/** Descrição do gráfico para leitor de tela, com as últimas leituras e datas. */
function rotuloAcessivel(cfg, formato) {
  if (cfg.itens) return `${cfg.descricao}. ${cfg.itens.map(g => `${g.rotulo}: ${g.valor}. ${g.detalhe}`).join(' ')}`;
  const periodo = cfg.meses ? `${rotuloMes(cfg.meses[0] + '-01')} to ${rotuloMes(cfg.meses[cfg.meses.length - 1] + '-01')}` : '';
  return `${cfg.descricao}, ${periodo}. Latest: ${cfg.series.map(s => `${s.nomeCurto} ${formato(s.ultimo.valor)} on ${s.ultimo.rotulo}`).join('; ')}.`;
}

async function montarPainel() {
  const areas = [...document.querySelectorAll('[data-grafico]')];
  let dados = null;
  try { dados = await carregarDados(); } catch (erro) { console.error(erro); }

  const carimbo = document.querySelector('[data-atualizado]');
  if (carimbo) carimbo.textContent = dados ? `Data fetched ${rotuloBusca(dados.atualizadoEm)}` : 'Data unavailable';

  for (const raiz of areas) {
    const card = raiz.closest('.tile') || document;
    const def = GRAFICOS[raiz.dataset.grafico];
    if (!def || !dados) { indisponivel(card, raiz); continue; }
    let cfg = null;
    try { cfg = def.montar(dados); } catch (erro) { console.error(erro); }
    if (!cfg) { indisponivel(card, raiz); continue; }

    const formato = def.formatoChave || fmtPct;
    cfg.rotuloAcessivel = rotuloAcessivel(cfg, formato);
    if (cfg.itens) {
      for (const g of cfg.itens) g.detalheCurto = g.curto || g.detalhe.split('. ')[0];
      const lista = card.querySelector('[data-gauges]');
      if (lista) lista.innerHTML = cfg.itens.map(g => `<li><b>${g.rotulo}</b>: ${g.detalhe}</li>`).join('');
    } else {
      preencherChave(card, cfg, formato);
      preencherTabela(card.querySelector('[data-tabela]'), cfg.meses, cfg.series, rotuloMes, formato);
    }
    def.desenhar(raiz, cfg);
  }
}

montarPainel();
