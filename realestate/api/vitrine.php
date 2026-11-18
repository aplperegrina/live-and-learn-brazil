<?php
/* Vitrine dos boletins: imóveis da Nonstop dentro do perímetro da página, em três faixas de preço.
   GET ?site=brooklin|morumbi → JSON com no máximo 3 imóveis por faixa.

   Mesmas regras de privacidade do Matchmaking: sem endereço, número, prédio, andar exato,
   código da Nonstop, corretor ou imobiliária. A foto é regravada no nosso servidor.
   O resultado fica guardado por 12 horas para não consultar a Nonstop a cada visita. */
define('VT_LIB', is_dir(__DIR__ . '/../matchmaking/lib') ? __DIR__ . '/../matchmaking/lib' : getenv('MM_LIB'));
require_once VT_LIB . '/email.php';

// Faixas (decisão da Andrea, 17/11/2026): nada abaixo de R$ 600 mil; R$ 600 mil a 1 mi, 1 a 3 mi e acima de 3 mi (max null).
const VT_FAIXAS = [
    'brooklin' => [[600000, 1000000], [1000000, 3000000], [3000000, null]],
    'morumbi' => [[600000, 1000000], [1000000, 3000000], [3000000, null]],
    'cidadejardim' => [[600000, 1000000], [1000000, 3000000], [3000000, null]],   // página própria desde 17/11/2026
];
const VT_VALIDADE = 12 * 3600;
const VT_POR_FAIXA = 3;
// referência local do m² (ver vt_referencia): [metros, mínimo de vendas, rótulo]; ficam aqui em cima porque o código
// principal roda antes das funções do fim do arquivo
const VT_RAIOS = [[70, 4, 'quadra'], [150, 5, 'vizinhas'], [300, 5, 'vizinhas']];
const VT_CASAS = ['CASA_TIPO', 'CASA_EM_CONDOMINIO', 'SOBRADO', 'CASA'];

$site = preg_replace('/[^a-z]/', '', strtolower($_GET['site'] ?? ''));
if (!isset(VT_FAIXAS[$site])) { mm_json_saida(['erro' => 'site'], 400); exit; }

$cache = mm_dir_dados() . "/vitrine5_$site.json";   // "5": três faixas e ordem pelo m² contra a quadra; caches antigos não são reaproveitados
if (is_file($cache) && time() - filemtime($cache) < VT_VALIDADE) {
    header('Cache-Control: public, max-age=1800');
    header('Content-Type: application/json; charset=utf-8');
    readfile($cache);
    exit;
}

try {
    $saida = ['site' => $site, 'atualizado' => date('c'), 'faixas' => vt_montar($site)];
    file_put_contents($cache, json_encode($saida, JSON_UNESCAPED_UNICODE));
    header('Cache-Control: public, max-age=1800');
    mm_json_saida($saida);
} catch (Throwable $e) {
    mm_log('erro', "vitrine $site: " . $e->getMessage());
    if (is_file($cache)) { header('Content-Type: application/json; charset=utf-8'); readfile($cache); exit; }   // vale a última versão boa
    mm_json_saida(['erro' => 'indisponivel'], 503);
}

function vt_montar(string $site): array {
    $per = json_decode(file_get_contents(VT_LIB . '/dados/perimetros.json'), true);
    $anel = $per['paginas'][$site]['geometria']['coordinates'][0];
    $paths = mm_polyline(array_map(fn($c) => [$c[1], $c[0]], $anel));
    $fotos_arq = mm_dir_dados() . '/vitrine_fotos.json';
    $fotos = is_file($fotos_arq) ? (json_decode(file_get_contents($fotos_arq), true) ?: []) : [];

    $faixas = [];
    foreach (VT_FAIXAS[$site] as [$min, $max]) {
        $lista = ns_listar(array_filter(['paths' => $paths, 'use' => 'RESIDENCIAL', 'minVal' => $min ?: null, 'maxVal' => $max],
                                        fn($v) => $v !== null), 'VENDA');
        $lista = array_values(array_filter($lista, fn($c) => ($c['transactionStatus'] ?? '') === 'SEM_OBSERVACOES'
            && !empty($c['image']) && empty($c['mgmt']['hidePrice'])));
        // Oportunidades (Andrea, 17/11/2026): primeiro os imóveis com o m² pedido mais baixo em relação à mediana das vendas
        // do ITBI na mesma quadra ou vizinhas, para o mesmo tamanho (vt_referencia). Abaixo de 60% da mediana pode ser erro de
        // área no anúncio ou um prédio bem mais antigo: o imóvel continua na vitrine, mas sem selo e na ordem neutra (como
        // se estivesse na mediana), para não abrir a faixa. Sem área ou localização, vão para o fim, pela data.
        foreach ($lista as &$c) { $c['_razao'] = vt_razao($c); } unset($c);
        $ordem = fn($c) => $c['_razao'] === null ? 9 : ($c['_razao'] < 0.6 ? 1.0 : $c['_razao']);
        usort($lista, fn($a, $b) => [$ordem($a), (string)($b['createdAt'] ?? '')] <=> [$ordem($b), (string)($a['createdAt'] ?? '')]);
        $escolhidos = [];
        foreach (array_slice($lista, 0, 15) as $c) {
            if (count($escolhidos) >= VT_POR_FAIXA) break;
            $d = ns_detalhe($c['base36Id']);
            if (!$d || array_intersect($d['pendencies'] ?? [], MM_PENDENCIAS_EXCLUEM)) continue;
            $imgs = array_values(array_filter(array_map(fn($m) => $m['url'] ?? null, $d['media']['images'] ?? [])));
            if (count($imgs) < 2) continue;
            // a primeira foto costuma ser a fachada: usa a segunda
            $fonte = $imgs[1];
            if (empty($fotos[$fonte])) { $fotos[$fonte] = mm_publicar_foto($fonte, 900); }
            if (!$fotos[$fonte]) continue;
            $cartao = vt_cartao($d, $fotos[$fonte]);
            // selo só quando o m² pedido fica ao menos 5% abaixo da mediana das vendas na quadra (ou vizinhas)
            $cartao['abaixo'] = ($c['_razao'] !== null && $c['_razao'] >= 0.6 && $c['_razao'] <= 0.95) ? (int)round((1 - $c['_razao']) * 100) : null;
            $cartao['ref'] = $cartao['abaixo'] ? (vt_referencia($c)['onde'] ?? null) : null;
            $escolhidos[] = $cartao;
        }
        $faixas[] = ['min' => $min, 'max' => $max, 'imoveis' => $escolhidos];
    }
    file_put_contents($fotos_arq, json_encode($fotos));
    return $faixas;
}

