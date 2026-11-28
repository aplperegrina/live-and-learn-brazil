import cv2, numpy as np
import fotos_vitrine as F
def lisa_clara(I):
    """fração da faixa da marca (medida logo acima e abaixo dela) que é superfície clara e lisa, onde o resíduo aparece"""
    h,w=I.shape[:2]
    if w>1600: I=cv2.resize(I,(1600,round(h*1600/w)),interpolation=cv2.INTER_AREA); h,w=I.shape[:2]
    a=F.alpha_para(I); ys,xs=np.where(a>0.06)
    if len(ys)==0: return 0.0
    y0,y1,x0,x1=ys.min(),ys.max(),xs.min(),xs.max(); d=max(10,(y1-y0)//2)
    g=cv2.cvtColor(I,cv2.COLOR_BGR2GRAY).astype(np.float32)
    mu=cv2.blur(g,(9,9)); sd=np.sqrt(np.maximum(cv2.blur(g*g,(9,9))-mu*mu,0))
    rows=np.r_[max(0,y0-d):y0, y1+1:min(h,y1+1+d)]
    M=(mu[rows,x0:x1]>140)&(sd[rows,x0:x1]<6)
    return float(M.mean())
