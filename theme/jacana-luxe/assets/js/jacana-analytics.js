(function () {
  function uuid() {
    return 'xxxxxxxxxxxx4xxxyxxxxxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
      var r = (Math.random() * 16) | 0;
      var v = c === 'x' ? r : (r & 0x3) | 0x8;
      return v.toString(16);
    });
  }

  var visitorKey = localStorage.getItem('jacana_visitor_key');
  if (!visitorKey) {
    visitorKey = uuid();
    localStorage.setItem('jacana_visitor_key', visitorKey);
  }

  var sessionKey = sessionStorage.getItem('jacana_session_key');
  if (!sessionKey) {
    sessionKey = uuid();
    sessionStorage.setItem('jacana_session_key', sessionKey);
  }

  var startedAt = new Date();
  var startedAtIso = startedAt.toISOString();

  function sendEvent(type, payload) {
    if (!window.jacanaAnalytics || !window.jacanaAnalytics.endpoint) {
      return;
    }
    fetch(window.jacanaAnalytics.endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        visitor_key: visitorKey,
        session_key: sessionKey,
        type: type,
        payload: payload || {}
      }),
      keepalive: true
    }).catch(function () {});
  }

  sendEvent('pageview_start', {
    url: window.location.href,
    title: document.title,
    referrer: document.referrer,
    started_at: startedAtIso
  });

  sendEvent('session_start', {
    started_at: startedAtIso
  });

  function sendEndEvent() {
    var durationSec = Math.max(1, Math.round((Date.now() - startedAt.getTime()) / 1000));
    sendEvent('pageview_end', {
      url: window.location.href,
      title: document.title,
      referrer: document.referrer,
      started_at: startedAtIso,
      duration_sec: durationSec
    });
  }

  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') {
      sendEndEvent();
    }
  });

  window.addEventListener('beforeunload', function () {
    sendEndEvent();
  });
})();
