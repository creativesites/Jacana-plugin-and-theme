(function () {
  var stickyHeader = document.querySelector('[data-jacana-header]');
  if (!stickyHeader) {
    return;
  }

  function onScroll() {
    stickyHeader.classList.toggle('is-scrolled', window.scrollY > 10);
  }

  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
})();

(function () {
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.querySelector('.primary-nav');
  if (!toggle || !nav) {
    return;
  }
  toggle.addEventListener('click', function () {
    var isOpen = nav.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });
})();

(function () {
  var nav = document.querySelector('.primary-nav');
  if (!nav) {
    return;
  }
  var items = nav.querySelectorAll('.menu-item-has-children');
  items.forEach(function (item) {
    var link = item.querySelector('a');
    var submenu = item.querySelector('.sub-menu');
    if (!link || !submenu) {
      return;
    }
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'submenu-toggle';
    button.setAttribute('aria-expanded', 'false');
    button.addEventListener('click', function (event) {
      event.preventDefault();
      var isOpen = item.classList.toggle('is-open');
      button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    link.after(button);
  });
})();

(function () {
  var slides = document.querySelectorAll('.hero-slide');
  var dots = document.querySelectorAll('.hero-dot');
  if (!slides.length || !dots.length) {
    return;
  }

  var current = 0;
  var interval = 6500;
  var timer;

  function setSlide(index) {
    slides[current].classList.remove('is-active');
    dots[current].classList.remove('is-active');
    current = index;
    slides[current].classList.add('is-active');
    dots[current].classList.add('is-active');
  }

  function nextSlide() {
    var next = current + 1;
    if (next >= slides.length) {
      next = 0;
    }
    setSlide(next);
  }

  function start() {
    timer = window.setInterval(nextSlide, interval);
  }

  function reset() {
    window.clearInterval(timer);
    start();
  }

  dots.forEach(function (dot, index) {
    dot.addEventListener('click', function () {
      setSlide(index);
      reset();
    });
  });

  start();
})();

(function () {
  var buttons = document.querySelectorAll('.tour-gallery-button');
  if (!buttons.length) {
    return;
  }

  var lightbox = document.createElement('div');
  lightbox.className = 'lightbox';
  lightbox.innerHTML = '<button class=\"lightbox-close\" type=\"button\" aria-label=\"Close\">×</button><button class=\"lightbox-nav lightbox-prev\" type=\"button\" aria-label=\"Previous media\"><span aria-hidden=\"true\">&#8249;</span></button><button class=\"lightbox-nav lightbox-next\" type=\"button\" aria-label=\"Next media\"><span aria-hidden=\"true\">&#8250;</span></button><div class=\"lightbox-inner\"><div class=\"lightbox-media\"><img class=\"lightbox-image\" alt=\"\"><div class=\"lightbox-video-wrap\" hidden><iframe class=\"lightbox-video\" title=\"Media preview\" src=\"\" loading=\"lazy\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share\" allowfullscreen></iframe></div></div><div class=\"lightbox-meta\"><div class=\"lightbox-title\"></div><div class=\"lightbox-desc\"></div><div class=\"lightbox-counter\" hidden></div></div></div>';
  document.body.appendChild(lightbox);

  var image = lightbox.querySelector('.lightbox-image');
  var videoWrap = lightbox.querySelector('.lightbox-video-wrap');
  var video = lightbox.querySelector('.lightbox-video');
  var title = lightbox.querySelector('.lightbox-title');
  var desc = lightbox.querySelector('.lightbox-desc');
  var counter = lightbox.querySelector('.lightbox-counter');
  var close = lightbox.querySelector('.lightbox-close');
  var prev = lightbox.querySelector('.lightbox-prev');
  var next = lightbox.querySelector('.lightbox-next');
  var activeButtons = [];
  var activeIndex = -1;

  function normalizeVideoUrl(url) {
    if (!url) {
      return '';
    }
    var value = String(url);

    if (value.indexOf('youtube.com/watch') !== -1) {
      try {
        var parsed = new URL(value);
        var videoId = parsed.searchParams.get('v');
        if (videoId) {
          return 'https://www.youtube.com/embed/' + videoId;
        }
      } catch (error) {
        return value;
      }
    }

    if (value.indexOf('youtu.be/') !== -1) {
      var ytParts = value.split('youtu.be/');
      if (ytParts[1]) {
        return 'https://www.youtube.com/embed/' + ytParts[1].split(/[?#]/)[0];
      }
    }

    if (value.indexOf('vimeo.com/') !== -1 && value.indexOf('/video/') === -1) {
      var vmParts = value.split('vimeo.com/');
      if (vmParts[1]) {
        return 'https://player.vimeo.com/video/' + vmParts[1].split(/[?#]/)[0];
      }
    }

    return value;
  }

  function renderLightboxItem(button) {
    if (!button) {
      return;
    }
    var src = button.getAttribute('data-lightbox-src') || '';
    var text = button.getAttribute('data-lightbox-title') || '';
    var description = button.getAttribute('data-lightbox-desc') || '';
    var videoUrl = button.getAttribute('data-lightbox-video') || '';
    var hasVideo = !!videoUrl;
    var normalizedVideo = hasVideo ? normalizeVideoUrl(videoUrl) : '';

    if (hasVideo) {
      video.src = normalizedVideo;
      videoWrap.hidden = false;
      image.hidden = true;
      image.src = src || '';
      image.alt = text || 'Gallery video preview';
    } else {
      video.src = '';
      videoWrap.hidden = true;
      image.hidden = false;
      image.src = src || '';
      image.alt = text || 'Gallery image';
    }

    title.textContent = text || '';
    if (desc) {
      desc.textContent = description || '';
      desc.hidden = !description;
    }
  }

  function getButtonScope(button) {
    return button.closest('[data-gallery-grid], .tour-gallery, .gallery-masonry') || document;
  }

  function getNavigableButtons(scope) {
    var scopeButtons = Array.prototype.slice.call(scope.querySelectorAll('.tour-gallery-button'));
    return scopeButtons.filter(function (item) {
      if (!item || item.hidden) {
        return false;
      }
      var card = item.closest('[data-gallery-item]');
      if (card && card.hidden) {
        return false;
      }
      return true;
    });
  }

  function updateNavState() {
    var hasNav = activeButtons.length > 1;
    lightbox.classList.toggle('has-nav', hasNav);
    if (prev) {
      prev.hidden = !hasNav;
    }
    if (next) {
      next.hidden = !hasNav;
    }
    if (counter) {
      counter.hidden = activeButtons.length < 1;
      if (!counter.hidden) {
        counter.textContent = (activeIndex + 1) + ' / ' + activeButtons.length;
      }
    }
  }

  function openAtIndex(index) {
    if (!activeButtons.length) {
      return;
    }
    if (index < 0) {
      index = activeButtons.length - 1;
    }
    if (index >= activeButtons.length) {
      index = 0;
    }
    activeIndex = index;
    renderLightboxItem(activeButtons[activeIndex]);
    updateNavState();
  }

  function openLightboxFromButton(button) {
    if (!button) {
      return;
    }
    var scope = getButtonScope(button);
    activeButtons = getNavigableButtons(scope);
    activeIndex = activeButtons.indexOf(button);
    if (activeIndex < 0) {
      activeButtons = [button];
      activeIndex = 0;
    }
    openAtIndex(activeIndex);
    lightbox.classList.add('is-open');
  }

  function closeLightbox() {
    lightbox.classList.remove('is-open');
    video.src = '';
    videoWrap.hidden = true;
    image.hidden = false;
    activeButtons = [];
    activeIndex = -1;
  }

  buttons.forEach(function (button) {
    button.addEventListener('click', function () {
      var src = button.getAttribute('data-lightbox-src');
      if (src) {
        openLightboxFromButton(button);
      }
    });
  });

  close.addEventListener('click', closeLightbox);
  image.addEventListener('click', function () {
    if (!image.hidden && lightbox.classList.contains('is-open')) {
      closeLightbox();
    }
  });
  if (prev) {
    prev.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      openAtIndex(activeIndex - 1);
    });
  }
  if (next) {
    next.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      openAtIndex(activeIndex + 1);
    });
  }
  lightbox.addEventListener('click', function (event) {
    if (event.target === lightbox) {
      closeLightbox();
    }
  });
  document.addEventListener('keydown', function (event) {
    if (!lightbox.classList.contains('is-open')) {
      return;
    }
    if (event.key === 'Escape') {
      closeLightbox();
    } else if (event.key === 'ArrowRight') {
      openAtIndex(activeIndex + 1);
    } else if (event.key === 'ArrowLeft') {
      openAtIndex(activeIndex - 1);
    }
  });
})();

(function () {
  var filterGroups = document.querySelectorAll('[data-gallery-filters]');
  if (!filterGroups.length) {
    return;
  }

  filterGroups.forEach(function (group) {
    var section = group.closest('.jacana-gallery-grid-widget');
    var grid = section ? section.querySelector('[data-gallery-grid]') : null;
    var buttons = group.querySelectorAll('[data-gallery-filter]');
    var items = grid ? grid.querySelectorAll('[data-gallery-item]') : [];

    if (!grid || !buttons.length || !items.length) {
      return;
    }

    function setFilter(value) {
      buttons.forEach(function (button) {
        var isActive = button.getAttribute('data-gallery-filter') === value;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });

      items.forEach(function (item) {
        var category = item.getAttribute('data-gallery-category') || '';
        var show = value === 'all' || value === category;
        item.hidden = !show;
      });
    }

    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        setFilter(button.getAttribute('data-gallery-filter') || 'all');
      });
    });
  });
})();

