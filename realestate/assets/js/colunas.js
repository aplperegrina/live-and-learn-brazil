/* ==========================================================================
   Colunas: uma série mensal a partir do zero, uma coluna por mês. A última
   leitura fica em tinta cheia; as anteriores, a meia tinta.

   cfg: { meses:[AAAA-MM...], formatoEixo(v), rotuloAcessivel,
          series:[{ cor, valores }] }  (só a primeira série é desenhada)

   Movimento: cada coluna cresce a partir do eixo em --mov-barra, escalonadas
   55ms entre meses. Keyframes com unidade (px), porque propriedades
   geométricas de SVG sem unidade são descartadas.
   ========================================================================== */

import { svgEl, tempo, curva, quandoVisivel, aoRedimensionar, escala, reduzMovimento, cabeAnoNoInicio } from './animacao.js?v=20261113';

const MESES = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

export function desenharColunas(raiz, cfg) {
  const serie = cfg.series[0], n = cfg.meses.length;
  const y0 = escala(0, Math.max(...serie.valores.filter(v => v != null)), true);
  let animado = reduzMovimento;
  let colunas = [];

  function desenhar() {
    const W = Math.max(240, Math.round(raiz.clientWidth));
    const H = Math.max(150, Math.round(raiz.clientHeight) || 240);
    const m = { top: 12, right: 8, bottom: 40, left: 40 };
    const larguraUtil = W - m.left - m.right, alturaUtil = H - m.top - m.bottom;
    const y = v => m.top + (1 - (v - y0.inicio) / (y0.fim - y0.inicio)) * alturaUtil;
    const yBase = y(0);
    const passo = larguraUtil / n;
    const largura = Math.max(5, Math.min(18, passo * 0.62));

    raiz.innerHTML = '';
    const svg = svgEl('svg', { width: W, height: H, viewBox: `0 0 ${W} ${H}`, role: 'img', 'aria-label': cfg.rotuloAcessivel }, raiz);
    const grade = svgEl('g', {}, svg);
    for (const v of y0.marcas) {
      svgEl('line', { x1: m.left, x2: W - m.right, y1: y(v), y2: y(v), class: v === 0 ? 'eixo-base' : 'eixo-linha' }, grade);
      svgEl('text', { x: m.left - 8, y: y(v) + 4, 'text-anchor': 'end', class: 'eixo-rotulo' }, grade).textContent = cfg.formatoEixo(v);
    }
    colunas = [];
    /* o ano do primeiro mês só entra se não encosta no do janeiro seguinte */
    const janeiroSeguinte = cfg.meses.findIndex((ym, i) => i > 0 && ym.endsWith('-01'));
    const anoNoInicio = janeiroSeguinte < 0 || cabeAnoNoInicio(janeiroSeguinte * passo, false);
    cfg.meses.forEach((ym, i) => {
      const cx = m.left + i * passo + passo / 2;
      const v = serie.valores[i];
      if (v != null) {
        const rect = svgEl('rect', { x: (cx - largura / 2).toFixed(1), width: largura.toFixed(1), y: yBase, height: 0, style: `fill:var(--dado-${serie.cor});fill-opacity:${i === n - 1 ? 1 : 0.5}` }, svg);
        colunas.push({ rect, y: y(v), h: yBase - y(v), i });
      }
      /* eixo x: um mês a cada três, contando a partir do último; o ano em janeiro e, se couber, no primeiro mês */
      if ((n - 1 - i) % 3 === 0) svgEl('text', { x: cx, y: yBase + 18, 'text-anchor': 'middle', class: 'eixo-rotulo' }, grade).textContent = MESES[+ym.slice(5, 7) - 1];
      if (ym.endsWith('-01') || (i === 0 && anoNoInicio)) svgEl('text', { x: cx, y: yBase + 33, 'text-anchor': 'middle', class: 'eixo-rotulo eixo-rotulo--ano' }, grade).textContent = ym.slice(0, 4);
    });
    if (animado) for (const c of colunas) { c.rect.setAttribute('y', c.y); c.rect.setAttribute('height', c.h); }
  }

  function animar() {
    if (animado) return;
    animado = true;
    const d = tempo('--mov-barra', 1600), ease = curva('--curva', 'cubic-bezier(.33,0,.15,1)');
    for (const c of colunas) {
      const yZero = c.rect.getAttribute('y');
      c.rect.animate([{ height: '0px', y: `${yZero}px` }, { height: `${c.h}px`, y: `${c.y}px` }], { duration: d, delay: c.i * 55, easing: ease, fill: 'forwards' });
    }
  }

  desenhar();
  quandoVisivel(raiz, estadoFinal => { if (!estadoFinal) animar(); });
  aoRedimensionar(raiz, desenhar);
}
