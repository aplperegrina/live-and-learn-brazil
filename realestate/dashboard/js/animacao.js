/* ==========================================================================
   Utilidades comuns aos gráficos: SVG, tokens de movimento, observador de
   viewport e escala de eixo. Sem biblioteca.

   Movimento (brief, seção 4): cada gráfico anima quando entra na viewport
   (threshold 0.35), uma vez; com prefers-reduced-motion nasce no estado final.
   ========================================================================== */

export const NS = 'http://www.w3.org/2000/svg';
export const reduzMovimento = matchMedia('(prefers-reduced-motion: reduce)').matches;

export function svgEl(tag, atributos = {}, pai) {
  const n = document.createElementNS(NS, tag);
  for (const [k, v] of Object.entries(atributos)) {
    if (k === 'style') n.style.cssText = v;
    else n.setAttribute(k, v);
  }
  if (pai) pai.appendChild(n);
  return n;
}

/** Lê um token de tempo do :root ("20s" ou "400ms") em milissegundos. */
export function tempo(token, padraoMs) {
  const v = getComputedStyle(document.documentElement).getPropertyValue(token).trim();
  if (!v) return padraoMs;
  return v.endsWith('ms') ? parseFloat(v) : parseFloat(v) * 1000;
}
export function curva(token, padrao) {
  return getComputedStyle(document.documentElement).getPropertyValue(token).trim() || padrao;
}

/**
 * Chama `fn(estadoFinal)` uma única vez: de imediato com `true` se o usuário
 * prefere menos movimento; senão com `false` quando 35% do elemento entrar na tela.
 */
export function quandoVisivel(el, fn) {
  if (reduzMovimento) { fn(true); return; }
  const observador = new IntersectionObserver(entradas => {
    if (entradas.some(e => e.isIntersecting)) { observador.disconnect(); fn(false); }
  }, { threshold: 0.35 });
  observador.observe(el);
}

/** Redesenha quando a largura do contêiner muda de verdade (rotação, janela). */
export function aoRedimensionar(el, fn) {
  if (!('ResizeObserver' in window)) return;
  let largura = el.clientWidth;
  new ResizeObserver(() => {
    if (Math.abs(el.clientWidth - largura) > 40) { largura = el.clientWidth; fn(); }
  }).observe(el);
}

/** Escala "redonda" para um eixo: início, fim e marcas com no máximo 5 intervalos. */
export function escala(min, max, desdeZero = false) {
  if (desdeZero) min = Math.min(0, min);
  const passos = [0.05, 0.1, 0.2, 0.25, 0.5, 1, 2, 4, 5, 10, 20, 25, 50, 100, 200, 500];
  const amplitude = Math.max(max - min, 1e-9);
  const passo = passos.find(p => amplitude / p <= 5) ?? passos[passos.length - 1];
  const inicio = desdeZero ? 0 : Math.floor(min / passo) * passo;
  const fim = Math.ceil(max / passo) * passo;
  const marcas = [];
  for (let v = inicio; v <= fim + 1e-9; v += passo) marcas.push(+v.toFixed(6));
  return { inicio, fim: marcas[marcas.length - 1], passo, marcas };
}

/** Preenche uma tabela (para leitor de tela) com meses × séries. */
export function preencherTabela(tabela, meses, series, rotuloMes, formato) {
  if (!tabela) return;
  tabela.querySelectorAll('thead, tbody, tfoot').forEach(e => e.remove());
  const thead = `<thead><tr><th scope="col">Month</th>${series.map(s => `<th scope="col">${s.nomeCurto}</th>`).join('')}</tr></thead>`;
  const linhas = meses.map((ym, i) =>
    `<tr><th scope="row">${rotuloMes(ym + '-01')}</th>${series.map(s => {
      const v = s.valores ? s.valores[i] : null;
      return `<td>${v == null ? '—' : formato(v)}</td>`;
    }).join('')}</tr>`).join('');
  const fontes = `<tfoot><tr><td colspan="${series.length + 1}">${series.map(s => `${s.nomeCurto}: ${s.fonte}, last observation ${s.ultimo.rotulo}.`).join(' ')}</td></tr></tfoot>`;
  tabela.insertAdjacentHTML('beforeend', thead + `<tbody>${linhas}</tbody>` + fontes);
}
