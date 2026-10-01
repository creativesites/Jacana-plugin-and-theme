<?php

if (!defined('ABSPATH')) {
  exit;
}

final class Jacana_I18n_Form_Bridge {
  const OPTION_FORM_MAP = 'jacana_i18n_cf7_form_map';

  /** @var Jacana_I18n_Lang_Detector */
  private $lang_detector;

  /** @var bool */
  private $hooks_registered = false;

  public function __construct(Jacana_I18n_Lang_Detector $lang_detector) {
    $this->lang_detector = $lang_detector;
  }

  public function register_hooks() {
    if ($this->hooks_registered) {
      return;
    }

    if (class_exists('WPCF7_ContactForm')) {
      add_action('wpcf7_contact_form', array($this, 'localize_contact_form'), 20);
    }

    $this->hooks_registered = true;
  }

  public function localize_contact_form($contact_form) {
    if ($this->lang_detector->is_default_language() || !($contact_form instanceof WPCF7_ContactForm)) {
      return;
    }

    $form_id = (int) $contact_form->id();
    if ($form_id <= 0) {
      return;
    }

    $map = $this->get_form_map();
    if (empty($map[$form_id])) {
      return;
    }

    $lang = $this->lang_detector->get_current_language();
    $translated_id = isset($map[$form_id][$lang]) ? absint($map[$form_id][$lang]) : 0;

    if ($translated_id <= 0 || $translated_id === $form_id) {
      return;
    }

    $translated_form = wpcf7_contact_form($translated_id);

    if (!($translated_form instanceof WPCF7_ContactForm)) {
      return;
    }

    $contact_form->set_properties($translated_form->get_properties());

    if (method_exists($contact_form, 'set_locale') && method_exists($translated_form, 'locale')) {
      $locale = (string) $translated_form->locale();
      if ($locale !== '') {
        $contact_form->set_locale($locale);
      }
    }
  }

  public function get_form_map_text() {
    return trim((string) get_option(self::OPTION_FORM_MAP, ''));
  }

  public function save_form_map_text($text) {
    update_option(self::OPTION_FORM_MAP, trim((string) $text));
  }

  public function get_form_map() {
    $lines = preg_split('/\r\n|\r|\n/', $this->get_form_map_text());
    $supported_languages = array_keys($this->lang_detector->get_supported_languages());
    $supported_languages = array_values(array_filter($supported_languages, function ($lang) {
      return $lang !== $this->lang_detector->get_default_language();
    }));
    $map = array();

    foreach ((array) $lines as $line) {
      $line = trim((string) $line);
      if ($line === '' || strpos($line, '#') === 0) {
        continue;
      }

      $parts = preg_split('/\s*=>\s*/', $line, 2);
      $source_id = isset($parts[0]) ? absint($parts[0]) : 0;
      if ($source_id <= 0 || empty($parts[1])) {
        continue;
      }

      $targets = array();
      foreach (preg_split('/\s*,\s*/', (string) $parts[1]) as $segment) {
        $segment_parts = preg_split('/\s*:\s*/', (string) $segment, 2);
        $lang = isset($segment_parts[0]) ? sanitize_key((string) $segment_parts[0]) : '';
        $target_id = isset($segment_parts[1]) ? absint($segment_parts[1]) : 0;

        if ($target_id <= 0 || !in_array($lang, $supported_languages, true)) {
          continue;
        }

        $targets[$lang] = $target_id;
      }

      if (!empty($targets)) {
        $map[$source_id] = $targets;
      }
    }

    return $map;
  }

  public function get_available_forms() {
    if (!post_type_exists('wpcf7_contact_form')) {
      return array();
    }

    $posts = get_posts(array(
      'post_type' => 'wpcf7_contact_form',
      'post_status' => 'any',
      'posts_per_page' => 200,
      'orderby' => 'title',
      'order' => 'ASC',
    ));

    $forms = array();

    foreach ((array) $posts as $post) {
      $forms[] = array(
        'id' => (int) $post->ID,
        'title' => (string) $post->post_title,
      );
    }

    return $forms;
  }
}
