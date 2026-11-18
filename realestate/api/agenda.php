<?php
/* Horários livres para visitas, a partir da agenda Google da Andrea (17/11/2026).

   GET → JSON {fonte: "agenda"|"padrao", dias: [{data: "2026-11-19", periodos: ["manha","tarde"]}, ...]}

   A agenda é lida pelo "endereço secreto em formato iCal" (Google Agenda → Configurações da agenda →
   Integrar agenda), guardado como AGENDA_ICS no config.php privado (fora da pasta pública). Só leitura:
   o visitante nunca vê título, local ou horário dos compromissos, só se a manhã ou a tarde de cada dia
   tem uma janela livre para visita. Sem AGENDA_ICS (ou se a leitura falhar), devolve os dias úteis com
   os dois períodos, e a Andrea confirma pelo WhatsApp como antes.

   Regras: próximos 12 dias, a partir de amanhã; domingo entra (os domingos sem atendimento são fechados na agenda
   com um evento de dia inteiro "Ocupado"); sábado só de manhã;
   manhã = 9h–12h, tarde = 14h–18h; o período vale se tiver 90 minutos seguidos livres. */
define('AG_LIB', is_dir(__DIR__ . '/../matchmaking/lib') ? __DIR__ . '/../matchmaking/lib' : getenv('MM_LIB'));
require_once AG_LIB . '/base.php';

const AG_DIAS = 12;
const AG_PERIODOS = ['manha' => [9 * 60, 12 * 60], 'tarde' => [14 * 60, 18 * 60]];
const AG_JANELA = 90;          // minutos livres seguidos para caber uma visita
const AG_VALIDADE = 15 * 60;   // a agenda é relida a cada 15 minutos

date_default_timezone_set('America/Sao_Paulo');
header('Cache-Control: no-store');

$url = trim((string)mm_config('AGENDA_ICS', ''), " \t\"'");
$ocupado = null;
if ($url === '' && PHP_SAPI === 'cli') fwrite(STDERR, "motivo: AGENDA_ICS está vazio no config.php\n");
if ($url !== '') {
    try { $ocupado = ag_ocupado(ag_ics($url)); }
    catch (Throwable $e) {
        mm_log('erro', 'agenda: ' . $e->getMessage());
        if (PHP_SAPI === 'cli') fwrite(STDERR, 'motivo: ' . $e->getMessage() . "\n");   // só no teste local (TESTAR-AGENDA.bat)
    }
}
mm_json_saida(['fonte' => $ocupado === null ? 'padrao' : 'agenda', 'dias' => ag_dias($ocupado ?? [])]);

/* Texto do iCal, com cache curto em arquivo (pasta de dados privada). */
function ag_ics(string $url): string {
    $cache = mm_dir_dados() . '/agenda.ics';
    if (is_file($cache) && time() - filemtime($cache) < AG_VALIDADE) return file_get_contents($cache);
    // o Google costuma responder ao endereço iCal com redirecionamento: segue até 5 (mm_http não segue)
    $c = curl_init($url);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 25, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_USERAGENT => 'boletins-agenda/1.0']);
    $corpo = curl_exec($c);
    $status = (int)curl_getinfo($c, CURLINFO_HTTP_CODE);
    $erro = curl_error($c);
    curl_close($c);
    if ($status !== 200 || !str_contains((string)$corpo, 'BEGIN:VCALENDAR')) {
        if (is_file($cache)) return file_get_contents($cache);   // vale a última leitura boa
        $host = parse_url($url, PHP_URL_HOST) ?: 'endereço inválido';
        $dica = match (true) {
            $corpo === false => "sem conexão ($erro)",
            $status === 404 => 'o Google não achou a agenda: confira se copiou o "Endereço SECRETO no formato iCal" (não o público)',
            $status === 401, $status === 403 => 'acesso negado: copie de novo o endereço secreto no formato iCal',
            $status === 200 => 'a resposta não é um arquivo de agenda (iCal)',
            default => "resposta $status",
        };
        throw new RuntimeException("$dica [servidor: $host]");
    }
    file_put_contents($cache, $corpo);
    return $corpo;
}

/* Intervalos ocupados [início, fim] em timestamp, só os que caem na janela consultada. */
function ag_ocupado(string $ics): array {
    $ini = strtotime('tomorrow'); $fim = $ini + (AG_DIAS + 1) * 86400;
    $ics = preg_replace("/\r?\n[ \t]/", '', $ics);   // desdobra linhas continuadas
    preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', $ics, $m);
    $out = [];
    foreach ($m[1] as $ev) {
        $p = ag_props($ev);
        if (strtoupper($p['TRANSP'][0]['v'] ?? '') === 'TRANSPARENT') continue;   // marcado como "disponível"
        if (strtoupper($p['STATUS'][0]['v'] ?? '') === 'CANCELLED') continue;
        if (empty($p['DTSTART'])) continue;
        $a = ag_data($p['DTSTART'][0]);
        $b = !empty($p['DTEND']) ? ag_data($p['DTEND'][0]) : ($a + (str_contains($p['DTSTART'][0]['k'], 'VALUE=DATE') ? 86400 : 3600));
        $dur = max(0, $b - $a);
        $excl = [];
        foreach ($p['EXDATE'] ?? [] as $x) foreach (explode(',', $x['v']) as $v) $excl[] = ag_data(['k' => $x['k'], 'v' => $v]);
        foreach (ag_repeticoes($a, $p['RRULE'][0]['v'] ?? null, $fim) as $s) {
            if (in_array($s, $excl, true)) continue;
            if ($s + $dur > $ini && $s < $fim) $out[] = [$s, $s + $dur];
        }
    }
    return $out;
}

