# Rodada 28/11/2026: acrescenta os novos selecionados ao JSON existente, sem renumerar referências
import pandas as pd, json, glob, os, sys, requests, concurrent.futures as cf
sys.path.insert(0,'.'); from fotos_vitrine2 import processar
REPO='/home/claude/live-and-learn-brazil/realestate/oportunidades'
REF_XLSX='/mnt/user-data/uploads/Downloads/referencias_oportunidades.xlsx'
sel=pd.read_csv('selecionados.csv',dtype={'codigo':str,'numero':str})
fichas={f['codigo']:f for f in json.load(open('fichas.json'))}
ref=pd.read_excel(REF_XLSX,dtype={'Código Coelho':str})
sel=sel[~sel.codigo.isin(ref['Código Coelho'])]
sel['bairro']=sel.slug.str.split('/').str[1]
sel['area_f']=[fichas[c].get('area_util') or fichas[c].get('area_construida') or a for c,a in zip(sel.codigo,sel.area)]
sel['preco_f']=[fichas[c].get('preco') or p for c,p in zip(sel.codigo,sel.preco)]
antes=len(sel)
dup=sel.sort_values('preco_f').duplicated(['rua','numero','area_f'],keep='first')
dups=sel.sort_values('preco_f')[dup]; dups.to_csv('duplicatas_vnc.csv',index=False)
sel=sel.sort_values('preco_f').drop_duplicates(['rua','numero','area_f']).sort_values('razao')
print('selecionados',antes,'duplicatas',antes-len(sel),'novos',len(sel)); print(sel.bairro.value_counts())
if '--so-contar' in sys.argv: sys.exit()
# fotos originais
def baixar(cod, ids):
    d=f'fotos_vnc/{cod}'; os.makedirs(d,exist_ok=True)
    for k,i in enumerate(ids,1):
        fn=f'{d}/{cod}_{k:02d}.jpg'
        if os.path.exists(fn): continue
        for _ in range(3):
            try:
                r=requests.get(f'https://static.coelhodafonseca.com.br/images/imoveis/original/{i}.jpg',timeout=60)
                if r.ok: open(fn,'wb').write(r.content); break
            except Exception: pass
with cf.ThreadPoolExecutor(8) as ex: list(ex.map(lambda r: baixar(r.codigo,[x for x in r.imagens.split(',') if x]), [r for _,r in sel.iterrows()]))
PREF={'brooklin':'BK','itaim-bibi':'IT','cidade-jardim':'CJ','moema':'MO','morumbi':'MR','jardim-guedala':'JG','vila-nova-conceicao':'VN'}
TIPO={'apartamento':'apartamento','cobertura':'cobertura','garden':'garden','studio':'studio','casa':'casa','casa-em-condominio':'casa_condominio','casa-de-vila':'casa_vila','casa-em-rua-fechada':'casa_rua_fechada'}
CAR={'ar condicionado':'ar','dep':'dependencia','terraço':'terraco','depósito':'deposito','escritório':'escritorio','churrasqueira':'churrasqueira','reformado (a)':'reformado','piscina':'piscina','mobiliado (a)':'mobiliado','lavanderia':'lavanderia','varanda gourmet':'varanda_gourmet','duplex':'duplex','gerador':'gerador','copa':'copa','rua sem saída':'rua_sem_saida','esquina':'esquina','vagas para visitantes':'vagas_visitantes'}
dados=json.load(open(f'{REPO}/dados/oportunidades.json'))
exist={i['ref'] for i in dados['imoveis']}
cont={p:max([int(r.split('-')[1]) for r in exist if r.startswith(p+'-')],default=0) for p in PREF.values()}
novos=[]; mapa=[]
for _,r in sel.iterrows():
    p=PREF[r.bairro]; cont[p]+=1; rf=f'{p}-{cont[p]:02d}'; f=fichas[r.codigo]
    paths=sorted(glob.glob(f'fotos_vnc/{r.codigo}/*.jpg'))
    usadas=processar(r.codigo,paths,f"{REPO}/fotos/{rf.lower()}")
    casa=r.categoria.startswith('casa')
    novos.append({'ref':rf,'bairro':r.bairro,'tipo':TIPO.get(r.categoria,r.categoria),
        'area':round(r.area_f),'area_tipo':'construida' if casa else 'util',
        'terreno':round(f['area_terreno']) if casa and f.get('area_terreno') else None,
        'dorm':int(f['dorm']) if f.get('dorm') else None,'suites':int(f['suites']) if f.get('suites') else None,
        'vagas':int(f['vagas']) if f.get('vagas') else None,'banheiros':f.get('banheiros'),
        'preco':int(r.preco_f),'condominio':int(f['condominio']) if f.get('condominio') else None,
        'caracs':list(dict.fromkeys(CAR[c] for c in f['caracs'] if c in CAR)),
        'fotos':[f"fotos/{rf.lower()}/{k}.webp" for k in range(1,len(usadas)+1)],
        'imobiliaria':'Coelho da Fonseca','avaliacao':'motor','_razao':r.razao})
    mapa.append({'Ref. vitrine':rf,'Código Coelho':r.codigo,'Link Coelho':r.url,'Endereço':f"{r.rua}, {r.numero}",'Bairro':r.bairro,
                 'Fotos usadas (ordem)':', '.join(u for u,_ in usadas),'Preço ÷ mediana':r.razao,'Confiança':r.confianca})
# ordem global por preço ÷ mediana (as referências não mudam)
raz=dict(zip(ref['Ref. vitrine'],ref['Preço ÷ mediana']))
for i in dados['imoveis']: i['_razao']=raz.get(i['ref'],9)
todos=dados['imoveis']+novos; todos.sort(key=lambda i:(i['_razao'],i['ref']))
for k,i in enumerate(todos,1): i['ordem']=k; i.pop('_razao')
dados['imoveis']=todos; dados['atualizado']='2026-11-28'
json.dump(dados,open(f'{REPO}/dados/oportunidades.json','w'),ensure_ascii=False,separators=(',',':'))
os.makedirs('/mnt/user-data/outputs',exist_ok=True)
pd.concat([ref,pd.DataFrame(mapa)]).to_excel('/mnt/user-data/outputs/referencias_oportunidades.xlsx',index=False)
pd.DataFrame(mapa).to_csv('mapa_vnc.csv',index=False)
print(len(novos),'novos;',len(todos),'no total', cont)
