<?php
/* Live & Learn Brazil — recebe o formulário de /innovation-ties/ e envia dois e-mails
   (o pedido completo para a recepção, uma confirmação curta para quem escreveu) com
   mail() do PHP. Não grava nada no servidor. Nunca devolve em HTML o que foi digitado
   sem escapar. Os campos e as regras vêm de dados.php.

   Com JavaScript (fetch, Accept: application/json): responde JSON {"ok":true|false}.
   Sem JavaScript: em caso de sucesso redireciona para /innovation-ties/request-received/;
   em caso de falha volta a desenhar o formulário com o que já estava preenchido. */

declare(strict_types=1);
require_once __DIR__ . '/../full-advisory/config.php';
require_once __DIR__ . '/dados.php';

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const IT_CONFIRMACAO = '/innovation-ties/request-received/';

$quer_json = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
$modelo = (($_POST['modelo'] ?? '') === 'b') ? 'b' : 'a';

/* ---- Leitura e limpeza ----------------------------------------------------------- */

function campo(string $nome, int $max = 500): string
{
    $v = $_POST[$nome] ?? '';
    if (!is_string($v)) return '';
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    $v = preg_replace('/[^\P{C}\n\t]/u', '', $v) ?? '';
    if (!mb_check_encoding($v, 'UTF-8')) return '';
    return trim(mb_substr($v, 0, $max));
}

function linha(string $v, int $max = 200): string
{
    return trim(mb_substr(preg_replace('/[\r\n\t\x0B\f]+/u', ' ', $v) ?? '', 0, $max));
}

function palavras(string $v): int
{
    $p = preg_split('/\s+/u', trim($v), -1, PREG_SPLIT_NO_EMPTY);
    return $p === false ? 0 : count($p);
}

/* Os valores lidos, já limpos, guardados para poder redesenhar o formulário numa falha */
$valores = [];

function responder(bool $ok, string $motivo = ''): never
{
    global $quer_json, $modelo, $valores;
    if ($quer_json) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($ok ? ['ok' => true, 'redirect' => IT_CONFIRMACAO] : ['ok' => false, 'reason' => $motivo]);
        exit;
    }
    if ($ok) {
        header('Location: ' . IT_CONFIRMACAO, true, 303);
        exit;
    }
    // Sem JavaScript: a pessoa volta ao formulário com tudo o que escreveu no lugar
    http_response_code(422);
    $it_valores = $valores;
    $it_erro = true;
    require __DIR__ . ($modelo === 'b' ? '/modelo-b/index.php' : '/index.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Method not allowed';
    exit;
}

/* ---- Robôs: honeypot e carimbo de hora ------------------------------------------- */

if (campo('website_extra') !== '') {
    responder(true);                 // armadilha preenchida: finge sucesso, não envia nada
}

$carimbo = campo('t', 120);
if (!preg_match('/^(\d{9,11})\.([0-9a-f]{64})$/', $carimbo, $m)) {
    responder(true);
}
$emitido = (int) $m[1];
if (!hash_equals(hash_hmac('sha256', (string) $emitido, fa_segredo()), $m[2])) {
    responder(true);
}
$idade = time() - $emitido;
if ($idade < FA_ATRASO_MINIMO) {
    responder(true);
}
if ($idade > FA_IDADE_MAXIMA) {
    responder(false, 'expired');
}

/* ---- Campos, conforme a definição em dados.php ----------------------------------- */

$campos = [];
foreach (IT_FORM as $s) {
    foreach ($s['campos'] as $f) $campos[$f['k']] = $f;
}

foreach ($campos as $k => $f) {
    switch ($f['tipo']) {
        case 'checkbox':
            $v = $_POST[$k] ?? [];
            $valores[$k] = is_array($v) ? array_values(array_intersect($f['opcoes'], array_map('strval', $v))) : [];
            break;
        case 'radio':
            $v = $_POST[$k] ?? '';
            $valores[$k] = (is_string($v) && in_array($v, $f['opcoes'], true)) ? $v : '';
            break;
        case 'pais':
            $v = $_POST[$k] ?? '';
            $valores[$k] = (is_string($v) && in_array($v, it_paises(), true)) ? $v : '';
            break;
        case 'consentimento':
            $valores[$k] = (($_POST[$k] ?? '') === 'yes');
            break;
        case 'textarea':
            $valores[$k] = campo($k, (int) $f['max']);
            break;
        default:
            $valores[$k] = linha(campo($k, (int) $f['max']), (int) $f['max']);
    }
}

