#!/usr/bin/env python3
"""SEO e GEO dos boletins de realestate.liveandlearnbrazil.com.

Os boletins montam o texto com JavaScript, e a maioria dos robôs de busca e de IA
não roda JavaScript. Este script grava no próprio HTML o que esses robôs precisam ler:

  - no <head>: título, descrição, Open Graph, Twitter, datas e dados estruturados
    (JSON-LD: corretora, autora, artigo, trilha e FAQ), entre <!-- seo:inicio --> e <!-- seo:fim -->;
  - no corpo: o texto em português já preenchido nos elementos que o JavaScript
    preenche depois (o JavaScript substitui o conteúdo, então nada aparece duplicado),
    sempre entre <!--pre--> e <!--/pre-->;
  - o subtítulo da capa, a bio da autora com a data de atualização e, quando o
    boletim tem perguntas no arquivo de configuração, a seção de perguntas frequentes;
  - na pasta realestate/: robots.txt, sitemap.xml e llms.txt.

Textos fixos ficam em scripts/seo-boletins.json. Os números vêm dos blocos D e C de
cada boletim e de realestate/dados/dados-alugueis.json, então basta rodar o script de
novo depois de atualizar os dados:

    python3 scripts/seo-boletins.py

O script é idempotente: rodar duas vezes seguidas não muda nada na segunda.
Só usa a biblioteca padrão do Python.
"""
import html
import json
import os
import re
import sys
from datetime import date

RAIZ = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PASTA = os.path.join(RAIZ, 'realestate')
CFG = json.load(open(os.path.join(RAIZ, 'scripts', 'seo-boletins.json'), encoding='utf-8'))
BASE = CFG['base'].rstrip('/')
P = CFG['pessoa']

MESES = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto',
         'setembro', 'outubro', 'novembro', 'dezembro']
MESES_C = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez']


# ---------- formatação ----------
def nf(v, d=0):
    s = f'{v:,.{d}f}'
    return s.replace(',', '§').replace('.', ',').replace('§', '.')


def reais(v, d=0):
    return 'R$ ' + nf(v, d)


def mil(v):
    if v >= 1e6:
        x = v / 1e6
        return f'R$ {nf(x, 2)} ' + ('milhão' if x < 2 else 'milhões')
    return f'R$ {nf(round(v / 1000))} mil'


def data_pt(iso):
    d = date.fromisoformat(iso[:10])
    return f'{d.day} de {MESES[d.month - 1]} de {d.year}'


def esc(s):
    return html.escape(s, quote=True)


def sem_tags(s):
    return html.unescape(re.sub(r'<[^>]+>', '', s))


def json_ld(obj):
    return json.dumps(obj, ensure_ascii=False, separators=(',', ':')).replace('</', '<\\/')


# ---------- leitura dos boletins ----------
def bloco_json(h, id_):
    m = re.search(r'<script id="%s" type="application/json">(.*?)</script>' % id_, h, re.S)
    return json.loads(m.group(1))


def tx(o):
    return o if isinstance(o, str) else o.get('pt', '')


def textos_T(h):
    """Pega os textos fixos em português do objeto T do JavaScript do boletim."""
    m = re.search(r'const T = \{(.*?)\n\};', h, re.S)
    return dict(re.findall(r"(\w+): \{pt:'((?:[^'\\]|\\.)*)'", m.group(1))) if m else {}


def alugueis():
    try:
        return json.load(open(os.path.join(PASTA, 'dados', 'dados-alugueis.json'), encoding='utf-8'))['series']
    except Exception:
        return {}