(function () {
  var reveals = document.querySelectorAll('.jacana-reveal');
  if (!reveals.length) {
    return;
  }

  if (typeof window.IntersectionObserver === 'undefined') {
    reveals.forEach(function (item) {
      item.classList.add('is-visible');
    });
    return;
  }

  var observer = new window.IntersectionObserver(function (entries, obs) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        obs.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.18,
    rootMargin: '0px 0px -48px 0px'
  });

  reveals.forEach(function (item) {
    observer.observe(item);
  });
})();

(function () {
  var maps = document.querySelectorAll('.jacana-highlights-map');
  if (!maps.length) {
    return;
  }

  function openConciergeFallback() {
    var toggle = document.querySelector('[data-jacana-concierge-toggle], .jacana-concierge-toggle, .jacana-ai-concierge-toggle');
    if (toggle) {
      toggle.click();
      return true;
    }

    if (window.jacanaConcierge && window.jacanaConcierge.whatsapp) {
      var phone = String(window.jacanaConcierge.whatsapp).replace(/[^0-9]/g, '');
      if (phone) {
        window.open('https://wa.me/' + phone, '_blank', 'noopener,noreferrer');
        return true;
      }
    }

    return false;
  }

  // ── Destination alias map ─────────────────────────────────
  // Maps any destination param value (lowercase) → exact hotspot title (lowercase).
  // Covers old card titles, user-friendly names, and common alternatives.
  var DEST_ALIASES = {
    // Etosha
    'etosha waterhole':              'etosha national park',
    'etosha waterholes':             'etosha national park',
    'etosha':                        'etosha national park',
    'rhino in etosha':               'etosha national park',
    'rhino in etosha national park': 'etosha national park',
    'rhino tracking':                'etosha national park',
    'okaukuejo waterhole':           'etosha national park',
    'elephants at okaukuejo waterhole': 'etosha national park',
    // Sossusvlei
    'sossusvlei dunes':              'sossusvlei',
    'dunes':                         'sossusvlei',
    'namib dunes':                   'sossusvlei',
    // Walvis Bay / Sandwich Harbour
    'sandwich harbour':              'walvisbay',
    'walvis bay':                    'walvisbay',
    'walvis':                        'walvisbay',
    // Swakopmund / coast
    'flamingos':                     'swakopmund',
    'flamingo coast':                'swakopmund',
    'flamingos, swakopmund coast':   'swakopmund',
    'swakopmund coast':              'swakopmund',
    // Damaraland / Spitzkoppe
    'spitzkoppe rock arch':          'damaraland',
    'spitzkoppe':                    'damaraland',
    'spitzkoppe rock':               'damaraland',
  };

  function resolveDestParam(raw) {
    if (!raw) { return ''; }
    var normalized = raw.toLowerCase().trim().replace(/,\s*$/, ''); // strip trailing comma
    return DEST_ALIASES[normalized] || normalized;
  }

  function findHotspot(hotspots, resolved) {
    var list = Array.from(hotspots);
    // 1. Exact match
    var exact = list.find(function (h) {
      return h.dataset.title && h.dataset.title.toLowerCase() === resolved;
    });
    if (exact) { return exact; }
    // 2. Hotspot title starts with the resolved value
    var starts = list.find(function (h) {
      return h.dataset.title && h.dataset.title.toLowerCase().indexOf(resolved) === 0;
    });
    if (starts) { return starts; }
    // 3. Word-level overlap: any significant word in resolved appears in the hotspot title
    var words = resolved.split(/[\s,]+/).filter(function (w) { return w.length > 3; });
    return list.find(function (h) {
      if (!h.dataset.title) { return false; }
      var titleWords = h.dataset.title.toLowerCase().split(/\s+/);
      return words.some(function (w) { return titleWords.indexOf(w) !== -1; });
    }) || null;
  }

  maps.forEach(function (widget) {
    var hotspots = widget.querySelectorAll('.jacana-map-hotspot');
    var placesPills = widget.querySelectorAll('.jacana-map-place-pill');
    var title = widget.querySelector('[data-map-title]');
    var overlayTitle = widget.querySelector('[data-map-title-overlay]');
    var teaser = widget.querySelector('[data-map-teaser]');
    var description = widget.querySelector('[data-map-description]');
    var image = widget.querySelector('[data-map-image]');
    var videoWrap = widget.querySelector('[data-map-video-wrap]');
    var video = widget.querySelector('[data-map-video]');
    var cta = widget.querySelector('[data-map-chat-cta]');
    var chatButton = widget.querySelector('[data-map-chat]');

    if (!hotspots.length || (!title && !overlayTitle) || !teaser || !description || !image || !videoWrap || !video || !cta) {
      return;
    }

    function normalizeVideoUrl(url) {
      if (!url) {
        return '';
      }

      var value = String(url);

      if (value.indexOf('youtube.com/watch') !== -1) {
        try {
          var parsed = new URL(value);
          var videoId = parsed.searchParams.get('v');
          if (videoId) {
            return 'https://www.youtube.com/embed/' + videoId;
          }
        } catch (error) {
          return value;
        }
      }

      if (value.indexOf('youtu.be/') !== -1) {
        var parts = value.split('youtu.be/');
        if (parts[1]) {
          return 'https://www.youtube.com/embed/' + parts[1].split(/[?#]/)[0];
        }
      }

      return value;
    }

    function setActiveHotspot(button) {
      var targetIndex = button.dataset.index;

      hotspots.forEach(function (spot) {
        spot.classList.remove('is-active');
      });

      placesPills.forEach(function (pill) {
        if (pill.dataset.placeIndex === targetIndex) {
          pill.classList.add('is-active');
        } else {
          pill.classList.remove('is-active');
        }
      });

      button.classList.add('is-active');

      var placeName = button.dataset.title || '';
      if (title) {
        title.textContent = placeName;
      }
      if (overlayTitle) {
        overlayTitle.textContent = placeName;
      }

      teaser.textContent = button.dataset.teaser || '';
      description.textContent = button.dataset.description || '';

      cta.innerHTML = (button.dataset.ctaLabel || 'I want to know more about this place') + ' <span class="jacana-cta-arrow" aria-hidden="true">&rarr;</span>';

      if (chatButton) {
        chatButton.textContent = button.dataset.chatLabel || 'Ask the chatbot';
      }

      var hasVideo = !!button.dataset.video;
      var hasImage = !!button.dataset.image;

      if (hasImage) {
        image.src = button.dataset.image;
        image.alt = button.dataset.title || 'Destination highlight';
        image.classList.add('is-visible');
      } else {
        image.src = '';
        image.alt = '';
        image.classList.remove('is-visible');
      }

      if (hasVideo) {
        video.src = normalizeVideoUrl(button.dataset.video);
        videoWrap.hidden = false;
      } else {
        video.src = '';
        videoWrap.hidden = true;
      }
    }

    hotspots.forEach(function (spot) {
      spot.addEventListener('click', function () {
        setActiveHotspot(spot);
      });
    });

    placesPills.forEach(function (pill) {
      pill.addEventListener('click', function () {
        var idx = pill.dataset.placeIndex;
        var spot = Array.from(hotspots).find(function (h) { return h.dataset.index === idx; });
        if (spot) {
          setActiveHotspot(spot);
        }
      });
    });

    if (chatButton) {
      chatButton.addEventListener('click', function (event) {
        event.preventDefault();
        openConciergeFallback();
      });
    }

    // Default: first hotspot. Override with ?destination= URL param.
    var rawDestParam = new URLSearchParams(window.location.search).get('destination');
    if (rawDestParam) {
      var resolvedDest = resolveDestParam(rawDestParam);
      var destMatch    = findHotspot(hotspots, resolvedDest);
      if (destMatch) {
        setActiveHotspot(destMatch);
        setTimeout(function () {
          widget.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 350);
      } else {
        setActiveHotspot(hotspots[0]);
      }
    } else {
      setActiveHotspot(hotspots[0]);
    }
  });
})();

(function () {
  var faqBlocks = document.querySelectorAll('[data-jacana-faq-block], .jacana-faq-main');
  if (!faqBlocks.length) {
    return;
  }

  faqBlocks.forEach(function (block) {
    var scope = block.closest('.jacana-faq-knowledge-widget') || block.parentElement || block;
    var input = block.querySelector('.jacana-faq-search-input');
    var items = block.querySelectorAll('[data-faq-item]');
    var resultCount = block.querySelector('[data-faq-results-count]');
    var clearButton = block.querySelector('[data-faq-clear]');
    var emptyState = block.querySelector('[data-faq-empty]');
    var topicButtons = scope.querySelectorAll('[data-faq-topic]');

    if (!input || !items.length) {
      return;
    }

    function applyQuery(query) {
      var visibleCount = 0;

      items.forEach(function (item) {
        var text = item.dataset.faqText || '';
        var isVisible = !query || text.indexOf(query) !== -1;
        item.hidden = !isVisible;
        if (isVisible) {
          visibleCount += 1;
        }
      });

      if (resultCount) {
        resultCount.textContent = visibleCount + (visibleCount === 1 ? ' answer found' : ' answers found');
      }
      if (clearButton) {
        clearButton.hidden = !query;
      }
      if (emptyState) {
        emptyState.hidden = visibleCount !== 0;
      }
    }

    input.addEventListener('input', function () {
      var query = input.value.trim().toLowerCase();
      applyQuery(query);
    });

    if (clearButton) {
      clearButton.addEventListener('click', function () {
        input.value = '';
        applyQuery('');
        input.focus();
      });
    }

    items.forEach(function (item) {
      item.addEventListener('toggle', function () {
        if (!item.open) {
          return;
        }
        items.forEach(function (other) {
          if (other !== item) {
            other.open = false;
          }
        });
      });
    });

    topicButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        var topic = (button.getAttribute('data-faq-topic') || '').trim().toLowerCase();
        input.value = topic;
        applyQuery(topic);
        if (items.length) {
          for (var i = 0; i < items.length; i += 1) {
            if (!items[i].hidden) {
              items[i].open = true;
              break;
            }
          }
        }
        input.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    });

    applyQuery('');
  });
})();

(function () {
  var trackedForms = document.querySelectorAll('.wpcf7 form, form');
  if (!trackedForms.length) {
    return;
  }

  function fillHiddenField(form, name, value) {
    var field = form.querySelector('[name="' + name + '"]');
    if (!field) {
      return;
    }
    field.value = value;
  }

  trackedForms.forEach(function (form) {
    fillHiddenField(form, 'source_page', document.title || '');
    fillHiddenField(form, 'source_url', window.location.href || '');
  });
})();

(function () {
  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-jacana-open-chat]');
    if (!trigger) {
      return;
    }
    event.preventDefault();
    var source = trigger.getAttribute('data-jacana-open-chat') || 'website';
    var message = 'I need help with ' + source + '.';
    window.dispatchEvent(new CustomEvent('jacana:open-concierge', {
      detail: { message: message, skipPicker: true }
    }));
  });
})();

