#!/usr/bin/env python3
"""Envia um ou mais arquivos do repositório para a Hostinger por FTPS (FTP explícito sobre TLS).

Feito para rodar no GitHub Actions, sem interação e sem cair para FTP em texto puro.
Só usa a biblioteca padrão do Python (ftplib), nada para instalar.

Uso:
    python3 scripts/enviar-dados-ftp.py src/data/dados-sgs.json [outro/arquivo ...]

Variáveis de ambiente (vêm dos Secrets do repositório):
    FTP_HOST   servidor (ex.: 185.211.7.251)
    FTP_USER   usuário FTP da hospedagem
    FTP_PASS   senha FTP
    FTP_DEST   pasta remota raiz do site (padrão: /public_html)
    FTP_PORT   porta (padrão: 21)

Cada arquivo é gravado no mesmo caminho relativo dentro de FTP_DEST, criando as
pastas que faltarem. O tamanho remoto é conferido depois do envio.
"""
import ftplib
import os
import sys

HOST = os.environ.get("FTP_HOST", "")
USER = os.environ.get("FTP_USER", "")
PASS = os.environ.get("FTP_PASS", "")
DEST = os.environ.get("FTP_DEST", "/public_html").rstrip("/") or "/"
PORT = int(os.environ.get("FTP_PORT", "21"))

arquivos = [a for a in sys.argv[1:] if a.strip()]
if not arquivos:
    print("Nada a enviar: informe ao menos um arquivo.")
    sys.exit(2)
faltando = [n for n, v in (("FTP_HOST", HOST), ("FTP_USER", USER), ("FTP_PASS", PASS)) if not v]
if faltando:
    print("Faltam os secrets: " + ", ".join(faltando) + ". Configure em Settings > Secrets and variables > Actions.")
    sys.exit(2)
for a in arquivos:
    if not os.path.isfile(a):
        print(f"Arquivo não encontrado: {a}")
        sys.exit(2)

ftp = ftplib.FTP_TLS(timeout=60)
ftp.connect(HOST, PORT)
ftp.auth()
ftp.login(USER, PASS)
ftp.prot_p()            # canal de dados também cifrado
ftp.set_pasv(True)
print(f"Conectado por FTPS a {HOST}:{PORT}, raiz {DEST}")


def garantir_pasta(caminho_remoto):
    """Cria, uma a uma, as pastas de um caminho remoto absoluto."""
    partes = [p for p in caminho_remoto.split("/") if p]
    atual = ""
    for parte in partes:
        atual += "/" + parte
        try:
            ftp.mkd(atual)
        except ftplib.error_perm:
            pass            # já existe


falhas = []
for rel in arquivos:
    rel = rel.replace("\\", "/").lstrip("./")
    remoto = f"{DEST}/{rel}"
    garantir_pasta(os.path.dirname(remoto))
    with open(rel, "rb") as fh:
        ftp.storbinary(f"STOR {remoto}", fh)
    local = os.path.getsize(rel)
    try:
        tam_remoto = ftp.size(remoto)
    except ftplib.error_perm:
        tam_remoto = None
    ok = tam_remoto in (None, local)
    print(f"  {'ok  ' if ok else 'ERRO'} {rel} -> {remoto} ({local} bytes{'' if ok else f', remoto {tam_remoto}'})")
    if not ok:
        falhas.append(rel)

ftp.quit()
if falhas:
    print("Falhou: " + ", ".join(falhas))
    sys.exit(1)
print(f"Enviados {len(arquivos)} arquivo(s).")
