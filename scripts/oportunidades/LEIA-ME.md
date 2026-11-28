# Triagem de oportunidades (Coelho da Fonseca → motor de avaliação → página Oportunidades)

Processo usado em 27/11/2026 para Brooklin, Itaim Bibi e Cidade Jardim. Os scripts foram escritos para aquela rodada:
caminhos e nomes de arquivo estão no código e precisam ser ajustados a cada nova rodada (veja "Ao reaproveitar").

## Etapas

1. `1_ler_busca_coelho.py URL saida.tsv`: lê todas as páginas de uma busca do site da Coelho da Fonseca (`&page=N`) e,
   para cada anúncio, a página do imóvel: rua, número, CEP, terreno e os IDs das fotos (`static.coelhodafonseca.com.br/images/imoveis/original/<id>.jpg`).
   Roda direto do Python (requests), sem navegador.
2. `2_avaliar_no_motor.py anuncios.tsv resultado.csv`: passa cada anúncio pelo motor do app (repositório privado
   `aplperegrina/meuestrategistadevendas`, pasta `assistente-corretor/app`, classe `Servico`), do mesmo jeito que o app faz:
   localização pelo endereço (ou número vizinho da mesma rua, ou SQL do ITBI amplo), área do IPTU estimada a partir da
   área do anúncio (método A4: `estimar_area_regua`), cobertura com área do apartamento-tipo. Grava `razao` = preço pedido ÷
   valor na mediana dos comparáveis, `camada` e `confianca`.
3. Seleção: `razao <= 1` e `confianca` alta ou média.
4. `3_ficha_dos_selecionados.py`: dados objetivos de cada selecionado (área útil/construída, terreno, dormitórios, suítes,
   vagas, condomínio, características da frase padrão do anúncio).
5. `4_montar_json_e_fotos.py`: escolhe 5 fotos por imóvel (`fotos_vitrine.py`: evita planta, foto repetida e foto com a marca
   sobre parede branca), tira a marca d'água (`tirar_marca.py` + `marca_dagua_alpha.npz`), grava WebP 960 px em
   `realestate/oportunidades/fotos/<ref>/` e o `realestate/oportunidades/dados/oportunidades.json`.
   Também gera a planilha de referências (ref da vitrine ↔ código e link da Coelho), que fica só com a Andrea.
6. `planilha_triagem.py`: planilha da triagem completa (selecionados, todos avaliados, não avaliados).

## Ao reaproveitar (nova rodada, outros bairros)

- **Não renumerar o que já existe.** `4_montar_json_e_fotos.py` foi escrito para montar o JSON do zero. Numa nova rodada,
  ler o `oportunidades.json` atual, manter os imóveis existentes e acrescentar os novos com a próxima referência do bairro.
  A correspondência ref ↔ código da Coelho está na planilha `referencias_oportunidades.xlsx` (computador da Andrea, pasta Downloads);
  ela não vai para o repositório, que é público. Usar essa planilha para não repetir imóvel já publicado.
- **Prefixos de referência por bairro**: BK Brooklin, IT Itaim Bibi, CJ Cidade Jardim; criar um prefixo de 2 letras para cada bairro novo
  e acrescentar o bairro em `BAIRROS` (e, se tiver preposição diferente de "no", em `NO`) no `realestate/oportunidades/index.html`.
- **Duplicatas**: mesmo endereço e mesma área = mesma unidade anunciada mais de uma vez; fica a de menor preço.
- **Marca d'água**: `marca_dagua_alpha.npz` é o padrão da marca da Coelho em fotos 3:2 (1600×1067); fotos de outra proporção
  usam o mesmo padrão pela largura, com a altura achada por correlação. Conferir as capas numa folha de miniaturas.
- **Boletim do bairro**: se o bairro tiver boletim, a fileira do boletim já lê o JSON (constante `OP_BAIRRO` no boletim).
- **Avaliação feita pela Andrea**: imóvel com `"avaliacao": "andrea"` ganha o selo "Avaliado pessoalmente por Andrea".
