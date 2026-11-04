<?php
/* Envio pela caixa reception@liveandlearnbrazil.com (smtp.hostinger.com, 465 SSL ou 587 STARTTLS),
   com autenticação, para que SPF e DKIM do domínio se apliquem.

   Limite diário: cada envio automático entra na tabela envios. A partir de 80% do limite
   (LIMITE_DIARIO na configuração, padrão 1000) a Andrea recebe um aviso; em 95% os envios
   automáticos param até o dia seguinte, para preservar a caixa para o trabalho do dia a dia. */
require_once __DIR__ . '/base.php';

class MmLimiteDiario extends RuntimeException {}

function mm_enviados_hoje(): int {
    $st = mm_db()->prepare('SELECT COUNT(*) FROM envios WHERE dia = ? AND ok = 1');
    $st->execute([date('Y-m-d')]);
    return (int)$st->fetchColumn();
}

function mm_limite_diario(): int { return (int)mm_config('LIMITE_DIARIO', 1000); }

/* $html: corpo HTML completo; $texto: versão em texto puro. */
function mm_enviar(string $para, string $assunto, string $texto, string $html, string $tipo, ?int $busca_id = null): bool {
    // trava de teste: com REDIRECIONAR_PARA na configuração, nenhum e-mail sai para cliente
    if ($desvio = mm_config('REDIRECIONAR_PARA')) {
        if ($para !== $desvio) $assunto = "[TESTE · seria para $para] $assunto";
        $para = $desvio;
    }
    $limite = mm_limite_diario();
    $feitos = mm_enviados_hoje();
    if ($feitos >= 0.95 * $limite) throw new MmLimiteDiario("$feitos envios hoje; limite $limite");

    $limite_bloco = '=_mm_' . mm_aleatorio(8);
    $dominio = 'liveandlearnbrazil.com';
    $cab = [
        'Date: ' . date('r'),
        'From: ' . mm_mime(MM_REMETENTE) . ' <' . MM_CAIXA . '>',
        'To: <' . $para . '>',
        'Reply-To: <' . MM_CAIXA . '>',
        'Subject: ' . mm_mime($assunto),
        'Message-ID: <' . mm_aleatorio(12) . '@' . $dominio . '>',
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $limite_bloco . '"',
    ];
    if ($tipo === 'cliente') {
        $cab[] = 'List-Unsubscribe: <mailto:' . MM_CAIXA . '?subject=descadastrar>';
    }
    $corpo = "--$limite_bloco\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($texto))
        . "--$limite_bloco\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($html)) . "--$limite_bloco--\r\n";
    $mensagem = implode("\r\n", $cab) . "\r\n\r\n" . $corpo;

    $ok = false; $detalhe = '';
    try { mm_smtp_enviar($para, $mensagem); $ok = true; }
    catch (Throwable $e) { $detalhe = $e->getMessage(); mm_log('erro', "SMTP para $tipo: $detalhe"); }

    mm_db()->prepare('INSERT INTO envios (data, dia, busca_id, tipo, para, ok, detalhe) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([date('Y-m-d H:i:s'), date('Y-m-d'), $busca_id, $tipo, $para, $ok ? 1 : 0, mb_substr($detalhe, 0, 500)]);

    if ($ok) mm_aviso_limite($feitos + 1, $limite);
    return $ok;
}

function mm_aviso_limite(int $feitos, int $limite): void {
    if ($feitos < 0.8 * $limite) return;
    $db = mm_db(); $hoje = date('Y-m-d');
    $st = $db->prepare("SELECT valor FROM limites WHERE chave = 'aviso_limite'"); $st->execute();
    if ($st->fetchColumn() === $hoje) return;
    $db->prepare("INSERT OR REPLACE INTO limites (chave, valor) VALUES ('aviso_limite', ?)")->execute([$hoje]);
    try {
        mm_smtp_enviar(MM_CAIXA, implode("\r\n", [
            'Date: ' . date('r'), 'From: ' . mm_mime(MM_REMETENTE) . ' <' . MM_CAIXA . '>', 'To: <' . MM_CAIXA . '>',
            'Subject: ' . mm_mime('Matchmaking: envios perto do limite diário'), 'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ]) . "\r\n\r\nHoje já saíram $feitos e-mails automáticos, de um limite de $limite.\r\nAo chegar a 95%, os envios automáticos param até amanhã.\r\n");
    } catch (Throwable $e) { mm_log('erro', 'aviso de limite não enviado: ' . $e->getMessage()); }
}

/* Aviso interno para a própria caixa (não conta como envio a cliente, mas entra no total do dia). */
function mm_enviar_interno(string $assunto, string $texto, ?string $html = null): bool {
    $html ??= '<pre style="font-family:Arial,sans-serif;font-size:14px;white-space:pre-wrap">' . mm_h($texto) . '</pre>';
    try { return mm_enviar(MM_CAIXA, $assunto, $texto, $html, 'interno'); }
    catch (Throwable $e) { mm_log('erro', 'aviso interno não enviado: ' . $e->getMessage()); return false; }
}

function mm_mime(string $s): string {
    return preg_match('/[^\x20-\x7e]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
}

function mm_smtp_enviar(string $para, string $mensagem): void {
    $host = mm_config('SMTP_HOST', 'smtp.hostinger.com');
    $porta = (int)mm_config('SMTP_PORT', 465);
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
    $alvo = ($porta === 465 ? 'ssl://' : 'tcp://') . $host . ':' . $porta;
    $s = @stream_socket_client($alvo, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
    if (!$s) throw new RuntimeException("conexão SMTP falhou: $errstr");
    stream_set_timeout($s, 30);
    $ler = function () use ($s): string {
        $r = '';
        while (($l = fgets($s, 1024)) !== false) { $r .= $l; if (strlen($l) < 4 || $l[3] === ' ') break; }
        return $r;
    };
    $cmd = function (string $c, array $esperado, bool $secreto = false) use ($s, $ler): string {
        if ($c !== '') fwrite($s, $c . "\r\n");
        $r = $ler();
        if (!in_array((int)substr($r, 0, 3), $esperado, true)) {
            $mostra = $secreto ? '(credencial)' : mb_substr($c, 0, 40);
            throw new RuntimeException("SMTP $mostra -> " . trim($r));
        }
        return $r;
    };
    $cmd('', [220]);
    $cmd('EHLO liveandlearnbrazil.com', [250]);
    if ($porta !== 465) {
        $cmd('STARTTLS', [220]);
        if (!stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('STARTTLS falhou');
        $cmd('EHLO liveandlearnbrazil.com', [250]);
    }
    $cmd('AUTH LOGIN', [334]);
    $cmd(base64_encode((string)mm_config('SMTP_USER')), [334], true);
    $cmd(base64_encode((string)mm_config('SMTP_PASS')), [235], true);
    $cmd('MAIL FROM:<' . MM_CAIXA . '>', [250]);
    $cmd('RCPT TO:<' . $para . '>', [250, 251]);
    $cmd('DATA', [354]);
    $dados = preg_replace('/^\./m', '..', str_replace(["\r\n", "\n"], ["\n", "\r\n"], $mensagem));
    $cmd($dados . "\r\n.", [250]);
    $cmd('QUIT', [221]);
    fclose($s);
}
