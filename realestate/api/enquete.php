<?php
/* Enquetes dos boletins de bairro (realestate.liveandlearnbrazil.com)
   POST JSON: {acao:"voto", enquete:"adivinhe"|"planos", site:"brooklin", palpite?, resposta?, optin?, nome?, contato?, idioma?}
   Guarda contagens em ../_dados/enquetes.json e os contatos (só de quem marcou a caixa) em ../_dados/contatos.csv.
   A pasta _dados tem .htaccess que bloqueia o acesso pelo navegador. */
date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo '{"erro":"metodo"}'; exit; }

/* os dados ficam fora da pasta pública quando possível (sobrevivem aos deploys); senão, em ../_dados protegida */
$DIR = dirname(__DIR__, 3) . '/_dados_boletins';
if (!is_dir($DIR) && !@mkdir($DIR, 0750, true)) { $DIR = __DIR__ . '/../_dados'; }
if (!is_dir($DIR)) { mkdir($DIR, 0750, true); }
if (!file_exists("$DIR/.htaccess")) { file_put_contents("$DIR/.htaccess", "Require all denied\n"); }

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) { http_response_code(400); echo '{"erro":"json"}'; exit; }
$site = preg_replace('/[^a-z]/', '', strtolower($in['site'] ?? ''));
$enq  = $in['enquete'] ?? '';
$SITES = ['brooklin', 'cidadejardim', 'morumbi', 'osasco', 'chacara'];
$PLANOS = ['ficar', 'maior', 'menor', 'investir', 'vender', 'alugar'];
if (!in_array($site, $SITES, true) || !in_array($enq, ['adivinhe', 'planos'], true)) { http_response_code(400); echo '{"erro":"param"}'; exit; }

/* um voto por pessoa por dia em cada enquete (IP guardado só como hash) */
$ip = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . date('Y-m-d') . '|' . $site . $enq . '|lnl');

$fp = fopen("$DIR/enquetes.json", 'c+');
flock($fp, LOCK_EX);
$raw = stream_get_contents($fp);
$db = $raw ? json_decode($raw, true) : [];
if (!is_array($db)) $db = [];
$db[$site] = $db[$site] ?? [];
$e = &$db[$site][$enq];
if (!is_array($e)) $e = ['n' => 0, 'soma' => 0, 'contagem' => array_fill_keys($PLANOS, 0), 'ips' => []];
// limpa os hashes de outros dias
$hoje = date('Y-m-d');
if (($e['dia'] ?? '') !== $hoje) { $e['ips'] = []; $e['dia'] = $hoje; }
$repetido = in_array($ip, $e['ips'], true);

if (!$repetido) {
  if ($enq === 'adivinhe') {
    $g = intval($in['palpite'] ?? 0);
    if ($g >= 1000 && $g <= 50000) { $e['n']++; $e['soma'] += $g; $e['ips'][] = $ip; }
  } else {
    $r = $in['resposta'] ?? '';
    if (in_array($r, $PLANOS, true)) { $e['contagem'][$r]++; $e['n']++; $e['ips'][] = $ip; }
  }
}
$out = $enq === 'adivinhe'
  ? ['n' => $e['n'], 'media' => $e['n'] ? intval(round($e['soma'] / $e['n'])) : null]
  : ['n' => $e['n'], 'contagem' => $e['contagem']];
ftruncate($fp, 0); rewind($fp);
fwrite($fp, json_encode($db, JSON_UNESCAPED_UNICODE));
fflush($fp); flock($fp, LOCK_UN); fclose($fp);

/* contato de quem pediu novidades */
if ($enq === 'planos' && !empty($in['optin'])) {
  $contato = trim(mb_substr(strip_tags($in['contato'] ?? ''), 0, 120));
  $nome = trim(mb_substr(strip_tags($in['nome'] ?? ''), 0, 80));
  $resp = in_array($in['resposta'] ?? '', $PLANOS, true) ? $in['resposta'] : '';
  if (mb_strlen($contato) >= 6) {
    $novo = !file_exists("$DIR/contatos.csv");
    $f = fopen("$DIR/contatos.csv", 'a');
    flock($f, LOCK_EX);
    if ($novo) fputcsv($f, ['data', 'site', 'plano', 'nome', 'contato', 'idioma']);
    fputcsv($f, [date('Y-m-d H:i'), $site, $resp, $nome, $contato, ($in['idioma'] ?? 'pt') === 'en' ? 'en' : 'pt']);
    flock($f, LOCK_UN); fclose($f);
    @mail('reception@liveandlearnbrazil.com', "Novo contato pela enquete ($site)",
      "Bairro: $site\nPlano: $resp\nNome: $nome\nContato: $contato\n", "From: noreply@liveandlearnbrazil.com\r\nContent-Type: text/plain; charset=utf-8");
  }
}
echo json_encode($out);
