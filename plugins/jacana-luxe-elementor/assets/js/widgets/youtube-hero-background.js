(function () {
  'use strict';

  var WIDGET_SELECTOR = '.jacana-ytbg-hero';
  var initialized = new WeakSet();
  var apiCallbacks = [];
  var apiRequested = false;

  function markReady(widgetEl) {
    widgetEl.classList.remove('is-loading');
    widgetEl.classList.add('is-ready');
  }

  function onYouTubeApiReady(callback) {
    if (window.YT && window.YT.Player) {
      callback();
      return;
    }

    apiCallbacks.push(callback);

    if (apiRequested) {
      return;
    }

    apiRequested = true;

    var previousReady = window.onYouTubeIframeAPIReady;
    window.onYouTubeIframeAPIReady = function () {
      if (typeof previousReady === 'function') {
        try {
          previousReady();
        } catch (e) {}
      }

      while (apiCallbacks.length) {
        var cb = apiCallbacks.shift();
        try {
          cb();
        } catch (e) {}
      }
    };

    var script = document.createElement('script');
    script.src = 'https://www.youtube.com/iframe_api';
    script.async = true;
    document.head.appendChild(script);
  }

  function setupWidget(widgetEl) {
    if (!widgetEl || initialized.has(widgetEl)) {
      return;
    }
    initialized.add(widgetEl);

    var videoId = (widgetEl.getAttribute('data-video-id') || '').trim();
    var host = widgetEl.querySelector('[data-player-host]');

    if (!/^[A-Za-z0-9_-]{11}$/.test(videoId) || !host) {
      markReady(widgetEl);
      return;
    }

    var playerId = 'jacana-ytbg-player-' + Math.random().toString(36).slice(2, 11);
    host.id = playerId;

    var readyApplied = false;
    var applyReady = function () {
      if (readyApplied) {
        return;
      }
      readyApplied = true;
      markReady(widgetEl);
    };

    onYouTubeApiReady(function () {
      if (!(window.YT && window.YT.Player)) {
        return;
      }

      var player = new window.YT.Player(playerId, {
        host: 'https://www.youtube-nocookie.com',
        videoId: videoId,
        playerVars: {
          autoplay: 1,
          controls: 0,
          rel: 0,
          modestbranding: 1,
          mute: 1,
          playsinline: 1,
          loop: 1,
          playlist: videoId,
          iv_load_policy: 3,
          disablekb: 1,
          fs: 0,
          cc_load_policy: 0,
          enablejsapi: 1,
          origin: window.location.origin
        },
        events: {
          onReady: function (event) {
            try {
              event.target.mute();
              event.target.playVideo();
            } catch (e) {}
          },
          onStateChange: function (event) {
            if (event.data === window.YT.PlayerState.PLAYING) {
              applyReady();
            }
          }
        }
      });

      widgetEl._jacanaYtBgPlayer = player;
    });
  }

  function init(context) {
    var root = context && context.querySelectorAll ? context : document;
    var widgets = root.querySelectorAll(WIDGET_SELECTOR);
    widgets.forEach(setupWidget);
  }

  document.addEventListener('DOMContentLoaded', function () {
    init(document);
  });

  window.addEventListener('elementor/frontend/init', function () {
    if (!window.elementorFrontend || !window.elementorFrontend.hooks) {
      return;
    }

    window.elementorFrontend.hooks.addAction(
      'frontend/element_ready/jacana_youtube_hero_background.default',
      function ($scope) {
        init($scope[0]);
      }
    );
  });
})();
