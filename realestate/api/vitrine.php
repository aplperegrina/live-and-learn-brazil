<?php
/* Vitrine dos boletins: imóveis da Nonstop dentro do perímetro da página, em duas faixas de preço.
   GET ?site=brooklin|morumbi → JSON com no máximo 3 imóveis por faixa.

   Mesmas regras de privacidade do Matchmaking: sem endereço, número, prédio, andar exato,
   código da Nonstop, corretor ou imobiliária. A foto é regravada no nosso servidor.
   O resultado fica guardado por 12 horas para não consultar a Nonstop a cada visita. */
define('VT_LIB', is_dir(__DIR__ . '/../matchmaking/lib') ? __DIR__ . '/../matchmaking/lib' : getenv('MM_LIB'));
require_once VT_LIB . '/email.php';

const VT_FAIXAS = [
    'brooklin' => [[800000, 1000000], [1500000, 3000000]],
    'morumbi' => [[0, 1500000], [1500000, 4000000]],
];
const VT_VALIDADE = 12 * 3600;
const VT_POR_FAIXA = 3;

$site = preg_replace('/[^a-z]/', '', strtolower($_GET['site'] ?? ''));
if (!isset(VT_FAIXAS[$site])) { mm_json_saida(['erro' => 'site'], 400); exit; }

$cache = mm_dir_dados() . "/vitrine_$site.json";
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
        $lista = ns_listar(['paths' => $paths, 'use' => 'RESIDENCIAL', 'minVal' => $min ?: null, 'maxVal' => $max], 'VENDA');
        $lista = array_values(array_filter($lista, fn($c) => ($c['transactionStatus'] ?? '') === 'SEM_OBSERVACOES'
            && !empty($c['image']) && empty($c['mgmt']['hidePrice'])));
        usort($lista, fn($a, $b) => strcmp((string)($b['createdAt'] ?? ''), (string)($a['createdAt'] ?? '')));   // mais recentes primeiro
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
            $escolhidos[] = vt_cartao($d, $fotos[$fonte]);
        }
        $faixas[] = ['min' => $min, 'max' => $max, 'imoveis' => $escolhidos];
    }
    file_put_contents($fotos_arq, json_encode($fotos));
    return $faixas;
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
