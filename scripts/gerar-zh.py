#!/usr/bin/env python3
"""Gera zh/index.html (home em chinês simplificado) a partir de index.html (inglês).

O desenho é o mesmo; muda o texto, o <head> (SEO para Baidu) e a tipografia chinesa
(sem caixa alta, entrelinha 1.8, nada abaixo de 14 px, fontes do sistema).
Termos seguem o glossário aprovado em "Correcao texto da pagina em chines.docx":
巴西落地服务 (destination services), 企业葡萄牙语培训, 跨文化沟通, 共融之地.

Uso, a partir da pasta SITE 2:  python scripts/gerar-zh.py
Rodar de novo sempre que index.html mudar. Se um trecho em inglês mudar, o script
para e avisa qual tradução precisa ser atualizada.
"""
import os, sys

RAIZ = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
s = open(os.path.join(RAIZ, 'index.html'), encoding='utf-8').read()

def R(en, zh, n=1):
    global s
    c = s.count(en)
    if c != n:
        sys.exit(f'Trecho em inglês mudou ou repetiu ({c}x, esperado {n}x):\n  {en[:120]}')
    s = s.replace(en, zh)

WA = 'https://wa.me/5511954395027'

# ------------------------------------------------------------------ head
R('<!-- Home Live & Learn Brazil (EN).', '<!-- Home Live & Learn Brazil (中文, gerada por scripts/gerar-zh.py a partir de index.html; não editar à mão).')
R('<html lang="en">', '<html lang="zh-Hans">')
R('<title>Live &amp; Learn Brazil · Real estate for foreign companies in São Paulo</title>',
  '<title>圣保罗房地产选址顾问_巴西落地服务_企业葡萄牙语培训-Live &amp; Learn Brazil</title>')
R('<meta name="description" content="Andrea Longobardi, licensed broker in São Paulo: location study, property search and a safe purchase or lease for foreign companies. Real data, step by step.">',
  '<meta name="description" content="为在巴西落地的中国及外国企业提供圣保罗住宅与商业地产选址、租赁与购置顾问：结合真实成交数据，研究物流、客户、距离与价格，让您的业务在这座城市顺利运转。另设企业葡萄牙语培训。">')
R('<link rel="canonical" href="https://liveandlearnbrazil.com/">', '<link rel="canonical" href="https://liveandlearnbrazil.com/zh/">')
R('<link rel="icon" type="image/svg+xml" href="img/', '<link rel="icon" type="image/svg+xml" href="/img/')
R('<script src="src/analytics.js', '<script src="/src/analytics.js')
R('url("fonts/', 'url("/fonts/', 8)
JSONLD = '''<script type="application/ld+json">
{"@context":"https://schema.org","@type":"ProfessionalService","@id":"https://liveandlearnbrazil.com/#organization",
 "name":"Live & Learn Brazil","url":"https://liveandlearnbrazil.com/zh/","inLanguage":"zh-Hans",
 "description":"圣保罗住宅与商业地产选址、租赁与购置顾问，巴西落地服务，企业葡萄牙语培训与跨文化沟通。",
 "areaServed":{"@type":"State","name":"圣保罗州（São Paulo）"},
 "founder":{"@type":"Person","name":"Andrea Longobardi","alternateName":"安臻","jobTitle":"房地产经纪人（CRECI-SP 338018-F）","knowsLanguage":["zh-Hans","pt-BR","en","it"]},
 "email":"reception@liveandlearnbrazil.com","telephone":"+55 11 95439 5027",
 "knowsAbout":["圣保罗房地产","商业地产选址","巴西落地服务","企业葡萄牙语培训","跨文化沟通"]}
</script>
<style>'''
R('<script src="/src/analytics.js?v=20261127a" defer></script>\n<style>', '<script src="/src/analytics.js?v=20261127a" defer></script>\n' + JSONLD)

# tipografia chinesa (brief: nada < 14 px, entrelinha 1.8, sem caixa alta nem espaçamento largo)
ZHCSS = '''
/* ---------- tipografia chinesa ---------- */
:root { --zh: "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", "Source Han Sans SC", "Noto Sans SC", sans-serif; }
body { font-family: "Open Sans", var(--zh); line-height: 1.8; word-break: normal; line-break: strict; }
h1, h2, h3, h4 { font-family: "Libertinus Sans", var(--zh); font-weight: 500; line-height: 1.4; letter-spacing: 0; }
.hero h1 { line-height: 1.3; }
.mono, .aba .t, .btn, .chip, .palco-rot, .caminhos b, .cred th, .comparar th, .breve, .fontes-esc, .ind-cab,
.desenho text, .opcoes .raiz text, .opcoes .t, .acordo .no text, .rede .no-rede text, .legenda .mono {
  text-transform: none !important; letter-spacing: .02em !important; font-family: "PT Sans Narrow", var(--zh); }
.mono, .aba .t, .chip, .palco-rot, .caminhos b, .btn { font-size: 15px; font-weight: 600; }
.cab .mono, .frente .mono { font-size: 15px; }
#comprar .cab .mono { font-size: 26px; letter-spacing: .04em !important; }
.painel p, .painel li, .ficha, .caminhos li, .frente p, .comparar td { font-size: 16px; line-height: 1.8; }
.ficha small, .fonte, .fontes-esc, figcaption, .legenda li, .ind p, .rodape { font-size: 14px !important; }
.desenho .t, .opcoes .t, .acordo .no text { font-size: 16px; }
.desenho .s, .opcoes .s, .acordo .no text.s { font-size: 14px; }
.rede .no-rede text { font-size: 12px; }
.llm .dica { font-size: 14px; line-height: 1.7; }
'''
R('</style>', ZHCSS + '</style>')

