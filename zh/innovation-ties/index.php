<?php
/* Live & Learn Brazil — /zh/innovation-ties/ (versão chinesa; as mesmas partes de
   /innovation-ties/, com a copy de dados-zh.php). Texto original desta página:
   A arte de circuito entra como dois blocos em ardósia: o hero e a faixa de
   "get started". Em cada um o desenho fica em gelo acinzentado, à direita, e
   esvanece antes de chegar ao texto (o esvanecimento já vem no próprio PNG).
   enviar.php inclui este arquivo quando o envio falha sem JavaScript, passando
   $it_valores (o que a pessoa já tinha escrito) e $it_erro. */
declare(strict_types=1);
$GLOBALS['it_lang'] = 'zh';
require_once __DIR__ . '/../../full-advisory/config.php';
require_once __DIR__ . '/../../innovation-ties/dados.php';
require_once __DIR__ . '/../../innovation-ties/dados-zh.php';
require_once __DIR__ . '/../../innovation-ties/partes.php';
header('Cache-Control: no-store');

$raiz = '../../';
$valores = $it_valores ?? [];
$erro = !empty($it_erro);
$emitido = time();
$carimbo = $emitido . '.' . hash_hmac('sha256', (string) $emitido, fa_segredo());
?>
<!doctype html>
<html lang="zh-Hans">
<head>
<?php it_head($raiz, 'https://liveandlearnbrazil.com/zh/innovation-ties/'); ?>
</head>
<body class="<?= $erro ? 'fa-estado-erro' : '' ?>">
<div class="page dd fa it">

<?php it_cabecalho($raiz); ?>

  <main>

  <!-- ===== HERO: bloco ardósia, circuito à direita esvanecendo até o texto ===== -->
  <section class="it-hero it-hero--arte" aria-labelledby="it-h1">
    <div class="it-hero-texto">
<?php it_hero_texto($raiz); ?>
    </div>
  </section>

  <!-- ===== 01 / POR QUE VALE ===== -->
  <section class="dd-secao" aria-label="为什么值得投入">
<?php it_intro(); ?>
  </section>

  <!-- ===== TALENTOS: parcerias com instituições de ensino e formação de talentos (região clara) ===== -->
  <section class="dd-secao" id="talent" aria-labelledby="it-t-talentos">
<?php it_talentos('zh'); ?>
  </section>

  <!-- ===== 02 / O QUE FAZEMOS + EXPERIÊNCIA (azul-gelo) ===== -->
  <section class="dd-secao dd-secao--azul" aria-label="我们做什么">
<?php it_oque(); ?>
  </section>

  <!-- ===== 03 / COMO FUNCIONA: a linha com pontos ===== -->
  <section class="dd-secao" aria-label="运作方式">
<?php it_como(); ?>
  </section>

  <!-- ===== 04 / PARA QUEM + 05 / PREÇO ===== -->
  <section class="dd-secao" aria-label="适用对象">
<?php it_quem(); ?>
<?php it_preco(); ?>
  </section>

  <!-- ===== COMEÇAR: segundo bloco ardósia, mesmo tratamento do hero ===== -->
  <section class="it-comecar it-comecar--arte" aria-label="开始">
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
<script type="module" src="<?= e($raiz) ?>src/components/innovation/formulario.js?v=20260927a"></script>
</body>
</html>
