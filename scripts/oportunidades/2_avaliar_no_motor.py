import re, sys, json, statistics, warnings
warnings.filterwarnings("ignore")
import pandas as pd
sys.path.insert(0, "/home/claude/meuestrategistadevendas/assistente-corretor/app")
from corretor.web.servico import Servico
from corretor.dados.localizador import normalizar_logradouro, normalizar_numero
import glob
_g=pd.concat([pd.read_csv(f,dtype={"sql":str},usecols=["sql","logradouro","numero"]) for f in glob.glob("/home/claude/meuestrategistadevendas/assistente-corretor/dados/itbi/itbi_*.csv.gz")])
_g=_g.dropna(); _g["ln"]=_g.logradouro.map(normalizar_logradouro); _g["numero"]=pd.to_numeric(_g.numero,errors="coerce")
_G={k:v for k,v in _g.groupby("ln")}
STOP={"DE","DA","DO","DAS","DOS","E","R","AV","AL","PC","TV","DR","PROF","PRODR","COM","COMEN","CON","CONEGO","SGT","PDE","GAL","DONA","S","ENG","CEL","MIN","EMB","JR","JUNIOR"}
def core(n): return frozenset(t.strip(".") for t in n.split() if t.strip(".") not in STOP and len(t.strip("."))>1)
_NOMES=None
def casar_nome(s, rua):
    global _NOMES
    if _NOMES is None:
        _NOMES=[(n,core(n)) for n in set(s.localizador.nomes)|set(_G)]
    c=core(normalizar_logradouro(rua))
    if not c: return None
    hits=[n for n,k in _NOMES if k and (k==c or ((k<=c or c<=k) and len(k&c)>=2 and all(len(t)<=2 for t in (k^c))))]
    hits.sort(key=lambda n:(n not in s.localizador.por_logradouro, -len(core(n)&c)))
    return hits[0] if hits else None
def loc_itbi_amplo(s, rua, numero):
    g=_G.get(normalizar_logradouro(rua)); n=normalizar_numero(numero)
    if g is None or n is None: return None
    g=g.assign(d=(g.numero-n).abs()).sort_values("d")
    for sql in g.sql.head(5):
        l=s.localizador.por_sql(sql)
        if l is not None:
            d=l.como_dict(); d.pop("unidades",None); d.update({"fonte":"vizinho","logradouro":normalizar_logradouro(rua),"numero":n,"numero_vizinho":int(g.numero.iloc[0])}); return d
    return None

ALIAS={"Rua Reverendo Miguel Rizzo Júnior":"R REV MIGUEL RIZZO JUNIOR","Rua Marechal Hastimphilo de Moura":"R MAL HASTINFILO DE MOURA",
"Avenida Doutor Guilherme Dumont Villares":"AV DR GUILHERME DUMONT VILARES","Rua Joaquim Cândido de Azevedo Marques":"R JOAQUIM C DE AZEVEDO MQS",
"Rua Desembargador Amorim Lima":"R DESEM AMORIM LIMA","Rua Manuel Carlos de Figueiredo Ferraz":"R DR MANUEL CARLOS DE F FERRAZ",
"Rua Professor José Horácio Meirelles Teixeira":"R PROF J H MEIRELLES TEIXEIRA","Rua Comissário Gastão Moutinho":"R COMIS GASTAO MOUTINHO",
"Rua Comandante Garcia DAvila":"R COMTE GARCIA D AVILA","Coronel Raul Humaitá":"R CEL RAUL HUMAITA VILA NOVA","Avenida Rubem Berta - lado par":"AV RUBEM BERTA",
"Rua Luigi Pirandello":"R PIRANDELLO","Rua Desembargador Dalmo do Valle Nogueira":"R DESEM DALMO DO VALLE NOGUEIRA","Rua Comandante Lira":"R COMTE LIRA",
"Avenida Quarto Centenário":"AV IV CENTENARIO","Praça Jacintho Moreira Cabral":"PC MOREIRA CABRAL","Rua Desembargador Aguiar Valim":"R DESEM AGUIAR VALIM"}
s = Servico()
df = pd.read_csv(sys.argv[1], sep="\t", dtype=str).fillna("")
TIPO = {"apartamento": "apartamento", "cobertura": "apartamento", "garden": "apartamento", "studio": "apartamento",
        "duplex": "apartamento", "casa": "casa", "casa-em-condominio": "casa", "casa-de-vila": "casa", "casa-em-rua-fechada": "casa"}