# caminhos absolutos (a página fica em /zh/)
R('src="img/', 'src="/img/', s.count('src="img/'))
R('<script src="src/mapas/mapas.js', '<script src="/src/mapas/mapas.js')
R('href="#inicio"><img', 'href="/zh/"><img')

# ------------------------------------------------------------------ cabeçalho
R('<nav aria-label="Sections">', '<nav aria-label="页面导航">')
R('<a href="#passos">Step by step</a>', '<a href="#passos">服务流程</a>')
R('<a href="#comprar">Buy or rent</a>', '<a href="#comprar">购置还是租赁</a>')
R('<a href="#dados">Data</a>', '<a href="#dados">市场数据</a>')
R('<a href="#portugues">Portuguese</a>', '<a href="#portugues">葡萄牙语</a>')
R('<a href="#sobre">About</a>', '<a href="#sobre">关于</a>')
R('<div class="idiomas" aria-label="Language">', '<div class="idiomas" aria-label="语言">')
R('<a href="/" aria-current="page" hreflang="en">EN</a><a href="/zh/" class="zh" lang="zh" hreflang="zh-Hans">中文</a>',
  '<a href="/" hreflang="en" lang="en">EN</a><a href="/zh/" class="zh" aria-current="page" hreflang="zh-Hans">中文</a>')
R('<a class="btn" href="#contato">Contact</a>', '<a class="btn" href="#contato">联系我们</a>')

# ------------------------------------------------------------------ hero e as duas frentes
R('<img class="hero-foto" src="/img/hero-skyline.jpg" alt=""', '<img class="hero-foto" src="/img/hero-skyline.jpg" alt="圣保罗城市天际线"')
R('<h1 id="hero-t">your strategy<br>your talent<br>on shared ground</h1>', '<h1 id="hero-t">您的战略<br>您的团队<br>立足共融之地</h1>')
R('<section class="frentes" aria-label="What we do">', '<section class="frentes" aria-label="服务内容">')
R('<p class="mono">Real estate</p>', '<p class="mono">房地产顾问</p>')
R('<h2 id="f-imovel">Your best address in São Paulo</h2>', '<h2 id="f-imovel">您在圣保罗的最佳地址</h2>')
R('<p>An empathetic and intelligent consultancy to find the best residential and commercial spots for each individual case. We study how your business works in the city (logistics, clients, proximity, price), not just the purchase of a property.</p>',
  '<p>以同理心与专业洞察为您提供选址顾问服务，因案制宜，为您找到最合适的住宅与商业地段。我们研究的是您的业务在这座城市中如何运转——物流、客户、距离、价格——而不只是买下一处房产。</p>')
R('<p class="frente-destaque"><b>No extra fees.</b> Commissions are paid by the real estate agency.</p>',
  '<p class="frente-destaque"><b>无额外费用。</b>佣金由房产中介方支付。</p>')
R(f'<a class="btn" href="{WA}?text=Hello%20Andrea%2C%20I%27d%20like%20to%20talk%20about%20a%20property%20in%20S%C3%A3o%20Paulo.">WhatsApp</a>\n        <a class="btn btn--linha" href="#passos">How it works ↓</a>',
  '<a class="btn" href="#wechat">微信咨询</a>\n        <a class="btn btn--linha" href="#passos">服务流程 ↓</a>')
R('<p class="mono">Portuguese</p>', '<p class="mono">葡萄牙语</p>')
R('<h2 id="f-pt">Everyday and business Portuguese</h2>', '<h2 id="f-pt">生活与商务葡萄牙语</h2>')
R('<p>A digital platform and specialised classes that really help you learn fast and efficiently, develop intercultural communication and reach your goals in Brazil.</p>',
  '<p>数字学习平台与专业课程，真正帮助您快速、高效地学习，提升跨文化沟通能力，实现您在巴西的目标。</p>')
R(f'<a class="btn" href="{WA}?text=Hello%20Andrea%2C%20I%27d%20like%20to%20book%20a%20Portuguese%20trial%20class.">Book a trial class</a>\n        <a class="btn btn--linha" href="#portugues">See the app ↓</a>',
  '<a class="btn" href="#wechat">预约试听课</a>\n        <a class="btn btn--linha" href="#portugues">查看学习应用 ↓</a>')

