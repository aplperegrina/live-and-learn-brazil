# Live & Learn Brazil — design system

A brief for Claude Design (or any designer or agent) describing how liveandlearnbrazil.com is built. Everything here is taken from the published site: the shared stylesheet `css/styles.css` and one stylesheet per page in `css/`. Token names in backticks are the real CSS custom properties. When this document and the CSS disagree, the CSS wins; update this file.

Repository: `aplperegrina/live-and-learn-brazil`. Static site (HTML, CSS, a little vanilla JS, PHP only for forms), published to Hostinger from the `main` branch. No framework, no build step, no external requests: fonts, images and scripts are all self-hosted, because the site must open in China.

## 1. Character

Editorial, calm, warm. A pearl-white page with graphite text, two slate regions per page (an opening and a closing), champagne hairlines as the only ornament, and a mulberry accent used sparingly. Straight corners, no shadows, no thick borders, no gradients except a 4% tint inside slate blocks and the champagne curves. Blocks are separated by flat colour or by a 1px or 2px line, never by elevation.

One page, one voice: the same header and footer everywhere, the same four fonts, the same seven colours.

## 2. Colour

### Palette and dose

The dose is the approximate share of the page area. Keep it.

| Role | Token | Hex | Dose |
|---|---|---|---|
| Pearl white. Page and header background; text on dark grounds. The token keeps its old name. | `--gelo` | `#FBFCF8` | 55% |
| Slate. Whole dark regions: opening, closing, footer, photo veils, the Destination card, the diagnosis bar. | `--ardosia` | `#3E495F` | 20% |
| Light champagne. Support surface: light cards, highlight boxes, the "How it works" panel, the WhatsApp/WeChat block, hero paragraph text (at 92%). | `--champagne-claro` | `#F2E4C7` | 10% |
| Ice blue. Light cards, form fields, button background, alternate section background. | `--azul-gelo` | `#F6F9FF` | 8% |
| Champagne. Hairlines of 1–2px, underlines, dots, numerals, the footer curve, the diagonal thread. Two full-champagne cards on Destination Services. | `--champagne` | `#DCC792` | 5% |
| Mulberry. Accent only: menu hover and current item, button outline and hover, list dashes, section labels, checked state, stroke of one art variant. | `--amora` | `#4E1F39` | 2% |
| Graphite. Running text and headings on light grounds; background of the dark art blocks. | `--grafite` | `#333333` | text |

Utility colours outside the palette:

| Use | Token | Value |
|---|---|---|
| Secondary text on light grounds (pulls toward slate, never neutral grey). 6.1:1 on pearl white, 5.9:1 on ice blue, 5.0:1 on light champagne. | `--texto-secundario` | `#56607A` |
| Secondary text on slate, 7.6:1 | `--texto-sobre-ardosia` | `#C3CAD6` |
| Divider hairline on light grounds | `--linha` | `rgba(51, 51, 51, 0.12)` |
| Champagne hairline on slate | `--linha-champagne` | `rgba(220, 199, 146, 0.28)` |
| Hero paragraph on slate (light champagne at 92%) | `--creme-hero` | `rgba(242, 228, 199, 0.92)` |
| Language panel beside the home hero (champagne at 80%) | `--painel-idiomas` | `rgba(220, 199, 146, 0.8)` |
| QR code cards only (pure white for scanning) | `--branco` | `#FFFFFF` |

Data ink (`--dado-*`: slate, green, yellow, coral, champagne, dark green) exists only for the SVG charts of the real-estate dashboard. It never appears in buttons, borders, backgrounds, titles or icons.

### Rules

- The pearl white dominates. Slate appears as whole regions, never as scattered blocks. No slate block is flat: a 2px champagne line on top, an internal 4% tint, a light-champagne panel or generous air.
- Two dark regions per page: the opening and the closing. The closing joins the footer, which is also slate, so they read as one.
- Champagne is detail. Never running text, never a large ground. The exception is deliberate: two full-champagne cards on Destination Services, with graphite text (7.6:1).
- Mulberry is accent and only accent. Never a section background, never running text. It carries the section labels, the hover states, the checked state and the button outline.
- No green anywhere. The former institutional green was removed; the `--verde` token no longer exists.
- Text on slate is `--gelo`; secondary text on slate is `--texto-sobre-ardosia`.

### Contrast

Every text/ground pair meets 4.5:1 (3:1 for text of 24px and up, control outlines, focus rings). Checked pairs: graphite on pearl white 12:1, on ice blue 12:1, on light champagne 10:1, on champagne 7.6:1; pearl white on slate 8.9:1; champagne on slate 5.4:1; mulberry on pearl white 11.9:1 and on light champagne 10:1.

## 3. Typography

Four self-hosted families, subset to Latin plus the arrows and punctuation the texts use, in `fonts/` as WOFF2. No Google Fonts.

