<?php
/* Live & Learn Brazil — recebe o formulário de /contact/ e envia dois e-mails
   (um para a recepção, uma confirmação no idioma da pessoa) com mail() do PHP.
   Não grava nada no servidor. Nunca devolve o que foi digitado em HTML.

   Com JavaScript (fetch, Accept: application/json): responde JSON {"ok":true|false}.
   Sem JavaScript: redireciona para /contact/?lang=xx&sent=1 ou &error=1. */

declare(strict_types=1);
require_once __DIR__ . '/../full-advisory/config.php';
require_once __DIR__ . '/textos.php';

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$quer_json = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
$lang = ct_idioma(is_string($_POST['lang'] ?? null) ? $_POST['lang'] : 'en');

function responder(bool $ok, string $motivo = ''): never
{
    global $quer_json, $lang;
    if ($quer_json) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'reason' => $motivo]);
    } else {
        $pagina = ct_pagina($lang);
        $pagina .= (str_contains($pagina, '?') ? '&' : '?') . ($ok ? 'sent=1' : 'error=1');
        header('Location: ' . $pagina, true, 303);
    }
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Method not allowed';
    exit;
}

/* ---- Leitura e limpeza dos campos ------------------------------------------------ */

function campo(string $nome, int $max = 500): string
{
    $v = $_POST[$nome] ?? '';
    if (!is_string($v)) return '';
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    $v = preg_replace('/[^\P{C}\n\t]/u', '', $v) ?? '';   // remove caracteres de controle, mantém quebras
    if (!mb_check_encoding($v, 'UTF-8')) return '';
    return trim(mb_substr($v, 0, $max));
}

/* Uma linha só: sem quebras de qualquer tipo (vai para cabeçalhos e para o assunto) */
function linha(string $v, int $max = 200): string
{
    return trim(mb_substr(preg_replace('/[\r\n\t\x0B\f]+/u', ' ', $v) ?? '', 0, $max));
}

/* ---- Robôs: honeypot e carimbo de hora ------------------------------------------ */

if (campo('website') !== '') {
    responder(true);                 // honeypot preenchido: finge sucesso, não envia nada
}

$carimbo = campo('t', 120);
if (!preg_match('/^(\d{9,11})\.([0-9a-f]{64})$/', $carimbo, $m)) {
    responder(true);                 // carimbo ausente ou forjado: mesmo tratamento
}
$emitido = (int) $m[1];
if (!hash_equals(hash_hmac('sha256', (string) $emitido, fa_segredo()), $m[2])) {
    responder(true);
}
$idade = time() - $emitido;
if ($idade < FA_ATRASO_MINIMO) {
    responder(true);                 // mais rápido que uma pessoa consegue preencher
}
if ($idade > FA_IDADE_MAXIMA) {
    responder(false, 'expired');     // página aberta há mais de um dia: pedir nova tentativa
}

/* ---- Campos ---------------------------------------------------------------------- */

$d = [
    'nome'     => linha(campo('name', 200)),
    'telefone' => linha(campo('phone', 60)),
    'email'    => linha(campo('email', 254)),
    'mensagem' => campo('message', 5000),
    'about'    => in_array($_POST['about'] ?? '', ['destination', 'innovation', 'portuguese'], true) ? (string) $_POST['about'] : '',
];

foreach (['nome', 'email', 'mensagem'] as $k) {
    if ($d[$k] === '') responder(false, 'missing');
}
if (filter_var($d['email'], FILTER_VALIDATE_EMAIL) === false || preg_match('/[^\x21-\x7E]/', $d['email'])) {
    responder(false, 'email');
}

/* ---- Montagem dos e-mails -------------------------------------------------------- */

function cabecalhos(string $reply_to = ''): string
{
    $de = mb_encode_mimeheader(FA_NOME_REMETENTE, 'UTF-8', 'B') . ' <' . FA_REMETENTE . '>';
    $h = "From: $de\r\n";
    if ($reply_to !== '') $h .= "Reply-To: <$reply_to>\r\n";
    $h .= "MIME-Version: 1.0\r\n";
    $h .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $h .= "Content-Transfer-Encoding: 8bit\r\n";
    $h .= "X-Mailer: liveandlearnbrazil.com/contact";
    return $h;
}

function enviar(string $para, string $assunto, string $corpo, string $reply_to = ''): bool
{
    $assunto_mime = mb_encode_mimeheader($assunto, 'UTF-8', 'B');
    // -f define o remetente do envelope; sem isso a Hostinger costuma recusar ou marcar como spam
    return mail($para, $assunto_mime, $corpo, cabecalhos($reply_to), '-f' . FA_REMETENTE);
}

$idiomas = ['en' => 'English', 'pt' => 'Portuguese', 'it' => 'Italian', 'zh' => 'Mandarin'];
$origens = ['destination' => 'Destination Services — request a scope', 'innovation' => 'Innovation Ties — initial call', 'portuguese' => 'Brazilian Portuguese and Communication — class plan'];
$origem  = $origens[$d['about']] ?? 'Contact form';
$rotulo  = fn(string $r, string $v): string => str_pad($r, 20) . ($v === '' ? '—' : $v) . "\n";
$quando  = gmdate('Y-m-d H:i') . ' UTC';

$corpo  = "$origem\n";
$corpo .= "Received via liveandlearnbrazil.com/contact/ on $quando\n\n";
$corpo .= $rotulo('Name', $d['nome']);
$corpo .= $rotulo('Email', $d['email']);
$corpo .= $rotulo('Phone or WhatsApp', $d['telefone']);
$corpo .= $rotulo('Page language', $idiomas[$lang]);
$corpo .= "\nMessage\n" . $d['mensagem'] . "\n\n";
$corpo .= "Reply to this message to answer " . $d['nome'] . " directly.\n";

$assuntos = ['destination' => 'Request a scope — Destination Services', 'innovation' => 'Innovation Ties — initial call', 'portuguese' => 'Class plan — Brazilian Portuguese and Communication'];
$assunto = ($assuntos[$d['about']] ?? 'Contact form') . ' — ' . linha($d['nome'], 120);

$t = CT_TEXTOS[$lang];
$confirmacao  = str_replace('{nome}', $d['nome'], $t['conf_corpo']) . "\n\n";
$confirmacao .= "Live & Learn Brazil\nhttps://liveandlearnbrazil.com/\n";

/* ---- Envio ----------------------------------------------------------------------- */

if (!enviar(FA_DESTINO, $assunto, $corpo, $d['email'])) {
    responder(false, 'mail');
}
enviar($d['email'], $t['conf_assunto'], $confirmacao);   // cópia: falha não bloqueia
responder(true);
