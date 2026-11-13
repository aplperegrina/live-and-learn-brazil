/* ==========================================================================
   Barras horizontais: uma categoria por linha, o rótulo à esquerda, o valor
   alinhado à direita e, se houver, uma linha de referência tracejada.

   cfg: { cor, formato(v), rotuloAcessivel,
          categorias:[{ rotulo, valor }], referencia:{ rotulo, valor } }

   Movimento: cada barra cresce da esquerda em --mov-barra, escalonadas 90ms
   entre linhas. Keyframes com unidade (px), porque propriedades geométricas
   de SVG sem unidade são descartadas.
   ========================================================================== */

import { svgEl, tempo, curva, quandoVisivel, aoRedimensionar, reduzMovimento } from './animacao.js?v=20261112';

export function desenharBarrasH(raiz, cfg) {
  const maior = Math.max(...cfg.categorias.map(g => g.valor), cfg.referencia ? cfg.referencia.valor : 0);
  let animado = reduzMovimento;
  let barras = [];

  function desenhar() {
    const W = Math.max(240, Math.round(raiz.clientWidth));
    const H = Math.max(150, Math.round(raiz.clientHeight) || 220);
    const m = { top: 24, bottom: 6 };
    const x0 = 86, reserva = 68;                      /* coluna dos rótulos à esquerda e coluna dos valores à direita */
    const larguraUtil = W - x0 - reserva;
    const linha = (H - m.top - m.bottom) / cfg.categorias.length;
    const grossura = Math.max(10, Math.min(18, linha * 0.42));
    const x = v => x0 + (v / maior) * larguraUtil;

    raiz.innerHTML = '';
    const svg = svgEl('svg', { width: W, height: H, viewBox: `0 0 ${W} ${H}`, role: 'img', 'aria-label': cfg.rotuloAcessivel }, raiz);
    barras = [];
    cfg.categorias.forEach((g, i) => {
      const cy = m.top + linha * i + linha / 2;
      svgEl('text', { x: 0, y: cy + 4, class: 'gauge-rotulo' }, svg).textContent = g.rotulo;
      svgEl('rect', { x: x0, y: (cy - grossura / 2).toFixed(1), width: larguraUtil.toFixed(1), height: grossura.toFixed(1), class: 'barra-trilho' }, svg);
      const w = x(g.valor) - x0;
      const rect = svgEl('rect', { x: x0, y: (cy - grossura / 2).toFixed(1), width: animado ? w.toFixed(1) : 0, height: grossura.toFixed(1), style: `fill:var(--dado-${cfg.cor})` }, svg);
      barras.push({ rect, w, i });
      svgEl('text', { x: W, y: cy + 5, 'text-anchor': 'end', class: 'gauge-valor' }, svg).textContent = cfg.formato(g.valor);
    });
    if (cfg.referencia) {
      const xr = x(cfg.referencia.valor);
      svgEl('line', { x1: xr, x2: xr, y1: m.top - 6, y2: H - m.bottom, class: 'ref-linha' }, svg);
      svgEl('text', { x: xr, y: m.top - 11, 'text-anchor': 'middle', class: 'ref-rotulo' }, svg).textContent = cfg.referencia.rotulo.toUpperCase();
    }
  }

  function animar() {
    if (animado) return;
    animado = true;
    const d = tempo('--mov-barra', 1600), ease = curva('--curva', 'cubic-bezier(.33,0,.15,1)');
    for (const b of barras) b.rect.animate([{ width: '0px' }, { width: `${b.w}px` }], { duration: d, delay: b.i * 90, easing: ease, fill: 'forwards' });
  }

  desenhar();
  quandoVisivel(raiz, estadoFinal => { if (!estadoFinal) animar(); });
  aoRedimensionar(raiz, desenhar);
}
