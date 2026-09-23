<?php
/* Live & Learn Brazil — recebe o formulário de /full-advisory/ e envia dois e-mails
   (um para a recepção, um de confirmação para quem escreveu) com mail() do PHP.
   Não grava nada no servidor. Nunca devolve o que foi digitado em HTML.

   Com JavaScript (fetch, Accept: application/json): responde JSON {"ok":true|false}.
   Sem JavaScript: redireciona para /full-advisory/?sent=1 ou ?error=1. */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$quer_json = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

function responder(bool $ok, string $motivo = ''): never
{
    global $quer_json;
    if ($quer_json) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'reason' => $motivo]);
    } else {
        header('Location: ' . FA_PAGINA . ($ok ? '?sent=1' : '?error=1'), true, 303);
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

function lista(string $nome, array $permitidos): array
{
    $v = $_POST[$nome] ?? [];
    if (!is_array($v)) return [];
    return array_values(array_intersect($permitidos, array_map('strval', $v)));
}

function opcao(string $nome, array $permitidas): string
{
    $v = $_POST[$nome] ?? '';
    return (is_string($v) && in_array($v, $permitidas, true)) ? $v : '';
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

$funcionarios = ['Up to 50', '51–200', '201–500', 'More than 500'];
$departamentos = ['Leadership', 'Operations and production', 'Engineering and technical', 'Sales and marketing',
                  'Finance and administration', 'HR', 'Legal and compliance', 'Other'];
$diagnostico = ['Yes', 'Not yet, but we plan to', 'No'];
$inicio = ['Within a month', 'In one to three months', 'Later this year', 'Still exploring'];
$idiomas = ['Portuguese', 'English', 'Mandarin', 'Italian'];

$d = [
    'nome'          => linha(campo('name', 200)),
    'cargo'         => linha(campo('role', 200)),
    'email'         => linha(campo('email', 254)),
    'telefone'      => linha(campo('phone', 60)),
    'empresa'       => linha(campo('company', 200)),
    'pais_matriz'   => linha(campo('hq_country', 120)),
    'local_brasil'  => linha(campo('location', 200)),
    'funcionarios'  => opcao('employees', $funcionarios),
    'departamentos' => lista('departments', $departamentos),
    'descricao'     => campo('description', 5000),
    'diagnostico'   => opcao('diagnostic', $diagnostico),
    'inicio'        => opcao('start', $inicio),
    'idioma'        => opcao('language', $idiomas),
    'consentimento' => ($_POST['consent'] ?? '') === 'yes',
];

$obrigatorios = ['nome', 'cargo', 'email', 'empresa', 'pais_matriz', 'local_brasil', 'funcionarios', 'descricao', 'diagnostico', 'inicio', 'idioma'];
foreach ($obrigatorios as $k) {
    if ($d[$k] === '') responder(false, 'missing');
}
if (!$d['consentimento']) responder(false, 'consent');
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
    $h .= "X-Mailer: liveandlearnbrazil.com/full-advisory";
    return $h;
}

function enviar(string $para, string $assunto, string $corpo, string $reply_to = ''): bool
{
    $assunto_mime = mb_encode_mimeheader($assunto, 'UTF-8', 'B');
    // -f define o remetente do envelope; sem isso a Hostinger costuma recusar ou marcar como spam
    return mail($para, $assunto_mime, $corpo, cabecalhos($reply_to), '-f' . FA_REMETENTE);
}

$rotulo = fn(string $r, string $v): string => str_pad($r, 34) . ($v === '' ? '—' : $v) . "\n";
$quando = gmdate('Y-m-d H:i') . ' UTC';

$corpo  = "Full advisory enquiry\n";
$corpo .= "Received via liveandlearnbrazil.com/full-advisory/ on $quando\n\n";
$corpo .= "01 / ABOUT YOU\n";
$corpo .= $rotulo('Full name', $d['nome']);
$corpo .= $rotulo('Role', $d['cargo']);
$corpo .= $rotulo('Work email', $d['email']);
$corpo .= $rotulo('Phone or WhatsApp', $d['telefone']);
$corpo .= "\n02 / ABOUT THE OPERATION\n";
$corpo .= $rotulo('Company', $d['empresa']);
$corpo .= $rotulo('Head office country', $d['pais_matriz']);
$corpo .= $rotulo('Where the operation is in Brazil', $d['local_brasil']);
$corpo .= $rotulo('Number of employees in Brazil', $d['funcionarios']);
$corpo .= $rotulo('Departments involved', implode(', ', $d['departamentos']));
$corpo .= "\n03 / WHAT IS HAPPENING\n";
$corpo .= "Describe what you are seeing\n" . $d['descricao'] . "\n\n";
$corpo .= $rotulo('Have you done our diagnostic?', $d['diagnostico']);
$corpo .= $rotulo('When would you like to start?', $d['inicio']);
$corpo .= "\n04 / HOW WE SHOULD REPLY\n";
$corpo .= $rotulo('Preferred language', $d['idioma']);
$corpo .= $rotulo('Consent', 'Given');
$corpo .= "\nReply to this message to answer " . $d['nome'] . " directly.\n";

$assunto = 'Full advisory enquiry — ' . linha($d['empresa'], 120);

$confirmacao  = "Thank you. We have received your enquiry about full advisory for " . $d['empresa'] . ".\n\n";
$confirmacao .= "We will reply within two working days, in " . $d['idioma'] . ", to this address.\n\n";
$confirmacao .= "If you need to add anything in the meantime, just reply to this message.\n\n";
$confirmacao .= "Live & Learn Brazil\nreception@liveandlearnbrazil.com\nhttps://liveandlearnbrazil.com/\n";

/* ---- Envio ----------------------------------------------------------------------- */

if (!enviar(FA_DESTINO, $assunto, $corpo, $d['email'])) {
    responder(false, 'mail');
}
enviar($d['email'], 'We have received your enquiry — Live & Learn Brazil', $confirmacao);   // cópia: falha não bloqueia
responder(true);