| Use | Token | Family | Weights |
|---|---|---|---|
| Headings `h1`–`h3` | `--fonte-titulo` | Libertinus Sans | 400 (700 only if needed) |
| Text, calls to action, buttons, footer | `--fonte-texto` | Open Sans | 400, 400 italic, 600, 700 |
| Top menu, section labels, numerals | `--fonte-menu` | PT Sans Narrow | 400, 700 |
| Language menu | `--fonte-idiomas` | Open Sans + Noto Sans SC (only the two ideograms of 中文) | 600 |

Chinese pages (`/zh/`) swap headings and menu to a subset Noto Sans SC and use the system CJK font for body text; uppercase and wide tracking are turned off there.

### Scale

- Body: 20px, line-height 1.6, graphite.
- Introduction directly below the hero: 20px, line-height 1.6, same as body.
- Section prose elsewhere (two-column sections, team block): 28–32px, line-height 1.45.
- Card body: 20px, line-height 1.6.
- Headings: weight 400, line-height 1.02–1.08. Home hero h1 86px; page heroes 64–76px; band h2 62px; closing h2 56px; card h2 40px; card h3 (checkbox cards) 34px, small variant 30px.
- Hero lead on the home: 34px on cream; on the other pages 21–24px.

### Uppercase with tracking

Uppercase with letter-spacing is the signature of every call to action and label. Always PT Sans Narrow 700 for labels and menus, Open Sans 700 for calls, buttons and contact links.

| Element | Size / tracking |
|---|---|
| Call to action `.cta` (2px underline) | 20px / 0.14em |
| Menu `.menu a` | 20px / 0.14em |
| Button `.btn` | 20px / 0.14em |
| Language menu `.idiomas a` | 24px / 0.16em |
| Diagnosis bar `.diagnostico` | 24px / 0.14em |
| Page label above the hero h1 | 42px / 0.14em, champagne on slate |
| Section label (the section title itself) | 42px / 0.14em, mulberry on pearl white |
| Sub-label ("01 / RECOGNISE THE SIGNALS") | 30px, mulberry |
| Small label (breadcrumbs, panel titles) | 16–22px / 0.12–0.14em |
| Footer site link `.footer-site` | 18px / 0.18em |
| QR caption `.qr span` | 14px / 0.18em |

On the service pages there is no Libertinus heading for a group of cards: the uppercase label is the title.

## 4. Layout and spacing

| Token | Value | Use |
|---|---|---|
| `--largura-max` | 1440px | Page width, centred; the pearl-white page fills the viewport behind it |
| `--margem` | 56px | Side margins (40px up to 1100px, 24px on phones) |
| `--coluna-gap` | 24px | Gap between columns and cards |
| `--altura-header` | 132px | Header height |
| `--altura-logo` | 78px | Logo height in the header |
| section rhythm | 104px | Vertical air between sections; 88px below a card group |
| card padding | 52px | Home cards; 40px on the checkbox cards |

Grids are 12 columns with a 24px gap. Cards span 4, 5, 7, 8 or 12 columns; avoid rows of identical boxes. Rhythm comes from alternating widths (5 + 7, then 7 + 5), one full-width block, and one transversal element crossing the page (a champagne curve, a diagonal thread).

Breakpoints: 1100px (tablet: two columns, menu wraps) and 760px (phone: one column, header stacks, panels drop into normal flow). Write desktop first.

## 5. Shared components

**Header.** 132px, pearl white, 1px `--linha` below. Logo horizontal-light at 78px on the left, never shrinking. Menu on the right in PT Sans Narrow: it takes the remaining width and wraps into two right-aligned lines when the four items do not fit. Hover and current page in mulberry. On phones the header stacks.

**Footer.** Slate, the logotype curve in champagne drawn as an SVG across the whole block, logo horizontal-dark at 146px on the left, the site address as an uppercase link with a champagne underline on the right.

**Hero (home).** 662px, slate with the skyline photo behind two gradient veils, a 72px champagne line above the h1, the lead in cream. Beside it, the language panel in champagne at 80% with the four languages as uppercase links with a champagne dot.

**Hero (inner pages).** A slate block with a 2px champagne line on top, page label in champagne, h1 in pearl white, lead in pearl white or cream. Some heroes carry a panel that starts inside the slate and descends into the next section (Destination Services), a form beside the text (Portuguese), or line art fading toward the text (Innovation Ties).

**Card.** Flat colour block, 52px padding, h2 40px, body 20px, call to action pinned to the bottom. Tones: ice blue (`.card--azul-gelo`), light champagne (`.card--champagne`), full champagne (`.card--champagne-cheio`), slate with pearl-white text (`.card--ardosia`). Min-height, never fixed height: Portuguese and Italian texts are longer.

**Checkbox card.** A card with a real `<input type="checkbox">` and the h3 as its `<label>`, 2px champagne line on top (mulberry on the champagne tones). Checked: 2px inset mulberry outline and mulberry title. The visitor ticks services and the list travels with the request-a-scope link.

**Call to action `.cta`.** Uppercase Open Sans 700, 20px, 2px champagne underline that turns to the text colour on hover. The mulberry variant `.cta--amora` exists for emphasis.

