/* ==========================================================================
   Gráfico de linhas, com área em degradê opcional por série.
   Usado por: juros (24 meses), valores indexados, câmbio (diário) e IBC-Br.

   cfg: { meses:[AAAA-MM...], desdeZero, formato(v), descricao,
          series:[{ id, nomeCurto, cor, largura, area,
                    valores:[v|null por mês]  ou  pontos:[[x fracionário, v]] }] }

   Movimento: linha traçada por stroke-dasharray/dashoffset (getTotalLength)
   em --mov-linha, easing quase linear; área entra por opacity com 2s de
   atraso em --mov-area; ponta aparece no fim. Sem máscara perseguindo a linha.
   ========================================================================== */

import { svgEl, tempo, curva, quandoVisivel, aoRedimensionar, escala, reduzMovimento } from './animacao.js';

const MESES = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

export function desenharLinhas(raiz, cfg) {
  const n = cfg.meses.length;
  const todos = cfg.series.flatMap(s => (s.pontos ? s.pontos.map(p => p[1]) : s.valores.filter(v => v != null)));
  if (!todos.length) return;
  const y0 = escala(Math.min(...todos), Math.max(...todos), cfg.desdeZero);

  let animado = reduzMovimento;
  let tracos = [], areas = [], pontas = [];

  function desenhar() {
    const W = Math.max(240, Math.round(raiz.clientWidth));
    const H = Math.max(150, Math.round(raiz.clientHeight) || 260);
    const estreito = W < 420;
    const formatoEixo = cfg.formatoEixo || cfg.formato;
    const larguraRotulo = Math.max(...y0.marcas.map(v => formatoEixo(v).length)) * 7 + 8;
    const m = { top: 12, right: estreito ? 10 : 16, bottom: 40, left: larguraRotulo };
    const larguraUtil = W - m.left - m.right;
    const alturaUtil = H - m.top - m.bottom;
    const x = i => m.left + (i * larguraUtil) / (n - 1);
    const y = v => m.top + (1 - (v - y0.inicio) / (y0.fim - y0.inicio)) * alturaUtil;
    const yBase = y(y0.inicio);

    raiz.innerHTML = '';
    const svg = svgEl('svg', { width: W, height: H, viewBox: `0 0 ${W} ${H}`, role: 'img', 'aria-label': cfg.rotuloAcessivel }, raiz);
    const defs = svgEl('defs', {}, svg);
    const grade = svgEl('g', {}, svg);

    for (const v of y0.marcas) {
      svgEl('line', { x1: m.left, x2: W - m.right, y1: y(v), y2: y(v), class: v === y0.inicio ? 'eixo-base' : 'eixo-linha' }, grade);
      svgEl('text', { x: m.left - 8, y: y(v) + 4, 'text-anchor': 'end', class: 'eixo-rotulo' }, grade).textContent = formatoEixo(v);
    }

    /* eixo x: mês, e o ano em janeiro (e no primeiro mês, quando cabe) */
    const passo = estreito ? 6 : W < 640 ? 3 : 1;
    for (let i = 0; i < n; i++) {
      const ym = cfg.meses[i];
      const janeiro = ym.endsWith('-01');
      if (i % passo === 0) {
        svgEl('text', { x: x(i), y: yBase + 18, 'text-anchor': i === 0 ? 'start' : i === n - 1 ? 'end' : 'middle', class: 'eixo-rotulo' }, grade)
          .textContent = MESES[+ym.slice(5, 7) - 1];
      }
      if (janeiro || (i === 0 && !estreito)) {
        svgEl('text', { x: x(i), y: yBase + 33, 'text-anchor': i === 0 ? 'start' : 'middle', class: 'eixo-rotulo eixo-rotulo--ano' }, grade).textContent = ym.slice(0, 4);
      }
    }

    tracos = []; areas = []; pontas = [];
    const camadaAreas = svgEl('g', {}, svg);
    const camadaLinhas = svgEl('g', {}, svg);

    cfg.series.forEach(s => {
      const cor = `var(--dado-${s.cor})`;
      const coordenadas = s.pontos
        ? [s.pontos.map(([px, v]) => [x(px), y(v)])]
        : (() => { const segs = []; let seg = []; s.valores.forEach((v, i) => { if (v == null) { if (seg.length) segs.push(seg); seg = []; } else seg.push([x(i), y(v)]); }); if (seg.length) segs.push(seg); return segs; })();
      if (!coordenadas.length || !coordenadas[0].length) return;

      const d = coordenadas.map(seg => seg.map(([px, py], j) => `${j ? 'L' : 'M'}${px.toFixed(1)} ${py.toFixed(1)}`).join('')).join('');
      if (s.area) {
        const idGrad = `grad-${cfg.id}-${s.id}`;
        const grad = svgEl('linearGradient', { id: idGrad, x1: 0, y1: 0, x2: 0, y2: 1 }, defs);
        svgEl('stop', { offset: '0%', style: `stop-color:${cor};stop-opacity:.38` }, grad);
        svgEl('stop', { offset: '100%', style: `stop-color:${cor};stop-opacity:0` }, grad);
        const dArea = coordenadas.map(seg => `M${seg[0][0].toFixed(1)} ${yBase.toFixed(1)}` + seg.map(([px, py]) => `L${px.toFixed(1)} ${py.toFixed(1)}`).join('') + `L${seg[seg.length - 1][0].toFixed(1)} ${yBase.toFixed(1)}Z`).join('');
        areas.push(svgEl('path', { d: dArea, style: `fill:url(#${idGrad})`, class: 'serie-area' }, camadaAreas));
      }
      tracos.push(svgEl('path', { d, class: 'serie-linha', style: `stroke:${cor};stroke-width:${s.largura || 2}` }, camadaLinhas));
      const fim = coordenadas[coordenadas.length - 1]; const [px, py] = fim[fim.length - 1];
      pontas.push(svgEl('circle', { cx: px.toFixed(1), cy: py.toFixed(1), r: 3.2, style: `fill:${cor}`, class: 'serie-ponta' }, camadaLinhas));
    });

    if (animado) return;
    for (const t of tracos) { const L = t.getTotalLength(); t.style.strokeDasharray = L; t.style.strokeDashoffset = L; }
    for (const a of areas) a.style.opacity = 0;
    for (const p of pontas) p.style.opacity = 0;
  }

  function animar() {
    if (animado) return;
    animado = true;
    const dLinha = tempo('--mov-linha', 20000), dArea = tempo('--mov-area', 6000);
    const easeLinha = curva('--curva-linha', 'cubic-bezier(.2,0,.35,1)'), ease = curva('--curva', 'cubic-bezier(.33,0,.15,1)');
    for (const t of tracos) { const L = t.getTotalLength(); t.animate([{ strokeDashoffset: L }, { strokeDashoffset: 0 }], { duration: dLinha, easing: easeLinha, fill: 'forwards' }); }
    for (const a of areas) a.animate([{ opacity: 0 }, { opacity: 1 }], { duration: dArea, delay: 2000, easing: ease, fill: 'forwards' });
    for (const p of pontas) p.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 600, delay: dLinha, easing: ease, fill: 'forwards' });
  }

  desenhar();
  quandoVisivel(raiz, estadoFinal => { if (!estadoFinal) animar(); });
  aoRedimensionar(raiz, desenhar);
}
