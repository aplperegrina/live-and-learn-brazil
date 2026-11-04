<?php
/* Google Maps Platform pelo servidor: Geocoding, Routes (computeRouteMatrix) e Maps Static.
   A chave do servidor nunca vai para o navegador nem para os e-mails: o mapa é baixado aqui
   e publicado como imagem própria. */
require_once __DIR__ . '/base.php';

function gg_chave(): string {
    $k = mm_config('GOOGLE_MAPS_KEY');
    if (!$k) throw new RuntimeException('GOOGLE_MAPS_KEY ausente na configuração');
    return $k;
}

function gg_geocodificar(string $endereco): ?array {
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
        'address' => $endereco, 'region' => 'br', 'language' => 'pt-BR', 'key' => gg_chave(),
        'components' => 'country:BR',
    ]);
    [$st, $d] = mm_http('GET', $url);
    if ($st !== 200 || ($d['status'] ?? '') !== 'OK') {
        mm_log('aviso', "Geocoding sem resultado ($st " . ($d['status'] ?? '') . ')');
        return null;
    }
    $r = $d['results'][0];
    return ['lat' => $r['geometry']['location']['lat'], 'lng' => $r['geometry']['location']['lng'],
            'formatado' => $r['formatted_address']];
}

/* Próximo dia útil às 8h de São Paulo, em UTC (RFC 3339). */
function gg_partida(): string {
    $sp = new DateTimeZone('America/Sao_Paulo');
    $d = new DateTime('tomorrow 08:00', $sp);
    while ((int)$d->format('N') >= 6) $d->modify('+1 day');
    return $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
}

const GG_MODOS = ['carro' => 'DRIVE', 'transporte' => 'TRANSIT', 'pe' => 'WALK', 'bicicleta' => 'BICYCLE'];

/* Tempo (minutos) de cada origem até o destino. $origens: lista de [lat, lng]. Devolve índice => minutos|null. */
function gg_tempos(array $origens, array $destino, string $modo): array {
    $res = array_fill(0, count($origens), null);
    foreach (array_chunk($origens, 50, true) as $lote) {   // TRANSIT aceita até 100 elementos por chamada
        $idx = array_keys($lote);
        $corpo = [
            'origins' => array_map(fn($o) => ['waypoint' => ['location' => ['latLng' => ['latitude' => $o[0], 'longitude' => $o[1]]]]], array_values($lote)),
            'destinations' => [['waypoint' => ['location' => ['latLng' => ['latitude' => $destino[0], 'longitude' => $destino[1]]]]]],
            'travelMode' => GG_MODOS[$modo] ?? 'TRANSIT',
        ];
        if (in_array($corpo['travelMode'], ['DRIVE', 'TRANSIT'], true)) $corpo['departureTime'] = gg_partida();
        if ($corpo['travelMode'] === 'DRIVE') $corpo['routingPreference'] = 'TRAFFIC_AWARE';
        [$st, $d] = mm_http('POST', 'https://routes.googleapis.com/distanceMatrix/v2:computeRouteMatrix', [
            'Content-Type: application/json', 'X-Goog-Api-Key: ' . gg_chave(),
            'X-Goog-FieldMask: originIndex,destinationIndex,duration,condition',
        ], $corpo, 60);
        if ($st !== 200 || !is_array($d)) { mm_log('erro', "Routes respondeu $st"); continue; }
        foreach ($d as $el) {
            if (($el['condition'] ?? '') !== 'ROUTE_EXISTS' || empty($el['duration'])) continue;
            $res[$idx[$el['originIndex'] ?? 0]] = (int)round(((int)rtrim($el['duration'], 's')) / 60);
        }
    }
    return $res;
}

/* Baixa o mapa estático com o círculo de privacidade e devolve os bytes PNG. */
function gg_mapa_circulo(float $lat, float $lng, int $raio): ?string {
    $circ = mm_circulo($lat, $lng, $raio, 40);
    $url = 'https://maps.googleapis.com/maps/api/staticmap?' . http_build_query([
        'size' => '560x280', 'scale' => 2, 'zoom' => 16, 'center' => sprintf('%.6f,%.6f', $lat, $lng),
        'path' => 'color:0x34425ECC|weight:3|fillcolor:0x34425E33|enc:' . mm_polyline($circ),
        'language' => 'pt-BR', 'key' => gg_chave(),
    ]);
    // sem nomes de prédios, condomínios e comércios no mapa (podem identificar o imóvel)
    $url .= '&style=' . rawurlencode('feature:poi|visibility:off') . '&style=' . rawurlencode('feature:landscape.man_made|element:labels|visibility:off');
    [$st, $d] = mm_http('GET', $url);
    return $st === 200 && is_string($d) ? $d : null;
}