**Button `.btn`.** Uppercase, ice-blue ground, 1px mulberry outline, graphite text; hover fills mulberry with pearl-white text. On slate, buttons are outlined in champagne or pearl white with transparent ground.

**Diagnosis bar.** A full-width slate link with uppercase text and a champagne arrow that slides on hover.

**Quote.** A 2px champagne line on the left, 40px indent, no italics, no quotation marks.

**Dash list.** Each item takes a 10px × 2px mulberry dash instead of a bullet.

**Photo.** Always through `.foto`: desaturated and brightened (`--filtro-foto`) under a multiplied slate veil (`--veu-foto`). Never a raw photograph.

**Band.** Slate block inside the page margins with the aerial photo veiled, a 62px title, contact links and two QR codes on white cards.

**Highlight.** Light-champagne box with an uppercase message and a champagne arrow; whole box is the link and darkens to champagne on hover.

**Section label.** PT Sans Narrow 700, uppercase, mulberry, 42px on the service pages and 30px with a "01 /" numeral on Due Diligence. It is the heading of the section.

**Numbered steps.** Numeral in PT Sans Narrow 34px, mulberry, beside a 20px line; steps separated by `--linha` hairlines.

**Forms.** Single column up to 720–760px wide, fields on ice blue with a 45% graphite border, labels as small uppercase, one primary button, states "received" and "error" written in the page. No third-party embeds.

## 6. Line art

Three pencil-style illustrations (a street, a balcony, an office) and a circuit drawing are the site's imagery besides photos. They enter as masks: the PNG holds only the alpha of the stroke, and CSS gives the ground and the stroke colour from tokens. Approved pairs:

- graphite ground, pearl-white stroke (`.arte--grafite`);
- pearl-white ground, graphite stroke (`.arte--gelo`), with a champagne line on top so the block still reads on the page;
- champagne ground, mulberry stroke (`.arte--champagne`).

Art blocks sit inside the card grid beside the service cards, take the row height (min 260px), can span two rows for a portrait crop, and become 3:2 on phones. Crops are chosen per block with `mask-position`; never mirror a drawing. Circuit art on Innovation Ties is pre-tinted grey-white on slate and fades before it reaches the text.

## 7. Decorative lines

- 2px champagne line on top of every slate region and every checkbox card.
- Champagne curves in gradient (light champagne to champagne) crossing Due Diligence sections behind the content.
- A diagonal champagne thread, 2px, rotated −1.2°, separating the two service groups on Destination Services.
- The logotype curve in the footer.

Lines are the only ornament. No icons except the WhatsApp and WeChat marks on the contact page.

## 8. Logo

Official SVGs with outlined text in `img/` (also `SVG/` and `PNG-fundo-transparente/`). The file name describes the ground it sits on: `*-light.svg` on light grounds, `*-dark.svg` on dark grounds. Horizontal is the main version; stacked for square spaces; mark alone for avatar and favicon. Never recompose, tilt, shadow, recolour or place on a low-contrast photo. In the site: horizontal light at 78px in the header, horizontal dark at 146px in the footer.

## 9. Accessibility and motion

- Focus visible: 2px champagne outline, 3px offset, on every focusable element.
- Contrast as in section 2; secondary text never lighter than `--texto-secundario`.
- Real form controls, labels bound with `for`, `aria-current="page"` on the menu.
- Transitions of 0.25s on colour and border only; charts animate once, slowly, and stop under `prefers-reduced-motion`.

## 10. Languages

Home in English, Portuguese (`/pt/`), Chinese (`/zh/`) and Italian (`/it/`), with reciprocal `hreflang`. Inner pages are English for now, built so a translated copy is a folder beside them. Italian headings run longer: heroes get a smaller h1 there. Chinese drops uppercase and tracking.

## 11. Pages

| Path | What it is |
|---|---|
| `/` | Home: hero with language panel, two-column intro, service mosaic, band, team |
| `/intercultural-due-diligence/` | Long page with numbered sections, champagne curves, the diagnostic instrument, prices via Stripe links |
| `/innovation-ties/` | Slate hero with circuit art, expertise blocks, a request form |
| `/brazilian-portuguese-and-communication/` | Hero with a form beside it, illustrated folds, app screens, three courses |
| `/destination-services/` | Hero with the "How it works" panel, two groups of checkbox cards with line-art blocks, "Send us your list" closing |
| `/sao-paulo-real-estate/` | Compact dashboard of SVG charts from Central Bank data, refreshed every weekday |
| `/full-advisory/`, `/contact/` | Forms |
| `/diagnostic-confirmed/`, `/session-confirmed/` | Post-payment confirmations, noindex |

## 12. Never

- No green, no neutral grey text, no pure black.
- No rounded corners, no shadows, no thick borders, no blue-purple gradients, no emoji, no left-border cards.
- No Google Fonts, no CDN, no third-party widget: the site must load in China.
- No prices on the service pages except the Due Diligence products.
- No raw photo, no mirrored drawing, no logo recomposed.
- No hex value in markup or page CSS: everything through `var(--*)`. New colours go into `css/styles.css` with a usage note and a contrast check.
