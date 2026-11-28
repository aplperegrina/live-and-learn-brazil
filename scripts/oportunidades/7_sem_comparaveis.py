# Coberturas que o motor não avalia (metragem sem vendas comparáveis no ITBI), incluídas por decisão da Andrea (28/11/2026).
# Entram com "avaliacao": "sem_comparaveis" (indicação no cartão) e no fim da ordem.
import pandas as pd, json, glob, os, sys, requests, re, concurrent.futures as cf
from bs4 import BeautifulSoup
sys.path.insert(0,'.'); from fotos_vitrine2 import processar
REPO='/home/claude/live-and-learn-brazil/realestate/oportunidades'
o=pd.read_csv('resultado_vnc.csv',dtype={'codigo':str,'numero':str}); t=pd.read_csv('/home/claude/data/anuncios_vnc.tsv',sep='\t',dtype=str).fillna('')
c=o[(o.status=='sem área estimável')&(o.categoria=='cobertura')].merge(t[['codigo','slug','imagens']],on='codigo')
num=lambda s: float(s.replace('.','').replace(',','.'))
def ficha(r):
    h=requests.get("https://www.coelhodafonseca.com.br"+r.slug,timeout=60).text
    tx=BeautifulSoup(h,"html.parser").get_text("\n"); i=tx.find('Ver filtros'); j=tx.find('Agendar visita',i); head=re.sub(r'\s*\n\s*','\n',tx[i:j]); f={}
    for lab,k in [('Área útil','area_util'),('Área construída','area_construida'),('Dorms','dorm'),('Suítes','suites'),('Vagas','vagas')]:
        m=re.search(r'\n([\d.,]+)\n'+lab+r'\n',head)
        if m: f[k]=num(m.group(1))
    m=re.search(r'Venda\nR\$ ([\d.]+)',head); f['preco']=num(m.group(1)) if m else r.preco
    m=re.search(r'Condomínio:?\s*\nR\$\n?\s*([\d.,]+)',head); f['condominio']=num(m.group(1)) if m else None
    raw=h.replace('\\\\"','"'); m=re.search(r'banheiro\(s\),\s*([^.<\\"]+)',raw); f['caracs']=[x.strip(' .') for x in m.group(1).split(',')] if m else []
    return f
with cf.ThreadPoolExecutor(6) as ex: F=list(ex.map(ficha,[r for _,r in c.iterrows()]))
c['f']=F; c['area_f']=[f.get('area_util') or a for f,a in zip(F,c.area)]; c['preco_f']=[f['preco'] for f in F]
c=c.sort_values('preco_f').drop_duplicates(['rua','numero','area_f']).sort_values('preco_f',ascending=False)
print(len(c),'coberturas')
def baixar(cod, ids):
    d=f'fotos_vnc/{cod}'; os.makedirs(d,exist_ok=True)
    for k,i in enumerate(ids,1):
        fn=f'{d}/{cod}_{k:02d}.jpg'
        if os.path.exists(fn): continue
        r=requests.get(f'https://static.coelhodafonseca.com.br/images/imoveis/original/{i}.jpg',timeout=60)
        if r.ok: open(fn,'wb').write(r.content)
with cf.ThreadPoolExecutor(8) as ex: list(ex.map(lambda r: baixar(r.codigo,[x for x in r.imagens.split(',') if x]),[r for _,r in c.iterrows()]))
CAR={'ar condicionado':'ar','dep':'dependencia','terraço':'terraco','depósito':'deposito','escritório':'escritorio','churrasqueira':'churrasqueira','reformado (a)':'reformado','piscina':'piscina','mobiliado (a)':'mobiliado','lavanderia':'lavanderia','varanda gourmet':'varanda_gourmet','duplex':'duplex','gerador':'gerador','copa':'copa','rua sem saída':'rua_sem_saida','esquina':'esquina','vagas para visitantes':'vagas_visitantes'}
dados=json.load(open(f'{REPO}/dados/oportunidades.json'))
n=max(int(i['ref'].split('-')[1]) for i in dados['imoveis'] if i['ref'].startswith('VN-')); ordem=max(i['ordem'] for i in dados['imoveis'])
mapa=[]
for _,r in c.iterrows():
    n+=1; ordem+=1; rf=f'VN-{n:02d}'; f=r.f
    u=processar(r.codigo,sorted(glob.glob(f'fotos_vnc/{r.codigo}/*.jpg')),f'{REPO}/fotos/{rf.lower()}')
    dados['imoveis'].append({'ref':rf,'bairro':'vila-nova-conceicao','tipo':'cobertura','area':round(r.area_f),'area_tipo':'util','terreno':None,
        'dorm':int(f['dorm']) if f.get('dorm') else None,'suites':int(f['suites']) if f.get('suites') else None,'vagas':int(f['vagas']) if f.get('vagas') else None,
        'banheiros':None,'preco':int(r.preco_f),'condominio':int(f['condominio']) if f.get('condominio') else None,
        'caracs':list(dict.fromkeys(CAR[x] for x in f['caracs'] if x in CAR)),'fotos':[f'fotos/{rf.lower()}/{k}.webp' for k in range(1,len(u)+1)],
        'imobiliaria':'Coelho da Fonseca','avaliacao':'sem_comparaveis','ordem':ordem})
    mapa.append({'Ref. vitrine':rf,'Código Coelho':r.codigo,'Link Coelho':r.url,'Endereço':f'{r.rua}, {r.numero}','Bairro':'vila-nova-conceicao',
        'Fotos usadas (ordem)':', '.join(x for x,_ in u),'Preço ÷ mediana':None,'Confiança':'sem comparáveis (incluída por decisão da Andrea)'})
json.dump(dados,open(f'{REPO}/dados/oportunidades.json','w'),ensure_ascii=False,separators=(',',':'))
pd.DataFrame(mapa).to_csv('mapa_vnc_cob.csv',index=False); print([m['Ref. vitrine'] for m in mapa], len(dados['imoveis']))