# ------------------------------------------------------------------ console de passos
R('<div><p class="mono">How we work · 5 steps</p><h2 id="passos-t">From the city map to your keys.</h2></div>',
  '<div><p class="mono">服务流程 · 5 个步骤</p><h2 id="passos-t">从城市地图，到交钥匙。</h2></div>')
R('<p>Each step below is real work with real data. Click a step, or let it play. The maps combine public records from different sources: with them and with what you tell us, we build real estate options that move your plans forward.</p>',
  '<p>以下每一步都是基于真实数据的实际工作。点击任一步骤，或让它自动播放。地图整合了多个来源的公开数据：结合这些数据与您的需求，我们为您提出推动计划落地的房地产方案。</p>')
R('<div class="abas" role="tablist" aria-label="Steps">', '<div class="abas" role="tablist" aria-label="服务步骤">')
for en, zh in [('City', '城市'), ('Street', '街道'), ('Property', '物业'), ('Deal', '交易'), ('First weeks', '入住初期')]:
    R(f'<span class="t">{en}</span>', f'<span class="t">{zh}</span>')
R('South &amp; West São Paulo · metro, river, parks', '圣保罗南区与西区 · 地铁、河流、公园')
R('>Loading map…</p>', '>地图加载中…</p>', 2)
R('aria-pressed="true" data-camada="metro">Metro</button>', 'aria-pressed="true" data-camada="metro">地铁</button>')
R('<p class="palco-rot mono">Brooklin · every block, by real sale price</p>', '<p class="palco-rot mono">Brooklin 区 · 每个街区按真实成交价显示</p>')
R('<div class="legenda" id="legenda-br" aria-label="Price legend">', '<div class="legenda" id="legenda-br" aria-label="价格图例">')
R('data-cam="preco">Price / m²</button>', 'data-cam="preco">每平米价格</button>')
R('data-cam="zonas">Microzones</button>', 'data-cam="zonas">功能分区</button>')
R('data-cam="metro">Metro</button>', 'data-cam="metro">地铁</button>')
R('data-zona="berrini">Office hub</button>', 'data-zona="berrini">办公集聚区</button>')
R('data-zona="residencial">Residential</button>', 'data-zona="residencial">住宅区</button>')
R('data-zona="chacara">In transformation</button>', 'data-zona="chacara">转型中</button>')

# passo 3: desenho dos três caminhos
R('<p class="palco-rot mono">Three paths · one starting point: your plans</p>', '<p class="palco-rot mono">三种路径 · 同一个出发点：您的计划</p>')
R('<title id="opcoes-t">Your plans and needs lead to three paths: a corporate floor, a lease or a purchase</title>', '<title id="opcoes-t">从您的计划与需求出发的三种路径：整层写字楼、租赁或购置</title>')
R('text-anchor="middle">Your plans and needs</text>', 'text-anchor="middle">您的计划与需求</text>')
R('>Corporate floor</text>', '>整层写字楼</text>')
R('>office hubs, full floors</text>', '>商务区整层办公</text>')
R('>Lease</text>', '>租赁</text>')
R('>move in fast, less capital</text>', '>快速入驻，资金占用少</text>')
R('>Purchase</text>', '>购置</text>')
R('>several uses, residence path</text>', '>一址多用，助力居留</text>')
R('<p class="ficha rede-imoveis"><span class="mono">Our network</span><b>10,000+</b> properties in São Paulo<small>Plus partnerships with the best neighbourhood agencies in the city. Whatever you are looking for, we find it on the best terms and with full security.</small></p>',
  '<p class="ficha rede-imoveis"><span class="mono">我们的房源网络</span><b>10,000+</b> 套圣保罗房源<small>并与圣保罗各街区最优秀的房产中介建立合作。无论您在寻找什么，我们都能以最佳条件、在全程保障下为您找到。</small></p>')

# passo 4: checklist da negociação
R('<p class="palco-rot mono">The deal · checked before you sign</p>', '<p class="palco-rot mono">交易 · 签约前全面核查</p>')
R('<title id="acordo-t">Deal checklist: title search, negotiation, contract, notary and registry, keys</title>', '<title id="acordo-t">交易核查清单：产权调查、价格谈判、合同、公证与登记、交钥匙</title>')
for en, zh in [('>Title search</text>', '>产权调查</text>'), ('>Certificates on the owner and the property</text>', '>核查业主与房产的各项证明</text>'),
               ('>Negotiation</text>', '>价格谈判</text>'), ('>Price and terms, backed by real sales nearby</text>', '>以周边真实成交为依据商谈价格与条款</text>'),
               ('>Contract</text>', '>合同</text>'), ('>Drafted and reviewed with a property lawyer</text>', '>由房地产律师起草并审核</text>'),
               ('>Notary &amp; registry</text>', '>公证与登记</text>'), ('>Deed signed, tax paid, ownership registered</text>', '>签署契约、缴纳税费、完成产权登记</text>'),
               ('>Keys</text>', '>交钥匙</text>'), ('>Handover, utilities and condo transferred</text>', '>交房，水电与物业费账户过户</text>')]:
    R(en, zh)

