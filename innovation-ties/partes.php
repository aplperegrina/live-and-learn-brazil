<?php
/* Live & Learn Brazil — /innovation-ties/ : as partes da página, iguais nos dois
   modelos de design. Cada modelo (index.php e modelo-b/index.php) chama estas funções
   na ordem do briefing e acrescenta só a sua própria decoração.
   $raiz é o caminho até a raiz do site ('../' ou '../../'). */

declare(strict_types=1);

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function it_cabecalho(string $raiz): void
{
    $c = IT_COPY; ?>
  <header class="header">
    <a class="header-logo" href="<?= e($raiz) ?>"><img src="<?= e($raiz) ?>img/live-and-learn-brazil-horizontal-light.svg" alt="Live &amp; Learn Brazil"></a>
    <nav class="menu" aria-label="Services">
      <a href="<?= e($raiz) ?>intercultural-due-diligence/">Intercultural Due Diligence</a>
      <a href="./" aria-current="page">Innovation Ties</a>
      <a href="<?= e($raiz) ?>brazilian-portuguese-and-communication/">Brazilian Portuguese and Communication</a>
      <a href="<?= e($raiz) ?>destination-services/">Destination Services</a>
    </nav>
  </header>
<?php }

/* Hero: o texto. Cada modelo põe à volta o fundo que quiser. */
function it_hero_texto(string $raiz): void
{
    $c = IT_COPY; ?>
      <nav class="dd-rotulo it-migalhas" aria-label="Breadcrumb">
        <a href="<?= e($raiz) ?>">Home</a> <span aria-hidden="true">/</span>
        <span>Expertise</span> <span aria-hidden="true">/</span>
        <span aria-current="page">Innovation Ties</span>
      </nav>
      <p class="dd-rotulo dd-rotulo--claro"><?= e($c['rotulo']) ?></p>
      <h1 class="it-h1"><?= e($c['h1']) ?></h1>
      <p class="it-sub"><?= e($c['sub']) ?></p>
      <div class="it-hero-acoes">
        <a class="dd-btn it-btn--claro" href="#request-form"><?= e($c['cta_primario']) ?></a>
        <a class="it-link-claro" href="<?= e($raiz) ?>contact/?about=innovation"><?= e($c['cta_secundario']) ?> <span aria-hidden="true">↗</span></a>
      </div>
<?php }

function it_intro(): void
{
    $c = IT_COPY; ?>
    <div class="dd-cabeca">
      <p class="dd-rotulo dd-rotulo--secao"><?= e($c['intro_rotulo']) ?></p>
      <div class="dd-intro"><p><?= e($c['intro']) ?></p></div>
    </div>
<?php }

function it_oque(): void
{
    $c = IT_COPY; ?>
    <div class="dd-cabeca">
      <p class="dd-rotulo dd-rotulo--secao"><?= e($c['oque_rotulo']) ?></p>
      <h2 class="dd-titulo"><?= e($c['oque_titulo']) ?></h2>
    </div>
    <div class="it-oque">
      <ul class="it-lista it-lista--grande">
        <?php foreach ($c['oque_itens'] as $i): ?><li><?= e($i) ?></li><?php endforeach; ?>
      </ul>
      <aside class="it-experiencia">
        <h3 class="dd-rotulo dd-rotulo--sub"><?= e($c['exp_rotulo']) ?></h3>
        <p class="it-exp-intro"><?= e($c['exp_intro']) ?></p>
        <ul class="it-lista">
          <?php foreach ($c['exp_itens'] as $i): ?><li><?= e($i) ?></li><?php endforeach; ?>
        </ul>
      </aside>
    </div>
<?php }

/* Como funciona: a linha com pontos, como na página Intercultural Due Diligence.
   Cada etapa carrega o seu pedaço de fio, por isso a linha se recompõe sozinha
   quando as colunas mudam de 6 para 3 (tablet) ou para 1 (celular). */
function it_como(): void
{
    $c = IT_COPY; ?>
    <div class="dd-cabeca">
      <p class="dd-rotulo dd-rotulo--secao"><?= e($c['como_rotulo']) ?></p>
      <h2 class="dd-titulo"><?= e($c['como_titulo']) ?></h2>
    </div>
    <ol class="it-linha">
      <?php foreach ($c['etapas'] as $n => [$titulo, $texto]): ?>
      <li class="it-etapa">
        <span class="it-numero" aria-hidden="true"><?= sprintf('%02d', $n + 1) ?></span>
        <span class="it-fio" aria-hidden="true"></span>
        <h3><?= e($titulo) ?></h3>
        <p><?= e($texto) ?></p>
      </li>
      <?php endforeach; ?>
    </ol>
<?php }

