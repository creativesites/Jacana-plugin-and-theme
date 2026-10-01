<?php
/**
 * Plugin Name: Jacana I18n
 * Description: Runtime language context, Elementor translation bridge, and concierge language propagation for Jacana.
 * Version: 0.1.2
 * Author: Winston Zulu
 */

if (!defined('ABSPATH')) {
  exit;
}

define('JACANA_I18N_VERSION', '0.1.2');
define('JACANA_I18N_FILE', __FILE__);
define('JACANA_I18N_DIR', plugin_dir_path(__FILE__));
define('JACANA_I18N_URL', plugin_dir_url(__FILE__));

require_once JACANA_I18N_DIR . 'includes/class-dictionary.php';
require_once JACANA_I18N_DIR . 'includes/class-lang-detector.php';
require_once JACANA_I18N_DIR . 'includes/class-string-filter.php';
require_once JACANA_I18N_DIR . 'includes/class-ai-translator.php';
require_once JACANA_I18N_DIR . 'includes/class-form-bridge.php';
require_once JACANA_I18N_DIR . 'includes/class-elementor-string-extractor.php';
require_once JACANA_I18N_DIR . 'includes/class-elementor-bridge.php';
require_once JACANA_I18N_DIR . 'includes/class-gettext-bridge.php';
require_once JACANA_I18N_DIR . 'includes/class-language-pack-manager.php';
require_once JACANA_I18N_DIR . 'includes/class-master-string-exporter.php';
require_once JACANA_I18N_DIR . 'includes/class-static-string-catalog.php';

final class Jacana_I18n {
  /** @var Jacana_I18n|null */
  private static $instance = null;

  /** @var Jacana_I18n_Lang_Detector */
  private $lang_detector;

  /** @var Jacana_I18n_Dictionary */
  private $dictionary;

  /** @var Jacana_I18n_String_Filter */
  private $string_filter;

  /** @var Jacana_I18n_AI_Translator */
  private $ai_translator;

  /** @var Jacana_I18n_Form_Bridge */
  private $form_bridge;

  /** @var Jacana_I18n_Elementor_String_Extractor */
  private $string_extractor;

  /** @var Jacana_I18n_Elementor_Bridge */
  private $elementor_bridge;

  /** @var Jacana_I18n_Gettext_Bridge */
  private $gettext_bridge;

  /** @var Jacana_I18n_Language_Pack_Manager */
  private $language_pack_manager;

  /** @var Jacana_I18n_Master_String_Exporter */
  private $master_string_exporter;

  /** @var Jacana_I18n_Static_String_Catalog */
  private $string_catalog;

  public static function instance() {
    if (null === self::$instance) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  private function __construct() {
    $this->lang_detector = new Jacana_I18n_Lang_Detector();
    $this->dictionary = new Jacana_I18n_Dictionary(JACANA_I18N_DIR . 'languages/', $this->lang_detector);
    $this->string_filter = new Jacana_I18n_String_Filter($this->dictionary, $this->lang_detector);
    $this->ai_translator = new Jacana_I18n_AI_Translator();
    $this->form_bridge = new Jacana_I18n_Form_Bridge($this->lang_detector);
    $this->string_extractor = new Jacana_I18n_Elementor_String_Extractor();
    $this->elementor_bridge = new Jacana_I18n_Elementor_Bridge($this->dictionary, $this->lang_detector, $this->string_extractor);
    $this->gettext_bridge = new Jacana_I18n_Gettext_Bridge($this->dictionary, $this->lang_detector);
    $this->language_pack_manager = new Jacana_I18n_Language_Pack_Manager($this->dictionary);
    $this->string_catalog = new Jacana_I18n_Static_String_Catalog();
    $this->master_string_exporter = new Jacana_I18n_Master_String_Exporter(
      $this->string_extractor,
      $this->string_catalog,
      JACANA_I18N_DIR . 'data/master-strings.json'
    );

    add_action('init', array($this, 'bootstrap'), 1);
    add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 90);
    add_action('wp_head', array($this, 'render_seo_alternates'), 1);
    add_action('admin_menu', array($this, 'register_admin_page'));
    add_action('admin_post_jacana_i18n_export_strings', array($this, 'handle_export_strings'));
    add_action('admin_post_jacana_i18n_save_glossary', array($this, 'handle_save_glossary'));
    add_action('admin_post_jacana_i18n_save_form_map', array($this, 'handle_save_form_map'));
    add_action('admin_post_jacana_i18n_sync_language', array($this, 'handle_sync_language'));
    add_action('admin_post_jacana_i18n_sync_all_languages', array($this, 'handle_sync_all_languages'));
    add_action('admin_post_jacana_i18n_translate_language', array($this, 'handle_translate_language'));
    add_action('admin_post_jacana_i18n_translate_all_languages', array($this, 'handle_translate_all_languages'));
    add_action('admin_post_jacana_i18n_review_language', array($this, 'handle_review_language'));
    add_action('admin_post_jacana_i18n_language_maintenance', array($this, 'handle_language_maintenance'));
    add_action('admin_post_jacana_i18n_download_language', array($this, 'handle_download_language'));
    add_action('admin_post_jacana_i18n_import_language', array($this, 'handle_import_language'));
    add_action('admin_post_jacana_i18n_export_untranslated', array($this, 'handle_export_untranslated'));
    add_action('admin_post_jacana_i18n_import_translations', array($this, 'handle_import_translations'));
    add_filter('language_attributes', array($this, 'filter_language_attributes'));
    add_filter('body_class', array($this, 'filter_body_class'));
    add_filter('wp_nav_menu_items', array($this, 'inject_menu_language_switcher'), 20, 2);
    add_shortcode('jacana_language_switcher', array($this, 'render_switcher_shortcode'));

    // Translation Bridge Hooks
    add_filter('jacana_i18n_translate_string', array($this->string_filter, 'translate_string'), 10, 2);
    add_filter('nav_menu_item_title', array($this, 'filter_nav_menu_item_title'), 10, 2);
    add_filter('the_title', array($this, 'filter_post_title'), 10, 2);
    add_filter('the_content', array($this, 'filter_post_content'), 10);
    add_filter('the_excerpt', array($this, 'filter_post_excerpt'), 10);
    add_filter('get_post_metadata', array($this, 'filter_post_meta'), 10, 4);
  }

  public function bootstrap() {
    $this->lang_detector->register_rewrite_rules();
    $this->lang_detector->bootstrap();
    $this->string_filter->register_hooks();
    $this->elementor_bridge->register_hooks();
    $this->gettext_bridge->register_hooks();
    $this->form_bridge->register_hooks();
  }

  public function enqueue_assets() {
    if (is_admin()) {
      return;
    }

    $script_path = JACANA_I18N_DIR . 'assets/js/lang-switcher.js';
    $script_url = JACANA_I18N_URL . 'assets/js/lang-switcher.js';
    $style_path = JACANA_I18N_DIR . 'assets/css/lang-switcher.css';
    $style_url = JACANA_I18N_URL . 'assets/css/lang-switcher.css';

    wp_enqueue_style(
      'jacana-i18n-switcher',
      $style_url,
      array(),
      file_exists($style_path) ? (string) filemtime($style_path) : JACANA_I18N_VERSION
    );

    wp_enqueue_script(
      'jacana-i18n-switcher',
      $script_url,
      array(),
      file_exists($script_path) ? (string) filemtime($script_path) : JACANA_I18N_VERSION,
      true
    );

    $config = array(
      'currentLanguage' => $this->lang_detector->get_current_language(),
      'defaultLanguage' => $this->lang_detector->get_default_language(),
      'currentLocale' => $this->lang_detector->get_current_locale(),
      'cookieName' => Jacana_I18n_Lang_Detector::COOKIE_NAME,
      'cookiePath' => COOKIEPATH ? COOKIEPATH : '/',
      'supportedLanguages' => $this->lang_detector->get_supported_languages(),
      'currentUrl' => $this->lang_detector->get_current_url(),
      'localizedUrls' => $this->lang_detector->get_switcher_urls(),
      'labels' => $this->get_frontend_labels(),
      'strings' => $this->get_frontend_strings(),
    );

    wp_add_inline_script(
      'jacana-i18n-switcher',
      'window.jacanaI18n=' . wp_json_encode($config) . ';',
      'before'
    );

    $bridge = 'window.jacanaConcierge = window.jacanaConcierge || {};'
      . 'window.jacanaConcierge.lang = ' . wp_json_encode($this->lang_detector->get_current_language()) . ';'
      . 'window.jacanaConcierge.locale = ' . wp_json_encode($this->lang_detector->get_current_locale()) . ';'
      . 'window.jacanaAiExperience = window.jacanaAiExperience || {};'
      . 'window.jacanaAiExperience.lang = ' . wp_json_encode($this->lang_detector->get_current_language()) . ';'
      . 'window.jacanaAiExperience.locale = ' . wp_json_encode($this->lang_detector->get_current_locale()) . ';';

    if (wp_script_is('jacana-ai-concierge', 'enqueued')) {
      wp_add_inline_script('jacana-ai-concierge', $bridge, 'after');
    }

    if (wp_script_is('jacana-luxe-ai-experience', 'enqueued')) {
      wp_add_inline_script('jacana-luxe-ai-experience', $bridge, 'before');
    }
  }

