/* Destination Services — a lista que o visitante monta.
   Lê as caixas marcadas no formulário [data-lista], mostra a lista no fecho
   e leva os itens no link REQUEST A SCOPE, que abre o formulário de contato
   (/contact/?about=destination&items=a|b|c) já com a lista na mensagem.
   Sem JavaScript o link continua funcionando, só sem a lista.
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
  };
  // Endereço do formulário de contato, sem a lista (o href inicial do link)
  const base = pedido ? pedido.getAttribute('href').split('?')[0] : '';
  const pergunta = pedido ? (pedido.getAttribute('href').split('?')[1] || '') : '';

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
    if (pedido && base) {
      let href = base + (pergunta ? `?${pergunta}` : '');
      if (lista.length) href += `${pergunta ? '&' : '?'}items=${encodeURIComponent(lista.join('|'))}`;
      pedido.setAttribute('href', href);
    }
  }

  form.addEventListener('change', atualizar);
  // Sem backend aqui: o formulário nunca é enviado; o pedido segue pelo link
  form.addEventListener('submit', (e) => e.preventDefault());
  atualizar();
}
