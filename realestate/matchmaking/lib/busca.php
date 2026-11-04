<?php
/* Processamento de um pedido do Matchmaking (especificação, seção 3.2):
   geocodificar → raio pelo tempo e meio de transporte → Nonstop com o círculo em paths →
   descartar status e pendências → 50 mais próximos em linha reta → tempo real no Google Routes →
   nota de compatibilidade → rascunho para aprovação da Andrea. */
require_once __DIR__ . '/base.php';
require_once __DIR__ . '/textos.php';
require_once __DIR__ . '/nonstop.php';
require_once __DIR__ . '/google.php';
require_once __DIR__ . '/smtp.php';

const MM_VELOCIDADE_KMH = ['carro' => 22, 'transporte' => 15, 'bicicleta' => 12, 'pe' => 4.5];
const MM_PENDENCIAS_EXCLUEM = ['PENHORA', 'INVENTARIO', 'PROPRIETARIO_INCAPAZ', 'USUFRUTO'];
const MM_PENDENCIAS_ALERTA = ['ALIENACAO_FIDUCIARIA', 'DEBITO_DE_CONDOMINIO', 'DEBITO_DE_IPTU'];
const MM_MAX_OPCOES = 5;
const MM_MIN_OPCOES = 3;
const MM_MAX_CANDIDATOS = 10;   // as opções além das 5 ficam de reserva para troca no painel

function mm_buscar_registro(int $id): ?array {
    $st = mm_db()->prepare('SELECT * FROM buscas WHERE id = ?');
    $st->execute([$id]);
    $b = $st->fetch();
    if (!$b) return null;
    $b['pedido'] = json_decode($b['pedido'], true);
    $b['resultado'] = $b['resultado'] ? json_decode($b['resultado'], true) : null;
    return $b;
}

