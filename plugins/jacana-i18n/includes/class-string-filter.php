<?php

if (!defined('ABSPATH')) {
  exit;
}

final class Jacana_I18n_String_Filter {
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

    add_filter('rest_post_dispatch', array($this, 'translate_rest_response'), 20, 3);
    $this->hooks_registered = true;
  }

  public function translate_string($string, array $context = array()) {
    return $this->dictionary->translate_string($string, $context);
  }

  public function translate_recursive($value, array $context = array()) {
    if (is_string($value)) {
      return $this->translate_string($value, $context);
    }

    if (is_array($value)) {
      foreach ($value as $key => $child) {
        $child_context = $context;
        $child_context['key'] = isset($context['key']) ? $context['key'] . '.' . $key : (string) $key;
        $value[$key] = $this->translate_recursive($child, $child_context);
      }

      return $value;
    }

    if ($value instanceof stdClass) {
      $data = get_object_vars($value);
      $translated = $this->translate_recursive($data, $context);

      return (object) $translated;
    }

    return $value;
  }

  public function translate_rest_response($response, $server, $request) {
    if ($this->lang_detector->is_default_language()) {
      return $response;
    }

    if (!($response instanceof WP_REST_Response)) {
      return $response;
    }

    $route = $request instanceof WP_REST_Request ? (string) $request->get_route() : '';

    if (!$this->should_translate_rest_route($route)) {
      return $response;
    }

    $data = $response->get_data();
    $response->set_data($this->translate_recursive($data, array(
      'key' => 'rest' . str_replace('/', '.', trim($route, '/')),
      'rest_route' => $route,
    )));

    return $response;
  }

  private function should_translate_rest_route($route) {
    if ($route === '') {
      return false;
    }

    if (strpos($route, '/jacana/') === 0) {
      return true;
    }

    return (bool) apply_filters('jacana_i18n_translate_rest_route', false, $route);
  }
}