function it_quem(): void
{
    $c = IT_COPY; ?>
    <div class="dd-cabeca">
      <p class="dd-rotulo dd-rotulo--secao"><?= e($c['quem_rotulo']) ?></p>
      <h2 class="dd-titulo"><?= e($c['quem_titulo']) ?></h2>
    </div>
    <div class="it-quem">
      <?php foreach ($c['quem_itens'] as [$quem, $resto]): ?>
      <article class="it-quem-card"><h3><?= e($quem) ?></h3><p><?= e($resto) ?></p></article>
      <?php endforeach; ?>
    </div>
<?php }

function it_preco(): void
{
    $c = IT_COPY; ?>
    <h2 class="dd-rotulo dd-rotulo--sub"><?= e($c['preco_rotulo']) ?></h2>
    <dl class="it-preco">
      <?php foreach ($c['preco_itens'] as [$item, $como]): ?>
      <div class="it-preco-linha"><dt><?= e($item) ?></dt><dd><?= e($como) ?></dd></div>
      <?php endforeach; ?>
    </dl>
<?php }

function it_comecar(string $raiz): void
{
    $c = IT_COPY; ?>
    <p class="dd-rotulo dd-rotulo--sub"><?= e($c['comecar_rotulo']) ?></p>
    <h2 class="it-comecar-titulo"><?= e($c['comecar_titulo']) ?></h2>
    <div class="it-hero-acoes">
      <a class="dd-btn" href="#request-form"><?= e($c['cta_primario']) ?></a>
      <a class="it-link" href="<?= e($raiz) ?>contact/?about=innovation"><?= e($c['cta_secundario']) ?> <span aria-hidden="true">↗</span></a>
    </div>
<?php }

/* ---- Formulário ------------------------------------------------------------------ */

function it_campo(array $f, array $valores): void
{
    $c = IT_COPY;
    $id = 'it-' . $f['k'];
    $req = !empty($f['req']);
    $marca = $req ? ' <span class="it-req" aria-hidden="true">' . e($c['obrigatorio']) . '</span>' : '';
    $v = $valores[$f['k']] ?? '';
    $cond = isset($f['mostra']);
    $attrs = $cond ? ' data-mostra-campo="' . e($f['mostra']['campo']) . '" data-mostra-valores="' . e(implode('|', $f['mostra']['valores'])) . '" data-mostra-opcional="' . ($req ? 'nao' : 'sim') . '" hidden' : '';
    // Um campo condicional nasce escondido e sem required; o JavaScript liga os dois ao revelá-lo.
    $obriga = ($req && !$cond) ? ' required' : '';
    $auto = isset($f['auto']) ? ' autocomplete="' . e($f['auto']) . '"' : '';

    if ($f['tipo'] === 'consentimento') { ?>
      <div class="fa-consentimento">
        <label class="fa-opcao"><input type="checkbox" id="<?= e($id) ?>" name="<?= e($f['k']) ?>" value="yes"<?= $obriga ?>><span><?= e($f['r']) ?><?= $marca ?></span></label>
      </div>
    <?php return; }

    if ($f['tipo'] === 'radio' || $f['tipo'] === 'checkbox') {
        $tipo = $f['tipo'] === 'radio' ? 'radio' : 'checkbox';
        $nome = $tipo === 'checkbox' ? $f['k'] . '[]' : $f['k']; ?>
      <fieldset class="fa-grupo it-grupo"<?= $attrs ?>>
        <legend><?= e($f['r']) ?><?= $marca ?></legend>
        <div class="fa-opcoes fa-opcoes--grade">
          <?php foreach ($f['opcoes'] as $i => $o):
              $marcado = $tipo === 'checkbox' ? in_array($o, (array) $v, true) : ((string) $v === $o); ?>
          <label class="fa-opcao"><input type="<?= $tipo ?>" name="<?= e($nome) ?>" value="<?= e($o) ?>"<?= $marcado ? ' checked' : '' ?><?= ($tipo === 'radio' && $i === 0) ? $obriga : '' ?>><span><?= e($o) ?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>
    <?php return; } ?>

      <div class="fa-campo"<?= $attrs ?>>
        <label for="<?= e($id) ?>"><?= e($f['r']) ?><?= $marca ?></label>
        <?php if ($f['tipo'] === 'textarea'): ?>
          <textarea id="<?= e($id) ?>" name="<?= e($f['k']) ?>" rows="8" maxlength="<?= (int) $f['max'] ?>" data-palavras="<?= (int) ($f['palavras'] ?? 0) ?>"<?= $obriga ?>><?= e((string) $v) ?></textarea>
          <p class="fa-ajuda it-contador" data-contador><span>0</span> <?= e($c['contador']) ?></p>
        <?php elseif ($f['tipo'] === 'pais'): ?>
          <select id="<?= e($id) ?>" name="<?= e($f['k']) ?>"<?= $obriga ?> autocomplete="country-name">
            <option value="">Select</option>
            <?php foreach (it_paises() as $p): ?><option<?= ((string) $v === $p) ? ' selected' : '' ?>><?= e($p) ?></option><?php endforeach; ?>
          </select>
        <?php else: ?>
          <input type="<?= e($f['tipo']) ?>" id="<?= e($id) ?>" name="<?= e($f['k']) ?>" maxlength="<?= (int) $f['max'] ?>" value="<?= e((string) $v) ?>"<?= $obriga ?><?= $auto ?><?= $f['tipo'] === 'url' ? ' placeholder="https://"' : '' ?>>
        <?php endif; ?>
        <?php if (!empty($f['dica'])): ?><p class="fa-ajuda"><?= e($f['dica']) ?></p><?php endif; ?>
      </div>
<?php }