# painéis de texto
R('<p class="mono">Step 01 · The city</p>', '<p class="mono">第 01 步 · 城市</p>')
R('<h3>Where does your company need to be?</h3>', '<h3>您的企业应该落在哪里？</h3>')
R('<div><h4>What I do</h4><p>I map where your clients, suppliers, staff and visiting colleagues need to go: metro lines, the river corridor, the airport, the routes that decide your week. São Paulo is big; the right side of it saves hours.</p></div>',
  '<div><h4>我们做什么</h4><p>我们梳理您的客户、供应商、员工和来访同事需要往返的地点：地铁线路、沿河交通走廊、机场，以及决定您每周日程的通勤路线。圣保罗很大，选对区域能节省大量时间。</p></div>')
R('<div><h4>You receive</h4><ul class="entrega"><li>A shortlist of 2–3 areas that fit your company</li><li>Metro and road access for each one</li></ul></div>',
  '<div><h4>您将获得</h4><ul class="entrega"><li>2–3 个适合您企业的备选区域</li><li>每个区域的地铁与道路交通情况</li></ul></div>')
R('<div class="ficha" id="bairro-info" aria-live="polite"><span class="mono">On the map</span>Lines 2, 4, 5 and 17 of the metro, CPTM Line 9 along the Pinheiros river, parks and main avenues. Tap a neighbourhood to read it.</div>',
  '<div class="ficha" id="bairro-info" aria-live="polite"><span class="mono">地图说明</span>地铁 2、4、5、17 号线，沿 Pinheiros 河的 CPTM 9 号线，以及公园与主干道。点击街区名称即可查看简介。</div>')

R('<p class="mono">Step 02 · The street</p>', '<p class="mono">第 02 步 · 街道</p>')
R('<h3>Block by block, zone by zone.</h3>', '<h3>逐个街区，逐个分区。</h3>')
R('<div><h4>What I do</h4><p>I read zoning and block-level prices. On a mixed-use street a house can be a shop, an office and a home; one block away it may not.</p></div>',
  '<div><h4>我们做什么</h4><p>我们研究城市分区规划与街区级别的价格。在混合用途街道上，一栋房子可以同时作为店铺、办公室和住宅；隔一个街区，可能就不允许了。</p></div>')
R('<span class="mono">Brooklin · whole area</span><b id="br-m2">R$ 12,655</b>/m² apartments<small>Median of 2,963 sales recorded in 12 months to Sep 2026. Pick a microzone below the map.</small>',
  '<span class="mono">Brooklin 区 · 全区</span><b id="br-m2">R$ 12,655</b>/平米（公寓）<small>截至 2026 年 9 月的 12 个月内 2,963 笔成交的中位数。可在地图下方选择功能分区。</small>')
R('<div><h4>Facts I use here</h4><ul class="entrega">', '<div><h4>我们参考的事实</h4><ul class="entrega">')
R('<li>Line 17-Gold opened on 31 Mar 2026, linking Brooklin to Congonhas and Line 5</li>', '<li>地铁 17 号线（金线）于 2026 年 3 月 31 日开通，连接 Brooklin、孔戈尼亚斯机场与 5 号线</li>')
R('<li>Berrini office vacancy 11.2% in Q3 2026, against 14.1% citywide</li>', '<li>2026 年第三季度 Berrini 写字楼空置率为 11.2%，全市为 14.1%</li>')
R('<li>41% of the Água Espraiada Brooklin sector’s extra building rights still available (Apr 2026): new towers will keep coming</li>',
  '<li>截至 2026 年 4 月，Água Espraiada 城市更新计划 Brooklin 片区仍有 41% 的额外建筑容量可用：新楼盘还会陆续出现</li>')
R('<p class="fonte">ITBI (Prefeitura de São Paulo) · JLL · SP Urbanismo · Metrô de São Paulo. Metro lines drawn schematically between stations.</p>',
  '<p class="fonte">ITBI（圣保罗市政府）· JLL · SP Urbanismo · 圣保罗地铁。地铁线路为站间示意。</p>')

R('<p class="mono">Step 03 · The property</p>', '<p class="mono">第 03 步 · 物业</p>')
R('<h3>Three paths, chosen from your plans.</h3>', '<h3>三种路径，依您的计划而定。</h3>')
R('<div><h4>What we do</h4><p>Corporate floors, a lease or a purchase: each has its advantages, and everything starts from your plans and needs.</p></div>',
  '<div><h4>我们做什么</h4><p>整层写字楼、租赁或购置：各有优势，一切都从您的计划与需求出发。</p></div>')
R('<li><b>Corporate floors</b>Full office floors in hubs such as Faria Lima or Berrini, with building services and flexible layouts.</li>',
  '<li><b>整层写字楼</b>在 Faria Lima、Berrini 等商务区租用整层办公空间，配套物业服务，布局灵活。</li>')
R('<li><b>Lease</b>Move in quickly, with less capital tied up, while you get to know the city.</li>',
  '<li><b>租赁</b>快速入驻，资金占用少，同时慢慢熟悉这座城市。</li>')
