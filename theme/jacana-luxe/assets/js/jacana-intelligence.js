(function () {
  if (!window.jacanaAnalytics || !window.jacanaAnalytics.endpoint) {
    return;
  }

  var endpoint = window.jacanaAnalytics.endpoint;
  var aiEndpoint = window.jacanaAnalytics.aiEndpoint || '';
  var aiLogEndpoint = window.jacanaAnalytics.aiLogEndpoint || '';
  var visitorKey = localStorage.getItem('jacana_visitor_key') || '';
  var sessionKey = sessionStorage.getItem('jacana_session_key') || '';
  var PROFILE_KEY = 'jacana_profile';
  var LEGACY_PROFILE_KEY = 'jacana_chat_profile';

  function parseJSON(value, fallback) {
    try {
      var parsed = JSON.parse(value);
      return parsed && typeof parsed === 'object' ? parsed : fallback;
    } catch (e) {
      return fallback;
    }
  }

  function getProfile() {
    var unified = parseJSON(localStorage.getItem(PROFILE_KEY) || '{}', {});
    var legacy = parseJSON(localStorage.getItem(LEGACY_PROFILE_KEY) || '{}', {});
    return Object.assign({}, legacy, unified);
  }

  function saveProfile(patch) {
    var profile = Object.assign({}, getProfile(), patch || {});
    localStorage.setItem(PROFILE_KEY, JSON.stringify(profile));
    localStorage.setItem(LEGACY_PROFILE_KEY, JSON.stringify(profile));
    return profile;
  }

  var profile = getProfile();

  var state = {
    sections: [],
    sectionDwell: {},
    sectionVisits: {},
    scrollDepth: 0,
    interactions: 0,
    startTime: Date.now(),
    persona: profile.persona || 'observer',
    intent: parseInt(profile.intent || 0, 10) || 0,
    stage: profile.stage || 'discovering',
    ctaClicks: 0,
    bookingSignals: 0,
    recentActions: [],
    smartPromptRequested: false
  };

  var shownInsights = parseJSON(localStorage.getItem('jacana_insights_shown') || '{}', {});
  var smartPromptConfig = (window.jacanaAnalytics && window.jacanaAnalytics.smartPrompt) ? window.jacanaAnalytics.smartPrompt : {};
  var clientErrorThrottle = {};

  function reportAiError(payload) {
    if (!aiLogEndpoint) {
      return;
    }
    payload = payload || {};
    var key = [
      payload.source || 'analytics',
      payload.endpoint || '',
      payload.status || '',
      payload.message || ''
    ].join('|').slice(0, 280);
    var now = Date.now();
    if (clientErrorThrottle[key] && now - clientErrorThrottle[key] < 4000) {
      return;
    }
    clientErrorThrottle[key] = now;

    fetch(aiLogEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        visitor_key: visitorKey,
        session_key: sessionKey,
        source: payload.source || 'analytics',
        level: payload.level || 'error',
        message: payload.message || 'frontend_error',
        endpoint: payload.endpoint || '',
        status: payload.status || 0,
        page_url: window.location.href,
        meta: payload.meta || {}
      })
    }).catch(function () {});
  }

  function getSmartPromptConfig() {
    return {
      enabled: smartPromptConfig.enabled !== false,
      delaySeconds: Math.max(5, Math.min(600, parseInt(smartPromptConfig.delaySeconds || 30, 10) || 30)),
      minIntent: Math.max(0, Math.min(100, parseInt(smartPromptConfig.minIntent || 20, 10) || 20)),
      minInteractions: Math.max(0, parseInt(smartPromptConfig.minInteractions || 2, 10) || 2),
      minScrollDepth: Math.max(0, Math.min(100, parseInt(smartPromptConfig.minScrollDepth || 20, 10) || 20)),
      minDwellSeconds: Math.max(0, parseInt(smartPromptConfig.minDwellSeconds || 20, 10) || 20),
      oncePerSession: smartPromptConfig.oncePerSession !== false,
      promptInstructions: String(smartPromptConfig.promptInstructions || '').trim()
    };
  }

  function sendEvent(type, payload) {
    fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        visitor_key: visitorKey,
        session_key: sessionKey,
        type: type,
        payload: payload || {}
      }),
      keepalive: true
    }).then(function (res) {
      if (res.ok) {
        return;
      }
      reportAiError({
        source: 'analytics_track',
        message: 'http_error',
        endpoint: endpoint,
        status: res.status,
        meta: { event_type: type }
      });
    }).catch(function (error) {
      reportAiError({
        source: 'analytics_track',
        message: 'network_error',
        endpoint: endpoint,
        meta: {
          event_type: type,
          error: String((error && error.message) || error || '')
        }
      });
    });
  }

  function emitIntentUpdate() {
    var detail = {
      persona: state.persona,
      intent: state.intent,
      stage: state.stage
    };

    window.dispatchEvent(new CustomEvent('jacana:intent-updated', { detail: detail }));
  }

  function initSections() {
    var sections = Array.prototype.slice.call(document.querySelectorAll('section, .section'));
    sections.forEach(function (section, index) {
      var id = section.getAttribute('id') || section.getAttribute('data-jacana-section') || 'section-' + index;
      section.setAttribute('data-jacana-section', id);
      state.sections.push({ id: id, el: section });
      state.sectionDwell[id] = 0;
      state.sectionVisits[id] = 0;
    });
  }

  function classifyStage(intent, bookingSignals, ctaClicks) {
    if (intent >= 70 || bookingSignals >= 2 || ctaClicks >= 3) {
      return 'ready_to_book';
    }
    if (intent >= 40 || bookingSignals >= 1) {
      return 'evaluating';
    }
    return 'discovering';
  }

  function updatePersonaIntent() {
    var sectionsViewed = Object.keys(state.sectionVisits).filter(function (key) {
      return state.sectionVisits[key] > 0;
    }).length;

    var totalDwell = Object.keys(state.sectionDwell).reduce(function (sum, key) {
      return sum + (state.sectionDwell[key] || 0);
    }, 0);

    var intent = 0;
    intent += Math.min(35, sectionsViewed * 7);
    intent += Math.min(25, Math.round(totalDwell / 10));
    intent += Math.min(20, state.interactions * 4);
    intent += Math.min(20, state.bookingSignals * 10);

    state.intent = Math.min(100, intent);

    var persona = 'observer';
    if (sectionsViewed >= 3 && totalDwell >= 35) {
      persona = 'dreamer';
    }
    if (state.scrollDepth >= 75 && totalDwell < 30) {
      persona = 'skimmer';
    }
    if (state.interactions >= 3 && totalDwell >= 40) {
      persona = 'planner';
    }
    if (state.intent >= 75) {
      persona = 'ready_to_book';
    }

    state.persona = persona;
    state.stage = classifyStage(state.intent, state.bookingSignals, state.ctaClicks);

    saveProfile({
      persona: state.persona,
      intent: state.intent,
      stage: state.stage
    });

    document.body.setAttribute('data-jacana-persona', state.persona);
    document.body.setAttribute('data-jacana-stage', state.stage);

    sendEvent('persona_intent', {
      persona: state.persona,
      intent: state.intent,
      stage: state.stage,
      sections_viewed: sectionsViewed,
      total_dwell: totalDwell
    });

    sendEvent('journey_stage', {
      stage: state.stage,
      intent: state.intent,
      persona: state.persona
    });

    emitIntentUpdate();
  }

  function trackScrollDepth() {
    var docHeight = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
    if (!docHeight) {
      return;
    }

    var scrollPos = window.scrollY + window.innerHeight;
    var depth = Math.round((scrollPos / docHeight) * 100);
    if (depth <= state.scrollDepth) {
      return;
    }

    state.scrollDepth = depth;
    sendEvent('scroll_depth', { depth: depth });
    updatePersonaIntent();
  }

  function detectServiceLabel(node) {
    if (!node) {
      return '';
    }

    var attr = node.getAttribute('data-jacana-service') || node.getAttribute('data-service-name') || '';
    if (attr) {
      return attr;
    }

    var card = node.closest('.jacana-service-card, .jacana-rental-details-card, .jacana-map-panel, .jacana-booking-service-button');
    if (!card) {
      return '';
    }

    var heading = card.querySelector('h2, h3, h4');
    return heading ? heading.textContent.trim() : '';
  }

  function trackInteractions() {
    document.addEventListener('click', function (event) {
      var target = event.target;
      if (!target) {
        return;
      }

      var actionNode = target.closest('a, button');
      if (!actionNode) {
        return;
      }

      var label = (actionNode.textContent || '').trim().slice(0, 140);
      var href = actionNode.getAttribute('href') || '';
      var service = detectServiceLabel(actionNode) || label;
      var actionKind = actionNode.tagName.toLowerCase();

      state.interactions += 1;
      state.recentActions.push({
        kind: actionKind,
        label: label,
        href: href,
        service: service,
        at: Date.now()
      });
      if (state.recentActions.length > 12) {
        state.recentActions = state.recentActions.slice(state.recentActions.length - 12);
      }
      sendEvent('interaction', {
        tag: actionKind,
        text: label,
        href: href
      });

      var isConversionCta = actionNode.matches('.jacana-service-button, .jacana-rental-detail-link, [data-map-chat-cta], .jacana-booking-service-button, .header-cta, a[href*="/booking"]');
      if (isConversionCta) {
        state.ctaClicks += 1;
        state.bookingSignals += 1;
        sendEvent('widget_engagement', {
          service: service,
          label: label,
          href: href,
          page: window.location.href
        });
      }

      if (/booking|quote|book|reserve/i.test(label + ' ' + href)) {
        state.bookingSignals += 1;
      }

      updatePersonaIntent();
    });
  }

  function topSectionsByDwell(limit) {
    var rows = Object.keys(state.sectionDwell).map(function (id) {
      return { id: id, seconds: state.sectionDwell[id] || 0 };
    }).sort(function (a, b) {
      return b.seconds - a.seconds;
    });
    return rows.slice(0, limit || 3);
  }

  function collectSmartPromptSnapshot() {
    var sectionsViewed = Object.keys(state.sectionVisits).filter(function (key) {
      return state.sectionVisits[key] > 0;
    }).length;
    var totalDwell = Object.keys(state.sectionDwell).reduce(function (sum, key) {
      return sum + (state.sectionDwell[key] || 0);
    }, 0);

    return {
      page_url: window.location.href,
      page_title: document.title,
      elapsed_seconds: Math.round((Date.now() - state.startTime) / 1000),
      persona: state.persona,
      stage: state.stage,
      intent: state.intent,
      scroll_depth: state.scrollDepth,
      interactions: state.interactions,
      cta_clicks: state.ctaClicks,
      booking_signals: state.bookingSignals,
      sections_viewed: sectionsViewed,
      total_dwell_seconds: totalDwell,
      top_sections: topSectionsByDwell(3),
      recent_actions: state.recentActions.slice(-8)
    };
  }

  function meetsSmartPromptConditions(config, snapshot) {
    if (!config.enabled) {
      return false;
    }

    if ((snapshot.intent || 0) < config.minIntent) {
      return false;
    }

    if ((snapshot.interactions || 0) < config.minInteractions) {
      return false;
    }

    if ((snapshot.scroll_depth || 0) < config.minScrollDepth) {
      return false;
    }

    if ((snapshot.total_dwell_seconds || 0) < config.minDwellSeconds) {
      return false;
    }

    return true;
  }

  function observeSections() {
    if (typeof window.IntersectionObserver === 'undefined') {
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        var id = entry.target.getAttribute('data-jacana-section');
        if (!id) {
          return;
        }

        if (entry.isIntersecting) {
          state.sectionVisits[id] += 1;
          sendEvent('section_enter', { section: id });
          entry.target.__jacanaEnter = Date.now();
        } else if (entry.target.__jacanaEnter) {
          var delta = Math.round((Date.now() - entry.target.__jacanaEnter) / 1000);
          state.sectionDwell[id] += delta;
          sendEvent('section_dwell', { section: id, seconds: delta });
          entry.target.__jacanaEnter = null;
          updatePersonaIntent();
        }
      });
    }, { threshold: 0.35 });

    state.sections.forEach(function (section) {
      observer.observe(section.el);
    });
  }

  function shouldShow(key) {
    return !shownInsights[key];
  }

  function markShown(key) {
    shownInsights[key] = true;
    localStorage.setItem('jacana_insights_shown', JSON.stringify(shownInsights));
  }

  function createInsightCard(data) {
    var card = document.createElement('div');
    card.className = 'jacana-insight-card';
    card.innerHTML =
      '<div class="jacana-insight-kicker">' + (data.kicker || 'Jacana Insight') + '</div>' +
      '<div class="jacana-insight-title">' + (data.title || '') + '</div>' +
      '<div class="jacana-insight-body">' + (data.body || '') + '</div>' +
      (data.cta ? '<button class="jacana-insight-cta">' + data.cta + '</button>' : '');

    if (data.cta && data.cta_link) {
      card.querySelector('.jacana-insight-cta').addEventListener('click', function () {
        sendEvent('insight_cta_click', {
          title: data.title || '',
          cta_link: data.cta_link || ''
        });

        if (data.cta_link === 'chat://open') {
          window.dispatchEvent(new CustomEvent('jacana:open-concierge', {
            detail: { message: data.chat_message || 'I need help planning my Namibia trip.' }
          }));
          return;
        }

        if (data.cta_link === 'booking://open') {
          window.dispatchEvent(new CustomEvent('jacana:booking-modal', { detail: { source: 'smart_insight' } }));
          return;
        }

        window.location.href = data.cta_link;
      });
    }

    return card;
  }

  function insertInsight(sectionId, data) {
    var section = document.querySelector('[data-jacana-section="' + sectionId + '"]');
    if (!section) {
      return;
    }

    var existing = section.querySelector('[data-jacana-insight="' + sectionId + '"]');
    if (existing) {
      return;
    }

    var wrapper = document.createElement('div');
    wrapper.className = 'jacana-insight-wrapper';
    wrapper.setAttribute('data-jacana-insight', sectionId);
    wrapper.appendChild(createInsightCard(data));
    section.appendChild(wrapper);
  }

  function requestAI(prompt, fallback) {
    if (!aiEndpoint) {
      return Promise.resolve(fallback);
    }

    return fetch(aiEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ prompt: prompt })
    })
      .then(function (res) {
        if (!res.ok) {
          reportAiError({
            source: 'analytics_ai',
            message: 'http_error',
            endpoint: aiEndpoint,
            status: res.status
          });
          return {};
        }
        return res.json().catch(function (parseError) {
          reportAiError({
            source: 'analytics_ai',
            message: 'json_parse_error',
            endpoint: aiEndpoint,
            status: res.status,
            meta: { error: String((parseError && parseError.message) || parseError || '') }
          });
          return {};
        });
      })
      .then(function (data) {
        if (!data || !data.data || !data.data.candidates || !data.data.candidates[0]) {
          return fallback;
        }

        var text = data.data.candidates[0].content.parts[0].text || '';
        var start = text.indexOf('{');
        var end = text.lastIndexOf('}');
        if (start !== -1 && end !== -1 && end > start) {
          text = text.slice(start, end + 1);
        }

        try {
          return JSON.parse(text);
        } catch (e) {
          return fallback;
        }
      })
      .catch(function (error) {
        reportAiError({
          source: 'analytics_ai',
          message: 'network_error',
          endpoint: aiEndpoint,
          meta: { error: String((error && error.message) || error || '') }
        });
        return fallback;
      });
  }

  function scheduleInsights() {
    window.setInterval(function () {
      var timeOnPage = Math.round((Date.now() - state.startTime) / 1000);
      var firstSection = state.sections[0] ? state.sections[0].id : 'section-0';
      var lastSection = state.sections[state.sections.length - 1] ? state.sections[state.sections.length - 1].id : firstSection;

      if (timeOnPage > 8 && shouldShow('hero_insight')) {
        markShown('hero_insight');
        requestAI(
          'Return JSON with keys kicker,title,body,cta,cta_link. Create one concise luxury Namibia planning tip.',
          {
            kicker: 'Jacana Insight',
            title: 'Build your route around drive comfort',
            body: 'Most Namibia journeys work best when daily drives are balanced with two-night stays in key regions.',
            cta: 'Start planning',
            cta_link: 'booking://open'
          }
        ).then(function (data) {
          insertInsight(firstSection, data);
          sendEvent('insight_shown', { key: 'hero_insight' });
        });
      }

      if (timeOnPage > 18 && state.intent > 35 && shouldShow('service_insight')) {
        markShown('service_insight');
        var fallback = {
          kicker: 'Planning Assist',
          title: 'Need the right service mix?',
          body: 'Combine game drives, transfers and car rental in one request so timing and logistics stay aligned.',
          cta: 'Chat with concierge',
          cta_link: 'chat://open',
          chat_message: 'Help me combine services for one itinerary.'
        };
        insertInsight(firstSection, fallback);
        sendEvent('insight_shown', { key: 'service_insight' });
      }

      if (timeOnPage > 26 && state.intent > 55 && shouldShow('booking_insight')) {
        markShown('booking_insight');
        insertInsight(lastSection, {
          kicker: 'Ready When You Are',
          title: 'Get a tailored quote in under 24 hours',
          body: 'Share your dates and priorities and Jacana will respond with a practical itinerary and service pricing.',
          cta: 'Request booking',
          cta_link: 'booking://open'
        });
        sendEvent('insight_shown', { key: 'booking_insight' });
      }
    }, 4000);
  }

  function requestSmartPrompt() {
    if (state.smartPromptRequested) {
      return;
    }
    var config = getSmartPromptConfig();

    var shownKey = 'jacana_smart_prompt_shown';
    if (config.oncePerSession && sessionStorage.getItem(shownKey) === '1') {
      return;
    }

    var snapshot = collectSmartPromptSnapshot();
    if (!meetsSmartPromptConditions(config, snapshot)) {
      return;
    }
    state.smartPromptRequested = true;

    var fallback = {
      title: 'Need help shaping your route?',
      message: 'I can suggest an itinerary based on your interests, timing and comfort level.',
      question: 'Would you like a quick tailored suggestion?',
      cta_label: 'Start booking',
      cta_action: 'open_booking',
      cta_link: 'booking://open',
      cta_message: 'Please suggest a tailored Namibia route based on what I viewed on this page.',
      dismiss_label: 'Not now'
    };

    var prompt =
      'You are a conversion assistant for a premium Namibia travel website.\n' +
      'Based on this behavior snapshot, return ONE timely micro-popup in strict JSON.\n' +
      'JSON keys: title, message, question, cta_label, cta_action, cta_link, cta_message, dismiss_label.\n' +
      'Allowed cta_action values: open_chat, open_booking, navigate.\n' +
      'Rules: concise, helpful, non-pushy, high-value. Keep title under 8 words.\n' +
      'Behavior snapshot: ' + JSON.stringify(snapshot) + '\n' +
      (config.promptInstructions ? ('Additional instructions: ' + config.promptInstructions) : '');

    requestAI(prompt, fallback).then(function (data) {
      var payload = {
        title: data && data.title ? data.title : fallback.title,
        message: data && data.message ? data.message : fallback.message,
        question: data && data.question ? data.question : fallback.question,
        cta_label: data && data.cta_label ? data.cta_label : fallback.cta_label,
        cta_action: data && data.cta_action ? data.cta_action : fallback.cta_action,
        cta_link: data && data.cta_link ? data.cta_link : fallback.cta_link,
        cta_message: data && data.cta_message ? data.cta_message : fallback.cta_message,
        dismiss_label: data && data.dismiss_label ? data.dismiss_label : fallback.dismiss_label,
        snapshot: snapshot
      };

      if (config.oncePerSession) {
        sessionStorage.setItem(shownKey, '1');
      }
      sendEvent('smart_prompt_generated', {
        action: payload.cta_action,
        label: payload.cta_label,
        intent: state.intent,
        stage: state.stage
      });

      window.dispatchEvent(new CustomEvent('jacana:smart-prompt', { detail: payload }));
    });
  }

  function scheduleSmartPrompt() {
    var config = getSmartPromptConfig();
    if (!config.enabled) {
      return;
    }

    var timer = window.setInterval(function () {
      var elapsed = Math.round((Date.now() - state.startTime) / 1000);
      if (elapsed < config.delaySeconds) {
        return;
      }

      requestSmartPrompt();
      if (state.smartPromptRequested) {
        window.clearInterval(timer);
      }
    }, 5000);
  }

  function init() {
    initSections();
    trackInteractions();
    observeSections();
    scheduleInsights();
    scheduleSmartPrompt();
    window.addEventListener('scroll', trackScrollDepth, { passive: true });
    updatePersonaIntent();
  }

  init();
})();
