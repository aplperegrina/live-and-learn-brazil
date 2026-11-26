#!/usr/bin/env python3
"""Gera os dados dos mapas interativos da home (Live & Learn Brazil).

Saídas (em src/mapas/dados/, usadas pela home):
  brooklin.json      quadras do Brooklin com m² (ITBI), sub-regiões, estações e avenidas,
                     no sistema de coordenadas do boletim (W x H em pixels do boletim)
  sp-sul-oeste.json  Zona Sul e Oeste: avenidas, rio/Marginal, parques, metrô, perímetros
                     dos boletins e rótulos de bairros, em metros UTM 23S / 10 (1 unidade = 10 m)

Fontes:
  - realestate/{brooklin,morumbi,cidadejardim}/index.html  (JSON "D" e "C" dos boletins)
  - realestate/radar/perimetros.json                        (perímetros em lat/lng)
  - GeoSampa, na pasta do motor de boletins (MOTOR abaixo):
      dados_geo/vias_regiao.geojson, estacao_metro.geojson,
      GEOSAMPA_cadparcs_parque_unidade_conservacao.geojson

Uso, a partir da pasta SITE 2:
    python scripts/gerar-mapas.py
"""
import json, math, os, re, sys

RAIZ = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
MOTOR = os.environ.get('MOTOR_BOLETINS', r'C:\Users\aplpe\matchmaking\motor_sites_imobiliarios')
GEO = os.path.join(MOTOR, 'dados_geo')
SAIDA = os.path.join(RAIZ, 'src', 'mapas', 'dados')
os.makedirs(SAIDA, exist_ok=True)


# ---------------------------------------------------------------- utilidades
def boletim(slug):
    t = open(os.path.join(RAIZ, 'realestate', slug, 'index.html'), encoding='utf-8').read()
    D = json.loads(re.search(r'<script[^>]*id="D"[^>]*>(.*?)</script>', t, re.S).group(1))
    C = json.loads(re.search(r'<script[^>]*id="C"[^>]*>(.*?)</script>', t, re.S).group(1))
    return D, C


def latlng_utm(lat, lng, zona=23):
    """WGS84/SIRGAS2000 -> UTM (hemisfério sul). Fórmula padrão de Transversa de Mercator."""
    a, f = 6378137.0, 1 / 298.257222101
    e2 = f * (2 - f); ep2 = e2 / (1 - e2); k0 = 0.9996
    lon0 = math.radians(-183 + 6 * zona)
    phi, lam = math.radians(lat), math.radians(lng)
    N = a / math.sqrt(1 - e2 * math.sin(phi) ** 2)
    T = math.tan(phi) ** 2; C = ep2 * math.cos(phi) ** 2; A = math.cos(phi) * (lam - lon0)
    M = a * ((1 - e2 / 4 - 3 * e2 ** 2 / 64 - 5 * e2 ** 3 / 256) * phi
             - (3 * e2 / 8 + 3 * e2 ** 2 / 32 + 45 * e2 ** 3 / 1024) * math.sin(2 * phi)
             + (15 * e2 ** 2 / 256 + 45 * e2 ** 3 / 1024) * math.sin(4 * phi)
             - (35 * e2 ** 3 / 3072) * math.sin(6 * phi))
    x = k0 * N * (A + (1 - T + C) * A ** 3 / 6 + (5 - 18 * T + T * T + 72 * C - 58 * ep2) * A ** 5 / 120) + 500000
    y = k0 * (M + N * math.tan(phi) * (A * A / 2 + (5 - T + 9 * C + 4 * C * C) * A ** 4 / 24
                                        + (61 - 58 * T + T * T + 600 * C - 330 * ep2) * A ** 6 / 720))
    return x, y + 10000000


def dp(pts, tol):
    """Douglas-Peucker."""
    if len(pts) < 3: return pts
    (x1, y1), (x2, y2) = pts[0], pts[-1]
    dx, dy = x2 - x1, y2 - y1; L = math.hypot(dx, dy) or 1e-9
    dmax, idx = 0, 0
    for i in range(1, len(pts) - 1):
        d = abs(dy * pts[i][0] - dx * pts[i][1] + x2 * y1 - y2 * x1) / L
        if d > dmax: dmax, idx = d, i
    if dmax > tol: return dp(pts[:idx + 1], tol)[:-1] + dp(pts[idx:], tol)
    return [pts[0], pts[-1]]


