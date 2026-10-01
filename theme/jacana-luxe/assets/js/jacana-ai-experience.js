(function () {
  function boot() {
  var config = window.jacanaAiExperience || {};
  var i18n = window.jacanaI18n || {};
  if (!config.enabled) {
    return;
  }

  var visitorKey = localStorage.getItem('jacana_visitor_key') || '';
  var sessionKey = sessionStorage.getItem('jacana_session_key') || '';
  var railEnabled = !!config.showRail;
  var root = document.getElementById('jacana-ai-layer');
  if (!root) {
    root = document.createElement('div');
    root.id = 'jacana-ai-layer';
    root.setAttribute('aria-live', 'polite');
    document.body.appendChild(root);
  }

  var sessionCounts = {
    major: parseInt(sessionStorage.getItem('jacana_ai_major_count') || '0', 10) || 0,
    minor: parseInt(sessionStorage.getItem('jacana_ai_minor_count') || '0', 10) || 0
  };

  var state = {
    profile: null,
    bookingDraft: null,
    pageType: detectPageType(),
    service: '',
    vehicle: '',
    destination: '',
    leadCaptured: sessionStorage.getItem('jacana_ai_lead_captured') === '1',
    railExpanded: false,
    railActive: false,
    railDismissed: sessionStorage.getItem('jacana_ai_rail_dismissed') === '1',
    interactions: [],
    galleryCategoryCounts: {},
    insightShown: false
  };
  var clientErrorThrottle = {};
  var railAutoDismissTimer = null;
  var railAutoDismissMs = Math.max(5000, (parseInt(config.railAutoDismissSeconds || 14, 10) || 14) * 1000);

  function label(key, fallback) {
    if (i18n && i18n.labels && Object.prototype.hasOwnProperty.call(i18n.labels, key) && i18n.labels[key]) {
      return i18n.labels[key];
    }
    return fallback;
  }

  function getSiteLanguage() {
    var lang = String(config.lang || i18n.currentLanguage || document.documentElement.getAttribute('lang') || 'en').toLowerCase();
    if (lang.indexOf('-') !== -1) {
      lang = lang.split('-')[0];
    }
    return lang || 'en';
  }

  root.innerHTML = (railEnabled ? [
    '<div class="jacana-ai-rail" data-ai-rail hidden aria-hidden="true" style="display:none;">',
    '  <div class="jacana-ai-rail-head">',
    '    <button class="jacana-ai-rail-toggle" type="button" data-ai-rail-toggle aria-expanded="false" aria-controls="jacana-ai-rail-actions">',
    (config.chatbotIcon ? '      <div class="jacana-ai-rail-icon-wrap"><img src="' + config.chatbotIcon + '" alt="" class="jacana-ai-rail-icon"></div>' : ''),
    '      <span class="jacana-ai-rail-label">' + label('catalog.ai.rail_title', 'Jacana AI Planner') + '</span>',
    '      <span class="jacana-ai-rail-summary" data-ai-rail-summary>' + label('catalog.ai.rail_summary', 'Building your Namibia journey.') + '</span>',
    '    </button>',
    '    <button type="button" class="jacana-ai-rail-dismiss" data-ai-rail-dismiss aria-label="' + label('catalog.ai.dismiss_planner', 'Dismiss planner') + '">&times;</button>',
    '  </div>',
    '  <div class="jacana-ai-rail-actions" id="jacana-ai-rail-actions" data-ai-rail-actions hidden aria-hidden="true">',
    '    <button type="button" class="jacana-ai-mini-button" data-ai-rail-action="booking">' + label('catalog.header.start_booking', 'Start booking') + '</button>',
    '    <button type="button" class="jacana-ai-mini-button is-secondary" data-ai-rail-action="sheet">' + label('catalog.ai.refine_plan', 'Refine plan') + '</button>',
    '  </div>',
    '</div>'
  ] : []).concat([
    '<div class="jacana-ai-sheet-shell" data-ai-sheet-shell hidden>',
    '  <div class="jacana-ai-sheet-backdrop" data-ai-close></div>',
    '  <section class="jacana-ai-sheet" data-ai-sheet role="dialog" aria-modal="true" aria-label="Jacana AI concierge"></section>',
    '</div>'
  ]).join('');

  var rail = root.querySelector('[data-ai-rail]');
  var railSummary = root.querySelector('[data-ai-rail-summary]');
  var railActions = root.querySelector('[data-ai-rail-actions]');
  var sheetShell = root.querySelector('[data-ai-sheet-shell]');
  var sheet = root.querySelector('[data-ai-sheet]');

  function safeJsonParse(value, fallback) {
    try {
      return JSON.parse(value);
    } catch (error) {
      return fallback;
    }
  }

  function reportAiError(payload) {
    if (!config.aiLogEndpoint) {
      return;
    }
    payload = payload || {};
    var key = [
      payload.source || 'ai_experience',
      payload.endpoint || '',
      payload.status || '',
      payload.message || ''
    ].join('|').slice(0, 280);
    var now = Date.now();
    if (clientErrorThrottle[key] && now - clientErrorThrottle[key] < 4000) {
      return;
    }
    clientErrorThrottle[key] = now;

    fetch(config.aiLogEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        visitor_key: visitorKey,
        session_key: sessionKey,
        source: payload.source || 'ai_experience',
        level: payload.level || 'error',
        message: payload.message || 'frontend_error',
        endpoint: payload.endpoint || '',
        status: payload.status || 0,
        page_url: window.location.href,
        meta: payload.meta || {}
      })
    }).catch(function () {});
  }

  function loadBookingDraft() {
    var draft = safeJsonParse(localStorage.getItem('jacana_booking_draft') || '{}', {});
    return draft && typeof draft === 'object' ? draft : {};
  }

  function saveBookingDraft(partial) {
    var current = state.bookingDraft && typeof state.bookingDraft === 'object' ? state.bookingDraft : {};
    var merged = Object.assign({}, current, partial || {});
    if (Array.isArray(current.selected_services) || Array.isArray((partial || {}).selected_services)) {
      var selected = []
        .concat(Array.isArray(current.selected_services) ? current.selected_services : [])
        .concat(Array.isArray((partial || {}).selected_services) ? partial.selected_services : []);
      merged.selected_services = Array.from(new Set(selected.filter(Boolean)));
    }
    state.bookingDraft = merged;
    localStorage.setItem('jacana_booking_draft', JSON.stringify(merged));
    return merged;
  }

  state.bookingDraft = loadBookingDraft();

  function detectPageType() {
    var path = window.location.pathname || '/';
    if (path.indexOf('/booking') !== -1) {
      return 'booking';
    }
    if (path.indexOf('/car-rental') !== -1 || path.indexOf('/car-rentals') !== -1) {
      return 'car_rentals';
    }
    if (path.indexOf('/services') !== -1) {
      return 'services';
    }
    if (path.indexOf('/gallery') !== -1) {
      return 'gallery';
    }
    if (path.indexOf('/about') !== -1) {
      return 'about';
    }
    if (path.indexOf('/tailor') !== -1 || path.indexOf('/tour') !== -1) {
      return 'tours';
    }
    if (path.indexOf('/contact') !== -1) {
      return 'contact';
    }
    return 'general';
  }

  function saveCounts() {
    sessionStorage.setItem('jacana_ai_major_count', String(sessionCounts.major));
    sessionStorage.setItem('jacana_ai_minor_count', String(sessionCounts.minor));
  }

  function canShowMajor() {
    return !state.leadCaptured && sessionCounts.major < (config.maxMajorPrompts || 1) && !config.isBookingPage;
  }

  function canShowMinor() {
    return !state.leadCaptured && sessionCounts.minor < (config.maxMinorPrompts || 2);
  }

  function updateJourneySummary() {
    if (!railSummary) {
      return;
    }
    var parts = [];
    if (state.service) {
      parts.push(state.service.replace(/_/g, ' '));
    }
    if (state.vehicle) {
      parts.push(state.vehicle);
    }
    if (state.destination) {
      parts.push(state.destination);
    }
    railSummary.textContent = parts.length ? 'Focused on ' + parts.join(' / ') + '.' : 'Building your Namibia journey.';
  }

  function clearRailAutoDismissTimer() {
    if (!railEnabled || !rail) {
      return;
    }
    if (railAutoDismissTimer) {
      window.clearTimeout(railAutoDismissTimer);
      railAutoDismissTimer = null;
    }
  }

  function scheduleRailAutoDismiss() {
    if (!railEnabled || !rail) {
      return;
    }
    if (state.railDismissed || !state.railActive) {
      return;
    }
    clearRailAutoDismissTimer();
    railAutoDismissTimer = window.setTimeout(function () {
      if (state.railDismissed || !state.railActive) {
        return;
      }
      if (!sheetShell.hidden || rail.classList.contains('is-expanded')) {
        scheduleRailAutoDismiss();
        return;
      }
      dismissRail(false);
    }, railAutoDismissMs);
  }

  function dismissRail(persist) {
    if (!railEnabled || !rail) {
      return;
    }
    var toggle = root.querySelector('[data-ai-rail-toggle]');
    clearRailAutoDismissTimer();
    state.railActive = false;
    rail.hidden = true;
    rail.setAttribute('aria-hidden', 'true');
    rail.style.display = 'none';
    rail.classList.remove('is-expanded');
    if (toggle) {
      toggle.setAttribute('aria-expanded', 'false');
    }
    if (railActions) {
      railActions.hidden = true;
      railActions.setAttribute('aria-hidden', 'true');
    }
    if (persist) {
      state.railDismissed = true;
      sessionStorage.setItem('jacana_ai_rail_dismissed', '1');
    }
  }

  function activateRail() {
    if (!railEnabled || !rail) {
      return;
    }
    if (state.railDismissed) {
      return;
    }
    var toggle = root.querySelector('[data-ai-rail-toggle]');
    state.railActive = true;
    rail.hidden = false;
    rail.setAttribute('aria-hidden', 'false');
    rail.style.display = 'grid';
    rail.classList.remove('is-expanded');
    if (toggle) {
      toggle.setAttribute('aria-expanded', 'false');
    }
    if (railActions) {
      railActions.hidden = true;
      railActions.setAttribute('aria-hidden', 'true');
    }
    scheduleRailAutoDismiss();
  }

  function inferServiceFromContext(context) {
    context = context || {};
    if (context.service) {
      return context.service;
    }
    if (state.service) {
      return state.service;
    }
    if (context.vehicle || state.vehicle || state.pageType === 'car_rentals') {
      return 'car_rental';
    }
    if (context.destination || state.destination || state.pageType === 'gallery' || state.pageType === 'tours') {
      return 'tailor_made';
    }
    if (state.pageType === 'services') {
      return 'tailor_made';
    }
    return 'general_booking';
  }

  function normalizeContext(context) {
    context = context || {};
    return {
      service: inferServiceFromContext(context),
      vehicle: context.vehicle || state.vehicle || '',
      destination: context.destination || state.destination || '',
      widgetName: context.widgetName || '',
      sourceSection: context.sourceSection || '',
      sourceNode: context.sourceNode || null
    };
  }

  function getSectionLabel(node) {
    var section = node && node.closest ? node.closest('section, article, .card, .elementor-widget, .elementor-container') : null;
    if (!section || !section.querySelector) {
      return '';
    }
    var heading = section.querySelector('h1, h2, h3');
    return heading ? trimText(heading.textContent, 120) : '';
  }

  function extractContextFromNode(node) {
    var context = {
      service: '',
      vehicle: '',
      destination: '',
      widgetName: '',
      sourceNode: node || null
    };
    var current = node;
    while (current && current !== document.body) {
      if (!context.service) {
        context.service = current.getAttribute && (current.getAttribute('data-jacana-service') || current.getAttribute('data-ai-chip-service') || current.getAttribute('data-service-name')) || '';
      }
      if (!context.vehicle) {
        context.vehicle = current.getAttribute && current.getAttribute('data-jacana-vehicle') || '';
      }
      if (!context.destination) {
        context.destination = current.getAttribute && (current.getAttribute('data-jacana-destination') || current.getAttribute('data-title')) || '';
      }
      if (!context.widgetName) {
        context.widgetName = current.getAttribute && (current.getAttribute('data-jacana-widget') || current.getAttribute('data-ai-chip-widget')) || '';
      }
      current = current.parentElement;
    }
    context.sourceSection = getSectionLabel(node);
    if (!context.destination) {
      var panelTitle = document.querySelector('[data-map-title-overlay]');
      if (panelTitle) {
        var titleText = panelTitle.textContent.trim();
        if (titleText && titleText.toLowerCase() !== 'select a destination') {
          context.destination = titleText;
        }
      }
    }
    return normalizeContext(context);
  }

  function isModifiedClick(event) {
    return !!(event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0);
  }

  function isBookingLink(node) {
    if (!node || !node.getAttribute) {
      return false;
    }
    return node.hasAttribute('data-jacana-booking-modal');
  }

  function renderSuccessState(options) {
    options = options || {};
    var actions = [];
    if (options.showBooking !== false) {
      actions.push('    <button type="button" class="jacana-ai-mini-button" data-ai-success-booking>' + escapeHtml(options.primaryLabel || 'Open another booking') + '</button>');
    }
    actions.push('    <button type="button" class="jacana-ai-mini-button is-secondary" data-ai-close>' + escapeHtml(options.secondaryLabel || 'Continue browsing') + '</button>');
    return [
      '<div class="jacana-ai-sheet-head jacana-ai-success-head">',
      '  <span class="jacana-ai-sheet-kicker">' + escapeHtml(options.kicker || 'Request received') + '</span>',
      '  <button type="button" class="jacana-ai-close" data-ai-close aria-label="Close">&times;</button>',
      '  <h3>' + escapeHtml(options.title || 'Jacana has your details') + '</h3>',
      '  <p>' + escapeHtml(options.body || 'We have queued your request and will follow up shortly.') + '</p>',
      '  <div class="jacana-ai-inline-actions">',
      actions.join(''),
      '  </div>',
      '</div>'
    ].join('');
  }

  function rememberInteraction(type, payload) {
    state.interactions.push({
      type: type,
      payload: payload || {},
      at: new Date().toISOString()
    });
    if (state.interactions.length > 18) {
      state.interactions = state.interactions.slice(-18);
    }
  }

  function trimText(value, limit) {
    var text = String(value || '').replace(/\s+/g, ' ').trim();
    if (!text) {
      return '';
    }
    if (!limit || text.length <= limit) {
      return text;
    }
    return text.slice(0, limit) + '...';
  }

  function extractSectionContext(context) {
    var sourceNode = context && context.sourceNode ? context.sourceNode : null;
    var section = sourceNode && sourceNode.closest ? sourceNode.closest('section, article, .card, .elementor-widget, .elementor-section, .elementor-container') : null;
    if (!section) {
      section = document.querySelector('main section, .elementor section, body');
    }
    var titleNode = section && section.querySelector ? section.querySelector('h1, h2, h3') : null;
    var text = section && 'innerText' in section ? section.innerText : '';
    return {
      title: titleNode ? trimText(titleNode.textContent, 120) : '',
      text: trimText(text, config.contextCharLimit || 900)
    };
  }

  function getAccommodationOptions(context) {
    var sourceNode = context && context.sourceNode ? context.sourceNode : null;
    var section = sourceNode && sourceNode.closest ? sourceNode.closest('.jacana-accommodation-styles') : null;
    if (!section) {
      section = document.querySelector('.jacana-accommodation-styles');
    }
    var options = [];
    if (!section) {
      return options;
    }
    section.querySelectorAll('.jacana-accom-card-title').forEach(function (titleNode, index) {
      var label = trimText(titleNode.textContent, 80);
      if (!label) {
        return;
      }
      var card = titleNode.closest('.jacana-accom-card');
      var descriptionNode = card ? card.querySelector('.jacana-accom-card-desc') : null;
      options.push({
        label: label,
        value: 'style_' + (index + 1),
        description: trimText(descriptionNode ? descriptionNode.textContent : '', 140)
      });
    });
    return options;
  }

  function getPlannerFallback(kind, context) {
    var fallback = {
      title: 'Shape your next step',
      description: 'Answer a couple of questions and Jacana will guide you to the right booking path.',
      questionOneTitle: 'What matters most right now?',
      questionTwoTitle: 'What style should Jacana optimize for?',
      stepOneOptions: [],
      stepTwoOptions: [],
      resultTitle: trimText(context.service || context.vehicle || context.destination || 'Personalized guidance ready', 100),
      resultBody: 'Jacana now has enough context to guide you to booking, a callback request, or a direct chat.',
      bookingLabel: 'Continue to booking',
      callbackLabel: 'Request callback',
      chatLabel: 'Ask Jana'
    };

    var isAccommodation = context.widgetName === 'jacana_accommodation_styles' || context.service === 'accommodation';
    if (isAccommodation) {
      fallback.title = 'Match the right stay style';
      fallback.description = 'Choose the accommodation mood that fits your Namibia route. Jacana will shape the next step around it.';
      fallback.questionOneTitle = 'Which accommodation style feels closest to your trip?';
      fallback.questionTwoTitle = 'What should Jacana optimize after that?';
      fallback.stepOneOptions = getAccommodationOptions(context);
      fallback.stepTwoOptions = [
        { label: 'Comfort and privacy', value: 'comfort_privacy' },
        { label: 'Balanced value', value: 'balanced_value' },
        { label: 'Adventure and camping', value: 'adventure_camping' }
      ];
      fallback.resultTitle = 'Accommodation guidance ready';
      return fallback;
    }

    if (context.widgetName === 'jacana_tailor_made_story' || context.service === 'tailor_made') {
      fallback.title = 'Start shaping the journey';
      fallback.description = 'Jacana can qualify the route quickly before opening a longer conversation.';
      fallback.stepOneOptions = [
        { label: 'A full tailor-made route', value: 'full_route' },
        { label: 'A specific destination or stop', value: 'single_destination' },
        { label: 'A fast quote to compare options', value: 'quote' }
      ];
      fallback.stepTwoOptions = [
        { label: 'Private and premium', value: 'premium' },
        { label: 'Balanced comfort', value: 'balanced' },
        { label: 'Adventure and flexibility', value: 'flexible' }
      ];
      return fallback;
    }

    if (kind === 'inclusions') {
      fallback.title = 'Choose the right tour setup';
      fallback.description = 'Jacana can help you decide between guided support and self-drive flexibility without opening a full chat.';
      fallback.questionOneTitle = 'Which setup feels closer to your trip?';
      fallback.questionTwoTitle = 'What should Jacana optimize next?';
      fallback.stepOneOptions = [
        { label: 'Guided support', value: 'guided' },
        { label: 'Self-drive freedom', value: 'self_drive' },
        { label: 'Need help comparing both', value: 'compare' }
      ];
      fallback.stepTwoOptions = [
        { label: 'Logistics and ease', value: 'ease' },
        { label: 'Budget control', value: 'budget' },
        { label: 'Wildlife access', value: 'wildlife' }
      ];
      return fallback;
    }

    if (kind === 'faq') {
      fallback.title = 'Find the answer that matters';
      fallback.description = 'Jacana can narrow the next answer or move you straight into planning if the FAQ is no longer enough.';
      fallback.questionOneTitle = 'What do you need right now?';
      fallback.questionTwoTitle = 'How should Jacana help next?';
      fallback.stepOneOptions = [
        { label: 'Travel planning answer', value: 'planning' },
        { label: 'A practical requirement', value: 'requirement' },
        { label: 'Need direct trip advice', value: 'direct_advice' }
      ];
      fallback.stepTwoOptions = [
        { label: 'Show the right answer', value: 'faq_answer' },
        { label: 'Move to booking', value: 'booking' },
        { label: 'Escalate to chat', value: 'chat' }
      ];
      return fallback;
    }

    if (kind === 'expert') {
      fallback.title = 'Bring the right Jacana expert in';
      fallback.description = 'This keeps the first step focused so the visitor only gets the next best action.';
      fallback.questionOneTitle = 'What do you need expert help with?';
      fallback.questionTwoTitle = 'How would you like Jacana to help?';
      fallback.stepOneOptions = [
        { label: 'Tailor-made route advice', value: 'route_advice' },
        { label: 'Vehicle and logistics help', value: 'logistics' },
        { label: 'Safari and wildlife guidance', value: 'safari' }
      ];
      fallback.stepTwoOptions = [
        { label: 'Quick booking handoff', value: 'booking' },
        { label: 'Request a callback', value: 'callback' },
        { label: 'Open chat', value: 'chat' }
      ];
      return fallback;
    }

    if (kind === 'vehicle') {
      fallback.title = 'Match the right rental vehicle';
      fallback.description = 'This keeps the advice in-page. The full chat is only there if you need custom route planning.';
      fallback.stepOneOptions = [
        { label: 'Mostly road driving', value: 'road' },
        { label: 'Mixed 4x4 route', value: 'mixed_4x4' },
        { label: 'Camping-first route', value: 'camping' }
      ];
      fallback.stepTwoOptions = [
        { label: '1-2 travelers', value: '1_2' },
        { label: '3-4 travelers', value: '3_4' },
        { label: '5+ travelers', value: '5_plus' }
      ];
      return fallback;
    }

    if (kind === 'destination') {
      fallback.title = 'Plan this destination into your route';
      fallback.description = 'Keep the planning tight and relevant to the current highlight.';
      fallback.stepOneOptions = [
        { label: 'Wildlife first', value: 'wildlife' },
        { label: 'Scenic route', value: 'scenic' },
        { label: 'Luxury stay nearby', value: 'luxury_nearby' }
      ];
      fallback.stepTwoOptions = [
        { label: 'Self-drive', value: 'self_drive' },
        { label: 'Guided', value: 'guided' },
        { label: 'Need advice', value: 'need_advice' }
      ];
      return fallback;
    }

    fallback.stepOneOptions = [
      { label: 'Need a full route', value: 'full_route' },
      { label: 'Need one service only', value: 'single_service' },
      { label: 'Need a fast quote', value: 'quote' }
    ];
    fallback.stepTwoOptions = [
      { label: 'Luxury / premium', value: 'luxury' },
      { label: 'Balanced comfort', value: 'balanced' },
      { label: 'Practical / budget', value: 'practical' }
    ];
    return fallback;
  }

  function requestPlannerBlueprint(kind, context, fallback) {
    if (!config.aiEndpoint) {
      return Promise.resolve(fallback);
    }

    var section = extractSectionContext(context);
    var promptLines = [
      config.prompts && config.prompts.plannerSystem ? config.prompts.plannerSystem : '',
      context.service === 'accommodation' && config.prompts && config.prompts.accommodationPlanner ? config.prompts.accommodationPlanner : '',
      kind === 'vehicle' && config.prompts && config.prompts.vehiclePlanner ? config.prompts.vehiclePlanner : '',
      (kind === 'planner' || context.service === 'tailor_made') && config.prompts && config.prompts.tailorPlanner ? config.prompts.tailorPlanner : '',
      'Current site language: ' + getSiteLanguage() + '. Return all visible copy in that language.',
      'Return JSON only.',
      'Schema: {"title":"","description":"","questionOneTitle":"","questionTwoTitle":"","stepOneOptions":[{"label":"","value":"","description":""}],"stepTwoOptions":[{"label":"","value":"","description":""}],"resultTitle":"","resultBody":"","bookingLabel":"","callbackLabel":"","chatLabel":""}',
      'Keep labels concise and factual.',
      'Use only the active section context. Do not suggest unrelated options.',
      'Context:',
      JSON.stringify({
        pageType: state.pageType,
        pageTitle: config.pageTitle || document.title || '',
        pageUrl: window.location.href,
        widgetName: context.widgetName || '',
        service: context.service || '',
        vehicle: context.vehicle || '',
        destination: context.destination || '',
        sectionTitle: section.title,
        sectionText: section.text,
        recentInteractions: state.interactions.slice(-8),
        bookingDraft: state.bookingDraft,
        knownProfile: state.profile,
        fallback: fallback
      })
    ];

    return fetchJson(config.aiEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ prompt: promptLines.join('\n') })
    }, { source: 'ai_experience_planner' }).then(function (response) {
      var raw = response && response.data && response.data.candidates && response.data.candidates[0] && response.data.candidates[0].content && response.data.candidates[0].content.parts && response.data.candidates[0].content.parts[0] && response.data.candidates[0].content.parts[0].text;
      if (!raw) {
        return fallback;
      }
      try {
        var parsed = JSON.parse(String(raw).replace(/^```json\s*/i, '').replace(/```$/, '').trim());
        parsed = parsed && typeof parsed === 'object' ? parsed : {};
        parsed.stepOneOptions = Array.isArray(parsed.stepOneOptions) && parsed.stepOneOptions.length ? parsed.stepOneOptions : fallback.stepOneOptions;
        parsed.stepTwoOptions = Array.isArray(parsed.stepTwoOptions) && parsed.stepTwoOptions.length ? parsed.stepTwoOptions : fallback.stepTwoOptions;
        parsed.title = parsed.title || fallback.title;
        parsed.description = parsed.description || fallback.description;
        parsed.questionOneTitle = parsed.questionOneTitle || fallback.questionOneTitle;
        parsed.questionTwoTitle = parsed.questionTwoTitle || fallback.questionTwoTitle;
        parsed.resultTitle = parsed.resultTitle || fallback.resultTitle;
        parsed.resultBody = parsed.resultBody || fallback.resultBody;
        parsed.bookingLabel = parsed.bookingLabel || fallback.bookingLabel;
        parsed.callbackLabel = parsed.callbackLabel || fallback.callbackLabel;
        parsed.chatLabel = parsed.chatLabel || fallback.chatLabel;
        if (context.widgetName === 'jacana_accommodation_styles' || context.service === 'accommodation') {
          parsed.title = fallback.title;
          parsed.description = fallback.description;
          parsed.questionOneTitle = fallback.questionOneTitle;
          parsed.stepOneOptions = fallback.stepOneOptions;
        }
        return parsed;
      } catch (error) {
        return fallback;
      }
    }).catch(function () {
      return fallback;
    });
  }

  function fetchJson(url, options, meta) {
    meta = meta || {};
    return fetch(url, options || {}).then(function (response) {
      return response.text().then(function (raw) {
        var data = {};
        if (raw) {
          try {
            data = JSON.parse(raw);
          } catch (parseError) {
            reportAiError({
              source: meta.source || 'ai_experience',
              message: 'json_parse_error',
              endpoint: url,
              status: response.status,
              meta: {
                parse_error: String((parseError && parseError.message) || parseError || ''),
                excerpt: String(raw).slice(0, 260)
              }
            });
          }
        }

        if (!response.ok) {
          reportAiError({
            source: meta.source || 'ai_experience',
            message: 'http_error',
            endpoint: url,
            status: response.status,
            meta: { response: data }
          });
          if (data && typeof data === 'object') {
            data.__jacanaLogged = true;
            throw data;
          }
          throw { __jacanaLogged: true, error: 'http_error', status: response.status };
        }
        return data;
      });
    }).catch(function (error) {
      if (!(error && error.__jacanaLogged)) {
        reportAiError({
          source: meta.source || 'ai_experience',
          message: 'network_error',
          endpoint: url,
          status: 0,
          meta: { error: String((error && error.message) || error || '') }
        });
      }
      throw error;
    });
  }

  function trackTouchpoint(data) {
    rememberInteraction(data.componentType || 'surface', {
      componentKey: data.componentKey || '',
      widgetName: data.widgetName || '',
      serviceInterest: data.serviceInterest || state.service || '',
      actionType: data.actionType || 'view',
      payload: data.payload || {}
    });
    if (!config.trackEndpoint || !visitorKey || !sessionKey) {
      return Promise.resolve();
    }
    return fetchJson(config.trackEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        visitor_key: visitorKey,
        session_key: sessionKey,
        component_type: data.componentType || 'surface',
        component_key: data.componentKey || '',
        page_url: window.location.href,
        widget_name: data.widgetName || '',
        service_interest: data.serviceInterest || state.service || '',
        action_type: data.actionType || 'view',
        payload: data.payload || {}
      })
    }, { source: 'ai_experience_touchpoint' }).catch(function () {});
  }

  function trackConversion(stage, extra) {
    rememberInteraction('conversion_signal', {
      stage: stage,
      extra: extra || {}
    });
    if (!config.conversionEndpoint || !visitorKey || !sessionKey) {
      return Promise.resolve();
    }
    var payload = Object.assign({
      visitor_key: visitorKey,
      session_key: sessionKey,
      stage: stage,
      service: state.service || '',
      label: config.pageTitle || document.title || 'AI Planner',
      page_url: window.location.href,
      meta: {
        vehicle: state.vehicle,
        destination: state.destination,
        page_type: state.pageType
      }
    }, extra || {});
    return fetchJson(config.conversionEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }, { source: 'ai_experience_conversion' }).catch(function () {});
  }

  function openChat(message) {
    window.dispatchEvent(new CustomEvent('jacana:open-concierge', {
      detail: {
        message: message || label('catalog.ai.default_chat_request', 'I need help planning my Namibia journey.'),
        skipPicker: true
      }
    }));
  }

  function closeSheet() {
    sheetShell.hidden = true;
    sheet.innerHTML = '';
    window.dispatchEvent(new CustomEvent('jacana:ai-surface-close'));
    scheduleRailAutoDismiss();
  }

  function openSheet(content, opts) {
    if (!sheetShell || !sheet) {
      return;
    }
    if (opts && opts.major) {
      sessionCounts.major += 1;
      saveCounts();
    }
    sheet.innerHTML = content;
    sheetShell.hidden = false;
    window.dispatchEvent(new CustomEvent('jacana:ai-surface-open', { detail: opts || {} }));
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>\"]/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[char] || char;
    });
  }

  function getProfile() {
    if (!config.profileEndpoint || !visitorKey || state.profile) {
      return Promise.resolve(state.profile);
    }
    return fetchJson(config.profileEndpoint + '?visitor_key=' + encodeURIComponent(visitorKey) + '&session_key=' + encodeURIComponent(sessionKey), {}, { source: 'ai_experience_profile' })
      .then(function (response) {
        state.profile = response.profile || null;
        if (state.profile && state.profile.lead) {
          saveBookingDraft(Object.assign({}, state.profile.lead, {
            service_interest: state.profile.lead.service_interest || state.bookingDraft.service_interest || '',
            selected_services: state.profile.lead.service_interest ? [state.profile.lead.service_interest] : (state.bookingDraft.selected_services || [])
          }));
        }
        return state.profile;
      })
      .catch(function () {
        return null;
      });
  }

  function requestInsight(context) {
    if (!config.aiEndpoint || !visitorKey || !canShowMinor()) {
      return Promise.resolve(null);
    }

    var prompt = [
      'You are generating a premium in-page travel planning prompt for Jacana Safaris & Tours.',
      'Current site language: ' + getSiteLanguage() + '. Return all visible copy in that language.',
      'Return JSON only with keys: title, body, ctaLabel, ctaAction, chipLabel.',
      'Keep the body under 28 words.',
      'Prefer helpful, low-pressure guidance.',
      'Context:',
      JSON.stringify(context)
    ].join('\n');

    return fetchJson(config.aiEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ prompt: prompt })
    }, { source: 'ai_experience_insight' }).then(function (response) {
      var raw = response && response.data && response.data.candidates && response.data.candidates[0] && response.data.candidates[0].content && response.data.candidates[0].content.parts && response.data.candidates[0].content.parts[0] && response.data.candidates[0].content.parts[0].text;
      if (!raw) {
        return null;
      }
      try {
        return JSON.parse(String(raw).replace(/^```json\s*/i, '').replace(/```$/, '').trim());
      } catch (error) {
        return null;
      }
    }).catch(function () {
      return null;
    });
  }

  var NAMIBIA_FACTS = [
    { icon: '🌍', label: 'Oldest desert', text: 'The Namib Desert is 55 million years old — the world\'s oldest, receiving virtually no rainfall.' },
    { icon: '🦁', label: 'Etosha wildlife', text: '114 mammal species call Etosha home, including the endangered black rhino.' },
    { icon: '🌟', label: 'Darkest skies', text: 'NamibRand is one of the world\'s few IDA Gold Tier Dark Sky Reserves — outstanding stargazing.' },
    { icon: '🦒', label: 'Cheetah capital', text: 'Namibia has the world\'s largest free-roaming cheetah population — over 3,000 individuals.' },
    { icon: '🏜️', label: 'Record dunes', text: 'Dune 45 rises 170m above the valley floor and is one of the most photographed dunes on Earth.' },
    { icon: '🛻', label: 'Self-drive tip', text: 'Over 90% of Namibia\'s roads are untarred — a 4×4 is highly recommended for national parks.' },
    { icon: '🌿', label: 'Conservation first', text: 'Namibia was the first country to enshrine environmental protection in its constitution (1990).' },
    { icon: '👥', label: 'Rich heritage', text: 'Over 13 ethnic groups call Namibia home. The San people have lived here for more than 20,000 years.' },
    { icon: '🐘', label: 'Desert elephants', text: 'Damaraland\'s desert-adapted elephants walk up to 70km daily in search of water.' },
    { icon: '🏔️', label: 'Brandberg peak', text: 'At 2,573m, Brandberg is Namibia\'s highest peak and home to the famous White Lady rock painting.' },
    { icon: '☀️', label: 'Sun country', text: 'Namibia enjoys over 300 days of sunshine per year — one of the sunniest countries on Earth.' },
    { icon: '🐋', label: 'Marine wonder', text: 'Walvis Bay hosts Africa\'s largest pelican colony and is a top whale-watching destination.' },
    { icon: '🎨', label: 'Ancient rock art', text: 'Twyfelfontein\'s 2,500+ San engravings are a UNESCO World Heritage Site dating back thousands of years.' },
    { icon: '🦅', label: 'Birding paradise', text: 'Over 700 bird species have been recorded in Namibia, including several Namib-endemic species.' },
    { icon: '🌅', label: 'Best season', text: 'May–October is dry season — wildlife gathers at waterholes for spectacular, predictable game viewing.' },
    { icon: '✈️', label: 'Fly-in safari', text: 'Fly-in safaris save 2–3 days of driving. Namibia has over 500 registered private airstrips.' },
    { icon: '💎', label: 'Diamond territory', text: 'The Sperrgebiet (Forbidden Zone) was closed to the public for a century due to vast diamond deposits.' },
    { icon: '🦓', label: 'Two zebra species', text: 'Etosha hosts both Plains and Mountain zebra — the Mountain zebra is endemic to southern Africa.' },
    { icon: '🌊', label: 'Skeleton Coast', text: 'Named for whale bones once lining its shores, the Skeleton Coast stretches over 500km of wild coastline.' },
    { icon: '🌙', label: 'Night magic', text: 'Sossusvlei is one of the few places on Earth where the Southern Cross and Milky Way share the same sky.' },
  ];

  function getRandomFact() {
    return NAMIBIA_FACTS[Math.floor(Math.random() * NAMIBIA_FACTS.length)];
  }

  function fallbackInsight() {
    var defaults = {
      car_rentals: {
        title: 'Choose the right vehicle first',
        body: 'Tell Jacana your route, group size, and travel style — the planner will match you to the best-fit vehicle before you book.',
        ctaLabel: 'Find my vehicle',
        ctaAction: 'vehicle',
        chipLabel: 'Vehicle match'
      },
      services: {
        title: 'Combine services intelligently',
        body: 'Airport transfers, accommodation, and car rental work best when planned as one seamless route.',
        ctaLabel: 'Build my mix',
        ctaAction: 'service',
        chipLabel: 'Best combination'
      },
      accommodation: {
        title: 'Match your stay to your style',
        body: 'From luxury tented camps to self-catering lodges — tell Jacana your budget and we\'ll narrow it down fast.',
        ctaLabel: 'Match my stay',
        ctaAction: 'accommodation',
        chipLabel: 'Stay matcher'
      },
      tours: {
        title: 'Not sure which tour fits?',
        body: 'Answer two quick questions and Jacana will point you to the right itinerary for your group and budget.',
        ctaLabel: 'Find my tour',
        ctaAction: 'planner',
        chipLabel: 'Tour guide'
      },
      destination: {
        title: 'Explore before you book',
        body: 'Jacana can walk you through each destination — wildlife, roads, best months, and hidden highlights.',
        ctaLabel: 'Explore destinations',
        ctaAction: 'destination',
        chipLabel: 'Destination guide'
      }
    };
    var match = defaults[state.pageType] || defaults.tours;
    return Object.assign({}, match);
  }

  function renderInsightCard(target, insight) {
    if (!target || !insight || !canShowMinor() || state.insightShown) {
      return;
    }

    var dismissKey = 'jacana_ai_insight_dismissed_' + (state.pageType || 'generic');
    if (sessionStorage.getItem(dismissKey) === '1') {
      return;
    }

    var fact = getRandomFact();
    var widgetName = target.closest('[class]') ? target.closest('[class]').className.split(' ')[0] : '';

    var card = document.createElement('aside');
    card.className = 'jacana-ai-inline-card';
    card.setAttribute('role', 'complementary');
    card.setAttribute('aria-label', 'Travel planning insight');
    card.innerHTML = [
      '<div class="jacana-ai-card-head">',
      '  <span class="jacana-ai-inline-chip">' + escapeHtml(insight.chipLabel || 'Jacana AI') + '</span>',
      '  <button type="button" class="jacana-ai-card-dismiss" data-ai-card-dismiss aria-label="Dismiss this insight">&times;</button>',
      '</div>',
      '<div class="jacana-ai-card-body">',
      '  <h3>' + escapeHtml(insight.title || 'Need help choosing?') + '</h3>',
      '  <p>' + escapeHtml(insight.body || '') + '</p>',
      '  <div class="jacana-ai-inline-actions">',
      '    <button type="button" class="jacana-ai-mini-button" data-ai-inline-cta="primary">' + escapeHtml(insight.ctaLabel || 'Continue') + '</button>',
      '    <button type="button" class="jacana-ai-mini-button is-secondary" data-ai-inline-cta="chat">Ask Jana</button>',
      '  </div>',
      '</div>',
      '<div class="jacana-ai-card-fact" aria-label="Namibia travel fact">',
      '  <span class="jacana-ai-card-fact-icon" aria-hidden="true">' + fact.icon + '</span>',
      '  <p><span class="jacana-ai-card-fact-label">' + escapeHtml(fact.label) + '</span>' + escapeHtml(fact.text) + '</p>',
      '</div>'
    ].join('');

    target.insertAdjacentElement('afterend', card);
    state.insightShown = true;
    sessionCounts.minor += 1;
    saveCounts();

    trackTouchpoint({
      componentType: 'insight_card',
      componentKey: insight.ctaAction || 'planner',
      widgetName: widgetName,
      serviceInterest: state.service,
      actionType: 'render'
    });

    card.addEventListener('click', function (event) {
      var button = event.target.closest('[data-ai-inline-cta], [data-ai-card-dismiss]');
      if (!button) return;

      if (button.hasAttribute('data-ai-card-dismiss')) {
        sessionStorage.setItem(dismissKey, '1');
        card.style.transition = 'opacity 240ms ease, transform 240ms ease';
        card.style.opacity = '0';
        card.style.transform = 'translateY(8px) scale(0.97)';
        setTimeout(function () { card.remove(); }, 250);
        trackTouchpoint({ componentType: 'insight_card', componentKey: 'dismiss', widgetName: widgetName, actionType: 'dismiss' });
        return;
      }

      if (button.getAttribute('data-ai-inline-cta') === 'chat') {
        openChat('I would like help planning the best Namibia option for me.');
        return;
      }

      openPlanner(insight.ctaAction || 'planner', { widgetName: widgetName });
    });
  }

  function renderChipBar(container, chips) {
    if (!container || !chips || !chips.length || container.querySelector('.jacana-ai-chip-bar')) {
      return;
    }

    var firstChip = chips[0] || {};
    var widgetKey = firstChip.widgetName || 'generic';
    var dismissalKey = 'jacana_ai_chips_dismissed_' + widgetKey;

    if (sessionStorage.getItem(dismissalKey) === '1') {
      return;
    }

    var bar = document.createElement('div');
    bar.className = 'jacana-ai-chip-bar';
    bar.setAttribute('role', 'group');
    bar.setAttribute('aria-label', 'Quick planning options');

    var barLabel = document.createElement('span');
    barLabel.className = 'jacana-ai-chip-bar-label';
    barLabel.textContent = 'Quick help';
    barLabel.setAttribute('aria-hidden', 'true');
    bar.appendChild(barLabel);

    chips.forEach(function (chip) {
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'jacana-ai-chip';
      button.textContent = chip.label;
      button.setAttribute('data-ai-chip-action', chip.action);
      button.setAttribute('data-ai-chip-service', chip.service || '');
      button.setAttribute('data-ai-chip-widget', chip.widgetName || '');
      bar.appendChild(button);
    });

    var close = document.createElement('button');
    close.type = 'button';
    close.className = 'jacana-ai-chip-dismiss';
    close.innerHTML = '&times;';
    close.setAttribute('aria-label', 'Dismiss quick options');
    close.addEventListener('click', function () {
      bar.style.transition = 'opacity 200ms ease';
      bar.style.opacity = '0';
      setTimeout(function () { bar.remove(); }, 210);
      sessionStorage.setItem(dismissalKey, '1');
    });
    bar.appendChild(close);

    container.appendChild(bar);
  }


  function submitLead(route, payload) {
    var endpoint = config.appointmentEndpoint;
    if (!endpoint) {
      return Promise.resolve();
    }
    saveBookingDraft(payload || {});
    window.dispatchEvent(new CustomEvent('jacana:ai-capture-start', { detail: { route: route, payload: payload || {} } }));
    return fetchJson(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(Object.assign({
        visitor_key: visitorKey,
        session_key: sessionKey,
        route: route,
        source_page: window.location.href
      }, payload || {}))
    }, { source: 'ai_experience_appointment' }).then(function (response) {
      state.leadCaptured = true;
      sessionStorage.setItem('jacana_ai_lead_captured', '1');
      if (route === 'appointment_request') {
        window.dispatchEvent(new CustomEvent('jacana:appointment-request', { detail: response }));
      }
      window.dispatchEvent(new CustomEvent('jacana:ai-capture-complete', { detail: response }));
      return response;
    });
  }

  function submitBooking(payload) {
    if (!config.bookingEndpoint) {
      return Promise.resolve();
    }
    saveBookingDraft(payload || {});
    window.dispatchEvent(new CustomEvent('jacana:ai-capture-start', { detail: { route: 'booking_request', payload: payload || {} } }));
    return fetchJson(config.bookingEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(Object.assign({
        visitor_key: visitorKey,
        session_key: sessionKey,
        source_page: window.location.href
      }, payload || {}))
    }, { source: 'ai_experience_booking' }).then(function (response) {
      state.leadCaptured = true;
      sessionStorage.setItem('jacana_ai_lead_captured', '1');
      window.dispatchEvent(new CustomEvent('jacana:booking-handoff', { detail: response }));
      window.dispatchEvent(new CustomEvent('jacana:ai-capture-complete', { detail: response }));
      return response;
    });
  }

  function getServiceCatalog() {
    return [
      { value: 'tailor_made', label: 'Tailor-made tours' },
      { value: 'accommodation', label: 'Accommodation' },
      { value: 'car_rental', label: 'Car rental' },
      { value: 'game_drive', label: 'Game drive' },
      { value: 'transfers', label: 'Airport transfers' },
      { value: 'flights', label: 'Flights' },
      { value: 'city_tours', label: 'City tours' }
    ];
  }

  function renderBookingSummaryRows(draft) {
    var rows = [];
    [
      ['Name', draft.name],
      ['Email', draft.email],
      ['Phone', draft.phone],
      ['Country', draft.country],
      ['Travel dates', draft.travel_dates],
      ['Duration', draft.duration],
      ['Party size', draft.party_size],
      ['Budget', draft.budget_tier]
    ].forEach(function (pair) {
      if (pair[1]) {
        rows.push('<span><strong>' + escapeHtml(pair[0]) + ':</strong> ' + escapeHtml(pair[1]) + '</span>');
      }
    });
    return rows.join('');
  }

  function renderServicesChecklist(selectedServices) {
    var selected = Array.isArray(selectedServices) ? selectedServices : [];
    return getServiceCatalog().map(function (service) {
      var checked = selected.indexOf(service.value) !== -1 ? ' checked' : '';
      return '<label class="jacana-ai-check-chip"><input type="checkbox" name="selected_services[]" value="' + escapeHtml(service.value) + '"' + checked + '><span>' + escapeHtml(service.label) + '</span></label>';
    }).join('');
  }

  function renderCaptureForm(context, routeLabel) {
    context = normalizeContext(context);
    var draft = Object.assign({}, state.bookingDraft || {});
    return [
      '<div class="jacana-ai-sheet-head">',
      '  <span class="jacana-ai-sheet-kicker">Jacana planning handoff</span>',
      '  <button type="button" class="jacana-ai-close" data-ai-close aria-label="Close">&times;</button>',
      '  <h3>' + escapeHtml(routeLabel) + '</h3>',
      '  <p>Leave just the essentials. Jacana will follow up without forcing you through a full booking flow.</p>',
      '</div>',
      '<form class="jacana-ai-form" data-ai-capture-form method="post" action="#">',
      '  <input type="hidden" name="service_interest" value="' + escapeHtml(context.service) + '">',
      '  <input type="hidden" name="vehicle_interest" value="' + escapeHtml(context.vehicle) + '">',
      '  <input type="hidden" name="destination_interest" value="' + escapeHtml(context.destination) + '">',
      '  <input type="hidden" name="source_widget" value="' + escapeHtml(context.widgetName) + '">',
      '  <input type="hidden" name="source_section" value="' + escapeHtml(context.sourceSection || '') + '">',
      '  <label><span>Name</span><input type="text" name="name" value="' + escapeHtml(draft.name || '') + '" required></label>',
      '  <label><span>Email</span><input type="email" name="email" value="' + escapeHtml(draft.email || '') + '"></label>',
      '  <label><span>Phone</span><input type="text" name="phone" value="' + escapeHtml(draft.phone || '') + '"></label>',
      '  <label><span>Travel dates</span><input type="text" name="travel_dates" value="' + escapeHtml(draft.travel_dates || '') + '" placeholder="Month or exact dates"></label>',
      '  <label><span>Party size</span><input type="text" name="party_size" value="' + escapeHtml(draft.party_size || '') + '" placeholder="2 adults, 1 child"></label>',
      '  <label class="jacana-span-2"><span>Preferred callback time</span><input type="text" name="preferred_time" placeholder="Tomorrow afternoon, WhatsApp, email only..."></label>',
      '  <label class="jacana-span-2"><span>Summary</span><textarea name="summary" rows="4" placeholder="Tell us the route, comfort level, or specific need."></textarea></label>',
      '  <div class="jacana-ai-form-actions jacana-span-2">',
      '    <button type="submit" class="jacana-ai-mini-button">Send request</button>',
      '    <button type="button" class="jacana-ai-mini-button is-secondary" data-ai-chat-escalate>Continue in chat</button>',
      '  </div>',
      '</form>'
    ].join('');
  }

  function renderBookingForm(context) {
    context = normalizeContext(context);
    var draft = Object.assign({}, state.bookingDraft || {});
    var selectedServices = Array.from(new Set([]
      .concat(Array.isArray(draft.selected_services) ? draft.selected_services : [])
      .concat(context.service ? [context.service] : [])
      .filter(Boolean)));
    var knownCore = ['name', 'email', 'phone', 'country', 'travel_dates', 'duration', 'party_size', 'budget_tier'];
    var hasTravelerSnapshot = knownCore.some(function (key) {
      return !!draft[key];
    });
    var missingCore = knownCore.filter(function (key) {
      if (key === 'phone') {
        return false;
      }
      if (key === 'country' || key === 'duration' || key === 'budget_tier') {
        return false;
      }
      return !draft[key];
    });
    var shouldShowCoreFields = !hasTravelerSnapshot || missingCore.length > 0;
    var placesValue = draft.additional_places || context.destination || '';
    var vehicleValue = draft.vehicle_interest || context.vehicle || '';
    var destinationValue = draft.destination_interest || context.destination || '';
    var sourceSection = context.sourceSection || draft.source_section || '';
    var isContactPage = state.pageType === 'contact';
    var intro = hasTravelerSnapshot
      ? 'Jacana already has some of your details. Update only what changed and add any new services or places.'
      : isContactPage
        ? 'You\'ve come to the right place. Share a few details about your ideal Namibia trip and we\'ll put together a tailored proposal just for you.'
        : 'Give Jacana the essentials. We will turn this into a coordinated Namibia plan instead of forcing a long checkout.';
    if (config.prompts && config.prompts.bookingPrompt) {
      intro = config.prompts.bookingPrompt;
    }
    return [
      '<div class="jacana-ai-sheet-head jacana-ai-booking-head">',
      '  <span class="jacana-ai-sheet-kicker">Jacana booking studio</span>',
      '  <button type="button" class="jacana-ai-close" data-ai-close aria-label="Close">&times;</button>',
      '  <h3>' + (isContactPage ? 'Plan your Namibia journey' : 'Request your tailored booking') + '</h3>',
      '  <p>' + escapeHtml(intro) + '</p>',
      '</div>',
      '<form class="jacana-ai-form jacana-ai-booking-form" data-ai-booking-form method="post" action="#">',
      '  <input type="hidden" name="service_interest" value="' + escapeHtml(context.service) + '">',
      '  <input type="hidden" name="vehicle_interest" value="' + escapeHtml(vehicleValue) + '">',
      '  <input type="hidden" name="destination_interest" value="' + escapeHtml(destinationValue) + '">',
      '  <input type="hidden" name="source_widget" value="' + escapeHtml(context.widgetName) + '">',
      '  <input type="hidden" name="source_section" value="' + escapeHtml(sourceSection) + '">',
      hasTravelerSnapshot ? '  <div class="jacana-ai-booking-summary jacana-span-2"><div><span class="jacana-ai-sheet-kicker">Saved traveler details</span><div class="jacana-ai-booking-summary-grid">' + renderBookingSummaryRows(draft) + '</div></div><button type="button" class="jacana-ai-mini-button is-secondary" data-ai-edit-details>Update traveler details</button></div>' : '',
      shouldShowCoreFields ? '  <div class="jacana-ai-form-edit jacana-span-2">' : '  <div class="jacana-ai-form-edit jacana-span-2" hidden>',
      '    <div class="jacana-ai-form-grid">',
      '      <label><span>Name</span><input type="text" name="name" value="' + escapeHtml(draft.name || '') + '" required></label>',
      '      <label><span>Email</span><input type="email" name="email" value="' + escapeHtml(draft.email || '') + '"' + (draft.email ? '' : ' required') + '></label>',
      '      <label><span>Phone / WhatsApp</span><input type="text" name="phone" value="' + escapeHtml(draft.phone || '') + '"></label>',
      '      <label><span>Country</span><input type="text" name="country" value="' + escapeHtml(draft.country || '') + '"></label>',
      '      <label><span>Travel dates</span><input type="text" name="travel_dates" value="' + escapeHtml(draft.travel_dates || '') + '" placeholder="Month or exact dates"></label>',
      '      <label><span>Duration</span><input type="text" name="duration" value="' + escapeHtml(draft.duration || '') + '" placeholder="7 nights, 10 days..."></label>',
      '      <label><span>Party size</span><input type="text" name="party_size" value="' + escapeHtml(draft.party_size || '') + '" placeholder="2 adults, 1 child"></label>',
      '      <label><span>Budget</span><select name="budget_tier"><option value="">Select</option><option value="practical"' + (draft.budget_tier === 'practical' ? ' selected' : '') + '>Practical</option><option value="balanced"' + (draft.budget_tier === 'balanced' ? ' selected' : '') + '>Balanced</option><option value="premium"' + (draft.budget_tier === 'premium' ? ' selected' : '') + '>Premium</option><option value="luxury"' + (draft.budget_tier === 'luxury' ? ' selected' : '') + '>Luxury</option></select></label>',
      '    </div>',
      '  </div>',
      '  <label class="jacana-span-2"><span>Services for this booking</span><div class="jacana-ai-check-grid">' + renderServicesChecklist(selectedServices) + '</div></label>',
      selectedServices.indexOf('accommodation') !== -1 || context.service === 'accommodation' ? '  <label><span>Accommodation style</span><select name="accommodation_style"><option value="">Select</option><option value="luxury_tented_camp"' + (draft.accommodation_style === 'luxury_tented_camp' ? ' selected' : '') + '>Luxury tented camps</option><option value="lodges_hotels"' + (draft.accommodation_style === 'lodges_hotels' ? ' selected' : '') + '>Lodges / Hotels</option><option value="guest_houses"' + (draft.accommodation_style === 'guest_houses' ? ' selected' : '') + '>Guest houses</option><option value="backpackers"' + (draft.accommodation_style === 'backpackers' ? ' selected' : '') + '>Backpackers</option><option value="rooftop_tent"' + (draft.accommodation_style === 'rooftop_tent' ? ' selected' : '') + '>Rooftop tent</option><option value="ground_tent"' + (draft.accommodation_style === 'ground_tent' ? ' selected' : '') + '>Ground tent</option></select></label>' : '',
      context.vehicle || draft.vehicle_interest ? '  <label><span>Vehicle</span><input type="text" name="vehicle_interest_display" value="' + escapeHtml(vehicleValue) + '" disabled></label>' : '',
      '  <label class="jacana-span-2"><span>Places or stops to add</span><textarea name="additional_places" rows="3" placeholder="Add more places or route ideas if needed.">' + escapeHtml(placesValue) + '</textarea></label>',
      '  <label class="jacana-span-2"><span>Trip details</span><textarea name="message" rows="5" placeholder="Tell Jacana what you want to book, where you plan to travel, and any must-haves.">' + escapeHtml(draft.message || '') + '</textarea></label>',
      '  <div class="jacana-ai-form-actions jacana-span-2">',
      '    <button type="submit" class="jacana-ai-mini-button">Send booking request</button>',
      '    <button type="button" class="jacana-ai-mini-button is-secondary" data-ai-chat-escalate>Need custom advice first?</button>',
      '  </div>',
      '</form>'
    ].join('');
  }

  function openCapture(context, route) {
    activateRail();
    openSheet(renderCaptureForm(context, route === 'booking_form_handoff' ? 'Booking handoff' : 'Request a planning call'), { major: true, flow: route });
  }

  function openBookingModal(context) {
    var normalized = normalizeContext(context || {});
    var draftUpdate = {
      service_interest: normalized.service || '',
      vehicle_interest: normalized.vehicle || '',
      destination_interest: normalized.destination || '',
      source_widget: normalized.widgetName || '',
      source_section: normalized.sourceSection || '',
      selected_services: normalized.service ? [normalized.service] : []
    };
    saveBookingDraft(draftUpdate);
    state.service = normalized.service || state.service;
    state.vehicle = normalized.vehicle || state.vehicle;
    state.destination = normalized.destination || state.destination;
    updateJourneySummary();
    openSheet([
      '<div class="jacana-ai-sheet-head jacana-ai-loading-head">',
      '  <span class="jacana-ai-sheet-kicker">Jacana booking studio</span>',
      '  <button type="button" class="jacana-ai-close" data-ai-close aria-label="Close">&times;</button>',
      '  <h3>Loading your booking details</h3>',
      '  <p>Jacana is pulling together the details you already shared so you only update what changed.</p>',
      '  <div class="jacana-ai-loader"><span></span><span></span><span></span></div>',
      '</div>'
    ].join(''), { major: true, flow: 'booking_modal' });

    getProfile().catch(function () {
      return null;
    }).finally(function () {
      openSheet(renderBookingForm(normalized), { flow: 'booking_modal' });
      trackTouchpoint({
        componentType: 'booking_modal',
        componentKey: 'booking_request',
        widgetName: normalized.widgetName || '',
        serviceInterest: normalized.service || state.service,
        actionType: 'open',
        payload: {
          vehicle: normalized.vehicle || '',
          destination: normalized.destination || '',
          sourceSection: normalized.sourceSection || ''
        }
      });
    });
  }

  function openPlanner(kind, context) {
    context = normalizeContext(context || {});
    activateRail();
    state.service = context.service || state.service;
    state.vehicle = context.vehicle || state.vehicle;
    state.destination = context.destination || state.destination;
    updateJourneySummary();

    var fallback = getPlannerFallback(kind, context);
    var widgetName = context.widgetName || '';

    openSheet([
      '<div class="jacana-ai-sheet-head jacana-ai-loading-head">',
      '  <span class="jacana-ai-sheet-kicker">' + escapeHtml(label('catalog.ai.advisor_kicker', 'Jacana AI advisor')) + '</span>',
      '  <button type="button" class="jacana-ai-close" data-ai-close aria-label="' + escapeHtml(label('catalog.ai.close', 'Close')) + '">&times;</button>',
      '  <h3>' + escapeHtml(label('catalog.ai.preparing_title', 'Preparing your next step')) + '</h3>',
      '  <p>' + escapeHtml(label('catalog.ai.preparing_body', 'Jacana is tailoring the questions to this section and your recent interactions.')) + '</p>',
      '  <div class="jacana-ai-loader"><span></span><span></span><span></span></div>',
      '</div>'
    ].join(''), { major: true, flow: kind, service: context.service });

    trackTouchpoint({
      componentType: 'decision_sheet',
      componentKey: kind,
      widgetName: widgetName,
      serviceInterest: context.service,
      actionType: 'open',
      payload: { vehicle: context.vehicle, destination: context.destination }
    });

    getProfile().then(function (profile) {
      if (profile) {
        state.profile = profile;
      }
      return requestPlannerBlueprint(kind, context, fallback);
    }).then(function (blueprint) {
      var html = [
        '<div class="jacana-ai-sheet-head">',
        '  <span class="jacana-ai-sheet-kicker">' + escapeHtml(label('catalog.ai.advisor_kicker', 'Jacana AI advisor')) + '</span>',
        '  <button type="button" class="jacana-ai-close" data-ai-close aria-label="' + escapeHtml(label('catalog.ai.close', 'Close')) + '">&times;</button>',
        '  <h3>' + escapeHtml(blueprint.title) + '</h3>',
        '  <p>' + escapeHtml(blueprint.description) + '</p>',
        '</div>',
        '<div class="jacana-ai-step" data-ai-step="1">',
        '  <span class="jacana-ai-step-label">' + escapeHtml(label('catalog.ai.step_one', 'Step 1')) + '</span>',
        '  <h4>' + escapeHtml(blueprint.questionOneTitle) + '</h4>',
        '  <div class="jacana-ai-option-grid">'
      ];

      (blueprint.stepOneOptions || []).forEach(function (item) {
        html.push('<button type="button" class="jacana-ai-option" data-ai-answer="1" data-ai-value="' + escapeHtml(item.value) + '"><strong>' + escapeHtml(item.label) + '</strong>' + (item.description ? '<small>' + escapeHtml(item.description) + '</small>' : '') + '</button>');
      });

      html.push('  </div></div>');
      html.push('<div class="jacana-ai-step" data-ai-step="2" hidden><span class="jacana-ai-step-label">' + escapeHtml(label('catalog.ai.step_two', 'Step 2')) + '</span><h4>' + escapeHtml(blueprint.questionTwoTitle) + '</h4><div class="jacana-ai-option-grid">');
      (blueprint.stepTwoOptions || []).forEach(function (item) {
        html.push('<button type="button" class="jacana-ai-option" data-ai-answer="2" data-ai-value="' + escapeHtml(item.value) + '"><strong>' + escapeHtml(item.label) + '</strong>' + (item.description ? '<small>' + escapeHtml(item.description) + '</small>' : '') + '</button>');
      });
      html.push('</div></div>');
      html.push('<div class="jacana-ai-sheet-result" data-ai-result hidden></div>');
      sheet.innerHTML = html.join('');

      var answers = {};
      sheet.querySelectorAll('[data-ai-answer]').forEach(function (button) {
        button.addEventListener('click', function () {
          var step = button.getAttribute('data-ai-answer');
          var stepContainer = button.closest('[data-ai-step]');
          if (stepContainer) {
            stepContainer.querySelectorAll('[data-ai-answer="' + step + '"]').forEach(function (option) {
              option.classList.remove('is-selected');
            });
          }
          button.classList.add('is-selected');

          answers['step_' + step] = button.getAttribute('data-ai-value');
          window.dispatchEvent(new CustomEvent('jacana:ai-progress', { detail: { kind: kind, answers: answers } }));
          trackTouchpoint({
            componentType: 'decision_sheet',
            componentKey: kind,
            widgetName: widgetName,
            serviceInterest: context.service,
            actionType: 'answer',
            payload: { step: step, value: answers['step_' + step] }
          });

          if (step === '1') {
            var stepOne = sheet.querySelector('[data-ai-step="1"]');
            var stepTwo = sheet.querySelector('[data-ai-step="2"]');
            window.setTimeout(function () {
              if (stepOne) {
                stepOne.hidden = true;
              }
              if (stepTwo) {
                stepTwo.hidden = false;
              }
            }, 140);
            return;
          }

          state.service = context.service || state.service;
          if (context.vehicle) {
            state.vehicle = context.vehicle;
          }
          if (context.destination) {
            state.destination = context.destination;
          }
          updateJourneySummary();
          trackConversion(kind === 'vehicle' ? 'vehicle_interest' : 'service_interest', {
            service: state.service,
            label: widgetName || kind,
            meta: { answers: answers, vehicle: state.vehicle, destination: state.destination, widget: widgetName }
          });

          var result = sheet.querySelector('[data-ai-result]');
          if (!result) {
            return;
          }

          result.hidden = false;
          result.innerHTML = [
            '<span class="jacana-ai-sheet-kicker">Recommended next step</span>',
            '<h4>' + escapeHtml(blueprint.resultTitle || fallback.resultTitle) + '</h4>',
            '<p>' + escapeHtml(blueprint.resultBody || fallback.resultBody) + '</p>',
            '<div class="jacana-ai-inline-actions">',
            '  <button type="button" class="jacana-ai-mini-button" data-ai-result-action="booking">' + escapeHtml(blueprint.bookingLabel || fallback.bookingLabel) + '</button>',
            '  <button type="button" class="jacana-ai-mini-button is-secondary" data-ai-result-action="callback">' + escapeHtml(blueprint.callbackLabel || fallback.callbackLabel) + '</button>',
            '  <button type="button" class="jacana-ai-mini-button is-secondary" data-ai-result-action="chat">' + escapeHtml(blueprint.chatLabel || fallback.chatLabel) + '</button>',
            '</div>'
          ].join('');

          result.querySelectorAll('[data-ai-result-action]').forEach(function (actionButton) {
            actionButton.addEventListener('click', function () {
              var action = actionButton.getAttribute('data-ai-result-action');
              if (action === 'booking') {
                openBookingModal({
                  service: state.service,
                  vehicle: state.vehicle,
                  destination: state.destination,
                  widgetName: widgetName,
                  sourceNode: context.sourceNode
                });
                trackConversion('booking_start', {
                  service: state.service,
                  label: widgetName || kind,
                  meta: { source: 'decision_sheet', answers: answers }
                });
                return;
              }
              if (action === 'callback') {
                openCapture({
                  service: state.service,
                  vehicle: state.vehicle,
                  destination: state.destination,
                  widgetName: widgetName,
                  sourceNode: context.sourceNode
                }, 'appointment_request');
                return;
              }
              openChat('Use this context for a factual reply: ' + JSON.stringify({
                service: state.service,
                vehicle: state.vehicle,
                destination: state.destination,
                widgetName: widgetName,
                answers: answers,
                section: extractSectionContext(context)
              }));
            });
          });
        });
      });
    });
  }

  function bindSheetInteractions() {
    root.addEventListener('click', function (event) {
      var closeNode = event.target.closest('[data-ai-close]');
      if (closeNode) {
        closeSheet();
        return;
      }
      var escalate = event.target.closest('[data-ai-chat-escalate]');
      if (escalate) {
        openChat('Please continue helping me with my request.');
        return;
      }
      var editDetails = event.target.closest('[data-ai-edit-details]');
      if (editDetails) {
        var form = editDetails.closest('[data-ai-booking-form]');
        var editPanel = form ? form.querySelector('.jacana-ai-form-edit') : null;
        if (editPanel) {
          editPanel.hidden = !editPanel.hidden;
          editDetails.textContent = editPanel.hidden ? 'Update traveler details' : 'Hide traveler details';
        }
      }
    });

    root.addEventListener('submit', function (event) {
      var form = event.target.closest('[data-ai-capture-form], [data-ai-booking-form]');
      if (!form) {
        return;
      }
      event.preventDefault();
      var formData = new FormData(form);
      var payload = {};
      formData.forEach(function (value, key) {
        if (key === 'selected_services[]') {
          if (!Array.isArray(payload.selected_services)) {
            payload.selected_services = [];
          }
          payload.selected_services.push(String(value || '').trim());
          return;
        }
        payload[key] = String(value || '').trim();
      });

      var isBookingForm = form.hasAttribute('data-ai-booking-form');
      form.classList.add('is-loading');
      form.querySelectorAll('button, input, textarea, select').forEach(function (field) {
        field.disabled = true;
      });
      var request = isBookingForm ? submitBooking(payload) : submitLead('appointment_request', payload);

      request.then(function () {
        trackConversion(isBookingForm ? 'booking_start' : 'lead_submit', {
          service: payload.service_interest || state.service,
          label: payload.source_widget || (isBookingForm ? 'Booking modal' : 'AI capture')
        });
        sheet.innerHTML = renderSuccessState({
          kicker: isBookingForm ? 'Booking request sent' : 'Request sent',
          title: isBookingForm ? 'Jacana is building your booking' : 'Jacana has your details',
          body: isBookingForm ? 'Your booking request is in the CRM and has been sent to the bookings team for follow-up.' : 'Your request is queued for follow-up. You can keep browsing or open another booking request.'
        });
      }).catch(function () {
        form.classList.remove('is-loading');
        form.querySelectorAll('button, input, textarea, select').forEach(function (field) {
          if (field.name === 'vehicle_interest_display') {
            return;
          }
          field.disabled = false;
        });
        sheet.insertAdjacentHTML('beforeend', '<p class="jacana-ai-error">The request could not be sent. Please try again in a moment.</p>');
      });
    });
  }

  function normalizeBookingLinks() {
    // Booking links navigate normally — no interception.
  }

  function adaptServiceCards() {
    document.querySelectorAll('.jacana-main-services-grid .jacana-service-card').forEach(function (card) {
      var titleNode = card.querySelector('h3');
      var serviceName = titleNode ? titleNode.textContent.trim() : '';
      var serviceKey = '';
      var primary = card.querySelector('.jacana-service-button');
      var secondary = card.querySelector('.jacana-service-chat');
      var actions = card.querySelector('.jacana-service-actions');

      if (secondary) {
        serviceKey = secondary.getAttribute('data-jacana-service') || '';
        secondary.textContent = 'Help me decide';
        secondary.setAttribute('data-jacana-ai-surface', 'service');
        secondary.setAttribute('data-jacana-ai-flow', 'service');
        secondary.setAttribute('data-jacana-service', serviceKey);
        secondary.setAttribute('data-jacana-widget', 'jacana_main_services_grid');
      }

      if (primary) {
        var href = primary.getAttribute('href') || '';
        if (!href || href === '#' || isBookingLink(primary)) {
          if (!href || href === '#') {
            primary.setAttribute('href', '#');
          }
          primary.setAttribute('data-jacana-booking-modal', 'true');
          primary.setAttribute('data-jacana-service', serviceKey || serviceName);
          primary.setAttribute('data-jacana-widget', 'jacana_main_services_grid');
        }
      }

      if (actions) {
        renderChipBar(actions, [
          { label: 'Compare options', action: 'service', service: serviceKey, widgetName: 'jacana_main_services_grid' },
          { label: 'Ask about availability', action: 'service', service: serviceKey, widgetName: 'jacana_main_services_grid' }
        ]);
      }

      card.setAttribute('data-jacana-ai-surface', 'service-card');
      card.setAttribute('data-jacana-service', serviceKey || serviceName);
    });
  }

  function adaptCarRentalOffers() {
    document.querySelectorAll('.jacana-rental-offer-card').forEach(function (card) {
      var titleNode = card.querySelector('h3');
      var title = titleNode ? titleNode.textContent.trim() : 'Vehicle';
      var cta = card.querySelector('.jacana-rental-offer-link');
      if (cta) {
        cta.textContent = 'Talk to Jacana about this vehicle';
        cta.setAttribute('data-jacana-ai-surface', 'vehicle');
        cta.setAttribute('data-jacana-ai-flow', 'vehicle');
        cta.setAttribute('data-jacana-service', 'car_rental');
        cta.setAttribute('data-jacana-vehicle', title);
        cta.setAttribute('data-jacana-widget', 'jacana_car_rental_offers');
      }
    });
  }

  function adaptCarRentalShowcase() {
    document.querySelectorAll('.jacana-rental-detail-link').forEach(function (link) {
      var detailCard = link.closest('[data-rental-detail]');
      var title = detailCard ? (detailCard.getAttribute('data-service-name') || '') : '';
      link.textContent = 'Talk to Jacana about this vehicle';
      link.setAttribute('data-jacana-ai-surface', 'vehicle');
      link.setAttribute('data-jacana-ai-flow', 'vehicle');
      link.setAttribute('data-jacana-service', 'car_rental');
      link.setAttribute('data-jacana-vehicle', title || link.getAttribute('data-jacana-service') || 'Vehicle');
      link.setAttribute('data-jacana-widget', 'jacana_car_rental_showcase');
    });
  }

  function adaptFleetExplorer() {
    /* Fleet explorer now uses a static "Contact us now" CTA rendered in PHP.
       No AI chip injection needed here. */
  }

  function adaptTailorMadeStory() {
    document.querySelectorAll('.jacana-tailor-made-story .hero-actions, .jacana-tailor-made-story .jacana-tailor-story-actions').forEach(function (actions) {
      renderChipBar(actions, [
        { label: 'Shape my route', action: 'planner', service: 'tailor_made', widgetName: 'jacana_tailor_made_story' },
        { label: 'Request a quote', action: 'booking', service: 'tailor_made', widgetName: 'jacana_tailor_made_story' }
      ]);
    });
  }

  function adaptContactPage() {
    if (state.pageType !== 'contact') {
      return;
    }
    // Ensure all .jacana-cif-cta-button elements open the booking modal
    document.querySelectorAll('.jacana-cif-cta-button').forEach(function (btn) {
      if (!btn.hasAttribute('data-jacana-booking-modal')) {
        btn.setAttribute('data-jacana-booking-modal', 'true');
        btn.setAttribute('data-jacana-service', 'tailor_made');
        btn.setAttribute('data-jacana-widget', 'jacana_contact_info_form');
        btn.setAttribute('href', '#');
      }
    });
    // Add an AI chip bar below the section header if present
    var header = document.querySelector('.jacana-cif-section .jacana-cif-header, .jacana-cif-inner > h2, .jacana-cif-inner > .jacana-cif-kicker');
    if (header) {
      renderChipBar(header, [
        { label: 'Plan my trip', action: 'planner', service: 'tailor_made', widgetName: 'jacana_contact_info_form' },
        { label: 'Get a quote', action: 'booking', service: 'tailor_made', widgetName: 'jacana_contact_info_form' }
      ]);
    }
  }

  function adaptToursInclusions() {
    document.querySelectorAll('.jacana-tours-inclusions .jacana-incl-header').forEach(function (header) {
      renderChipBar(header, [
        { label: 'Help me choose', action: 'inclusions', service: 'tailor_made', widgetName: 'jacana_tours_inclusions' },
        { label: 'Request a quote', action: 'booking', service: 'tailor_made', widgetName: 'jacana_tours_inclusions' }
      ]);
    });
    document.querySelectorAll('.jacana-tours-inclusions .jacana-incl-card').forEach(function (card) {
      card.setAttribute('data-jacana-widget', 'jacana_tours_inclusions');
      card.setAttribute('data-jacana-service', 'tailor_made');
    });
  }

  function adaptAccommodationStyles() {
    var section = document.querySelector('.jacana-accommodation-styles');
    if (!section) {
      return;
    }
    var header = section.querySelector('.jacana-accom-header');
    renderChipBar(header || section, [
      { label: 'Match my stay style', action: 'service', service: 'accommodation', widgetName: 'jacana_accommodation_styles' },
      { label: 'Request a quote', action: 'booking', service: 'accommodation', widgetName: 'jacana_accommodation_styles' }
    ]);
  }

  function bindAccommodationStyleDetails() {
    document.addEventListener('click', function (event) {
      var trigger = event.target.closest('[data-jacana-ai-action="style_details"]');
      if (!trigger) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      var card = trigger.closest('.jacana-accom-card');
      var title = trigger.getAttribute('data-jacana-accommodation-style') || (card ? trimText(card.querySelector('.jacana-accom-card-title').textContent, 80) : 'Accommodation style');
      var descriptionNode = card ? card.querySelector('.jacana-accom-card-desc') : null;
      var section = extractSectionContext({ sourceNode: trigger });
      var context = [
        'Provide factual details about this Namibia accommodation style: "' + title + '".',
        descriptionNode ? 'Card description: ' + trimText(descriptionNode.textContent, 240) : '',
        'Explain what it is, who it suits, the main pros, any tradeoffs, and when Jacana would typically recommend it in a Namibia itinerary.'
      ].filter(Boolean).join(' ');
      state.service = 'accommodation';
      activateRail();
      updateJourneySummary();
      trackTouchpoint({
        componentType: 'detail_chat',
        componentKey: 'accommodation_style',
        widgetName: 'jacana_accommodation_styles',
        serviceInterest: 'accommodation',
        actionType: 'open',
        payload: { style: title }
      });
      window.dispatchEvent(new CustomEvent('jacana:open-concierge', {
        detail: {
          message: 'Tell me more about ' + title,
          context: context,
          skipPicker: true
        }
      }));
    }, true);
  }

  function adaptTeamProfiles() {
    // Booking CTA is now rendered in PHP (class-team-profiles.php).
    // No dynamic injection needed — the PHP button uses data-ai-chip-action="booking"
    // which routes to openBookingModal() via bindAiSurfaceClicks().
  }

  function adaptSocialProof() {
    document.querySelectorAll('[data-jacana-open-review-form]').forEach(function (button) {
      button.addEventListener('click', function () {
        var form = button.closest('.jacana-social-reviews').querySelector('[data-jacana-review-form]');
        if (form) {
          form.hidden = !form.hidden;
          button.classList.toggle('is-active', !form.hidden);
          if (!form.hidden) {
            form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          }
        }
      });
    });

    document.querySelectorAll('[data-jacana-review-form]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        var status = form.querySelector('[data-jacana-review-status]');
        var data = {};
        new FormData(form).forEach(function (value, key) {
          if (key === 'consent_public') {
            data[key] = true;
            return;
          }
          data[key] = String(value || '').trim();
        });
        if (!data.rating) {
          data.rating = 5;
        }
        data.source = 'social_proof_widget';
        if (status) {
          status.textContent = 'Submitting...';
        }
        fetchJson(config.reviewsEndpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        }, { source: 'ai_experience_reviews' }).then(function () {
          if (status) {
            status.textContent = 'Submitted for approval.';
          }
          form.reset();
          form.hidden = true;
          window.dispatchEvent(new CustomEvent('jacana:review-submitted'));
          trackTouchpoint({
            componentType: 'review_form',
            componentKey: 'social_proof',
            widgetName: 'jacana_social_proof',
            serviceInterest: data.service_interest || '',
            actionType: 'submit'
          });
          openSheet(renderSuccessState({
            kicker: 'Review received',
            title: 'Thank you for reviewing Jacana',
            body: 'Your review has been submitted for approval. Once approved, it can appear on the site as premium social proof.',
            showBooking: false,
            secondaryLabel: 'Close'
          }), { flow: 'review_success' });
        }).catch(function () {
          if (status) {
            status.textContent = 'Could not submit right now.';
          }
        });
      });
    });
  }

  function adaptFaqAndDownloads() {
    document.querySelectorAll('.jacana-faq-knowledge-widget .section-header, .jacana-downloads-library-widget .section-header').forEach(function (header) {
      renderChipBar(header, [
        { label: 'Help me find the answer', action: 'faq', service: 'faq', widgetName: 'jacana_faq_downloads' },
        { label: 'Plan my trip', action: 'service', service: 'tailor_made', widgetName: 'jacana_downloads_library' }
      ]);
    });
  }

  function adaptGallery() {
    var hero = document.querySelector('.jacana-gallery-hero .hero-content');
    if (hero) {
      renderChipBar(hero, [
        { label: 'Plan a similar experience', action: 'destination', service: 'tailor_made', widgetName: 'jacana_gallery_hero' },
        { label: 'Ask about this style', action: 'destination', service: 'tailor_made', widgetName: 'jacana_gallery_hero' }
      ]);
    }

    document.addEventListener('click', function (event) {
      var card = event.target.closest('.jacana-gallery-card-button');
      if (!card) {
        return;
      }
      var wrapper = card.closest('[data-gallery-item]');
      var category = wrapper ? (wrapper.getAttribute('data-gallery-category') || 'gallery') : 'gallery';
      state.galleryCategoryCounts[category] = (state.galleryCategoryCounts[category] || 0) + 1;
      if (state.galleryCategoryCounts[category] === 2 && canShowMinor()) {
        state.destination = category.replace(/-/g, ' ');
        updateJourneySummary();
        var grid = document.querySelector('.jacana-gallery-curated-grid');
        if (grid) {
          renderInsightCard(grid, {
            title: 'You keep returning to ' + state.destination,
            body: 'Jacana can turn that visual preference into a route, accommodation style, and booking recommendation.',
            ctaLabel: 'Plan around this style',
            ctaAction: 'destination',
            chipLabel: 'Visual intent detected'
          });
        }
      }
    });
  }

  function adaptHighlightsMap() {
    document.addEventListener('click', function (event) {
      var hotspot = event.target.closest('.jacana-map-hotspot, .jacana-map-place-pill');
      if (hotspot) {
        var title = hotspot.getAttribute('data-title') || hotspot.getAttribute('data-service-name') || hotspot.textContent.trim();
        if (title) {
          state.destination = title;
          state.service = inferServiceFromContext({ destination: title });
          updateJourneySummary();
          trackTouchpoint({
            componentType: 'map_selection',
            componentKey: 'highlight',
            widgetName: 'jacana_highlights_map',
            serviceInterest: state.service,
            actionType: 'select',
            payload: { destination: title }
          });
        }
        return;
      }

      var cta = event.target.closest('[data-map-chat-cta]');
      if (!cta) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      var destination = state.destination || '';
      var panelTitleEl = cta.closest('[data-dex-widget]')
        ? document.querySelector('[data-dex-sl-title]')
        : document.querySelector('[data-map-title-overlay]');
      if (panelTitleEl && panelTitleEl.textContent.trim() && panelTitleEl.textContent.trim() !== 'Select a destination') {
        destination = panelTitleEl.textContent.trim();
      }
      var chatMessage = destination
        ? 'Tell me more about ' + destination + ' in Namibia. What makes it special, who is it best suited for, and how does Jacana typically include it in a journey?'
        : 'I am exploring destinations on the Namibia map. Can you tell me more about what Jacana offers across different regions?';
      trackTouchpoint({
        componentType: 'map_chat',
        componentKey: 'place_info',
        widgetName: 'jacana_highlights_map',
        serviceInterest: state.service || 'tailor_made',
        actionType: 'chat_open',
        payload: { destination: destination }
      });
      openChat(chatMessage);
    }, true);
  }

  function adaptReviewsPage() {
    var root = document.querySelector('[data-jacana-reviews-page]');
    if (!root) return;

    var statsContainer = root.querySelector('[data-jacana-reviews-stats-container]');
    var feedContainer = root.querySelector('[data-jacana-reviews-feed]');
    var loadMoreBtn = root.querySelector('[data-jacana-load-more-reviews]');
    var formDrawer = root.querySelector('[data-jacana-review-form-drawer]');
    var form = root.querySelector('[data-jacana-review-form]');
    var toggleBtns = root.querySelectorAll('[data-jacana-toggle-review-form]');
    var service = root.getAttribute('data-service') || '';
    var limit = parseInt(root.getAttribute('data-limit') || '12', 10);
    var offset = 0;

    function renderStars(rating) {
      var stars = '';
      rating = Math.round(parseFloat(rating || 0));
      for (var i = 1; i <= 5; i++) {
        stars += '<span aria-hidden="true">' + (i <= rating ? '★' : '☆') + '</span>';
      }
      return stars;
    }

    function loadStats() {
      if (!config.reviewsEndpoint || !statsContainer) return;
      var url = config.reviewsEndpoint + '/stats';
      if (service) {
        url += (url.indexOf('?') === -1 ? '?' : '&') + 'service_interest=' + encodeURIComponent(service);
      }

      fetchJson(url, {}, { source: 'reviews_stats' }).then(function(data) {
        if (!data || !data.ok) return;
        
        var breakdownHtml = '';
        for (var i = 5; i >= 1; i--) {
          var count = data.breakdown[i] || 0;
          var percent = data.total > 0 ? (count / data.total) * 100 : 0;
          breakdownHtml += [
            '<div class="jacana-breakdown-row">',
            '  <div class="jacana-breakdown-label">' + i + ' ' + (i === 1 ? 'star' : 'stars') + '</div>',
            '  <div class="jacana-breakdown-bar-bg"><div class="jacana-breakdown-bar-fill" style="width: ' + percent + '%"></div></div>',
            '  <div class="jacana-breakdown-count">' + count + '</div>',
            '</div>'
          ].join('');
        }

        statsContainer.innerHTML = [
          '<div class="jacana-stats-grid">',
          '  <div class="jacana-stats-main">',
          '    <h3>' + (parseFloat(data.average || 0).toFixed(1)) + '</h3>',
          '    <div class="jacana-stats-stars">' + renderStars(data.average) + '</div>',
          '    <div class="jacana-stats-count">' + (data.total || 0) + (data.total === 1 ? ' review' : ' reviews') + '</div>',
          '  </div>',
          '  <div class="jacana-stats-breakdown">',
          breakdownHtml,
          '  </div>',
          '</div>'
        ].join('');
      });
    }

    function loadReviews(isAppend) {
      if (!config.reviewsEndpoint || !feedContainer) return;
      var url = config.reviewsEndpoint + (config.reviewsEndpoint.indexOf('?') === -1 ? '?' : '&') + 'limit=' + limit + '&offset=' + offset;
      if (service) {
        url += '&service_interest=' + encodeURIComponent(service);
      }

      var loader = root.querySelector('.jacana-feed-loader');
      if (loader && !isAppend) loader.hidden = false;

      fetchJson(url, {}, { source: 'reviews_feed' }).then(function(data) {
        if (loader) loader.hidden = true;
        if (!data || !data.ok || !data.items) return;

        var html = data.items.map(function(item) {
          var initial = escapeHtml(item.name).charAt(0).toUpperCase();
          var origin = '';
          if (item.trip_label && item.country) {
            origin = escapeHtml(item.trip_label) + ' · ' + escapeHtml(item.country);
          } else if (item.trip_label) {
            origin = escapeHtml(item.trip_label);
          } else if (item.country) {
            origin = escapeHtml(item.country);
          }
          var featuredBadge = item.status === 'featured' ? '<div class="jacana-rp-card-featured" aria-label="Featured review">✦</div>' : '';
          return [
            '<article class="jacana-rp-card jacana-reveal">',
            '  <div class="jacana-rp-card-stars">' + renderStars(item.rating) + '</div>',
            '  <blockquote class="jacana-rp-card-quote">' + escapeHtml(item.review_text) + '</blockquote>',
            '  <div class="jacana-rp-card-footer">',
            '    <div class="jacana-rp-card-avatar" aria-hidden="true">' + initial + '</div>',
            '    <div class="jacana-rp-card-meta">',
            '      <strong class="jacana-rp-card-name">' + escapeHtml(item.name) + '</strong>',
            origin ? '      <span class="jacana-rp-card-origin">' + origin + '</span>' : '',
            '    </div>',
            featuredBadge,
            '  </div>',
            '  <div class="jacana-rp-card-stripe" aria-hidden="true"></div>',
            '</article>'
          ].join('');
        }).join('');

        if (isAppend) {
          feedContainer.innerHTML += html;
        } else {
          feedContainer.innerHTML = html || '<p class="jacana-rp-no-reviews">No reviews yet — be the first to share your experience.</p>';
        }

        if (loadMoreBtn) {
          loadMoreBtn.hidden = !data.has_more;
        }
      }).catch(function() {
        if (loader) loader.hidden = true;
      });
    }

    // Toggle Form
    toggleBtns.forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        if (!formDrawer) return;
        var isHidden = formDrawer.hidden;
        formDrawer.hidden = !isHidden;
        
        if (isHidden) {
          // Add a small delay for animation to kick in via CSS
          setTimeout(function() {
            formDrawer.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }, 100);
        }
      });
    });

    // Star Picker Interaction
    var ratingPicker = root.querySelector('[data-jacana-rating-picker]');
    if (ratingPicker) {
      var stars = ratingPicker.querySelectorAll('.jacana-rp-star-btn');
      var hiddenInput = ratingPicker.querySelector('input[type="hidden"]');
      
      stars.forEach(function(star, idx) {
        star.addEventListener('mouseenter', function() {
          var val = parseInt(this.getAttribute('data-value'), 10);
          stars.forEach(function(s, i) {
            s.classList.toggle('is-hover', (i + 1) <= val);
          });
        });
        
        star.addEventListener('mouseleave', function() {
          stars.forEach(function(s) { s.classList.remove('is-hover'); });
        });
        
        star.addEventListener('click', function() {
          var val = parseInt(this.getAttribute('data-value'), 10);
          hiddenInput.value = val;
          stars.forEach(function(s, i) {
            s.classList.toggle('is-active', (i + 1) <= val);
          });
        });
      });
      
      // Init state
      var initialVal = parseInt(hiddenInput.value || '5', 10);
      stars.forEach(function(s, i) {
        s.classList.toggle('is-active', (i + 1) <= initialVal);
      });
    }

    // Form Submission
    if (form) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        var statusEl = form.querySelector('[data-jacana-review-status]');
        var submitBtn = form.querySelector('button[type="submit"]');
        
        var formData = new FormData(form);
        var payload = {};
        formData.forEach(function(value, key) { payload[key] = value; });
        
        if (statusEl) {
          statusEl.textContent = 'Submitting…';
          statusEl.className = 'jacana-rp-status';
        }
        if (submitBtn) submitBtn.disabled = true;

        fetchJson(config.reviewsEndpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        }, { source: 'review_submission' }).then(function(res) {
          if (res && res.ok) {
            form.reset();
            // Re-init star state to 5 after reset
            var hiddenRating = form.querySelector('input[name="rating"]');
            if (hiddenRating) hiddenRating.value = '5';
            var allStars = form.querySelectorAll('.jacana-rp-star-btn');
            allStars.forEach(function(s, i) { s.classList.toggle('is-active', (i + 1) <= 5); });
            if (statusEl) {
              statusEl.textContent = 'Thank you — your review will appear once approved.';
              statusEl.className = 'jacana-rp-status is-success';
            }
            setTimeout(function() {
              if (formDrawer) formDrawer.hidden = true;
              if (statusEl) { statusEl.textContent = ''; statusEl.className = 'jacana-rp-status'; }
            }, 3200);
          } else {
            throw new Error('Submission failed');
          }
        }).catch(function() {
          if (statusEl) {
            statusEl.textContent = 'Something went wrong. Please try again.';
            statusEl.className = 'jacana-rp-status is-error';
          }
        }).finally(function() {
          if (submitBtn) submitBtn.disabled = false;
        });
      });
    }

    loadStats();
    loadReviews(false);

    if (loadMoreBtn) {
      loadMoreBtn.addEventListener('click', function() {
        offset += limit;
        loadReviews(true);
      });
    }
  }


  function escapeHtml(text) {
    if (!text) return '';
    var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
  }

  function bindAiSurfaceClicks() {
    document.addEventListener('click', function (event) {
      var bookingNode = event.target.closest('[data-jacana-booking-modal], a[href]');
      if (bookingNode && isBookingLink(bookingNode) && !bookingNode.hasAttribute('data-jacana-ai-flow')) {
        if (isModifiedClick(event)) {
          return;
        }
        event.preventDefault();
        event.stopPropagation();
        openBookingModal(extractContextFromNode(bookingNode));
        return;
      }

      var genericCta = event.target.closest('.jacana-tours-widget a.button, .jacana-tours-widget button.button');
      if (genericCta && !genericCta.hasAttribute('data-jacana-ai-flow') && !genericCta.hasAttribute('data-ai-chip-action') && !genericCta.hasAttribute('data-map-chat-cta') && !genericCta.hasAttribute('data-jacana-open-review-form')) {
        var genericHref = genericCta.getAttribute('href') || '';
        var genericLabel = trimText(genericCta.textContent || '', 120).toLowerCase();
        if (!genericHref || genericHref === '#') {
          if (/quote|book|booking|request/i.test(genericLabel)) {
            event.preventDefault();
            event.stopPropagation();
            openBookingModal(extractContextFromNode(genericCta));
            return;
          }
        }
        if (!genericHref || genericHref === '#') {
          event.preventDefault();
          event.stopPropagation();
          var genericContext = extractContextFromNode(genericCta);
          openPlanner(genericContext.vehicle ? 'vehicle' : 'service', genericContext);
          return;
        }
      }

      var node = event.target.closest('[data-jacana-ai-flow], [data-ai-chip-action], [data-ai-rail-action], [data-ai-success-booking]');
      if (!node) {
        return;
      }

      if (node.hasAttribute('data-ai-success-booking')) {
        event.preventDefault();
        openBookingModal({});
        return;
      }

      var railAction = node.getAttribute('data-ai-rail-action');
      if (railAction) {
        event.preventDefault();
        if (railAction === 'booking') {
          openBookingModal({ widgetName: 'rail' });
          return;
        }
        openPlanner('planner', { widgetName: 'rail' });
        return;
      }

      var chipAction = node.getAttribute('data-ai-chip-action');
      if (chipAction) {
        event.preventDefault();
        var chipContext = extractContextFromNode(node);
        state.service = chipContext.service || state.service;
        state.vehicle = chipContext.vehicle || state.vehicle;
        state.destination = chipContext.destination || state.destination;
        updateJourneySummary();
        if (chipAction === 'booking') {
          openBookingModal(chipContext);
          return;
        }
        openPlanner(chipAction, chipContext);
        return;
      }

      var flow = node.getAttribute('data-jacana-ai-flow');
      if (!flow) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      var context = extractContextFromNode(node);
      state.service = context.service || state.service;
      state.vehicle = context.vehicle || state.vehicle;
      state.destination = context.destination || state.destination;
      updateJourneySummary();
      if (flow === 'booking') {
        openBookingModal(context);
        return;
      }
      openPlanner(flow, context);
    }, true);
  }

  window.addEventListener('jacana:booking-modal', function (event) {
    var detail = event && event.detail ? event.detail : {};
    openBookingModal({ widgetName: detail.source || 'site_event' });
  });

  function bindRail() {
    if (!railEnabled || !rail) {
      return;
    }
    var toggle = root.querySelector('[data-ai-rail-toggle]');
    var dismiss = root.querySelector('[data-ai-rail-dismiss]');
    if (!toggle) {
      return;
    }
    toggle.addEventListener('click', function () {
      var isExpanded = rail.classList.toggle('is-expanded');
      toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
      if (railActions) {
        railActions.hidden = !isExpanded;
        railActions.setAttribute('aria-hidden', isExpanded ? 'false' : 'true');
      }
      if (isExpanded) {
        clearRailAutoDismissTimer();
      } else {
        scheduleRailAutoDismiss();
      }
    });
    if (dismiss) {
      dismiss.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        dismissRail(true);
      });
    }
    rail.addEventListener('mouseenter', clearRailAutoDismissTimer);
    rail.addEventListener('mouseleave', function () {
      if (!rail.classList.contains('is-expanded')) {
        scheduleRailAutoDismiss();
      }
    });
  }

  function initGlobalInsight() {
    if (!canShowMinor()) {
      updateJourneySummary();
      return;
    }
    window.setTimeout(function () {
      var target = document.querySelector('.hero, .jacana-gallery-hero, .jacana-services-hub .section-header, .jacana-rental-fleet-explorer .section-header, .jacana-tailor-made-story, .jacana-faq-knowledge-widget .section-header');
      if (!target) {
        return;
      }
      getProfile().then(function (profile) {
        return requestInsight({
          page_type: state.pageType,
          title: config.pageTitle,
          url: window.location.href,
          profile: profile,
          service: state.service,
          vehicle: state.vehicle,
          destination: state.destination
        });
      }).then(function (insight) {
        renderInsightCard(target, insight || fallbackInsight());
      });
    }, Math.max(6000, (config.delaySeconds || 8) * 1000));
  }

  bindSheetInteractions();
  normalizeBookingLinks();
  bindAiSurfaceClicks();
  bindRail();
  adaptContactPage();
  adaptServiceCards();
  adaptCarRentalOffers();
  adaptCarRentalShowcase();
  adaptFleetExplorer();
  adaptTailorMadeStory();
  adaptToursInclusions();
  adaptAccommodationStyles();
  bindAccommodationStyleDetails();
  adaptTeamProfiles();
  adaptSocialProof();
  adaptFaqAndDownloads();
  adaptGallery();
  adaptHighlightsMap();
  adaptReviewsPage();
  initGlobalInsight();
  updateJourneySummary();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
    return;
  }

  boot();
})();