  public function filter_language_attributes($output) {
    $locale = str_replace('_', '-', $this->lang_detector->get_current_locale());

    if (preg_match('/lang=(["\']).*?\1/i', $output)) {
      $output = preg_replace('/lang=(["\']).*?\1/i', 'lang="' . esc_attr($locale) . '"', $output);
    } else {
      $output .= ' lang="' . esc_attr($locale) . '"';
    }

    return trim($output);
  }

  public function filter_body_class($classes) {
    $classes[] = 'jacana-lang-' . sanitize_html_class($this->lang_detector->get_current_language());

    if ($this->lang_detector->is_default_language()) {
      $classes[] = 'jacana-lang-default';
    }

    return $classes;
  }

  public function render_seo_alternates() {
    if (is_admin() || is_feed() || is_trackback() || (defined('REST_REQUEST') && REST_REQUEST)) {
      return;
    }

    $canonical = $this->lang_detector->localize_url($this->lang_detector->get_current_url(), $this->lang_detector->get_current_language());
    $alternates = $this->lang_detector->get_alternate_urls();
    $render_canonical = !defined('WPSEO_VERSION') && !defined('RANK_MATH_VERSION');
    $render_canonical = (bool) apply_filters('jacana_i18n_render_canonical', $render_canonical, $canonical);

    if ($render_canonical) {
      echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    }

    foreach ($alternates as $code => $url) {
      $hreflang = $code === 'x-default'
        ? 'x-default'
        : strtolower(str_replace('_', '-', (string) ($this->lang_detector->get_supported_languages()[$code]['locale'] ?? $code)));
      echo '<link rel="alternate" hreflang="' . esc_attr($hreflang) . '" href="' . esc_url($url) . '">' . "\n";
    }
  }

  public function render_switcher_shortcode($atts) {
    $atts = shortcode_atts(
      array(
        'class' => '',
        'label' => $this->translate_catalog_label('catalog.ui.language', 'Language'),
        'variant' => 'inline',
      ),
      $atts,
      'jacana_language_switcher'
    );

    $extra_classes = array_filter(preg_split('/\s+/', (string) $atts['class']));
    $extra_classes = array_map('sanitize_html_class', is_array($extra_classes) ? $extra_classes : array());
    $classes = trim(implode(' ', array_merge(array('jacana-language-switcher'), $extra_classes)));
    $languages = $this->lang_detector->get_supported_languages();
    $current = $this->lang_detector->get_current_language();
    $urls = $this->lang_detector->get_switcher_urls();

    if ((string) $atts['variant'] === 'dropdown') {
      return $this->render_dropdown_switcher($classes, (string) $atts['label'], $languages, $current, $urls);
    }

    ob_start();
    ?>
    <nav class="<?php echo esc_attr($classes); ?>" data-jacana-lang-switcher aria-label="<?php echo esc_attr($atts['label']); ?>">
      <ul class="jacana-language-switcher__list">
        <?php foreach ($languages as $code => $data) : ?>
          <li class="jacana-language-switcher__item">
            <a
              class="jacana-language-switcher__link<?php echo $current === $code ? ' is-current' : ''; ?>"
              href="<?php echo esc_url(isset($urls[$code]) ? $urls[$code] : home_url('/')); ?>"
              hreflang="<?php echo esc_attr(strtolower(str_replace('_', '-', $data['locale']))); ?>"
              lang="<?php echo esc_attr(strtolower(str_replace('_', '-', $data['locale']))); ?>"
              data-jacana-lang-link="<?php echo esc_attr($code); ?>"
              title="<?php echo esc_attr((string) $data['label']); ?>"
              aria-label="<?php echo esc_attr((string) $data['label']); ?>"
            >
              <span class="jacana-language-switcher__flag" aria-hidden="true"><?php echo esc_html($this->get_language_flag($code)); ?></span>
              <span class="screen-reader-text"><?php echo esc_html((string) $data['label']); ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <?php

    return (string) ob_get_clean();
  }

  public function inject_menu_language_switcher($items, $args) {
    if (is_admin() || !isset($args->theme_location)) {
      return $items;
    }

    $location = (string) $args->theme_location;
    if (!in_array($location, array('footer'), true)) {
      return $items;
    }

    $languages = $this->lang_detector->get_supported_languages();
    if (count($languages) <= 1) {
      return $items;
    }

    $current = $this->lang_detector->get_current_language();
    $current_flag = $this->get_language_flag($current);
    $urls = $this->lang_detector->get_switcher_urls();
    $menu = array();

    foreach ($languages as $code => $data) {
      $menu[] = sprintf(
        '<li class="menu-item %1$s"><a href="%2$s" lang="%3$s" hreflang="%3$s" data-jacana-lang-link="%4$s" title="%5$s" aria-label="%5$s"><span class="jacana-language-menu__flag" aria-hidden="true">%6$s</span><span class="screen-reader-text">%5$s</span></a></li>',
        $code === $current ? 'current-lang-item' : '',
        esc_url(isset($urls[$code]) ? $urls[$code] : home_url('/')),
        esc_attr(strtolower(str_replace('_', '-', (string) $data['locale']))),
        esc_attr($code),
        esc_attr((string) $data['label']),
        esc_html($this->get_language_flag($code))
      );
    }

    $item = sprintf(
      '<li class="menu-item menu-item-has-children jacana-language-menu jacana-language-menu--%1$s"><a href="%2$s" class="jacana-language-menu__trigger" aria-haspopup="true" aria-expanded="false" aria-label="%4$s"><span class="jacana-language-menu__flag" aria-hidden="true">%3$s</span><span class="screen-reader-text">%4$s</span></a><ul class="sub-menu jacana-language-menu__list">%5$s</ul></li>',
      esc_attr($location),
      esc_url($this->lang_detector->get_current_url()),
      esc_html($current_flag),
      esc_attr($this->translate_catalog_label('catalog.ui.language', 'Language')),
      implode('', $menu)
    );

    return $items . $item;
  }

  private function render_dropdown_switcher($classes, $label, array $languages, $current, array $urls) {
    $current_label = isset($languages[$current]['label']) ? (string) $languages[$current]['label'] : strtoupper((string) $current);
    $current_flag = $this->get_language_flag($current);

    ob_start();
    ?>
    <details class="<?php echo esc_attr(trim($classes . ' jacana-language-switcher--dropdown')); ?>" data-jacana-lang-switcher>
      <summary class="jacana-language-switcher__summary" aria-label="<?php echo esc_attr($label); ?>">
        <span class="jacana-language-switcher__flag jacana-language-switcher__flag--current" aria-hidden="true"><?php echo esc_html($current_flag); ?></span>
        <span class="screen-reader-text"><?php echo esc_html($current_label); ?></span>
      </summary>
      <ul class="jacana-language-switcher__dropdown-list">
        <?php foreach ($languages as $code => $data) : ?>
          <li class="jacana-language-switcher__dropdown-item">
            <a
              class="jacana-language-switcher__dropdown-link<?php echo $current === $code ? ' is-current' : ''; ?>"
              href="<?php echo esc_url(isset($urls[$code]) ? $urls[$code] : home_url('/')); ?>"
              hreflang="<?php echo esc_attr(strtolower(str_replace('_', '-', $data['locale']))); ?>"
              lang="<?php echo esc_attr(strtolower(str_replace('_', '-', $data['locale']))); ?>"
              data-jacana-lang-link="<?php echo esc_attr($code); ?>"
              title="<?php echo esc_attr((string) $data['label']); ?>"
              aria-label="<?php echo esc_attr((string) $data['label']); ?>"
            >
              <span class="jacana-language-switcher__flag" aria-hidden="true"><?php echo esc_html($this->get_language_flag($code)); ?></span>
              <span class="screen-reader-text"><?php echo esc_html((string) $data['label']); ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </details>
    <?php

    return (string) ob_get_clean();
  }

  public function register_admin_page() {
    add_management_page(
      __('Jacana I18n', 'jacana-luxe'),
      __('Jacana I18n', 'jacana-luxe'),
      'manage_options',
      'jacana-i18n',
      array($this, 'render_admin_page')
    );
  }