/* Referência local (Andrea, 17/11/2026): mediana do m² das vendas do ITBI na MESMA QUADRA; sem vendas suficientes,
   nas quadras vizinhas até ~3 quarteirões (300 m). Nunca o distrito. Só vendas de tamanho parecido (0,6 a 1,6 vez a área
   do anúncio), dos últimos 24 meses, com valor igual ou acima do valor venal de referência (lib/dados/vendas_quadras.json,
   gerado por scripts/gerar_vendas_vitrine.py). Casas comparadas com casas; o resto com apartamentos. */

function vt_referencia(array $c): ?array {
    static $vendas = null;
    $vendas ??= json_decode((string)@file_get_contents(VT_LIB . '/dados/vendas_quadras.json'), true) ?: [];
    $area = (float)($c['areas']['private'] ?? 0);
    $geo = $c['address']['geo']['coordinates'] ?? null;   // GeoJSON: [lng, lat]
    if ($area < 20 || !$geo) return null;
    [$lng, $lat] = [(float)$geo[0], (float)$geo[1]];
    $lista = $vendas[in_array($c['type'] ?? '', VT_CASAS, true) ? 'casas' : 'aptos'] ?? [];
    $kx = 111320 * cos(deg2rad($lat));
    $perto = [];   // [distância, m²] das vendas de tamanho parecido até 300 m
    foreach ($lista as [$la, $lo, $m2, $a]) {
        if ($a < 0.6 * $area || $a > 1.6 * $area) continue;
        $d = hypot(($la - $lat) * 111320, ($lo - $lng) * $kx);
        if ($d <= 300) $perto[] = [$d, $m2];
    }
    foreach (VT_RAIOS as [$raio, $min, $rot]) {
        $m = array_column(array_filter($perto, fn($p) => $p[0] <= $raio), 1);
        if (count($m) >= $min) {
            sort($m); $n = count($m);
            $med = $n % 2 ? $m[intdiv($n, 2)] : ($m[$n / 2 - 1] + $m[$n / 2]) / 2;
            return ['m2' => $med, 'n' => $n, 'onde' => $rot];
        }
    }
    return null;
}

/* m² pedido ÷ mediana local; null sem área, preço, localização ou vendas suficientes por perto. */
function vt_razao(array $c): ?float {
    $preco = (float)($c['values']['sale'] ?? 0); $area = (float)($c['areas']['private'] ?? 0);
    if ($preco <= 0 || $area <= 0 || !($ref = vt_referencia($c))) return null;
    return ($preco / $area) / $ref['m2'];
}

function vt_cartao(array $d, string $foto): array {
    $rot = fn(array $tab, string $k) => ['pt' => mm_rotulo($tab, $k, 'pt'), 'en' => mm_rotulo($tab, $k, 'en')];
    $vagas = is_array($d['parkingLots'] ?? null) ? count($d['parkingLots']) : null;
    return [
        'codigo' => mm_codigo($d['base36Id']),
        'tipo' => $rot(MM_TIPOS, $d['type']),
        'area' => $d['areas']['private'] ?? null,
        'dorm' => $d['rooms'] ?? null, 'suites' => $d['suites'] ?? null, 'vagas' => $vagas,
        'preco' => $d['values']['sale'] ?? null,
        // taxa de condomínio acima de 1% do preço ao mês é erro de digitação no anúncio: fica de fora
        'condominio' => (($c = $d['values']['condoFee'] ?? null) && $c < 0.01 * ($d['values']['sale'] ?? 0)) ? $c : null,
        'ano' => $d['yearOfConstruction'] ?? ($d['condo']['yearOfConstruction'] ?? null),
        'andar' => mm_faixa_andar(isset($d['floor']) ? (int)$d['floor'] : null),
        // sem bairro nem endereço: a localização fica com a Andrea, informada no contato
        'caracs' => array_map(fn($k) => $rot(MM_CARAC, $k), array_slice($d['features'] ?? [], 0, 4)),
        'caracs_cond' => array_map(fn($k) => $rot(MM_CARAC, $k), array_slice($d['condo']['features'] ?? [], 0, 4)),
        'foto' => $foto,
    ];
}
