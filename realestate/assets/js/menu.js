/* ==========================================================================
   Menu do cabeçalho: marca a seção em que o leitor está.

   Vale para os links com data-espia. A seção atual é a última cujo topo já
   cruzou a linha a 35% da altura da tela; no fim da página, vale a última
   seção visível, mesmo que não tenha chegado à linha. O link recebe
   aria-current="true", que o CSS usa para manter o fio bronze aceso.
   ========================================================================== */

const links = new Map();
document.querySelectorAll('.secoes a[data-espia]').forEach(a => {
  const alvo = document.getElementById(a.getAttribute('href').slice(1) || '-');
  if (alvo) links.set(alvo, a);
});

let pedido = 0;
function marcar() {
  pedido = 0;
  const linha = innerHeight * 0.35;
  const noFim = scrollY + innerHeight >= document.documentElement.scrollHeight - 2;
  let atual = null;
  for (const alvo of links.keys()) {
    const topo = alvo.getBoundingClientRect().top;
    if (topo <= linha || (noFim && topo < innerHeight)) atual = alvo;
  }
  for (const [alvo, a] of links) {
    if (alvo === atual) a.setAttribute('aria-current', 'true');
    else a.removeAttribute('aria-current');
  }
}
/* no máximo uma medição por quadro, por mais que a rolagem dispare */
const pedir = () => { if (!pedido) pedido = requestAnimationFrame(marcar); };

if (links.size) {
  addEventListener('scroll', pedir, { passive: true });
  addEventListener('resize', pedir);
  marcar();
}