def ajusta_afim(src, dst):
    """Mínimos quadrados para x' = a x + b y + c ; y' = d x + e y + f."""
    import itertools
    def resolve(A, b):  # 3x3
        n = len(A)
        M = [row[:] + [b[i]] for i, row in enumerate(A)]
        for c in range(n):
            p = max(range(c, n), key=lambda r: abs(M[r][c])); M[c], M[p] = M[p], M[c]
            for r in range(n):
                if r != c:
                    k = M[r][c] / M[c][c]
                    M[r] = [M[r][j] - k * M[c][j] for j in range(n + 1)]
        return [M[i][n] / M[i][i] for i in range(n)]
    AtA = [[0] * 3 for _ in range(3)]; Atx = [0] * 3; Aty = [0] * 3
    for (x, y), (u, v) in zip(src, dst):
        r = [x, y, 1]
        for i in range(3):
            Atx[i] += r[i] * u; Aty[i] += r[i] * v
            for j in range(3): AtA[i][j] += r[i] * r[j]
    a = resolve(AtA, Atx); d = resolve(AtA, Aty)
    return lambda x, y: (a[0] * x + a[1] * y + a[2], d[0] * x + d[1] * y + d[2])


def kpi(k):
    return {c: k.get(c) for c in ('m2', 'm2_prev', 'm2_var', 'n', 'por_mes', 'ticket', 'area_med')}


# ---------------------------------------------------------------- Brooklin
D, C = boletim('brooklin')
M = D['mapa']
nomes_sub = ['residencial', 'berrini', 'chacara']   # ordem de M['rc'] (conferida no mapa)
quadras = []
for q in M['q']:
    o = {'k': q['k'], 'd': q['d']}
    if q.get('in'): o['in'] = 1
    if q.get('a'): o['a'] = [q['a'][0], q['a'][1]]          # apto: m² mediano, nº de vendas
    if q.get('h'): o['h'] = [q['h'][0], q['h'][1]]          # casa: m² mediano, nº de vendas
    if q.get('r'): o['r'] = q['r'][:2]
    quadras.append(o)

avenidas = sorted({l[0] for l in M['L'] if l[0].startswith('Av.')})
rotulos = [l for l in M['L'] if l[0].startswith('Av.') and l[1] == 0]
# um rótulo por avenida (o primeiro)
vistos, rot = set(), []
for l in rotulos:
    if l[0] in vistos: continue
    vistos.add(l[0]); rot.append(l)

brooklin = {
    'fonte': 'Prefeitura de São Paulo, guias de ITBI (boletim Brooklin, ' + C['edicao']['en'] + ')',
    'periodo': D['periodo'], 'W': M['W'], 'H': M['H'], 'b': M['b'],
    'sub': {n: {'d': M['rc'][i], 'kpi': kpi(D['sub'][n]['kpi']), 'nome': C['subareas'][n]} for i, n in enumerate(nomes_sub)},
    'kpi': kpi(D['kpi']), 'quantis': M['quantis'], 'q': quadras,
    'p': {k: {'xy': v, 'pt': C['pois'][k]['pt'], 'en': C['pois'][k]['en'], 'tipo': C['pois'][k]['k']} for k, v in M['p'].items()},
    'avenidas': rot,
    'movimento': D['movimento'],
}
json.dump(brooklin, open(os.path.join(SAIDA, 'brooklin.json'), 'w', encoding='utf-8'), ensure_ascii=False, separators=(',', ':'))

# ---------------------------------------------------------------- Zona Sul e Oeste
E0, N1 = 319000, 7395600            # canto superior esquerdo (UTM)
E1, N0 = 332000, 7380200
ESC = 10                            # 1 unidade = 10 m
W, H = (E1 - E0) / ESC, (N1 - N0) / ESC
px = lambda e, n: (round((e - E0) / ESC, 1), round((N1 - n) / ESC, 1))

def caminho(pts, tol=8):
    pts = dp([(e, n) for e, n in pts], tol)
    xy = [px(e, n) for e, n in pts]
    return 'M' + 'L'.join(f'{x:g},{y:g}' for x, y in xy)

