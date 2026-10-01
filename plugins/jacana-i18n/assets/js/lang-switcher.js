(function () {
  var config = window.jacanaI18n || {};
  var cookieName = config.cookieName || 'jacana_lang';
  var cookiePath = config.cookiePath || '/';
  var currentLanguage = config.currentLanguage || 'en';
  var supportedLanguages = config.supportedLanguages || {};
  var localizedUrls = config.localizedUrls || {};

  function normalizeLanguageCode(value) {
    var raw = String(value || '').trim().toLowerCase();
    if (!raw) {
      return '';
    }
    raw = raw.replace('_', '-');
    return raw.split('-')[0];
  }

  function isSupportedLanguage(code) {
    var normalized = normalizeLanguageCode(code);
    return !!normalized && Object.prototype.hasOwnProperty.call(supportedLanguages, normalized);
  }

  function setCookie(name, value) {
    var secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = name + '=' + encodeURIComponent(value) + '; path=' + cookiePath + '; max-age=31536000; SameSite=Lax' + secure;
  }

  function normalizedComparableUrl(rawUrl) {
    try {
      var parsed = new URL(rawUrl, window.location.origin);
      parsed.hash = '';
      parsed.searchParams.delete('lang');
      var path = parsed.pathname.replace(/\/+$/, '') || '/';
      var params = parsed.searchParams.toString();
      return parsed.origin + path + (params ? '?' + params : '');
    } catch (e) {
      return '';
    }
  }

  function extractLanguageFromUrl(rawUrl) {
    try {
      var parsed = new URL(rawUrl, window.location.origin);
      var queryLang = normalizeLanguageCode(parsed.searchParams.get('lang'));
      if (isSupportedLanguage(queryLang)) {
        return queryLang;
      }

      var pathParts = parsed.pathname.split('/').filter(Boolean);
      if (pathParts.length > 0) {
        var pathLang = normalizeLanguageCode(pathParts[0]);
        if (isSupportedLanguage(pathLang)) {
          return pathLang;
        }
      }
    } catch (e) {
      return '';
    }

    return '';
  }

  function isLikelyLanguageSwitcherLink(link) {
    if (!link) {
      return false;
    }

    if (link.hasAttribute('data-jacana-lang-link') || link.hasAttribute('data-lang') || link.hasAttribute('hreflang') || link.hasAttribute('lang')) {
      return true;
    }

    if (link.closest(
      '.jacana-language-switcher, .jacana-language-menu, .cpel-switcher, .cpel-switcher__nav, '
      + '.elementor-widget-polylang-language-switcher, .pll-parent-menu-item, .pll-switcher, '
      + '.lang-item, .menu-item-language, .language-switcher, .lang-switcher'
    )) {
      return true;
    }

    var linkClass = String(link.className || '');
    if (/(lang-item|language-switch|cpel-switcher|pll-switcher|menu-item-language)/i.test(linkClass)) {
      return true;
    }

    var href = link.getAttribute('href') || '';
    return href.indexOf('lang=') !== -1;
  }

  function extractLanguageFromLink(link) {
    var attrCandidates = [
      link.getAttribute('data-jacana-lang-link'),
      link.getAttribute('data-lang'),
      link.getAttribute('hreflang'),
      link.getAttribute('lang')
    ];

    for (var i = 0; i < attrCandidates.length; i += 1) {
      var direct = normalizeLanguageCode(attrCandidates[i]);
      if (isSupportedLanguage(direct)) {
        return direct;
      }
    }

    var href = link.getAttribute('href') || '';
    var fromHref = extractLanguageFromUrl(href);
    if (isSupportedLanguage(fromHref)) {
      return fromHref;
    }

    var clickedComparable = normalizedComparableUrl(href);
    if (!clickedComparable) {
      return '';
    }

    for (var lang in localizedUrls) {
      if (!Object.prototype.hasOwnProperty.call(localizedUrls, lang)) {
        continue;
      }
      var normalizedLang = normalizeLanguageCode(lang);
      if (!isSupportedLanguage(normalizedLang)) {
        continue;
      }
      if (normalizedComparableUrl(localizedUrls[lang]) === clickedComparable) {
        return normalizedLang;
      }
    }

    return '';
  }

  // Use a single delegated listener on document so that language-switcher links
  // added dynamically (Elementor popups, lazy-loaded sections, etc.) are covered
  // without needing to re-run a setup function.
  function attachDelegatedHandler() {
    document.addEventListener('click', function (event) {
      var link = event.target.closest('a');
      if (!link) {
        return;
      }

      if (!isLikelyLanguageSwitcherLink(link)) {
        return;
      }

      var lang = extractLanguageFromLink(link);
      if (!isSupportedLanguage(lang)) {
        return;
      }

      setCookie(cookieName, lang);
      // Do NOT preventDefault — let the browser follow href normally.
    });
  }

  function syncGlobals() {
    window.jacanaConcierge = window.jacanaConcierge || {};
    window.jacanaAiExperience = window.jacanaAiExperience || {};
    window.jacanaConcierge.lang = currentLanguage;
    window.jacanaConcierge.locale = config.currentLocale || '';
    window.jacanaAiExperience.lang = currentLanguage;
    window.jacanaAiExperience.locale = config.currentLocale || '';
  }

  function installTranslator() {
    var labels = config.labels || {};
    var strings = config.strings || {};
    window.jacanaI18n = window.jacanaI18n || {};
    window.jacanaI18n.currentLanguage = currentLanguage;
    window.jacanaI18n.currentLocale = config.currentLocale || '';
    window.jacanaI18n.labels = labels;
    window.jacanaI18n.strings = strings;
    window.jacanaI18n.t = function (key, fallback) {
      if (key && Object.prototype.hasOwnProperty.call(labels, key) && labels[key]) {
        return labels[key];
      }
      return fallback || '';
    };
    window.jacanaI18n.translateText = function (source) {
      if (source && Object.prototype.hasOwnProperty.call(strings, source) && strings[source]) {
        return strings[source];
      }
      return source || '';
    };
  }

  // Remove ?lang= from the address bar after the server has already used it to
  // detect the language and set the cookie.  This keeps URLs clean for users
  // and avoids duplicate-content issues, without affecting the current render
  // (the server already processed the param on this request).
  function cleanLangQueryParam() {
    if (!window.history || !window.history.replaceState) {
      return;
    }
    try {
      var url = new URL(window.location.href);
      if (!url.searchParams.has('lang')) {
        return;
      }
      url.searchParams.delete('lang');
      var clean = url.pathname + (url.search || '') + (url.hash || '');
      window.history.replaceState(null, '', clean || '/');
    } catch (e) {
      // URL API not available (very old browser) — leave the URL as-is.
    }
  }

  document.documentElement.setAttribute('data-jacana-lang', currentLanguage);
  syncGlobals();

  installTranslator();
  cleanLangQueryParam();

  // Delegated handler is safe to attach immediately — it captures bubbled clicks
  // from any [data-jacana-lang-link] in the document, present or future.
  attachDelegatedHandler();

  window.dispatchEvent(new CustomEvent('jacana:i18n-ready', {
    detail: {
      currentLanguage: currentLanguage,
      defaultLanguage: config.defaultLanguage || 'en'
    }
  }));
})();
