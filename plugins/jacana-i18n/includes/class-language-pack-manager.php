<?php

if (!defined('ABSPATH')) {
  exit;
}

final class Jacana_I18n_Language_Pack_Manager {
  /** @var Jacana_I18n_Dictionary */
  private $dictionary;

  public function __construct(Jacana_I18n_Dictionary $dictionary) {
    $this->dictionary = $dictionary;
  }

  public function sync_with_master($lang, array $master_data) {
    $lang = sanitize_key((string) $lang);
    $pack = $this->dictionary->get_dictionary($lang);
    $master_items = isset($master_data['items']) && is_array($master_data['items']) ? $master_data['items'] : array();
    $master_generated_at = isset($master_data['generatedAt']) ? (string) $master_data['generatedAt'] : '';

    $current_keys = isset($pack['keys']) && is_array($pack['keys']) ? $pack['keys'] : array();
    $active_keys = array();
    $counts = array(
      'missing' => 0,
      'translated' => 0,
      'source_changed' => 0,
      'orphaned' => 0,
      'total' => 0,
    );

    foreach ($master_items as $item) {
      if (!is_array($item) || empty($item['key'])) {
        continue;
      }

      $key = (string) $item['key'];
      $source = isset($item['source']) ? (string) $item['source'] : '';
      $source_hash = $this->dictionary->hash_source($source);
      $active_keys[$key] = true;

      $entry = isset($current_keys[$key]) && is_array($current_keys[$key]) ? $current_keys[$key] : array();
      $entry = $this->dictionary->normalize_entry($entry, $source);
      $entry['source'] = $source;

      if ($entry['sourceHash'] !== $source_hash) {
        $entry['sourceHash'] = $source_hash;
        $entry['status'] = $entry['translation'] !== '' ? 'source_changed' : 'missing';
      } elseif ($entry['translation'] === '') {
        $entry['status'] = 'missing';
      } elseif ($entry['status'] === '' || $entry['status'] === 'legacy') {
        $entry['status'] = 'translated';
      }

      $current_keys[$key] = $entry;
      $counts[$entry['status']] = isset($counts[$entry['status']]) ? $counts[$entry['status']] + 1 : 1;
      $counts['total'] += 1;
    }

    foreach ($current_keys as $key => $entry) {
      if (isset($active_keys[$key])) {
        continue;
      }

      $entry = $this->dictionary->normalize_entry(is_array($entry) ? $entry : array(), isset($entry['source']) ? (string) $entry['source'] : '');
      $entry['status'] = 'orphaned';
      $current_keys[$key] = $entry;
      $counts['orphaned'] += 1;
    }

    $pack['keys'] = $current_keys;
    $pack['meta']['sourceExportGeneratedAt'] = $master_generated_at;
    $this->dictionary->save_pack($lang, $pack);

    return $counts;
  }

  public function get_items_for_translation($lang, array $master_data, $mode = 'missing', $limit = 50) {
    $lang = sanitize_key((string) $lang);
    $mode = sanitize_key((string) $mode);
    $limit = max(1, absint($limit));

    $this->sync_with_master($lang, $master_data);
    $pack = $this->dictionary->get_dictionary($lang);
    $keys = isset($pack['keys']) && is_array($pack['keys']) ? $pack['keys'] : array();
    $wanted = $mode === 'changed'
      ? array('source_changed')
      : array('missing', 'source_changed', 'ai_failed');

    $items = array();
    foreach ($keys as $key => $entry) {
      if (!is_array($entry)) {
        continue;
      }

      if (!in_array((string) ($entry['status'] ?? ''), $wanted, true)) {
        continue;
      }

      $source = isset($entry['source']) ? (string) $entry['source'] : '';
      if ($source === '') {
        continue;
      }

      $items[] = array(
        'key' => (string) $key,
        'source' => $source,
        'sourceHash' => (string) ($entry['sourceHash'] ?? ''),
        'status' => (string) ($entry['status'] ?? ''),
      );

      if (count($items) >= $limit) {
        break;
      }
    }

    return $items;
  }