def valores(D):
    """Números que as respostas do FAQ e o llms.txt podem usar, entre chaves."""
    k = D['kpi']
    fim = D['periodo']['fim']
    ano, mes = fim // 100, fim % 100
    v = {
        'm2': reais(k['m2']), 'm2_iptu': reais(k['m2_iptu']),
        'm2_var_txt': ('alta' if k['m2_var'] >= 0 else 'queda') + f" de {nf(abs(k['m2_var']), 1)}%",
        'n': nf(k['n']), 'por_mes': nf(round(k['por_mes'])),
        'n_var_txt': f"{nf(abs(k['n_var']), 1)}% " + ('a mais' if k['n_var'] >= 0 else 'a menos'),
        'ticket': mil(k['ticket']), 'area_med': nf(k['area_med']),
        'mes_fim': f'{MESES[mes - 1]} de {ano}', 'mes_fim_curto': f'{MESES_C[mes - 1]}/{ano}',
        'v55': mil(round(k['m2'] * 55, -3)), 'v70': mil(round(k['m2'] * 70, -3)),
    }
    for nome, s in (D.get('sub') or {}).items():
        sk = s.get('kpi') or {}
        if sk.get('m2'):
            v[f'sub_{nome}_m2'] = reais(sk['m2'])
            v[f'sub_{nome}_ticket'] = mil(sk['ticket'])
            v[f'sub_{nome}_por_mes'] = nf(round(sk['por_mes']))
    for m in D.get('movimento') or []:
        t = m['tipo']
        v[f'mov_{t}_vol_a'] = nf(m['vol_a'], 1)
        v[f'mov_{t}_vol_b'] = nf(m['vol_b'], 1)
        v[f'mov_{t}_m2_a'] = reais(m['m2_a'])
        v[f'mov_{t}_m2_b'] = reais(m['m2_b'])
    a = alugueis()
    if 'aluguelSp' in a:
        u = a['aluguelSp']['ultimo']
        d = date.fromisoformat(u['data'])
        v['aluguel_sp'] = reais(u['valor'], 2)
        v['aluguel_sp_mes'] = f'{MESES[d.month - 1]} de {d.year}'
        for n in ('1d', '2d', '3d', '4d'):
            if 'aluguelSp' + n in a:
                v['aluguel_sp_' + n] = reais(a['aluguelSp' + n]['ultimo']['valor'], 2)
    if 'rentabilidadeSp' in a:
        v['rentab_sp'] = nf(a['rentabilidadeSp']['ultimo']['valor'], 2) + '%'
    return v


# ---------- dados estruturados ----------
def pessoa():
    return {
        '@type': 'Person', '@id': BASE + '/#andrea', 'name': P['nome'], 'jobTitle': P['cargo'],
        'description': P['bio'], 'image': BASE + P['foto'], 'telephone': P['telefone'], 'email': P['email'],
        'url': BASE + '/', 'knowsLanguage': P['idiomas'],
        'worksFor': {'@type': 'Organization', 'name': P['imobiliaria']},
        'hasCredential': [
            {'@type': 'EducationalOccupationalCredential', 'credentialCategory': 'Registro profissional de corretor de imóveis',
             'name': P['creci'], 'recognizedBy': {'@type': 'Organization', 'name': 'Conselho Regional de Corretores de Imóveis do Estado de São Paulo (CRECI-SP)'}},
            {'@type': 'EducationalOccupationalCredential', 'credentialCategory': 'degree', 'name': P['formacao']},
        ],
    }


def corretora():
    return {
        '@type': 'RealEstateAgent', '@id': BASE + '/#corretora', 'name': f"{P['nome']} · {P['cargo']}",
        'description': P['bio'], 'url': BASE + '/', 'image': BASE + P['foto'],
        'telephone': P['telefone'], 'email': P['email'], 'knowsLanguage': P['idiomas'],
        'address': {'@type': 'PostalAddress', 'addressLocality': 'São Paulo', 'addressRegion': 'SP', 'addressCountry': 'BR'},
        'areaServed': [{'@type': 'Place', 'name': f'{a}, São Paulo'} for a in P['areas']],
        'employee': {'@id': BASE + '/#andrea'},
    }


def site():
    return {'@type': 'WebSite', '@id': BASE + '/#site', 'url': BASE + '/', 'name': 'Boletins imobiliários de São Paulo · Andrea Longobardi',
            'inLanguage': ['pt-BR', 'en'], 'publisher': {'@id': BASE + '/#corretora'}}


# ---------- cabeçalho ----------
LIXO_HEAD = [r'<title>.*?</title>', r'<meta name="description"[^>]*>', r'<meta property="og:[^"]*"[^>]*>',
             r'<meta name="twitter:[^"]*"[^>]*>', r'<meta name="author"[^>]*>', r'<meta property="article:[^"]*"[^>]*>']

