(function () {
  var PROFILE_KEY = 'jacana_profile';
  var LEGACY_PROFILE_KEY = 'jacana_chat_profile';

  var state = {
    isOpen: false,
    history: [],
    awaiting: false,
    visitorKey: localStorage.getItem('jacana_visitor_key') || '',
    sessionKey: sessionStorage.getItem('jacana_session_key') || '',
    crmProfile: null,
    leadPromptShown: false,
    leadCaptured: false,
    userMessageCount: 0,
    nudged: false,
    currentHotspot: '',
    leadFlow: {
      active: false,
      step: '',
      collected: {}
    }
  };

  var cfg = window.jacanaConcierge || {};
  // jacanaI18n is populated by the lang-switcher inline script which can load
  // after this script. Read it lazily via a getter so the concierge always sees
  // the fully-populated object even when the i18n script loads after us.
  var i18n = { get labels() { return (window.jacanaI18n || {}).labels || {}; },
               get strings() { return (window.jacanaI18n || {}).strings || {}; },
               get currentLanguage() { return (window.jacanaI18n || {}).currentLanguage || 'en'; },
               get translateText() { return (window.jacanaI18n || {}).translateText; } };
  var chatEndpoint = cfg.chatEndpoint || '';
  var chatHistoryEndpoint = cfg.chatHistoryEndpoint || '';
  var chatSessionsEndpoint = cfg.chatSessionsEndpoint || '';
  var chatSessionLabelEndpoint = cfg.chatSessionLabelEndpoint || '';
  var aiEndpoint = cfg.aiEndpoint || '';
  var aiLogEndpoint = cfg.aiLogEndpoint || '';
  var leadEndpoint = cfg.leadEndpoint || '';
  var profileEndpoint = cfg.profileEndpoint || '';
  var conversionEndpoint = cfg.conversionEndpoint || '';
  var whatsapp = cfg.whatsapp || '';
  var logo = cfg.logo || '';
  var trackEndpoint = cfg.trackEndpoint || '';
  var mapsKey = cfg.mapsKey || '';
  var mapsReady = false;
  var pendingMaps = [];
  var containerEl = null;
  var triggerEl = null;
  var bookingModalEl = null;
  var smartPromptEl = null;
  var clientErrorThrottle = {};

  function uuid() {
    return 'xxxxxxxxxxxx4xxxyxxxxxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
      var r = (Math.random() * 16) | 0;
      var v = c === 'x' ? r : (r & 0x3) | 0x8;
      return v.toString(16);
    });
  }

  function parseJSON(value, fallback) {
    try {
      var parsed = JSON.parse(value);
      return parsed && typeof parsed === 'object' ? parsed : fallback;
    } catch (e) {
      return fallback;
    }
  }

  function getSiteLanguage() {
    var lang = String(cfg.lang || i18n.currentLanguage || document.documentElement.getAttribute('lang') || 'en').toLowerCase();
    if (lang.indexOf('-') !== -1) {
      lang = lang.split('-')[0];
    }
    return lang || 'en';
  }

  function label(key, fallback) {
    if (i18n && i18n.labels && Object.prototype.hasOwnProperty.call(i18n.labels, key) && i18n.labels[key]) {
      return i18n.labels[key];
    }
    return fallback;
  }

  function speak(english, german) {
    var lang = getSiteLanguage();
    var translated = i18n && typeof i18n.translateText === 'function' ? i18n.translateText(english) : english;

    if (lang !== 'en' && translated && translated !== english) {
      return translated;
    }

    if (lang === 'de' && german) {
      return german;
    }

    return english;
  }

  function getProfile() {
    var unified = parseJSON(localStorage.getItem(PROFILE_KEY) || '{}', {});
    var legacy = parseJSON(localStorage.getItem(LEGACY_PROFILE_KEY) || '{}', {});
    if (!legacy || typeof legacy !== 'object') {
      return unified;
    }
    return Object.assign({}, unified, legacy);
  }

  function setProfile(patch) {
    var profile = Object.assign({}, getProfile(), patch || {});
    localStorage.setItem(PROFILE_KEY, JSON.stringify(profile));
    localStorage.setItem(LEGACY_PROFILE_KEY, JSON.stringify(profile));
    return profile;
  }

  function ensureVisitorKey() {
    if (!state.visitorKey) {
      state.visitorKey = uuid();
      localStorage.setItem('jacana_visitor_key', state.visitorKey);
    }
  }

  function setSessionKey(key) {
    if (!key) {
      return;
    }
    state.sessionKey = key;
    sessionStorage.setItem('jacana_session_key', key);
  }

  function logEvent(type, payload) {
    if (!trackEndpoint) {
      return;
    }
    fetchJsonWithLogging(trackEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        visitor_key: state.visitorKey,
        session_key: state.sessionKey,
        type: type,
        payload: payload || {}
      })
    }, { source: 'chatbot_track' }).catch(function () {});
  }

  function trackConversion(stage, payload) {
    var data = Object.assign({
      stage: stage,
      page_url: window.location.href,
      persona: document.body.getAttribute('data-jacana-persona') || '',
      intent_score: parseInt(getProfile().intent || 0, 10) || 0
    }, payload || {});

    logEvent('conversion_' + stage, data);

    if (!conversionEndpoint || !state.visitorKey || !state.sessionKey) {
      return;
    }

    fetchJsonWithLogging(conversionEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        visitor_key: state.visitorKey,
        session_key: state.sessionKey,
        stage: stage,
        service: data.service || '',
        label: data.label || '',
        page_url: data.page_url || window.location.href,
        persona: data.persona || '',
        intent_score: data.intent_score || 0,
        meta: data
      })
    }, { source: 'chatbot_conversion' }).catch(function () {});
  }

  function debugLog(message, data) {
    if (window.localStorage.getItem('jacana_chat_debug') !== '1') {
      return;
    }
    try {
      console.log('[Jacana Chat]', message, data || {});
    } catch (e) {}
  }

  function reportClientError(payload) {
    if (!aiLogEndpoint) {
      return;
    }
    payload = payload || {};
    var key = [
      payload.source || 'chatbot',
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
        visitor_key: state.visitorKey || '',
        session_key: state.sessionKey || '',
        source: payload.source || 'chatbot',
        level: payload.level || 'error',
        message: payload.message || 'frontend_error',
        endpoint: payload.endpoint || '',
        status: payload.status || 0,
        page_url: window.location.href,
        meta: payload.meta || {}
      })
    }).catch(function () {});
  }

  function fetchJsonWithLogging(url, options, meta) {
    meta = meta || {};
    return fetch(url, options || {}).then(function (res) {
      return res.text().then(function (raw) {
        var data = {};
        if (raw) {
          try {
            data = JSON.parse(raw);
          } catch (parseError) {
            reportClientError({
              source: meta.source || 'chatbot',
              message: 'json_parse_error',
              endpoint: url,
              status: res.status,
              meta: {
                parse_error: String((parseError && parseError.message) || parseError || ''),
                excerpt: String(raw).slice(0, 260)
              }
            });
          }
        }

        if (!res.ok) {
          reportClientError({
            source: meta.source || 'chatbot',
            message: 'http_error',
            endpoint: url,
            status: res.status,
            meta: { response: data }
          });
          if (data && typeof data === 'object') {
            data.__jacanaLogged = true;
            throw data;
          }
          throw { __jacanaLogged: true, error: 'http_error', status: res.status };
        }
        return data;
      });
    }).catch(function (error) {
      if (!(error && error.__jacanaLogged)) {
        reportClientError({
          source: meta.source || 'chatbot',
          message: 'network_error',
          endpoint: url,
          status: 0,
          meta: { error: String((error && error.message) || error || '') }
        });
      }
      throw error;
    });
  }

  function escapeHtml(str) {
    return String(str || '').replace(/[&<>"]/g, function (s) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[s] || s;
    });
  }

  function maybeShowTriggerNudge() {
    if (!triggerEl || state.nudged) {
      return;
    }
    state.nudged = true;
    triggerEl.classList.add('is-nudged');
    window.setTimeout(function () {
      if (triggerEl) {
        triggerEl.classList.remove('is-nudged');
      }
    }, 5000);
  }

  function createUI() {
    var trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'chatbot-trigger';
    trigger.setAttribute('data-jacana-concierge-toggle', 'true');
    trigger.setAttribute('aria-label', label('catalog.concierge.open', 'Open Jacana Concierge'));
    trigger.innerHTML = '<img src="' + logo + '" alt="Chatbot" /><div class="chatbot-trigger-pulse"></div>';

    var container = document.createElement('div');
    container.className = 'chatbot-container';
    container.setAttribute('data-jacana-concierge', 'true');
    container.innerHTML =
      '<div class="chatbot-header">' +
      '<div class="chatbot-header-content">' +
      '<button class="chatbot-back" aria-label="Back">&#8592;</button>' +
      '<div class="chatbot-avatar"><div class="chatbot-avatar-inner"><img src="' + logo + '" alt="Jacana" /></div></div>' +
      '<div class="chatbot-info"><h6>' + escapeHtml(label('catalog.concierge.name', 'Jana • Jacana Concierge')) + '</h6><p class="chatbot-subtitle">' + escapeHtml(label('catalog.concierge.subtitle', 'Your Namibian guide')) + '</p></div>' +
      '</div>' +
      '<button class="chatbot-close" aria-label="Close">&times;</button>' +
      '</div>' +
      '<div class="chatbot-content">' +
      '<div class="chatbot-messages" id="chatbotMessages"></div>' +
      '<div class="chatbot-input" id="chatbotInput">' +
      '<input type="text" class="chatbot-input-field" id="chatbotInputField" placeholder="' + escapeHtml(label('catalog.concierge.ask_placeholder', 'Ask about tours, dates, pricing...')) + '">' +
      '<button class="chatbot-send-btn" id="chatbotSendBtn" aria-label="Send message">&#10148;</button>' +
      '</div>' +
      '</div>';

    document.body.appendChild(trigger);
    document.body.appendChild(container);
    createBookingModal();
    createSmartPrompt();

    containerEl = container;
    triggerEl = trigger;

    trigger.addEventListener('click', function () {
      openChat({ skipPicker: true });
    });

    container.querySelector('.chatbot-close').addEventListener('click', function () {
      closeChat();
    });

    container.querySelector('.chatbot-back').addEventListener('click', function () {
      resetConversation();
      showSessionPicker();
    });

    var sendBtn = container.querySelector('#chatbotSendBtn');
    var input = container.querySelector('#chatbotInputField');

    sendBtn.addEventListener('click', function () {
      var text = input.value;
      input.value = '';
      sendUserMessage(text);
    });

    input.addEventListener('keypress', function (event) {
      if (event.key === 'Enter') {
        var text = input.value;
        input.value = '';
        sendUserMessage(text);
      }
    });
  }

  function createBookingModal() {
    var modal = document.createElement('div');
    modal.className = 'jacana-booking-modal';
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML =
      '<div class="jacana-booking-modal-backdrop" data-jacana-modal-close></div>' +
      '<div class="jacana-booking-modal-dialog" role="dialog" aria-modal="true" aria-label="Start your safari plan">' +
      '<button type="button" class="jacana-booking-modal-close" data-jacana-modal-close aria-label="Close">&times;</button>' +
      '<div class="jacana-booking-modal-head"><strong>' + escapeHtml(label('catalog.booking.modal_title', speak('Start Your Tailor-Made Safari', 'Starte deine individuelle Safari'))) + '</strong><p>' + escapeHtml(label('catalog.booking.modal_body', speak('Share a few details and Jana will pass your request to our travel team.', 'Teile ein paar Details, und Jana gibt deine Anfrage an unser Reiseteam weiter.'))) + '</p></div>' +
      '<div class="jacana-booking-modal-form">' +
      '<input type="text" data-booking-field="name" placeholder="' + escapeHtml(label('catalog.booking.name', speak('Your name', 'Dein Name'))) + '">' +
      '<input type="email" data-booking-field="email" placeholder="' + escapeHtml(label('catalog.booking.email', speak('Email', 'E-Mail'))) + '">' +
      '<input type="text" data-booking-field="travel_dates" placeholder="' + escapeHtml(label('catalog.booking.dates', speak('Travel dates', 'Reisedaten'))) + '">' +
      '<textarea data-booking-field="message" rows="4" placeholder="' + escapeHtml(label('catalog.booking.message', speak('Tell us what you want to explore in Namibia', 'Erzähl uns, was du in Namibia erleben möchtest'))) + '"></textarea>' +
      '<button type="button" class="button button-primary" data-booking-submit>' + escapeHtml(label('catalog.booking.submit', speak('Let the Adventure Begin', 'Das Abenteuer kann beginnen'))) + '</button>' +
      '</div>' +
      '</div>';

    document.body.appendChild(modal);
    bookingModalEl = modal;

    modal.querySelectorAll('[data-jacana-modal-close]').forEach(function (node) {
      node.addEventListener('click', function () {
        closeBookingModal();
      });
    });

    var submit = modal.querySelector('[data-booking-submit]');
    if (submit) {
      submit.addEventListener('click', function () {
        var payload = {};
        modal.querySelectorAll('[data-booking-field]').forEach(function (field) {
          var key = field.getAttribute('data-booking-field');
          payload[key] = (field.value || '').trim();
        });

        var leadPayload = {
          name: payload.name || '',
          email: payload.email || '',
          travel_dates: payload.travel_dates || '',
          service_interest: state.currentHotspot || getProfile().service_interest || '',
          summary: payload.message || '',
          source: 'booking_modal'
        };

        submitLead(leadPayload).then(function (result) {
          if (result && result.ok) {
            closeBookingModal();
            openChat({ skipPicker: true });
            appendMessage('assistant', ['<div>' + escapeHtml(speak('Thank you. I have your details, and our team will craft ideas for your journey.', 'Danke. Ich habe deine Angaben und unser Team erstellt passende Reiseideen für dich.')) + '</div>']);
            trackConversion('lead_submit', { label: 'Booking modal lead' });
          } else {
            appendMessage('assistant', ['<div>' + escapeHtml(speak('I could not save that just now. Please try again or continue chatting with me here.', 'Das konnte gerade nicht gespeichert werden. Bitte versuche es erneut oder chatte hier mit mir weiter.')) + '</div>']);
          }
        });
      });
    }
  }

  function openBookingModal(prefill) {
    if (!bookingModalEl) {
      return;
    }

    var profile = getProfile();
    var merged = Object.assign({}, profile, prefill || {});
    bookingModalEl.setAttribute('aria-hidden', 'false');
    bookingModalEl.classList.add('is-open');

    bookingModalEl.querySelectorAll('[data-booking-field]').forEach(function (field) {
      var key = field.getAttribute('data-booking-field');
      if (!key) {
        return;
      }
      if (key === 'message' && merged.service_interest) {
        field.value = merged.message || ('I am interested in ' + merged.service_interest + '.');
        return;
      }
      field.value = merged[key] || '';
    });
  }

  function closeBookingModal() {
    if (!bookingModalEl) {
      return;
    }
    bookingModalEl.setAttribute('aria-hidden', 'true');
    bookingModalEl.classList.remove('is-open');
  }

  function createSmartPrompt() {
    var prompt = document.createElement('div');
    prompt.className = 'jacana-smart-prompt';
    prompt.setAttribute('aria-hidden', 'true');
    prompt.innerHTML =
      '<button type="button" class="jacana-smart-prompt-close" data-smart-close aria-label="Close">&times;</button>' +
      '<div class="jacana-smart-prompt-kicker">Jana Insight</div>' +
      '<div class="jacana-smart-prompt-title" data-smart-title></div>' +
      '<div class="jacana-smart-prompt-message" data-smart-message></div>' +
      '<div class="jacana-smart-prompt-question" data-smart-question></div>' +
      '<div class="jacana-smart-prompt-actions">' +
      '<button type="button" class="button button-primary" data-smart-cta></button>' +
      '<button type="button" class="button" data-smart-dismiss></button>' +
      '</div>';

    document.body.appendChild(prompt);
    smartPromptEl = prompt;

    var close = prompt.querySelector('[data-smart-close]');
    var dismiss = prompt.querySelector('[data-smart-dismiss]');
    if (close) {
      close.addEventListener('click', function () {
        hideSmartPrompt('close');
      });
    }
    if (dismiss) {
      dismiss.addEventListener('click', function () {
        hideSmartPrompt('dismiss');
      });
    }
  }

  function hideSmartPrompt(reason) {
    if (!smartPromptEl) {
      return;
    }
    smartPromptEl.classList.remove('is-visible');
    smartPromptEl.setAttribute('aria-hidden', 'true');
    if (reason) {
      trackConversion('smart_prompt_dismiss', { label: reason });
    }
  }

  function showSmartPrompt(data) {
    if (!smartPromptEl || !data || state.isOpen) {
      return;
    }

    var titleNode = smartPromptEl.querySelector('[data-smart-title]');
    var messageNode = smartPromptEl.querySelector('[data-smart-message]');
    var questionNode = smartPromptEl.querySelector('[data-smart-question]');
    var ctaNode = smartPromptEl.querySelector('[data-smart-cta]');
    var dismissNode = smartPromptEl.querySelector('[data-smart-dismiss]');

    if (!titleNode || !messageNode || !questionNode || !ctaNode || !dismissNode) {
      return;
    }

    titleNode.textContent = data.title || 'Need help planning?';
    messageNode.textContent = data.message || '';
    questionNode.textContent = data.question || '';
    ctaNode.textContent = data.cta_label || 'Ask Jana';
    dismissNode.textContent = data.dismiss_label || 'Not now';

    ctaNode.onclick = function () {
      var action = String(data.cta_action || 'open_chat');
      trackConversion('smart_prompt_click', {
        label: data.cta_label || 'smart_prompt',
        service: data.service || '',
        action: action
      });

      hideSmartPrompt();

      if (action === 'open_booking') {
        openBookingModal({ service_interest: data.service || getProfile().service_interest || '' });
        return;
      }
      if (action === 'navigate' && data.cta_link) {
        window.location.href = data.cta_link;
        return;
      }

      openChat({ skipPicker: true });
      if (data.cta_message) {
        sendUserMessage(data.cta_message);
      }
    };

    smartPromptEl.classList.add('is-visible');
    smartPromptEl.setAttribute('aria-hidden', 'false');
    trackConversion('smart_prompt_shown', {
      label: data.cta_label || '',
      action: data.cta_action || ''
    });
  }

  function setWelcomeMode(enabled) {
    if (!containerEl) {
      return;
    }
    if (enabled) {
      containerEl.classList.add('is-welcome');
    } else {
      containerEl.classList.remove('is-welcome');
    }
  }

  function openChat(options) {
    if (!containerEl || !triggerEl) {
      return;
    }
    options = options || {};

    containerEl.classList.add('active');
    triggerEl.classList.add('hidden');
    state.isOpen = true;
    hideSmartPrompt();
    logEvent('chatbot_open', {});
    trackConversion('chat_open', { label: 'Chat opened' });

    if (!state.history.length && options.skipPicker) {
      resetConversation();
      startConversation();
      return;
    }

    if (!state.history.length) {
      showSessionPicker();
    }
  }

  function closeChat() {
    if (!containerEl || !triggerEl) {
      return;
    }

    containerEl.classList.remove('active');
    triggerEl.classList.remove('hidden');
    state.isOpen = false;
    logEvent('chatbot_close', {});
  }

  function logChat(role, content) {
    if (!chatEndpoint || !content) {
      return;
    }

    fetchJsonWithLogging(chatEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        visitor_key: state.visitorKey,
        session_key: state.sessionKey,
        role: role,
        content: content
      })
    }, { source: 'chatbot_chat_log' }).catch(function () {});
  }

  function appendMessage(role, elements) {
    var container = document.getElementById('chatbotMessages');
    if (!container) {
      return;
    }

    var wrapper = document.createElement('div');
    wrapper.className = 'chatbot-message';

    if (role === 'user') {
      var user = document.createElement('div');
      user.className = 'user-message';
      user.textContent = elements[0] || '';
      wrapper.appendChild(user);
    } else {
      var botWrap = document.createElement('div');
      botWrap.className = 'bot-message-wrapper';
      var avatar = document.createElement('div');
      avatar.className = 'bot-avatar-small';
      avatar.innerHTML = '<div class="bot-avatar-small-inner"><img src="' + logo + '" alt="Jacana" /></div>';

      var bot = document.createElement('div');
      bot.className = 'bot-message';
      bot.innerHTML = (elements || []).join('');

      botWrap.appendChild(avatar);
      botWrap.appendChild(bot);
      wrapper.appendChild(botWrap);
    }

    container.appendChild(wrapper);
    container.scrollTop = container.scrollHeight;
  }

  function showTyping() {
    var container = document.getElementById('chatbotMessages');
    if (!container) {
      return;
    }

    var wrap = document.createElement('div');
    wrap.className = 'chatbot-message';
    wrap.id = 'typingIndicator';
    wrap.innerHTML = '<div class="bot-message-wrapper"><div class="bot-avatar-small"><div class="bot-avatar-small-inner"><img src="' + logo + '" alt="AI" /></div></div><div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div></div>';

    container.appendChild(wrap);
    container.scrollTop = container.scrollHeight;
  }

  function hideTyping() {
    var typing = document.getElementById('typingIndicator');
    if (typing) {
      typing.remove();
    }
  }

  function profileGreeting() {
    if (state.crmProfile && state.crmProfile.greeting) {
      return state.crmProfile.greeting;
    }

    var profile = getProfile();
    if (profile.name) {
      return speak(
        'Welcome back, ' + profile.name + '! Ready to continue exploring Namibia\'s secrets?',
        'Willkommen zurück, ' + profile.name + '! Bereit, Namibias Geheimnisse weiter zu entdecken?'
      );
    }

    return speak(
      'Hello, I\'m Jana, your local Namibian safari guide. Tell me what you dream of seeing, and I\'ll help shape your journey.',
      'Hallo, ich bin Jana, deine lokale Namibia-Safari-Guide. Sag mir, was du erleben möchtest, und ich helfe dir bei der Planung.'
    );
  }

  function getQuickReplyOptions() {
    var profile = getProfile();
    var focus = state.crmProfile && Array.isArray(state.crmProfile.service_focus) ? state.crmProfile.service_focus : [];
    var options = [];

    focus.forEach(function (item) {
      if (item && options.indexOf(item) === -1) {
        options.push(item);
      }
    });

    ['Game Drive in Etosha', 'Car rental options', 'Self-drive itinerary', 'Airport transfers', 'Pricing estimate'].forEach(function (item) {
      if (options.indexOf(item) === -1) {
        options.push(item);
      }
    });

    if (profile.service_interest && options.indexOf(profile.service_interest) === -1) {
      options.unshift(profile.service_interest);
    }

    return options.slice(0, 5);
  }

  function startConversation() {
    if (!state.sessionKey) {
      setSessionKey(uuid());
    }

    setWelcomeMode(false);

    appendMessage('assistant', [
      '<div><strong>' + escapeHtml(profileGreeting()) + '</strong></div>'
    ]);

    var options = getQuickReplyOptions().map(function (option) {
      return '<button class="quick-reply-btn" data-opt="' + escapeHtml(option) + '" data-label="' + escapeHtml(option) + '"><span class="text">' + escapeHtml(option) + '</span></button>';
    }).join('');

    appendMessage('assistant', [
      '<div class="quick-replies">' + options + '</div>'
    ]);

    bindDynamicButtons();
  }

  function loadHistory(sessionKey) {
    if (!chatHistoryEndpoint || !state.visitorKey) {
      return Promise.resolve(false);
    }

    setWelcomeMode(false);

    var url = chatHistoryEndpoint + '?visitor_key=' + encodeURIComponent(state.visitorKey);
    if (sessionKey) {
      url += '&session_key=' + encodeURIComponent(sessionKey);
    }

    return fetchJsonWithLogging(url, {}, { source: 'chatbot_history' })
      .then(function (data) {
        if (!data || !data.ok || !data.messages || !data.messages.length) {
          return false;
        }

        data.messages.forEach(function (msg) {
          state.history.push({ role: msg.role, content: msg.content });
          if (msg.role === 'user') {
            state.userMessageCount += 1;
            appendMessage('user', [msg.content]);
          } else {
            renderStoredAssistant(msg.content);
          }
        });

        return true;
      })
      .catch(function () {
        return false;
      });
  }

  function showSessionPicker() {
    var container = document.getElementById('chatbotMessages');
    if (!container) {
      return;
    }

    ensureVisitorKey();
    if (!state.sessionKey) {
      setSessionKey(uuid());
    }

    setWelcomeMode(true);
    appendMessage('assistant', [
      '<div class="chatbot-welcome"><strong>Welcome to Jacana Concierge.</strong><br>Pick up where you left off or start a new Namibia planning session.</div>'
    ]);

    renderSessionOptions();
  }

  function renderSessionOptions() {
    if (!chatSessionsEndpoint || !state.visitorKey) {
      startConversation();
      return;
    }

    fetchJsonWithLogging(chatSessionsEndpoint + '?visitor_key=' + encodeURIComponent(state.visitorKey), {}, { source: 'chatbot_sessions' })
      .then(function (data) {
        if (!data || !data.ok) {
          startConversation();
          return;
        }

        var sessions = data.sessions || [];
        var optionsHtml = '<div class="session-list">';
        optionsHtml += '<div class="session-card" role="button" data-session="new"><span class="session-title">Start new session</span><span class="session-meta">Fresh itinerary planning</span></div>';

        if (!sessions.length) {
          optionsHtml += '<div class="session-empty">No previous sessions yet. Start a new chat to build your itinerary.</div>';
        }

        sessions.forEach(function (session) {
          var label = session.label ? session.label : 'Resume chat';
          var preview = session.first_message ? session.first_message : 'Previous conversation';
          optionsHtml += '<div class="session-card" role="button" data-session="' + escapeHtml(session.session_key) + '">' +
            '<span class="session-title">' + escapeHtml(label) + '</span>' +
            '<span class="session-preview">' + escapeHtml(preview).slice(0, 90) + '</span>' +
            '<span class="session-meta">' + escapeHtml(formatSessionMeta(session)) + '</span>' +
            '<span class="session-badge">' + escapeHtml(formatLastActive(session.last_seen)) + '</span>' +
            '<button class="session-rename" data-session="' + escapeHtml(session.session_key) + '" type="button">Rename</button>' +
            '</div>';
        });

        optionsHtml += '</div>';
        appendMessage('assistant', [optionsHtml]);
        bindSessionButtons();
      })
      .catch(function () {
        startConversation();
      });
  }

  function bindSessionButtons() {
    var container = document.getElementById('chatbotMessages');
    if (!container) {
      return;
    }

    container.querySelectorAll('.session-card[data-session]').forEach(function (button) {
      if (button.dataset.bound === '1') {
        return;
      }
      button.dataset.bound = '1';

      button.addEventListener('click', function () {
        var session = button.getAttribute('data-session');
        if (session === 'new') {
          resetConversation();
          setSessionKey(uuid());
          trackConversion('session_resume', { label: 'new_session' });
          startConversation();
          return;
        }

        resetConversation();
        setSessionKey(session);
        trackConversion('session_resume', { label: 'resume_session' });
        loadHistory(session).then(function (loaded) {
          if (!loaded) {
            startConversation();
          }
        });
      });
    });

    container.querySelectorAll('.session-rename[data-session]').forEach(function (button) {
      if (button.dataset.bound === '1') {
        return;
      }
      button.dataset.bound = '1';
      button.addEventListener('click', function (event) {
        event.stopPropagation();
        promptRenameSession(button.getAttribute('data-session'));
      });
    });
  }

  function resetConversation() {
    state.history = [];
    state.userMessageCount = 0;
    state.leadPromptShown = false;

    var container = document.getElementById('chatbotMessages');
    if (container) {
      container.innerHTML = '';
    }
  }

  function inferIntentFromMessage(text) {
    var value = String(text || '').toLowerCase();

    if (/book|reservation|quote|price|cost|rate|availability|inquiry|contact/.test(value)) {
      trackConversion('booking_start', { label: text.slice(0, 80) });
    }

    if (/car|4x4|vehicle|rental|fortuner|hilux|suv/.test(value)) {
      setProfile({ service_interest: 'Car Rentals' });
      trackConversion('vehicle_interest', { service: 'Car Rentals', label: text.slice(0, 80) });
    }

    if (/etosha|game drive|safari|wildlife/.test(value)) {
      setProfile({ service_interest: 'Game Drive' });
      trackConversion('service_interest', { service: 'Game Drive', label: text.slice(0, 80) });
    }

    if (/caprivi|zambezi region|sossusvlei|swakopmund|skeleton coast|damaraland|etosha/.test(value)) {
      var cleaned = text.slice(0, 80);
      state.currentHotspot = cleaned;
      setProfile({ service_interest: cleaned });
      trackConversion('service_interest', { service: cleaned, label: cleaned });
    }
  }

  function sendUserMessage(text) {
    var trimmed = (text || '').trim();
    if (!trimmed || state.awaiting) {
      return;
    }

    state.awaiting = true;
    state.userMessageCount += 1;
    state.history.push({ role: 'user', content: trimmed });

    appendMessage('user', [trimmed]);
    logChat('user', trimmed);
    logEvent('chatbot_message', { role: 'user', content: trimmed.slice(0, 200) });
    inferIntentFromMessage(trimmed);

    if (state.leadFlow.active) {
      handleLeadCaptureResponse(trimmed);
      state.awaiting = false;
      return;
    }

    if (/availability|available next week|next departure|departure date/i.test(trimmed)) {
      appendMessage('assistant', ['<div>' + escapeHtml(speak('Great question. We do not run fixed departures because every journey is tailor-made. Share your preferred dates and style, and I will help you shape the best route.', 'Gute Frage. Wir haben keine festen Abfahrten, weil jede Reise individuell geplant wird. Teile mir deine Wunschdaten und deinen Stil mit, dann plane ich die beste Route mit dir.')) + '</div>']);
      state.awaiting = false;
      if (!state.leadCaptured) {
        maybeOfferLeadCapture('availability_question');
      }
      return;
    }

    showTyping();

    callAI(trimmed).then(function (response) {
      hideTyping();
      renderAI(response);
      state.awaiting = false;

      if (state.userMessageCount >= 3 && !state.leadCaptured) {
        maybeOfferLeadCapture('after_discussion');
      }
    });
  }

  function buildPrompt(message) {
    var profile = getProfile();
    var pageInfo = {
      url: window.location.href,
      title: document.title,
      h1: document.querySelector('h1') ? document.querySelector('h1').textContent : ''
    };
    var today = new Date().toISOString().slice(0, 10);
    var siteLang = getSiteLanguage();

    var crm = state.crmProfile || {};
    var history = state.history.slice(-10).map(function (item) {
      return item.role + ': ' + item.content;
    }).join('\n');

    var system =
      'You are Jana, a passionate Namibian safari guide with over 15 years of experience. You know local roads, seasons, waterholes, and hidden gems.\n' +
      'Today is ' + today + '. Current site language is ' + siteLang + '. Always respond in ' + siteLang + '.\n' +
      'Tone: warm, human, practical, storyteller. Use emojis very sparingly.\n' +
      'Never mention real-time availability, fixed departure dates, or pre-packaged tours. Everything is tailor-made.\n' +
      'If asked about availability, explain that journeys are customized and you gather preferences first.\n' +
      'Do not repeat questions if details were already provided.\n' +
      'If the user gives destination/date (example: Caprivi Strip on 30 June 2026), acknowledge it directly and propose useful next planning steps.\n' +
      'Respond ONLY as JSON: {"components":[...]}. Allowed component types: text, options, card, list, cta, form, itinerary, pricing, tier, timing, trip_style.\n' +
      'Do not use map_route or map_places components.\n' +
      'Use CTA wording naturally from this set when relevant: "Let the adventure begin", "Yes, I am ready", "Get more information", "Contact us now", "Get a personalised quote".\n' +
      'CTA actions should prefer "lead_capture", "booking", or "whatsapp".\n' +
      'Known profile: ' + JSON.stringify(profile) + '\n' +
      'CRM profile: ' + JSON.stringify(crm) + '\n' +
      'Current hotspot context: ' + JSON.stringify(state.currentHotspot || '') + '\n' +
      'Page context: ' + JSON.stringify(pageInfo) + '\n';

    if (state.pendingContext) {
      system += 'Context for this response: ' + state.pendingContext + '\n';
      state.pendingContext = null;
    }

    return system + '\nConversation:\n' + history + '\nUser: ' + message;
  }

  function callAI(message) {
    if (!aiEndpoint) {
      return Promise.resolve({ components: [{ type: 'text', content: 'AI endpoint not configured.' }] });
    }

    return fetchAI(buildPrompt(message), 0);
  }

  function extractJsonObject(text) {
    if (!text) {
      return '';
    }

    var cleaned = String(text).trim();

    if (cleaned.indexOf('```') !== -1) {
      cleaned = cleaned.replace(/```json|```/gi, '').trim();
    }

    var start = cleaned.indexOf('{');
    var end = cleaned.lastIndexOf('}');
    if (start === -1 || end === -1 || end < start) {
      return '';
    }

    return cleaned.slice(start, end + 1);
  }

  function coerceAIResponse(text) {
    var raw = String(text || '').trim();
    if (!raw) {
      return { components: [{ type: 'text', content: 'Sorry, I did not get that. Could you rephrase your request?' }] };
    }

    var candidate = extractJsonObject(raw);
    if (candidate) {
      try {
        var parsed = JSON.parse(candidate);
        if (parsed && Array.isArray(parsed.components)) {
          return parsed;
        }
      } catch (e) {}
    }

    // If model returned plain text, render it instead of dropping to a generic fallback.
    raw = raw.replace(/```json|```/gi, '').trim();
    return { components: [{ type: 'text', content: raw }] };
  }

  function fetchAI(prompt, attempt) {
    return fetchJsonWithLogging(aiEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ prompt: prompt })
    }, { source: 'chatbot_ai' })
      .then(function (data) {
        if (data && data.ok === false) {
          if (attempt < 1) {
            return fetchAI(prompt, attempt + 1);
          }
          return { components: [{ type: 'text', content: 'AI error: ' + (data.error || 'unknown') }] };
        }

        var text = '';
        if (data && data.data && data.data.candidates && data.data.candidates[0]) {
          var parts = data.data.candidates[0].content.parts;
          text = parts && parts[0] ? parts[0].text : '';
        }
        return coerceAIResponse(text);
      })
      .catch(function () {
        if (attempt < 1) {
          return fetchAI(prompt, attempt + 1);
        }
        return { components: [{ type: 'text', content: 'Sorry, I could not reach the assistant.' }] };
      });
  }

  function normalizeOptions(options) {
    if (!options) {
      return [];
    }

    if (Array.isArray(options)) {
      return options.map(function (opt) {
        if (typeof opt === 'string') {
          return { label: opt, value: opt };
        }
        if (opt && typeof opt === 'object') {
          return { label: opt.label || opt.value || '', value: opt.value || opt.label || '' };
        }
        return { label: String(opt), value: String(opt) };
      });
    }

    return [];
  }

  function normalizeList(items) {
    if (!items) {
      return [];
    }
    if (Array.isArray(items)) {
      return items;
    }
    if (typeof items === 'string') {
      return [items];
    }
    return [];
  }

  function renderAI(response, opts) {
    var options = opts || {};

    if (!response || !Array.isArray(response.components)) {
      appendMessage('assistant', ['<div>Sorry, I did not get that.</div>']);
      return;
    }

    var htmlBlocks = response.components.map(function (component) {
      if (!component || !component.type) {
        return '';
      }

      if (component.type === 'text') {
        return '<div>' + escapeHtml(component.content || '') + '</div>';
      }

      if (component.type === 'options') {
        var optionButtons = normalizeOptions(component.options).map(function (opt) {
          var label = opt.label || opt.value || '';
          var value = opt.value || opt.label || '';
          return '<button class="quick-reply-btn" data-opt="' + escapeHtml(value) + '" data-label="' + escapeHtml(label) + '"><span class="text">' + escapeHtml(label) + '</span></button>';
        }).join('');
        return '<div class="quick-replies">' + optionButtons + '</div>';
      }

      if (component.type === 'card') {
        return '<div class="info-card"><div class="info-card-title">' + escapeHtml(component.title || '') + '</div><div class="info-card-content">' + escapeHtml(component.content || '') + '</div></div>';
      }

      if (component.type === 'list') {
        var listItems = normalizeList(component.items).map(function (item) {
          return '<li>' + escapeHtml(item) + '</li>';
        }).join('');
        return '<div class="info-card"><div class="info-card-title">' + escapeHtml(component.title || '') + '</div><ul>' + listItems + '</ul></div>';
      }

      if (component.type === 'cta') {
        var action = component.action || '';
        var text = component.text || 'Continue';
        var href = component.href || component.url || '';

        if (href) {
          return '<a class="quick-reply-btn" data-cta-link="' + escapeHtml(href) + '" href="' + escapeHtml(href) + '"><span class="text">' + escapeHtml(text) + '</span></a>';
        }

        return '<button class="quick-reply-btn" data-action="' + escapeHtml(action) + '"><span class="text">' + escapeHtml(text) + '</span></button>';
      }

      if (component.type === 'form') {
        var fields = Array.isArray(component.fields) ? component.fields : [];
        var fieldHtml = fields.map(function (field) {
          var name = field.name || '';
          var label = field.label || name;
          var type = field.type || (name === 'email' ? 'email' : 'text');
          return '<input type="' + escapeHtml(type) + '" class="chatbot-input-field" data-field="' + escapeHtml(name) + '" placeholder="' + escapeHtml(label) + '">';
        }).join('');

        return '<div class="info-card">' +
          '<div class="info-card-title">' + escapeHtml(component.title || 'Share your details') + '</div>' +
          '<div class="chatbot-inline-form">' + fieldHtml + '<button class="quick-reply-btn" data-form-submit="true"><span class="text">' + escapeHtml(component.submitText || 'Send') + '</span></button></div>' +
          '</div>';
      }

      if (component.type === 'itinerary') {
        var days = (component.days || []).map(function (day) {
          return '<li><strong>Day ' + escapeHtml(day.day || '') + ':</strong> ' + escapeHtml(day.title || '') + ' - ' + escapeHtml(day.detail || '') + '</li>';
        }).join('');
        return '<div class="info-card"><div class="info-card-title">' + escapeHtml(component.title || 'Itinerary Preview') + '</div><ul>' + days + '</ul></div>';
      }

      if (component.type === 'pricing') {
        return '<div class="info-card"><div class="info-card-title">Estimated Range</div><div class="info-card-content"><strong>' + escapeHtml(component.range || '') + '</strong><br>' + escapeHtml(component.note || '') + '</div></div>';
      }

      if (component.type === 'tier') {
        var tiers = (component.options || []).map(function (item) {
          return '<li><strong>' + escapeHtml(item.label || '') + ':</strong> ' + escapeHtml(item.detail || '') + '</li>';
        }).join('');
        return '<div class="info-card"><div class="info-card-title">Accommodation Tiers</div><ul>' + tiers + '</ul></div>';
      }

      if (component.type === 'timing') {
        var months = (component.months || []).map(function (month) {
          return '<span class="quick-reply-btn" data-opt="' + escapeHtml(month) + '" data-label="' + escapeHtml(month) + '"><span class="text">' + escapeHtml(month) + '</span></span>';
        }).join('');
        return '<div class="info-card"><div class="info-card-title">Best Months</div><div>' + months + '</div><div class="info-card-content">' + escapeHtml(component.note || '') + '</div></div>';
      }

      if (component.type === 'trip_style') {
        var styles = (component.options || []).map(function (item) {
          return '<li><strong>' + escapeHtml(item.label || '') + ':</strong> ' + escapeHtml(item.detail || '') + '</li>';
        }).join('');
        return '<div class="info-card"><div class="info-card-title">Trip Style</div><ul>' + styles + '</ul></div>';
      }

      if (component.type === 'map_route') {
        return '<div class="info-card"><div class="info-card-title">Route Inspiration</div><div class="info-card-content">I can suggest a flexible route concept based on your interests and travel pace.</div></div>';
      }

      if (component.type === 'map_places') {
        return '<div class="info-card"><div class="info-card-title">Places Inspiration</div><div class="info-card-content">Tell me your dates and interests and I will suggest areas that fit your trip style.</div></div>';
      }

      return '';
    });

    appendMessage('assistant', htmlBlocks);

    if (!options.skipLog) {
      logChat('assistant', JSON.stringify(response));
      logEvent('chatbot_message', { role: 'assistant', summary: 'components:' + response.components.length });
    }

    bindDynamicButtons();
  }

  function renderStoredAssistant(content) {
    try {
      var parsed = JSON.parse(content);
      if (parsed && parsed.components) {
        renderAI(parsed, { skipLog: true });
        return;
      }
    } catch (e) {}

    appendMessage('assistant', ['<div>' + escapeHtml(content) + '</div>']);
  }

  function buildConversationSummary() {
    var sample = state.history.slice(-8).map(function (entry) {
      var prefix = entry.role === 'user' ? 'Guest' : 'Jacana';
      return prefix + ': ' + entry.content;
    });
    return sample.join('\n').slice(0, 1600);
  }

  function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());
  }

  function isPositiveReply(value) {
    return /^(yes|yep|yeah|sure|okay|ok|please do|go ahead|confirm)/i.test(String(value || '').trim());
  }

  function isNegativeReply(value) {
    return /^(no|nope|not now|later|cancel|stop)/i.test(String(value || '').trim());
  }

  function getNextLeadStep(collected) {
    if (!collected.name) {
      return 'name';
    }
    if (!collected.email && !collected.phone) {
      return 'email_or_phone';
    }
    if (!collected.travel_dates) {
      return 'travel_dates';
    }
    if (!collected.service_interest) {
      return 'service_interest';
    }
    return 'confirm';
  }

  function promptLeadStep() {
    var step = state.leadFlow.step;
    if (step === 'name') {
      appendMessage('assistant', ['<div>' + escapeHtml(speak('I would love to help. What name should I use for your itinerary notes?', 'Ich helfe dir gern. Welchen Namen soll ich für deine Reiseplanung verwenden?')) + '</div>']);
      return;
    }
    if (step === 'email_or_phone') {
      appendMessage('assistant', ['<div>' + escapeHtml(speak('Great. What is the best email or WhatsApp number so we can send your personalised plan?', 'Super. Welche E-Mail oder WhatsApp-Nummer ist am besten, damit wir dir deinen persönlichen Plan senden können?')) + '</div>']);
      return;
    }
    if (step === 'travel_dates') {
      appendMessage('assistant', ['<div>' + escapeHtml(speak('Nice. What travel dates are you considering?', 'Perfekt. Welche Reisedaten planst du?')) + '</div>']);
      return;
    }
    if (step === 'service_interest') {
      appendMessage('assistant', ['<div>' + escapeHtml(speak('Which part excites you most right now (for example Etosha game drive, Caprivi, Sossusvlei, or car rental)?', 'Was begeistert dich gerade am meisten (z. B. Etosha Game Drive, Caprivi, Sossusvlei oder Mietwagen)?')) + '</div>']);
      return;
    }
    if (step === 'confirm') {
      var summary = state.leadFlow.collected;
      appendMessage('assistant', ['<div>' + escapeHtml(speak('Perfect. I have ', 'Perfekt. Ich habe ')) + '<strong>' + escapeHtml(summary.name || speak('your details', 'deine Angaben')) + '</strong>, <strong>' + escapeHtml(summary.travel_dates || speak('your dates', 'deine Reisedaten')) + '</strong>, ' + escapeHtml(speak('and interest in', 'und Interesse an')) + ' <strong>' + escapeHtml(summary.service_interest || speak('Namibia travel', 'Namibia-Reise')) + '</strong>. ' + escapeHtml(speak('Shall I send this to our team now?', 'Soll ich das jetzt an unser Team senden?')) + '</div><div class="quick-replies"><button class="quick-reply-btn" data-opt="' + escapeHtml(speak('Yes, send it', 'Ja, senden')) + '" data-label="' + escapeHtml(speak('Yes, send it', 'Ja, senden')) + '"><span class="text">' + escapeHtml(speak('Yes, send it', 'Ja, senden')) + '</span></button><button class="quick-reply-btn" data-opt="' + escapeHtml(speak('Not yet', 'Noch nicht')) + '" data-label="' + escapeHtml(speak('Not yet', 'Noch nicht')) + '"><span class="text">' + escapeHtml(speak('Not yet', 'Noch nicht')) + '</span></button></div>']);
      bindDynamicButtons();
    }
  }

  function startLeadCapture(reason) {
    if (state.leadCaptured) {
      return;
    }

    state.leadPromptShown = true;
    state.leadFlow.active = true;
    state.leadFlow.collected = Object.assign({}, getProfile(), state.leadFlow.collected || {});
    if (state.currentHotspot && !state.leadFlow.collected.service_interest) {
      state.leadFlow.collected.service_interest = state.currentHotspot;
    }
    if (!state.leadFlow.collected.service_interest && state.crmProfile && state.crmProfile.service_focus && state.crmProfile.service_focus[0]) {
      state.leadFlow.collected.service_interest = state.crmProfile.service_focus[0];
    }
    state.leadFlow.reason = reason || 'conversation';
    state.leadFlow.step = getNextLeadStep(state.leadFlow.collected);
    promptLeadStep();
  }

  function completeLeadCapture() {
    var payload = state.leadFlow.collected || {};
    payload.summary = payload.summary || buildConversationSummary();
    payload.source = 'chatbot_conversational';
    submitLead(payload).then(function (result) {
      if (result && result.ok) {
        state.leadFlow.active = false;
        state.leadFlow.step = '';
        appendMessage('assistant', ['<div>' + escapeHtml(speak('Thank you. Your request is with us now. We will craft your tailor-made Namibia plan and contact you shortly.', 'Vielen Dank. Deine Anfrage ist bei uns eingegangen. Wir erstellen deinen individuellen Namibia-Plan und melden uns in Kürze.')) + '</div>']);
      } else {
        appendMessage('assistant', ['<div>' + escapeHtml(speak('I could not submit that right now. You can try once more, or share details via WhatsApp.', 'Das konnte gerade nicht gesendet werden. Du kannst es erneut versuchen oder uns per WhatsApp schreiben.')) + '</div>']);
      }
    });
  }

  function handleLeadCaptureResponse(text) {
    if (!state.leadFlow.active) {
      return false;
    }

    var value = String(text || '').trim();
    if (!value) {
      return true;
    }

    var step = state.leadFlow.step;
    var collected = state.leadFlow.collected;

    if (step === 'name') {
      collected.name = value;
    } else if (step === 'email_or_phone') {
      if (isValidEmail(value)) {
        collected.email = value;
      } else {
        collected.phone = value;
      }
    } else if (step === 'travel_dates') {
      collected.travel_dates = value;
    } else if (step === 'service_interest') {
      collected.service_interest = value;
    } else if (step === 'confirm') {
      if (isPositiveReply(value)) {
        completeLeadCapture();
      } else if (isNegativeReply(value)) {
        state.leadFlow.active = false;
        state.leadFlow.step = '';
        appendMessage('assistant', ['<div>' + escapeHtml(speak('No problem. We can continue exploring ideas first, then submit whenever you are ready.', 'Kein Problem. Wir können zuerst weiter Ideen sammeln und später senden, wenn du bereit bist.')) + '</div>']);
      } else {
        appendMessage('assistant', ['<div>' + escapeHtml(speak('Please reply with “Yes” when you want me to send it, or “Not yet”.', 'Bitte antworte mit „Ja“, wenn ich es senden soll, oder mit „Noch nicht“.')) + '</div>']);
      }
      return true;
    }

    state.leadFlow.collected = collected;
    setProfile(collected);
    state.leadFlow.step = getNextLeadStep(collected);
    promptLeadStep();
    return true;
  }

  function maybeOfferLeadCapture(reason) {
    if (state.leadCaptured || state.leadFlow.active) {
      return;
    }
    startLeadCapture(reason || 'timed_prompt');
  }

  function submitLead(payload) {
    if (!leadEndpoint) {
      return Promise.resolve({ ok: false, error: 'missing_lead_endpoint' });
    }

    var profile = getProfile();
    var merged = Object.assign({}, profile, payload || {});
    var summary = buildConversationSummary();

    var requestBody = {
      visitor_key: state.visitorKey,
      session_key: state.sessionKey,
      source: 'chatbot',
      source_page: window.location.href,
      name: merged.name || '',
      email: merged.email || '',
      phone: merged.phone || '',
      country: merged.country || '',
      travel_dates: merged.travel_dates || '',
      duration: merged.duration || '',
      party_size: merged.party_size || '',
      budget_tier: merged.budget_tier || '',
      travel_style: merged.travel_style || '',
      service_interest: merged.service_interest || '',
      interests: merged.interests || merged.service_interest || '',
      summary: merged.summary || summary
    };

    return fetchJsonWithLogging(leadEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(requestBody)
    }, { source: 'chatbot_lead_submit' })
      .then(function (data) {
        if (data && data.ok) {
          state.leadCaptured = true;
          setProfile(merged);
          trackConversion('lead_submit', {
            service: requestBody.service_interest || '',
            label: 'Chat lead form submitted'
          });
        }
        return data;
      })
      .catch(function () {
        return { ok: false, error: 'network' };
      });
  }

  function bindDynamicButtons() {
    var container = document.getElementById('chatbotMessages');
    if (!container) {
      return;
    }

    container.querySelectorAll('.quick-reply-btn[data-opt]').forEach(function (button) {
      if (button.dataset.bound === '1') {
        return;
      }
      button.dataset.bound = '1';
      button.addEventListener('click', function () {
        var value = button.getAttribute('data-opt');
        var label = button.getAttribute('data-label') || value;
        storeAnswer(value, label);
        sendUserMessage(label);
      });
    });

    container.querySelectorAll('.quick-reply-btn[data-action]').forEach(function (button) {
      if (button.dataset.bound === '1') {
        return;
      }
      button.dataset.bound = '1';
      button.addEventListener('click', function () {
        var action = button.getAttribute('data-action');
        if (action === 'whatsapp' && whatsapp) {
          trackConversion('whatsapp_click', { label: 'Chat CTA WhatsApp' });
          window.open('https://wa.me/' + whatsapp.replace(/[^\d]/g, ''), '_blank');
          return;
        }

        if (action === 'lead_capture') {
          startLeadCapture('cta_action');
          return;
        }

        if (action === 'booking') {
          trackConversion('booking_start', { label: 'Chat CTA booking' });
          openBookingModal({ service_interest: state.currentHotspot || getProfile().service_interest || '' });
          return;
        }
      });
    });

    container.querySelectorAll('[data-cta-link]').forEach(function (link) {
      if (link.dataset.bound === '1') {
        return;
      }
      link.dataset.bound = '1';
      link.addEventListener('click', function (event) {
        trackConversion('booking_start', { label: (link.textContent || '').trim() });
        var href = link.getAttribute('href') || '';
        if (href.indexOf('/booking') !== -1 || href === '#') {
          event.preventDefault();
          openBookingModal({ service_interest: state.currentHotspot || getProfile().service_interest || '' });
        }
      });
    });

    container.querySelectorAll('.quick-reply-btn[data-form-submit]').forEach(function (button) {
      if (button.dataset.bound === '1') {
        return;
      }
      button.dataset.bound = '1';
      button.addEventListener('click', function () {
        var parent = button.closest('.info-card');
        if (!parent) {
          return;
        }

        var payload = {};
        parent.querySelectorAll('.chatbot-input-field[data-field]').forEach(function (input) {
          var field = input.getAttribute('data-field');
          if (field) {
            payload[field] = input.value;
          }
        });

        submitLead(payload).then(function (result) {
          if (result && result.ok) {
            appendMessage('assistant', ['<div>Perfect. We captured your request and the Jacana team will contact you shortly.</div>']);
          } else {
            appendMessage('assistant', ['<div>I could not submit that right now. Please try again or use WhatsApp.</div>']);
          }
        });
      });
    });

    container.querySelectorAll('.quick-reply-btn[data-lead-form-submit]').forEach(function (button) {
      if (button.dataset.bound === '1') {
        return;
      }
      button.dataset.bound = '1';
      button.addEventListener('click', function () {
        var parent = button.closest('.info-card');
        if (!parent) {
          return;
        }

        var payload = {};
        parent.querySelectorAll('.chatbot-input-field[data-lead-field]').forEach(function (input) {
          var field = input.getAttribute('data-lead-field');
          if (field) {
            payload[field] = input.value;
          }
        });

        submitLead(payload).then(function (result) {
          if (result && result.ok) {
            appendMessage('assistant', ['<div>Thanks. Your quote request is in. We will follow up with a personalized plan shortly.</div>']);
          } else {
            appendMessage('assistant', ['<div>I could not submit that. Please verify email/phone and try again.</div>']);
          }
        });
      });
    });
  }

  function storeAnswer(value, label) {
    var patch = {};
    if (/luxury|adventure|romantic|family|guided|self-drive|game drive|rental|transfer|flight|hotel/i.test(value || '')) {
      patch.service_interest = label || value;
    }
    setProfile(patch);

    if (patch.service_interest) {
      trackConversion('service_interest', { service: patch.service_interest, label: patch.service_interest });
    }
  }

  function queueMap(kind, id, items) {
    pendingMaps.push({ kind: kind, id: id, items: items });
    loadMaps();
  }

  function loadMaps() {
    if (mapsReady || !mapsKey) {
      return;
    }

    if (window.google && window.google.maps) {
      mapsReady = true;
      renderMaps();
      return;
    }

    var script = document.createElement('script');
    script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(mapsKey) + '&libraries=places';
    script.onload = function () {
      mapsReady = true;
      renderMaps();
    };
    document.head.appendChild(script);
  }

  function renderMaps() {
    if (!mapsReady) {
      return;
    }

    pendingMaps.forEach(function (entry) {
      var el = document.getElementById(entry.id);
      if (!el) {
        return;
      }
      if (entry.kind === 'route') {
        renderRouteMap(el, entry.items);
      } else {
        renderPlacesMap(el, entry.items);
      }
    });
    pendingMaps = [];
  }

  function renderRouteMap(el, stops) {
    if (!stops || stops.length < 2) {
      return;
    }

    var points = stops.map(function (item) {
      if (item && typeof item.lat === 'number' && typeof item.lng === 'number') {
        return { lat: item.lat, lng: item.lng, label: item.label || '' };
      }
      return null;
    }).filter(Boolean);

    if (points.length < 2) {
      return;
    }

    var map = new google.maps.Map(el, { zoom: 6, center: { lat: points[0].lat, lng: points[0].lng } });
    var directionsService = new google.maps.DirectionsService();
    var directionsRenderer = new google.maps.DirectionsRenderer({ map: map, suppressMarkers: false });

    directionsService.route({
      origin: { lat: points[0].lat, lng: points[0].lng },
      destination: { lat: points[points.length - 1].lat, lng: points[points.length - 1].lng },
      waypoints: points.slice(1, -1).map(function (point) {
        return { location: { lat: point.lat, lng: point.lng }, stopover: true };
      }),
      travelMode: 'DRIVING'
    }, function (result, status) {
      if (status === 'OK') {
        directionsRenderer.setDirections(result);
      }
    });
  }

  function renderPlacesMap(el, places) {
    if (!places || !places.length) {
      return;
    }

    var map = new google.maps.Map(el, { zoom: 6, center: { lat: places[0].lat, lng: places[0].lng } });
    places.forEach(function (place) {
      new google.maps.Marker({ position: { lat: place.lat, lng: place.lng }, map: map, title: place.label || '' });
    });
  }

  function formatSessionMeta(session) {
    var started = session.started_at ? new Date(session.started_at.replace(' ', 'T')) : null;
    var dateText = started && !isNaN(started) ? started.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) : 'Recent';
    var timeText = started && !isNaN(started) ? started.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }) : '';
    var pageviews = session.pageviews ? session.pageviews + ' pageviews' : '';
    var duration = session.total_time_sec ? Math.max(1, Math.round(session.total_time_sec / 60)) + ' min' : '';
    return [dateText, timeText, pageviews, duration].filter(Boolean).join(' • ') || 'Previous session';
  }

  function formatLastActive(lastSeen) {
    if (!lastSeen) {
      return 'Last active: recently';
    }

    var date = new Date(String(lastSeen).replace(' ', 'T'));
    if (isNaN(date)) {
      return 'Last active: recently';
    }

    var diffMs = Date.now() - date.getTime();
    var diffMin = Math.max(1, Math.round(diffMs / 60000));
    if (diffMin < 60) {
      return 'Last active: ' + diffMin + 'm ago';
    }

    var diffHours = Math.round(diffMin / 60);
    if (diffHours < 24) {
      return 'Last active: ' + diffHours + 'h ago';
    }

    var diffDays = Math.round(diffHours / 24);
    return 'Last active: ' + diffDays + 'd ago';
  }

  function promptRenameSession(sessionKey) {
    if (!chatSessionLabelEndpoint) {
      return;
    }

    var name = window.prompt('Rename this session');
    if (!name) {
      return;
    }

    fetchJsonWithLogging(chatSessionLabelEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        visitor_key: state.visitorKey,
        session_key: sessionKey,
        label: name
      })
    }, { source: 'chatbot_session_label' })
      .then(function () {
        resetConversation();
        showSessionPicker();
      })
      .catch(function () {});
  }

  function getServiceContextFromNode(node) {
    if (!node) {
      return '';
    }

    var attr = node.getAttribute('data-jacana-service') || node.getAttribute('data-service-name') || '';
    if (attr) {
      return attr;
    }

    var card = node.closest('.jacana-service-card, .jacana-rental-details-card, .jacana-map-panel, .jacana-booking-service-button, .jacana-download-card, .jacana-faq-side');
    if (!card) {
      return '';
    }

    var heading = card.querySelector('h3, h4, h2');
    return heading ? heading.textContent.trim() : '';
  }

  function bindWidgetConversionBridge() {
    document.addEventListener('click', function (event) {
      var target = event.target;
      if (!target) {
        return;
      }

      var node = target.closest('.jacana-service-button, .jacana-service-chat, .jacana-rental-detail-link, .jacana-map-hotspot, .jacana-map-place-pill, [data-map-cta], .jacana-booking-service-button, .jacana-download-button, [data-jacana-open-chat], a[href*="/booking"]');
      if (!node) {
        return;
      }

      var label = (node.textContent || '').trim().slice(0, 120);
      var service = getServiceContextFromNode(node) || label;

      if (node.matches('.jacana-map-hotspot, .jacana-map-place-pill')) {
        var mapTitle = node.getAttribute('data-title') || node.getAttribute('data-service-name') || service;
        state.currentHotspot = mapTitle || service;
        trackConversion('map_interest', { service: service, label: label });
        setProfile({ service_interest: state.currentHotspot });
        return;
      } else if (node.matches('.jacana-download-button')) {
        trackConversion('service_interest', { service: 'Downloads', label: label || 'Download resource' });
      } else if (node.matches('.jacana-rental-detail-link')) {
        trackConversion('vehicle_interest', { service: service || 'Car Rentals', label: label });
      } else if (node.matches('.jacana-service-button, .jacana-booking-service-button')) {
        trackConversion('service_interest', { service: service, label: label });
      } else if (node.matches('.jacana-service-chat')) {
        if ((node.getAttribute('data-jacana-ai-flow') || '') !== '') {
          trackConversion('service_interest', { service: service, label: label || 'Open planner' });
          return;
        }
        trackConversion('service_interest', { service: service, label: label || 'Open chat' });
        event.preventDefault();
        event.stopPropagation();
        openChat({ skipPicker: true });
        if (service) {
          state.currentHotspot = service;
          sendUserMessage('I am interested in ' + service + '. Please guide me.');
        }
        return;
      } else if (node.matches('[data-jacana-open-chat]')) {
        trackConversion('service_interest', { service: 'FAQ Concierge', label: label || 'Open chat' });
        event.preventDefault();
        event.stopPropagation();
        openChat({ skipPicker: true });
        return;
      } else {
        trackConversion('booking_start', { service: service, label: label });
      }

      if (service) {
        setProfile({ service_interest: service });
      }

      var href = node.getAttribute('href') || '';
      if (href.indexOf('/booking') !== -1) {
        if ((node.getAttribute('data-jacana-booking-modal') || '') === 'true' && window.location.pathname.indexOf('/booking') === -1) {
          event.preventDefault();
          openBookingModal({ service_interest: service || state.currentHotspot || '' });
          return;
        }
      }

      if (href === '#' || href === '') {
        event.preventDefault();
        openChat({ skipPicker: true });
        if (service) {
          state.currentHotspot = service;
          sendUserMessage('I am interested in ' + service + '. Please guide me.');
        }
      }
    }, true);
  }

  function bindGlobalEvents() {
    window.addEventListener('jacana:intent-updated', function (event) {
      var detail = event && event.detail ? event.detail : {};
      var intent = parseInt(detail.intent || 0, 10) || 0;
      var persona = detail.persona || '';
      var profile = setProfile({ intent: intent, persona: persona });

      if (!state.isOpen && intent >= 65 && !state.leadCaptured) {
        maybeShowTriggerNudge();
      }

      if (state.isOpen && intent >= 80 && !state.leadCaptured && !state.leadPromptShown) {
        maybeOfferLeadCapture('high_intent');
      }

      if (window.localStorage.getItem('jacana_chat_debug') === '1') {
        debugLog('intent_update', profile);
      }
    });

    window.addEventListener('jacana:open-concierge', function (event) {
      var detail = event && event.detail ? event.detail : {};
      openChat({ skipPicker: !!detail.skipPicker });
      if (detail.context) {
        state.pendingContext = detail.context;
      }
      if (detail.message) {
        sendUserMessage(detail.message);
      }
    });

    window.addEventListener('jacana:smart-prompt', function (event) {
      var detail = event && event.detail ? event.detail : null;
      if (!detail) {
        return;
      }
      showSmartPrompt(detail);
    });
  }

  function bootstrapProfile() {
    if (!profileEndpoint || !state.visitorKey) {
      return;
    }

    var url = profileEndpoint + '?visitor_key=' + encodeURIComponent(state.visitorKey);
    if (state.sessionKey) {
      url += '&session_key=' + encodeURIComponent(state.sessionKey);
    }

    fetchJsonWithLogging(url, {}, { source: 'chatbot_profile' })
      .then(function (data) {
        if (!data || !data.ok || !data.profile) {
          return;
        }

        state.crmProfile = data.profile;
        var lead = data.profile.lead || {};

        setProfile({
          name: lead.name || '',
          email: lead.email || '',
          phone: lead.phone || '',
          travel_dates: lead.travel_dates || '',
          party_size: lead.party_size || '',
          service_interest: lead.service_interest || (data.profile.service_focus && data.profile.service_focus[0]) || '',
          intent: data.profile.intent || 0,
          persona: data.profile.persona || 'observer'
        });

        if (lead && (lead.email || lead.phone)) {
          state.leadCaptured = true;
        }
      })
      .catch(function () {});
  }

  function exposeGlobalAPI() {
    window.JacanaConcierge = {
      open: function () {
        openChat({ skipPicker: true });
      },
      close: function () {
        closeChat();
      },
      ask: function (message) {
        openChat({ skipPicker: true });
        if (message) {
          sendUserMessage(String(message));
        }
      },
      trackConversion: function (stage, payload) {
        trackConversion(stage, payload || {});
      }
    };
  }

  ensureVisitorKey();

  if (!state.sessionKey) {
    setSessionKey(uuid());
  }

  createUI();
  bootstrapProfile();
  bindWidgetConversionBridge();
  bindGlobalEvents();
  exposeGlobalAPI();
})();  
