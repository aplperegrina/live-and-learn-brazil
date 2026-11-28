import pandas as pd, json, glob, os, sys
sys.path.insert(0,'.'); from fotos_vitrine import processar
REPO='/home/claude/live-and-learn-brazil/realestate/oportunidades'
sel=pd.read_csv('selecionados.csv',dtype={'codigo':str,'numero':str})
fichas={f['codigo']:f for f in json.load(open('fichas.json'))}
sel['bairro']=sel.slug.str.split('/').str[1]
sel['area_f']=[fichas[c].get('area_util') or fichas[c].get('area_construida') or a for c,a in zip(sel.codigo,sel.area)]
sel['preco_f']=[fichas[c].get('preco') or p for c,p in zip(sel.codigo,sel.preco)]
# duplicatas: mesmo endereço e mesma área = mesma unidade; fica o menor preço
sel=sel.sort_values('preco_f').drop_duplicates(['rua','numero','area_f']).sort_values('razao')
PREF={'brooklin':'BK','itaim-bibi':'IT','cidade-jardim':'CJ'}
TIPO={'apartamento':'apartamento','cobertura':'cobertura','garden':'garden','studio':'studio','casa':'casa','casa-em-condominio':'casa_condominio','casa-de-vila':'casa_vila','casa-em-rua-fechada':'casa_rua_fechada'}
CAR={'ar condicionado':'ar','dep':'dependencia','terraço':'terraco','depósito':'deposito','escritório':'escritorio','churrasqueira':'churrasqueira','reformado (a)':'reformado','piscina':'piscina','mobiliado (a)':'mobiliado','lavanderia':'lavanderia','varanda gourmet':'varanda_gourmet','duplex':'duplex','gerador':'gerador','copa':'copa','rua sem saída':'rua_sem_saida','esquina':'esquina','vagas para visitantes':'vagas_visitantes'}
cont={k:0 for k in PREF}; itens=[]; mapa=[]
for ordem,(_,r) in enumerate(sel.iterrows(),1):
    cont[r.bairro]+=1; ref=f"{PREF[r.bairro]}-{cont[r.bairro]:02d}"
    f=fichas[r.codigo]
    src=f"vitrine_brooklin/fotos/{r.codigo}" if os.path.isdir(f"vitrine_brooklin/fotos/{r.codigo}") else f"fotos_cj_itaim/{r.codigo}"
    paths=sorted(glob.glob(src+'/*.jpg'))
    usadas=processar(r.codigo,paths,f"{REPO}/fotos/{ref.lower()}")
    casa=r.categoria.startswith('casa')
    itens.append({'ref':ref,'bairro':r.bairro,'tipo':TIPO.get(r.categoria,r.categoria),
        'area':round(r.area_f),'area_tipo':'construida' if casa else 'util',
        'terreno':round(f['area_terreno']) if casa and f.get('area_terreno') else None,
        'dorm':int(f['dorm']) if f.get('dorm') else None,'suites':int(f['suites']) if f.get('suites') else None,
        'vagas':int(f['vagas']) if f.get('vagas') else None,'banheiros':f.get('banheiros'),
        'preco':int(r.preco_f),'condominio':int(f['condominio']) if f.get('condominio') else None,
        'caracs':list(dict.fromkeys(CAR[c] for c in f['caracs'] if c in CAR)),
        'fotos':[f"fotos/{ref.lower()}/{k}.webp" for k in range(1,len(usadas)+1)],
        'imobiliaria':'Coelho da Fonseca','avaliacao':'motor','ordem':ordem})
    mapa.append({'Ref. vitrine':ref,'Código Coelho':r.codigo,'Link Coelho':r.url,'Endereço':f"{r.rua}, {r.numero}",'Bairro':r.bairro,
                 'Fotos usadas (ordem)':', '.join(u for u,_ in usadas),'Preço ÷ mediana':r.razao,'Confiança':r.confianca})
dados={'atualizado':'2026-11-27','imoveis':itens}
json.dump(dados,open(f"{REPO}/dados/oportunidades.json",'w'),ensure_ascii=False,separators=(',',':'))
pd.DataFrame(mapa).to_excel('/mnt/user-data/outputs/referencias_oportunidades.xlsx',index=False)
print(len(itens), cont)
