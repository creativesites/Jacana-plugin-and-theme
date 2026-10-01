<?php

if (!defined('ABSPATH')) {
  exit;
}

final class Jacana_I18n_Gettext_Bridge {
  /** @var Jacana_I18n_Dictionary */
  private $dictionary;

  /** @var Jacana_I18n_Lang_Detector */
  private $lang_detector;

  /** @var bool */
  private $hooks_registered = false;

  public function __construct(Jacana_I18n_Dictionary $dictionary, Jacana_I18n_Lang_Detector $lang_detector) {
    $this->dictionary = $dictionary;
    $this->lang_detector = $lang_detector;
  }

  public function register_hooks() {
    if ($this->hooks_registered) {
      return;
    }

    add_filter('gettext', array($this, 'translate_gettext'), 20, 3);
    $this->hooks_registered = true;
  }

  public function translate_gettext($translation, $text, $domain) {
    if (is_admin() || $this->lang_detector->is_default_language()) {
      return $translation;
    }

    if (!$this->should_translate_domain($domain)) {
      return $translation;
    }

    return $this->dictionary->translate_string((string) $translation, array(
      'domain' => (string) $domain,
    ));
  }

  private function should_translate_domain($domain) {
    $allowed_domains = apply_filters('jacana_i18n_gettext_domains', array(
      'jacana-luxe',
      'jacana-compliance',
      'contact-form-7',
    ));

    return in_array((string) $domain, (array) $allowed_domains, true);
  }
}
