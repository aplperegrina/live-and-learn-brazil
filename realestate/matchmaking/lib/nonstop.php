<?php
/* Cliente da API da Nonstop (https://docs.usenonstop.com). O token fica só no servidor.
   Armadilhas documentadas: perPage/currentPage (nunca limit/page); availableFor omitido vale VENDA;
   parâmetro com nome errado é ignorado em silêncio; o perímetro vai em paths (polyline do Google). */
require_once __DIR__ . '/base.php';

const NS_BASE = 'https://www.usenonstop.com/api/unstable';

function ns_chamar(string $metodo, string $caminho, array $query = [], $corpo = null): array {
    $token = mm_config('NONSTOP_TOKEN');
    if (!$token) throw new RuntimeException('NONSTOP_TOKEN ausente na configuração');
    $q = [];
    foreach ($query as $k => $v) {
        if ($v === null || $v === '' || $v === []) continue;
        $q[$k] = is_array($v) ? implode(',', $v) : $v;
    }
    // NONSTOP_BASE só é usado nos testes locais, para apontar a uma Nonstop simulada
    $url = rtrim(mm_config('NONSTOP_BASE', NS_BASE), '/') . $caminho . ($q ? '?' . http_build_query($q) : '');
    $cab = ['Authorization: Bearer ' . $token, 'Accept: application/json'];
    if ($corpo !== null) $cab[] = 'Content-Type: application/json';
    [$st, $d] = mm_http($metodo, $url, $cab, $corpo, 60);
    if ($st !== 200) {
        mm_log('erro', "Nonstop $metodo $caminho respondeu $st: " . (is_string($d) ? mb_substr($d, 0, 300) : json_encode($d)));
        mm_alerta_api("Nonstop $caminho respondeu $st");
    }
    return [$st, $d];
}

/* Avisa a Andrea uma vez por dia quando a API devolve status diferente de 200. */
function mm_alerta_api(string $msg): void {
    $db = mm_db(); $hoje = date('Y-m-d');
    $st = $db->prepare("SELECT valor FROM limites WHERE chave = 'alerta_api'"); $st->execute();
    if ($st->fetchColumn() === $hoje) return;
    $db->prepare("INSERT OR REPLACE INTO limites (chave, valor) VALUES ('alerta_api', ?)")->execute([$hoje]);
    if (function_exists('mm_enviar_interno')) {
        mm_enviar_interno('Matchmaking: falha na API', "A API respondeu com erro hoje.\n\n$msg\n\nDetalhes no registro do painel.");
    }
}

/* Lista imóveis com os filtros dados, em todas as páginas. $transacao: VENDA ou LOCACAO. */
function ns_listar(array $filtros, string $transacao): array {
    $todos = []; $pagina = 1;
    do {
        [$st, $d] = ns_chamar('GET', '/imoveis/todos', $filtros + [
            'availableFor' => $transacao, 'perPage' => 100, 'currentPage' => $pagina,
            'sortBy' => '_id', 'sortOrder' => 1,
        ]);
        if ($st !== 200 || !is_array($d)) break;
        if ($pagina === 1 && ($d['advertiser']['slug'] ?? '') !== mm_config('NONSTOP_SLUG', 'andrea-longobardi')) {
            mm_log('erro', 'advertiser.slug inesperado: ' . ($d['advertiser']['slug'] ?? 'nulo'));
        }
        $lote = $d['properties'] ?? [];
        foreach ($lote as $p) $todos[$p['id']] = $p;
        $total = (int)($d['total'] ?? 0);
        $pagina++;
    } while ($lote && count($todos) < $total && $pagina < 50);
    return array_values($todos);
}

function ns_detalhe(string $base36): ?array {
    [$st, $d] = ns_chamar('GET', '/imoveis/' . rawurlencode($base36));
    return $st === 200 && is_array($d) ? $d : null;
}

function ns_criar_lead(array $lead): bool {
    [$st, ] = ns_chamar('POST', '/leads/criar', [], $lead);
    return $st === 200;
}
