<?php
/* Live & Learn Brazil — /brazilian-portuguese-and-communication/
   Página do serviço de português e comunicação. Os dois formulários (abertura e
   fecho) são o formulário de contato padrão do site: postam em /contact/send.php
   com about=portuguese e voltam para cá com ?sent=1 quando não há JavaScript.
   A ilustração a lápis entra em PNG sem papel, já com o esvanecimento embutido. */
declare(strict_types=1);
header('Cache-Control: no-store');

$raiz = '../';
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/* O convite: um botão para a página de contato padrão, que traz o WhatsApp
   e o WeChat logo abaixo do formulário. */
function bp_convite(string $raiz, string $rotulo, string $texto, string $botao): void
{ ?>
      <div class="bp-convite">
        <p class="dd-rotulo bp-rotulo"><?= e($rotulo) ?></p>
        <p class="bp-convite-texto"><?= e($texto) ?></p>
        <a class="dd-btn" href="<?= e($raiz) ?>contact/?about=portuguese"><?= e($botao) ?></a>
        <p class="bp-convite-nota">WhatsApp and WeChat are on that page too.</p>
      </div>
<?php }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Brazilian Portuguese and Communication — Live &amp; Learn Brazil</title>
  <meta name="description" content="Three USP professors teaching Brazilian Portuguese and communication: Survival Portuguese, Business Portuguese and tailored courses for families, executives and teams, online or in person in São Paulo.">
  <link rel="canonical" href="https://liveandlearnbrazil.com/brazilian-portuguese-and-communication/">
  <link rel="alternate" hreflang="en" href="https://liveandlearnbrazil.com/brazilian-portuguese-and-communication/">
  <link rel="alternate" hreflang="x-default" href="https://liveandlearnbrazil.com/brazilian-portuguese-and-communication/">
  <link rel="icon" type="image/svg+xml" href="../img/live-and-learn-brazil-favicon.svg">
  <link rel="stylesheet" href="../css/styles.css?v=20260921a">
  <link rel="stylesheet" href="../css/due-diligence.css?v=20260923c">
  <link rel="stylesheet" href="../css/full-advisory.css?v=20260921b">
  <link rel="stylesheet" href="../css/portuguese.css?v=20260924b">
</head>
<body>
<div class="page dd fa bp">

  <!-- ===== MENU ===== -->
  <header class="header">
    <a class="header-logo" href="<?= e($raiz) ?>"><img src="<?= e($raiz) ?>img/live-and-learn-brazil-horizontal-light.svg" alt="Live &amp; Learn Brazil"></a>
    <nav class="menu" aria-label="Services">
      <a href="<?= e($raiz) ?>intercultural-due-diligence/">Intercultural Due Diligence</a>
      <a href="<?= e($raiz) ?>innovation-ties/">Innovation Ties</a>
      <a href="./" aria-current="page">Brazilian Portuguese and Communication</a>
      <a href="<?= e($raiz) ?>destination-services/">Destination Services</a>
    </nav>
  </header>

  <main>

  <!-- ===== ABERTURA: título e formulário lado a lado ===== -->
  <section class="bp-abertura" aria-labelledby="bp-h1">
    <div class="bp-abertura-texto">
      <nav class="dd-rotulo bp-migalhas" aria-label="Breadcrumb">
        <a href="<?= e($raiz) ?>">Home</a> <span aria-hidden="true">/</span>
        <span>Expertise</span> <span aria-hidden="true">/</span>
        <span aria-current="page">Brazilian Portuguese and Communication</span>
      </nav>
      <p class="dd-rotulo dd-rotulo--claro">Brazilian Portuguese and Communication</p>
      <h1 class="bp-h1" id="bp-h1">Brazilian Portuguese and Communication Skills</h1>
      <p class="bp-lead">Three professors, all graduates of the University of São Paulo (USP), each with more than ten years of experience teaching Portuguese as a Foreign Language and years of international experience living and working abroad. We keep refining our methodology class after class, with one goal in mind: your results.</p>
      <div class="bp-numeros">
        <div class="bp-numero"><b>3</b><span>professors from USP</span></div>
        <div class="bp-numero"><b>10+</b><span>years teaching Portuguese<br>as a Foreign Language</span></div>
        <div class="bp-numero"><b>3</b><span>courses, matched<br>to your context</span></div>
      </div>
    </div>

    <div class="bp-coluna-convite">
