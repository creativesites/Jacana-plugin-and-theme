<?php

if (!defined('ABSPATH')) {
  exit;
}

final class Jacana_I18n_Lang_Detector {
  const COOKIE_NAME = 'jacana_lang';

  /** @var string */
  private $default_language = 'en';

  /** @var string */
  private $current_language = 'en';

  /** @var array<string,array<string,string>> */
  private $supported_languages = array(
    'en' => array(
      'label' => 'English',
      'locale' => 'en_US',
    ),
    'de' => array(
      'label' => 'Deutsch',
      'locale' => 'de_DE',
    ),
    'fr' => array(
      'label' => 'Français',
      'locale' => 'fr_FR',
    ),
    'it' => array(
      'label' => 'Italiano',
      'locale' => 'it_IT',
    ),
    'es' => array(
      'label' => 'Español',
      'locale' => 'es_ES',
    ),
  );

  /** @var bool */
  private $bootstrapped = false;

  public function bootstrap() {
    if ($this->bootstrapped) {
      return;
    }

    add_filter('query_vars', array($this, 'register_query_vars'));
    add_action('parse_request', array($this, 'handle_prefixed_request'), 1);
    add_action('template_redirect', array($this, 'persist_language_cookie'), 1);
    add_action('template_redirect', array($this, 'redirect_localized_front_page'), 2);
    add_filter('redirect_canonical', array($this, 'filter_canonical_redirect'), 10, 2);
    add_filter('page_link', array($this, 'localize_frontend_url'), 20);
    add_filter('post_link', array($this, 'localize_frontend_url'), 20);
    add_filter('post_type_link', array($this, 'localize_frontend_url'), 20);
    add_filter('term_link', array($this, 'localize_frontend_url'), 20);
    add_filter('nav_menu_link_attributes', array($this, 'localize_menu_link_attributes'), 20, 4);

    $this->current_language = $this->detect_current_language();
    $this->bootstrapped = true;
  }

  public function register_rewrite_rules() {
    $pattern = implode('|', array_map('preg_quote', $this->get_non_default_languages()));

    add_rewrite_tag('%jacana_lang%', '([a-z]{2})');
    add_rewrite_tag('%jacana_path%', '(.*)');

    if ($pattern === '') {
      return;
    }

    add_rewrite_rule('^(' . $pattern . ')/?$', 'index.php?jacana_lang=$matches[1]&jacana_path=', 'top');
    add_rewrite_rule('^(' . $pattern . ')/(.*)?$', 'index.php?jacana_lang=$matches[1]&jacana_path=$matches[2]', 'top');
  }

  public function register_query_vars($vars) {
    $vars[] = 'jacana_lang';
    $vars[] = 'jacana_path';

    return $vars;
  }

  public function handle_prefixed_request($wp) {
    if (!isset($wp->query_vars['jacana_lang'])) {
      $this->current_language = $this->detect_current_language();
      return;
    }

    $lang = sanitize_key((string) $wp->query_vars['jacana_lang']);

    if (!$this->is_supported_language($lang)) {
      $this->current_language = $this->default_language;
      return;
    }

    $this->current_language = $lang;

    $path = isset($wp->query_vars['jacana_path']) ? trim((string) $wp->query_vars['jacana_path'], '/') : '';
    unset($wp->query_vars['jacana_path']);

    if ($path === '') {
      $front_page = (int) get_option('page_on_front');

      if ($front_page > 0) {
        $wp->query_vars['page_id'] = $front_page;
      }

      return;
    }

    $resolved = $this->resolve_path_to_query_vars($path);

    if (!empty($resolved)) {
      $wp->query_vars = array_merge($wp->query_vars, $resolved);
      return;
    }

    $wp->query_vars['pagename'] = $path;
  }

  public function persist_language_cookie() {
    if (headers_sent()) {
      return;
    }

    // Use the options-array form (PHP 7.3+) to set SameSite=Lax, matching the
    // attribute the JS setCookie helper writes, so browsers treat them as the
    // same cookie rather than two competing cookies with different attributes.
    setcookie(self::COOKIE_NAME, $this->get_current_language(), array(
      'expires'  => time() + YEAR_IN_SECONDS,
      'path'     => COOKIEPATH ? COOKIEPATH : '/',
      'domain'   => (string) COOKIE_DOMAIN,
      'secure'   => is_ssl(),
      'httponly' => false,
      'samesite' => 'Lax',
    ));
  }

