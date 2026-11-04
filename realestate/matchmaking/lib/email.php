<?php
/* E-mail ao cliente (especificação 3.4 e regras de privacidade da seção 2).

   - Fotos: baixadas da Nonstop, redimensionadas e regravadas em JPEG (sem metadados), publicadas
     em /matchmaking/f/ com nome aleatório. O endereço original da foto não vai no e-mail.
   - Mapa: círculo de 100 m em volta de um ponto deslocado de 20 a 60 m do imóvel (semente fixa
     por imóvel), de modo que o imóvel fica dentro do círculo sem ficar no centro.
   - Antes de enviar, o HTML final é varrido em busca de rua, número, condomínio, código Nonstop,
     corretor e imobiliária vindos da API. Se algum aparecer, o envio é bloqueado. */
require_once __DIR__ . '/busca.php';

const MM_RAIO_MAPA = 100;

function mm_dir_publico(): string {
    $dir = dirname(__DIR__) . '/f';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (!file_exists("$dir/.htaccess")) {
        file_put_contents("$dir/.htaccess", "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar)$\">\n  Require all denied\n</FilesMatch>\n");
    }
    return $dir;
}

function mm_centro_circulo(array $c): array {
    $h = crc32('mm|' . $c['base36']);
    $angulo = $h % 360;
    $dist = 20 + (($h >> 9) % 41);   // 20 a 60 m
    return mm_desloca($c['lat'], $c['lng'], $dist, $angulo);
}

/* Baixa, reduz e regrava uma imagem; devolve a URL pública. */
function mm_publicar_foto(string $url_origem, int $largura = 1000): ?string {
    [$st, $bytes] = mm_http('GET', $url_origem, [], null, 40);
    if ($st !== 200 || !is_string($bytes) || strlen($bytes) < 500) return null;
    $img = @imagecreatefromstring($bytes);
    if (!$img) return null;
    $w = imagesx($img); $h = imagesy($img);
    if ($w > $largura) {
        $nh = (int)round($h * $largura / $w);
        $nova = imagecreatetruecolor($largura, $nh);
        imagecopyresampled($nova, $img, 0, 0, 0, 0, $largura, $nh, $w, $h);
        imagedestroy($img); $img = $nova;
    }
    $nome = mm_aleatorio(16) . '.jpg';
    imagejpeg($img, mm_dir_publico() . '/' . $nome, 80);
    imagedestroy($img);
    return mm_url_base() . '/f/' . $nome;
}

function mm_publicar_mapa(array $c): ?string {
    [$lat, $lng] = mm_centro_circulo($c);
    $png = gg_mapa_circulo($lat, $lng, MM_RAIO_MAPA);
    if (!$png) return null;
    $nome = mm_aleatorio(16) . '.png';
    file_put_contents(mm_dir_publico() . '/' . $nome, $png);
    return mm_url_base() . '/f/' . $nome;
}

/* Publica fotos e mapa das opções escolhidas (uma vez; reaproveita se já publicado). */
function mm_preparar_midia(array &$c): void {
    $marcadas = $c['fotos'] ?? [];
    $chave = implode(',', $marcadas);
    if (($c['publicado']['chave'] ?? null) === $chave && !empty($c['publicado']['mapa'])) return;
    $fotos = [];
    foreach ($marcadas as $i) {
        if (!isset($c['imagens'][$i])) continue;
        if ($u = mm_publicar_foto($c['imagens'][$i])) $fotos[] = $u;
    }
    $c['publicado'] = ['chave' => $chave, 'fotos' => $fotos, 'mapa' => mm_publicar_mapa($c)];
}