function mm_salvar_resultado(int $id, array $campos): void {
    $sets = []; $vals = [];
    foreach ($campos as $k => $v) { $sets[] = "$k = ?"; $vals[] = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v; }
    $vals[] = $id;
    mm_db()->prepare('UPDATE buscas SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
}

function mm_processar_busca(int $id): void {
    $b = mm_buscar_registro($id);
    if (!$b) return;
    mm_salvar_resultado($id, ['status' => 'processando', 'erro' => null]);
    try {
        $r = mm_executar_busca($b['pedido']);
        $n = count($r['candidatos']);
        $status = $n ? 'aguardando_aprovacao' : 'sem_resultado';
        mm_salvar_resultado($id, ['status' => $status, 'resultado' => $r,
            'abertura' => $b['abertura'] ?: mm_abertura_padrao($b['pedido'], min($n, MM_MAX_OPCOES), $b['idioma'], $r['origem']['rotulo'] ?? null)]);
        mm_avisar_andrea($id, $status, $n);
    } catch (Throwable $e) {
        mm_log('erro', "busca $id: " . $e->getMessage());
        mm_salvar_resultado($id, ['status' => 'erro', 'erro' => mb_substr($e->getMessage(), 0, 500)]);
        mm_avisar_andrea($id, 'erro', 0);
    }
}

function mm_executar_busca(array $p): array {
    // 1. origem
    if (isset($p['lat'], $p['lng']) && is_numeric($p['lat']) && is_numeric($p['lng'])) {
        $origem = ['lat' => (float)$p['lat'], 'lng' => (float)$p['lng'], 'formatado' => $p['endereco']];
    } else {
        $origem = gg_geocodificar($p['endereco']);
        if (!$origem) throw new RuntimeException('endereço de referência não encontrado no Google');
    }
    $origem['rotulo'] = $p['rotulo_destino'] ?? null;

    // 2. raio de busca pelo tempo × velocidade média, círculo de 32 pontos em polyline
    $raio = $p['tempo'] / 60 * (MM_VELOCIDADE_KMH[$p['modo']] ?? 15) * 1000;
    $raio = max(800, min(25000, $raio));
    $paths = mm_polyline(mm_circulo($origem['lat'], $origem['lng'], $raio, 32));

    // 3. candidatos na Nonstop
    $transacao = $p['objetivo'] === 'alugar' ? 'LOCACAO' : 'VENDA';
    $filtros = [
        'paths' => $paths, 'use' => 'RESIDENCIAL',
        'minVal' => $p['preco_min'] ?: null, 'maxVal' => $p['preco_max'] ?: null,
        'type' => MM_TIPOS_FORM[$p['tipo']] ?? [],
        'minRooms' => $p['dorm_min'] ?: null, 'minSuites' => $p['suites_min'] ?: null,
        'minParkingLots' => $p['vagas_min'] ?: null, 'minPrivate' => $p['area_min'] ?: null,
    ];
    $lista = ns_listar($filtros, $transacao);
    $total_api = count($lista);

    // 4. descarta status diferente de SEM_OBSERVACOES e imóveis sem coordenada
    $validos = [];
    foreach ($lista as $c) {
        if (($c['transactionStatus'] ?? '') !== 'SEM_OBSERVACOES') continue;
        $geo = $c['address']['geo']['coordinates'] ?? null;   // GeoJSON: [lng, lat]
        if (!is_array($geo) || count($geo) < 2) continue;
        $c['_lat'] = (float)$geo[1]; $c['_lng'] = (float)$geo[0];
        $c['_dist'] = mm_distancia($origem['lat'], $origem['lng'], $c['_lat'], $c['_lng']);
        if ($c['_dist'] > $raio * 1.05) continue;   // segurança caso o filtro de perímetro não se aplique
        $validos[] = $c;
    }

    // 5. 50 mais próximos em linha reta (limita o custo do Google)
    usort($validos, fn($a, $b) => $a['_dist'] <=> $b['_dist']);
    $validos = array_slice($validos, 0, 50);

    // 6. tempo real até a origem informada (origem = imóvel, destino = trabalho)
    $tempos = $validos ? gg_tempos(array_map(fn($c) => [$c['_lat'], $c['_lng']], $validos), [$origem['lat'], $origem['lng']], $p['modo']) : [];
    $no_tempo = [];
    foreach ($validos as $i => $c) {
        if ($tempos[$i] === null || $tempos[$i] > $p['tempo']) continue;
        $c['_tempo'] = $tempos[$i];
        $no_tempo[] = $c;
    }
    usort($no_tempo, fn($a, $b) => $a['_tempo'] <=> $b['_tempo']);

    // 7. ficha completa dos 15 mais rápidos, pendências e nota
    $candidatos = [];
    foreach (array_slice($no_tempo, 0, 15) as $c) {
        $d = ns_detalhe($c['base36Id']);
        if (!$d) continue;
        if (array_intersect($d['pendencies'] ?? [], MM_PENDENCIAS_EXCLUEM)) continue;
        if (($d['transactionStatus'] ?? '') !== 'SEM_OBSERVACOES') continue;
        $candidatos[] = mm_montar_candidato($d, $c, $p, $transacao);
    }
    usort($candidatos, fn($a, $b) => $b['nota'] <=> $a['nota']);
    $candidatos = array_slice($candidatos, 0, MM_MAX_CANDIDATOS);

    return [
        'origem' => $origem, 'raio_m' => (int)$raio, 'transacao' => $transacao,
        'contagem' => ['api' => $total_api, 'raio' => count($validos), 'tempo' => count($no_tempo), 'candidatos' => count($candidatos)],
        'candidatos' => $candidatos,
        'escolhidos' => array_column(array_slice($candidatos, 0, MM_MAX_OPCOES), 'base36'),
        'processado_em' => date('c'),
    ];
}

function mm_montar_candidato(array $d, array $card, array $p, string $transacao): array {
    $preco = $transacao === 'LOCACAO' ? ($d['values']['longStay'] ?? null) : ($d['values']['sale'] ?? null);
    $area = $d['areas']['private'] ?? null;
    $lat = $card['_lat']; $lng = $card['_lng'];
    $distrito = mm_distrito($lat, $lng);
    $imagens = array_values(array_filter(array_map(fn($m) => $m['url'] ?? null, $d['media']['images'] ?? [])));
    $cond_feat = $d['condo']['features'] ?? [];
    $alertas = array_values(array_intersect($d['pendencies'] ?? [], MM_PENDENCIAS_ALERTA));
    $comissao = (isset($d['fees']['natural'], $d['fees']['total'])) ? $d['fees']['natural'] * $d['fees']['total'] : null;

    // nota (seção 3.3)
    $n_tempo = max(0, 1 - $card['_tempo'] / max(1, $p['tempo'])) * 0.7 + 0.3;
    $mediana = $transacao === 'VENDA' ? mm_mediana_privativa($distrito, $area) : null;
    $razao = ($mediana && $preco && $area) ? ($preco / $area) / $mediana : null;
    $n_preco = $razao === null ? 0.5 : max(0, min(1, (1.3 - $razao) / 0.6));
    $pedidas = array_merge($p['caracs_imovel'] ?? [], $p['caracs_cond'] ?? []);
    $tem = array_merge($d['features'] ?? [], $cond_feat);
    if (in_array('PISCINA', $p['caracs_cond'] ?? [], true)) $tem = array_merge($tem, array_intersect($cond_feat, ['PISCINA_ADULTO', 'PISCINA_AQUECIDA', 'PISCINA_COBERTA']) ? ['PISCINA'] : []);
    $n_carac = $pedidas ? count(array_intersect($pedidas, $tem)) / count($pedidas) : 1;
    $n_qual = min(1, count($imagens) / 12) * 0.6 + (!empty($d['media']['floorPlans']) ? 0.2 : 0)
            + ((!empty($d['media']['videos']) || !empty($d['media']['tours'])) ? 0.2 : 0);
    $n_com = $comissao === null ? 0.5 : min(1, $comissao / 0.03);
    $n_alerta = $alertas ? 0 : 1;
    $nota = round(100 * (0.30 * $n_tempo + 0.25 * $n_preco + 0.20 * $n_carac + 0.10 * $n_qual + 0.10 * $n_com + 0.05 * $n_alerta), 1);

    $selec = count($imagens) > 1 ? array_slice(range(1, count($imagens) - 1), 0, 4) : [];
    return [
        'base36' => $d['base36Id'], 'codigo' => mm_codigo($d['base36Id']),
        'tipo' => $d['type'], 'area' => $area, 'dorm' => $d['rooms'] ?? null, 'suites' => $d['suites'] ?? null,
        'vagas' => is_array($d['parkingLots'] ?? null) ? count($d['parkingLots']) : ($card['parkingLots'] ?? null),
        'andar_faixa' => mm_faixa_andar(isset($d['floor']) ? (int)$d['floor'] : null),
        'ano' => $d['yearOfConstruction'] ?? ($d['condo']['yearOfConstruction'] ?? null),
        'preco' => $preco, 'condominio' => $d['values']['condoFee'] ?? null, 'iptu' => $d['values']['propertyTax'] ?? null,
        'ocultar_preco' => !empty($d['mgmt']['hidePrice']),
        'bairro' => $d['address']['area'] ?? '', 'distrito' => $distrito,
        'lat' => $lat, 'lng' => $lng, 'tempo' => $card['_tempo'], 'dist_m' => (int)$card['_dist'],
        'caracs' => array_values($d['features'] ?? []), 'caracs_cond' => array_values($cond_feat),
        'imagens' => $imagens, 'fotos' => $selec,
        'nota' => $nota, 'nota_partes' => ['tempo' => round($n_tempo, 2), 'preco_m2' => round($n_preco, 2), 'caracs' => round($n_carac, 2),
            'anuncio' => round($n_qual, 2), 'comissao' => round($n_com, 2), 'alertas' => $n_alerta, 'razao_itbi' => $razao ? round($razao, 2) : null],
        'alertas' => $alertas, 'comissao_pf' => $comissao,
        // só para o painel e para a verificação de privacidade; nunca vão para o cliente
        'interno' => [
            'rua' => $d['address']['street'] ?? '', 'numero' => $d['address']['number'] ?? '', 'complemento' => $d['address']['complement'] ?? '',
            'condominio' => $d['condo']['name'] ?? '', 'corretor' => $d['user']['name'] ?? '',
            'imobiliaria' => $d['user']['brokerage']['name'] ?? '', 'whatsapp_corretor' => $d['user']['whatsapp'] ?? '',
            'titulo' => $d['title'] ?? '', 'descricao' => mb_substr($d['description'] ?? '', 0, 3000),
            'andar' => $d['floor'] ?? null, 'url' => $card['url'] ?? '',
        ],
    ];
}

/* Descrição montada só a partir dos dados estruturados (o texto do anunciante não é usado). */
function mm_descricao(array $c, string $lang): string {
    $tipo = mm_rotulo(MM_TIPOS, $c['tipo'], $lang);
    $partes = [];
    if ($c['area']) $partes[] = number_format($c['area'], 0, ',', '.') . ' ' . mm_t('area', $lang);
    if ($c['dorm']) $partes[] = $c['dorm'] . ' ' . mm_t($c['dorm'] == 1 ? 'dorm1' : 'dorm', $lang);
    if ($c['suites']) $partes[] = $c['suites'] . ' ' . mm_t($c['suites'] == 1 ? 'suite1' : 'suite', $lang);
    if ($c['vagas']) $partes[] = $c['vagas'] . ' ' . mm_t($c['vagas'] == 1 ? 'vaga1' : 'vaga', $lang);
    $sep = $lang === 'zh' ? '，' : ', ';
    return $tipo . ($partes ? ($lang === 'zh' ? '：' : ' · ') . implode($sep, $partes) : '');
}

function mm_abertura_padrao(array $p, int $n, string $lang, ?string $rotulo): string {
    $faixa = $p['preco_max'] ? (($p['preco_min'] ? mm_reais($p['preco_min']) . ' – ' : ($lang === 'pt' ? 'até ' : ($lang === 'en' ? 'up to ' : '最高 '))) . mm_reais($p['preco_max'])) : '—';
    $destino = $rotulo ?: mm_t('seu_destino', $lang);
    return mm_t('abertura', $lang, max($n, 1), mm_t($p['objetivo'] === 'alugar' ? 'alugar' : 'comprar', $lang),
        $p['tempo'], mm_t_modo($p['modo'], $lang), $destino, $faixa);
}

function mm_prioridade(array $p): int {
    $n = 0;
    $n += ['ate3' => 3, '3a6' => 1][$p['prazo'] ?? ''] ?? 0;
    $n += ['aprovado' => 3, 'avista' => 3, 'analise' => 1][$p['financiamento'] ?? ''] ?? 0;
    $n += ($p['visitou'] ?? '') === 'sim' ? 1 : 0;
    return $n;
}

function mm_avisar_andrea(int $id, string $status, int $n): void {
    $link = mm_url_base() . '/painel/?busca=' . $id;
    $msg = match ($status) {
        'aguardando_aprovacao' => "Nova busca no Matchmaking com $n opções encontradas, aguardando a sua aprovação.",
        'sem_resultado' => 'Nova busca no Matchmaking sem opções no tempo pedido. Ela entrou no mapa de demanda; a resposta ao cliente aguarda a sua aprovação.',
        default => 'Uma busca do Matchmaking não pôde ser processada. Veja o detalhe no painel.',
    };
    mm_enviar_interno("Matchmaking: busca nº $id", "$msg\n\nAbrir no painel: $link");
}
