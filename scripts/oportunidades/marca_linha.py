# Marca d'água das fotos fora de 3:2 (4:3, 16:9, fotos de celular): "COELHO DA FONSECA" numa linha, centralizada,
# com largura proporcional à da foto. Molde estimado em 27/11/2026 a partir de 177 fotos com a marca:
#   marca_linha_alpha.npz: forma da marca (faixa de 200 x 1600 px), usada para achar a faixa e testar a presença;
#   marca_linha_a2.npz: opacidade por pixel da marca branca (≈0,30 nas letras), estimada com as fotos alinhadas; usada para remover.
import cv2, numpy as np, os
_D=os.path.dirname(os.path.abspath(__file__))
_A=np.load(os.path.join(_D,'marca_linha_alpha.npz'))['a'].astype(np.float32)
_A2=np.clip(np.load(os.path.join(_D,'marca_linha_a2.npz'))['a'].astype(np.float32),0,0.6); _K=1-_A2; _O=255*_A2
_AY0=int(np.where(_A>0)[0].min())
_MT=cv2.dilate((_A>0.005).astype(np.uint8),np.ones((15,15),np.uint8))
_T=cv2.GaussianBlur(_A/_A.max(),(0,0),1.5).astype(np.float32)
def alpha_para(I):
    h,w=I.shape[:2]; s=w/1600
    b=cv2.resize(_A,(w,max(1,round(_A.shape[0]*s))),interpolation=cv2.INTER_LINEAR); bh=b.shape[0]
    g=cv2.cvtColor(I,cv2.COLOR_BGR2GRAY).astype(np.float32)
    gx=np.abs(cv2.Sobel(g,cv2.CV_32F,1,0))+np.abs(cv2.Sobel(g,cv2.CV_32F,0,1))
    bx=np.abs(cv2.Sobel(b,cv2.CV_32F,1,0))+np.abs(cv2.Sobel(b,cv2.CV_32F,0,1))
    y0=h//2-bh//2; best=(-1,y0)
    for y in range(max(0,y0-round(30*s)),min(h-bh,y0+round(30*s))+1):
        c=float((gx[y:y+bh]*bx).sum())
        if c>best[0]: best=(c,y)
    a=np.zeros((h,w),np.float32); a[best[1]:best[1]+bh]=b; return a
def presenca(I,a):
    """razão entre o clareamento observado nas letras e o esperado (≈1 com marca, ≈0 sem marca)"""
    m=(a>0.01).astype(np.uint8); g=cv2.cvtColor(I,cv2.COLOR_BGR2GRAY).astype(np.float32)
    B=cv2.inpaint(g.astype(np.uint8),cv2.dilate(m,np.ones((7,7),np.uint8)),7,cv2.INPAINT_TELEA).astype(np.float32)
    s=a>=a.max()*0.9; esp=(a[s]*(255-B[s])).mean()
    return float((g[s]-B[s]).mean()/esp) if esp>3 else 0.0
def limpar_1600(I):
    """I com 1600 px de largura"""
    a=alpha_para(I)
    if presenca(I,a)<0.45: return I.copy(), False
    HB=_A.shape[0]; y0=int(np.where(a>0)[0].min())-_AY0; y0=max(0,min(I.shape[0]-HB,y0))
    crop=I[y0:y0+HB]; g=cv2.cvtColor(crop,cv2.COLOR_BGR2GRAY)
    B=cv2.inpaint(g,_MT,9,cv2.INPAINT_TELEA)
    D=cv2.GaussianBlur(np.clip(g.astype(np.float32)-B,0,None),(0,0),1.5); D/=np.percentile(D,99.5)+1e-3
    W=np.eye(2,3,dtype=np.float32)
    try: _,W=cv2.findTransformECC(_T,D.astype(np.float32),W,cv2.MOTION_AFFINE,(cv2.TERM_CRITERIA_EPS|cv2.TERM_CRITERIA_COUNT,200,1e-5),None,5)
    except cv2.error: pass
    k=cv2.warpAffine(_K,W,(1600,HB),flags=cv2.INTER_LINEAR,borderValue=1.0)
    o=cv2.warpAffine(_O,W,(1600,HB),flags=cv2.INTER_LINEAR,borderValue=0.0)
    Cf=crop.astype(np.float32); R=(Cf-o[...,None])/np.clip(k,0.3,1.5)[...,None]
    # proteção: em fotos retocadas depois da marca, a subtração pode passar do ponto em fundos escuros;
    # as letras não podem ficar mais escuras que o fundo estimado ao redor (com folga de 10)
    Bc=cv2.inpaint(crop,_MT,9,cv2.INPAINT_TELEA).astype(np.float32)
    aw=cv2.warpAffine(_A2,W,(1600,HB),flags=cv2.INTER_LINEAR)[...,None]
    R=np.where(aw>0.05,np.maximum(R,np.minimum(Bc-10,Cf)),R)
    R=np.clip(R,0,255).astype(np.uint8)
    # contorno das letras: a compressão JPEG da foto original deixa um traço; reconstrói só esse anel
    m=(_A2w:=cv2.warpAffine(_A2,W,(1600,HB),flags=cv2.INTER_LINEAR))>0.08
    anel=cv2.morphologyEx(m.astype(np.uint8),cv2.MORPH_GRADIENT,np.ones((5,5),np.uint8))
    R=cv2.inpaint(R,anel,3,cv2.INPAINT_TELEA)
    J=I.copy(); J[y0:y0+HB]=R
    return J, True
def limpar_arr(I):
    h,w=I.shape[:2]
    if w==1600: J,ok=limpar_1600(I); return J,ok
    I2=cv2.resize(I,(1600,round(h*1600/w)),interpolation=cv2.INTER_AREA if w>1600 else cv2.INTER_CUBIC)
    J,ok=limpar_1600(I2)
    return (cv2.resize(J,(w,h),interpolation=cv2.INTER_AREA if w<1600 else cv2.INTER_CUBIC) if ok else I.copy()), ok
