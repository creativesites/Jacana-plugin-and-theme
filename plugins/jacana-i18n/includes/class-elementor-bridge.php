<?php

if (!defined('ABSPATH')) {
  exit;
}

final class Jacana_I18n_Elementor_Bridge {
  /** @var Jacana_I18n_Dictionary */
  private $dictionary;

  /** @var Jacana_I18n_Lang_Detector */
  private $lang_detector;

  /** @var Jacana_I18n_Elementor_String_Extractor */
  private $extractor;

  /** @var bool */
  private $hooks_registered = false;

  /**
   * Stores pre-translation strings keyed by widget ID so that
   * translate_widget_render_content can do its str_replace against original
   * (English) strings rather than the already-translated strings that
   * translate_widget_before_render leaves in widget settings.
   *
   * @var array<string,array<string,string>>
   */
  private $pre_translation_strings = array();

  /**
   * Captures the unmodified template ID passed to the
   * elementor/theme/get_location_templates/template_id filter before any
   * third-party plugin (e.g. connect-polylang-elementor) can replace it with
   * a non-default-language copy. Used by translate_location_template_id to
   * restore the original ID when jacana-i18n is in the default language.
   *
   * @var int
   */
  private $original_location_template_id = 0;

  /**
   * Captures the unmodified Elementor kit ID from option_elementor_active_kit
   * before connect-polylang-elementor (priority 10) can replace it.
   *
   * @var int
   */
  private $original_kit_id = 0;

  public function __construct(
    Jacana_I18n_Dictionary $dictionary,
    Jacana_I18n_Lang_Detector $lang_detector,
    Jacana_I18n_Elementor_String_Extractor $extractor
  ) {
    $this->dictionary = $dictionary;
    $this->lang_detector = $lang_detector;
    $this->extractor = $extractor;
  }

  public function register_hooks() {
    if ($this->hooks_registered) {
      return;
    }

    add_action('elementor/frontend/widget/before_render', array($this, 'translate_widget_before_render'), 20);
    add_filter('elementor/widget/render_content', array($this, 'translate_widget_render_content'), 20, 2);
    // Priority 5: capture original IDs before connect-polylang-elementor (priority 10)
    // can replace them with non-default-language copies.
    add_filter('elementor/theme/get_location_templates/template_id', array($this, 'capture_original_location_template_id'), 5, 2);
    add_filter('option_elementor_active_kit', array($this, 'capture_original_kit_id'), 5);
    add_filter('elementor/theme/get_location_templates/template_id', array($this, 'translate_location_template_id'), 30, 2);
    add_filter('option_elementor_active_kit', array($this, 'translate_active_kit_id'), 30);
    // Force all elements into the shortcode path when Elementor is building its
    // element cache. Without this, static-text widgets (Heading, Text Editor, etc.)
    // render their translated HTML directly into the cache rather than as a
    // re-renderable [elementor-element] shortcode. That language-specific HTML is
    // then served to all subsequent visitors regardless of the current language.
    add_filter('elementor/element/is_dynamic_content', array($this, 'force_shortcode_path_during_cache_build'), 10, 3);
    // One-time cleanup: delete any stale element-cache entries that were stored
    // with translated (non-English) HTML before this fix was in place.
    $this->maybe_clear_stale_element_cache();
    $this->hooks_registered = true;
  }

  public function translate_widget_before_render($widget) {
    if ($this->lang_detector->is_default_language() || !is_object($widget)) {
      return;
    }

    if (!method_exists($widget, 'get_name')) {
      return;
    }

    if ((string) $widget->get_name() === 'template') {
      $this->translate_template_widget_id($widget);
    }

    if (!method_exists($widget, 'get_settings') || !method_exists($widget, 'set_settings') || !method_exists($widget, 'get_id')) {
      return;
    }

    $settings = $widget->get_settings();

    if (!is_array($settings) || empty($settings)) {
      return;
    }

    // Cache original strings keyed by widget ID so translate_widget_render_content
    // can str_replace using the pre-translation (English) values even after we
    // have already updated the widget's settings below.
    $widget_id_key = (string) $widget->get_id();
    if ($widget_id_key !== '') {
      $this->pre_translation_strings[$widget_id_key] = $this->extractor->extract_widget_strings($settings);
    }

    $translated_settings = $this->translate_widget_settings($settings, $widget);

    if ($translated_settings !== $settings) {
      $widget->set_settings($translated_settings);
    }
  }