function mm_card_html(array $c, string $lang, ?array $midia, string $modo, string $destino): string {
    $az = '#34425E'; $champ = '#DCC792'; $goiaba = '#E0604E'; $ink = '#232B3A'; $muted = '#56607A';
    $serif = "'Libertinus Sans',Georgia,serif"; $sans = "'Open Sans',Arial,sans-serif"; $narrow = "'PT Sans Narrow','Arial Narrow',Arial,sans-serif";
    $fotos = $midia['fotos'] ?? []; $mapa = $midia['mapa'] ?? null;
    $h = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FFFFFF;border-top:3px solid ' . $champ . ';margin:0 0 22px">';
    $h .= '<tr><td style="padding:18px 20px 6px;font-family:' . $narrow . ';font-size:13px;letter-spacing:.1em;text-transform:uppercase;color:' . $muted . '">' . mm_h(mm_t('codigo', $lang, $c['codigo'])) . ' · ' . mm_h($c['bairro']) . '</td></tr>';
    $h .= '<tr><td style="padding:0 20px 4px;font-family:' . $serif . ';font-weight:700;font-size:22px;line-height:1.25;color:' . $ink . '">' . mm_h($c['texto_titulo']) . '</td></tr>';
    $h .= '<tr><td style="padding:6px 20px 12px;font-family:' . $serif . ';font-weight:700;font-size:20px;color:' . $az . '">'
        . mm_h(mm_t('trajeto', $lang, $c['tempo'], mm_t_modo($modo, $lang), $destino)) . '</td></tr>';
    if ($fotos) {
        $h .= '<tr><td style="padding:0 20px">';
        foreach ($fotos as $i => $f) {
            $w = $i === 0 ? '100%' : '49%';
            $h .= '<img src="' . mm_h($f) . '" alt="" width="' . ($i === 0 ? 520 : 255) . '" style="display:inline-block;width:' . $w . ';max-width:' . $w . ';height:auto;border:0;margin:0 0 6px' . ($i > 0 && $i % 2 === 0 ? ';margin-left:2%' : '') . '">';
        }
        $h .= '</td></tr>';
    }
    $linhas = [];
    if ($c['ocultar_preco'] || !$c['preco']) $linhas[] = mm_t('preco_consulta', $lang);
    else $linhas[] = mm_t($c['transacao'] === 'LOCACAO' ? 'aluguel' : 'preco', $lang) . ': <b>' . mm_reais($c['preco']) . ($c['transacao'] === 'LOCACAO' ? mm_t('mes', $lang) : '') . '</b>';
    $extra = [];
    if ($c['condominio']) $extra[] = mm_t('condominio', $lang) . ' ' . mm_reais($c['condominio']) . mm_t('mes', $lang);
    if ($c['iptu']) $extra[] = mm_t('iptu', $lang) . ' ' . mm_reais($c['iptu']) . mm_t('ano_txt', $lang);
    if ($extra) $linhas[] = implode(' · ', $extra);
    $det = [];
    if ($c['andar_faixa']) $det[] = mm_t('andar', $lang, mm_t($c['andar_faixa'], $lang));
    if ($c['ano']) $det[] = mm_t('ano', $lang, (int)$c['ano']);
    if ($det) $linhas[] = implode(' · ', $det);
    $h .= '<tr><td style="padding:10px 20px 4px;font-family:' . $sans . ';font-size:15px;line-height:1.6;color:' . $ink . '">' . implode('<br>', $linhas) . '</td></tr>';
    foreach ([['no_imovel', 'caracs'], ['no_condominio', 'caracs_cond']] as [$rot, $campo]) {
        $itens = array_map(fn($k) => mm_rotulo(MM_CARAC, $k, $lang), array_slice($c[$campo] ?? [], 0, 10));
        if (!$itens) continue;
        $h .= '<tr><td style="padding:8px 20px 0;font-family:' . $sans . ';font-size:14px;line-height:1.55;color:' . $ink . '"><span style="font-family:' . $narrow . ';font-size:12px;letter-spacing:.09em;text-transform:uppercase;color:' . $muted . '">' . mm_h(mm_t($rot, $lang)) . '</span><br>' . mm_h(implode(' · ', $itens)) . '</td></tr>';
    }
    if (!empty($c['texto_livre'])) {
        $h .= '<tr><td style="padding:12px 20px 0;font-family:' . $sans . ';font-size:15px;line-height:1.6;color:' . $ink . '">' . nl2br(mm_h($c['texto_livre'])) . '</td></tr>';
    }
    if ($mapa) {
        $h .= '<tr><td style="padding:14px 20px 0"><img src="' . mm_h($mapa) . '" alt="" width="520" style="display:block;width:100%;height:auto;border:0"></td></tr>';
    }
    $h .= '<tr><td style="padding:6px 20px 0;font-family:' . $sans . ';font-size:13px;color:' . $muted . '">' . mm_h(mm_t('local', $lang, $c['bairro'], MM_RAIO_MAPA)) . '</td></tr>';
    $wa = 'https://wa.me/' . MM_WHATSAPP . '?text=' . rawurlencode(mm_t('wa_msg', $lang, $c['codigo']));
    $h .= '<tr><td style="padding:16px 20px 20px"><a href="' . mm_h($wa) . '" style="display:inline-block;background:' . $az . ';color:#FFFFFF;font-family:' . $sans . ';font-weight:600;font-size:15px;text-decoration:none;padding:13px 22px;border-radius:24px">' . mm_h(mm_t('botao', $lang)) . '</a></td></tr>';
    $h .= '</table>';
    return $h;
}