  public function redirect_localized_front_page() {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron() || headers_sent()) {
      return;
    }

    if ($this->detect_language_from_request_uri()) {
      return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/';
    $path = trim((string) wp_parse_url($request_uri, PHP_URL_PATH), '/');
    $query = (string) wp_parse_url($request_uri, PHP_URL_QUERY);

    if ($path !== '' || $query !== '') {
      return;
    }

    $preferred = $this->get_current_language();
    if ($preferred === $this->default_language) {
      return;
    }

    $target = $this->localize_url(home_url('/'), $preferred);
    $current = $this->get_current_url();

    if ($this->normalize_url_for_compare($target) === $this->normalize_url_for_compare($current)) {
      return;
    }

    wp_safe_redirect($target, 302);
    exit;
  }

  public function filter_canonical_redirect($redirect_url, $requested_url) {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
      return $redirect_url;
    }

    // Suppress redirect when the URL carries a language prefix (e.g. /de/about/).
    $request_lang = $this->detect_language_from_request_uri();
    if ($request_lang !== '') {
      return false;
    }

    // Also suppress when a ?lang= query param is present with a valid language
    // code, so WordPress doesn't strip it before the cookie is written.
    if (isset($_GET['lang'])) {
      $param_lang = sanitize_key((string) $_GET['lang']);
      if ($this->is_supported_language($param_lang)) {
        return false;
      }
    }

