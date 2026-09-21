# Prompt para o Claude Code — subpágina Destination Services

Cole o texto abaixo no Claude Code, com os dois arquivos já salvos no repositório
(sugestão: `design/destination-services-reference.html` e este arquivo ao lado dele).

---

Construa a subpágina `/destination-services/` do site liveandlearnbrazil.com.

A referência de layout está em `design/destination-services-reference.html`. É um
arquivo exportado de uma ferramenta de design, não código de produção: aproveite dele
a estrutura das seções, as medidas, as cores, a tipografia e o texto — e reescreva a
marcação com os componentes e tokens que o site já tem.

## O que a página é

Um cardápio de serviços: o visitante marca as caixas dos serviços que lhe interessam
e monta o próprio pacote, em vez de escolher entre planos fechados. Isso precisa ficar
claro no layout, não só no texto.

Ordem das seções: cabeçalho → hero (ardósia) com o painel "How it works" ao lado →
parágrafo de introdução → grupo SITE, HOME AND OPERATIONS (5 cards) → fio inclinado →
grupo DAILY LIFE AND FAMILY (7 cards) → fecho "Send us your list" (ardósia) → rodapé.

## Regras da implementação

- Reutilize os componentes e tokens existentes (`src/styles.css`, `Header`, `Footer`,
  `Logo`, `Card`). Nada de hexadecimal solto no JSX: tudo por `var(--*)`.
- O placeholder `[ LOGO ]` no cabeçalho vira o componente `Logo` real.
- Fontes auto-hospedadas do projeto (Libertinus Sans, Open Sans, PT Sans Narrow),
  sem nenhuma chamada ao Google Fonts — o site precisa abrir na China. Os caminhos
  `/fonts/…` da referência são ilustrativos.
- Os títulos de grupo são os rótulos em PT Sans Narrow caixa alta, 42px, amora. Não
  existe título em Libertinus nessas seções: o rótulo é o título.
- O painel "How it works" começa dentro do hero e desce para a seção seguinte; a
  última linha do parágrafo de introdução termina na mesma altura em que termina esse
  painel. Esse alinhamento é intencional — preserve-o no desktop.
- Os cards usam `min-height`, nunca altura fixa: o texto traduzido é mais longo em
  português e em italiano e não pode ser cortado.
- Cada card é `<input type="checkbox">` + `<label for>` de verdade, acessível por
  teclado. A seleção do visitante vira a lista que segue junto com o pedido de escopo
  no formulário de contato (o botão REQUEST A SCOPE).
- A referência é desktop 1440px. Escreva o responsivo: em tablet os cards viram duas
  colunas, no celular uma coluna só, e o painel "How it works" passa a vir depois do
  hero, em fluxo normal.
- Contraste mínimo 4,5:1 em todo par de texto novo (3:1 para texto grande).

## Idiomas

Comece pela versão em inglês, com a estrutura de i18n já preparada para português,
chinês e italiano, como no resto do site.

## O que NÃO entra

- Nenhum preço na página. O caminho comercial é o botão REQUEST A SCOPE.
- Nenhum verde, em lugar nenhum.
- Champagne cheio (`#DCC792`) aparece como fundo em dois cards (Security advisory e
  Portuguese for children and teenagers). Foi uma decisão deliberada; mantenha.
