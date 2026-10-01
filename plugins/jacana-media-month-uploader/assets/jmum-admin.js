(function () {
  'use strict';

  function bytesToSize(bytes) {
    if (!bytes || bytes < 1) {
      return '0 B';
    }
    var units = ['B', 'KB', 'MB', 'GB'];
    var idx = 0;
    var value = bytes;
    while (value >= 1024 && idx < units.length - 1) {
      value /= 1024;
      idx += 1;
    }
    return value.toFixed(idx === 0 ? 0 : 1) + ' ' + units[idx];
  }

  function getConfig() {
    if (typeof JmumConfig === 'undefined' || !JmumConfig) {
      return {
        messages: {
          filesSelected: 'Selected files:',
          overLimit: 'Selection exceeded the limit. Extra files were skipped.',
          duplicateInSelection: 'Duplicate files in this selection were collapsed.'
        }
      };
    }
    return JmumConfig;
  }

  document.addEventListener('DOMContentLoaded', function () {
    var config = getConfig();
    var dropZone = document.getElementById('jmum-drop-zone');
    var fileInput = document.getElementById('jmum_files');
    var clearButton = document.getElementById('jmum-clear-files');
    var warning = document.getElementById('jmum-warning');
    var fileMeta = document.getElementById('jmum-file-meta');
    var batchLimitInput = document.getElementById('jmum_batch_max_files');
    var effectiveLimitEl = document.getElementById('jmum-effective-limit');

    if (!dropZone || !fileInput || !fileMeta || !batchLimitInput) {
      return;
    }

    var selectedFiles = [];
    var canRebuildFileList = typeof DataTransfer !== 'undefined';

    function showWarning(text) {
      warning.hidden = false;
      warning.textContent = text;
    }

    function clearWarning() {
      warning.hidden = true;
      warning.textContent = '';
    }

    function getManualLimit() {
      var raw = parseInt(batchLimitInput.value || '0', 10);
      if (isNaN(raw) || raw < 1) {
        return 1;
      }
      return raw;
    }

    function getPhpLimit() {
      var phpLimit = parseInt(config.phpMaxFiles || '0', 10);
      return isNaN(phpLimit) || phpLimit < 1 ? 0 : phpLimit;
    }

    function getEffectiveLimit() {
      var manual = getManualLimit();
      var phpLimit = getPhpLimit();
      if (phpLimit > 0) {
        return Math.min(manual, phpLimit);
      }
      return manual;
    }

    function uniqueFiles(files) {
      var seen = new Set();
      var unique = [];
      files.forEach(function (file) {
        var key = [file.name, file.size, file.lastModified].join('__');
        if (!seen.has(key)) {
          seen.add(key);
          unique.push(file);
        }
      });
      return unique;
    }

    function isAllowedFile(file) {
      if (!file || typeof file.type !== 'string') return false;
      return file.type.indexOf('image/') === 0 ||
             file.type.indexOf('video/') === 0 ||
             file.type === 'application/pdf';
    }

    function syncInput() {
      if (!canRebuildFileList) {
        return;
      }
      var transfer = new DataTransfer();
      selectedFiles.forEach(function (file) {
        transfer.items.add(file);
      });
      fileInput.files = transfer.files;
    }

    function updateMeta() {
      var totalBytes = selectedFiles.reduce(function (sum, file) {
        return sum + (file.size || 0);
      }, 0);
      fileMeta.textContent = config.messages.filesSelected + ' ' + selectedFiles.length + ' (' + bytesToSize(totalBytes) + ')';
      if (effectiveLimitEl) {
        effectiveLimitEl.textContent = String(getEffectiveLimit());
      }
    }

    function applyLimit() {
      var limit = getEffectiveLimit();
      if (selectedFiles.length > limit) {
        selectedFiles = selectedFiles.slice(0, limit);
        showWarning(config.messages.overLimit);
      }
      syncInput();
      updateMeta();
    }

    function addFiles(incoming) {
      var allowed = incoming.filter(isAllowedFile);
      if (!canRebuildFileList) {
        if (incoming.length !== allowed.length) {
          showWarning('Some files were ignored (only images, PDFs, and videos are accepted).');
        }
        if (selectedFiles.length > 0) {
          showWarning('Your browser does not support cumulative drag-and-drop selection. Use one pick at a time.');
          return;
        }
        selectedFiles = uniqueFiles(allowed);
        applyLimit();
        return;
      }

      var candidateList = selectedFiles.concat(allowed);
      selectedFiles = uniqueFiles(candidateList);
      var duplicateCount = candidateList.length - selectedFiles.length;
      if (incoming.length !== allowed.length) {
        showWarning('Some files were ignored (only images, PDFs, and videos are accepted).');
      } else if (duplicateCount > 0) {
        showWarning(config.messages.duplicateInSelection);
      }
      applyLimit();
    }

    fileInput.addEventListener('change', function (event) {
      clearWarning();
      var files = Array.prototype.slice.call(event.target.files || []);
      addFiles(files);
    });

    batchLimitInput.addEventListener('change', function () {
      clearWarning();
      applyLimit();
    });

    if (clearButton) {
      clearButton.addEventListener('click', function () {
        selectedFiles = [];
        syncInput();
        clearWarning();
        updateMeta();
      });
    }

    ['dragenter', 'dragover'].forEach(function (name) {
      dropZone.addEventListener(name, function (event) {
        event.preventDefault();
        event.stopPropagation();
        dropZone.classList.add('is-dragging');
      });
    });

    ['dragleave', 'drop'].forEach(function (name) {
      dropZone.addEventListener(name, function (event) {
        event.preventDefault();
        event.stopPropagation();
        dropZone.classList.remove('is-dragging');
      });
    });

    dropZone.addEventListener('drop', function (event) {
      clearWarning();
      if (!canRebuildFileList) {
        showWarning('Drag-and-drop batching is limited in this browser. Use the file picker.');
        return;
      }
      var files = Array.prototype.slice.call((event.dataTransfer && event.dataTransfer.files) || []);
      addFiles(files);
    });

    dropZone.addEventListener('click', function () {
      fileInput.click();
    });

    dropZone.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        fileInput.click();
      }
    });

    updateMeta();
  });
})();