function ag_props(string $ev): array {
    $p = [];
    foreach (preg_split('/\r?\n/', trim($ev)) as $linha) {
        if (!preg_match('/^([A-Z-]+)([^:]*):(.*)$/', $linha, $x)) continue;
        $p[$x[1]][] = ['k' => $x[2], 'v' => trim($x[3])];
    }
    return $p;
}

/* DTSTART/DTEND: "20261119T140000Z" (UTC), "20261119T110000" com TZID, ou "20261119" (dia inteiro). */
function ag_data(array $c): int {
    $v = $c['v'];
    if (preg_match('/TZID=([^;:]+)/', $c['k'], $tz)) {
        try { return (new DateTime($v, new DateTimeZone($tz[1])))->getTimestamp(); } catch (Throwable $e) {}
    }
    if (str_ends_with($v, 'Z')) return (new DateTime($v, new DateTimeZone('UTC')))->getTimestamp();
    return (new DateTime($v))->getTimestamp();   // hora local de São Paulo ou dia inteiro
}

/* Expansão simples das repetições (DAILY e WEEKLY, com INTERVAL, BYDAY, COUNT e UNTIL); outras: só a primeira. */
function ag_repeticoes(int $inicio, ?string $rrule, int $limite): array {
    if (!$rrule) return [$inicio];
    $r = [];
    foreach (explode(';', $rrule) as $par) { [$k, $v] = array_pad(explode('=', $par, 2), 2, ''); $r[$k] = $v; }
    $freq = $r['FREQ'] ?? ''; $passo = max(1, (int)($r['INTERVAL'] ?? 1));
    $ate = isset($r['UNTIL']) ? min($limite, ag_data(['k' => '', 'v' => $r['UNTIL']]) + 1) : $limite;
    $max = isset($r['COUNT']) ? (int)$r['COUNT'] : PHP_INT_MAX;
    if (!in_array($freq, ['DAILY', 'WEEKLY'], true)) return [$inicio];
    $dias = ['SU' => 0, 'MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6];
    $byday = isset($r['BYDAY']) ? array_map(fn($d) => $dias[substr($d, -2)] ?? null, explode(',', $r['BYDAY'])) : null;
    $out = []; $n = 0; $d0 = new DateTime('@' . $inicio); $d0->setTimezone(new DateTimeZone('America/Sao_Paulo'));
    for ($i = 0; $n < $max && $i < 4000; $i++) {
        $d = (clone $d0)->modify("+$i day"); $t = $d->getTimestamp();
        if ($t >= $ate) break;
        $semanas = intdiv($i + (int)$d0->format('w'), 7);
        if ($freq === 'DAILY' && $i % $passo) continue;
        if ($freq === 'WEEKLY') {
            if ($semanas % $passo) continue;
            $alvo = $byday ?? [(int)$d0->format('w')];
            if (!in_array((int)$d->format('w'), $alvo, true)) continue;
        }
        $n++;
        $out[] = $t;
    }
    return $out;
}

/* Dias e períodos com janela livre de AG_JANELA minutos. */
function ag_dias(array $ocupado): array {
    $out = [];
    for ($i = 1; $i <= AG_DIAS; $i++) {
        $dia = strtotime("+$i day", strtotime('today'));
        $sem = (int)date('w', $dia);
        // domingo entra (17/11/2026: a Andrea atende domingos a cada 15 dias; os outros ela fecha na agenda)
        $per = [];
        foreach (AG_PERIODOS as $nome => [$a, $b]) {
            if ($sem === 6 && $nome === 'tarde') continue;   // sábado só de manhã
            if (ag_tem_janela($dia + $a * 60, $dia + $b * 60, $ocupado)) $per[] = $nome;
        }
        if ($per) $out[] = ['data' => date('Y-m-d', $dia), 'periodos' => $per];
    }
    return $out;
}

function ag_tem_janela(int $ini, int $fim, array $ocupado): bool {
    $blocos = array_values(array_filter($ocupado, fn($o) => $o[1] > $ini && $o[0] < $fim));
    usort($blocos, fn($x, $y) => $x[0] <=> $y[0]);
    $cursor = $ini;
    foreach ($blocos as [$a, $b]) {
        if ($a - $cursor >= AG_JANELA * 60) return true;
        $cursor = max($cursor, $b);
    }
    return $fim - $cursor >= AG_JANELA * 60;
}
