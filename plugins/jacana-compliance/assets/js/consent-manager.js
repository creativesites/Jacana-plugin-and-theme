(function () {
  'use strict';

  var STORAGE_KEY   = 'jacana_consent';
  var POLICY_VER    = '1.0';
  var SHOW_DELAY_MS = 900;

  var shell    = null;
  var banner   = null;
  var prefs    = null;
  var analyticsCheck  = null;
  var marketingCheck  = null;

  /* ── Storage ─────────────────────────────────── */
  function getConsent() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return null;
      var obj = JSON.parse(raw);
      if (obj && obj.version === POLICY_VER) return obj;
      return null;
    } catch (e) {
      return null;
    }
  }

  function saveConsent(analytics, marketing) {
    var obj = {
      functional: true,
      analytics:  !!analytics,
      marketing:  !!marketing,
      timestamp:  Date.now(),
      version:    POLICY_VER,
    };
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(obj));
    } catch (e) {}
    dispatchEvent(obj);
    return obj;
  }

  function dispatchEvent(consent) {
    var ev;
    try {
      ev = new CustomEvent('jacana:consent:update', { detail: consent, bubbles: true });
    } catch (e) {
      ev = document.createEvent('CustomEvent');
      ev.initCustomEvent('jacana:consent:update', true, false, consent);
    }
    document.dispatchEvent(ev);
  }

  /* ── UI helpers ──────────────────────────────── */
  function showShell() {
    if (shell) {
      shell.hidden = false;
      showBanner();
    }
  }

  function hideBanner() {
    if (banner) banner.hidden = true;
  }

  function showBanner() {
    if (banner) banner.hidden = false;
    if (prefs)  prefs.hidden  = true;
  }

  function showPrefs() {
    if (banner) banner.hidden = true;
    if (prefs)  prefs.hidden  = false;
    if (prefs)  prefs.focus();

    var consent = getConsent();
    if (analyticsCheck) analyticsCheck.checked = consent ? !!consent.analytics : false;
    if (marketingCheck) marketingCheck.checked  = consent ? !!consent.marketing : false;
    updateToggleLabels();
  }

  function hideAll() {
    if (shell) shell.hidden = true;
  }

  function acceptAll() {
    saveConsent(true, true);
    hideAll();
  }

  function rejectNonEssential() {
    saveConsent(false, false);
    hideAll();
  }

  function savePrefs() {
    var analytics = analyticsCheck ? analyticsCheck.checked : false;
    var marketing = marketingCheck  ? marketingCheck.checked  : false;
    saveConsent(analytics, marketing);
    hideAll();
  }

  function updateToggleLabels() {
    [analyticsCheck, marketingCheck].forEach(function (input) {
      if (!input) return;
      var label = input.closest('label');
      if (!label) return;
      var stateLabel = label.querySelector('.jacana-cb-toggle-label--state');
      if (stateLabel) {
        stateLabel.textContent = input.checked ? 'On' : 'Off';
      }
      var track = label.querySelector('.jacana-cb-toggle-track');
      if (track) {
        if (input.checked) {
          track.classList.add('is-on');
          track.querySelector('.jacana-cb-toggle-thumb') && (track.querySelector('.jacana-cb-toggle-thumb').style.transform = 'translateX(18px)');
        } else {
          track.classList.remove('is-on');
          track.querySelector('.jacana-cb-toggle-thumb') && (track.querySelector('.jacana-cb-toggle-thumb').style.transform = '');
        }
      }
    });
  }

  /* ── Bootstrap ───────────────────────────────── */
  function init() {
    /* Skip banner for logged-in WP admins */
    if (document.body && document.body.classList.contains('logged-in')) return;

    shell   = document.getElementById('jacana-cb-shell');
    if (!shell) return;

    banner  = shell.querySelector('.jacana-cb-banner');
    prefs   = document.getElementById('jacana-cb-prefs');
    analyticsCheck = document.getElementById('jacana-cb-analytics');
    marketingCheck = document.getElementById('jacana-cb-marketing');

    /* Bind toggle change events */
    [analyticsCheck, marketingCheck].forEach(function (input) {
      if (input) input.addEventListener('change', updateToggleLabels);
    });

    /* Bind buttons */
    var btnAccept     = document.getElementById('jacana-cb-accept');
    var btnReject     = document.getElementById('jacana-cb-reject');
    var btnManage     = document.getElementById('jacana-cb-manage');
    var btnClose      = document.getElementById('jacana-cb-prefs-close');
    var btnSave       = document.getElementById('jacana-cb-save-prefs');
    var btnAcceptAll2 = document.getElementById('jacana-cb-accept-all-prefs');

    if (btnAccept)     btnAccept.addEventListener('click', acceptAll);
    if (btnReject)     btnReject.addEventListener('click', rejectNonEssential);
    if (btnManage)     btnManage.addEventListener('click', showPrefs);
    if (btnClose)      btnClose.addEventListener('click', showBanner);
    if (btnSave)       btnSave.addEventListener('click', savePrefs);
    if (btnAcceptAll2) btnAcceptAll2.addEventListener('click', acceptAll);

    /* Escape key closes prefs */
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && prefs && !prefs.hidden) showBanner();
    });

    /* Check stored consent */
    var stored = getConsent();
    if (!stored) {
      /* No consent yet — show banner after delay */
      setTimeout(showShell, SHOW_DELAY_MS);
    } else {
      /* Consent already given — fire event so site scripts can respond */
      dispatchEvent(stored);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