<?php bp_convite($raiz, 'Talk to a professor', 'Tell us who will study and what you need. We reply within two working days.', 'Ask for a class plan'); ?>
    </div>
  </section>

  <!-- ===== DOBRA 1: a conversa à esquerda, o foco à direita ===== -->
  <section class="bp-dobra bp-dobra--conversa" aria-labelledby="bp-t-foco">
    <img class="bp-ilustracao" src="../img/ilustracao-conversa.png" alt="Pencil drawing of three people talking beside a pharmacy sign" width="1240" height="523">
    <div class="bp-dobra-texto">
      <p class="dd-rotulo bp-rotulo" id="bp-t-foco">01 / Our focus</p>
      <p>Grammar and pronunciation are the starting point. Our focus is communication and the results you, your family and your team achieve through it. In any culture, word choice, intonation, facial expressions and turn-taking can radically change the outcome of a conversation. We specialize in teaching the most effective way to communicate in each context, according to your goals.</p>
      <p class="bp-citacao">Our course rests on two foundations: learning the essential rules and thriving in the contexts that matter most to you.</p>
    </div>
  </section>

  <!-- ===== MATERIAIS E METODOLOGIA ===== -->
  <section class="bp-secao bp-secao--azul" aria-labelledby="bp-t-materiais">
    <div class="bp-cabeca">
      <p class="dd-rotulo dd-rotulo--secao">02 / Our materials and methodology</p>
      <h2 id="bp-t-materiais">Everything you need between one class and the next.</h2>
    </div>
    <div class="bp-materiais">
      <article class="bp-material">
        <h3>Survival Portuguese app</h3>
        <p>An exclusive app that goes wherever you go, with ready-to-use structures and vocabulary for real situations, plus review materials. Recorded by our own professors, with accurate pronunciation and relevant cultural insights on communication.</p>
      </article>
      <article class="bp-material">
        <h3>Digital book</h3>
        <p>Built on the many materials we have studied and refined through years of practice, and adapted to your needs and learning contexts.</p>
      </article>
      <article class="bp-material">
        <h3>Live classes, online or in person</h3>
        <p>Each class is planned with the techniques best suited to your profile, whether you are a child, a teenager, a family or an executive. Both the materials and the professor's specialization are matched to your needs. Our team includes graduates in Education and in Languages and Literature, with specializations in public speaking, theater and self-expression, and business communication.</p>
      </article>
    </div>
  </section>

  <!-- ===== O APLICATIVO: três telas ===== -->
  <section class="bp-app" aria-labelledby="bp-t-app">
    <div class="bp-cabeca bp-cabeca-dupla">
      <div class="bp-cabeca">
        <p class="dd-rotulo dd-rotulo--secao">The app</p>
        <h2 id="bp-t-app">Survival Portuguese, in your pocket.</h2>
      </div>
      <p>Phrases, exercises and video scenes for the places you reach first: the supermarket, the pharmacy, the bank, your building. Every line is recorded by our professors.</p>
    </div>
    <div class="bp-telas">
      <figure class="bp-tela">
        <img src="../img/app-frases.jpg" alt="App screen: vocabulary cards for the supermarket" width="640" height="1385" loading="lazy">
        <figcaption>Phrases</figcaption>
      </figure>
      <figure class="bp-tela">
        <img src="../img/app-videos.jpg" alt="App screen: a video scene at the butcher counter with what to notice" width="640" height="1385" loading="lazy">
        <figcaption>Videos</figcaption>
      </figure>
      <figure class="bp-tela">
        <img src="../img/app-exercicios.jpg" alt="App screen: an exercise choosing what to say at the checkout" width="640" height="1385" loading="lazy">
        <figcaption>Exercises</figcaption>
      </figure>
    </div>
  </section>

  <!-- ===== OS TRÊS CURSOS ===== -->
  <section class="bp-secao bp-secao--gelo" id="courses" aria-labelledby="bp-t-cursos">
    <div class="bp-cabeca bp-cabeca-dupla">
      <div class="bp-cabeca">
        <p class="dd-rotulo dd-rotulo--secao">03 / Our courses</p>
        <h2 id="bp-t-cursos">Three ways in, one method behind them.</h2>
      </div>
      <p>Every course includes our digital book and access to our exclusive app, with AI resources to practise pronunciation, intonation, vocabulary and communicative structures between classes. Classes are dynamic and built around your development as a learner.</p>
    </div>

    <div class="bp-cursos">
      <article class="bp-curso">
        <h3>Survival Portuguese</h3>
        <p class="bp-curso-meta">10 hours · online</p>
        <p class="bp-curso-lead">For newcomers who need to handle daily life in Brazil from the very first weeks.</p>
        <ul class="bp-itens">
          <li><span>Essential structures and vocabulary for real situations: the supermarket, restaurants, transportation, the doctor, the bank, your building and your neighborhood</span></li>
          <li><span>Intonation and pronunciation practice so Brazilians understand you on the first try</span></li>
          <li><span>Cultural tips for everyday interactions: greetings, politeness, small talk and how to ask for help</span></li>
          <li><span>Access to the Survival Portuguese app, with phrases recorded by our professors that you can use on the spot</span></li>
        </ul>
      </article>

      <article class="bp-curso bp-curso--destaque">
        <h3>Business Portuguese</h3>
        <p class="bp-curso-meta">30–40 hours · online or in person · individuals or small groups</p>
        <p class="bp-curso-lead">For executives and professionals who work with Brazilian partners, clients and teams.</p>
        <ul class="bp-itens">
          <li><span>Meetings, presentations, negotiations and business lunches</span></li>
          <li><span>Emails, WhatsApp messages and phone calls in a professional register</span></li>
          <li><span>Brazilian business etiquette: building relationships, giving feedback, handling disagreement and closing agreements</span></li>
          <li><span>Vocabulary and structures adapted to your industry and role</span></li>
        </ul>
      </article>

      <article class="bp-curso">
        <h3>Tailored Language and Communication Skills</h3>
        <p class="bp-curso-meta">Online or in person · individuals, families or small groups</p>
        <p class="bp-curso-lead">A course designed around your goals and the contexts that matter most to you. Possible focuses include:</p>
        <ul class="bp-itens">
          <li><span><b>Families and children:</b> support for children adapting to schools in São Paulo, communication with teachers and school staff, and Portuguese for the whole family's daily routine</span></li>
          <li><span><b>Intercultural communication:</b> for expatriates who want to communicate with confidence across the many contexts of Brazilian life, from social gatherings to public services</span></li>
          <li><span><b>Public speaking:</b> presentations, speeches and self-expression, drawing on our team's background in theater and public communication</span></li>
          <li><span><b>Communication within your organization:</b> interacting with different departments and levels of hierarchy, understanding unwritten rules and adapting your style to Brazilian corporate culture</span></li>
        </ul>
      </article>
    </div>
  </section>

  <!-- ===== DOBRA 2: famílias e escolas, ilustração à direita ===== -->
  <section class="bp-dobra bp-dobra--placas" aria-labelledby="bp-t-familias">
    <div class="bp-dobra-texto">
      <p class="dd-rotulo bp-rotulo" id="bp-t-familias">04 / Families and schools</p>
      <p class="bp-grande">Two of our professors also have years of experience mediating communication between expatriate families in São Paulo and their children's schools, supporting international students and families as they adapt and laying the groundwork for a successful study journey in Brazil.</p>
    </div>
    <img class="bp-ilustracao" src="../img/ilustracao-placas.png" alt="Pencil drawing of road signs to Santos, Cubatão and Praia Grande above a viaduct" width="1240" height="369">
  </section>

  <!-- ===== FECHO: o formulário completo ===== -->
  <section class="bp-fecho" id="contact" aria-label="Start here">
    <div class="bp-fecho-texto">
      <p class="dd-rotulo bp-rotulo">Start here</p>
      <p>A class plan starts with a conversation: who is learning, the contexts that matter most, and the results you want. We reply within two working days, in Portuguese, English, Mandarin or Italian.</p>
      <div class="bp-whatsapp">
        <p>Prefer mobile? Write to us on WhatsApp.</p>
        <a href="https://wa.me/5511954395027">+55 11 95439 5027</a>
      </div>
    </div>

    <div class="bp-coluna-convite">
<?php bp_convite($raiz, 'Ready when you are', 'Fill in the form on our contact page and we will come back with a class plan.', 'Ask for a class plan'); ?>
    </div>
  </section>

  </main>

  <!-- ===== RODAPÉ ===== -->
  <footer class="footer">
    <svg class="footer-curva" aria-hidden="true" viewBox="0 0 1000 1000" preserveAspectRatio="none"><path d="M0 900 C 250 900, 400 640, 520 540 S 800 180, 1000 100" fill="none" stroke="#DCC792" stroke-width="2" stroke-linecap="round" vector-effect="non-scaling-stroke"/></svg>
    <a class="footer-logo" href="<?= e($raiz) ?>"><img src="<?= e($raiz) ?>img/live-and-learn-brazil-horizontal-dark.svg" alt="Live &amp; Learn Brazil"></a>
    <a class="footer-site" href="https://liveandlearnbrazil.com">liveandlearnbrazil.com</a>
  </footer>

</div>
</body>
</html>
