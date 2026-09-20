/* ==========================================================================
   Barras divergentes: variação mensal de índices, positiva para cima e
   negativa para baixo da linha do zero. Um grupo por mês, uma barra por série.

   cfg: { meses:[AAAA-MM...], formato(v), series:[{ id, nomeCurto, cor, valores }] }

   Movimento: cada barra cresce a partir do eixo em --mov-barra, escalonadas
   70ms entre meses e 30ms entre séries. Keyframes com unidade (px), porque
   propriedades geométricas de SVG sem unidade são descartadas.
   ========================================================================== */

import { svgEl, tempo, curva, quandoVisivel, aoRedimensionar, escala, reduzMovimento } from './animacao.js';

const MESES = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

export function desenharBarras(raiz, cfg) {
  const valores = cfg.series.flatMap(s => s.valores.filter(v => v != null));
  if (!valores.length) return;
  const maior = Math.max(...valores.map(Math.abs), 0.1);
  const y0 = escala(-maior, maior);

  let animado = reduzMovimento;
  let barras = []; /* { rect, y, h } */

  function desenhar() {
    const W = Math.max(240, Math.round(raiz.clientWidth));
    const H = Math.max(150, Math.round(raiz.clientHeight) || 240);
    const m = { top: 10, right: 8, bottom: 30, left: 44 };
    const larguraUtil = W - m.left - m.right, alturaUtil = H - m.top - m.bottom;
    const y = v => m.top + (1 - (v - y0.inicio) / (y0.fim - y0.inicio)) * alturaUtil;
    const yZero = y(0);
    const n = cfg.meses.length, k = cfg.series.length;
    const grupo = larguraUtil / n;
    const larguraBarra = Math.max(4, Math.min(16, (grupo * 0.66) / k));

    raiz.innerHTML = '';
    const svg = svgEl('svg', { width: W, height: H, viewBox: `0 0 ${W} ${H}`, role: 'img', 'aria-label': cfg.rotuloAcessivel }, raiz);
    const grade = svgEl('g', {}, svg);
    for (const v of y0.marcas) {
      svgEl('line', { x1: m.left, x2: W - m.right, y1: y(v), y2: y(v), class: v === 0 ? 'eixo-base' : 'eixo-linha' }, grade);
      svgEl('text', { x: m.left - 8, y: y(v) + 4, 'text-anchor': 'end', class: 'eixo-rotulo' }, grade).textContent = v === 0 ? '0' : (cfg.formatoEixo || cfg.formato)(v);
    }

    barras = [];
    cfg.meses.forEach((ym, i) => {
      const xGrupo = m.left + i * grupo + (grupo - larguraBarra * k) / 2;
      cfg.series.forEach((s, j) => {
        const v = s.valores[i];
        if (v == null) return;
        const h = Math.abs(y(v) - yZero), yTopo = v >= 0 ? yZero - h : yZero;
        const rect = svgEl('rect', { x: (xGrupo + j * larguraBarra).toFixed(1), width: (larguraBarra - 1.5).toFixed(1), y: yZero, height: 0, style: `fill:var(--dado-${s.cor})` }, svg);
        barras.push({ rect, y: yTopo, h, i, j });
      });
      svgEl('text', { x: m.left + i * grupo + grupo / 2, y: H - 10, 'text-anchor': 'middle', class: 'eixo-rotulo' }, grade)
        .textContent = `${MESES[+ym.slice(5, 7) - 1]} ${ym.slice(2, 4)}`;
    });

    if (animado) for (const b of barras) { b.rect.setAttribute('y', b.y); b.rect.setAttribute('height', b.h); }
  }

  function animar() {
    if (animado) return;
    animado = true;
    const d = tempo('--mov-barra', 1600), ease = curva('--curva', 'cubic-bezier(.33,0,.15,1)');
    for (const b of barras) {
      const yZero = b.rect.getAttribute('y');
      b.rect.animate([{ height: '0px', y: `${yZero}px` }, { height: `${b.h}px`, y: `${b.y}px` }], { duration: d, delay: b.i * 70 + b.j * 30, easing: ease, fill: 'forwards' });
    }
  }

  desenhar();
  quandoVisivel(raiz, estadoFinal => { if (!estadoFinal) animar(); });
  aoRedimensionar(raiz, desenhar);
}
