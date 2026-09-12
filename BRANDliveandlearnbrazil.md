# Live & Learn Brazil — definição da marca

Fonte única de verdade da identidade visual. Para produzir qualquer material da
marca (site, cartão, apresentação, documento, anúncio), siga o que está aqui.

Serviços: relocation de executivos, assessoria imobiliária de alto valor e due
diligence intercultural para multinacionais entrando em São Paulo. O público é
corporativo e privado de alto padrão. O tom é discreto, preciso e confiante,
nunca efusivo.

---

## 1. Paleta

A proporção faz parte da identidade tanto quanto os valores.

| Papel | Hex | oklch | Dose |
|---|---|---|---|
| Papel (fundo dominante) | `#FFFAFA` | `oklch(0.989 0.005 17.2)` | maior parte |
| Papel elevado | `#F4F1E9` | `oklch(0.958 0.011 89.7)` | apoio |
| Papel rebaixado | `#EAE7DC` | `oklch(0.927 0.015 94.2)` | apoio |
| Verde profundo (âncora) | `#273219` | `oklch(0.3 0.045 128.2)` | 20% |
| Verde sombra | `#1D2613` | `oklch(0.254 0.036 129.3)` | apoio |
| Sálvia (artes e detalhes) | `#617D62` | `oklch(0.56 0.052 145.7)` | 25% |
| Sálvia escura | `#4A5F4C` | `oklch(0.462 0.04 147.4)` | apoio |
| Tinta (escrita) | `#0F140A` | `oklch(0.182 0.021 129.9)` | texto |
| Ouro escuro | `#8D7343` | `oklch(0.569 0.073 81.7)` | 6% |
| Ouro | `#C6A868` | `oklch(0.744 0.09 85.2)` | 6% |
| Ouro claro | `#E2CC94` | `oklch(0.85 0.077 89)` | 6% |
| Ouro tinta (ouro sobre papel) | `#7A6238` | `oklch(0.51 0.066 80.4)` | 6% |

**Texto secundário.** Sobre papel use `#55604A`. Sobre verde use `#B3BFA9`.
Nunca cinza neutro: o secundário sempre puxa para o verde do fundo.

**Regra do ouro.** O ouro é detalhe, não superfície. Fios, o gradiente do logo,
etiquetas em caixa alta, contornos de botão. Sobre papel, texto pequeno em ouro
usa `#7A6238`, nunca `#C6A868`, que dá só 2,2:1 de contraste.

**Gradiente do ouro.** Corre da ponta escura para a clara e volta, no sentido do
elemento. Sobre verde: `#8D7343 → #E2CC94 → #8D7343`. Sobre papel:
`#7A6238 → #C6A868 → #7A6238`. Reservado ao logo e a fios finos, nunca em texto.

### Contraste verificado

| Par | Razão |
|---|---|
| Tinta `#0F140A` sobre papel | 18,1:1 |
| Papel sobre verde `#273219` | 13,1:1 |
| Verde `#273219` sobre papel | 13,1:1 |
| Secundário `#55604A` sobre papel | 6,4:1 |
| Ouro tinta `#7A6238` sobre papel | 5,6:1 |
| Ouro `#C6A868` sobre verde | 5,9:1 |
| Secundário `#B3BFA9` sobre verde | 7,0:1 |

Todo par de texto novo precisa de 4,5:1, ou 3:1 se for texto grande ou contorno
de controle. Confira antes de fechar qualquer peça.

---

## 2. Tipografia

Três famílias, todas gratuitas sob a SIL Open Font License.

| Uso | Fonte | Pesos |
|---|---|---|
| Títulos | EB Garamond | 400 nos títulos, 500 e 600 para ênfase |
| Texto, menus, botões | Jost | 300, 400, 500 |
| Logo, palavra LIVE & LEARN | Marcellus | 400 |
| Logo, palavra BRAZIL | Jost Light | 300 |

**EB Garamond não tem peso 300.** O 400 é o mais leve. Não peça 300: o navegador
inventa um peso falso.