PRINCIPAIS = {'NACOES UNIDAS': 'Marginal Pinheiros', 'BANDEIRANTES': 'Av. dos Bandeirantes', 'JORN ROBERTO MARINHO': 'Av. Roberto Marinho',
              'STO AMARO': 'Av. Santo Amaro', 'BRIG FARIA LIMA': 'Av. Faria Lima', 'ENG LUIZ CARLOS BERRINI': 'Av. Berrini',
              'DR CHUCRI ZAIDAN': 'Av. Chucri Zaidan', 'REBOUCAS': 'Av. Rebouças', 'NOVE DE JULHO': 'Av. 9 de Julho',
              'BRASIL': 'Av. Brasil', 'IBIRAPUERA': 'Av. Ibirapuera', 'REPUBLICA DO LIBANO': 'Av. República do Líbano',
              'WASHINGTON LUIS': 'Av. Washington Luís', 'JOAO DIAS': 'Av. João Dias', 'GIOVANNI GRONCHI': 'Av. Giovanni Gronchi',
              'MORUMBI': 'Av. Morumbi', 'PROF FRANCISCO MORATO': 'Av. Francisco Morato', 'VER JOSE DINIZ': 'Av. Ver. José Diniz',
              'PRES JUSCELINO KUBITSCHEK': 'Av. Juscelino Kubitschek', 'CIDADE JARDIM': 'Av. Cidade Jardim',
              'EUSEBIO MATOSO': 'Av. Eusébio Matoso', 'HELIO PELLEGRINO': 'Av. Hélio Pellegrino', 'VITAL BRAZIL': 'Av. Vital Brasil',
              'PEDROSO DE MORAIS': 'Av. Pedroso de Morais', 'ROQUE PETRONI JUNIOR': 'Av. Roque Petroni Jr.'}
vias = json.load(open(os.path.join(GEO, 'vias_regiao.geojson'), encoding='utf-8'))
grandes, menores = {}, []
for f in vias['features']:
    p = f['properties']
    if p.get('cd_tipo_logradouro') != 'AV': continue
    chave = ' '.join(x for x in (p.get('cd_titulo_logradouro'), p.get('nm_logradouro')) if x)
    pts = f['geometry']['coordinates']
    if chave in PRINCIPAIS:
        grandes.setdefault(PRINCIPAIS[chave], []).append(caminho(pts, 6))
    else:
        menores.append(caminho(pts, 14))

parques = []
pq = json.load(open(os.path.join(GEO, 'GEOSAMPA_cadparcs_parque_unidade_conservacao.geojson'), encoding='utf-8'))
for f in pq['features']:
    g = f['geometry']
    if not g: continue
    polys = g['coordinates'] if g['type'] == 'MultiPolygon' else [g['coordinates']]
    nome = next((f['properties'][k] for k in f['properties'] if k.startswith('nm_')), '')
    for poly in polys:
        anel = poly[0]
        es = [c[0] for c in anel]; ns = [c[1] for c in anel]
        if max(es) < E0 or min(es) > E1 or max(ns) < N0 or min(ns) > N1: continue
        area = abs(sum(anel[i][0] * anel[i - 1][1] - anel[i - 1][0] * anel[i][1] for i in range(len(anel)))) / 2
        if area < 60000: continue          # menos de 6 ha fica de fora
        parques.append({'nome': nome, 'd': caminho(anel, 10) + 'Z', 'ha': round(area / 10000)})

# metrô: estações do GeoSampa (Verde, Amarela, Lilás)
est = json.load(open(os.path.join(GEO, 'estacao_metro.geojson'), encoding='utf-8'))
ORDEM = {
    'LILAS': ['CAPÃO REDONDO', 'CAMPO LIMPO', 'VILA DAS BELEZAS', 'GIOVANNI GRONCHI', 'SANTO AMARO', 'LARGO TREZE',
              'ADOLFO PINHEIRO', 'ALTO DA BOA VISTA', 'BORBA GATO', 'BROOKLIN', 'CAMPO BELO', 'EUCALIPTOS'],
    'AMARELA': ['VILA SÔNIA', 'SÃO PAULO-MORUMBI', 'BUTANTÃ', 'PINHEIROS', 'FARIA LIMA', 'FRADIQUE COUTINHO', 'OSCAR FREIRE'],
    'VERDE': ['VILA MADALENA', 'SANTUÁRIO NOSSA SENHORA DE FÁTIMA-SUMARÉ', 'CLÍNICAS'],
}
def nome_est(s):
    s = {'SANTUÁRIO NOSSA SENHORA DE FÁTIMA-SUMARÉ': 'Sumaré'}.get(s, s).title()
    for a, b in ((' Da ', ' da '), (' Das ', ' das '), (' De ', ' de '), (' Do ', ' do ')): s = s.replace(a, b)
    return s

pos = {}
for f in est['features']:
    p = f['properties']; e, n = f['geometry']['coordinates']
    pos[(p['nm_linha_metro_trem'], p['nm_estacao_metro_trem'])] = (e, n)
linhas = {}
for lin, seq in ORDEM.items():
    pts = [pos[(lin, s)] for s in seq if (lin, s) in pos]
    linhas[lin] = {'d': 'M' + 'L'.join('%g,%g' % px(*p) for p in pts),
                   'est': [{'n': nome_est(s), 'xy': px(*pos[(lin, s)])} for s in seq if (lin, s) in pos]}

