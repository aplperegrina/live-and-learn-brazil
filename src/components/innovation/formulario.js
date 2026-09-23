/* Innovation Ties — comportamento do formulário de pedido.
   1. Campos condicionais: aparecem (e passam a ser obrigatórios) só com a resposta que os revela.
   2. Descrição do projeto: contador de palavras, máximo 500.
   3. Botão de envio desligado até que tudo o que é obrigatório esteja preenchido.
   4. Envio por fetch: leva para a página de confirmação; se falhar, mostra o aviso
      sem recarregar, e o que foi escrito continua no lugar.
   Sem JavaScript o formulário continua funcionando: o PHP valida tudo de novo. */

const form = document.getElementById('it-form');
if (form) {
  const botao = form.querySelector('.fa-enviar');
  const erro = document.getElementById('it-erro');
  const condicionais = Array.from(form.querySelectorAll('[data-mostra-campo]'));
  const area = form.querySelector('textarea[data-palavras]');
  const contador = area ? area.parentElement.querySelector('[data-contador] span') : null;
  const maxPalavras = area ? Number(area.dataset.palavras || 0) : 0;

  function contarPalavras(texto) {
    const t = texto.trim();
    return t === '' ? 0 : t.split(/\s+/).length;
  }

  function atualizarCondicionais() {
    condicionais.forEach((bloco) => {
      const nome = bloco.dataset.mostraCampo;
      const aceitos = (bloco.dataset.mostraValores || '').split('|');
      const marcado = form.querySelector(`input[name="${nome}"]:checked`);
      const mostrar = !!marcado && aceitos.includes(marcado.value);
      bloco.hidden = !mostrar;
      // "Please name the institution(s)" é obrigatório só enquanto está à vista
      const campo = bloco.querySelector('input, textarea, select');
      if (campo) {
        campo.required = mostrar && bloco.dataset.mostraOpcional !== 'sim';
        if (!mostrar) campo.setCustomValidity('');
      }
    });
  }

  function atualizarContador() {
    if (!area || !contador) return;
    const n = contarPalavras(area.value);
    contador.textContent = String(n);
    const passou = maxPalavras > 0 && n > maxPalavras;
    area.parentElement.classList.toggle('it-passou', passou);
    area.setCustomValidity(passou ? `Please keep the description to ${maxPalavras} words.` : '');
  }

  // Grupos de escolha múltipla obrigatórios: pelo menos uma caixa marcada
  const gruposObrigatorios = Array.from(form.querySelectorAll('fieldset.it-grupo'))
    .filter((g) => g.querySelector('input[type="checkbox"]') && g.querySelector('legend .it-req'))
    .map((g) => Array.from(g.querySelectorAll('input[type="checkbox"]')));

  function gruposCompletos() {
    return gruposObrigatorios.every((caixas) => caixas.some((c) => c.checked));
  }

  function atualizarBotao() {
    botao.disabled = !(form.checkValidity() && gruposCompletos());
  }

  function atualizarTudo() {
    atualizarCondicionais();
    atualizarContador();
    atualizarBotao();
  }

  form.addEventListener('input', atualizarTudo);
  form.addEventListener('change', atualizarTudo);

  form.addEventListener('submit', (ev) => {
    ev.preventDefault();
    if (!form.checkValidity() || !gruposCompletos()) {
      const primeiro = form.querySelector(':invalid');
      if (primeiro) { primeiro.scrollIntoView({ block: 'center' }); primeiro.focus({ preventScroll: true }); }
      return;
    }
    document.body.classList.remove('fa-estado-erro');
    const rotulo = botao.textContent;
    botao.disabled = true;
    botao.setAttribute('aria-busy', 'true');
    botao.textContent = botao.getAttribute('data-enviando') || rotulo;

    fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then((r) => r.json())
      .then((j) => {
        if (j && j.ok) { window.location.href = j.redirect || '/innovation-ties/request-received/'; return; }
        throw new Error(j && j.reason ? j.reason : 'erro');
      })
      .catch(() => {
        document.body.classList.add('fa-estado-erro');
        botao.disabled = false;
        botao.removeAttribute('aria-busy');
        botao.textContent = rotulo;
        erro.scrollIntoView({ block: 'center' });
        erro.focus({ preventScroll: true });
      });
  });

  atualizarTudo();
}