function it_formulario(string $raiz, string $carimbo, array $valores = []): void
{
    $c = IT_COPY; ?>
    <div class="dd-cabeca">
      <p class="dd-rotulo dd-rotulo--secao"><?= e($c['form_rotulo']) ?></p>
      <h2 class="dd-titulo" id="it-t-form"><?= e($c['form_titulo']) ?></h2>
      <p class="it-form-lead"><?= e($c['form_lead']) ?></p>
      <div class="dd-intro dd-intro--linha"><p><?= e($c['form_intro']) ?></p></div>
    </div>

    <form class="fa-form it-form" id="it-form" method="post" action="enviar.php" accept-charset="UTF-8" novalidate>
      <p class="fa-erro" id="it-erro" role="alert" tabindex="-1"><?= e($c['erro']) ?></p>

      <input type="hidden" name="t" value="<?= e($carimbo) ?>">
      <div class="fa-armadilha" aria-hidden="true">
        <label for="it-website-extra">Website</label>
        <input type="text" id="it-website-extra" name="website_extra" tabindex="-1" autocomplete="off">
      </div>

      <p class="it-nota-req"><?= e($c['obrigatorio_nota']) ?></p>

      <?php foreach (IT_FORM as $s): ?>
      <fieldset class="fa-secao it-secao">
        <legend><h3 class="dd-rotulo dd-rotulo--sub"><?= e($s['titulo']) ?></h3></legend>
        <?php foreach ($s['campos'] as $f) it_campo($f, $valores); ?>
      </fieldset>
      <?php endforeach; ?>

      <div class="fa-acoes">
        <button class="dd-btn fa-enviar" type="submit" data-enviando="<?= e($c['enviando']) ?>" disabled><?= e($c['enviar']) ?></button>
      </div>
    </form>
<?php }

function it_fecho(string $raiz): void
{
    $c = IT_COPY; ?>
    <h2 id="it-t-fecho"><?= e($c['fecho_titulo']) ?></h2>
    <p class="dd-fecho-texto"><?= e($c['fecho_texto']) ?></p>
    <a class="it-fecho-cta" href="<?= e($raiz) ?>contact/?about=innovation"><?= e($c['fecho_cta']) ?> <span aria-hidden="true">↗</span></a>
<?php }

function it_rodape(string $raiz): void
{ ?>
  <footer class="footer">
    <svg class="footer-curva" aria-hidden="true" viewBox="0 0 1000 1000" preserveAspectRatio="none"><path d="M0 900 C 250 900, 400 640, 520 540 S 800 180, 1000 100" fill="none" stroke="#DCC792" stroke-width="2" stroke-linecap="round" vector-effect="non-scaling-stroke"/></svg>
    <a class="footer-logo" href="<?= e($raiz) ?>"><img src="<?= e($raiz) ?>img/live-and-learn-brazil-horizontal-dark.svg" alt="Live &amp; Learn Brazil"></a>
    <a class="footer-site" href="https://liveandlearnbrazil.com">liveandlearnbrazil.com</a>
  </footer>
<?php }

/* <head> comum: só muda o modelo (classe do body) e o canonical. */
function it_head(string $raiz, string $canonical, bool $noindex = false): void
{
    $c = IT_COPY; ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($c['titulo']) ?></title>
  <meta name="description" content="<?= e($c['descricao']) ?>">
  <?php if ($noindex): ?><meta name="robots" content="noindex, nofollow">
  <?php endif; ?><link rel="canonical" href="<?= e($canonical) ?>">
  <link rel="icon" type="image/svg+xml" href="<?= e($raiz) ?>img/live-and-learn-brazil-favicon.svg">
  <link rel="stylesheet" href="<?= e($raiz) ?>css/styles.css?v=20260921a">
  <link rel="stylesheet" href="<?= e($raiz) ?>css/due-diligence.css?v=20260921c">
  <link rel="stylesheet" href="<?= e($raiz) ?>css/full-advisory.css?v=20260921b">
  <link rel="stylesheet" href="<?= e($raiz) ?>css/innovation-ties.css?v=20260923c">
<?php }