R('<li><b>Purchase</b>One address for several uses, a path to residence and no rent readjustments.</li>',
  '<li><b>购置</b>一个地址满足多种用途，助力投资居留，无需应对租金调整。</li>')
R('<div><h4>You receive</h4><ul class="entrega"><li>Visit notes and photos for each property</li><li>Document checklist and a fit-out estimate with technical partners</li></ul></div>',
  '<div><h4>您将获得</h4><ul class="entrega"><li>每处房产的看房记录与照片</li><li>文件核查清单，以及与技术伙伴共同出具的装修预算</li></ul></div>')

R('<p class="mono">Step 04 · The deal</p>', '<p class="mono">第 04 步 · 交易</p>')
R('<h3>Nothing signed before it is checked.</h3>', '<h3>未经核查，绝不签约。</h3>')
R('<div><h4>What I do</h4><p>I negotiate with data on recent sales nearby, run the title search and coordinate lawyers and notaries I have worked with for years, whether you buy with cash, financing or lease.</p></div>',
  '<div><h4>我们做什么</h4><p>我们以周边近期成交数据为依据进行谈判，开展产权调查，并协调合作多年的律师与公证处——无论您是全款购置、贷款购置还是租赁。</p></div>')
R('<div><h4>You receive</h4><ul class="entrega"><li>A negotiated price and terms</li><li>Checked documents and a reviewed contract</li><li>Registered deed (or signed lease) and the keys</li></ul></div>',
  '<div><h4>您将获得</h4><ul class="entrega"><li>谈妥的价格与条款</li><li>经过核查的文件与审核过的合同</li><li>已登记的产权契约（或已签署的租约）以及钥匙</li></ul></div>')
R('<p class="ficha"><span class="mono">Financing today</span><b id="p5-juros">8.99%</b> a year<small>Average rate on home loans to individuals, Banco Central.</small></p>',
  '<p class="ficha"><span class="mono">当前贷款利率</span>年利率 <b id="p5-juros">8.99%</b><small>巴西央行公布的个人住房贷款平均利率。</small></p>')

R('<p class="mono">Step 05 · The first weeks</p>', '<p class="mono">第 05 步 · 入住初期</p>')
R('<h3>Settling in, with people who did it before.</h3>', '<h3>由过来人陪您安顿下来。</h3>')
R('<div><h4>What I do</h4><p>I introduce you to a network of Chinese families who have lived in São Paulo for years: a walk around the neighbourhood, opening a bank account, visiting schools, the first weeks.</p></div>',
  '<div><h4>我们做什么</h4><p>我们为您引荐在圣保罗生活多年的华人家庭网络：熟悉街区、开立银行账户、参观学校，陪您度过最初几周。</p></div>')
R('<div><h4>You receive</h4><ul class="entrega"><li>Introductions to the right people</li><li>A simple first-weeks plan for your team</li><li>Portuguese classes, if you want them</li></ul></div>',
  '<div><h4>您将获得</h4><ul class="entrega"><li>对接合适的人</li><li>为您团队准备的简明入住计划</li><li>如有需要，葡萄牙语课程</li></ul></div>')
R('<p class="fonte">Icons on the map are illustrative, not addresses.</p>', '<p class="fonte">地图上的图标仅为示意，并非实际地址。</p>')
R('<span class="mono" id="console-estado">Step 1 of 5</span>', '<span class="mono" id="console-estado">第 1 步，共 5 步</span>')
R('<span class="mono">❚❚ Pause</span></button>', '<span class="mono">❚❚ 暂停</span></button>')

# ------------------------------------------------------------------ comprar ou alugar
R('<div><p class="mono">Buy or rent</p><h2 id="comprar-t">For a mid-sized company, buying often solves more than it costs.</h2></div>',
  '<div><p class="mono">购置还是租赁</p><h2 id="comprar-t">对中型企业而言，购置房产解决的问题，往往比它花费的更多。</h2></div>')
R('<p>Most foreign companies rent by reflex. Here is the comparison I go through with each client.</p>',
  '<p>大多数外国企业习惯性地选择租赁。以下是我们与每位客户逐项对比的内容。</p>')
R('<thead><tr><th scope="col"></th><th scope="col">Renting</th><th scope="col">Buying</th></tr></thead>',
  '<thead><tr><th scope="col"></th><th scope="col">租赁</th><th scope="col">购置</th></tr></thead>')
R('data-rot="Renting"', 'data-rot="租赁"', 4)
R('data-rot="Buying"', 'data-rot="购置"', 4)
R('<th scope="row">Uses</th><td data-rot="租赁">Separate contracts for warehouse, shop, office and staff housing.</td><td data-rot="购置">One address for all of them, where zoning allows it.</td>',
  '<th scope="row">用途</th><td data-rot="租赁">仓库、店铺、办公室和员工住房分别签约。</td><td data-rot="购置">在分区规划允许的情况下，一个地址满足全部用途。</td>')