/* Monta o e-mail final. $previa = true usa as fotos originais (só para o painel). */
function mm_montar_email(array $b, bool $previa = false): array {
    $lang = $b['idioma']; $p = $b['pedido']; $r = $b['resultado'] ?? [];
    $destino = $r['origem']['rotulo'] ?? null ?: mm_t('seu_destino', $lang);
    $nome = trim((string)$b['nome']);
    $ola = mm_t('ola', $lang, $nome ? ($lang === 'zh' ? '，' . $nome : ', ' . $nome) : '');
    $opcoes = mm_opcoes_escolhidas($b);
    $sem = !$opcoes;

    $cards = ''; $texto = $ola . "\n\n";
    if ($sem) {
        $texto .= mm_t('sem_resultado', $lang) . "\n";
    } else {
        $texto .= trim((string)$b['abertura']) . "\n\n";
        foreach ($opcoes as $c) {
            $c['transacao'] = $r['transacao'] ?? 'VENDA';
            $c['texto_titulo'] = mm_descricao($c, $lang);
            $c['texto_livre'] = $r['textos'][$c['base36']] ?? '';
            $midia = $previa
                ? ['fotos' => array_values(array_intersect_key($c['imagens'], array_flip($c['fotos'] ?? []))), 'mapa' => null]
                : ($c['publicado'] ?? null);
            $cards .= mm_card_html($c, $lang, $midia, $p['modo'], $destino);
            $texto .= mm_t('codigo', $lang, $c['codigo']) . ' · ' . $c['bairro'] . "\n" . $c['texto_titulo'] . "\n"
                . mm_t('trajeto', $lang, $c['tempo'], mm_t_modo($p['modo'], $lang), $destino) . "\n"
                . ($c['ocultar_preco'] || !$c['preco'] ? mm_t('preco_consulta', $lang) : mm_t('preco', $lang) . ': ' . mm_reais($c['preco'])) . "\n"
                . 'WhatsApp: https://wa.me/' . MM_WHATSAPP . '?text=' . rawurlencode(mm_t('wa_msg', $lang, $c['codigo'])) . "\n\n";
        }
    }
    $descad = mm_url_base() . '/descadastrar.php?t=' . $b['token'];
    $corpo_abertura = $sem ? nl2br(mm_h(mm_t('sem_resultado', $lang))) : nl2br(mm_h(trim((string)$b['abertura'])));
    $narrow = "'PT Sans Narrow','Arial Narrow',Arial,sans-serif"; $sans = "'Open Sans',Arial,sans-serif";
    $html = '<!doctype html><html lang="' . ($lang === 'zh' ? 'zh-CN' : ($lang === 'en' ? 'en' : 'pt-BR')) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . mm_h(mm_t($sem ? 'assunto_sem' : 'assunto', $lang)) . '</title></head>'
        . '<body style="margin:0;background:#F6F9FF"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F6F9FF"><tr><td align="center" style="padding:20px 10px">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px">'
        . '<tr><td style="background:#34425E;padding:20px 24px;font-family:' . $narrow . ';font-size:15px;letter-spacing:.12em;text-transform:uppercase;color:#FFFFFF">' . mm_h(mm_t('cabecalho', $lang)) . '</td></tr>'
        . '<tr><td style="background:#FFFFFF;border-top:3px solid #DCC792;padding:22px 24px;font-family:' . $sans . ';font-size:15px;line-height:1.6;color:#232B3A">' . mm_h($ola) . '<br><br>' . $corpo_abertura . '</td></tr>'
        . '<tr><td style="padding:20px 0 0">' . $cards . '</td></tr>'
        . '<tr><td style="padding:0 4px 18px;font-family:' . $sans . ';font-size:12px;line-height:1.5;color:#56607A">' . mm_h(mm_t('estimativa', $lang)) . '</td></tr>'
        . '<tr><td style="background:#34425E;padding:20px 24px;font-family:' . $sans . ';font-size:13px;line-height:1.6;color:#C3CAD6">'
        . mm_h(mm_t('atendimento', $lang)) . '<br><span style="font-family:' . $narrow . ';letter-spacing:.06em;text-transform:uppercase;color:#DCC792">' . mm_h(MM_ASSINATURA) . '</span><br>'
        . 'WhatsApp ' . MM_WHATSAPP_TXT . ' · ' . MM_CAIXA . '<br><br><a href="' . mm_h($descad) . '" style="color:#C3CAD6">' . mm_h(mm_t('descadastrar', $lang)) . '</a></td></tr>'
        . '</table></td></tr></table></body></html>';
    $texto .= mm_t('estimativa', $lang) . "\n\n--\n" . mm_t('atendimento', $lang) . "\n" . MM_ASSINATURA . "\nWhatsApp " . MM_WHATSAPP_TXT . ' · ' . MM_CAIXA
        . "\n\n" . mm_t('descadastrar', $lang) . ': ' . $descad . "\n";
    return ['assunto' => mm_t($sem ? 'assunto_sem' : 'assunto', $lang), 'html' => $html, 'texto' => $texto, 'opcoes' => $opcoes];
}