    return $redirect_url;
  }

  public function localize_frontend_url($url, ...$unused) {
    if (!$this->should_localize_urls()) {
      return $url;
    }

    return $this->localize_url($url);
  }

  public function localize_menu_link_attributes($atts, $menu_item = null, $args = null, $depth = null) {
    if (!$this->should_localize_urls() || empty($atts['href'])) {
      return $atts;
    }

    $atts['href'] = $this->localize_url($atts['href']);

    return $atts;
  }

  public function localize_url($url, $lang = null) {
    $lang = $lang ? sanitize_key((string) $lang) : $this->get_current_language();

    if (!$this->is_supported_language($lang) || !$this->is_localizable_url($url)) {
      return $url;
    }

    $parts = wp_parse_url($url);
    $home_parts = wp_parse_url(home_url('/'));

    if (empty($parts['path'])) {
      $path = '/';
    } else {
      $path = '/' . ltrim((string) $parts['path'], '/');
    }

    $path = $this->strip_language_prefix($path);

    if ($lang !== $this->default_language) {
      $path = $this->add_language_prefix($path, $lang);
    }

    $rebuilt = '';

    if (!empty($parts['scheme'])) {
      $rebuilt .= $parts['scheme'] . '://';
    } elseif (!empty($home_parts['scheme'])) {
      $rebuilt .= $home_parts['scheme'] . '://';
    }

    if (!empty($parts['user'])) {
      $rebuilt .= $parts['user'];
      if (!empty($parts['pass'])) {
        $rebuilt .= ':' . $parts['pass'];
      }
      $rebuilt .= '@';
    }

    $rebuilt .= !empty($parts['host']) ? $parts['host'] : (string) ($home_parts['host'] ?? '');

    if (!empty($parts['port'])) {
      $rebuilt .= ':' . $parts['port'];
    }

    $rebuilt .= $path;

    if (!empty($parts['query'])) {
      $rebuilt .= '?' . $parts['query'];
    }

    if (!empty($parts['fragment'])) {
      $rebuilt .= '#' . $parts['fragment'];
    }

    return $rebuilt;
  }

  public function get_switcher_urls() {
    $urls = array();
    $current_url = $this->get_current_url();

    foreach ($this->supported_languages as $code => $data) {
      // Strip any existing ?lang= param, then localize to the target language.
      $base_url = remove_query_arg('lang', $this->localize_url($current_url, $code));

      // For the default language there is no URL prefix (e.g. /en/ does not
      // exist), so the server cannot detect "English" from the path alone and
      // must rely on the browser cookie.  If the cookie is stale — which
      // happens when a user comes from /de/ and the language-switcher element
      // lacks data-jacana-lang-link (common in Elementor/Zoora kit sections) —
      // the server would re-detect German and either serve German content or
      // redirect back to /de/.  Adding ?lang={code} gives the server an
      // explicit, highest-priority signal that overrides the stale cookie.
      // filter_canonical_redirect already suppresses canonical stripping for
      // ?lang= params, and redirect_localized_front_page exits early when the
      // query string is non-empty, so both redirect paths are safe.
      if ($code === $this->default_language) {
        $base_url = add_query_arg('lang', $code, $base_url);
      }

      $urls[$code] = $base_url;
    }

    return $urls;
  }

  public function get_alternate_urls() {
    $urls = $this->get_switcher_urls();
    $urls['x-default'] = $this->localize_url($this->get_current_url(), $this->get_default_language());

    return $urls;
  }

  public function get_current_url() {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/';
    $path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
    $query = (string) wp_parse_url($request_uri, PHP_URL_QUERY);
    list(, $relative_path) = $this->split_home_path_and_relative($path);

    $url = home_url($relative_path);

    if ($query !== '') {
      // Strip the internal ?lang= param so switcher URLs, canonical tags, and
      // hreflang alternates are always based on the clean page URL.
      $params = array();
      wp_parse_str($query, $params);
      unset($params['lang']);
      $clean_query = http_build_query($params);
      if ($clean_query !== '') {
        $url = $url . '?' . $clean_query;
      }
    }

    return $url;
  }

  public function get_default_language() {
    return $this->default_language;
  }

  public function get_current_language() {
    return $this->current_language;
  }

  public function get_current_locale() {
    return (string) $this->supported_languages[$this->get_current_language()]['locale'];
  }

  public function is_default_language() {
    return $this->get_current_language() === $this->get_default_language();
  }

  public function get_supported_languages() {
    return $this->supported_languages;
  }

  public function is_supported_language($lang) {
    return isset($this->supported_languages[$lang]);
  }

  private function detect_current_language() {
    // 1. URL Query Parameter (Explicit Override)
    if (isset($_GET['lang'])) {
      $lang = sanitize_key((string) $_GET['lang']);
      if ($this->is_supported_language($lang)) {
        return $lang;
      }
    }

    // 2. Query Var (from rewrite rules)
    $query_lang = get_query_var('jacana_lang');

    if (is_string($query_lang) && $this->is_supported_language($query_lang)) {
      return $query_lang;
    }

    $uri_lang = $this->detect_language_from_request_uri();
    if ($uri_lang) {
      return $uri_lang;
    }

    $cookie_lang = isset($_COOKIE[self::COOKIE_NAME]) ? sanitize_key((string) wp_unslash($_COOKIE[self::COOKIE_NAME])) : '';
    if ($cookie_lang && $this->is_supported_language($cookie_lang)) {
      return $cookie_lang;
    }

    return $this->default_language;
  }

  private function detect_language_from_request_uri() {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '';
    $path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
    list(, $relative_path) = $this->split_home_path_and_relative($path);
    $segments = array_values(array_filter(explode('/', trim($relative_path, '/'))));

    if (empty($segments)) {
      return '';
    }

    $candidate = sanitize_key((string) $segments[0]);

    return in_array($candidate, $this->get_non_default_languages(), true) ? $candidate : '';
  }

  private function resolve_path_to_query_vars($path) {
    $normalized = trim((string) $path, '/');

    if ($normalized === '') {
      return array();
    }

    $post_id = url_to_postid(home_url('/' . $normalized . '/'));

    if ($post_id > 0) {
      $post = get_post($post_id);

      if ($post instanceof WP_Post) {
        if ($post->post_type === 'page') {
          return array('page_id' => (int) $post->ID);
        }

        if ($post->post_type === 'post') {
          return array('p' => (int) $post->ID);
        }

        return array(
          'post_type' => $post->post_type,
          'name' => $post->post_name,
        );
      }
    }

    $page = get_page_by_path($normalized, OBJECT, 'page');
    if ($page instanceof WP_Post) {
      return array('page_id' => (int) $page->ID);
    }

    $rewrite_match = $this->match_rewrite_rules($normalized);
    if (!empty($rewrite_match)) {
      return $rewrite_match;
    }

    return array();
  }

  private function should_localize_urls() {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
      return false;
    }

    if (defined('REST_REQUEST') && REST_REQUEST) {
      return false;
    }

    return true;
  }

  private function is_localizable_url($url) {
    if (!is_string($url) || $url === '') {
      return false;
    }

    if (strpos($url, '#') === 0) {
      return false;
    }

    if (preg_match('#^(mailto|tel|javascript):#i', $url)) {
      return false;
    }

    $parts = wp_parse_url($url);
    $home_parts = wp_parse_url(home_url('/'));

    if (!empty($parts['host']) && !empty($home_parts['host']) && strtolower((string) $parts['host']) !== strtolower((string) $home_parts['host'])) {
      return false;
    }

    $path = isset($parts['path']) ? (string) $parts['path'] : '';

    if (strpos($path, '/wp-admin') === 0 || strpos($path, '/wp-login.php') === 0) {
      return false;
    }

    if (!empty($parts['query']) && strpos((string) $parts['query'], 'rest_route=') !== false) {
      return false;
    }

    return true;
  }

  private function strip_language_prefix($path) {
    $path = '/' . ltrim((string) $path, '/');
    list($base_path, $relative_path) = $this->split_home_path_and_relative($path);
    $languages = array_keys($this->supported_languages);

    if (empty($languages)) {
      return $path;
    }

    $pattern = '#^/(' . implode('|', array_map('preg_quote', $languages)) . ')(/|$)#i';
    $relative_path = (string) preg_replace($pattern, '/', $relative_path, 1);

    if ($relative_path === '') {
      $relative_path = '/';
    }

    if ($base_path !== '') {
      $base_path = rtrim($base_path, '/');
      return $base_path . ($relative_path === '/' ? '/' : $relative_path);
    }

    return $relative_path;
  }

  private function add_language_prefix($path, $lang) {
    $lang = sanitize_key((string) $lang);
    $path = '/' . ltrim((string) $path, '/');
    list($base_path, $relative_path) = $this->split_home_path_and_relative($path);

    $relative_path = '/' . ltrim((string) $relative_path, '/');
    $relative_suffix = $relative_path === '/' ? '' : $relative_path;

    if ($base_path !== '') {
      return rtrim($base_path, '/') . '/' . $lang . $relative_suffix;
    }

    return '/' . $lang . $relative_suffix;
  }

  private function split_home_path_and_relative($path) {
    $path = '/' . ltrim((string) $path, '/');
    $home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
    $home_path = '/' . trim($home_path, '/');

    if ($home_path === '//') {
      $home_path = '/';
    }

    if ($home_path !== '/' && $home_path !== '') {
      if ($path === $home_path || $path === rtrim($home_path, '/')) {
        return array($home_path, '/');
      }

      $home_prefix = rtrim($home_path, '/') . '/';
      if (strpos($path, $home_prefix) === 0) {
        $relative = substr($path, strlen(rtrim($home_path, '/')));
        $relative = '/' . ltrim((string) $relative, '/');
        return array($home_path, $relative === '' ? '/' : $relative);
      }
    }

    return array('', $path);
  }

  private function match_rewrite_rules($normalized_path) {
    global $wp_rewrite;

    if (!($wp_rewrite instanceof WP_Rewrite)) {
      return array();
    }

    $rules = $wp_rewrite->wp_rewrite_rules();
    if (!is_array($rules) || empty($rules)) {
      return array();
    }

    $request = trim((string) $normalized_path, '/');

    foreach ($rules as $match => $query) {
      if (strpos((string) $query, 'jacana_lang=') !== false) {
        continue;
      }

      if (!preg_match('#^' . $match . '$#', $request, $matches)) {
        continue;
      }

      $resolved_query = (string) $query;

      foreach ($matches as $index => $value) {
        if (!is_int($index)) {
          continue;
        }

        $resolved_query = str_replace('$matches[' . $index . ']', rawurlencode((string) $value), $resolved_query);
      }

      parse_str($resolved_query, $vars);

      if (!is_array($vars) || empty($vars)) {
        continue;
      }

      unset($vars['error'], $vars['jacana_lang'], $vars['jacana_path']);

      if (!empty($vars)) {
        return $vars;
      }
    }

    return array();
  }

  private function get_non_default_languages() {
    return array_values(array_filter(array_keys($this->supported_languages), function ($lang) {
      return $lang !== $this->default_language;
    }));
  }

  private function normalize_url_for_compare($url) {
    $parts = wp_parse_url((string) $url);

    if (!is_array($parts)) {
      return trim((string) $url);
    }

    $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : '';
    $host = isset($parts['host']) ? strtolower((string) $parts['host']) : '';
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    $path = isset($parts['path']) ? rtrim((string) $parts['path'], '/') : '';
    $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

    return $scheme . '://' . $host . $port . ($path === '' ? '/' : $path) . $query;
  }
}