R('<th scope="row">Residence</th><td data-rot="租赁">No effect on immigration.</td><td data-rot="购置">Property above the legal minimum can support a residence permit for the investor.</td>',
  '<th scope="row">居留</th><td data-rot="租赁">对移民身份没有影响。</td><td data-rot="购置">购置超过法定最低金额的房产，可用于申请投资者居留许可。</td>')
R('<th scope="row">Readjustments</th><td data-rot="租赁">Contracts in force rose <b data-v="ivar12m">4.47%</b> in 12 months (FGV IVAR); asking rents <b data-v="aluguelSp12m">6.63%</b> (FipeZap).</td><td data-rot="购置">No rent to renegotiate. Condo fees and IPTU remain.</td>',
  '<th scope="row">租金调整</th><td data-rot="租赁">现行合同 12 个月内上涨 <b data-v="ivar12m">4.47%</b>（FGV IVAR）；挂牌租金上涨 <b data-v="aluguelSp12m">6.63%</b>（FipeZap）。</td><td data-rot="购置">无需重新谈判租金；物业费与 IPTU 房产税仍需缴纳。</td>')
R('<th scope="row">Asset</th><td data-rot="租赁">Payments leave the company.</td><td data-rot="购置">The property stays on the books, in the currency the company now earns in.</td>',
  '<th scope="row">资产</th><td data-rot="租赁">租金支出流出公司。</td><td data-rot="购置">房产留在公司账上，以公司在巴西的收入货币（雷亚尔）计价。</td>')
R('<div><h3>But buying is not always the answer.</h3><p>Corporate floors and short stays usually make more sense rented. With the Selic at <b data-v="selic">13.75%</b> a year, money has a price: every recommendation I make comes with the numbers, including the ones that argue against buying.</p></div>',
  '<div><h3>但购置并不总是最佳答案。</h3><p>整层写字楼和短期停留通常更适合租赁。巴西基准利率（Selic）为每年 <b data-v="selic">13.75%</b>，资金是有成本的：我们给出的每一项建议都附有数据，包括那些不支持购置的数据。</p></div>')

# ------------------------------------------------------------------ dados
R('<div><p class="mono">São Paulo · data panel</p><h2 id="dados-t">The numbers that decide what a property really costs.</h2></div>',
  '<div><p class="mono">圣保罗 · 数据面板</p><h2 id="dados-t">决定房产真实成本的数字。</h2></div>')
R('<p>Rates, the exchange rate and rents, updated every working day from public sources. The full panel lives on my real estate portal.</p>',
  '<p>利率、汇率与租金，每个工作日根据公开数据更新。完整面板见我们的房地产门户网站。</p>')
R('<p class="fontes-esc">Banco Central do Brasil (SGS) · FGV IBRE (IVAR) · Fipe (FipeZap). <span id="atualizado"></span> <a href="https://realestate.liveandlearnbrazil.com/">Open the full market panel →</a></p>',
  '<p class="fontes-esc">巴西央行（SGS）· FGV IBRE（IVAR）· Fipe（FipeZap）。<span id="atualizado"></span> <a href="https://realestate.liveandlearnbrazil.com/">打开完整市场面板 →</a></p>')

# ------------------------------------------------------------------ português
R('<p class="mono">Also · Portuguese</p>', '<p class="mono">另设 · 葡萄牙语</p>')
R('<h2 id="pt-t">Portuguese for business and intercultural communication.</h2>', '<h2 id="pt-t">企业葡萄牙语培训与跨文化沟通。</h2>')
R('<p>Classes built around your real situations, with materials written for each client.</p>', '<p>围绕您真实的工作与生活场景设计课程，教材为每位学员量身编写。</p>')
R('<li>Meetings, negotiation and WhatsApp with suppliers</li>', '<li>会议、商务谈判，以及与供应商的 WhatsApp 沟通</li>')
R('<li>The first weeks: bank, building, doctor, school</li>', '<li>落地初期：银行、公寓物业、就医、学校</li>')
R('<li>Fifteen years teaching Portuguese to foreigners, including executives at Bank of China</li>', '<li>十五年对外葡萄牙语教学经验，学员包括中国银行的高管</li>')
R(f'<a class="btn" href="{WA}?text=Hello%20Andrea%2C%20I%27d%20like%20to%20book%20a%20Portuguese%20trial%20class.">Book a trial class</a>\n      <span class="breve mono"><i aria-hidden="true"></i>Online platform in development</span>',
  '<a class="btn" href="#wechat">预约试听课</a>\n      <span class="breve mono"><i aria-hidden="true"></i>在线学习平台开发中</span>')
R('alt="App: supermarket vocabulary in Portuguese, English and Chinese"', 'alt="葡萄牙语学习应用：超市场景词汇（葡、英、中三语）"')
R('alt="App: exercise on the natural way to ask to pay by Pix"', 'alt="葡萄牙语学习应用：用 Pix 付款的地道说法练习"')
R('alt="App: short video scenes"', 'alt="葡萄牙语学习应用：情景短视频"')
R('<figcaption>Survival Portuguese app, as it is today. Every line recorded by a teacher.</figcaption>', '<figcaption>生存葡萄牙语应用（Survival Portuguese）当前版本，每句话均由教师录音。</figcaption>')

