<?php
/* Live & Learn Brazil — /full-advisory/ (página PHP para carimbar a hora de abertura
   e mostrar o estado ?sent=1 / ?error=1 sem depender de JavaScript). */
declare(strict_types=1);
require_once __DIR__ . '/config.php';
header('Cache-Control: no-store');
$emitido = time();
$carimbo = $emitido . '.' . hash_hmac('sha256', (string) $emitido, fa_segredo());
$estado = (($_GET['sent'] ?? '') === '1') ? 'enviado' : ((($_GET['error'] ?? '') === '1') ? 'erro' : '');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Full advisory — Live &amp; Learn Brazil</title>
  <meta name="description" content="Tell us about your operation. We will come back with a proposed scope for on-site intercultural advisory, sized to your company.">
  <link rel="canonical" href="https://liveandlearnbrazil.com/full-advisory/">
  <link rel="icon" type="image/svg+xml" href="../img/live-and-learn-brazil-favicon.svg">
  <link rel="stylesheet" href="../css/styles.css?v=20260921a">
  <link rel="stylesheet" href="../css/due-diligence.css?v=20260921c">
  <link rel="stylesheet" href="../css/full-advisory.css?v=20260921b">
</head>
<body class="<?= $estado !== '' ? 'fa-estado-' . $estado : '' ?>">
<div class="page dd fa">

  <!-- ===== MENU ===== -->
  <header class="header">
    <a class="header-logo" href="../"><img src="../img/live-and-learn-brazil-horizontal-light.svg" alt="Live &amp; Learn Brazil"></a>
    <nav class="menu" aria-label="Services">
      <a href="../intercultural-due-diligence/">Intercultural Due Diligence</a>
      <a href="../innovation-ties/">Innovation Ties</a>
      <a href="../#portuguese">Business Portuguese</a>
      <a href="../#destination">Destination Services</a>
    </nav>
  </header>

  <main>

  <!-- ===== ABERTURA (gelo) ===== -->
  <section class="fa-abertura" aria-labelledby="fa-h1">
    <nav class="dd-rotulo fa-migalhas" aria-label="Breadcrumb">
      <a href="../">Home</a> <span aria-hidden="true">/</span>
      <span>Expertise</span> <span aria-hidden="true">/</span>
      <a href="../intercultural-due-diligence/">Intercultural Due Diligence</a> <span aria-hidden="true">/</span>
      <span aria-current="page">Full advisory</span>
    </nav>
    <p class="dd-rotulo dd-rotulo--sub">FULL ADVISORY</p>
    <h1 id="fa-h1">Tell us about <br>your operation.</h1>
    <p class="fa-lead">Full advisory is scoped for each company. What you tell us here is what we use to prepare a first proposal: the size of the work, the people involved and the time it needs. We reply within two working days.</p>
  </section>

  <div class="fa-corpo">

    <!-- ===== O QUE INCLUI (champagne-claro, ao lado do formulário) ===== -->
    <aside class="fa-inclui" aria-labelledby="fa-t-inclui">
      <h2 class="dd-rotulo" id="fa-t-inclui">What it includes</h2>
      <ul class="fa-lista">
        <li>On-site interviews, by level and department</li>
        <li>Observation of real processes and a process map</li>
        <li>Team charters built with the people who will use them</li>
        <li>Workshops</li>
        <li>Organisational communication protocols, written to align with ISO 9001 guidance</li>
      </ul>
      <p class="fa-nota">If you have already bought a diagnostic, say so below — the report shortens the scoping considerably.</p>
    </aside>

    <div class="fa-coluna">

      <!-- ===== ESTADO: RECEBIDO (substitui o formulário) ===== -->
      <section class="fa-recebido" aria-labelledby="fa-t-recebido">
        <p class="dd-rotulo dd-rotulo--sub">RECEIVED</p>
        <h2 id="fa-t-recebido" tabindex="-1">Thank you. We will reply <br>within two working days.</h2>
        <p>A copy of your message has been sent to the email you gave. If nothing arrives, check your spam folder, or <a class="fa-link" href="../contact/">use the contact form</a>.</p>
        <a class="dd-cta" href="../intercultural-due-diligence/">Back to Intercultural Due Diligence <span aria-hidden="true">↗</span></a>
      </section>

      <!-- ===== FORMULÁRIO ===== -->
      <form class="fa-form" id="fa-form" method="post" action="send.php" accept-charset="UTF-8">

        <!-- Estado: erro (aparece com ?error=1 ou quando o envio por fetch falha) -->
        <p class="fa-erro" id="fa-erro" role="alert" tabindex="-1">Something went wrong and your message was not sent. Please try again, or <a class="fa-link" href="../contact/">use the contact form</a>.</p>

        <!-- Carimbo de hora assinado (anti-robô) e honeypot fora do fluxo de leitura e de tabulação -->
        <input type="hidden" name="t" value="<?= htmlspecialchars($carimbo, ENT_QUOTES, 'UTF-8') ?>">
        <div class="fa-armadilha" aria-hidden="true">
          <label for="fa-website">Website</label>
          <input type="text" id="fa-website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <fieldset class="fa-secao">
          <legend><h2 class="dd-rotulo dd-rotulo--sub">01 / ABOUT YOU</h2></legend>
          <div class="fa-campo">
            <label for="fa-name">Full name <span class="fa-req">(required)</span></label>
            <input type="text" id="fa-name" name="name" required autocomplete="name" maxlength="200">
          </div>
          <div class="fa-campo">
            <label for="fa-role">Role <span class="fa-req">(required)</span></label>
            <input type="text" id="fa-role" name="role" required autocomplete="organization-title" maxlength="200">
          </div>
          <div class="fa-campo">
            <label for="fa-email">Work email <span class="fa-req">(required)</span></label>
            <input type="email" id="fa-email" name="email" required autocomplete="email" maxlength="254">
          </div>
          <div class="fa-campo">
            <label for="fa-phone">Phone or WhatsApp <span class="fa-req">(optional)</span></label>
            <input type="tel" id="fa-phone" name="phone" autocomplete="tel" maxlength="60" aria-describedby="fa-phone-ajuda">
            <p class="fa-ajuda" id="fa-phone-ajuda">Include the country code.</p>
          </div>
        </fieldset>

        <fieldset class="fa-secao">
          <legend><h2 class="dd-rotulo dd-rotulo--sub">02 / ABOUT THE OPERATION</h2></legend>
          <div class="fa-campo">
            <label for="fa-company">Company <span class="fa-req">(required)</span></label>
            <input type="text" id="fa-company" name="company" required autocomplete="organization" maxlength="200">
          </div>
          <div class="fa-campo">
            <label for="fa-hq">Head office country <span class="fa-req">(required)</span></label>
            <input type="text" id="fa-hq" name="hq_country" required autocomplete="country-name" maxlength="120">
          </div>
          <div class="fa-campo">
            <label for="fa-location">Where the operation is in Brazil <span class="fa-req">(required)</span></label>
            <input type="text" id="fa-location" name="location" required placeholder="City and state" maxlength="200">
          </div>
          <div class="fa-campo">
            <label for="fa-employees">Number of employees in Brazil <span class="fa-req">(required)</span></label>
            <select id="fa-employees" name="employees" required>
              <option value="">Select</option>
              <option>Up to 50</option>
              <option>51–200</option>
              <option>201–500</option>
              <option>More than 500</option>
            </select>
          </div>
          <fieldset class="fa-grupo">
            <legend>Departments you think are involved <span class="fa-req">(optional)</span></legend>
            <div class="fa-opcoes fa-opcoes--grade">
              <label class="fa-opcao"><input type="checkbox" name="departments[]" value="Leadership"><span>Leadership</span></label>
              <label class="fa-opcao"><input type="checkbox" name="departments[]" value="Operations and production"><span>Operations and production</span></label>
              <label class="fa-opcao"><input type="checkbox" name="departments[]" value="Engineering and technical"><span>Engineering and technical</span></label>
              <label class="fa-opcao"><input type="checkbox" name="departments[]" value="Sales and marketing"><span>Sales and marketing</span></label>
              <label class="fa-opcao"><input type="checkbox" name="departments[]" value="Finance and administration"><span>Finance and administration</span></label>
              <label class="fa-opcao"><input type="checkbox" name="departments[]" value="HR"><span>HR</span></label>
              <label class="fa-opcao"><input type="checkbox" name="departments[]" value="Legal and compliance"><span>Legal and compliance</span></label>
              <label class="fa-opcao"><input type="checkbox" name="departments[]" value="Other"><span>Other</span></label>
            </div>
          </fieldset>
        </fieldset>

        <fieldset class="fa-secao">
          <legend><h2 class="dd-rotulo dd-rotulo--sub">03 / WHAT IS HAPPENING</h2></legend>
          <div class="fa-campo">
            <label for="fa-description">Describe what you are seeing <span class="fa-req">(required)</span></label>
            <textarea id="fa-description" name="description" rows="6" required maxlength="5000" placeholder="Where work slows down, where decisions come back, where the two sides read things differently. A few lines are enough."></textarea>
          </div>
          <fieldset class="fa-grupo">
            <legend>Have you done our diagnostic? <span class="fa-req">(required)</span></legend>
            <div class="fa-opcoes">
              <label class="fa-opcao"><input type="radio" name="diagnostic" value="Yes" required><span>Yes</span></label>
              <label class="fa-opcao"><input type="radio" name="diagnostic" value="Not yet, but we plan to"><span>Not yet, but we plan to</span></label>
              <label class="fa-opcao"><input type="radio" name="diagnostic" value="No"><span>No</span></label>
            </div>
          </fieldset>
          <div class="fa-campo">
            <label for="fa-start">When would you like to start? <span class="fa-req">(required)</span></label>
            <select id="fa-start" name="start" required>
              <option value="">Select</option>
              <option>Within a month</option>
              <option>In one to three months</option>
              <option>Later this year</option>
              <option>Still exploring</option>
            </select>
          </div>
        </fieldset>

        <fieldset class="fa-secao">
          <legend><h2 class="dd-rotulo dd-rotulo--sub">04 / HOW WE SHOULD REPLY</h2></legend>
          <fieldset class="fa-grupo">
            <legend>Preferred language <span class="fa-req">(required)</span></legend>
            <div class="fa-opcoes">
              <label class="fa-opcao"><input type="radio" name="language" value="Portuguese" required><span>Portuguese</span></label>
              <label class="fa-opcao"><input type="radio" name="language" value="English"><span>English</span></label>
              <label class="fa-opcao"><input type="radio" name="language" value="Mandarin"><span>Mandarin</span></label>
              <label class="fa-opcao"><input type="radio" name="language" value="Italian"><span>Italian</span></label>
            </div>
          </fieldset>
          <div class="fa-consentimento">
            <label class="fa-opcao"><input type="checkbox" name="consent" value="yes" required><span>I agree that Live &amp; Learn Brazil may use this information to reply to my enquiry. It will not be shared or used for anything else. <span class="fa-req">(required)</span></span></label>
          </div>
        </fieldset>

        <div class="fa-acoes">
          <button class="dd-btn fa-enviar" type="submit">Send</button>
        </div>
      </form>

    </div>
  </div>

  <!-- ===== FECHO (ardósia), emenda no rodapé ===== -->
  <section class="dd-fecho fa-fecho" aria-labelledby="fa-t-fecho">
    <h2 id="fa-t-fecho">Prefer to talk first?</h2>
    <p class="dd-fecho-texto">Use the contact form, or WhatsApp. We work in Portuguese, English, Mandarin and Italian.</p>
    <a class="fa-email" href="../contact/">CONTACT US</a>
  </section>

  </main>

  <!-- ===== RODAPÉ ===== -->
  <footer class="footer">
    <svg class="footer-curva" aria-hidden="true" viewBox="0 0 1000 1000" preserveAspectRatio="none"><path d="M0.0 900.0L9.0 901.5L18.1 902.7L27.0 903.5L36.0 904.1L44.9 904.3L53.9 904.2L62.8 903.8L71.7 903.1L80.7 902.1L89.5 900.7L98.5 899.1L107.3 897.2L116.2 895.0L125.1 892.4L133.9 889.7L142.7 886.6L151.5 883.2L160.4 879.6L169.2 875.7L178.0 871.5L186.7 867.1L195.4 862.4L204.2 857.5L212.9 852.4L221.6 847.0L230.3 841.4L238.9 835.5L247.6 829.5L256.2 823.2L264.8 816.7L273.4 810.1L281.9 803.2L290.5 796.2L299.0 788.9L307.5 781.5L316.0 773.9L324.4 766.2L332.9 758.2L341.3 750.2L349.6 742.0L358.0 733.6L366.4 725.1L374.7 716.5L383.0 707.7L391.3 698.9L399.6 689.9L407.8 680.8L416.0 671.6L424.2 662.3L432.4 652.9L440.6 643.5L448.7 633.9L456.9 624.3L465.0 614.6L473.1 604.8L481.1 595.0L489.2 585.1L497.2 575.1L505.3 565.2L513.3 555.2L521.3 545.2L529.3 535.1L537.3 525.1L545.2 514.9L553.2 504.9L561.1 494.8L569.0 484.6L576.9 474.6L584.9 464.5L592.8 454.5L600.7 444.4L608.6 434.4L616.5 424.4L624.3 414.5L632.2 404.6L640.1 394.8L647.9 385.1L655.8 375.4L663.6 365.7L671.5 356.2L679.4 346.7L687.2 337.3L695.1 328.0L703.0 318.8L710.9 309.7L718.7 300.7L726.6 291.8L734.6 283.1L742.5 274.5L750.4 265.9L758.3 257.5L766.3 249.3L774.2 241.3L782.2 233.3L790.2 225.5L798.2 218.0L806.3 210.5L814.3 203.3L822.4 196.2L830.5 189.3L838.7 182.6L846.8 176.1L855.0 169.8L863.2 163.7L871.5 157.8L879.8 152.2L888.1 146.7L896.4 141.5L904.8 136.6L913.3 131.9L921.7 127.4L930.2 123.2L938.8 119.3L947.4 115.6L956.0 112.3L964.8 109.2L973.5 106.4L982.3 103.9L991.1 101.8L1000.0 100.0" fill="none" stroke="#DCC792" stroke-width="2" stroke-linecap="round" vector-effect="non-scaling-stroke"/></svg>
    <a class="footer-logo" href="../"><img src="../img/live-and-learn-brazil-horizontal-dark.svg" alt="Live &amp; Learn Brazil"></a>
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
    // Espera o reflow (o formulário some, a página encolhe) antes de rolar até ao aviso
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
    botao.textContent = 'Sending…';
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