  public function apply_translations($lang, array $translations, $status = 'ai_draft', array $master_items = array()) {
    $lang = sanitize_key((string) $lang);
    $pack = $this->dictionary->get_dictionary($lang);
    $keys = isset($pack['keys']) && is_array($pack['keys']) ? $pack['keys'] : array();
    $updated = 0;

    // Index master items for discovery if adding new strings
    $master_map = array();
    if (!empty($master_items)) {
      foreach ($master_items as $m_item) {
        if (is_array($m_item) && !empty($m_item['key'])) {
          $master_map[(string) $m_item['key']] = $m_item;
        }
      }
    }

    foreach ($translations as $key => $translation) {
      $key = (string) $key;
      $is_known = isset($keys[$key]) && is_array($keys[$key]);

      if (!$is_known) {
        // Try to discover the source text from master data
        if (isset($master_map[$key]['source'])) {
          $keys[$key] = array('source' => (string) $master_map[$key]['source']);
        } else {
          // Skip keys with no known source
          continue;
        }
      }

      $entry = $this->dictionary->normalize_entry($keys[$key], isset($keys[$key]['source']) ? (string) $keys[$key]['source'] : '');
      $entry['translation'] = is_string($translation) ? trim($translation) : '';
      $entry['status'] = $entry['translation'] !== '' ? $status : 'ai_failed';
      $entry['updatedAt'] = gmdate('c');
      $keys[$key] = $entry;
      $updated += 1;
    }

    if ($updated === 0) {
      return 0;
    }

    $pack['keys'] = $keys;
    return $this->dictionary->save_pack($lang, $pack) ? $updated : 0;
  }

  public function get_pack_summary($lang) {
    $pack = $this->dictionary->get_dictionary($lang);
    $summary = array(
      'missing' => 0,
      'translated' => 0,
      'ai_draft' => 0,
      'source_changed' => 0,
      'orphaned' => 0,
      'ai_failed' => 0,
      'total' => 0,
    );

    foreach ((array) ($pack['keys'] ?? array()) as $entry) {
      if (!is_array($entry)) {
        continue;
      }

      $status = (string) ($entry['status'] ?? 'missing');
      if (!isset($summary[$status])) {
        $summary[$status] = 0;
      }
      $summary[$status] += 1;
      $summary['total'] += 1;
    }

    return $summary;
  }

  public function get_review_items($lang, $status = 'ai_draft', $limit = 25) {
    $pack = $this->dictionary->get_dictionary($lang);
    $keys = isset($pack['keys']) && is_array($pack['keys']) ? $pack['keys'] : array();
    $limit = max(1, absint($limit));
    $status = sanitize_key((string) $status);
    $allowed_statuses = array('ai_draft', 'missing', 'source_changed', 'ai_failed', 'translated');
    $wanted = in_array($status, $allowed_statuses, true) ? $status : 'ai_draft';
    $items = array();

    foreach ($keys as $key => $entry) {
      if (!is_array($entry) || (string) ($entry['status'] ?? '') !== $wanted) {
        continue;
      }

      $items[$key] = array(
        'key' => (string) $key,
        'source' => (string) ($entry['source'] ?? ''),
        'translation' => (string) ($entry['translation'] ?? ''),
        'status' => (string) ($entry['status'] ?? ''),
        'updatedAt' => (string) ($entry['updatedAt'] ?? ''),
      );

      if (count($items) >= $limit) {
        break;
      }
    }

    return $items;
  }

  public function update_entries($lang, array $updates) {
    $lang = sanitize_key((string) $lang);
    $pack = $this->dictionary->get_dictionary($lang);
    $keys = isset($pack['keys']) && is_array($pack['keys']) ? $pack['keys'] : array();
    $allowed_statuses = array('translated', 'ai_draft', 'missing', 'source_changed', 'ai_failed');
    $updated = 0;

    foreach ($updates as $key => $payload) {
      if (!isset($keys[$key]) || !is_array($keys[$key]) || !is_array($payload)) {
        continue;
      }

      $entry = $this->dictionary->normalize_entry($keys[$key], isset($keys[$key]['source']) ? (string) $keys[$key]['source'] : '');
      $translation = trim((string) ($payload['translation'] ?? ''));
      $status = sanitize_key((string) ($payload['status'] ?? $entry['status']));

      if (!in_array($status, $allowed_statuses, true)) {
        $status = $translation !== '' ? 'translated' : 'missing';
      }

      if ($translation === '' && $status === 'translated') {
        $status = 'missing';
      }

      $entry['translation'] = $translation;
      $entry['status'] = $status;
      $entry['updatedAt'] = gmdate('c');
      $keys[$key] = $entry;
      $updated += 1;
    }

    $pack['keys'] = $keys;
    $this->dictionary->save_pack($lang, $pack);

    return $updated;
  }