# ------------------------------------------------------------------ sobre
R('<small>Real estate broker · CRECI-SP 338018-F</small>', '<small>房地产经纪人 · CRECI-SP 338018-F</small>')
R('<p class="mono">About</p>', '<p class="mono">关于</p>')
R('<h2 id="sobre-t">Live &amp; Learn Brazil is Andrea Longobardi.</h2>', '<h2 id="sobre-t">Live &amp; Learn Brazil 由安臻（Andrea Longobardi）主理。</h2>')
R('<p>You talk to the person who studies the data, walks the streets and sits at the negotiating table. When a case needs a lawyer, an accountant or an interpreter, I bring in people I have worked with for years.</p>',
  '<p>研究数据、实地走访街区、坐上谈判桌的，始终是同一个人。需要律师、会计师或翻译时，我们会引荐合作多年的专业人士。</p>')
R('<tr><th scope="row">Real estate</th><td>Licensed broker, CRECI-SP 338018-F; technical training in real estate transactions</td></tr>',
  '<tr><th scope="row">房地产</th><td>圣保罗州持证房地产经纪人（CRECI-SP 338018-F），受过房地产交易专业技术培训</td></tr>')
R('<tr><th scope="row">Building</th><td>Technical training in building construction and interior design</td></tr>',
  '<tr><th scope="row">建筑</th><td>受过建筑施工与室内设计专业技术培训</td></tr>')
R('<tr><th scope="row">Research</th><td>PhD in education and history, University of São Paulo and University of Bologna</td></tr>',
  '<tr><th scope="row">学术</th><td>圣保罗大学与博洛尼亚大学教育学与历史学博士</td></tr>')
R('<tr><th scope="row">China</th><td>Studied in Beijing; fifteen years teaching Portuguese to Chinese professionals</td></tr>',
  '<tr><th scope="row">中国</th><td>曾在北京学习；十五年为中国专业人士教授葡萄牙语</td></tr>')

# ------------------------------------------------------------------ contato e rodapé
R('<p class="mono">Start with step 01</p>', '<p class="mono">从第 01 步开始</p>')
R('<h2 id="contato-t">Tell me what your company needs. I’ll tell you where to look.</h2>', '<h2 id="contato-t">告诉我们您的企业需要什么，我们告诉您该去哪里找。</h2>')
R('<p>Size, uses, budget, how many people will come from China. The first conversation costs nothing.</p>', '<p>面积、用途、预算、将有多少人从中国过来。第一次沟通免费。</p>')
R(f'<a class="btn" href="{WA}?text=Hello%20Andrea%2C%20I%27d%20like%20to%20talk%20about%20a%20property%20for%20my%20company%20in%20S%C3%A3o%20Paulo.">WhatsApp</a>\n        <a class="btn btn--linha" href="mailto:reception@liveandlearnbrazil.com?subject=Property%20in%20S%C3%A3o%20Paulo">Email</a>',
  f'<a class="btn" href="#wechat">微信咨询</a>\n        <a class="btn btn--linha" href="{WA}">WhatsApp</a>\n        <a class="btn btn--linha" href="mailto:reception@liveandlearnbrazil.com?subject=%E5%9C%A3%E4%BF%9D%E7%BD%97%E6%88%BF%E5%9C%B0%E4%BA%A7%E5%92%A8%E8%AF%A2">邮件</a>')
R('alt="WeChat QR code"', 'alt="微信二维码"')
R('<li><span class="mono">WeChat</span>Scan the code <span class="zh" lang="zh">扫码添加微信</span></li>', '<li><span class="mono">微信</span>扫码添加微信（长按二维码可保存）</li>')
R('<li><span class="mono">Email</span>', '<li><span class="mono">邮箱</span>')
R('<span>Andrea Longobardi · CRECI-SP 338018-F · São Paulo</span>', '<span>安臻 Andrea Longobardi · CRECI-SP 338018-F · 圣保罗</span>')
R('<nav class="barra-cel" id="barra-cel" aria-label="Quick contact">', '<nav class="barra-cel" id="barra-cel" aria-label="快速联系">')
R(f'<a href="{WA}?text=Hello%20Andrea%2C%20I%27d%20like%20to%20talk%20about%20a%20property%20for%20my%20company%20in%20S%C3%A3o%20Paulo.">WhatsApp</a>\n  <a href="#contato">WeChat</a>',
  f'<a href="#wechat">微信</a>\n  <a href="{WA}">WhatsApp</a>')

# ------------------------------------------------------------------ textos gerados pelo script da página
R("const mes = s => { const [y, m] = s.split('-'); return new Date(+y, +m - 1, 1).toLocaleString('en-GB', { month: 'short', year: 'numeric' }); };",
  "const mes = s => { const [y, m] = s.split('-'); return `${+y}年${+m}月`; };")
