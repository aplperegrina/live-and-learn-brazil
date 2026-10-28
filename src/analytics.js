/* Live & Learn Brazil — rastreio com Matomo (auto-hospedado em analises.liveandlearnbrazil.com)
   Um único arquivo para os três sites. O ID do site é escolhido pelo domínio:
     liveandlearnbrazil.com            → 1
     realestate.liveandlearnbrazil.com → 2
     portfolio.liveandlearnbrazil.com  → 3
   Sem cookies (disableCookies), sem nenhuma chamada a domínio de terceiros.
   Eventos: WhatsApp, e-mail, envio de formulário, links de pagamento Stripe, troca de idioma. */
(function () {
  var host = location.hostname;
  var siteId = host.indexOf('realestate.') === 0 ? '2' : host.indexOf('portfolio.') === 0 ? '3' : '1';

  var _paq = window._paq = window._paq || [];
  _paq.push(['disableCookies']);
  _paq.push(['alwaysUseSendBeacon', true]);   // eventos sobrevivem à navegação (envio de formulário, clique em link)
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function () {
    var u = "//analises.liveandlearnbrazil.com/";
    _paq.push(['setTrackerUrl', u + 'matomo.php']);
    _paq.push(['setSiteId', siteId]);
    var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
    g.async = true; g.src = u + 'matomo.js'; s.parentNode.insertBefore(g, s);
  })();

  var pagina = function () { return location.pathname; };
  var nomesIdioma = { en: 'English', pt: 'Português', 'pt-br': 'Português', zh: '中文', 'zh-cn': '中文', 'zh-hans': '中文', it: 'Italiano' };
  var nomesForm = {
    'fa-form': 'Full Advisory', 'ct-form': 'Contact', 'it-form': 'Innovation Ties',
    'finder': 'Busca de imóveis', 'g-step1': 'Guia de bairro', 'plform': 'Enquete', 'voteForm': 'Enquete', 'lista': 'Lista de serviços'
  };

  // Cliques: WhatsApp, e-mail, Stripe, troca de idioma (delegação, vale para links criados depois)
  document.addEventListener('click', function (ev) {
    var alvo = ev.target && ev.target.closest ? ev.target.closest('a[href]') : null;
    if (!alvo) return;
    var href = alvo.getAttribute('href') || '';
    if (/wa\.me|api\.whatsapp\.com|whatsapp:/i.test(href)) {
      _paq.push(['trackEvent', 'Contato', 'WhatsApp', pagina()]);
    } else if (/^mailto:/i.test(href)) {
      _paq.push(['trackEvent', 'Contato', 'Email', pagina()]);
    } else if (/stripe\.com/i.test(href)) {
      var produto = alvo.getAttribute('data-product') || alvo.getAttribute('data-produto') || (alvo.textContent || '').trim().slice(0, 80) || href;
      _paq.push(['trackEvent', 'Pagamento', 'Clique', produto]);
    } else if (alvo.hasAttribute('hreflang')) {
      var cod = (alvo.getAttribute('hreflang') || '').toLowerCase();
      _paq.push(['trackEvent', 'Idioma', 'Troca', nomesIdioma[cod] || cod]);
    }
  }, true);

  // Envio de qualquer formulário (captura: dispara mesmo quando a página envia por JS)
  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (!f || f.tagName !== 'FORM') return;
    var nome = f.getAttribute('data-matomo-nome') || nomesForm[f.id] || f.id || f.getAttribute('name') || f.className || 'formulario';
    _paq.push(['trackEvent', 'Formulário', 'Envio', nome]);
  }, true);
})();
