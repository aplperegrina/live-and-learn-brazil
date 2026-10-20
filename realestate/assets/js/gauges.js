/* ==========================================================================
   Gauges radiais: um arco por indicador, com o valor no centro e o rótulo
   ao lado. Empilhados na vertical.

   cfg: { itens:[{ id, rotulo, valor, fracao (0–1), cor, detalhe }] }

   Movimento: stroke-dasharray animado do zero até o valor em --mov-arco,
   escalonado 260ms entre arcos.
   ========================================================================== */

import { svgEl, tempo, curva, quandoVisivel, aoRedimensionar, reduzMovimento } from './animacao.js';

/** Quebra `texto` em até `maxLinhas` linhas de até `maxChars` caracteres; a última ganha "…" se sobrar texto. */
function quebrar(texto, maxChars, maxLinhas) {
  const palavras = String(texto).split(' ');
  const linhas = [];
  let atual = '';
  for (const p of palavras) {
    const tentativa = atual ? `${atual} ${p}` : p;
    if (tentativa.length <= maxChars || !atual) atual = tentativa;
    else { linhas.push(atual); atual = p; if (linhas.length === maxLinhas) break; }
  }
  if (linhas.length < maxLinhas) linhas.push(atual);
  else linhas[maxLinhas - 1] = linhas[maxLinhas - 1].slice(0, Math.max(0, maxChars - 1)) + '…';
  const ultima = linhas[linhas.length - 1];
  if (ultima.length > maxChars) linhas[linhas.length - 1] = ultima.slice(0, Math.max(0, maxChars - 1)) + '…';
  return linhas;
}

export function desenharGauges(raiz, cfg) {
  let animado = reduzMovimento;
  let arcos = []; /* { el, C, fracao } */

  function desenhar() {
    const W = Math.max(220, Math.round(raiz.clientWidth));
    const H = Math.max(150, Math.round(raiz.clientHeight) || 260);
    const k = cfg.itens.length;
    const linha = H / k;
    const R = Math.max(22, Math.min(40, linha / 2 - 8, W / 6));
    const grossura = R > 30 ? 8 : 6;
    const cx = R + grossura / 2 + 2;

    raiz.innerHTML = '';
    const svg = svgEl('svg', { width: W, height: H, viewBox: `0 0 ${W} ${H}`, role: 'img', 'aria-label': cfg.rotuloAcessivel }, raiz);
    arcos = [];
    cfg.itens.forEach((g, i) => {
      const cy = linha * i + linha / 2;
      const C = 2 * Math.PI * R;
      svgEl('circle', { cx, cy, r: R, class: 'gauge-fundo', style: `stroke-width:${grossura}` }, svg);
      const arco = svgEl('circle', { cx, cy, r: R, class: 'gauge-arco', style: `stroke:var(--dado-${g.cor});stroke-width:${grossura};stroke-dasharray:${animado ? C * g.fracao : 0} ${C}`, transform: `rotate(-90 ${cx} ${cy})` }, svg);
      arcos.push({ el: arco, C, fracao: g.fracao });
      svgEl('text', { x: cx, y: cy + 5, 'text-anchor': 'middle', class: 'gauge-valor' }, svg).textContent = g.valor;
      const xTexto = cx + R + grossura + 12;
      const disponivel = W - xTexto - 4;
      /* rótulo em até duas linhas; detalhe em uma, com reticências se não couber */
      const linhasRotulo = quebrar(g.rotulo, Math.floor(disponivel / 6.6), 2);
      const yRotulo = linhasRotulo.length > 1 ? cy - 9 : cy - 2;
      const rotulo = svgEl('text', { x: xTexto, y: yRotulo, class: 'gauge-rotulo' }, svg);
      linhasRotulo.forEach((l, j) => { svgEl('tspan', { x: xTexto, dy: j ? 15 : 0 }, rotulo).textContent = l; });
      const yDetalhe = yRotulo + 15 * linhasRotulo.length + 2;
      if (yDetalhe < cy + R) {
        svgEl('text', { x: xTexto, y: yDetalhe, class: 'gauge-detalhe' }, svg).textContent = quebrar(g.detalheCurto, Math.floor(disponivel / 5.6), 1)[0];
      }
    });
  }

  function animar() {
    if (animado) return;
    animado = true;
    const d = tempo('--mov-arco', 2400), ease = curva('--curva', 'cubic-bezier(.33,0,.15,1)');
    arcos.forEach((a, i) => a.el.animate([{ strokeDasharray: `0 ${a.C}` }, { strokeDasharray: `${a.C * a.fracao} ${a.C}` }], { duration: d, delay: i * 260, easing: ease, fill: 'forwards' }));
  }

  desenhar();
  quandoVisivel(raiz, estadoFinal => { if (!estadoFinal) animar(); });
  aoRedimensionar(raiz, desenhar);
}