/* Um campo condicional só é exigido quando a resposta que o revela foi escolhida */
function visivel(array $f, array $valores): bool
{
    if (!isset($f['mostra'])) return true;
    return in_array($valores[$f['mostra']['campo']] ?? '', $f['mostra']['valores'], true);
}

foreach ($campos as $k => $f) {
    if (!visivel($f, $valores)) { $valores[$k] = $f['tipo'] === 'checkbox' ? [] : ''; continue; }
    if (empty($f['req'])) continue;
    $v = $valores[$k];
    $vazio = $f['tipo'] === 'consentimento' ? ($v !== true) : (is_array($v) ? count($v) === 0 : $v === '');
    if ($vazio) responder(false, 'missing:' . $k);
}

if (filter_var($valores['email'], FILTER_VALIDATE_EMAIL) === false || preg_match('/[^\x21-\x7E]/', $valores['email'])) {
    responder(false, 'email');
}
if (palavras($valores['description']) > 500) {
    responder(false, 'words');
}

/* ---- Etiqueta interna de prioridade ---------------------------------------------- */

$prioridade = 'REVIEW';
$bate = true;
foreach (IT_PRIORIDADE as $k => $aceitos) {
    if (!in_array($valores[$k], $aceitos, true)) { $bate = false; break; }
}
if ($bate) $prioridade = 'PRIORITY';

/* ---- Montagem dos e-mails -------------------------------------------------------- */

function cabecalhos(string $reply_to = ''): string
{
    $de = mb_encode_mimeheader(FA_NOME_REMETENTE, 'UTF-8', 'B') . ' <' . FA_REMETENTE . '>';
    $h = "From: $de\r\n";
    if ($reply_to !== '') $h .= "Reply-To: <$reply_to>\r\n";
    $h .= "MIME-Version: 1.0\r\n";
    $h .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $h .= "Content-Transfer-Encoding: 8bit\r\n";
    $h .= "X-Mailer: liveandlearnbrazil.com/innovation-ties";
    return $h;
}

function enviar(string $para, string $assunto, string $corpo, string $reply_to = ''): bool
{
    // -f define o remetente do envelope; sem isso a Hostinger costuma recusar ou marcar como spam
    return mail($para, mb_encode_mimeheader($assunto, 'UTF-8', 'B'), $corpo, cabecalhos($reply_to), '-f' . FA_REMETENTE);
}

$corpo  = "Innovation Ties request — $prioridade\n";
$corpo .= "Received via liveandlearnbrazil.com/innovation-ties/ on " . gmdate('Y-m-d H:i') . " UTC\n";
$corpo .= "Words in the project description: " . palavras($valores['description']) . "\n";

foreach (IT_FORM as $s) {
    $corpo .= "\n" . mb_strtoupper($s['titulo']) . "\n";
    foreach ($s['campos'] as $f) {
        $k = $f['k'];
        if (!visivel($f, $valores)) continue;
        $v = $valores[$k];
        if ($f['tipo'] === 'consentimento') { $corpo .= str_pad('Consent', 34) . ($v ? 'Given' : '—') . "\n"; continue; }
        if (is_array($v)) $v = implode(', ', $v);
        if ($f['tipo'] === 'textarea') { $corpo .= $f['r'] . "\n" . ($v === '' ? '—' : $v) . "\n"; continue; }
        // Rótulo longo não cabe na coluna: vai numa linha só, com a resposta recuada abaixo
        $corpo .= mb_strlen($f['r']) > 32
            ? $f['r'] . "\n" . str_repeat(' ', 4) . ($v === '' ? '—' : $v) . "\n"
            : str_pad($f['r'], 34) . ($v === '' ? '—' : $v) . "\n";
    }
}
$corpo .= "\nReply to this message to answer " . $valores['your_name'] . " directly.\n";

$assunto = sprintf('[Innovation Ties] %s — %s (%s)', $prioridade, linha($valores['org_name'], 120), $valores['country']);

$confirmacao  = "Thank you. Your request has been received.\n\n";
$confirmacao .= "We will review your request and reply within 5 business days with a first assessment or a request for more information.\n\n";
$confirmacao .= "Live & Learn Brazil\nreception@liveandlearnbrazil.com\nhttps://liveandlearnbrazil.com/\n";

/* ---- Envio ----------------------------------------------------------------------- */

if (!enviar(FA_DESTINO, $assunto, $corpo, $valores['email'])) {
    responder(false, 'mail');
}
enviar($valores['email'], 'Your Innovation Ties request — Live & Learn Brazil', $confirmacao);   // cópia: falha não bloqueia
responder(true);
