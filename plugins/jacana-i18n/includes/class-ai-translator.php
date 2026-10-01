<?php

if (!defined('ABSPATH')) {
  exit;
}

final class Jacana_I18n_AI_Translator {
  const OPTION_GLOSSARY = 'jacana_i18n_glossary';

  /** @var array<int,array<string,string>>|null */
  private $glossary_entries = null;

  /** @var array<string,string> */
  private $target_labels = array(
    'de' => 'German',
    'fr' => 'French',
    'it' => 'Italian',
    'es' => 'Spanish',
  );

  public function translate_batch(array $items, $target_language) {
    $items = array_values(array_filter($items, function ($item) {
      return is_array($item) && !empty($item['key']) && isset($item['source']) && trim((string) $item['source']) !== '';
    }));

    if (empty($items)) {
      return array();
    }

    $locked_items = array();
    $locks_by_key = array();

    foreach ($items as $item) {
      list($locked_source, $locks) = $this->apply_glossary_locks((string) $item['source']);
      $locked_items[] = array(
        'key' => (string) $item['key'],
        'text' => $locked_source,
      );
      $locks_by_key[(string) $item['key']] = $locks;
    }

    $prompt = $this->build_batch_prompt($locked_items, $target_language);
    $response = $this->request_json($prompt);

    if (is_wp_error($response)) {
      return $response;
    }

    $translations = isset($response['translations']) && is_array($response['translations'])
      ? $response['translations']
      : (is_array($response) ? $response : array());

    $output = array();

    foreach ($translations as $key => $translation) {
      if (!is_string($translation)) {
        continue;
      }

      $output[(string) $key] = $this->restore_glossary_locks($translation, isset($locks_by_key[$key]) ? $locks_by_key[$key] : array());
    }

    return $output;
  }

  public function get_glossary_text() {
    $custom = trim((string) get_option(self::OPTION_GLOSSARY, ''));
    if ($custom !== '') {
      return $custom;
    }

    $legacy = trim((string) get_option('jacana_ai_translator_glossary', ''));
    if ($legacy !== '') {
      return $legacy;
    }

    return implode(
      PHP_EOL,
      array(
        'Jacana Safaris & Tours',
        'Jacana Luxe',
        'Namibia',
        'Windhoek',
        'Etosha National Park',
        'Sossusvlei',
        'Swakopmund',
        'Damaraland',
        'Caprivi',
        'Tailor-Made',
      )
    );
  }

  public function save_glossary_text($text) {
    update_option(self::OPTION_GLOSSARY, (string) $text);
    $this->glossary_entries = null;
  }

  private function build_batch_prompt(array $items, $target_language) {
    $target_label = isset($this->target_labels[$target_language]) ? $this->target_labels[$target_language] : strtoupper((string) $target_language);
    $json = wp_json_encode(array('items' => $items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return "Translate the UI copy in the JSON payload into {$target_label} ({$target_language}) for the Jacana Luxe travel website.\n"
      . "Tone: premium, concise, calm, authentically Namibian, never salesy.\n"
      . "Rules:\n"
      . "- Preserve keys exactly.\n"
      . "- Preserve HTML tags, placeholders like %s or %d, shortcode syntax [like-this], URLs, email addresses, phone numbers, and lock tokens like __JATLOCK_1__.\n"
      . "- Keep brand and glossary tokens unchanged; they will be restored later.\n"
      . "- Return only strict JSON in this exact shape: {\"translations\":{\"key\":\"translated text\"}}.\n"
      . "- Do not add commentary.\n"
      . $json;
  }

  private function request_json($prompt) {
    $api_key = sanitize_text_field((string) get_option('jacana_gemini_api_key', ''));
    if ($api_key === '') {
      return new WP_Error('missing_api_key', __('Gemini API key is missing.', 'jacana-luxe'));
    }

    $payload = array(
      'contents' => array(
        array('parts' => array(array('text' => (string) $prompt)))
      ),
      'generationConfig' => array(
        'temperature' => 0.2,
        'responseMimeType' => 'application/json',
      ),
    );

    $last_body = '';

    foreach ($this->get_models_to_try() as $model) {
      $response = wp_remote_post(
        'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . urlencode($api_key),
        array(
          'headers' => array('Content-Type' => 'application/json'),
          'body' => wp_json_encode($payload),
          'timeout' => 60,
        )
      );

      if (is_wp_error($response)) {
        continue;
      }

      $status = (int) wp_remote_retrieve_response_code($response);
      $body = (string) wp_remote_retrieve_body($response);
      $last_body = $body;

      if ($status >= 400) {
        continue;
      }

      $data = json_decode($body, true);
      $text = (string) ($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
      $decoded = json_decode($text, true);

      if (is_array($decoded)) {
        return $decoded;
      }
    }

    return new WP_Error('ai_failed', __('AI translation failed.', 'jacana-luxe'), array('last_body' => $last_body));
  }

  private function get_models_to_try() {
    $configured = sanitize_text_field((string) get_option('jacana_gemini_model', 'gemini-2.5-flash'));
    $models = array_filter(array_unique(array(
      $configured ?: 'gemini-2.5-flash',
      'gemini-2.5-flash',
      'gemini-2.5-pro-preview-03-25',
    )));

    return array_values($models);
  }

  private function parse_glossary_entries() {
    if (is_array($this->glossary_entries)) {
      return $this->glossary_entries;
    }

    $raw = $this->get_glossary_text();
    $lines = preg_split('/\r\n|\r|\n/', $raw);
    $entries = array();

    foreach ((array) $lines as $line) {
      $line = trim((string) $line);
      if ($line === '' || strpos($line, '#') === 0) {
        continue;
      }

      $parts = explode('=>', $line, 2);
      $source = trim((string) $parts[0]);
      if ($source === '') {
        continue;
      }

      $replacement = isset($parts[1]) ? trim((string) $parts[1]) : $source;
      if ($replacement === '') {
        $replacement = $source;
      }

      $entries[] = array(
        'source' => $source,
        'replacement' => $replacement,
      );
    }

    usort($entries, function ($left, $right) {
      return strlen((string) $right['source']) <=> strlen((string) $left['source']);
    });

    $this->glossary_entries = $entries;
    return $entries;
  }

  private function apply_glossary_locks($text) {
    $working = (string) $text;
    $locks = array();
    $index = 0;

    foreach ($this->parse_glossary_entries() as $entry) {
      $source = (string) $entry['source'];
      if ($source === '' || strpos($working, $source) === false) {
        continue;
      }

      $index += 1;
      $token = '__JATLOCK_' . $index . '__';
      $working = str_replace($source, $token, $working);
      $locks[$token] = (string) $entry['replacement'];
    }

    return array($working, $locks);
  }

  private function restore_glossary_locks($text, array $locks) {
    return empty($locks) ? (string) $text : strtr((string) $text, $locks);
  }
}