  public function bulk_transition_status($lang, array $from_statuses, $to_status) {
    $lang = sanitize_key((string) $lang);
    $to_status = sanitize_key((string) $to_status);
    $allowed_statuses = array('translated', 'ai_draft', 'missing', 'source_changed', 'ai_failed', 'orphaned');
    $from_statuses = array_values(array_filter(array_map('sanitize_key', $from_statuses)));

    if (!in_array($to_status, $allowed_statuses, true) || empty($from_statuses)) {
      return 0;
    }

    $pack = $this->dictionary->get_dictionary($lang);
    $keys = isset($pack['keys']) && is_array($pack['keys']) ? $pack['keys'] : array();
    $updated = 0;

    foreach ($keys as $key => $entry) {
      if (!is_array($entry)) {
        continue;
      }

      $current_status = sanitize_key((string) ($entry['status'] ?? 'missing'));
      if (!in_array($current_status, $from_statuses, true)) {
        continue;
      }

      $entry = $this->dictionary->normalize_entry($entry, isset($entry['source']) ? (string) $entry['source'] : '');
      if ($to_status === 'translated' && trim((string) ($entry['translation'] ?? '')) === '') {
        continue;
      }

      $entry['status'] = $to_status;
      $entry['updatedAt'] = gmdate('c');
      $keys[$key] = $entry;
      $updated += 1;
    }

    $pack['keys'] = $keys;
    $this->dictionary->save_pack($lang, $pack);

    return $updated;
  }

  /**
   * Reset entries where translation === source (Elementor enum values that were
   * mistakenly extracted and "translated" to the same or phonetically equivalent
   * text, e.g. content_width "full" → "Voll"). Resets their status to 'missing'
   * so they can be re-evaluated. Returns the number of entries reset.
   */
  public function reset_same_as_source_entries($lang) {
    $lang = sanitize_key((string) $lang);
    $pack = $this->dictionary->get_dictionary($lang);
    $keys = isset($pack['keys']) && is_array($pack['keys']) ? $pack['keys'] : array();
    $reset = 0;

    foreach ($keys as $key => $entry) {
      if (!is_array($entry)) {
        continue;
      }

      $source = trim((string) ($entry['source'] ?? ''));
      $translation = trim((string) ($entry['translation'] ?? ''));

      if ($source === '' || $translation === '' || $source !== $translation) {
        continue;
      }

      $entry = $this->dictionary->normalize_entry($entry, $source);
      $entry['translation'] = '';
      $entry['status'] = 'missing';
      $entry['updatedAt'] = gmdate('c');
      $keys[$key] = $entry;
      $reset += 1;
    }

    if ($reset > 0) {
      $pack['keys'] = $keys;
      $this->dictionary->save_pack($lang, $pack);
    }

    return $reset;
  }

  public function remove_orphaned($lang) {
    $lang = sanitize_key((string) $lang);
    $pack = $this->dictionary->get_dictionary($lang);
    $keys = isset($pack['keys']) && is_array($pack['keys']) ? $pack['keys'] : array();
    $removed = 0;

    foreach ($keys as $key => $entry) {
      if (!is_array($entry)) {
        continue;
      }

      if ((string) ($entry['status'] ?? '') !== 'orphaned') {
        continue;
      }

      unset($keys[$key]);
      $removed += 1;
    }

    $pack['keys'] = $keys;
    $this->dictionary->save_pack($lang, $pack);

    return $removed;
  }
}
