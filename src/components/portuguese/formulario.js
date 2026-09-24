/* Brazilian Portuguese and Communication — os dois formulários da página.
   Envia por fetch para /contact/send.php e troca o formulário pelo aviso de
   recebido, sem sair da página. Sem JavaScript o formulário envia normalmente
   e o PHP devolve a página com ?sent=1 ou ?error=1. */

const formularios = Array.from(document.querySelectorAll('[data-bp-form]'));

formularios.forEach((form) => {
  const bloco = form.parentElement;                 // a coluna que guarda o aviso e o formulário
  const recebido = bloco.querySelector('.bp-recebido');
  const erro = form.querySelector('.fa-erro');
  const botao = form.querySelector('.fa-enviar');

  form.addEventListener('submit', (ev) => {
    ev.preventDefault();
    document.body.classList.remove('fa-estado-erro');
    if (!form.reportValidity()) return;

    const rotulo = botao.textContent;
    botao.disabled = true;
    botao.setAttribute('aria-busy', 'true');
    botao.textContent = botao.getAttribute('data-enviando') || rotulo;

    fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then((r) => r.json())
      .then((j) => {
        if (!j || !j.ok) throw new Error('erro');
        // Só este bloco muda de estado: o outro formulário da página continua utilizável
        bloco.classList.add('bp-enviado');
        recebido.scrollIntoView({ block: 'center' });
        recebido.focus({ preventScroll: true });
      })
      .catch(() => {
        document.body.classList.add('fa-estado-erro');
        erro.scrollIntoView({ block: 'center' });
        erro.focus({ preventScroll: true });
      })
      .then(() => {
        botao.disabled = false;
        botao.removeAttribute('aria-busy');
        botao.textContent = rotulo;
      });
  });
});