  public function translate_widget_render_content($content, $widget) {
    if ($this->lang_detector->is_default_language() || !is_string($content) || trim($content) === '' || !is_object($widget)) {
      return $content;
    }

    if (!method_exists($widget, 'get_settings') || !method_exists($widget, 'get_name') || !method_exists($widget, 'get_id')) {
      return $content;
    }

    $widget_id = (string) $widget->get_id();

    // Use the pre-translation strings saved by translate_widget_before_render so
    // that str_replace operates on original English values, not the German strings
    // that set_settings() already placed in the widget.
    if ($widget_id !== '' && isset($this->pre_translation_strings[$widget_id])) {
      $strings = $this->pre_translation_strings[$widget_id];
      unset($this->pre_translation_strings[$widget_id]);
    } else {
      $settings = $widget->get_settings();
      if (!is_array($settings)) {
        return $content;
      }
      $strings = $this->extractor->extract_widget_strings($settings);
    }

    if (empty($strings)) {
      return $content;
    }

    uasort($strings, function ($left, $right) {
      return strlen((string) $right) <=> strlen((string) $left);
    });

    $post_id = $this->resolve_document_id($widget);
    $widget_type = (string) $widget->get_name();

    foreach ($strings as $path => $value) {
      $key = $this->build_widget_key($post_id, $widget_id, $path);

      $translated = $this->dictionary->translate_string($value, array(
        'key' => $key,
        'post_id' => (int) $post_id,
        'widget_id' => $widget_id,
        'widget_type' => $widget_type,
        'path' => $path,
      ));

      if ($translated !== $value) {
        $content = str_replace($value, $translated, $content);
      }
    }

    return $content;
  }

  public function capture_original_location_template_id($template_id, $location = '') {
    $this->original_location_template_id = absint($template_id);
    return $template_id;
  }

  public function translate_location_template_id($template_id, $location = '') {
    $template_id = absint($template_id);

    if ($template_id <= 0) {
      return $template_id;
    }

    // When jacana-i18n is in the default language, restore the original template
    // ID that was captured before plugins like connect-polylang-elementor (which
    // runs at priority 10) could substitute a non-default-language copy.
    if ($this->lang_detector->is_default_language()) {
      return $this->original_location_template_id > 0 ? $this->original_location_template_id : $template_id;
    }

    return $this->translate_post_id_for_current_language($template_id);
  }

  public function capture_original_kit_id($kit_id) {
    $this->original_kit_id = absint($kit_id);
    return $kit_id;
  }

  public function translate_active_kit_id($kit_id) {
    $kit_id = absint($kit_id);

    if ($kit_id <= 0) {
      return $kit_id;
    }

    if ($this->lang_detector->is_default_language()) {
      return $this->original_kit_id > 0 ? $this->original_kit_id : $kit_id;
    }

    return $this->translate_post_id_for_current_language($kit_id);
  }

  public function force_shortcode_path_during_cache_build($is_dynamic, $raw_data, $element) {
    // Elementor's document.php adds the __return_true callback to
    // elementor/element/should_render_shortcode during its cache-building pass.
    // Returning true here forces static elements (which would normally return
    // false) to use the [elementor-element] shortcode path, so the stored cache
    // contains only re-renderable shortcodes — no language-specific HTML.
    if (has_filter('elementor/element/should_render_shortcode', '__return_true')) {
      return true;
    }
    return $is_dynamic;
  }

  private function maybe_clear_stale_element_cache() {
    // Flag incremented whenever the element-cache invalidation logic changes.
    // Bump the suffix (v2, v3, …) to trigger a fresh one-time deletion.
    $flag = 'jacana_i18n_element_cache_cleared_v1';
    if (get_option($flag)) {
      return;
    }
    // Run from any context so the flag is set regardless of how WP boots.
    delete_post_meta_by_key('_elementor_element_cache');
    update_option($flag, '1', false);
  }

