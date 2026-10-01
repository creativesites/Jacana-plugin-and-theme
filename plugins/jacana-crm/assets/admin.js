(function () {
  function isAdminNotice(el) {
    if (!el || !el.classList) {
      return false;
    }

    return el.classList.contains('notice') ||
      el.classList.contains('updated') ||
      el.classList.contains('error') ||
      el.classList.contains('update-nag');
  }

  function relocateAdminNotices() {
    document.querySelectorAll('.jacana-admin-wrap').forEach(function (wrap) {
      var rail = wrap.querySelector('.jacana-admin-notices');

      if (!rail) {
        return;
      }

      Array.prototype.slice.call(wrap.children).forEach(function (child) {
        if (child === rail || !isAdminNotice(child)) {
          return;
        }

        rail.appendChild(child);
      });
    });
  }

  function activateSession(scope, id) {
    if (!scope || !id) {
      return;
    }

    scope.querySelectorAll('.jacana-session-item').forEach(function (button) {
      button.classList.toggle('is-active', button.getAttribute('data-session') === id);
    });

    scope.querySelectorAll('.jacana-chat-session').forEach(function (panel) {
      panel.classList.toggle('is-active', panel.getAttribute('data-session') === id);
    });
  }

  document.querySelectorAll('.jacana-chat-shell').forEach(function (shell) {
    shell.querySelectorAll('.jacana-session-item').forEach(function (button) {
      button.addEventListener('click', function () {
        activateSession(shell, button.getAttribute('data-session'));
      });
    });
  });

  function renderMap(el) {
    if (!el || !window.google || !window.google.maps) {
      return;
    }

    var type = el.getAttribute('data-map-type');
    var raw = el.getAttribute('data-map-data') || '[]';
    var items = [];
    try {
      items = JSON.parse(raw);
    } catch (error) {
      items = [];
    }

    if (!items.length) {
      return;
    }

    var center = { lat: items[0].lat, lng: items[0].lng };
    var map = new google.maps.Map(el, { zoom: 6, center: center });

    if (type === 'route' && items.length >= 2) {
      var directionsService = new google.maps.DirectionsService();
      var directionsRenderer = new google.maps.DirectionsRenderer({ map: map, suppressMarkers: false });
      directionsService.route({
        origin: { lat: items[0].lat, lng: items[0].lng },
        destination: { lat: items[items.length - 1].lat, lng: items[items.length - 1].lng },
        waypoints: items.slice(1, -1).map(function (point) {
          return { location: { lat: point.lat, lng: point.lng }, stopover: true };
        }),
        travelMode: 'DRIVING'
      }, function (result, status) {
        if (status === 'OK') {
          directionsRenderer.setDirections(result);
          return;
        }

        var line = new google.maps.Polyline({
          path: items.map(function (point) {
            return { lat: point.lat, lng: point.lng };
          }),
          geodesic: true,
          strokeColor: '#c89b53',
          strokeOpacity: 0.9,
          strokeWeight: 4
        });
        line.setMap(map);
        items.forEach(function (point) {
          new google.maps.Marker({
            position: { lat: point.lat, lng: point.lng },
            map: map,
            title: point.label || ''
          });
        });
      });
      return;
    }

    items.forEach(function (point) {
      new google.maps.Marker({
        position: { lat: point.lat, lng: point.lng },
        map: map,
        title: point.label || ''
      });
    });
  }

  function initMaps() {
    document.querySelectorAll('.jacana-chat-map').forEach(function (el) {
      renderMap(el);
    });
  }

  function initMediaUploader() {
    var mediaUploader;

    document.querySelectorAll('.jacana-media-upload-field').forEach(function (field) {
      var fieldId = field.getAttribute('data-field-id');
      var uploadBtn = field.querySelector('.jacana-media-upload-button');
      var removeBtn = field.querySelector('.jacana-media-remove-button');
      var input = field.querySelector('input[type="text"]');
      var preview = field.querySelector('.jacana-media-preview');

      if (!uploadBtn || !input || !preview) return;

      uploadBtn.addEventListener('click', function (e) {
        e.preventDefault();

        if (mediaUploader) {
          mediaUploader.open();
          return;
        }

        mediaUploader = wp.media({
          title: 'Select Chatbot Icon',
          button: {
            text: 'Use this image'
          },
          multiple: false
        });

        mediaUploader.on('select', function () {
          var attachment = mediaUploader.state().get('selection').first().toJSON();
          input.value = attachment.url;
          preview.innerHTML = '<img src="' + attachment.url + '" alt="" />';
          preview.classList.add('has-image');
          if (removeBtn) {
            removeBtn.style.display = 'inline-block';
          }
        });

        mediaUploader.open();
      });

      if (removeBtn) {
        removeBtn.addEventListener('click', function (e) {
          e.preventDefault();
          input.value = '';
          preview.innerHTML = '';
          preview.classList.remove('has-image');
          removeBtn.style.display = 'none';
        });
      }
    });
  }

  if (window.jacanaCrmAdmin && window.jacanaCrmAdmin.hasMaps) {
    if (window.google && window.google.maps) {
      initMaps();
    } else {
      window.addEventListener('load', initMaps);
    }
  }

  initMediaUploader();
  relocateAdminNotices();
})();
