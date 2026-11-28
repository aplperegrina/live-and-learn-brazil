import sys, re, requests, concurrent.futures as cf
from bs4 import BeautifulSoup
base_url, out = sys.argv[1], sys.argv[2]
S = requests.Session(); S.headers["User-Agent"] = "Mozilla/5.0"
cards, seen = [], set()
for p in range(1, 60):
    h = S.get(base_url + f"&page={p}", timeout=60).text
    soup = BeautifulSoup(h, "html.parser"); arts = soup.find_all("article")
    new = 0
    for a in arts:
        l = a.find("a", href=True)
        if not l: continue
        href = l["href"].split("?")[0]
        if href in seen: continue
        seen.add(href); new += 1
        cards.append((href, a.get_text("", strip=True)))
    if not arts or not new: break
print("cards", len(cards))
def det(c):
    href, card = c
    for _ in range(3):
        try:
            raw = S.get("https://www.coelhodafonseca.com.br" + href, timeout=60).text; break
        except Exception: raw = ""
    h = raw.replace('\\"', '"'); s = h.find('{"property":{'); seg = h[s:] if s >= 0 else ""
    g = lambda rx: (re.search(rx, seg) or [None, ""])[1]
    i1 = seg.find('"addressNumber"'); i2 = seg.find('"addressNumber"', i1 + 20)
    own = seg[: (i2 - 200) if i2 > 0 else 60000]
    imgs = list(dict.fromkeys(re.findall(r"images/imoveis/original/(\d+)\.jpg", own)))
    txt = BeautifulSoup(raw, "html.parser").get_text("", strip=False) if raw else ""
    terr = (re.search(r"([\d.,]+)\s*m²\s*Área do terreno", txt) or [None, ""])[1]
    code = (re.search(r"cf(\d+)$", href) or [None, ""])[1]
    return "\t".join([code, href, card.replace("\t", " "), g(r'"fullName":"([^"]*)"'), g(r'"addressNumber":"([^"]*)"'),
                      g(r'"zipCode":"([^"]*)"'), terr, "", g(r'"enterpriseName":"([^"]*)"').strip(), ",".join(imgs)])
with cf.ThreadPoolExecutor(8) as ex: rows = list(ex.map(det, cards))
open(out, "w").write("codigo\tslug\tcard\trua\tnumero\tcep\tterreno\tarea_util\tcondominio\timagens\n" + "\n".join(rows))
print("ok", len(rows))