  private function translate_widget_settings(array $settings, $widget) {
    $strings = $this->extractor->extract_widget_strings($settings);

    if (empty($strings)) {
      return $settings;
    }

    uasort($strings, function ($left, $right) {
      return strlen((string) $right) <=> strlen((string) $left);
    });

    $document_id = $this->resolve_document_id($widget);
    $widget_id = method_exists($widget, 'get_id') ? (string) $widget->get_id() : '';
    $widget_type = method_exists($widget, 'get_name') ? (string) $widget->get_name() : '';

    foreach ($strings as $path => $source) {
      $key = $this->build_widget_key($document_id, $widget_id, $path);
      $translated = $this->dictionary->translate_string($source, array(
        'key' => $key,
        'post_id' => (int) $document_id,
        'widget_id' => $widget_id,
        'widget_type' => $widget_type,
        'path' => $path,
      ));

      if ($translated === $source) {
        continue;
      }

      $this->replace_setting_value($settings, (string) $path, $translated);
    }

    return $settings;
  }

  private function translate_template_widget_id($widget) {
    if (!function_exists('pll_get_post') || !method_exists($widget, 'get_settings') || !method_exists($widget, 'set_settings')) {
      return;
    }

    $template_id = absint($widget->get_settings('template_id'));

    if ($template_id <= 0) {
      return;
    }

    $translated_id = $this->translate_post_id_for_current_language($template_id);

    if ($translated_id > 0 && $translated_id !== $template_id) {
      $widget->set_settings('template_id', $translated_id);
    }
  }

  private function translate_post_id_for_current_language($post_id) {
    if (!function_exists('pll_get_post')) {
      return absint($post_id);
    }

    $post_id = absint($post_id);

    if ($post_id <= 0) {
      return $post_id;
    }

    $lang = sanitize_key((string) $this->lang_detector->get_current_language());
    $translated_id = pll_get_post($post_id, $lang);

    return $translated_id ? absint($translated_id) : $post_id;
  }

  private function resolve_document_id($widget) {
    $document_id = 0;

    // 1. Widget's own document reference (Elementor Pro widgets expose this).
    if (is_object($widget) && method_exists($widget, 'get_document')) {
      $document = $widget->get_document();

      if (is_object($document)) {
        if (method_exists($document, 'get_main_id')) {
          $document_id = absint($document->get_main_id());
        } elseif (method_exists($document, 'get_id')) {
          $document_id = absint($document->get_id());
        }
      }
    }

    // 2. Elementor's current document stack — the most reliable source when a
    //    widget lives inside an elementor_library template rendered as a theme
    //    location (header/footer) or via Display Conditions. get_the_ID() in
    //    those contexts returns the queried page ID, not the template ID, which
    //    would cause every key lookup to miss.
    if ($document_id <= 0
      && class_exists('\Elementor\Plugin')
      && ! empty(\Elementor\Plugin::$instance->documents)
      && method_exists(\Elementor\Plugin::$instance->documents, 'get_current')
    ) {
      $current_doc = \Elementor\Plugin::$instance->documents->get_current();
      if (is_object($current_doc)) {
        if (method_exists($current_doc, 'get_main_id')) {
          $document_id = absint($current_doc->get_main_id());
        } elseif (method_exists($current_doc, 'get_id')) {
          $document_id = absint($current_doc->get_id());
        }
      }
    }

    // 3. Fall back to the currently-queried post.
    if ($document_id <= 0) {
      $document_id = absint(get_the_ID());
    }

    if ($document_id <= 0) {
      $document_id = absint(get_queried_object_id());
    }

    return $document_id;
  }

  private function build_widget_key($post_id, $widget_id, $path) {
    $post_id = absint($post_id);
    $widget_id = trim((string) $widget_id);
    $path = trim((string) $path);

    if ($post_id <= 0 || $widget_id === '' || $path === '') {
      return '';
    }

    return sprintf('post_%d.widget_%s.settings.%s', $post_id, $widget_id, $path);
  }

  private function replace_setting_value(array &$settings, $path, $value) {
    $segments = explode('.', (string) $path);
    $cursor =& $settings;
    $last_index = count($segments) - 1;

    foreach ($segments as $index => $segment) {
      $segment = (string) $segment;

      if ($index === $last_index) {
        if (is_array($cursor) && array_key_exists($segment, $cursor)) {
          $cursor[$segment] = $value;
        }
        return;
      }

      if (!is_array($cursor) || !array_key_exists($segment, $cursor) || !is_array($cursor[$segment])) {
        return;
      }

      $cursor =& $cursor[$segment];
    }
  }

}