function mm_opcoes_escolhidas(array $b): array {
    $r = $b['resultado'] ?? [];
    $por_id = [];
    foreach ($r['candidatos'] ?? [] as $c) $por_id[$c['base36']] = $c;
    $saida = [];
    foreach ($r['escolhidos'] ?? [] as $id) if (isset($por_id[$id])) $saida[] = $por_id[$id];
    return $saida;
}

function mm_sem_acento(string $s): string {
    $t = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    return mb_strtolower(preg_replace('/\s+/', ' ', $t !== false ? $t : $s));
}

/* Procura no e-mail final dados que identificam o imóvel. Devolve a lista do que achou. */
function mm_verificar_privacidade(string $html, string $texto, array $opcoes): array {
    // texto visível + versão em texto puro (os nomes aleatórios das imagens ficam de fora)
    $alvo = mm_sem_acento(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8') . ' ' . $texto);
    $achados = [];
    $genericos = ['rua', 'avenida', 'alameda', 'travessa', 'praca', 'sao paulo', 'condominio', 'edificio', 'residencial'];
    foreach ($opcoes as $c) {
        $i = $c['interno'] ?? [];
        $rua = preg_replace('/^(r|rua|av|avenida|al|alameda|tv|travessa|pca|praca|estr|estrada)\.?\s+/i', '', trim($i['rua'] ?? ''));
        $termos = [
            'rua' => $rua, 'condomínio' => $i['condominio'] ?? '', 'corretor' => $i['corretor'] ?? '',
            'imobiliária' => $i['imobiliaria'] ?? '', 'código Nonstop' => $c['base36'] ?? '',
        ];
        foreach ($termos as $rot => $t) {
            $t = mm_sem_acento(trim($t));
            if (mb_strlen($t) < 4 || in_array($t, $genericos, true)) continue;
            if (str_contains($alvo, $t)) $achados[] = "$rot ({$c['codigo']})";
        }
        $num = trim($i['numero'] ?? '');
        if ($num !== '' && $rua && preg_match('/' . preg_quote(mm_sem_acento($rua), '/') . '\W{0,3}' . preg_quote($num, '/') . '\b/', $alvo)) {
            $achados[] = "número ({$c['codigo']})";
        }
        if (!empty($i['url']) && str_contains($alvo, mm_sem_acento($i['url']))) $achados[] = "link do anúncio ({$c['codigo']})";
    }
    if (str_contains($alvo, 'usenonstop') || stripos($html, 'usenonstop') !== false) $achados[] = 'endereço da Nonstop';
    if (preg_match('/\b\d{1,2}[ºo°]?\s*andar\b/u', $alvo)) $achados[] = 'andar exato';
    return array_values(array_unique($achados));
}

/* Prepara mídia, verifica privacidade, envia, cria a lead. Devolve [ok, mensagem]. */
function mm_enviar_busca(int $id): array {
    $b = mm_buscar_registro($id);
    if (!$b) return [false, 'busca não encontrada'];
    if ($b['status'] === 'enviada') return [false, 'esta busca já foi enviada'];
    $st = mm_db()->prepare('SELECT 1 FROM descadastros WHERE email = ?'); $st->execute([mb_strtolower($b['email'])]);
    if ($st->fetchColumn()) return [false, 'o cliente pediu descadastro'];

    $r = $b['resultado'] ?? [];
    $r['candidatos'] ??= [];
    foreach ($r['candidatos'] as &$c) {
        if (in_array($c['base36'], $r['escolhidos'] ?? [], true)) mm_preparar_midia($c);
    }
    unset($c);
    mm_salvar_resultado($id, ['resultado' => $r]);
    $b['resultado'] = $r;

    $e = mm_montar_email($b);
    $achados = mm_verificar_privacidade($e['html'], $e['texto'], $e['opcoes']);
    if ($achados) {
        mm_log('aviso', "busca $id bloqueada na verificação: " . implode(', ', $achados));
        return [false, 'Envio bloqueado: o e-mail contém ' . implode(', ', $achados) . '. Ajuste o texto e tente de novo.'];
    }
    try {
        $ok = mm_enviar($b['email'], $e['assunto'], $e['texto'], $e['html'], 'cliente', $id);
    } catch (MmLimiteDiario $ex) {
        return [false, 'Limite diário de envios atingido (' . $ex->getMessage() . '). O envio fica para amanhã.'];
    }
    if (!$ok) return [false, 'O servidor de e-mail recusou o envio. Detalhe no registro.'];
    mm_salvar_resultado($id, ['status' => 'enviada', 'enviado_em' => date('c')]);
    mm_criar_lead_busca($b, $e['opcoes']);
    return [true, 'Enviado para ' . $b['email']];
}

function mm_criar_lead_busca(array $b, array $opcoes): void {
    $p = $b['pedido'];
    $sinais = [
        'prazo' => ['ate3' => 'até 3 meses', '3a6' => '3 a 6 meses', 'mais6' => 'mais de 6 meses'][$p['prazo'] ?? ''] ?? 'não informado',
        'financiamento' => ['aprovado' => 'aprovado', 'analise' => 'em análise', 'avista' => 'à vista', 'naosei' => 'não sabe'][$p['financiamento'] ?? ''] ?? 'não informado',
        'visitou' => ['sim' => 'sim', 'nao' => 'não'][$p['visitou'] ?? ''] ?? 'não informado',
    ];
    $msg = sprintf("Matchmaking (busca %d). Origem: %s. Até %d min de %s. %s, %s. Faixa: %s a %s. Mín. %s dorm. Prazo: %s; financiamento: %s; já visitou: %s. Opções enviadas: %s.%s",
        $b['id'], $p['endereco'], $p['tempo'], $p['modo'], $p['objetivo'], $p['tipo'],
        $p['preco_min'] ? mm_reais($p['preco_min']) : '—', $p['preco_max'] ? mm_reais($p['preco_max']) : '—', $p['dorm_min'] ?: '—',
        $sinais['prazo'], $sinais['financiamento'], $sinais['visitou'],
        $opcoes ? implode(', ', array_map(fn($c) => $c['codigo'] . '=' . $c['base36'], $opcoes)) : 'nenhuma',
        !empty($p['livre']) ? ' Observação: ' . mb_substr($p['livre'], 0, 300) : '');
    $ok = ns_criar_lead([
        'listingId' => null, 'origin' => 'SITE', 'name' => $b['nome'] ?: null, 'phone' => null,
        'whatsapp' => $b['whatsapp'] ?: null, 'email' => $b['email'], 'message' => $msg,
        'transactionType' => $p['objetivo'] === 'alugar' ? 'RENT' : 'SELL',
    ]);
    if (!$ok) mm_log('aviso', "lead da busca {$b['id']} não foi criada na Nonstop");
}
