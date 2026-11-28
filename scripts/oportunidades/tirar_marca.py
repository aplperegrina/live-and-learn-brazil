import cv2, numpy as np, sys
import os
A=np.load(os.path.join(os.path.dirname(os.path.abspath(__file__)),'marca_dagua_alpha.npz'))['a'].astype(np.float32)
def limpar(path, out, k=1.0, borda=5):
    I=cv2.imread(path).astype(np.float32); h,w=I.shape[:2]
    a=cv2.resize(A,(w,h),interpolation=cv2.INTER_CUBIC)
    ak=np.clip(a*k,0,0.97)[...,None]
    J=np.clip((I-ak*255)/(1-ak),0,255).astype(np.uint8)
    m=(a>0.06).astype(np.uint8)
    edge=cv2.morphologyEx(m,cv2.MORPH_GRADIENT,np.ones((borda,borda),np.uint8))
    J=cv2.inpaint(J,edge,4,cv2.INPAINT_TELEA)
    cv2.imwrite(out,J,[cv2.IMWRITE_JPEG_QUALITY,90])
if __name__=='__main__': limpar(sys.argv[1],sys.argv[2])
