<?php
/* Matchmaking Imobiliário — base comum: configuração, banco, registro e utilidades.

   Segredos (token da Nonstop, chaves do Google, SMTP, senha do painel) ficam em config.php,
   fora da pasta pública: <raiz da conta>/_dados_boletins/matchmaking/config.php (mesmo padrão
   da enquete dos boletins). Para testes locais, a variável de ambiente MM_DADOS aponta outra pasta. */

date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

const MM_CAIXA = 'reception@liveandlearnbrazil.com';
const MM_REMETENTE = 'Andrea Longobardi · Matchmaking Imobiliário';
const MM_WHATSAPP = '5511954395027';
const MM_WHATSAPP_TXT = '+55 11 95439-5027';
const MM_ASSINATURA = 'Andrea Longobardi · CRECI-SP 338018-F';

function mm_dir_dados(): string {
    static $dir = null;
    if ($dir) return $dir;
    $dir = getenv('MM_DADOS') ?: dirname(__DIR__, 4) . '/_dados_boletins/matchmaking';
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    if (!is_dir($dir)) {   // hospedagem sem acesso acima da pasta pública: pasta protegida dentro do site
        $dir = dirname(__DIR__) . '/_dados';
        if (!is_dir($dir)) mkdir($dir, 0750, true);
        if (!file_exists("$dir/.htaccess")) file_put_contents("$dir/.htaccess", "Require all denied\n");
    }
    return $dir;
}

function mm_config(?string $chave = null, $padrao = null) {
    static $cfg = null;
    if ($cfg === null) {
        $arq = mm_dir_dados() . '/config.php';
        // sem acesso à pasta privada pelo envio de arquivos: config ao lado do código, em lib/ (bloqueada pelo .htaccess)
        if (!file_exists($arq)) $arq = __DIR__ . '/config.php';
        $cfg = file_exists($arq) ? (require $arq) : [];
        if (!is_array($cfg)) $cfg = [];
    }
    if ($chave === null) return $cfg;
    return $cfg[$chave] ?? $padrao;
}

/* URL pública da pasta /matchmaking/ (sem barra no fim) */
function mm_url_base(): string {
    return rtrim(mm_config('URL_BASE', 'https://realestate.liveandlearnbrazil.com/matchmaking'), '/');
}

function mm_db(): PDO {
    static $db = null;
    if ($db) return $db;
    $db = new PDO('sqlite:' . mm_dir_dados() . '/matchmaking.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('PRAGMA journal_mode=WAL; PRAGMA busy_timeout=5000;');
    $db->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS buscas (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      criado TEXT NOT NULL,
      status TEXT NOT NULL,             -- recebida, processando, aguardando_aprovacao, sem_resultado, enviada, descartada, erro
      idioma TEXT NOT NULL,
      email TEXT NOT NULL,
      nome TEXT, whatsapp TEXT,
      pedido TEXT NOT NULL,             -- JSON do formulário, normalizado
      resultado TEXT,                   -- JSON: origem, candidatos, escolhidos, fotos marcadas
      abertura TEXT,                    -- texto de abertura editável no painel
      prioridade INTEGER DEFAULT 0,
      erro TEXT,
      enviado_em TEXT,
      token TEXT NOT NULL               -- usado no descadastro
    );
    CREATE TABLE IF NOT EXISTS codigos (
      codigo TEXT PRIMARY KEY,
      base36 TEXT UNIQUE NOT NULL,
      criado TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS envios (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      data TEXT NOT NULL, dia TEXT NOT NULL,
      busca_id INTEGER, tipo TEXT NOT NULL, para TEXT NOT NULL,
      ok INTEGER NOT NULL, detalhe TEXT
    );
    CREATE TABLE IF NOT EXISTS descadastros (email TEXT PRIMARY KEY, data TEXT NOT NULL);
    CREATE TABLE IF NOT EXISTS log (id INTEGER PRIMARY KEY AUTOINCREMENT, data TEXT, nivel TEXT, msg TEXT);
    CREATE TABLE IF NOT EXISTS limites (chave TEXT PRIMARY KEY, valor TEXT);
    SQL);
    return $db;
}

function mm_log(string $nivel, string $msg): void {
    try {
        mm_db()->prepare('INSERT INTO log (data, nivel, msg) VALUES (?, ?, ?)')->execute([date('Y-m-d H:i:s'), $nivel, mb_substr($msg, 0, 4000)]);
    } catch (Throwable $e) { error_log("[matchmaking] $nivel $msg"); }
}

function mm_json_saida($dados, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
}

function mm_h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }

function mm_aleatorio(int $bytes = 16): string { return bin2hex(random_bytes($bytes)); }

