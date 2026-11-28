import sys, pandas as pd, os, zipfile, shutil
reg, res, tsv, pasta = sys.argv[1:5]
o=pd.read_csv(res,dtype={'codigo':str,'numero':str})
t=pd.read_csv(tsv,sep='\t',dtype=str).fillna('')
img=dict(zip(t.codigo,t.imagens))
o['fotos_links']=o.codigo.map(lambda c:' '.join(f"https://static.coelhodafonseca.com.br/images/imoveis/original/{i}.jpg" for i in img.get(c,'').split(',') if i))
o['endereco']=o.rua+', '+o.numero.astype(str)
dup=o.groupby('endereco').codigo.transform('count')
o['obs']=['mesmo endereço de outro anúncio da lista' if d>1 else '' for d in dup]
o['grupo']=''
sel=(o.status=='avaliado')&(o.razao<=1)
o.loc[sel&o.confianca.isin(['alta','media']),'grupo']='A - bem precificado (confiança alta/média)'
o.loc[sel&(o.confianca=='baixa'),'grupo']='B - abaixo da mediana, confiança baixa (conferir)'
cols={'grupo':'Grupo','codigo':'Código','url':'Link do anúncio','categoria':'Tipo','endereco':'Endereço','cep':'CEP','distrito':'Distrito',
 'area':'Área do anúncio (m²)','terreno':'Terreno (m²)','preco':'Preço pedido (R$)','m2_anuncio_priv':'R$/m² do anúncio',
 'area_regua':'Área IPTU usada (m²)','metodo_area':'Como a área IPTU foi obtida','valor_mediana':'Valor na mediana (R$)',
 'faixa_min':'Faixa mín. (R$)','faixa_max':'Faixa máx. (R$)','razao':'Preço ÷ mediana','camada':'Camada dos comparáveis',
 'confianca':'Confiança','n_comp':'Nº de comparáveis','localizacao':'Localização no ITBI','n_fotos':'Nº de fotos','obs':'Observação',
 'status':'Situação','fotos_links':'Links das fotos (originais)'}
def fmt(d): return d[[c for c in cols if c in d.columns]].rename(columns=cols)
s=o[sel].sort_values(['grupo','razao'])
todos=o[o.status=='avaliado'].sort_values('razao')
fora=o[o.status!='avaliado']
como=pd.DataFrame({'Como ler':[
 f'Base: {len(o)} anúncios residenciais à venda da Coelho da Fonseca em {reg}, lidos em 27/11/2026.',
 'Cada anúncio passou pelo motor de avaliação do Assistente do Corretor (ITBI 2025–2026, vendas até 07/09/2026), na mesma lógica do app: localização pelo endereço (ou pelo número vizinho da mesma rua), área do IPTU estimada a partir da área do anúncio (método A4 do app: unidades do prédio entre 1 e 2 vezes a área privativa; casas usam a própria área), camadas edifício → quadra → raios → distrito.',
 '"Preço ÷ mediana": preço pedido dividido pelo valor na mediana dos comparáveis, corrigido até hoje. 1,00 = na mediana; abaixo de 1,00 = abaixo da mediana.',
 'Grupo A: na mediana ou abaixo, com confiança alta ou média (comparáveis no prédio, na quadra ou em até 600 m). São os indicados para a vitrine.',
 'Grupo B: abaixo da mediana, mas com confiança baixa (comparáveis a mais de 1 km ou só no distrito). Conferir antes de usar: o preço baixo pode ser só falta de comparável bom.',
 'Atenção: anúncios costumam pedir 15–20% acima do preço de venda e o ITBI tende a registrar valores declarados; por isso a maioria dos anúncios fica acima de 1,00. Sem checklist (conservação, vista, andar), o resultado é uma triagem, não uma avaliação.',
 'Mesmo endereço em mais de um código costuma ser a mesma unidade anunciada duas vezes ou unidades iguais no prédio: veja a coluna Observação.']})
os.makedirs(pasta,exist_ok=True)
x=f'{pasta}/triagem_{reg.lower().replace(" ","_").replace("+","_")}.xlsx'
with pd.ExcelWriter(x,engine='openpyxl') as w:
    como.to_excel(w,'Como ler',index=False); fmt(s).to_excel(w,'Selecionados',index=False)
    fmt(todos).to_excel(w,'Todos avaliados',index=False); fmt(fora).to_excel(w,'Não avaliados',index=False)
    for ws in w.book.worksheets:
        for col in ws.columns:
            ws.column_dimensions[col[0].column_letter].width=min(60,max(10,max(len(str(c.value or ''))for c in col[:50])+2))
        ws.freeze_panes='A2'
print(x, len(s), (s.grupo.str[0]=='A').sum())
