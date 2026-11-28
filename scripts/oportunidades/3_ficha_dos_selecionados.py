import re, requests, json, pandas as pd, concurrent.futures as cf
from bs4 import BeautifulSoup
sel=[]
for res,tsv in [('resultado_mmj.csv','/home/claude/data/anuncios_mmj.tsv')]:
    o=pd.read_csv(res,dtype={'codigo':str,'numero':str}); t=pd.read_csv(tsv,sep='\t',dtype=str).fillna('')
    a=o[(o.status=='avaliado')&(o.razao<=1)&o.confianca.isin(['alta','media'])].merge(t[['codigo','slug','imagens']],on='codigo')
    sel.append(a)
sel=pd.concat(sel).drop_duplicates('codigo')
def num(s): return float(s.replace('.','').replace(',','.'))
def get(r):
    h=requests.get("https://www.coelhodafonseca.com.br"+r.slug,timeout=60).text
    tx=BeautifulSoup(h,"html.parser").get_text("\n")
    i=tx.find('Ver filtros'); j=tx.find('Agendar visita',i); head=re.sub(r'\s*\n\s*','\n',tx[i:j])
    f={}
    for lab,k in [('Área útil','area_util'),('Área construída','area_construida'),('Área do terreno','area_terreno'),('Área total','area_total'),('Dorms','dorm'),('Suítes','suites'),('Vagas','vagas')]:
        m=re.search(r'\n([\d.,]+)\n'+lab+r'\n',head)
        if m: f[k]=num(m.group(1))
    m=re.search(r'Venda\nR\$ ([\d.]+)',head); f['preco']=num(m.group(1)) if m else None
    m=re.search(r'Condomínio:?\s*\nR\$\n?\s*([\d.,]+)',head); f['condominio']=num(m.group(1)) if m else None
    m=re.search(r'IPTU:?\s*\nR\$\n?\s*([\d.,]+)',head); f['iptu']=num(m.group(1)) if m else None
    raw=h.replace('\\\\"','"'); m=re.search(r'banheiro\(s\),\s*([^.<\\"]+)',raw); f['caracs']=[x.strip(' .') for x in m.group(1).split(',')] if m else []
    m=re.search(r'(\d+)\s*banheiro',tx); f['banheiros']=int(m.group(1)) if m else None
    f['codigo']=r.codigo; return f
with cf.ThreadPoolExecutor(8) as ex: fichas=list(ex.map(get,[r for _,r in sel.iterrows()]))
json.dump(fichas,open('fichas.json','w'),ensure_ascii=False,indent=0)
sel.to_csv('selecionados.csv',index=False)
import collections
print(len(fichas)); c=collections.Counter(x for f in fichas for x in f['caracs']); print(c.most_common(60))
print(sum(1 for f in fichas if f.get('preco')), sum(1 for f in fichas if f.get('condominio')), fichas[:2])
