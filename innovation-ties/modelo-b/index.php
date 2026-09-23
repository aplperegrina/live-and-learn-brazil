<?php
/* Live & Learn Brazil — /innovation-ties/modelo-b/ — MODELO B: a mesma copy, sem
   textura de fundo. A arte de circuito vira dois blocos: o painel ao lado do título
   e a faixa larga antes do formulário. Página de comparação, fora do sitemap. */
declare(strict_types=1);
require_once __DIR__ . '/../../full-advisory/config.php';
require_once __DIR__ . '/../dados.php';
require_once __DIR__ . '/../partes.php';
header('Cache-Control: no-store');

$raiz = '../../';
$valores = $it_valores ?? [];
$erro = !empty($it_erro);
$emitido = time();
$carimbo = $emitido . '.' . hash_hmac('sha256', (string) $emitido, fa_segredo());
?>
<!doctype html>
<html lang="en">
<head>
<?php it_head($raiz, 'https://liveandlearnbrazil.com/innovation-ties/', true); ?>
</head>
<body class="it-modelo-b<?= $erro ? ' fa-estado-erro' : '' ?>">
<div class="page dd fa it">

<?php it_cabecalho($raiz); ?>

  <main>

  <!-- ===== HERO: texto à esquerda, BLOCO 1 (painel ardósia com o circuito) à direita ===== -->
  <section class="it-hero it-hero--painel" aria-labelledby="it-h1">
    <div class="it-hero-texto">
<?php it_hero_texto($raiz); ?>
    </div>
    <div class="it-painel" aria-hidden="true"></div>
  </section>

  <!-- ===== 01 / POR QUE VALE ===== -->
  <section class="dd-secao" aria-label="Why it is worth the work">
<?php it_intro(); ?>
  </section>

  <!-- ===== 02 / O QUE FAZEMOS + EXPERIÊNCIA (azul-gelo) ===== -->
  <section class="dd-secao dd-secao--azul" aria-label="What we do">
<?php it_oque(); ?>
  </section>

  <!-- ===== 03 / COMO FUNCIONA: a linha com pontos ===== -->
  <section class="dd-secao" aria-label="How it works">
<?php it_como(); ?>
  </section>

  <!-- ===== 04 / PARA QUEM + 05 / PREÇO ===== -->
  <section class="dd-secao" aria-label="Who it is for">
<?php it_quem(); ?>
<?php it_preco(); ?>
  </section>

  <!-- ===== BLOCO 2: faixa larga de circuito, com o convite a começar ===== -->
  <section class="it-comecar it-comecar--faixa" aria-label="Get started">
<?php it_comecar($raiz); ?>
  </section>

  <!-- ===== FORMULÁRIO ===== -->
  <section class="dd-secao it-form-secao" id="request-form" aria-labelledby="it-t-form">
<?php it_formulario($raiz, $carimbo, $valores); ?>
  </section>

  <!-- ===== FECHO (ardósia), emenda no rodapé ===== -->
  <section class="dd-fecho it-fecho" aria-labelledby="it-t-fecho">
<?php it_fecho($raiz); ?>
  </section>

  </main>

<?php it_rodape($raiz); ?>

</div>
<script type="module" src="<?= e($raiz) ?>src/components/innovation/formulario.js?v=20260923a"></script>
</body>
</html>
