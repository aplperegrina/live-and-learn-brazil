/* Destination Services — a lista que o visitante monta.
   Lê as caixas marcadas no formulário [data-lista], mostra a lista no fecho
   e escreve o pedido de escopo no link REQUEST A SCOPE (mailto com os itens
   no corpo). Sem JavaScript o link continua funcionando, só sem a lista.
   Os textos ficam em data-* no HTML para a versão traduzida não mexer aqui. */

const form = document.querySelector('[data-lista]');
if (form) {
  const frase = form.querySelector('[data-lista-frase]');
  const itens = form.querySelector('[data-lista-itens]');
  const pedido = form.querySelector('[data-pedido]');
  const caixas = Array.from(form.querySelectorAll('input[type="checkbox"][name="service"]'));

  const textos = {
    vazio: frase ? frase.textContent : '',
    marcados: form.dataset.textoMarcados || 'Your list so far:',
    assunto: form.dataset.assunto || 'Request a scope — Destination Services',
    abertura: form.dataset.abertura || 'Hello,\n\nI would like a scope, timing and one price for the following destination services:',
    fecho: form.dataset.fecho || 'Thank you.',
  };
  const email = pedido ? pedido.getAttribute('href').replace(/^mailto:/, '').split('?')[0] : '';

  function marcados() {
    return caixas.filter((c) => c.checked).map((c) => c.value);
  }

  function atualizar() {
    const lista = marcados();

    if (itens) {
      itens.textContent = '';
      lista.forEach((nome) => {
        const li = document.createElement('li');
        li.textContent = nome;
        itens.appendChild(li);
      });
      itens.hidden = lista.length === 0;
    }
    if (frase) {
      frase.textContent = lista.length ? textos.marcados : textos.vazio;
    }
    if (pedido && email) {
      const corpo = lista.length
        ? `${textos.abertura}\n\n${lista.map((nome) => `- ${nome}`).join('\n')}\n\n${textos.fecho}`
        : '';
      let href = `mailto:${email}?subject=${encodeURIComponent(textos.assunto)}`;
      if (corpo) href += `&body=${encodeURIComponent(corpo)}`;
      pedido.setAttribute('href', href);
    }
  }

  form.addEventListener('change', atualizar);
  // Sem backend: o formulário nunca é enviado; o pedido sai pelo link
  form.addEventListener('submit', (e) => e.preventDefault());
  atualizar();
}