CSS = ('.capa-sub{font-family:var(--ui);font-size:clamp(15px,1.5vw,18px);font-weight:600;line-height:1.4;color:var(--ink2);'
       'max-width:44em;margin:12px auto 0;text-wrap:balance}'
       '.mast .capa-sub{margin:10px 0 0}'
       '.seo-bio{font-size:14px;line-height:1.5;max-width:60ch;margin-top:6px;opacity:.92}'
       '.faq-lista{display:grid;gap:10px;max-width:920px}'
       '.faq-item{background:#fff;border:1px solid var(--rule)}'
       '.faq-item>summary{list-style:none;cursor:pointer;display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:16px;padding:14px 18px;min-height:56px}'
       '.faq-item>summary::-webkit-details-marker{display:none}'
       '.faq-item>summary h3{font-size:19px;line-height:1.3}'
       '.faq-item>summary::after{content:"";width:11px;height:11px;border-right:2.5px solid var(--blue);border-bottom:2.5px solid var(--blue);transform:rotate(45deg) translate(-3px,-3px);transition:transform .25s}'
       '.faq-item[open]>summary::after{transform:rotate(225deg) translate(-3px,-3px)}'
       '.faq-item>summary:hover h3{color:var(--red)}'
       '.faq-item>summary:focus-visible{outline:3px solid var(--red);outline-offset:2px}'
       '.faq-r{padding:0 18px 18px;border-top:1px solid var(--rule)}'
       '.faq-r p{margin-top:12px;max-width:62em}'
       '.faq-fontes{margin-top:14px}')


def trocar_head(h, bloco):
    fim_head = h.index('</head>')
    head, corpo = h[:fim_head], h[fim_head:]
    head = re.sub(r'\n?[ \t]*<!-- seo:inicio.*?<!-- seo:fim -->', '', head, flags=re.S)
    for p in LIXO_HEAD:
        head = re.sub(r'[ \t]*' + p + r'\n?', '', head, flags=re.S)
    m = re.search(r'<meta name="viewport"[^>]*>', head)
    head = head[:m.end()] + '\n' + bloco + head[m.end():]
    return head + corpo


def meta_tags(t):
    linhas = [f'<title>{esc(t["titulo"])}</title>', f'<meta name="description" content="{esc(t["descricao"])}">',
              f'<meta name="author" content="{esc(P["nome"])}">']
    for k, v in t['og']:
        linhas.append(f'<meta property="{k}" content="{esc(v)}">')
    for k, v in t['tw']:
        linhas.append(f'<meta name="{k}" content="{esc(v)}">')
    return linhas


# ---------- corpo ----------
def preencher(markup, id_, inner, obrigatorio=True):
    pat = re.compile(r'(\bid="%s"[^>]*>)(?:<!--pre-->.*?<!--/pre-->)?' % re.escape(id_), re.S)
    n = len(pat.findall(markup))
    if n != 1:
        if obrigatorio:
            raise SystemExit(f'id="{id_}" aparece {n} vezes no HTML')
        return markup
    return pat.sub(lambda m: m.group(1) + '<!--pre-->' + inner + '<!--/pre-->', markup)