/* Código próprio da Live & Learn (LL-XXXX), estável por imóvel. Sem 0/O/1/I para não confundir. */
function mm_codigo(string $base36): string {
    $db = mm_db();
    $st = $db->prepare('SELECT codigo FROM codigos WHERE base36 = ?');
    $st->execute([$base36]);
    if ($c = $st->fetchColumn()) return $c;
    $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    for ($t = 0; $t < 50; $t++) {
        $c = 'LL-';
        for ($i = 0; $i < 4; $i++) $c .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        try {
            $db->prepare('INSERT INTO codigos (codigo, base36, criado) VALUES (?, ?, ?)')->execute([$c, $base36, date('c')]);
            return $c;
        } catch (PDOException $e) { /* código repetido: tenta outro */ }
    }
    throw new RuntimeException('não foi possível gerar código próprio');
}

/* Requisição HTTP com cURL. Devolve [status, corpo decodificado ou texto]. */
function mm_http(string $metodo, string $url, array $cabecalhos = [], $corpo = null, int $timeout = 40): array {
    $c = curl_init($url);
    curl_setopt_array($c, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST => $metodo, CURLOPT_HTTPHEADER => $cabecalhos, CURLOPT_FOLLOWLOCATION => false,
    ]);
    if ($corpo !== null) curl_setopt($c, CURLOPT_POSTFIELDS, is_string($corpo) ? $corpo : json_encode($corpo));
    $txt = curl_exec($c);
    $status = (int)curl_getinfo($c, CURLINFO_HTTP_CODE);
    $erro = curl_error($c);
    curl_close($c);
    if ($txt === false) return [0, $erro];
    $json = json_decode($txt, true);
    return [$status, $json ?? $txt];
}

/* Distância em metros entre dois pontos (lat, lng). */
function mm_distancia(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $r = 6371000; $p = M_PI / 180;
    $a = sin(($lat2 - $lat1) * $p / 2) ** 2 + cos($lat1 * $p) * cos($lat2 * $p) * sin(($lng2 - $lng1) * $p / 2) ** 2;
    return 2 * $r * asin(min(1, sqrt($a)));
}

/* Ponto a $m metros na direção $graus (0 = norte). */
function mm_desloca(float $lat, float $lng, float $m, float $graus): array {
    $d = $m / 6371000; $b = deg2rad($graus); $f1 = deg2rad($lat); $l1 = deg2rad($lng);
    $f2 = asin(sin($f1) * cos($d) + cos($f1) * sin($d) * cos($b));
    $l2 = $l1 + atan2(sin($b) * sin($d) * cos($f1), cos($d) - sin($f1) * sin($f2));
    return [rad2deg($f2), rad2deg($l2)];
}

/* Algoritmo de polyline do Google (precisão 1e-5). */
function mm_polyline(array $pontos): string {
    $saida = ''; $plat = 0; $plng = 0;
    $num = function (int $v): string {
        $v = $v < 0 ? ~($v << 1) : $v << 1; $s = '';
        while ($v >= 0x20) { $s .= chr((0x20 | ($v & 0x1f)) + 63); $v >>= 5; }
        return $s . chr($v + 63);
    };
    foreach ($pontos as [$lat, $lng]) {
        $ilat = (int)round($lat * 1e5); $ilng = (int)round($lng * 1e5);
        $saida .= $num($ilat - $plat) . $num($ilng - $plng);
        $plat = $ilat; $plng = $ilng;
    }
    return $saida;
}

function mm_circulo(float $lat, float $lng, float $raio, int $n = 32): array {
    $pts = [];
    for ($i = 0; $i <= $n; $i++) $pts[] = mm_desloca($lat, $lng, $raio, 360 * $i / $n);
    return $pts;
}

/* Distrito (nome sem acento, caixa alta) que contém o ponto. */
function mm_distrito(float $lat, float $lng): ?string {
    static $ds = null;
    $ds ??= json_decode(file_get_contents(__DIR__ . '/dados/distritos.json'), true);
    foreach ($ds as $d) {
        foreach ($d['aneis'] as $anel) {
            $dentro = false; $n = count($anel);
            for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
                [$xi, $yi] = $anel[$i]; [$xj, $yj] = $anel[$j];
                if ((($yi > $lat) !== ($yj > $lat)) && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi)) $dentro = !$dentro;
            }
            if ($dentro) return $d['nome'];
        }
    }
    return null;
}

/* Mediana do ITBI convertida para área privativa (fator 1,35 nos studios a 1,95 nas unidades grandes,
   o mesmo critério dos boletins). */
function mm_mediana_privativa(?string $distrito, ?float $area_privativa): ?float {
    static $md = null;
    $md ??= json_decode(file_get_contents(__DIR__ . '/dados/itbi_medianas.json'), true);
    if (!$distrito || !$area_privativa || empty($md[$distrito])) return null;
    $fator = 1.35 + (min(max($area_privativa, 35), 250) - 35) / 215 * 0.6;
    $area_iptu = $area_privativa * $fator;
    $faixa = $area_iptu > 150 ? '151-500m2' : '30-150m2';
    $linha = $md[$distrito][$faixa] ?? reset($md[$distrito]);
    return $linha ? $linha['mediana_m2'] * $fator : null;
}