**Jost é geométrica e tem altura de x baixa (46% do quadratim).** Por isso o
texto corrido fica em 17px com entrelinha 1,7, e as caixas de texto são
dimensionadas para a linha ficar entre 65 e 75 caracteres. Se mudar o corpo,
recalcule a largura. Meça com a largura média real de caractere, não com a
largura do zero: o `ch` do CSS engana em fontes geométricas.

**Etiquetas em caixa alta** usam entreletra de 0,22em a 0,34em, corpo de 11px a
12px, na cor de acento do fundo (ouro tinta sobre papel, ouro sobre verde).

Nada de texto funcional abaixo de 11px.

---

## 3. Logo

Arquivos prontos em `public/brand/`, com o texto convertido em contorno, então
não dependem de fonte instalada.

| Arquivo | Uso |
|---|---|
| `live-and-learn-brazil-horizontal-dark.svg` | assinatura sobre verde |
| `live-and-learn-brazil-horizontal-light.svg` | assinatura sobre papel |
| `live-and-learn-brazil-stacked-dark.svg` | empilhada sobre verde |
| `live-and-learn-brazil-stacked-light.svg` | empilhada sobre papel |
| `live-and-learn-brazil-mark-dark.svg` | só o símbolo, sobre verde |
| `live-and-learn-brazil-mark-light.svg` | só o símbolo, sobre papel |
| `public/favicon.svg` | símbolo sobre verde, cantos suaves |

**Anatomia.** Uma porta desenhada em fio com dois cantos abertos, entre LIVE e
LEARN, fazendo o papel do "e". Um ponto cheio no centro do limiar. Uma linha
única que nasce fina, engrossa ao atravessar a porta e volta a afinar.

**Regras de cor, obrigatórias.**

- Sobre papel, a palavra é sempre verde profundo `#273219`.
- Sobre verde, a palavra é sempre papel `#FFFAFA`.
- Nunca papel sobre papel, nunca verde sobre verde.
- O ponto e a porta usam o ouro do fundo correspondente.

**Não faça.** Não recomponha o logo à mão, não troque as fontes dele, não mude
as proporções, não aplique sombra, não incline, não coloque sobre foto de baixo
contraste. Para variações, use o editor descrito na seção 6.

---

## 4. Artes e fotografia

Os desenhos de linha da marca passam por um mapa de luminância para cair na
paleta. Não use a arte original azul-marinho.

- **Sobre verde**: fundo `#273219`, meios-tons em sálvia, linhas em ouro.
- **Sobre papel**: fundo `#FFFAFA`, linhas em sálvia, virando uma gravura.

O script está em `tools/artwork/recolor.cjs`. Para uma foto nova, aplique o mesmo
princípio: a sálvia é o filtro que traz a imagem para dentro da marca.

---

## 5. Composição

- **Papel domina.** O verde profundo aparece em regiões inteiras, não em blocos
  espalhados. No site são duas: a abertura e o bloco de fecho com o rodapé.
- **Divisão por fio, não por caixa.** As seções se separam com fios dourados de
  1px e gradientes sutis. Evite cartões, bordas grossas e cantos arredondados.
- **Sem números de seção** a menos que a ordem realmente informe algo.
- **Sem texto em degradê.** Ênfase vem de peso, tamanho ou cor sólida.
- **Espaço é material.** Margens generosas, respiro acima dos títulos maior que
  abaixo.

---

## 6. Onde mexer

| O que | Onde |
|---|---|
| Tokens de cor e tipografia do site | `src/styles.css` |
| Componente do logo em React | `src/components/brand/Logo.tsx` |
| Geometria do logo | `tools/logo/gen.cjs` |
| Gerar SVGs, favicon e componente | `tools/logo/build.cjs` |
| Editor visual do logo | `tools/logo/editor.template.html` |
| Recolorir artes | `tools/artwork/recolor.cjs` |
| Baixar as fontes | `tools/logo/fetch-fonts.sh` |

Depois de mudar a paleta, rode `tools/logo/build.cjs` e `tools/artwork/recolor.cjs`
para os arquivos acompanharem, e confira o contraste de novo.