  public function render_admin_page() {
    if (!current_user_can('manage_options')) {
      return;
    }

    $export_file = JACANA_I18N_DIR . 'data/master-strings.json';
    $export_data = $this->master_string_exporter->read_export();
    $item_count = isset($export_data['items']) && is_array($export_data['items']) ? count($export_data['items']) : 0;
    $generated_at = isset($export_data['generatedAt']) ? (string) $export_data['generatedAt'] : '';
    $languages = array_diff_key($this->lang_detector->get_supported_languages(), array($this->lang_detector->get_default_language() => true));
    $selected_language = isset($_GET['lang']) ? sanitize_key((string) $_GET['lang']) : 'de';
    if (!isset($languages[$selected_language])) {
      $selected_language = 'de';
    }
    $summary = $this->language_pack_manager->get_pack_summary($selected_language);
    $glossary_text = $this->ai_translator->get_glossary_text();
    $review_status = isset($_GET['review_status']) ? sanitize_key((string) $_GET['review_status']) : 'ai_draft';
    $review_items = $this->language_pack_manager->get_review_items($selected_language, $review_status, 25);
    $form_map_text = $this->form_bridge->get_form_map_text();
    $available_forms = $this->form_bridge->get_available_forms();
    $language_summaries = array();
    $gemini_key = trim((string) get_option('jacana_gemini_api_key', ''));
    $gemini_model = trim((string) get_option('jacana_gemini_model', 'gemini-3.1-pro-preview'));
    foreach ($languages as $code => $data) {
      $language_summaries[$code] = $this->language_pack_manager->get_pack_summary($code);
    }
    ?>
    <div class="wrap">
      <h1><?php echo esc_html__('Jacana I18n', 'jacana-luxe'); ?></h1>
      <p><?php echo esc_html__('Export stable Elementor translation keys, sync per-language packs with source hashes, and translate missing or changed strings with Gemini.', 'jacana-luxe'); ?></p>

      <?php if (isset($_GET['jacana_i18n_notice'])) : ?>
        <?php $notice = sanitize_key((string) $_GET['jacana_i18n_notice']); ?>
        <?php if ($notice === 'export_success') : ?>
          <div class="notice notice-success"><p><?php echo esc_html__('Master string export completed.', 'jacana-luxe'); ?></p></div>
        <?php elseif ($notice === 'export_error') : ?>
          <div class="notice notice-error"><p><?php echo esc_html__('Master string export failed.', 'jacana-luxe'); ?></p></div>
        <?php elseif ($notice === 'glossary_saved') : ?>
          <div class="notice notice-success"><p><?php echo esc_html__('Glossary and brand locks saved.', 'jacana-luxe'); ?></p></div>
        <?php elseif ($notice === 'sync_success') : ?>
          <div class="notice notice-success"><p><?php echo esc_html__('Language pack synced with the current master export.', 'jacana-luxe'); ?></p></div>
        <?php elseif ($notice === 'sync_all_success') : ?>
          <div class="notice notice-success"><p><?php echo esc_html__('All language packs synced with the current master export.', 'jacana-luxe'); ?></p></div>
        <?php elseif ($notice === 'translate_success') : ?>
          <div class="notice notice-success"><p><?php echo esc_html__('AI translation batch completed.', 'jacana-luxe'); ?></p></div>
        <?php elseif ($notice === 'translate_all_success') : ?>
          <div class="notice notice-success"><p><?php echo esc_html__('AI translation batch completed for all non-English languages.', 'jacana-luxe'); ?></p></div>
        <?php elseif ($notice === 'translate_empty') : ?>
          <div class="notice notice-info"><p><?php echo esc_html__('No strings currently need translation for that selection.', 'jacana-luxe'); ?></p></div>
        <?php elseif ($notice === 'translate_error') : ?>
          <div class="notice notice-error"><p><?php echo esc_html__('AI translation failed. Check the Gemini key/model and try again.', 'jacana-luxe'); ?></p></div>
        <?php elseif ($notice === 'review_saved') : ?>
          <div class="notice notice-success"><p><?php echo esc_html__('Draft review changes saved.', 'jacana-luxe'); ?></p></div>
        <?php elseif ($notice === 'form_map_saved') : ?>
          <div class="notice notice-success"><p><?php echo esc_html__('Contact Form 7 language map saved.', 'jacana-luxe'); ?></p></div>
        <?php elseif ($notice === 'maintenance_success') : ?>
          <div class="notice notice-success"><p><?php echo esc_html__('Language pack maintenance action completed.', 'jacana-luxe'); ?></p></div>
        <?php elseif ($notice === 'import_success') : ?>
          <div class="notice notice-success"><p><?php echo esc_html__('Language pack imported successfully.', 'jacana-luxe'); ?></p></div>
        <?php elseif ($notice === 'import_error') : ?>
          <div class="notice notice-error"><p><?php echo esc_html__('Language pack import failed. Check that the JSON is valid and matches the expected schema.', 'jacana-luxe'); ?></p></div>
        <?php elseif ($notice === 'export_untranslated_error') : ?>
          <div class="notice notice-error"><p><?php echo esc_html__('Untranslated export failed. Sync/export master strings first and try again.', 'jacana-luxe'); ?></p></div>
        <?php elseif ($notice === 'import_translations_success') : ?>
          <?php $imported_count = absint($_GET['imported'] ?? 0); ?>
          <div class="notice notice-success"><p><?php echo esc_html(sprintf(__('Imported %d translated entries.', 'jacana-luxe'), $imported_count)); ?></p></div>
        <?php elseif ($notice === 'import_translations_error') : ?>
          <div class="notice notice-error"><p><?php echo esc_html__('Translation JSON import failed. Use either {"translations":{"key":"text"}} or {"items":[{"key":"...","translation":"..."}]}.', 'jacana-luxe'); ?></p></div>
        <?php elseif ($notice === 'import_translations_empty') : ?>
          <div class="notice notice-warning"><p><?php echo esc_html__('Translation JSON was valid, but no matching keys were applied to the selected language pack.', 'jacana-luxe'); ?></p></div>
        <?php endif; ?>
      <?php endif; ?>

      <table class="widefat striped" style="max-width:960px;margin-top:20px;">
        <tbody>
          <tr>
            <th style="width:220px;"><?php echo esc_html__('Gemini API key', 'jacana-luxe'); ?></th>
            <td><?php echo esc_html($gemini_key !== '' ? __('Configured', 'jacana-luxe') : __('Missing', 'jacana-luxe')); ?></td>
          </tr>
          <tr>
            <th><?php echo esc_html__('Gemini model', 'jacana-luxe'); ?></th>
            <td><code><?php echo esc_html($gemini_model); ?></code></td>
          </tr>
          <tr>
            <th><?php echo esc_html__('Configured languages', 'jacana-luxe'); ?></th>
            <td><?php echo esc_html(implode(', ', wp_list_pluck($languages, 'label'))); ?></td>
          </tr>
          <tr>
            <th><?php echo esc_html__('CF7 forms discovered', 'jacana-luxe'); ?></th>
            <td><?php echo esc_html((string) count($available_forms)); ?></td>
          </tr>
        </tbody>
      </table>

      <table class="widefat striped" style="max-width:960px;margin-top:20px;">
        <tbody>
          <tr>
            <th style="width:220px;"><?php echo esc_html__('Current export file', 'jacana-luxe'); ?></th>
            <td><code><?php echo esc_html($export_file); ?></code></td>
          </tr>
          <tr>
            <th><?php echo esc_html__('Generated at', 'jacana-luxe'); ?></th>
            <td><?php echo $generated_at ? esc_html($generated_at) : esc_html__('Not generated yet', 'jacana-luxe'); ?></td>
          </tr>
          <tr>
            <th><?php echo esc_html__('Exported strings', 'jacana-luxe'); ?></th>
            <td><?php echo esc_html((string) $item_count); ?></td>
          </tr>
        </tbody>
      </table>

      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:24px;">
        <?php wp_nonce_field('jacana_i18n_export_strings'); ?>
        <input type="hidden" name="action" value="jacana_i18n_export_strings">
        <?php submit_button(__('Export Master Strings', 'jacana-luxe')); ?>
      </form>

      <hr style="margin:32px 0;">

      <h2><?php echo esc_html__('Language Automation', 'jacana-luxe'); ?></h2>
      <p><?php echo esc_html__('Choose a target language, sync its pack with the latest master export, then translate missing or changed strings in batches.', 'jacana-luxe'); ?></p>

      <table class="widefat striped" style="max-width:960px;margin-top:20px;">
        <tbody>
          <tr>
            <th style="width:220px;"><?php echo esc_html__('Selected language', 'jacana-luxe'); ?></th>
            <td><?php echo esc_html(isset($languages[$selected_language]['label']) ? $languages[$selected_language]['label'] : strtoupper($selected_language)); ?></td>
          </tr>
          <tr>
            <th><?php echo esc_html__('Pack summary', 'jacana-luxe'); ?></th>
            <td>
              <?php
              echo esc_html(
                sprintf(
                  'Total %1$d | Missing %2$d | AI draft %3$d | Translated %4$d | Changed %5$d | Failed %6$d | Orphaned %7$d',
                  (int) ($summary['total'] ?? 0),
                  (int) ($summary['missing'] ?? 0),
                  (int) ($summary['ai_draft'] ?? 0),
                  (int) ($summary['translated'] ?? 0),
                  (int) ($summary['source_changed'] ?? 0),
                  (int) ($summary['ai_failed'] ?? 0),
                  (int) ($summary['orphaned'] ?? 0)
                )
              );
              ?>
            </td>
          </tr>
        </tbody>
      </table>

      <table class="widefat striped" style="max-width:960px;margin-top:20px;">
        <thead>
          <tr>
            <th><?php echo esc_html__('Language', 'jacana-luxe'); ?></th>
            <th><?php echo esc_html__('Total', 'jacana-luxe'); ?></th>
            <th><?php echo esc_html__('Missing', 'jacana-luxe'); ?></th>
            <th><?php echo esc_html__('AI Draft', 'jacana-luxe'); ?></th>
            <th><?php echo esc_html__('Approved', 'jacana-luxe'); ?></th>
            <th><?php echo esc_html__('Changed', 'jacana-luxe'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($languages as $code => $data) : ?>
            <?php $pack_summary = $language_summaries[$code] ?? array(); ?>
            <tr>
              <td><a href="<?php echo esc_url(add_query_arg(array('page' => 'jacana-i18n', 'lang' => $code), admin_url('tools.php'))); ?>"><?php echo esc_html($data['label']); ?></a></td>
              <td><?php echo esc_html((string) ($pack_summary['total'] ?? 0)); ?></td>
              <td><?php echo esc_html((string) ($pack_summary['missing'] ?? 0)); ?></td>
              <td><?php echo esc_html((string) ($pack_summary['ai_draft'] ?? 0)); ?></td>
              <td><?php echo esc_html((string) ($pack_summary['translated'] ?? 0)); ?></td>
              <td><?php echo esc_html((string) ($pack_summary['source_changed'] ?? 0)); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:960px;margin-top:20px;background:#fff;border:1px solid #dcdcde;padding:20px;">
        <?php wp_nonce_field('jacana_i18n_sync_language'); ?>
        <input type="hidden" name="action" value="jacana_i18n_sync_language">
        <label for="jacana_i18n_sync_language_select" style="display:block;margin-bottom:12px;font-weight:600;"><?php echo esc_html__('Language Pack', 'jacana-luxe'); ?></label>
        <select id="jacana_i18n_sync_language_select" name="language">
          <?php foreach ($languages as $code => $data) : ?>
            <option value="<?php echo esc_attr($code); ?>"<?php selected($selected_language, $code); ?>><?php echo esc_html($data['label']); ?></option>
          <?php endforeach; ?>
        </select>
        <?php submit_button(__('Sync Language Pack', 'jacana-luxe'), 'secondary', 'submit', false, array('style' => 'margin-left:12px;')); ?>
      </form>

      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:960px;margin-top:20px;background:#fff;border:1px solid #dcdcde;padding:20px;">
        <?php wp_nonce_field('jacana_i18n_sync_all_languages'); ?>
        <input type="hidden" name="action" value="jacana_i18n_sync_all_languages">
        <p><?php echo esc_html__('Sync every non-English language pack in one pass so French, Italian, and Spanish stay aligned with German and the current export.', 'jacana-luxe'); ?></p>
        <?php submit_button(__('Sync All Language Packs', 'jacana-luxe'), 'secondary'); ?>
      </form>

      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:960px;margin-top:20px;background:#fff;border:1px solid #dcdcde;padding:20px;">
        <?php wp_nonce_field('jacana_i18n_translate_language'); ?>
        <input type="hidden" name="action" value="jacana_i18n_translate_language">
        <p>
          <label for="jacana_i18n_translate_language_select" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Language', 'jacana-luxe'); ?></label>
          <select id="jacana_i18n_translate_language_select" name="language">
            <?php foreach ($languages as $code => $data) : ?>
              <option value="<?php echo esc_attr($code); ?>"<?php selected($selected_language, $code); ?>><?php echo esc_html($data['label']); ?></option>
            <?php endforeach; ?>
          </select>
        </p>
        <p>
          <label for="jacana_i18n_translate_mode" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Mode', 'jacana-luxe'); ?></label>
          <select id="jacana_i18n_translate_mode" name="mode">
            <option value="missing"><?php echo esc_html__('Translate Missing + Changed', 'jacana-luxe'); ?></option>
            <option value="changed"><?php echo esc_html__('Refresh Changed Only', 'jacana-luxe'); ?></option>
          </select>
        </p>
        <p>
          <label for="jacana_i18n_translate_limit" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Batch Size', 'jacana-luxe'); ?></label>
          <input id="jacana_i18n_translate_limit" name="limit" type="number" min="1" max="250" value="50">
        </p>
        <?php submit_button(__('Run AI Translation Batch', 'jacana-luxe')); ?>
      </form>

      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:960px;margin-top:20px;background:#fff;border:1px solid #dcdcde;padding:20px;">
        <?php wp_nonce_field('jacana_i18n_translate_all_languages'); ?>
        <input type="hidden" name="action" value="jacana_i18n_translate_all_languages">
        <p>
          <label for="jacana_i18n_translate_all_mode" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Mode', 'jacana-luxe'); ?></label>
          <select id="jacana_i18n_translate_all_mode" name="mode">
            <option value="missing"><?php echo esc_html__('Translate Missing + Changed', 'jacana-luxe'); ?></option>
            <option value="changed"><?php echo esc_html__('Refresh Changed Only', 'jacana-luxe'); ?></option>
          </select>
        </p>
        <p>
          <label for="jacana_i18n_translate_all_limit" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Batch Size Per Language', 'jacana-luxe'); ?></label>
          <input id="jacana_i18n_translate_all_limit" name="limit" type="number" min="1" max="250" value="25">
        </p>
        <?php submit_button(__('Run AI Batch For All Languages', 'jacana-luxe'), 'secondary'); ?>
      </form>

      <hr style="margin:32px 0;">

      <h2><?php echo esc_html__('Review Drafts', 'jacana-luxe'); ?></h2>
      <p><?php echo esc_html__('Review AI-generated or unresolved translations before promoting them to approved site copy.', 'jacana-luxe'); ?></p>

      <form method="get" action="<?php echo esc_url(admin_url('tools.php')); ?>" style="max-width:960px;margin-top:20px;background:#fff;border:1px solid #dcdcde;padding:20px;">
        <input type="hidden" name="page" value="jacana-i18n">
        <p>
          <label for="jacana_i18n_review_language" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Language', 'jacana-luxe'); ?></label>
          <select id="jacana_i18n_review_language" name="lang">
            <?php foreach ($languages as $code => $data) : ?>
              <option value="<?php echo esc_attr($code); ?>"<?php selected($selected_language, $code); ?>><?php echo esc_html($data['label']); ?></option>
            <?php endforeach; ?>
          </select>
        </p>
        <p>
          <label for="jacana_i18n_review_status" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Queue', 'jacana-luxe'); ?></label>
          <select id="jacana_i18n_review_status" name="review_status">
            <option value="ai_draft"<?php selected($review_status, 'ai_draft'); ?>><?php echo esc_html__('AI Drafts', 'jacana-luxe'); ?></option>
            <option value="source_changed"<?php selected($review_status, 'source_changed'); ?>><?php echo esc_html__('Changed Source', 'jacana-luxe'); ?></option>
            <option value="missing"<?php selected($review_status, 'missing'); ?>><?php echo esc_html__('Missing', 'jacana-luxe'); ?></option>
            <option value="ai_failed"<?php selected($review_status, 'ai_failed'); ?>><?php echo esc_html__('Failed', 'jacana-luxe'); ?></option>
            <option value="translated"<?php selected($review_status, 'translated'); ?>><?php echo esc_html__('Approved', 'jacana-luxe'); ?></option>
          </select>
        </p>
        <?php submit_button(__('Load Review Queue', 'jacana-luxe'), 'secondary'); ?>
      </form>

      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:1280px;margin-top:20px;background:#fff;border:1px solid #dcdcde;padding:20px;">
        <?php wp_nonce_field('jacana_i18n_review_language'); ?>
        <input type="hidden" name="action" value="jacana_i18n_review_language">
        <input type="hidden" name="language" value="<?php echo esc_attr($selected_language); ?>">
        <input type="hidden" name="review_status" value="<?php echo esc_attr($review_status); ?>">
        <?php if (!empty($review_items)) : ?>
          <table class="widefat striped">
            <thead>
              <tr>
                <th style="width:40%;"><?php echo esc_html__('Source', 'jacana-luxe'); ?></th>
                <th style="width:40%;"><?php echo esc_html__('Translation', 'jacana-luxe'); ?></th>
                <th style="width:20%;"><?php echo esc_html__('Status', 'jacana-luxe'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($review_items as $item) : ?>
                <tr>
                  <td>
                    <code style="display:block;margin-bottom:8px;"><?php echo esc_html($item['key']); ?></code>
                    <div><?php echo esc_html($item['source']); ?></div>
                  </td>
                  <td>
                    <textarea name="translations[<?php echo esc_attr($item['key']); ?>]" rows="4" class="large-text"><?php echo esc_textarea($item['translation']); ?></textarea>
                  </td>
                  <td>
                    <select name="statuses[<?php echo esc_attr($item['key']); ?>]">
                      <option value="translated"<?php selected($item['status'], 'translated'); ?>><?php echo esc_html__('Approved', 'jacana-luxe'); ?></option>
                      <option value="ai_draft"<?php selected($item['status'], 'ai_draft'); ?>><?php echo esc_html__('AI Draft', 'jacana-luxe'); ?></option>
                      <option value="source_changed"<?php selected($item['status'], 'source_changed'); ?>><?php echo esc_html__('Changed Source', 'jacana-luxe'); ?></option>
                      <option value="missing"<?php selected($item['status'], 'missing'); ?>><?php echo esc_html__('Missing', 'jacana-luxe'); ?></option>
                      <option value="ai_failed"<?php selected($item['status'], 'ai_failed'); ?>><?php echo esc_html__('Failed', 'jacana-luxe'); ?></option>
                    </select>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php submit_button(__('Save Review Decisions', 'jacana-luxe')); ?>
        <?php else : ?>
          <p><?php echo esc_html__('No entries in that review queue right now.', 'jacana-luxe'); ?></p>
        <?php endif; ?>
      </form>

      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:960px;margin-top:20px;background:#fff;border:1px solid #dcdcde;padding:20px;">
        <?php wp_nonce_field('jacana_i18n_language_maintenance'); ?>
        <input type="hidden" name="action" value="jacana_i18n_language_maintenance">
        <p>
          <label for="jacana_i18n_maintenance_language" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Language', 'jacana-luxe'); ?></label>
          <select id="jacana_i18n_maintenance_language" name="language">
            <?php foreach ($languages as $code => $data) : ?>
              <option value="<?php echo esc_attr($code); ?>"<?php selected($selected_language, $code); ?>><?php echo esc_html($data['label']); ?></option>
            <?php endforeach; ?>
          </select>
        </p>
        <p>
          <label for="jacana_i18n_maintenance_kind" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Maintenance Action', 'jacana-luxe'); ?></label>
          <select id="jacana_i18n_maintenance_kind" name="kind">
            <option value="approve_ai_drafts"><?php echo esc_html__('Approve All AI Drafts', 'jacana-luxe'); ?></option>
            <option value="reset_ai_drafts"><?php echo esc_html__('Return AI Drafts To Missing', 'jacana-luxe'); ?></option>
            <option value="prune_orphaned"><?php echo esc_html__('Delete Orphaned Entries', 'jacana-luxe'); ?></option>
            <option value="reset_enum_corruptions"><?php echo esc_html__('Reset Corrupted Enum Translations (translation = source)', 'jacana-luxe'); ?></option>
          </select>
        </p>
        <?php submit_button(__('Run Maintenance Action', 'jacana-luxe'), 'secondary'); ?>
      </form>

      <hr style="margin:32px 0;">

      <h2><?php echo esc_html__('Import / Export Packs', 'jacana-luxe'); ?></h2>
      <p><?php echo esc_html__('Download any language pack as JSON, or paste a revised JSON pack back into the plugin to overwrite it.', 'jacana-luxe'); ?></p>

      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:960px;background:#fff;border:1px solid #dcdcde;padding:20px;">
        <?php wp_nonce_field('jacana_i18n_download_language'); ?>
        <input type="hidden" name="action" value="jacana_i18n_download_language">
        <p>
          <label for="jacana_i18n_download_language" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Language', 'jacana-luxe'); ?></label>
          <select id="jacana_i18n_download_language" name="language">
            <?php foreach ($languages as $code => $data) : ?>
              <option value="<?php echo esc_attr($code); ?>"<?php selected($selected_language, $code); ?>><?php echo esc_html($data['label']); ?></option>
            <?php endforeach; ?>
          </select>
        </p>
        <?php submit_button(__('Download Language Pack', 'jacana-luxe'), 'secondary'); ?>
      </form>

      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:960px;margin-top:20px;background:#fff;border:1px solid #dcdcde;padding:20px;">
        <?php wp_nonce_field('jacana_i18n_import_language'); ?>
        <input type="hidden" name="action" value="jacana_i18n_import_language">
        <p>
          <label for="jacana_i18n_import_language" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Language', 'jacana-luxe'); ?></label>
          <select id="jacana_i18n_import_language" name="language">
            <?php foreach ($languages as $code => $data) : ?>
              <option value="<?php echo esc_attr($code); ?>"<?php selected($selected_language, $code); ?>><?php echo esc_html($data['label']); ?></option>
            <?php endforeach; ?>
          </select>
        </p>
        <p>
          <label for="jacana_i18n_import_json" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Pack JSON', 'jacana-luxe'); ?></label>
          <textarea id="jacana_i18n_import_json" name="pack_json" rows="14" class="large-text code"></textarea>
        </p>
        <?php submit_button(__('Import Language Pack', 'jacana-luxe')); ?>
      </form>

      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:960px;margin-top:20px;background:#fff;border:1px solid #dcdcde;padding:20px;">
        <?php wp_nonce_field('jacana_i18n_export_untranslated'); ?>
        <input type="hidden" name="action" value="jacana_i18n_export_untranslated">
        <p>
          <label for="jacana_i18n_export_untranslated_language" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Language', 'jacana-luxe'); ?></label>
          <select id="jacana_i18n_export_untranslated_language" name="language">
            <?php foreach ($languages as $code => $data) : ?>
              <option value="<?php echo esc_attr($code); ?>"<?php selected($selected_language, $code); ?>><?php echo esc_html($data['label']); ?></option>
            <?php endforeach; ?>
          </select>
        </p>
        <p>
          <label for="jacana_i18n_export_untranslated_mode" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Include statuses', 'jacana-luxe'); ?></label>
          <select id="jacana_i18n_export_untranslated_mode" name="mode">
            <option value="unresolved"><?php echo esc_html__('Missing + Changed + Failed', 'jacana-luxe'); ?></option>
            <option value="missing"><?php echo esc_html__('Missing only', 'jacana-luxe'); ?></option>
            <option value="all_pending"><?php echo esc_html__('Include AI Drafts too', 'jacana-luxe'); ?></option>
          </select>
        </p>
        <p>
          <label for="jacana_i18n_export_untranslated_limit" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Max items (0 = all)', 'jacana-luxe'); ?></label>
          <input id="jacana_i18n_export_untranslated_limit" name="limit" type="number" min="0" max="20000" value="500">
        </p>
        <?php submit_button(__('Export Untranslated JSON', 'jacana-luxe'), 'secondary'); ?>
      </form>

      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:960px;margin-top:20px;background:#fff;border:1px solid #dcdcde;padding:20px;">
        <?php wp_nonce_field('jacana_i18n_import_translations'); ?>
        <input type="hidden" name="action" value="jacana_i18n_import_translations">
        <p>
          <label for="jacana_i18n_import_translations_language" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Language', 'jacana-luxe'); ?></label>
          <select id="jacana_i18n_import_translations_language" name="language">
            <?php foreach ($languages as $code => $data) : ?>
              <option value="<?php echo esc_attr($code); ?>"<?php selected($selected_language, $code); ?>><?php echo esc_html($data['label']); ?></option>
            <?php endforeach; ?>
          </select>
        </p>
        <p>
          <label for="jacana_i18n_import_translations_status" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Applied status', 'jacana-luxe'); ?></label>
          <select id="jacana_i18n_import_translations_status" name="status">
            <option value="ai_draft"><?php echo esc_html__('AI Draft', 'jacana-luxe'); ?></option>
            <option value="translated"><?php echo esc_html__('Approved', 'jacana-luxe'); ?></option>
          </select>
        </p>
        <p>
          <label for="jacana_i18n_import_translations_json" style="display:block;margin-bottom:6px;font-weight:600;"><?php echo esc_html__('Translated JSON', 'jacana-luxe'); ?></label>
          <textarea id="jacana_i18n_import_translations_json" name="translations_json" rows="14" class="large-text code" placeholder='{"translations":{"post_123.widget_abc.settings.title":"Titre"}}'></textarea>
        </p>
        <?php submit_button(__('Import Translated JSON', 'jacana-luxe')); ?>
      </form>

      <hr style="margin:32px 0;">

      <h2><?php echo esc_html__('Contact Form 7 Language Map', 'jacana-luxe'); ?></h2>
      <p><?php echo esc_html__('Map each English source form to its translated CF7 duplicate. Format one line as `42 => de:104, fr:105, it:106, es:107`.', 'jacana-luxe'); ?></p>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:960px;">
        <?php wp_nonce_field('jacana_i18n_save_form_map'); ?>
        <input type="hidden" name="action" value="jacana_i18n_save_form_map">
        <textarea name="form_map" rows="8" class="large-text code"><?php echo esc_textarea($form_map_text); ?></textarea>
        <?php submit_button(__('Save Form Map', 'jacana-luxe')); ?>
      </form>
      <?php if (!empty($available_forms)) : ?>
        <table class="widefat striped" style="max-width:960px;margin-top:20px;">
          <thead>
            <tr>
              <th style="width:120px;"><?php echo esc_html__('Form ID', 'jacana-luxe'); ?></th>
              <th><?php echo esc_html__('Title', 'jacana-luxe'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($available_forms as $form) : ?>
              <tr>
                <td><code><?php echo esc_html((string) $form['id']); ?></code></td>
                <td><?php echo esc_html($form['title']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <hr style="margin:32px 0;">

      <h2><?php echo esc_html__('Glossary and Brand Locks', 'jacana-luxe'); ?></h2>
      <p><?php echo esc_html__('One entry per line. Use `source => replacement` when the translated-side locked form should differ. Plain lines are hard-locked as-is.', 'jacana-luxe'); ?></p>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:960px;">
        <?php wp_nonce_field('jacana_i18n_save_glossary'); ?>
        <input type="hidden" name="action" value="jacana_i18n_save_glossary">
        <textarea name="glossary" rows="12" class="large-text code"><?php echo esc_textarea($glossary_text); ?></textarea>
        <?php submit_button(__('Save Glossary Locks', 'jacana-luxe')); ?>
      </form>
    </div>
    <?php
  }

  public function handle_export_strings() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have permission to export strings.', 'jacana-luxe'));
    }

    check_admin_referer('jacana_i18n_export_strings');

    $result = $this->master_string_exporter->export();
    $notice = is_array($result) ? 'export_success' : 'export_error';

    wp_safe_redirect(add_query_arg(array('page' => 'jacana-i18n', 'jacana_i18n_notice' => $notice), admin_url('tools.php')));
    exit;
  }

  public function handle_save_glossary() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have permission to save glossary settings.', 'jacana-luxe'));
    }

    check_admin_referer('jacana_i18n_save_glossary');
    $glossary = isset($_POST['glossary']) ? wp_unslash((string) $_POST['glossary']) : '';
    $this->ai_translator->save_glossary_text($glossary);

    wp_safe_redirect(add_query_arg(array('page' => 'jacana-i18n', 'jacana_i18n_notice' => 'glossary_saved'), admin_url('tools.php')));
    exit;
  }

