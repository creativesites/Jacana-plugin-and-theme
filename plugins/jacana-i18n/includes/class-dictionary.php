<?php

if (!defined('ABSPATH')) {
  exit;
}

final class Jacana_I18n_Dictionary {
  /** @var string */
  private $languages_dir;

  /** @var Jacana_I18n_Lang_Detector */
  private $lang_detector;

  /** @var array<string,array<string,mixed>> */
  private $cache = array();

  public function __construct($languages_dir, Jacana_I18n_Lang_Detector $lang_detector) {
    $this->languages_dir = trailingslashit((string) $languages_dir);
    $this->lang_detector = $lang_detector;
  }

  public function translate_string($string, array $context = array()) {
    if (!is_string($string) || $string === '' || $this->lang_detector->is_default_language()) {
      return $string;
    }

    $lang = $this->lang_detector->get_current_language();
    $key = isset($context['key']) ? (string) $context['key'] : '';

    if ($key !== '') {
      $entry = $this->get_entry($lang, $key);
      if (is_array($entry) && !empty($entry['translation'])) {
        return (string) $entry['translation'];
      }
    }

    $dictionary = $this->get_dictionary($lang);
    if (isset($dictionary['strings'][$string]) && is_string($dictionary['strings'][$string])) {
      return (string) $dictionary['strings'][$string];
    }

    return $string;
  }

  public function get_dictionary_for_current_language() {
    return $this->get_dictionary($this->lang_detector->get_current_language());
  }

  public function get_dictionary($lang) {
    $lang = sanitize_key((string) $lang);

    if (isset($this->cache[$lang])) {
      return $this->cache[$lang];
    }

    $file = $this->get_language_file_path($lang);
    if (!file_exists($file)) {
      $this->cache[$lang] = $this->create_empty_pack($lang);
      return $this->cache[$lang];
    }

    $raw = file_get_contents($file);
    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    $this->cache[$lang] = $this->normalize_pack($lang, is_array($decoded) ? $decoded : array());

    return $this->cache[$lang];
  }

  public function get_entry($lang, $key) {
    $pack = $this->get_dictionary($lang);

    return isset($pack['keys'][$key]) && is_array($pack['keys'][$key]) ? $pack['keys'][$key] : null;
  }

  public function save_pack($lang, array $pack) {
    $lang = sanitize_key((string) $lang);
    $pack = $this->normalize_pack($lang, $pack);
    $pack = $this->rebuild_string_map($pack);
    $pack['meta']['updatedAt'] = gmdate('c');

    wp_mkdir_p($this->languages_dir);
    $file_path = $this->get_language_file_path($lang);
    $this->backup_existing_pack($lang, $file_path);
    $json = wp_json_encode($pack, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!is_string($json)) {
      return false;
    }

    $result = file_put_contents($file_path, $json . PHP_EOL);

    if ($result === false) {
      return false;
    }

    $this->cache[$lang] = $pack;
    return true;
  }

  public function get_language_file_path($lang) {
    return $this->languages_dir . sanitize_key((string) $lang) . '.json';
  }

  public function create_empty_pack($lang) {
    $lang = sanitize_key((string) $lang);
    $label = $this->get_language_label($lang);

    return array(
      'meta' => array(
        'language' => $lang,
        'label' => $label,
        'updatedAt' => '',
        'sourceExportGeneratedAt' => '',
      ),
      'keys' => array(),
      'strings' => array(),
    );
  }

  public function normalize_entry(array $entry, $source = '') {
    $source = (string) $source;
    $translation = isset($entry['translation']) ? (string) $entry['translation'] : '';
    $status = isset($entry['status']) ? sanitize_key((string) $entry['status']) : '';
    $source_hash = isset($entry['sourceHash']) ? (string) $entry['sourceHash'] : '';
    $updated_at = isset($entry['updatedAt']) ? (string) $entry['updatedAt'] : '';

    if ($source_hash === '' && $source !== '') {
      $source_hash = $this->hash_source($source);
    }

    if ($status === '') {
      $status = $translation !== '' ? 'translated' : 'missing';
    }

    return array(
      'source' => $source,
      'translation' => $translation,
      'status' => $status,
      'sourceHash' => $source_hash,
      'updatedAt' => $updated_at,
    );
  }

  public function hash_source($source) {
    return sha1((string) $source);
  }

  private function normalize_pack($lang, array $decoded) {
    $pack = $this->create_empty_pack($lang);

    if (isset($decoded['meta']) && is_array($decoded['meta'])) {
      $pack['meta'] = array_merge($pack['meta'], array(
        'language' => sanitize_key((string) ($decoded['meta']['language'] ?? $lang)),
        'label' => (string) ($decoded['meta']['label'] ?? $pack['meta']['label']),
        'updatedAt' => (string) ($decoded['meta']['updatedAt'] ?? ''),
        'sourceExportGeneratedAt' => (string) ($decoded['meta']['sourceExportGeneratedAt'] ?? ''),
      ));
    }

    if (isset($decoded['keys']) && is_array($decoded['keys'])) {
      foreach ($decoded['keys'] as $key => $value) {
        if (is_string($value)) {
          $pack['keys'][$key] = $this->normalize_entry(array(
            'source' => '',
            'translation' => $value,
            'status' => 'legacy',
            'updatedAt' => '',
            'sourceHash' => '',
          ));
          continue;
        }

        if (is_array($value)) {
          $source = isset($value['source']) ? (string) $value['source'] : '';
          $pack['keys'][$key] = $this->normalize_entry($value, $source);
        }
      }
    }

    if (isset($decoded['strings']) && is_array($decoded['strings'])) {
      foreach ($decoded['strings'] as $source => $translation) {
        if (is_string($translation)) {
          $pack['strings'][$source] = $translation;
        }
      }
    }

    return $this->rebuild_string_map($pack);
  }

  private function rebuild_string_map(array $pack) {
    $strings = isset($pack['strings']) && is_array($pack['strings']) ? $pack['strings'] : array();

    foreach ((array) ($pack['keys'] ?? array()) as $entry) {
      if (!is_array($entry)) {
        continue;
      }

      $source = trim((string) ($entry['source'] ?? ''));
      $translation = trim((string) ($entry['translation'] ?? ''));

      if ($source === '' || $translation === '' || isset($strings[$source])) {
        continue;
      }

      $strings[$source] = $translation;
    }

    $pack['strings'] = $strings;

    return $pack;
  }

  private function get_language_label($lang) {
    $supported = $this->lang_detector->get_supported_languages();
    if (isset($supported[$lang]['label'])) {
      return (string) $supported[$lang]['label'];
    }

    return strtoupper($lang);
  }

  private function backup_existing_pack($lang, $file_path) {
    if (!is_string($file_path) || !file_exists($file_path)) {
      return;
    }

    $backup_dir = trailingslashit($this->languages_dir . 'backups');
    wp_mkdir_p($backup_dir);
    $stamp = gmdate('Ymd-His');
    $backup_path = $backup_dir . sanitize_key((string) $lang) . '-' . $stamp . '.json';

    // Best-effort safety backup; failures should not block a save.
    @copy($file_path, $backup_path);
  }
}
