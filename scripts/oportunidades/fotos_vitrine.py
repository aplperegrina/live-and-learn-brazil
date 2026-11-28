import cv2, numpy as np, os, json, glob, sys
sys.path.insert(0,os.path.dirname(os.path.abspath(__file__))); from tirar_marca import A
def limpar_arr(I):
    I=I.astype(np.float32); h,w=I.shape[:2]
    a=cv2.resize(A,(w,h),interpolation=cv2.INTER_CUBIC)
    ak=np.clip(a,0,0.97)[...,None]; J=np.clip((I-ak*255)/(1-ak),0,255).astype(np.uint8)
    m=(a>0.06).astype(np.uint8); edge=cv2.morphologyEx(m,cv2.MORPH_GRADIENT,np.ones((5,5),np.uint8))
    return cv2.inpaint(J,edge,4,cv2.INPAINT_TELEA), m
def feats(path):
    I=cv2.imread(path); h,w=I.shape[:2]
    if abs(w/h-1.5)>0.03: return None
    a=cv2.resize(A,(w,h)); band=a>0.06
    g=cv2.cvtColor(I,cv2.COLOR_BGR2GRAY); hsv=cv2.cvtColor(I,cv2.COLOR_BGR2HSV)
    claro=(g[band]>238).mean()                       # watermark over near-white: residual smudges
    planta=((g>225).mean()>0.6) and (hsv[...,1].mean()<25)   # floor plan / mostly white drawing
    hist=cv2.calcHist([hsv],[0,1],None,[16,8],[0,180,0,256]); cv2.normalize(hist,hist)
    small=cv2.resize(g,(32,24)).astype(np.float32)
    return dict(I=I,claro=claro,planta=planta,hist=hist,small=small)
def escolher(paths, n=5):
    cands=[]
    for i,p in enumerate(paths):
        f=feats(p)
        if f is None or f['planta']: continue
        f['i']=i; f['p']=p; cands.append(f)
    def bom(f): return f['claro']<0.35
    ordem=[f for f in cands if bom(f)]+[f for f in cands if not bom(f)]
    # primeira: a primeira boa entre as 3 primeiras do anúncio (costuma ser a foto principal), senão a primeira boa
    first=next((f for f in ordem if f['i']<3 and bom(f)), ordem[0] if ordem else None)
    esc=[first] if first else []
    for thr in (0.80,0.90,0.97):
        for f in ordem:
            if len(esc)>=n: break
            if any(f is e for e in esc): continue
            sim=max((cv2.compareHist(f['hist'],e['hist'],cv2.HISTCMP_CORREL) for e in esc),default=0)
            if sim<thr: esc.append(f)
    return esc[:n]
def processar(cod, paths, dest, largura=960):
    os.makedirs(dest,exist_ok=True); out=[]
    for k,f in enumerate(escolher(paths),1):
        J,_=limpar_arr(f['I']); h,w=J.shape[:2]; J=cv2.resize(J,(largura,int(h*largura/w)),interpolation=cv2.INTER_AREA)
        fn=f"{dest}/{k}.webp"; cv2.imwrite(fn,J,[cv2.IMWRITE_WEBP_QUALITY,74]); out.append((os.path.basename(f['p']),f['claro']))
    return out

# fotos fora de 3:2: a marca tem a mesma largura relativa; a altura certa é achada por correlação
_Y0,_Y1=440,610
_BAND=A[_Y0:_Y1]
def alpha_para(I):
    h,w=I.shape[:2]
    if abs(w/h-1.5)<=0.03: return cv2.resize(A,(w,h),interpolation=cv2.INTER_CUBIC)
    s=w/1600; b=cv2.resize(_BAND,(w,max(1,int(round(_BAND.shape[0]*s)))),interpolation=cv2.INTER_CUBIC); bh=b.shape[0]
    g=cv2.cvtColor(I,cv2.COLOR_BGR2GRAY).astype(np.float32); gx=cv2.Sobel(g,cv2.CV_32F,1,0); bx=cv2.Sobel(b,cv2.CV_32F,1,0)
    best=(-1e18,0)
    for y in range(max(0,int(h*0.3)),min(h-bh,int(h*0.7))):
        c=float((gx[y:y+bh]*bx).sum())
        if c>best[0]: best=(c,y)
    a=np.zeros((h,w),np.float32); a[best[1]:best[1]+bh]=b; return a
def limpar_arr(I):
    I=I.astype(np.float32); h,w=I.shape[:2]
    a=alpha_para(I.astype(np.uint8))
    ak=np.clip(a,0,0.97)[...,None]; J=np.clip((I-ak*255)/(1-ak),0,255).astype(np.uint8)
    m=(a>0.06).astype(np.uint8); edge=cv2.morphologyEx(m,cv2.MORPH_GRADIENT,np.ones((5,5),np.uint8))
    return cv2.inpaint(J,edge,4,cv2.INPAINT_TELEA), m
def feats(path):
    I=cv2.imread(path); h,w=I.shape[:2]
    if w<h: return None
    a=alpha_para(I); band=a>0.06
    g=cv2.cvtColor(I,cv2.COLOR_BGR2GRAY); hsv=cv2.cvtColor(I,cv2.COLOR_BGR2HSV)
    claro=(g[band]>238).mean() if band.any() else 0
    planta=((g>225).mean()>0.6) and (hsv[...,1].mean()<25)
    hist=cv2.calcHist([hsv],[0,1],None,[16,8],[0,180,0,256]); cv2.normalize(hist,hist)
    return dict(I=I,claro=claro,planta=planta,hist=hist)