(function () {
  var fleetBlocks = document.querySelectorAll('[data-rental-fleet]');
  if (!fleetBlocks.length) {
    return;
  }

  fleetBlocks.forEach(function (block) {
    var filters = block.querySelectorAll('[data-rental-filter]');
    var items = block.querySelectorAll('[data-rental-item]');
    var details = block.querySelectorAll('[data-rental-detail]');

    if (!items.length || !details.length) {
      return;
    }

    var activeIndex = 0;
    var activeFilter = 'all';

    function isItemVisible(item) {
      return !item.hidden;
    }

    function showDetail(index) {
      activeIndex = index;

      items.forEach(function (item) {
        var isActive = parseInt(item.getAttribute('data-rental-index') || '-1', 10) === index;
        item.classList.toggle('is-active', isActive);
        item.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });

      details.forEach(function (panel) {
        var isActive = parseInt(panel.getAttribute('data-rental-index') || '-1', 10) === index;
        panel.classList.toggle('is-active', isActive);
      });
    }

    function firstVisibleIndex() {
      for (var i = 0; i < items.length; i += 1) {
        if (isItemVisible(items[i])) {
          return parseInt(items[i].getAttribute('data-rental-index') || '0', 10);
        }
      }
      return 0;
    }

    function applyFilter(value) {
      activeFilter = value;

      filters.forEach(function (filterBtn) {
        var isActive = (filterBtn.getAttribute('data-rental-filter') || 'all') === value;
        filterBtn.classList.toggle('is-active', isActive);
        filterBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });

      items.forEach(function (item) {
        var category = item.getAttribute('data-rental-category') || '';
        var show = value === 'all' || category === value;
        item.hidden = !show;
      });

      var activeItem = block.querySelector('[data-rental-item][data-rental-index="' + activeIndex + '"]');
      if (!activeItem || activeItem.hidden) {
        showDetail(firstVisibleIndex());
      }
    }

    items.forEach(function (item) {
      item.addEventListener('click', function () {
        if (item.hidden) {
          return;
        }
        var index = parseInt(item.getAttribute('data-rental-index') || '0', 10);
        showDetail(index);
      });
    });

    filters.forEach(function (filterBtn) {
      filterBtn.addEventListener('click', function () {
        applyFilter(filterBtn.getAttribute('data-rental-filter') || 'all');
      });
    });

    showDetail(parseInt(items[0].getAttribute('data-rental-index') || '0', 10));
    if (activeFilter !== 'all') {
      applyFilter(activeFilter);
    }
  });
})();

