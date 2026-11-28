import pandas as pd, glob, json, sys, concurrent.futures as cf
sys.path.insert(0,'.'); from fotos_vitrine2 import processar
REPO='/home/claude/live-and-learn-brazil/realestate/oportunidades'
m=pd.read_csv('mapa_novos.csv',dtype=str)
def um(r):
    rf=r['Ref. vitrine']; u=processar(r['Código Coelho'],sorted(glob.glob(f"fotos_mmj/{r['Código Coelho']}/*.jpg")),f"{REPO}/fotos/{rf.lower()}")
    return rf,u
with cf.ProcessPoolExecutor(4) as ex: res=dict(ex.map(um,[r for _,r in m.iterrows()]))
m['Fotos usadas (ordem)']=[', '.join(f for f,_ in res[r]) for r in m['Ref. vitrine']]
m['capa_lc']=[res[r][0][1] for r in m['Ref. vitrine']]
m.to_csv('mapa_novos.csv',index=False)
d=json.load(open(f'{REPO}/dados/oportunidades.json'))
for i in d['imoveis']:
    if i['ref'] in res: i['fotos']=[f"fotos/{i['ref'].lower()}/{k}.webp" for k in range(1,len(res[i['ref']])+1)]
json.dump(d,open(f'{REPO}/dados/oportunidades.json','w'),ensure_ascii=False,separators=(',',':'))
print(m.capa_lc.describe(), (m.capa_lc>=0.25).sum(),'capas ainda acima de 0,25')