def boletim(slug, cfg):
    arq = os.path.join(PASTA, slug, 'index.html')
    h = open(arq, encoding='utf-8').read()
    orig = h
    D, C, T = bloco_json(h, 'D'), bloco_json(h, 'C'), textos_T(h)
    V = valores(D)
    url = f'{BASE}/{slug}/'
    sub = cfg['subtitulo']
    titulo = 'Boletim Imobiliário · ' + sub['pt']
    pub, atu = cfg['publicado'], cfg['atualizado']
    faq = [(q['p'], q['r'].format_map(V)) for q in cfg.get('faq', [])]

    # ---- JSON-LD ----
    grafo = [pessoa(), corretora(), site(), {
        '@type': 'Article', '@id': url + '#boletim', 'headline': cfg['manchete'], 'alternativeHeadline': sub['pt'],
        'description': cfg['descricao'], 'url': url, 'mainEntityOfPage': url, 'inLanguage': 'pt-BR',
        'datePublished': pub, 'dateModified': atu, 'image': BASE + P['foto'],
        'author': {'@id': BASE + '/#andrea'}, 'publisher': {'@id': BASE + '/#corretora'}, 'isPartOf': {'@id': BASE + '/#site'},
        'about': {'@type': 'Place', 'name': D['nome'], 'containedInPlace': {'@type': 'City', 'name': 'São Paulo'}},
        'citation': 'Guias de ITBI pagas, Prefeitura de São Paulo',
    }, {
        '@type': 'BreadcrumbList', 'itemListElement': [
            {'@type': 'ListItem', 'position': 1, 'name': 'Boletins imobiliários de São Paulo', 'item': BASE + '/'},
            {'@type': 'ListItem', 'position': 2, 'name': D['nome'], 'item': url}],
    }]
    if faq:
        grafo.append({'@type': 'FAQPage', '@id': url + '#perguntas', 'inLanguage': 'pt-BR', 'mainEntity': [
            {'@type': 'Question', 'name': p, 'acceptedAnswer': {'@type': 'Answer', 'text': sem_tags(r)}} for p, r in faq]})

    foto = BASE + P['foto']
    bloco = '\n'.join(['<!-- seo:inicio · gerado por scripts/seo-boletins.py; os textos ficam em scripts/seo-boletins.json -->'] + meta_tags({
        'titulo': titulo, 'descricao': cfg['descricao'],
        'og': [('og:type', 'article'), ('og:site_name', 'Boletins imobiliários de São Paulo · Andrea Longobardi'),
               ('og:locale', 'pt_BR'), ('og:title', titulo), ('og:description', cfg['descricao']), ('og:url', url),
               ('og:image', foto), ('og:image:width', '560'), ('og:image:height', '560'),
               ('og:image:alt', 'Andrea Longobardi, corretora de imóveis'),
               ('article:published_time', pub), ('article:modified_time', atu), ('article:author', P['nome'])],
        'tw': [('twitter:card', 'summary'), ('twitter:title', titulo), ('twitter:description', cfg['descricao']),
               ('twitter:image', foto)],
    }) + [f'<script type="application/ld+json">{json_ld({"@context": "https://schema.org", "@graph": grafo})}</script>',
          f'<style id="seo-css">{CSS}</style>', '<!-- seo:fim -->'])
    h = trocar_head(h, bloco)

    # ---- corpo: separa a marcação do JavaScript ----
    i = h.index('<script id="D"')
    markup, js = h[:i], h[i:]

    # títulos das telas internas (lugares e enquete) viram h2: a página fica com um só h1
    markup = re.sub(r'<h1 (id="(?:t-lg-h|t-pl-h)"[^>]*)>(.*?)</h1>', r'<h2 \1>\2</h2>', markup, flags=re.S)

    # subtítulo da capa, logo abaixo do nome do bairro
    if 'id="t-sub"' not in markup:
        j = markup.index('id="t-nome"')
        k = markup.index('</div></div>', j)
        markup = markup[:k] + '</div><p class="capa-sub" id="t-sub"></p></div>' + markup[k + len('</div></div>'):]

    # bio da autora no bloco de contato
    if 'id="seo-bio"' not in markup:
        alvo = '<span>reception@liveandlearnbrazil.com</span></div>'
        assert markup.count(alvo) == 1, 'bloco de contato não encontrado'
        markup = markup.replace(alvo, alvo + '<p class="seo-bio" id="seo-bio"></p>')

    nome, prep = D['nome'], {'no': 'no', 'na': 'na', 'em': 'em'}.get(C.get('prep', 'no'), 'no')
    pre = {
        't-kicker': 'Boletim Imobiliário', 't-nome': nome, 't-sub': sub['pt'],
        't-edicao': tx(C['edicao']), 't-dados': tx(C['dados_ate']),
        't-h1': f'Quanto vale o seu imóvel {prep} {nome} hoje?', 't-lead': T.get('lead', ''), 't-area': tx(C['area_desc']),
        't-m-h': T.get('mh', ''), 't-m-p': T.get('mp', ''), 't-p-h': T.get('ph', ''), 't-p-p': T.get('pp', ''),
        't-fo-k': tx(C['forte']['titulo']), 't-fo-f': tx(C['forte']['frase']),
        't-fo-l': ''.join(f'<li>{esc(tx(x))}</li>' for x in C['forte']['itens']),
        't-d-h': tx(C['dicas']['titulo']),
        'tips': ''.join(f'<div class="tip rise on"><div class="n">{n:02d}</div><h3>{esc(tx(d["t"]))}</h3><p>{esc(tx(d["d"]))}</p></div>'
                        for n, d in enumerate(C['dicas']['itens'], 1)),
        't-a-h': tx(C['artigo']['titulo']), 'article': ''.join(f'<p>{esc(tx(p))}</p>' for p in C['artigo']['paragrafos']),
        't-n-h': tx(C['noticias']['titulo']), 't-n-p': tx(C['noticias']['nota']),
        'news': ''.join(
            f'<article><div class="d">{MESES_C[int(n["data"][5:7]) - 1]} {n["data"][:4]}</div><div><h3>{esc(tx(n["t"]))}</h3>'
            f'<p>{esc(tx(n["d"]))}</p><div class="src">Fontes: ' +
            ' · '.join(f'<a href="{esc(u)}" target="_blank" rel="noopener">{esc(a)}</a>' for a, u in n['f']) + '</div></div></article>'
            for n in C['noticias']['itens']),
        'tl': ''.join(f'<div><b>{esc(x.get("ano", ""))}</b><p>{esc(tx(x))}</p></div>' for x in C['historia']['itens']),
        't-c-k': T.get('ck', ''), 't-c-tag': T.get('ctag', ''),
        'g-next': T.get('gnext', ''), 'g-go': T.get('ggo', ''), 'g-other': T.get('gother', ''),
        't-f-go': T.get('fgo', ''), 'copy': T.get('copy', ''), 't-pl-send': T.get('send', ''),
        'seo-bio': f'{esc(P["bio"])} Boletim atualizado em <time datetime="{atu}">{data_pt(atu)}</time>.',
    }
    opcionais = {'t-m-h', 't-m-p', 't-p-h', 't-p-p', 'g-go', 'g-other', 't-pl-send'}
    for id_, inner in pre.items():
        if id_ in ('tips', 'article', 'news', 'tl', 't-fo-l', 'seo-bio'):
            conteudo = inner
        else:
            conteudo = esc(inner)
        markup = preencher(markup, id_, conteudo, obrigatorio=id_ not in opcionais)

    # perguntas frequentes
    markup = re.sub(r'<!-- seo:faq:inicio -->.*?<!-- seo:faq:fim -->\n?', '', markup, flags=re.S)
    if faq:
        itens = ''.join(f'\n    <details class="faq-item"><summary><h3>{esc(p)}</h3></summary><div class="faq-r"><p>{r}</p></div></details>'
                        for p, r in faq)
        sec = (f'<!-- seo:faq:inicio -->\n<section class="sec faq" id="perguntas" lang="pt-BR" aria-labelledby="faq-h"><div class="wrap">\n'
               f'  <div class="sec-h"><div class="sq"></div><h2 id="faq-h">{esc(cfg["faq_titulo"])}</h2>'
               f'<p>Respostas de {esc(P["nome"])}, corretora de imóveis ({esc(P["creci"])}), com os dados deste boletim. '
               f'Atualizado em <time datetime="{atu}">{data_pt(atu)}</time>.</p></div>\n'
               f'  <div class="faq-lista">{itens}\n  </div>\n'
               f'  <p class="small faq-fontes">{esc(cfg.get("faq_fontes", "").format_map(V))}</p>\n'
               f'</div></section>\n<!-- seo:faq:fim -->\n')
        alvo = '<section class="sec band-blue" id="contato">'
        assert markup.count(alvo) == 1
        markup = markup.replace(alvo, sec + alvo)

    # ---- JavaScript: título com maiúscula, subtítulo traduzido ----
    js = js.replace("kicker: {pt:'Boletim imobiliário'", "kicker: {pt:'Boletim Imobiliário'")
    if "set('t-sub'" not in js:
        js = js.replace("set('t-kicker', tx(T.kicker));", "set('t-kicker', tx(T.kicker)); set('t-sub', tx(C.subtitulo));", 1)
    sub_json = '"subtitulo":' + json.dumps(sub, ensure_ascii=False, separators=(',', ':'))
    abre = '<script id="C" type="application/json">{'
    if '"subtitulo":' in js:
        js = re.sub(r'"subtitulo":\{"pt":"(?:[^"\\]|\\.)*","en":"(?:[^"\\]|\\.)*"\}', lambda m: sub_json, js, count=1)
    else:
        js = js.replace(abre, abre + sub_json + ',', 1)

    h = markup + js
    if h != orig:
        open(arq, 'w', encoding='utf-8').write(h)
        print(f'{slug}: atualizado')
    else:
        print(f'{slug}: sem mudança')
    return {'slug': slug, 'url': url, 'titulo': cfg['manchete'], 'sub': sub['pt'], 'atu': atu,
            'llms': cfg.get('llms', '').format_map(V), 'faq': bool(faq)}