(function () {
  var showcases = document.querySelectorAll('[data-rental-showcase]');
  if (!showcases.length) {
    return;
  }

  showcases.forEach(function (showcase) {
    var track = showcase.querySelector('[data-rental-track]');
    var slides = showcase.querySelectorAll('[data-rental-slide]');
    var details = showcase.querySelectorAll('[data-rental-detail]');
    var prev = showcase.querySelector('[data-rental-prev]');
    var next = showcase.querySelector('[data-rental-next]');
    var dots = showcase.querySelectorAll('[data-rental-dot]');

    if (!track || !slides.length || !details.length) {
      return;
    }

    var current = 0;
    var timer = null;
    var delay = 6000;

    function setIndex(index) {
      if (index < 0) {
        index = slides.length - 1;
      }
      if (index >= slides.length) {
        index = 0;
      }

      current = index;
      track.style.transform = 'translate3d(' + (-100 * current) + '%,0,0)';

      slides.forEach(function (slide, idx) {
        slide.classList.toggle('is-active', idx === current);
      });

      details.forEach(function (card, idx) {
        card.classList.toggle('is-active', idx === current);
      });

      dots.forEach(function (dot, idx) {
        var isActive = idx === current;
        dot.classList.toggle('is-active', isActive);
        dot.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });
    }

    function nextSlide() {
      setIndex(current + 1);
    }

    function startAuto() {
      if (slides.length < 2) {
        return;
      }
      stopAuto();
      timer = window.setInterval(nextSlide, delay);
    }

    function stopAuto() {
      if (timer) {
        window.clearInterval(timer);
        timer = null;
      }
    }

    function resetAuto() {
      stopAuto();
      startAuto();
    }

    if (prev) {
      prev.addEventListener('click', function () {
        setIndex(current - 1);
        resetAuto();
      });
    }

    if (next) {
      next.addEventListener('click', function () {
        setIndex(current + 1);
        resetAuto();
      });
    }

    dots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        var idx = parseInt(dot.getAttribute('data-index') || '0', 10);
        setIndex(isNaN(idx) ? 0 : idx);
        resetAuto();
      });
    });

    showcase.addEventListener('mouseenter', stopAuto);
    showcase.addEventListener('mouseleave', startAuto);
    showcase.addEventListener('focusin', stopAuto);
    showcase.addEventListener('focusout', function (event) {
      if (!showcase.contains(event.relatedTarget)) {
        startAuto();
      }
    });

    setIndex(0);
    startAuto();
  });
})();