num = lambda t: float(t.replace(".", "").replace(",", ".")) if t else None
out = []
for _, r in df.iterrows():
    slug = r["slug"]
    m = re.match(r"/[^/]+/(.+?)-(\d+-dormitorios|\d+-suites|\d+-vagas|\d+-metros|\d)", slug)
    cat = m.group(1) if m else slug.split("/")[-1].split("-")[0]
    area = re.search(r"-(\d+)-metros", slug)
    area = float(area.group(1)) if area else None
    preco = re.search(r"R\$\s*([\d.]+)", r["card"])
    preco = num(preco.group(1)) if preco else None
    rua = re.split(r"\s*-\s*(?:de|até|ate)\b", r["rua"])[0].strip()
    rua_anuncio = rua; rua = ALIAS.get(r["rua"].strip(), ALIAS.get(rua, rua))
    rec = {"codigo": r["codigo"], "categoria": cat, "rua": rua_anuncio, "numero": r["numero"], "cep": r["cep"], "area": area,
           "terreno": num(r["terreno"]), "preco": preco, "url": "https://www.coelhodafonseca.com.br" + slug,
           "n_fotos": len([x for x in r["imagens"].split(",") if x])}
    tipo = TIPO.get(cat)
    if not tipo or not area or not preco:
        rec["status"] = "fora (tipo/área/preço)"; out.append(rec); continue
    av = {"tipo": tipo, "endereco": {"logradouro": rua, "numero": r["numero"]}, "area_privativa": area, "respostas": {}}
    loc = s.localizar(rua, r["numero"])
    if loc is None:
        cands = [rua, "Rua " + rua, "Avenida " + rua]
        for c in cands:
            loc = s.localizar(c, r["numero"])
            if loc: rua = c; break
    if loc is None:
        par = s.logradouros_parecidos(rua) or s.logradouros_parecidos("Rua " + rua)
        OK = {"R PRSA ISABEL", "R CD DE PORTO ALEGRE", "R PRODR JOSE MQS DA CRUZ"}
        par = [p for p in par if p in OK]
        if par:
            loc = s.localizar(par[0], r["numero"]); rec["rua_itbi"] = par[0]
            if loc: rua = par[0]
    if loc is None:
        m = casar_nome(s, rua)
        if m:
            loc = s.localizar(m, r["numero"]) or loc_itbi_amplo(s, m, r["numero"])
            if loc: rec["rua_itbi"] = m; rua = m
    if loc is None:
        for c in [rua, "Rua "+rua]:
            loc = loc_itbi_amplo(s, c, r["numero"])
            if loc: break
    av["endereco"]["logradouro"] = rua
    if loc is None:
        rec["status"] = "endereço não localizado"; out.append(rec); continue
    av["localizacao"] = loc
    ano = None
    if loc.get("fonte") == "endereco":
        b = s.localizador.buscar(rua, r["numero"])
        anos = [u.ano_construcao for u in b.exatos[0].unidades if u.tipo_imovel == tipo and u.ano_construcao > 1800] if b.exatos else []
        ano = statistics.median(anos) if anos else None
    av["iptu"] = {"ano_construcao": ano, "area_terreno": rec["terreno"] if tipo == "casa" else None}
    if cat == "cobertura":
        av["respostas"]["cobertura"] = "sim"
    est = s.estimar_area_regua(av)
    if not est:
        rec["status"] = "sem área estimável"; out.append(rec); continue
    av["iptu"].update({"area_construida": est["area"], "fonte": "matrícula", "estimada_pelo_app": True, "estimativa": est})
    try:
        res = s.avaliar(av)
    except Exception as e:
        rec["status"] = f"erro {e}"; out.append(rec); continue
    if "erro" in res:
        rec["status"] = "sem comparáveis"; out.append(rec); continue
    mo = res["motor"]
    val = mo["valor_estimado"]
    rec.update({"status": "avaliado", "tipo": tipo, "area_regua": round(est["area"], 1), "metodo_area": est["metodo"],
                "localizacao": loc.get("fonte"), "camada": mo["camada"], "confianca": mo["confianca"], "n_comp": mo["n"],
                "mediana_m2_regua": round(mo["mediana_m2"]), "valor_mediana": round(val), "faixa_min": round(mo["valor_min"]),
                "faixa_max": round(mo["valor_max"]), "razao": round(preco / val, 3),
                "m2_anuncio_priv": round(preco / area), "distrito": loc.get("distrito")})
    out.append(rec)
pd.DataFrame(out).to_csv(sys.argv[2], index=False)
o = pd.DataFrame(out)
print(o.status.value_counts())
a = o[o.status == "avaliado"]
print(a.razao.describe()); print((a.razao <= 1).sum(), "na mediana ou abaixo")
print(a.groupby(["camada"]).size()); print(a.metodo_area.value_counts()); print(a.localizacao.value_counts())