def home(atu_dados):
    arq = os.path.join(PASTA, 'index.html')
    h = open(arq, encoding='utf-8').read()
    orig = h
    t = CFG['home']
    url = BASE + '/'
    foto = BASE + P['foto']
    grafo = [pessoa(), corretora(), site(), {
        '@type': 'WebPage', '@id': url + '#painel', 'url': url, 'name': t['titulo'], 'description': t['descricao'],
        'inLanguage': 'en', 'isPartOf': {'@id': BASE + '/#site'}, 'author': {'@id': BASE + '/#andrea'},
        'dateModified': atu_dados, 'about': {'@type': 'City', 'name': 'São Paulo'},
    }]
    bloco = '\n'.join(['<!-- seo:inicio · gerado por scripts/seo-boletins.py; os textos ficam em scripts/seo-boletins.json -->'] + meta_tags({
        'titulo': t['titulo'], 'descricao': t['descricao'],
        'og': [('og:type', 'website'), ('og:site_name', 'Live & Learn Brazil · São Paulo Real Estate'), ('og:locale', 'en_US'),
               ('og:title', t['titulo']), ('og:description', t['descricao']), ('og:url', url), ('og:image', foto),
               ('og:image:width', '560'), ('og:image:height', '560'), ('og:image:alt', 'Andrea Longobardi, real estate broker'),
               ('article:modified_time', atu_dados)],
        'tw': [('twitter:card', 'summary'), ('twitter:title', t['titulo']), ('twitter:description', t['descricao']), ('twitter:image', foto)],
    }) + [f'<script type="application/ld+json">{json_ld({"@context": "https://schema.org", "@graph": grafo})}</script>', '<!-- seo:fim -->'])
    h = trocar_head(h, '  ' + bloco.replace('\n', '\n  '))
    if h != orig:
        open(arq, 'w', encoding='utf-8').write(h)
        print('home: atualizada')
    else:
        print('home: sem mudança')