(function () {
  var studios = document.querySelectorAll('[data-booking-studio]');
  if (!studios.length) {
    return;
  }

  function parseChecklist(value) {
    if (!value) {
      return [];
    }

    try {
      var parsed = JSON.parse(value);
      if (Array.isArray(parsed)) {
        return parsed.filter(Boolean);
      }
    } catch (error) {
      return String(value).split('\n').map(function (item) {
        return item.trim();
      }).filter(Boolean);
    }

    return [];
  }

  studios.forEach(function (block) {
    var buttons = block.querySelectorAll('[data-booking-option]');
    var preview = block.querySelector('[data-booking-preview]');
    var formShell = block.querySelector('[data-booking-form-shell]');
    var fieldSelector = block.getAttribute('data-booking-field-selector') || '';

    if (!buttons.length || !preview) {
      return;
    }

    var tagNode = preview.querySelector('[data-booking-preview-tag]');
    var titleNode = preview.querySelector('[data-booking-preview-title]');
    var summaryNode = preview.querySelector('[data-booking-preview-summary]');
    var idealNode = preview.querySelector('[data-booking-preview-ideal]');
    var turnaroundNode = preview.querySelector('[data-booking-preview-turnaround]');
    var checklistNode = preview.querySelector('[data-booking-preview-checklist]');
    var servicePillNode = block.querySelector('[data-booking-form-service-pill]');
    var mappedField = null;

    function resolveMappedField() {
      if (!fieldSelector) {
        return null;
      }
      if (mappedField && document.contains(mappedField)) {
        return mappedField;
      }

      if (formShell) {
        mappedField = formShell.querySelector(fieldSelector);
      }
      if (!mappedField) {
        mappedField = block.querySelector(fieldSelector) || document.querySelector(fieldSelector);
      }
      return mappedField;
    }

    function setChecklist(items) {
      if (!checklistNode) {
        return;
      }
      checklistNode.innerHTML = '';
      items.forEach(function (item) {
        var li = document.createElement('li');
        li.textContent = item;
        checklistNode.appendChild(li);
      });
    }

    function applySelection(button) {
      if (!button) {
        return;
      }

      buttons.forEach(function (node) {
        var isActive = node === button;
        node.classList.toggle('is-active', isActive);
        node.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });

      var tag = button.getAttribute('data-booking-tag') || '';
      var title = button.getAttribute('data-booking-title') || '';
      var summary = button.getAttribute('data-booking-summary') || '';
      var ideal = button.getAttribute('data-booking-ideal') || '';
      var turnaround = button.getAttribute('data-booking-turnaround') || '';
      var checklist = parseChecklist(button.getAttribute('data-booking-checklist'));

      if (tagNode) {
        tagNode.textContent = tag;
        tagNode.hidden = !tag;
      }
      if (titleNode) {
        titleNode.textContent = title;
      }
      if (summaryNode) {
        summaryNode.textContent = summary;
      }
      if (idealNode) {
        idealNode.textContent = ideal;
      }
      if (turnaroundNode) {
        turnaroundNode.textContent = turnaround;
      }
      setChecklist(checklist);
      if (servicePillNode) {
        var servicePillLabel = servicePillNode.getAttribute('data-booking-form-service-label') || 'Selected request:';
        servicePillNode.textContent = servicePillLabel + ' ' + title;
      }
      preview.classList.remove('is-refreshing');
      void preview.offsetWidth;
      preview.classList.add('is-refreshing');

      var field = resolveMappedField();
      if (field) {
        field.value = title;
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }

    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        applySelection(button);
      });
    });

    applySelection(buttons[0]);
  });
})();
