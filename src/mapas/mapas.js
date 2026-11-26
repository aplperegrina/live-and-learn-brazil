/* Live & Learn Brazil — mapas interativos das propostas C e D.
   Sem dependências e sem nada externo (funciona na China). Os dados vêm de
   propostas/shared/dados/*.json, gerados por propostas/ferramentas/gerar-mapas.py.

   LLMapas.brooklin(el, opts) -> Promise<api>   quadras do Brooklin por m² (ITBI), microzonas, metrô
   LLMapas.sp(el, opts)       -> Promise<api>   Zona Sul e Oeste: avenidas, rio, parques, metrô, bairros

   Cores por variáveis CSS no elemento (todas opcionais): --m-fundo, --m-tinta, --m-sec, --m-linha,
   --m-bronze, --m-vinho, --m-rio, --m-parque, --m-q1..--m-q5, --m-realce. */
(function () {
  'use strict';
  const NS = 'http://www.w3.org/2000/svg';
  const reduz = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const fmt = n => Math.round(n).toLocaleString('en-US');
  const ZH = /^zh/i.test(document.documentElement.lang || '');
  const plural = (n, um, varios) => n > 1 ? varios : um;
  const BASE = (document.currentScript && document.currentScript.src || '').replace(/mapas\.js.*$/, '');
  const cache = {};
  const carrega = nome => cache[nome] || (cache[nome] = fetch(BASE + 'dados/' + nome + '.json').then(r => r.json()));

  function el(tag, attrs, pai) {
    const e = document.createElementNS(NS, tag);
    for (const k in attrs) if (attrs[k] != null) e.setAttribute(k, attrs[k]);
    if (pai) pai.appendChild(e);
    return e;
  }

  const CSS = `
.llm{position:relative;--m-fundo:#F6F9FF;--m-tinta:#2E2E2E;--m-sec:#56607A;--m-linha:#C9D1DE;--m-bronze:#A07A5F;--m-vinho:#8E1B2E;
 --m-rio:#B9C9DC;--m-parque:#DCE4D6;--m-q0:#EEF1F6;--m-q1:#DCE3EE;--m-q2:#B7C3D6;--m-q3:#8796B3;--m-q4:#566A8E;--m-q5:#2E3B55;--m-realce:#8E1B2E}
.llm svg{display:block;width:100%;height:auto;touch-action:manipulation;user-select:none}
.llm .q{fill:var(--m-q0);stroke:var(--m-fundo);stroke-width:5;transition:fill .5s,opacity .6s;cursor:pointer}
.llm .q.out{opacity:.35}
.llm .q.c1{fill:var(--m-q1)}.llm .q.c2{fill:var(--m-q2)}.llm .q.c3{fill:var(--m-q3)}.llm .q.c4{fill:var(--m-q4)}.llm .q.c5{fill:var(--m-q5)}
.llm .q.casa{fill:url(#llm-hach)}
.llm .q:hover,.llm .q.sel{fill:var(--m-realce)!important;opacity:1}
.llm.sem-preco .q{fill:var(--m-q0)!important}
.llm .limite{fill:none;stroke:var(--m-tinta);stroke-width:9;stroke-dasharray:30 18;opacity:.55}
.llm .zona{fill:transparent;stroke-width:14;transition:fill .4s,opacity .4s;cursor:pointer}
.llm .zona.escritorios{stroke:var(--m-bronze)}.llm .zona.residencial{stroke:var(--m-q4);stroke-dasharray:4 22;stroke-linecap:round}
.llm .zona.transformacao{stroke:var(--m-vinho);stroke-dasharray:46 22}
.llm .zona.ativa.escritorios{fill:rgba(160,122,95,.30)}.llm .zona.ativa.residencial{fill:rgba(86,106,142,.22)}.llm .zona.ativa.transformacao{fill:rgba(142,27,46,.16)}
.llm.sem-zonas .zona{opacity:0;pointer-events:none}
.llm .linha{fill:none;stroke-linecap:round;stroke-linejoin:round}
.llm.sem-metro .metro{opacity:0}
.llm .metro{transition:opacity .4s}
.llm .est{fill:#fff;stroke:var(--m-tinta)}
.llm .est-txt,.llm .av-txt,.llm .bairro-txt,.llm .rio-txt{font-family:"PT Sans Narrow","Arial Narrow",sans-serif;letter-spacing:.06em;paint-order:stroke;stroke:var(--m-fundo);stroke-linejoin:round}
.llm .est-txt{fill:var(--m-tinta);font-weight:700;text-transform:uppercase}
.llm .av-txt{fill:var(--m-sec);text-transform:uppercase}
.llm .rio-txt{fill:#5D7896;font-style:italic;text-transform:uppercase}
.llm .via{fill:none;stroke:var(--m-linha);stroke-linecap:round;stroke-linejoin:round;vector-effect:non-scaling-stroke}
.llm .ns{vector-effect:non-scaling-stroke}
.llm .via.grande{stroke:#AEB8C8}
.llm .via.grande:hover{stroke:var(--m-tinta)}
.llm .rio{fill:none;stroke:var(--m-rio);stroke-linecap:round;stroke-linejoin:round;vector-effect:non-scaling-stroke}
.llm .parque{fill:var(--m-parque);stroke:none}
.llm .perim{fill:rgba(160,122,95,.16);stroke:var(--m-bronze);stroke-width:1.6;vector-effect:non-scaling-stroke;cursor:pointer;transition:fill .3s}
.llm .perim:hover,.llm .perim.ativa{fill:rgba(160,122,95,.34)}
.llm .pino{cursor:pointer}
.llm .pino circle{stroke:#fff;stroke-width:2.5;transition:r .25s}
.llm .pino:hover circle,.llm .pino.ativa circle{r:13}
.llm .pino.escritorios circle{fill:var(--m-q5)}.llm .pino.residencial circle{fill:var(--m-bronze)}
.llm .pino.transformacao circle{fill:var(--m-vinho)}.llm .pino.misto circle{fill:var(--m-sec)}
.llm .bairro-txt{fill:var(--m-tinta);font-weight:700;text-transform:uppercase;stroke-width:6}
.llm .dica{position:absolute;z-index:5;pointer-events:none;max-width:260px;padding:10px 12px;background:#fff;border:1px solid var(--m-linha);
 box-shadow:0 10px 30px rgba(46,59,85,.16);font:13px/1.45 "Open Sans",Arial,sans-serif;color:var(--m-tinta);opacity:0;transform:translateY(4px);transition:opacity .15s,transform .15s}
.llm .dica.on{opacity:1;transform:none}
.llm .dica b{display:block;font-family:"Libertinus Sans",Arial,sans-serif;font-size:17px;font-weight:400;color:var(--m-q5)}
.llm .dica small{display:block;color:var(--m-sec);font-size:12px}
.llm .desenha{stroke-dasharray:var(--len);stroke-dashoffset:var(--len)}
.llm.vivo .desenha{transition:stroke-dashoffset 2.4s cubic-bezier(.33,0,.15,1);stroke-dashoffset:0}
@media (prefers-reduced-motion:reduce){.llm *{transition:none!important}.llm .desenha{stroke-dasharray:none}}`;
  function estilo() {
    if (document.getElementById('llm-css')) return;
    const s = document.createElement('style'); s.id = 'llm-css'; s.textContent = CSS; document.head.appendChild(s);
  }

  function dica(raiz) {
    const d = document.createElement('div'); d.className = 'dica'; d.setAttribute('role', 'status'); raiz.appendChild(d);
    return {
      mostra(html, ev) {
        d.innerHTML = html; d.classList.add('on');
        const r = raiz.getBoundingClientRect();
        let x = (ev.clientX || r.left + r.width / 2) - r.left + 14, y = (ev.clientY || r.top + r.height / 2) - r.top + 14;
        x = Math.min(x, r.width - d.offsetWidth - 6); y = Math.min(y, r.height - d.offsetHeight - 6);
        d.style.left = Math.max(6, x) + 'px'; d.style.top = Math.max(6, y) + 'px';
      },
      esconde() { d.classList.remove('on'); }
    };
  }

  function preparaDesenho(svg) {
    svg.querySelectorAll('.desenha').forEach(p => { try { p.style.setProperty('--len', Math.ceil(p.getTotalLength())); } catch (e) {} });
  }
  function quandoVisivel(raiz, fn) {
    // depois do traçado, tira o tracejado: com zoom e vector-effect ele deixaria a linha cortada
    const limpa = () => setTimeout(() => raiz.querySelectorAll('.desenha').forEach(p => p.classList.remove('desenha')), reduz ? 0 : 2700);
    if (reduz || !('IntersectionObserver' in window)) { raiz.classList.add('vivo'); fn(); limpa(); return; }
    new IntersectionObserver((es, ob) => es.forEach(e => { if (e.isIntersecting) { ob.disconnect(); requestAnimationFrame(() => { raiz.classList.add('vivo'); fn(); limpa(); }); } }), { threshold: .2 }).observe(raiz);
  }

  /* ------------------------------------------------------------ Brooklin */
  const ZONAS = ZH ? {
    berrini: { papel: 'escritorios', rot: '办公集聚区', nome: 'Berrini 一侧', txt: '位于河岸与 Berrini 大道之间。写字楼挂牌租金比 Faria Lima 商务轴线低 67–71%；2026 年第三季度空置率为 11.2%（JLL）。' },
    residencial: { papel: 'residencial', rot: '家庭住宅区', nome: 'Brooklin Paulista 与 Brooklin Novo', txt: '家庭公寓集中的街区，地铁 5 号线沿线；17 号线自 2026 年 3 月起开通。' },
    chacara: { papel: 'transformacao', rot: '转型中', nome: 'Chácara Santo Antônio', txt: '原工业用地正逐步转型为写字楼与住宅楼。' }
  } : {
    berrini: { papel: 'escritorios', rot: 'Office hub', txt: 'Between the river and Av. Berrini. Asking office rents 67–71% below the Faria Lima axis; vacancy 11.2% in Q3 2026 (JLL).' },
    residencial: { papel: 'residencial', rot: 'Family residential', txt: 'Brooklin Paulista and Brooklin Novo: family apartments, Line 5 and, since March 2026, Line 17.' },
    chacara: { papel: 'transformacao', rot: 'In transformation', txt: 'Chácara Santo Antônio: former industrial lots turning into offices and residential towers.' }
  };
  const COR_LINHA = { 5: '#9B3894', 17: '#A6883E' };

  async function brooklin(raiz, opts = {}) {
    estilo(); raiz.classList.add('llm');
    const D = await carrega('brooklin');
    const Q = D.quantis;
    const svg = el('svg', { viewBox: `0 0 ${D.W} ${D.H}`, role: 'img', 'aria-label': ZH ? '圣保罗 Brooklin 区地图：按公寓成交价中位数着色的街区、功能分区与地铁线路' : 'Map of Brooklin, São Paulo: city blocks shaded by median apartment price per square metre, microzones and metro lines' }, raiz);
    const defs = el('defs', {}, svg);
    const pat = el('pattern', { id: 'llm-hach', width: 40, height: 40, patternUnits: 'userSpaceOnUse', patternTransform: 'rotate(45)' }, defs);
    el('rect', { width: 40, height: 40, fill: 'var(--m-q1)' }, pat); el('line', { x1: 0, y1: 0, x2: 0, y2: 40, stroke: 'var(--m-bronze)', 'stroke-width': 10, opacity: .55 }, pat);

    const gq = el('g', {}, svg), gz = el('g', {}, svg), gm = el('g', { class: 'metro' }, svg), gt = el('g', {}, svg);
    const tip = dica(raiz);
    const cls = f => f.a ? 'c' + (f.a[0] < Q[0] ? 1 : f.a[0] < Q[1] ? 2 : f.a[0] < Q[2] ? 3 : f.a[0] < Q[3] ? 4 : 5) : f.h ? 'casa' : '';
    const quadras = D.q.map(f => {
      const p = el('path', { d: f.d, class: 'q ' + cls(f) + (f.in ? '' : ' out') }, gq);
      p.dataset.y = parseFloat(f.d.split(',')[1]);
      p.addEventListener('pointermove', ev => {
        const linhas = [];
        if (f.a) linhas.push(ZH ? `公寓：<b>R$ ${fmt(f.a[0])}/平米</b><small>${f.a[1]} 笔成交记录</small>` : `Apartments: <b>R$ ${fmt(f.a[0])}/m²</b><small>${f.a[1]} ${plural(f.a[1], 'sale', 'sales')} recorded</small>`);
        if (f.h) linhas.push(ZH ? `独栋住宅：R$ ${fmt(f.h[0])}/平米 <small>${f.h[1]} 笔成交</small>` : `Houses: R$ ${fmt(f.h[0])}/m² <small>${f.h[1]} ${plural(f.h[1], 'sale', 'sales')}</small>`);
        if (!linhas.length) linhas.push(ZH ? '<small>该街区在此期间没有带价格的成交记录</small>' : '<small>No priced sale in this block in the period</small>');
        tip.mostra(`<small>${(f.r || []).join(' · ')}</small>${linhas.join('')}`, ev);
      });
      p.addEventListener('pointerleave', tip.esconde);
      return p;
    });
    el('path', { d: D.b, class: 'limite' }, svg).parentNode.insertBefore(svg.lastChild, gz);

    const zonas = {};
    for (const k in D.sub) {
      const z = ZONAS[k];
      const p = el('path', { d: D.sub[k].d, class: 'zona ' + z.papel, tabindex: 0, 'aria-label': z.rot }, gz);
      zonas[k] = p;
      const v = `${D.sub[k].kpi.m2_var > 0 ? '+' : ''}${D.sub[k].kpi.m2_var}%`;
      const mostra = ev => tip.mostra(ZH
        ? `<small>${z.rot}</small><b>${z.nome}</b>R$ ${fmt(D.sub[k].kpi.m2)}/平米 · 较上一周期 ${v}<small>${z.txt}</small>`
        : `<small>${z.rot}</small><b>${D.sub[k].nome.en}</b>R$ ${fmt(D.sub[k].kpi.m2)}/m² · ${v} vs previous period<small>${z.txt}</small>`, ev);
      p.addEventListener('pointermove', mostra); p.addEventListener('pointerleave', tip.esconde);
      p.addEventListener('focus', () => api.zona(k)); p.addEventListener('click', () => api.zona(k));
    }

    const P = D.p;
    const traço = (ids, cor, larg) => el('path', { d: 'M' + ids.map(k => P[k].xy.join(',')).join('L'), class: 'linha desenha', stroke: cor, 'stroke-width': larg }, gm);
    traço(['st_altoboavista', 'st_borbagato', 'st_brooklin', 'st_campobelo'], COR_LINHA[5], 34);
    traço(['st_chucri', 'st_vilacordeiro', 'st_campobelo', 'st_josediniz'], COR_LINHA[17], 34);
    for (const k in P) {
      if (P[k].tipo !== 'st') continue;
      const [x, y] = P[k].xy;
      el('circle', { cx: x, cy: y, r: 38, class: 'est', 'stroke-width': 14 }, gm);
      const t = el('text', { x: x + 64, y: y + 22, class: 'est-txt', 'font-size': 92, 'stroke-width': 26 }, gm);
      t.textContent = P[k].en.replace(' station', '').replace(' stn.', '');
    }
    (D.avenidas || []).forEach(([n, , x, y, ang]) => {
      if (!/Berrini|Santo Amaro|Roberto Marinho|Bandeirantes|Jo[aã]o Dias|Vereador|Chucri|Portugal/.test(n)) return;
      const t = el('text', { x, y, class: 'av-txt', 'font-size': 78, 'stroke-width': 22, transform: `rotate(${ang} ${x} ${y})`, 'text-anchor': 'middle' }, gt);
      t.textContent = n;
    });

    preparaDesenho(svg);
    quadras.forEach(p => { p.style.opacity = 0; });
    quandoVisivel(raiz, () => {
      const ymax = D.H;
      quadras.forEach(p => { p.style.transition = reduz ? 'none' : `opacity .7s ${(+p.dataset.y / ymax * 1.4).toFixed(2)}s, fill .5s`; p.style.opacity = ''; });
    });

    const api = {
      dados: D,
      camada(nome, on) { raiz.classList.toggle('sem-' + nome, !on); },
      zona(k) {
        for (const z in zonas) zonas[z].classList.toggle('ativa', z === k);
        if (opts.aoZona) opts.aoZona(k, D.sub[k], ZONAS[k]);
      },
      legenda: () => ({ quantis: Q, zonas: ZONAS })
    };
    return api;
  }

  /* ------------------------------------------------------------ Zona Sul e Oeste */
  const PAPEL = ZH ? { escritorios: '办公集聚区', residencial: '住宅区', transformacao: '转型中', misto: '混合用途' }
                   : { escritorios: 'Office hub', residencial: 'Residential', transformacao: 'In transformation', misto: 'Mixed use' };
  // Leitura de corretora para cada bairro. Andrea: validar textos e papéis antes de publicar.
  const TXT_ZH = {
    'Pinheiros': 'Largo da Batata 周边聚集写字楼、餐厅和新建住宅楼。地铁 4 号线与 CPTM 9 号线。',
    'Vila Madalena': '低层街道、咖啡馆与工作室林立，深受创意与科技企业青睐。',
    'Itaim Bibi': 'Faria Lima 与 JK 商务轴线：全市租金最高的写字楼市场。',
    'Vila Olímpia': '介于 Faria Lima 与河岸之间，写字楼与小户型公寓集中。',
    'Jardins': 'Oscar Freire 一带成熟的高端住宅与零售区。',
    'Moema': '紧邻 Ibirapuera 公园的家庭公寓区，地铁 5 号线。',
    'Campo Belo': '孔戈尼亚斯机场旁安静的住宅街区，地铁 5 号线与 17 号线。',
    'Berrini': '写字楼集聚区，挂牌租金比 Faria Lima 商务轴线低 67–71%（2026 年第二季度）。',
    'Chácara Santo Antônio': '原工业区，正在转型为写字楼与住宅楼。',
    'Santo Amaro': '历史悠久的商业中心，混合用途街道众多，地铁 5 号线。',
    'Butantã': '圣保罗大学（USP）旁的住宅区，乘地铁 4 号线几分钟即达 Faria Lima。',
    'Vila Leopoldina': '西区的仓储用地正逐步被住宅楼取代。',
    'Brooklin': '两条地铁线沿线的家庭公寓区，紧邻 Berrini 写字楼集聚区。',
    'Morumbi': '大宅与封闭式街道，邻近爱因斯坦医院与体育场。',
    'Cidade Jardim': '赛马俱乐部与河岸之间的高端住宅楼与独栋别墅。'
  };
  const TXT_EN = {
    'Pinheiros': 'Offices, restaurants and new residential towers around Largo da Batata. Lines 4 and 9.',
    'Vila Madalena': 'Low-rise streets, cafés and studios. Popular with creative and tech companies.',
    'Itaim Bibi': 'The Faria Lima and JK axis: the most expensive office market in the city.',
    'Vila Olímpia': 'Offices and compact apartments between Faria Lima and the river.',
    'Jardins': 'Established high-end homes and retail around Oscar Freire.',
    'Moema': 'Family apartments next to Ibirapuera Park. Line 5.',
    'Campo Belo': 'Quiet residential streets by Congonhas Airport. Lines 5 and 17.',
    'Berrini': 'Office hub with asking rents 67–71% below the Faria Lima axis (Q2 2026).',
    'Chácara Santo Antônio': 'Former industrial area becoming offices and residential towers.',
    'Santo Amaro': 'Historic commercial centre with mixed-use streets. Line 5.',
    'Butantã': 'Residential, next to the University of São Paulo. Line 4 to Faria Lima in minutes.',
    'Vila Leopoldina': 'Warehouses giving way to residential towers on the west side.',
    'Brooklin': 'Family apartments on two metro lines, next to the Berrini office hub.',
    'Morumbi': 'Large houses and gated streets; Einstein Hospital and the stadium nearby.',
    'Cidade Jardim': 'High-end towers and houses between the Jockey Club and the river.'
  };
  const TXT = ZH ? TXT_ZH : TXT_EN;
  const SLUG = { 'Brooklin': 'brooklin', 'Morumbi': 'morumbi', 'Cidade Jardim': 'cidadejardim' };
  const COR = { LILAS: '#9B3894', AMARELA: '#E0B000', VERDE: '#007E5E', OURO: '#A6883E' };
  const NOME_LINHA = ZH ? { LILAS: '地铁 5 号线 · 紫线', AMARELA: '地铁 4 号线 · 黄线', VERDE: '地铁 2 号线 · 绿线', OURO: '地铁 17 号线 · 金线' }
                        : { LILAS: 'Line 5 · Lilac', AMARELA: 'Line 4 · Yellow', VERDE: 'Line 2 · Green', OURO: 'Line 17 · Gold' };

  async function sp(raiz, opts = {}) {
    estilo(); raiz.classList.add('llm');
    const S = await carrega('sp-sul-oeste');
    const vb0 = [0, 0, S.W, S.H];
    const svg = el('svg', { viewBox: vb0.join(' '), role: 'img', 'aria-label': ZH ? '圣保罗南区与西区地图：主干道、Pinheiros 河、公园、地铁线路与街区' : 'Map of the South and West zones of São Paulo: avenues, Pinheiros river, parks, metro lines and neighbourhoods' }, raiz);
    const tip = dica(raiz);
    const g = el('g', {}, svg);
    S.parques.forEach(p => { const e = el('path', { d: p.d, class: 'parque' }, g); el('title', {}, e).textContent = (ZH ? '公园 · Parque ' : 'Parque ') + p.nome; });
    const gv = el('g', {}, g);
    S.menores.forEach(d => el('path', { d, class: 'via', 'stroke-width': .7 }, gv));
    S.rio.forEach(d => el('path', { d, class: 'rio', 'stroke-width': 6 }, g));
    for (const n in S.avenidas) S.avenidas[n].forEach(d => {
      const p = el('path', { d, class: 'via grande', 'stroke-width': 1.6 }, g);
      p.addEventListener('pointermove', ev => tip.mostra(`<b>${n}</b>`, ev)); p.addEventListener('pointerleave', tip.esconde);
    });
    const escala = [];
    const rt = el('text', { x: 640, y: 720, class: 'rio-txt', 'font-size': 19, 'stroke-width': 5, transform: 'rotate(58 640 720)' }, g);
    rt.textContent = ZH ? 'Pinheiros 河 · CPTM 9 号线' : 'Pinheiros river · CPTM Line 9';
    escala.push([rt, 640, 720]);

    const gp = el('g', {}, g), perims = {};
    for (const slug in S.perimetros) {
      const dd = S.bairros_dados[slug];
      perims[slug] = S.perimetros[slug].map(d => {
        const p = el('path', { d, class: 'perim' }, gp);
        const nomeB = slug === 'cidadejardim' ? 'Cidade Jardim' : slug[0].toUpperCase() + slug.slice(1);
        const varB = `${dd.kpi.m2_var > 0 ? '+' : ''}${dd.kpi.m2_var}%`;
        p.addEventListener('pointermove', ev => tip.mostra(ZH
          ? `<small>Live &amp; Learn 街区简报 · ITBI</small><b>${nomeB}</b>公寓：R$ ${fmt(dd.kpi.m2)}/平米<small>较前 12 个月 ${varB} · ${fmt(dd.kpi.n)} 笔成交</small>`
          : `<small>Live &amp; Learn bulletin · ITBI</small><b>${nomeB}</b>Apartments: R$ ${fmt(dd.kpi.m2)}/m²<small>${varB} vs previous 12 months · ${fmt(dd.kpi.n)} sales</small>`, ev));
        p.addEventListener('pointerleave', tip.esconde);
        p.addEventListener('click', () => window.open(dd.url, '_blank', 'noopener'));
        return p;
      });
    }

    const gm = el('g', { class: 'metro' }, g);
    for (const k in S.linhas) {
      const L = S.linhas[k];
      el('path', { d: L.d, class: 'linha desenha ns', stroke: COR[k], 'stroke-width': 3.5 }, gm);
    }
    escala.push([el('circle', { cx: S.berrini_cptm[0], cy: S.berrini_cptm[1], r: 5, class: 'est', 'stroke-width': 2.2, stroke: '#01A9A7' }, gm), S.berrini_cptm[0], S.berrini_cptm[1]]);
    for (const k in S.linhas) S.linhas[k].est.forEach(e => {
      const c = el('circle', { cx: e.xy[0], cy: e.xy[1], r: 6, class: 'est', 'stroke-width': 2.6 }, gm);
      escala.push([c, e.xy[0], e.xy[1]]);
      c.addEventListener('pointermove', ev => tip.mostra(`<small>${NOME_LINHA[k]}</small><b>${e.n}</b>`, ev)); c.addEventListener('pointerleave', tip.esconde);
    });

    const gb = el('g', {}, svg), pinos = {};
    S.rotulos.forEach(r => {
      const [x, y] = r.xy;
      const pg = el('g', { class: 'pino ' + r.papel, tabindex: 0, role: 'button', 'aria-label': r.n + ', ' + PAPEL[r.papel] }, gb);
      el('circle', { cx: x, cy: y, r: 10 }, pg);
      const t = el('text', { x: x + 16, y: y + 7, class: 'bairro-txt', 'font-size': 21 }, pg); t.textContent = r.n;
      const info = ev => {
        const slug = SLUG[r.n], dd = slug && S.bairros_dados[slug];
        const dado = !dd ? '' : ZH ? `公寓：<b>R$ ${fmt(dd.kpi.m2)}/平米</b><small>成交价中位数，ITBI，截至 2026 年 9 月的 12 个月</small>`
                                   : `Apartments: <b>R$ ${fmt(dd.kpi.m2)}/m²</b><small>median of recorded sales, ITBI, 12 months to Sep 2026</small>`;
        tip.mostra(`<small>${PAPEL[r.papel]}</small><b>${r.n}</b>${dado}<small>${TXT[r.n] || ''}</small>`, ev);
      };
      pg.addEventListener('pointermove', info); pg.addEventListener('pointerleave', tip.esconde);
      pg.addEventListener('focus', ev => { const b = pg.getBoundingClientRect(); info({ clientX: b.right, clientY: b.bottom }); });
      pg.addEventListener('blur', tip.esconde);
      pg.addEventListener('click', () => { if (opts.aoBairro) opts.aoBairro(r.n, r, S); });
      pinos[r.n] = pg; escala.push([pg, x, y]);
    });

    preparaDesenho(svg);
    quandoVisivel(raiz, () => {});

    // zoom suave por viewBox; pinos, estações e rótulos mantêm o tamanho na tela
    let vb = vb0.slice(), anim = null;
    const reescala = () => { const k = vb[2] / S.W; escala.forEach(([e, x, y]) => e.setAttribute('transform', `translate(${x} ${y}) scale(${k}) translate(${-x} ${-y})` + (e === rt ? ' rotate(58 640 720)' : ''))); };
    function vai(alvo, dur = 1100) {
      cancelAnimationFrame(anim);
      if (reduz) { vb = alvo; svg.setAttribute('viewBox', vb.join(' ')); reescala(); return; }
      const de = vb.slice(), t0 = performance.now();
      const passo = t => {
        const k = Math.min(1, (t - t0) / dur), e = k < .5 ? 4 * k * k * k : 1 - Math.pow(-2 * k + 2, 3) / 2;
        vb = de.map((v, i) => v + (alvo[i] - v) * e); svg.setAttribute('viewBox', vb.join(' ')); reescala();
        if (k < 1) anim = requestAnimationFrame(passo);
      };
      anim = requestAnimationFrame(passo);
    }
    const proporcao = S.W / S.H;
    return {
      dados: S,
      camada(nome, on) { raiz.classList.toggle('sem-' + nome, !on); },
      tudo() { vai(vb0); },
      foco(nome, raio = 170) {
        const r = S.rotulos.find(x => x.n === nome); if (!r) return;
        const h = raio * 2, w = h * proporcao;
        vai([r.xy[0] - w / 2, r.xy[1] - h / 2, w, h]);
        for (const k in pinos) pinos[k].classList.toggle('ativa', k === nome);
      },
      perimetro(slug, on = true) { (perims[slug] || []).forEach(p => p.classList.toggle('ativa', on)); },
      PAPEL, TXT
    };
  }

  window.LLMapas = { brooklin, sp };
})();
