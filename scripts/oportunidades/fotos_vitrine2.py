# versão 28/11/2026: escolhe as fotos evitando a marca sobre superfície clara e lisa (onde sobra resíduo)
import cv2, numpy as np, os
import fotos_vitrine as F
import marca_linha as ML
from residuo import lisa_clara
def feats(p):
    I=cv2.imread(p)
    if I is None: return None
    h,w=I.shape[:2]
    if w<h: return None
    if w>1600: I=cv2.resize(I,(1600,round(h*1600/w)),interpolation=cv2.INTER_AREA)
    g=cv2.cvtColor(I,cv2.COLOR_BGR2GRAY); hsv=cv2.cvtColor(I,cv2.COLOR_BGR2HSV)
    planta=((g>225).mean()>0.6) and (hsv[...,1].mean()<25)
    hist=cv2.calcHist([hsv],[0,1],None,[16,8],[0,180,0,256]); cv2.normalize(hist,hist)
    fora=abs(w/h-1.5)>0.03
    return dict(I=I,planta=planta,hist=hist,lc=(0.3 if fora else lisa_clara(I)),fora32=fora)
def escolher(paths,n=5):
    c=[]
    for i,p in enumerate(paths):
        f=feats(p)
        if f is None or f['planta']: continue
        f['i']=i; f['p']=p; c.append(f)
    nivel=lambda f: 1 if f['fora32'] else (0 if f['lc']<0.25 else (1 if f['lc']<0.4 else 2))
    ordem=sorted(c,key=lambda f:(nivel(f),f['i']))
    # capa: a primeira foto do anúncio entre as de nível 0, de preferência entre as 4 primeiras
    cap=[f for f in c if nivel(f)==0]
    first=next((f for f in cap if f['i']<4), cap[0] if cap else (ordem[0] if ordem else None))
    esc=[first] if first else []
    for thr in (0.80,0.90,0.97):
        for f in ordem:
            if len(esc)>=n: break
            if any(f is e for e in esc): continue
            if max((cv2.compareHist(f['hist'],e['hist'],cv2.HISTCMP_CORREL) for e in esc),default=0)<thr: esc.append(f)
    return esc[:n]
def processar(cod,paths,dest,largura=960):
    os.makedirs(dest,exist_ok=True)
    for x in os.listdir(dest): os.remove(os.path.join(dest,x))
    out=[]
    for k,f in enumerate(escolher(paths),1):
        J,_=(ML.limpar_arr(f['I']) if f['fora32'] else F.limpar_arr(f['I'])); h,w=J.shape[:2]; J=cv2.resize(J,(largura,int(h*largura/w)),interpolation=cv2.INTER_AREA)
        cv2.imwrite(f"{dest}/{k}.webp",J,[cv2.IMWRITE_WEBP_QUALITY,74]); out.append((os.path.basename(f['p']),round(f['lc'],2)))
    return out