# Linha 17-Ouro e Berrini (CPTM): posições do boletim do Brooklin levadas para UTM por ajuste afim
pares = [('st_brooklin', ('LILAS', 'BROOKLIN')), ('st_campobelo', ('LILAS', 'CAMPO BELO')),
         ('st_borbagato', ('LILAS', 'BORBA GATO')), ('st_altoboavista', ('LILAS', 'ALTO DA BOA VISTA'))]
f_af = ajusta_afim([M['p'][a] for a, _ in pares], [pos[b] for _, b in pares])
resid = max(math.hypot(*[u - v for u, v in zip(f_af(*M['p'][a]), pos[b])]) for a, b in pares)
ouro = ['st_chucri', 'st_vilacordeiro', 'st_campobelo', 'st_josediniz']
pts17 = [f_af(*M['p'][k]) for k in ouro]
linhas['OURO'] = {'d': 'M' + 'L'.join('%g,%g' % px(*p) for p in pts17),
                  'est': [{'n': C['pois'][k]['en'].replace(' stn.', '').replace(' station', ''), 'xy': px(*p)} for k, p in zip(ouro, pts17)],
                  'nota': 'esquemático: segmentos retos entre estações'}
berrini_cptm = px(*f_af(*M['p']['st_berrini']))

# perímetros dos boletins (lat/lng)
per = json.load(open(os.path.join(RAIZ, 'realestate', 'radar', 'perimetros.json'), encoding='utf-8'))
perimetros = {}
for slug, aneis in per.items():
    perimetros[slug] = [caminho([latlng_utm(la, lo) for la, lo in anel], 4) + 'Z' for anel in aneis]

dados_bairros = {}
for slug in ('brooklin', 'morumbi', 'cidadejardim'):
    Ds, Cs = boletim(slug)
    dados_bairros[slug] = {'kpi': kpi(Ds['kpi']), 'sub': {k: {'kpi': kpi(v['kpi']), 'nome': Cs['subareas'].get(k)} for k, v in Ds['sub'].items()},
                           'url': f'https://realestate.liveandlearnbrazil.com/{slug}/'}

# Rótulos de bairro: posição aproximada do centro (lat/lng). Papel = leitura de corretora, a validar.
BAIRROS = [
    ('Pinheiros', -23.5668, -46.6935, 'misto'), ('Vila Madalena', -23.5545, -46.6905, 'residencial'),
    ('Itaim Bibi', -23.5855, -46.6780, 'escritorios'), ('Vila Olímpia', -23.5960, -46.6865, 'escritorios'),
    ('Jardins', -23.5675, -46.6655, 'residencial'), ('Moema', -23.6010, -46.6650, 'residencial'),
    ('Campo Belo', -23.6215, -46.6705, 'residencial'), ('Brooklin', -23.6165, -46.6935, 'residencial'),
    ('Berrini', -23.6060, -46.6955, 'escritorios'), ('Chácara Santo Antônio', -23.6340, -46.7060, 'transformacao'),
    ('Santo Amaro', -23.6530, -46.7095, 'misto'), ('Morumbi', -23.6000, -46.7225, 'residencial'),
    ('Cidade Jardim', -23.5875, -46.7020, 'residencial'), ('Butantã', -23.5715, -46.7085, 'residencial'),
    ('Vila Leopoldina', -23.5330, -46.7330, 'transformacao'),
]
rotulos_sp = []
for nome, la, lo, papel in BAIRROS:
    x, y = px(*latlng_utm(la, lo))
    if 0 <= x <= W and 0 <= y <= H: rotulos_sp.append({'n': nome, 'xy': [x, y], 'papel': papel})

sp = {
    'fonte': 'GeoSampa (vias, parques, estações), perímetros e ITBI dos boletins Live & Learn',
    'W': W, 'H': H, 'utm': {'E0': E0, 'N1': N1, 'escala_m': ESC},
    'rio': grandes.pop('Marginal Pinheiros', []),
    'avenidas': grandes, 'menores': menores, 'parques': parques,
    'linhas': linhas, 'berrini_cptm': berrini_cptm,
    'perimetros': perimetros, 'bairros_dados': dados_bairros, 'rotulos': rotulos_sp,
}
json.dump(sp, open(os.path.join(SAIDA, 'sp-sul-oeste.json'), 'w', encoding='utf-8'), ensure_ascii=False, separators=(',', ':'))

for fn in ('brooklin.json', 'sp-sul-oeste.json'):
    print(fn, round(os.path.getsize(os.path.join(SAIDA, fn)) / 1024), 'KB')
print('ajuste afim Brooklin->UTM, resíduo máximo: %.0f m' % resid)
print('parques:', len(parques), '| avenidas principais:', len(grandes), '| menores:', len(menores), '| rótulos:', len(rotulos_sp))
