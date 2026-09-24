<?php
/* Live & Learn Brazil — /contact/ : formulário curto de contato (nome, telefone, e-mail,
   mensagem) nos quatro idiomas (?lang=en|pt|it|zh), com WhatsApp e WeChat no fim.
   Página PHP para carimbar a hora de abertura (anti-robô) e mostrar ?sent=1 / ?error=1
   sem depender de JavaScript. ?about=destination&items=a|b|c preenche a mensagem com a
   lista de serviços marcada na página Destination Services. */
declare(strict_types=1);
require_once __DIR__ . '/../full-advisory/config.php';
require_once __DIR__ . '/textos.php';
header('Cache-Control: no-store');

$lang = ct_idioma((string) ($_GET['lang'] ?? 'en'));
$t = CT_TEXTOS[$lang];
$home = $lang === 'en' ? '../' : '../' . $lang . '/';

$emitido = time();
$carimbo = $emitido . '.' . hash_hmac('sha256', (string) $emitido, fa_segredo());
$estado = (($_GET['sent'] ?? '') === '1') ? 'enviado' : ((($_GET['error'] ?? '') === '1') ? 'erro' : '');

/* Mensagem pré-preenchida vinda da Destination Services */
$pedido_about = (string) ($_GET['about'] ?? '');
$about = in_array($pedido_about, ['destination', 'innovation', 'portuguese'], true) ? $pedido_about : '';
$mensagem = '';
if ($about === 'innovation') {
    $mensagem = $t['inovacao_abertura'] . "\n";
}
if ($about === 'portuguese') {
    $mensagem = $t['portugues_abertura'] . "\n";
}
if ($about === 'destination') {
    $itens = [];
    foreach (explode('|', (string) ($_GET['items'] ?? '')) as $item) {
        $item = trim((string) preg_replace('/[^\P{C}]/u', '', $item));
        if ($item !== '' && mb_check_encoding($item, 'UTF-8')) $itens[] = mb_substr($item, 0, 120);
        if (count($itens) >= 30) break;
    }
    $mensagem = $t['destino_abertura'] . "\n" . implode('', array_map(fn($i) => "- $i\n", $itens));
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="<?= e($t['html_lang']) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($t['titulo']) ?></title>
  <meta name="description" content="<?= e($t['descricao']) ?>">
  <link rel="canonical" href="https://liveandlearnbrazil.com<?= e(ct_pagina($lang)) ?>">
  <link rel="alternate" hreflang="en" href="https://liveandlearnbrazil.com/contact/">
  <link rel="alternate" hreflang="pt-BR" href="https://liveandlearnbrazil.com/contact/?lang=pt">
  <link rel="alternate" hreflang="it" href="https://liveandlearnbrazil.com/contact/?lang=it">
  <link rel="alternate" hreflang="zh-Hans" href="https://liveandlearnbrazil.com/contact/?lang=zh">
  <link rel="alternate" hreflang="x-default" href="https://liveandlearnbrazil.com/contact/">
  <link rel="icon" type="image/svg+xml" href="../img/live-and-learn-brazil-favicon.svg">
  <link rel="stylesheet" href="../css/styles.css?v=20260921a">
  <link rel="stylesheet" href="../css/due-diligence.css?v=20260921c">
  <link rel="stylesheet" href="../css/full-advisory.css?v=20260921b">
  <link rel="stylesheet" href="../css/contact.css?v=20260921a">
</head>
<body class="<?= $estado !== '' ? 'fa-estado-' . $estado : '' ?>">
<div class="page dd fa ct">

  <!-- ===== MENU ===== -->
  <header class="header">
    <a class="header-logo" href="<?= e($home) ?>"><img src="../img/live-and-learn-brazil-horizontal-light.svg" alt="Live &amp; Learn Brazil"></a>
    <nav class="menu" aria-label="<?= e($t['menu_aria']) ?>">
      <a href="../intercultural-due-diligence/"><?= e($t['menu'][0]) ?></a>
      <a href="../innovation-ties/"><?= e($t['menu'][1]) ?></a>
      <a href="../brazilian-portuguese-and-communication/"><?= e($t['menu'][2]) ?></a>
      <a href="<?= e($home) ?>#destination"><?= e($t['menu'][3]) ?></a>
    </nav>
  </header>

  <main>

  <!-- ===== ABERTURA (gelo) ===== -->
  <section class="fa-abertura ct-abertura" aria-labelledby="ct-h1">
    <nav class="dd-rotulo fa-migalhas" aria-label="Breadcrumb">
      <a href="<?= e($home) ?>"><?= e($t['migalha_home']) ?></a> <span aria-hidden="true">/</span>
      <span aria-current="page"><?= e($t['migalha_aqui']) ?></span>
    </nav>
    <p class="dd-rotulo dd-rotulo--sub"><?= e($t['rotulo']) ?></p>
    <h1 id="ct-h1"><?= e($t['h1']) ?></h1>
    <p class="fa-lead"><?= e($t['lead']) ?></p>
  </section>

  <div class="ct-corpo">

    <!-- ===== ESTADO: RECEBIDO (substitui o formulário) ===== -->
    <section class="fa-recebido" aria-labelledby="fa-t-recebido">
      <p class="dd-rotulo dd-rotulo--sub"><?= e($t['recebido_rotulo']) ?></p>
      <h2 id="fa-t-recebido" tabindex="-1"><?= e($t['recebido_h2']) ?></h2>
      <p><?= e($t['recebido_p']) ?></p>
      <a class="dd-cta" href="<?= e($home) ?>"><?= e($t['voltar']) ?> <span aria-hidden="true">↗</span></a>
    </section>

    <!-- ===== FORMULÁRIO ===== -->
    <form class="fa-form ct-form" id="fa-form" method="post" action="send.php" accept-charset="UTF-8">

      <p class="fa-erro" id="fa-erro" role="alert" tabindex="-1"><?= e($t['erro']) ?></p>

      <input type="hidden" name="t" value="<?= e($carimbo) ?>">
      <input type="hidden" name="lang" value="<?= e($lang) ?>">
      <input type="hidden" name="about" value="<?= e($about) ?>">
      <div class="fa-armadilha" aria-hidden="true">
        <label for="fa-website">Website</label>
        <input type="text" id="fa-website" name="website" tabindex="-1" autocomplete="off">
      </div>

      <div class="fa-campo">
        <label for="ct-name"><?= e($t['nome']) ?> <span class="fa-req"><?= e($t['obrigatorio']) ?></span></label>
        <input type="text" id="ct-name" name="name" required autocomplete="name" maxlength="200">
      </div>
      <div class="fa-campo">
        <label for="ct-phone"><?= e($t['telefone']) ?> <span class="fa-req"><?= e($t['opcional']) ?></span></label>
        <input type="tel" id="ct-phone" name="phone" autocomplete="tel" maxlength="60">
      </div>
      <div class="fa-campo">
        <label for="ct-email"><?= e($t['email']) ?> <span class="fa-req"><?= e($t['obrigatorio']) ?></span></label>
        <input type="email" id="ct-email" name="email" required autocomplete="email" maxlength="254">
      </div>
      <div class="fa-campo">
        <label for="ct-message"><?= e($t['mensagem']) ?> <span class="fa-req"><?= e($t['obrigatorio']) ?></span></label>
        <textarea id="ct-message" name="message" rows="6" required maxlength="5000" placeholder="<?= e($t['mensagem_dica']) ?>"><?= e($mensagem) ?></textarea>
      </div>

      <div class="fa-acoes ct-acoes">
        <button class="dd-btn fa-enviar" type="submit" data-enviando="<?= e($t['enviando']) ?>"><?= e($t['enviar']) ?></button>
        <p class="ct-nota"><?= e($t['nota']) ?></p>
      </div>
    </form>

    <!-- ===== CELULAR: WhatsApp e WeChat (champagne-claro) ===== -->
    <section class="ct-mobile" aria-labelledby="ct-t-mobile">
      <h2 class="dd-rotulo dd-rotulo--sub" id="ct-t-mobile"><?= e($t['mobile_rotulo']) ?></h2>
      <p class="ct-mobile-p"><?= e($t['mobile_p']) ?></p>
      <div class="ct-canais">
        <a class="ct-canal" href="<?= e(CT_WHATSAPP_LINK) ?>">
          <svg class="ct-icone" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2.5a9.5 9.5 0 0 0-8.2 14.3L2.5 21.5l4.9-1.3A9.5 9.5 0 1 0 12 2.5Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8.6 7.6c.3-.3.7-.3.9 0l1 1.5c.2.3.1.6-.1.8l-.6.6c.6 1.3 1.6 2.3 2.9 2.9l.6-.6c.2-.2.5-.3.8-.1l1.5 1c.3.2.3.6 0 .9l-.7.8c-.5.5-1.3.6-2 .3a10 10 0 0 1-5-5c-.3-.7-.2-1.5.3-2l.4-.5Z" fill="currentColor"/></svg>
          <span class="ct-canal-texto">
            <span class="ct-canal-nome">WhatsApp</span>
            <span class="ct-canal-valor"><?= e(CT_WHATSAPP_NUMERO) ?></span>
          </span>
        </a>
        <div class="ct-canal ct-canal--wechat">
          <svg class="ct-icone" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9.5 4C5.4 4 2.3 6.7 2.3 10c0 1.9 1 3.6 2.6 4.7l-.7 2.2 2.5-1.3c.8.2 1.6.4 2.5.4h.4A5.6 5.6 0 0 1 9 14c0-3.4 3.2-6.1 7.1-6.1h.5C15.9 5.6 13 4 9.5 4Z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M16 9.3c-3.3 0-6 2.2-6 4.9s2.7 4.9 6 4.9c.7 0 1.4-.1 2-.3l2.1 1.1-.6-1.8c1.3-.9 2.1-2.3 2.1-3.9 0-2.7-2.7-4.9-5.6-4.9Z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><circle cx="7.3" cy="9" r=".9" fill="currentColor"/><circle cx="11" cy="9" r=".9" fill="currentColor"/><circle cx="14.3" cy="13.5" r=".8" fill="currentColor"/><circle cx="17.7" cy="13.5" r=".8" fill="currentColor"/></svg>
          <span class="ct-canal-texto">
            <span class="ct-canal-nome"><?= e($t['wechat']) ?></span>
            <img class="ct-qr" src="../img/qr-wechat.png" alt="<?= e($t['wechat_alt']) ?>" width="150" height="150">
          </span>
        </div>
      </div>
    </section>

  </div>

  </main>

  <!-- ===== RODAPÉ ===== -->
  <footer class="footer">
    <svg class="footer-curva" aria-hidden="true" viewBox="0 0 1000 1000" preserveAspectRatio="none"><path d="M0 900 C 250 900, 400 640, 520 540 S 800 180, 1000 100" fill="none" stroke="#DCC792" stroke-width="2" stroke-linecap="round" vector-effect="non-scaling-stroke"/></svg>
    <a class="footer-logo" href="<?= e($home) ?>"><img src="../img/live-and-learn-brazil-horizontal-dark.svg" alt="Live &amp; Learn Brazil"></a>
    <a class="footer-site" href="https://liveandlearnbrazil.com">liveandlearnbrazil.com</a>
  </footer>

</div>

<!-- Envio por fetch: troca o formulário pelo estado "recebido" ou mostra o erro, sem sair da página.
     Sem JavaScript, o formulário envia normalmente e send.php redireciona para ?sent=1 ou ?error=1. -->
<script>
(function () {
  var form = document.getElementById('fa-form');
  if (!form || !window.fetch || !window.FormData) return;
  var corpo = document.body;
  var botao = form.querySelector('.fa-enviar');
  var erro = document.getElementById('fa-erro');
  var recebido = document.getElementById('fa-t-recebido');
  function mostrar(estado) {
    corpo.classList.remove('fa-estado-erro', 'fa-estado-enviado');
    corpo.classList.add('fa-estado-' + estado);
    var alvo = estado === 'enviado' ? recebido : erro;
    requestAnimationFrame(function () {
      var y = alvo.getBoundingClientRect().top + window.pageYOffset - 32;
      window.scrollTo(0, Math.max(0, y));
      alvo.focus({ preventScroll: true });
    });
  }
  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    corpo.classList.remove('fa-estado-erro');
    var rotulo = botao.textContent;
    botao.disabled = true;
    botao.setAttribute('aria-busy', 'true');
    botao.textContent = botao.getAttribute('data-enviando') || rotulo;
    fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) { mostrar(j && j.ok ? 'enviado' : 'erro'); })
      .catch(function () { mostrar('erro'); })
      .then(function () { botao.disabled = false; botao.removeAttribute('aria-busy'); botao.textContent = rotulo; });
  });
})();
</script>
</body>
</html>