def escrever(nome, texto):
    arq = os.path.join(PASTA, nome)
    antigo = open(arq, encoding='utf-8').read() if os.path.exists(arq) else None
    if antigo != texto:
        open(arq, 'w', encoding='utf-8').write(texto)
        print(f'{nome}: atualizado')


def main():
    try:
        atu_dados = json.load(open(os.path.join(PASTA, 'dados', 'dados-sgs.json'), encoding='utf-8'))['atualizadoEm'][:10]
    except Exception:
        atu_dados = date.today().isoformat()
    bs = [boletim(slug, cfg) for slug, cfg in CFG['boletins'].items()]
    home(atu_dados)

    escrever('robots.txt', '\n'.join([
        '# realestate.liveandlearnbrazil.com',
        '# Boletins imobiliários abertos a buscadores e assistentes de IA.',
        'User-agent: *',
        'Allow: /',
        'Disallow: /api/',
        'Disallow: /matchmaking/',
        'Content-Signal: search=yes, ai-input=yes, ai-train=no',
        '',
        f'Sitemap: {BASE}/sitemap.xml',
        '']))

    urls = [(BASE + '/', atu_dados, 'daily', '1.0')] + [(b['url'], b['atu'], 'monthly', '0.9') for b in bs]
    escrever('sitemap.xml', '<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' +
             ''.join(f'  <url><loc>{u}</loc><lastmod>{d}</lastmod><changefreq>{c}</changefreq><priority>{p}</priority></url>\n'
                     for u, d, c, p in urls) + '</urlset>\n')

    linhas = [
        '# Boletins imobiliários de São Paulo · Andrea Longobardi', '',
        '> Boletins do mercado imobiliário do Brooklin, do Morumbi e de Cidade Jardim, em São Paulo, com vendas reais '
        'registradas nas guias de ITBI da Prefeitura, mapa de preço do m² por quadra, notícias conferidas em duas fontes '
        'e dicas para quem compra ou vende. Feitos por Andrea Longobardi, corretora de imóveis (CRECI-SP 338018-F), '
        'e atualizados a cada dois meses. A página inicial traz, em inglês, um painel com juros, câmbio e aluguel que '
        'movem o mercado de São Paulo, atualizado nos dias úteis com dados do Banco Central, da FGV e da Fipe.', '',
        '## Boletins de bairro', '']
    for b in bs:
        linhas.append(f"- [{b['titulo']}]({b['url']}): {b['sub']}. Dados do boletim: {b['llms']}.")
    linhas += ['', '## Painel do mercado', '',
               f'- [São Paulo Real Estate, by the numbers]({BASE}/): juros, financiamento, câmbio, aluguel (IVAR e FipeZap) '
               f'e o m² dos bairros, em inglês, para executivos estrangeiros. Dados de {data_pt(atu_dados)}.', '',
               '## Autora e contato', '',
               f"- {P['nome']}, {P['cargo'].lower()}, {P['creci']}. {P['bio']}",
               f"- WhatsApp {P['telefone']} · {P['email']}", '']
    escrever('llms.txt', '\n'.join(linhas))


if __name__ == '__main__':
    main()