  public function handle_save_form_map() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have permission to save form mappings.', 'jacana-luxe'));
    }

    check_admin_referer('jacana_i18n_save_form_map');
    $form_map = isset($_POST['form_map']) ? wp_unslash((string) $_POST['form_map']) : '';
    $this->form_bridge->save_form_map_text($form_map);

    wp_safe_redirect(add_query_arg(array('page' => 'jacana-i18n', 'jacana_i18n_notice' => 'form_map_saved'), admin_url('tools.php')));
    exit;
  }

  public function handle_sync_language() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have permission to sync language packs.', 'jacana-luxe'));
    }

    check_admin_referer('jacana_i18n_sync_language');

    $lang = sanitize_key((string) ($_POST['language'] ?? 'de'));
    $master = $this->master_string_exporter->read_export();

    if (empty($master['items'])) {
      $export = $this->master_string_exporter->export();
      $master = is_array($export) ? $export : array();
    }

    if (!empty($master['items'])) {
      $this->language_pack_manager->sync_with_master($lang, $master);
      wp_safe_redirect(add_query_arg(array('page' => 'jacana-i18n', 'lang' => $lang, 'jacana_i18n_notice' => 'sync_success'), admin_url('tools.php')));
      exit;
    }

    wp_safe_redirect(add_query_arg(array('page' => 'jacana-i18n', 'lang' => $lang, 'jacana_i18n_notice' => 'export_error'), admin_url('tools.php')));
    exit;
  }

  public function handle_sync_all_languages() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have permission to sync language packs.', 'jacana-luxe'));
    }

    check_admin_referer('jacana_i18n_sync_all_languages');

    $master = $this->master_string_exporter->read_export();

    if (empty($master['items'])) {
      $export = $this->master_string_exporter->export();
      $master = is_array($export) ? $export : array();
    }

    if (empty($master['items'])) {
      wp_safe_redirect(add_query_arg(array('page' => 'jacana-i18n', 'jacana_i18n_notice' => 'export_error'), admin_url('tools.php')));
      exit;
    }

    foreach ($this->lang_detector->get_supported_languages() as $code => $data) {
      if ($code === $this->lang_detector->get_default_language()) {
        continue;
      }

      $this->language_pack_manager->sync_with_master($code, $master);
    }

    wp_safe_redirect(add_query_arg(array('page' => 'jacana-i18n', 'jacana_i18n_notice' => 'sync_all_success'), admin_url('tools.php')));
    exit;
  }

  public function handle_translate_language() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have permission to run AI translation.', 'jacana-luxe'));
    }

    check_admin_referer('jacana_i18n_translate_language');

    $lang = sanitize_key((string) ($_POST['language'] ?? 'de'));
    $mode = sanitize_key((string) ($_POST['mode'] ?? 'missing'));
    $limit = max(1, min(250, absint($_POST['limit'] ?? 50)));
    $master = $this->master_string_exporter->read_export();

    if (empty($master['items'])) {
      $export = $this->master_string_exporter->export();
      $master = is_array($export) ? $export : array();
    }

    if (empty($master['items'])) {
      wp_safe_redirect(add_query_arg(array('page' => 'jacana-i18n', 'lang' => $lang, 'jacana_i18n_notice' => 'export_error'), admin_url('tools.php')));
      exit;
    }

    $items = $this->language_pack_manager->get_items_for_translation($lang, $master, $mode, $limit);
    if (empty($items)) {
      wp_safe_redirect(add_query_arg(array('page' => 'jacana-i18n', 'lang' => $lang, 'jacana_i18n_notice' => 'translate_empty'), admin_url('tools.php')));
      exit;
    }

    $translated = array();
    foreach (array_chunk($items, 20) as $chunk) {
      $result = $this->ai_translator->translate_batch($chunk, $lang);
      if (is_wp_error($result)) {
        wp_safe_redirect(add_query_arg(array('page' => 'jacana-i18n', 'lang' => $lang, 'jacana_i18n_notice' => 'translate_error'), admin_url('tools.php')));
        exit;
      }
      $translated = array_merge($translated, $result);
    }

    if (!empty($translated)) {
      $this->language_pack_manager->apply_translations($lang, $translated, 'ai_draft');
      wp_safe_redirect(add_query_arg(array('page' => 'jacana-i18n', 'lang' => $lang, 'jacana_i18n_notice' => 'translate_success'), admin_url('tools.php')));
      exit;
    }

    wp_safe_redirect(add_query_arg(array('page' => 'jacana-i18n', 'lang' => $lang, 'jacana_i18n_notice' => 'translate_empty'), admin_url('tools.php')));
    exit;
  }

  public function handle_translate_all_languages() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have permission to run AI translation.', 'jacana-luxe'));
    }

    check_admin_referer('jacana_i18n_translate_all_languages');

    $mode = sanitize_key((string) ($_POST['mode'] ?? 'missing'));
    $limit = max(1, min(250, absint($_POST['limit'] ?? 25)));
    $master = $this->master_string_exporter->read_export();

    if (empty($master['items'])) {
      $export = $this->master_string_exporter->export();
      $master = is_array($export) ? $export : array();
    }

    if (empty($master['items'])) {
      wp_safe_redirect(add_query_arg(array('page' => 'jacana-i18n', 'jacana_i18n_notice' => 'export_error'), admin_url('tools.php')));
      exit;
    }

    $translated_any = false;

    foreach ($this->lang_detector->get_supported_languages() as $lang => $data) {
      if ($lang === $this->lang_detector->get_default_language()) {
        continue;
      }

      $items = $this->language_pack_manager->get_items_for_translation($lang, $master, $mode, $limit);
      if (empty($items)) {
        continue;
      }

      $translated = array();
      foreach (array_chunk($items, 20) as $chunk) {
        $result = $this->ai_translator->translate_batch($chunk, $lang);
        if (is_wp_error($result)) {
          wp_safe_redirect(add_query_arg(array('page' => 'jacana-i18n', 'jacana_i18n_notice' => 'translate_error'), admin_url('tools.php')));
          exit;
        }
        $translated = array_merge($translated, $result);
      }

      if (!empty($translated)) {
        $this->language_pack_manager->apply_translations($lang, $translated, 'ai_draft');
        $translated_any = true;
      }
    }

    wp_safe_redirect(add_query_arg(array(
      'page' => 'jacana-i18n',
      'jacana_i18n_notice' => $translated_any ? 'translate_all_success' : 'translate_empty',
    ), admin_url('tools.php')));
    exit;
  }

  public function handle_review_language() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have permission to review translations.', 'jacana-luxe'));
    }

    check_admin_referer('jacana_i18n_review_language');

    $lang = sanitize_key((string) ($_POST['language'] ?? 'de'));
    $review_status = sanitize_key((string) ($_POST['review_status'] ?? 'ai_draft'));
    $translations = isset($_POST['translations']) && is_array($_POST['translations']) ? wp_unslash($_POST['translations']) : array();
    $statuses = isset($_POST['statuses']) && is_array($_POST['statuses']) ? wp_unslash($_POST['statuses']) : array();
    $updates = array();

    foreach ($statuses as $key => $status) {
      $updates[(string) $key] = array(
        'translation' => isset($translations[$key]) ? (string) $translations[$key] : '',
        'status' => (string) $status,
      );
    }

    if (!empty($updates)) {
      $this->language_pack_manager->update_entries($lang, $updates);
    }

    wp_safe_redirect(add_query_arg(array(
      'page' => 'jacana-i18n',
      'lang' => $lang,
      'review_status' => $review_status,
      'jacana_i18n_notice' => 'review_saved',
    ), admin_url('tools.php')));
    exit;
  }

  public function handle_language_maintenance() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have permission to manage language packs.', 'jacana-luxe'));
    }

    check_admin_referer('jacana_i18n_language_maintenance');

    $lang = sanitize_key((string) ($_POST['language'] ?? 'de'));
    $kind = sanitize_key((string) ($_POST['kind'] ?? ''));

    if ($kind === 'approve_ai_drafts') {
      $this->language_pack_manager->bulk_transition_status($lang, array('ai_draft'), 'translated');
    } elseif ($kind === 'reset_ai_drafts') {
      $this->language_pack_manager->bulk_transition_status($lang, array('ai_draft'), 'missing');
    } elseif ($kind === 'prune_orphaned') {
      $this->language_pack_manager->remove_orphaned($lang);
    } elseif ($kind === 'reset_enum_corruptions') {
      // Remove entries where translation === source (no-op or Elementor enum
      // value that was mistakenly translated). Resetting them to 'missing'
      // lets the translator/AI provide a real translation or leave them blank.
      $this->language_pack_manager->reset_same_as_source_entries($lang);
    }

    wp_safe_redirect(add_query_arg(array(
      'page' => 'jacana-i18n',
      'lang' => $lang,
      'jacana_i18n_notice' => 'maintenance_success',
    ), admin_url('tools.php')));
    exit;
  }

  public function handle_download_language() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have permission to download language packs.', 'jacana-luxe'));
    }

    check_admin_referer('jacana_i18n_download_language');

    $lang = sanitize_key((string) ($_POST['language'] ?? 'de'));
    $pack = $this->dictionary->get_dictionary($lang);
    $json = wp_json_encode($pack, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if (!is_string($json)) {
      wp_safe_redirect(add_query_arg(array('page' => 'jacana-i18n', 'lang' => $lang, 'jacana_i18n_notice' => 'export_error'), admin_url('tools.php')));
      exit;
    }

    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="jacana-' . $lang . '-pack.json"');
    echo $json;
    exit;
  }

  public function handle_import_language() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have permission to import language packs.', 'jacana-luxe'));
    }

    check_admin_referer('jacana_i18n_import_language');

    $lang = sanitize_key((string) ($_POST['language'] ?? 'de'));
    $json = isset($_POST['pack_json']) ? wp_unslash((string) $_POST['pack_json']) : '';
    $decoded = json_decode($json, true);

    if (!is_array($decoded)) {
      wp_safe_redirect(add_query_arg(array(
        'page' => 'jacana-i18n',
        'lang' => $lang,
        'jacana_i18n_notice' => 'import_error',
      ), admin_url('tools.php')));
      exit;
    }

    // If the payload is a translations map/items list, treat it as a safe partial import.
    if ((isset($decoded['translations']) && is_array($decoded['translations'])) || (isset($decoded['items']) && is_array($decoded['items']))) {
      $translations = array();
      if (isset($decoded['translations']) && is_array($decoded['translations'])) {
        foreach ($decoded['translations'] as $key => $value) {
          if (!is_string($key)) {
            continue;
          }
          $translations[(string) $key] = is_string($value) ? $value : '';
        }
      } else {
        foreach ($decoded['items'] as $item) {
          if (!is_array($item) || empty($item['key'])) {
            continue;
          }
          $key = (string) $item['key'];
          $translations[$key] = isset($item['translation']) && is_string($item['translation'])
            ? $item['translation']
            : (isset($item['translated']) && is_string($item['translated']) ? $item['translated'] : '');
        }
      }

      $master = $this->master_string_exporter->read_export();
      $master_items = isset($master['items']) && is_array($master['items']) ? $master['items'] : array();

      $this->language_pack_manager->apply_translations($lang, $translations, 'ai_draft', $master_items);
      wp_safe_redirect(add_query_arg(array(
        'page' => 'jacana-i18n',
        'lang' => $lang,
        'jacana_i18n_notice' => 'import_success',
      ), admin_url('tools.php')));
      exit;
    }

    // Full pack import must contain a keys object; merge with existing pack to avoid destructive overwrite.
    if (!isset($decoded['keys']) || !is_array($decoded['keys'])) {
      wp_safe_redirect(add_query_arg(array(
        'page' => 'jacana-i18n',
        'lang' => $lang,
        'jacana_i18n_notice' => 'import_error',
      ), admin_url('tools.php')));
      exit;
    }

    $existing = $this->dictionary->get_dictionary($lang);
    $merged = $existing;

    if (isset($decoded['meta']) && is_array($decoded['meta'])) {
      $merged['meta'] = array_merge((array) ($merged['meta'] ?? array()), $decoded['meta']);
    }

    foreach ($decoded['keys'] as $key => $entry) {
      if (!is_string($key)) {
        continue;
      }
      if (is_array($entry) || is_string($entry)) {
        $merged['keys'][$key] = $entry;
      }
    }

    if (isset($decoded['strings']) && is_array($decoded['strings'])) {
      $merged['strings'] = array_merge((array) ($merged['strings'] ?? array()), $decoded['strings']);
    }

    if (!$this->dictionary->save_pack($lang, $merged)) {
      wp_safe_redirect(add_query_arg(array(
        'page' => 'jacana-i18n',
        'lang' => $lang,
        'jacana_i18n_notice' => 'import_error',
      ), admin_url('tools.php')));
      exit;
    }

    wp_safe_redirect(add_query_arg(array(
      'page' => 'jacana-i18n',
      'lang' => $lang,
      'jacana_i18n_notice' => 'import_success',
    ), admin_url('tools.php')));
    exit;
  }

  public function handle_export_untranslated() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have permission to export untranslated strings.', 'jacana-luxe'));
    }

    check_admin_referer('jacana_i18n_export_untranslated');

    $lang = sanitize_key((string) ($_POST['language'] ?? 'de'));
    $mode = sanitize_key((string) ($_POST['mode'] ?? 'unresolved'));
    $limit = max(0, absint($_POST['limit'] ?? 0));
    $master = $this->master_string_exporter->read_export();

    if (empty($master['items'])) {
      $export = $this->master_string_exporter->export();
      $master = is_array($export) ? $export : array();
    }

    if (empty($master['items'])) {
      wp_safe_redirect(add_query_arg(array(
        'page' => 'jacana-i18n',
        'lang' => $lang,
        'jacana_i18n_notice' => 'export_untranslated_error',
      ), admin_url('tools.php')));
      exit;
    }

    $this->language_pack_manager->sync_with_master($lang, $master);
    $pack = $this->dictionary->get_dictionary($lang);
    $keys = isset($pack['keys']) && is_array($pack['keys']) ? $pack['keys'] : array();
    $wanted_statuses = array('missing', 'source_changed', 'ai_failed');

    if ($mode === 'missing') {
      $wanted_statuses = array('missing');
    } elseif ($mode === 'all_pending') {
      $wanted_statuses = array('missing', 'source_changed', 'ai_failed', 'ai_draft');
    }

    $items = array();
    foreach ($keys as $key => $entry) {
      if (!is_array($entry)) {
        continue;
      }

      $status = sanitize_key((string) ($entry['status'] ?? ''));
      if (!in_array($status, $wanted_statuses, true)) {
        continue;
      }

      $items[] = array(
        'key' => (string) $key,
        'source' => (string) ($entry['source'] ?? ''),
        'status' => $status,
        'sourceHash' => (string) ($entry['sourceHash'] ?? ''),
      );

      if ($limit > 0 && count($items) >= $limit) {
        break;
      }
    }

    $payload = array(
      'meta' => array(
        'language' => $lang,
        'mode' => $mode,
        'generatedAt' => gmdate('c'),
        'count' => count($items),
        'limit' => $limit,
      ),
      'items' => $items,
    );

    $json = wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!is_string($json)) {
      wp_safe_redirect(add_query_arg(array(
        'page' => 'jacana-i18n',
        'lang' => $lang,
        'jacana_i18n_notice' => 'export_untranslated_error',
      ), admin_url('tools.php')));
      exit;
    }

    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="jacana-' . $lang . '-untranslated.json"');
    echo $json;
    exit;
  }

  public function handle_import_translations() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have permission to import translated strings.', 'jacana-luxe'));
    }

    check_admin_referer('jacana_i18n_import_translations');

    $lang = sanitize_key((string) ($_POST['language'] ?? 'de'));
    $status = sanitize_key((string) ($_POST['status'] ?? 'ai_draft'));
    $json = isset($_POST['translations_json']) ? wp_unslash((string) $_POST['translations_json']) : '';
    $decoded = json_decode($json, true);

    if (!is_array($decoded)) {
      wp_safe_redirect(add_query_arg(array(
        'page' => 'jacana-i18n',
        'lang' => $lang,
        'jacana_i18n_notice' => 'import_translations_error',
      ), admin_url('tools.php')));
      exit;
    }

    $translations = array();

    if (isset($decoded['translations']) && is_array($decoded['translations'])) {
      foreach ($decoded['translations'] as $key => $value) {
        if (!is_string($key)) {
          continue;
        }
        $translations[(string) $key] = is_string($value) ? $value : '';
      }
    } elseif (isset($decoded['items']) && is_array($decoded['items'])) {
      foreach ($decoded['items'] as $item) {
        if (!is_array($item) || empty($item['key'])) {
          continue;
        }
        $key = (string) $item['key'];
        $value = '';
        if (isset($item['translation']) && is_string($item['translation'])) {
          $value = $item['translation'];
        } elseif (isset($item['translated']) && is_string($item['translated'])) {
          $value = $item['translated'];
        } elseif (isset($item['target']) && is_string($item['target'])) {
          $value = $item['target'];
        } elseif (isset($item['text']) && is_string($item['text'])) {
          $value = $item['text'];
        }
        $translations[$key] = $value;
      }
    } elseif (isset($decoded['data']['translations']) && is_array($decoded['data']['translations'])) {
      foreach ($decoded['data']['translations'] as $key => $value) {
        if (!is_string($key)) {
          continue;
        }
        $translations[(string) $key] = is_string($value) ? $value : '';
      }
    }

    if (empty($translations)) {
      $all_strings = true;
      foreach ($decoded as $k => $v) {
        if (!is_string($k) || (!is_string($v) && null !== $v)) {
          $all_strings = false;
          break;
        }
      }
      if ($all_strings) {
        foreach ($decoded as $k => $v) {
          $translations[(string) $k] = (string) $v;
        }
      }
    }

    if (empty($translations)) {
      wp_safe_redirect(add_query_arg(array(
        'page' => 'jacana-i18n',
        'lang' => $lang,
        'jacana_i18n_notice' => 'import_translations_error',
      ), admin_url('tools.php')));
      exit;
    }

    $allowed_statuses = array('ai_draft', 'translated');
    if (!in_array($status, $allowed_statuses, true)) {
      $status = 'ai_draft';
    }

    $master = $this->master_string_exporter->read_export();
    $master_items = isset($master['items']) && is_array($master['items']) ? $master['items'] : array();

    $updated = $this->language_pack_manager->apply_translations($lang, $translations, $status, $master_items);

    if ($updated < 1) {
      wp_safe_redirect(add_query_arg(array(
        'page' => 'jacana-i18n',
        'lang' => $lang,
        'jacana_i18n_notice' => 'import_translations_empty',
      ), admin_url('tools.php')));
      exit;
    }

    wp_safe_redirect(add_query_arg(array(
      'page' => 'jacana-i18n',
      'lang' => $lang,
      'jacana_i18n_notice' => 'import_translations_success',
      'imported' => $updated,
    ), admin_url('tools.php')));
    exit;
  }

  private function translate_catalog_label($key, $fallback) {
    return $this->dictionary->translate_string((string) $fallback, array('key' => (string) $key));
  }

  private function get_frontend_labels() {
    $labels = array();

    foreach ($this->string_catalog->get_items() as $item) {
      if (!is_array($item) || empty($item['key']) || !isset($item['source'])) {
        continue;
      }

      $labels[(string) $item['key']] = $this->dictionary->translate_string((string) $item['source'], array(
        'key' => (string) $item['key'],
      ));
    }

    return $labels;
  }

  private function get_frontend_strings() {
    $dictionary = $this->dictionary->get_dictionary_for_current_language();

    return isset($dictionary['strings']) && is_array($dictionary['strings']) ? $dictionary['strings'] : array();
  }

  private function get_language_flag($code) {
    $flags = array(
      'en' => '🇬🇧',
      'de' => '🇩🇪',
      'fr' => '🇫🇷',
      'it' => '🇮🇹',
      'es' => '🇪🇸',
    );

    $flag = isset($flags[$code]) ? $flags[$code] : strtoupper((string) $code);

    return (string) apply_filters('jacana_i18n_language_flag', $flag, (string) $code);
  }

  public function get_lang_detector() {
    return $this->lang_detector;
  }

  public function get_dictionary() {
    return $this->dictionary;
  }

  public function get_ai_translator() {
    return $this->ai_translator;
  }

  public function get_language_pack_manager() {
    return $this->language_pack_manager;
  }

  public function get_master_string_exporter() {
    return $this->master_string_exporter;
  }

  public function get_switcher_html($atts = array()) {
    $defaults = array(
      'class' => '',
      'label' => $this->translate_catalog_label('catalog.ui.language', 'Language'),
      'variant' => 'inline',
    );

    return $this->render_switcher_shortcode(wp_parse_args($atts, $defaults));
  }

  public static function activate() {
    $plugin = self::instance();
    $plugin->lang_detector->register_rewrite_rules();
    flush_rewrite_rules();
  }

  public static function deactivate() {
    flush_rewrite_rules();
  }

  public function filter_nav_menu_item_title($title, $item) {
    if (is_admin() || $this->lang_detector->is_default_language()) {
      return $title;
    }

    return $this->string_filter->translate_string($title, array(
      'area' => 'navigation',
      'menu_id' => $item->ID,
    ));
  }

  public function filter_post_title($title, $id = null) {
    if (is_admin() || $this->lang_detector->is_default_language()) {
      return $title;
    }

    if (!$id) {
       $id = get_the_ID();
    }

    $post_type = get_post_type($id);
    if ($post_type !== 'jacana_destination') {
      return $title;
    }

    return $this->string_filter->translate_string($title, array(
      'key' => sprintf('post_%d.title', (int) $id),
      'area' => 'post_title',
      'post_id' => $id,
      'post_type' => $post_type,
    ));
  }

  public function filter_post_content($content) {
    if (is_admin() || $this->lang_detector->is_default_language()) {
      return $content;
    }

    $id = get_the_ID();
    $post_type = get_post_type($id);
    if ($post_type !== 'jacana_destination') {
      return $content;
    }

    return $this->string_filter->translate_string($content, array(
      'key' => sprintf('post_%d.content', (int) $id),
      'area' => 'post_content',
      'post_id' => $id,
      'post_type' => $post_type,
    ));
  }

  public function filter_post_excerpt($excerpt) {
    if (is_admin() || $this->lang_detector->is_default_language()) {
      return $excerpt;
    }

    $id = get_the_ID();
    $post_type = get_post_type($id);
    if ($post_type !== 'jacana_destination') {
      return $excerpt;
    }

    return $this->string_filter->translate_string($excerpt, array(
      'key' => sprintf('post_%d.excerpt', (int) $id),
      'area' => 'post_excerpt',
      'post_id' => $id,
      'post_type' => $post_type,
    ));
  }

  public function filter_post_meta($value, $object_id, $meta_key, $single) {
    if (is_admin() || $this->lang_detector->is_default_language()) {
      return $value;
    }

    if (strpos((string) $meta_key, '_jacana_dest_') !== 0) {
      return $value;
    }

    // We only auto-translate if it's not already being filtered (avoid recursion)
    static $filtering = false;
    if ($filtering) {
      return $value;
    }

    $filtering = true;
    $original_value = get_post_meta($object_id, $meta_key, $single);
    $filtering = false;

    if ($single) {
      if (!is_string($original_value) || $original_value === '') {
        return $value;
      }

      return $this->string_filter->translate_string($original_value, array(
        'key' => sprintf('post_%d.meta.%s', (int) $object_id, $meta_key),
        'area' => 'post_meta',
        'post_id' => $object_id,
        'meta_key' => $meta_key,
      ));
    } else {
      if (!is_array($original_value)) {
        return $value;
      }

      $translated_array = array();
      foreach ($original_value as $val) {
        if (is_string($val) && $val !== '') {
          $translated_array[] = $this->string_filter->translate_string($val, array(
            'key' => sprintf('post_%d.meta.%s', (int) $object_id, $meta_key),
            'area' => 'post_meta',
            'post_id' => $object_id,
            'meta_key' => $meta_key,
          ));
        } else {
          $translated_array[] = $val;
        }
      }
      return $translated_array;
    }
  }
}

function jacana_i18n() {
  return Jacana_I18n::instance();
}

function jacana_i18n_get_switcher_html($atts = array()) {
  return jacana_i18n()->get_switcher_html($atts);
}

function jacana_i18n_localize_url($url, $lang = null) {
  return jacana_i18n()->get_lang_detector()->localize_url((string) $url, $lang);
}

register_activation_hook(JACANA_I18N_FILE, array('Jacana_I18n', 'activate'));
register_deactivation_hook(JACANA_I18N_FILE, array('Jacana_I18n', 'deactivate'));

jacana_i18n();