R("const dia = s => new Date(s + 'T12:00:00').toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });",
  "const dia = s => { const [y, m, d] = s.split('-'); return `${+y}年${+m}月${+d}日`; };")
for en, zh in [
    ("['selic', 'Selic rate', '% a year', 2, 'The Central Bank’s base rate: the price of money and of every financing.'", "['selic', 'Selic 基准利率', '% 年', 2, '巴西央行基准利率：资金的价格，也是所有贷款的价格。'"),
    ("['ptax', 'Dollar', 'R$ per USD', 2, 'PTAX rate. A weaker real makes Brazil cheaper for foreign capital.'", "['ptax', '美元汇率', '雷亚尔/美元', 2, 'PTAX 汇率。雷亚尔走弱，巴西资产对外资更便宜。'"),
    ("['ipca12m', 'Inflation', '% in 12 months', 2, 'IPCA, the official consumer inflation index.'", "['ipca12m', '通胀率', '% 近 12 个月', 2, 'IPCA，巴西官方消费者物价指数。'"),
    ("['aluguelSp', 'Asking rent', 'R$ / m² month', 2, 'Average residential asking rent in São Paulo (FipeZap).'", "['aluguelSp', '挂牌租金', '雷亚尔/平米·月', 2, '圣保罗住宅平均挂牌租金（FipeZap）。'"),
    ("['rentabilidadeSp', 'Rental yield', '% a year', 2, 'Yearly rent as a share of the price, São Paulo average.'", "['rentabilidadeSp', '租金回报率', '% 年', 2, '年租金占房价的比例，圣保罗平均水平。'"),
    ("['jurosImob', 'Mortgage rate', '% a year', 2, 'Average rate on home loans to individuals.'", "['jurosImob', '房贷利率', '% 年', 2, '个人住房贷款平均利率。'"),
    ("$('atualizado').textContent = 'Updated ' + dia(atualizadoEm) + '.';", "$('atualizado').textContent = '更新于 ' + dia(atualizadoEm) + '。';"),
    ("${dd ? ` · R$ ${fmt(dd.kpi.m2)}/m²` : ''}", "${dd ? ` · R$ ${fmt(dd.kpi.m2)}/平米` : ''}"),
    ("t: 'School visits'", "t: '参观学校'"), ("t: 'Bank account'", "t: '开立银行账户'"), ("t: 'Chinese families'", "t: '华人家庭'"),
    ("t: 'Neighbourhood walk'", "t: '熟悉街区'"), ("t: 'Portuguese'", "t: '葡萄牙语'"),
    ('text-anchor="middle">Your address</text>', 'text-anchor="middle">您的新地址</text>'),
    ("<b>R$ ${fmt(sub.kpi.m2)}</b>/m² apartments · <span class=\"${sub.kpi.m2_var > 0 ? 'sobe' : ''}\">${sub.kpi.m2_var > 0 ? '+' : ''}${fmt(sub.kpi.m2_var, 1)}%</span> vs previous period<small>${sub.nome.en}. ${z.txt}</small>",
     "<b>R$ ${fmt(sub.kpi.m2)}</b>/平米（公寓）· 较上一周期 <span class=\"${sub.kpi.m2_var > 0 ? 'sobe' : ''}\">${sub.kpi.m2_var > 0 ? '+' : ''}${fmt(sub.kpi.m2_var, 1)}%</span><small>${z.nome || sub.nome.en}：${z.txt}</small>"),
    ("const faixas = [`below R$ ${fmt(Q[0] / 1000, 1)}k`,", "const faixas = [`低于 R$ ${fmt(Q[0] / 1000, 1)}k`,"),
    ("`above R$ ${fmt(Q[3] / 1000, 1)}k`];", "`高于 R$ ${fmt(Q[3] / 1000, 1)}k`];"),
    ('<span class="mono">Apartment m² · block median</span>', '<span class="mono">公寓每平米价格 · 街区中位数</span>'),
    ('</i>houses only</li>', '</i>仅有独栋住宅成交</li>'),
    ("rot.textContent = 'South & West São Paulo · metro, river, parks';", "rot.textContent = '圣保罗南区与西区 · 地铁、河流、公园';"),
    ("rot.textContent = 'Around your new address · the support network';", "rot.textContent = '您的新地址周边 · 生活支持网络';"),
    ("$('console-estado').textContent = `Step ${n} of ${abas.length}`;", "$('console-estado').textContent = `第 ${n} 步，共 ${abas.length} 步`;"),
    ("'<span class=\"mono\">▶ Play</span>'", "'<span class=\"mono\">▶ 播放</span>'"),
    ("'<span class=\"mono\">❚❚ Pause</span>'", "'<span class=\"mono\">❚❚ 暂停</span>'"),
]:
    R(en, zh)
R('>Map unavailable offline</p>', '>地图暂时无法加载</p>', 2)

out = os.path.join(RAIZ, 'zh', 'index.html')
open(out, 'w', encoding='utf-8', newline='\n').write(s)
print('zh/index.html gerado,', len(s), 'caracteres')
