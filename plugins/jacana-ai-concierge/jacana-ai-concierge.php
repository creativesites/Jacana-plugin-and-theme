<?php
/**
 * Plugin Name: Jacana AI Concierge
 * Description: AI-inspired concierge chat and lead capture for Jacana Safaris & Tours.
 * Version: 0.3.3
 * Author: Winston Zulu
 */

if (!defined('ABSPATH')) {
  exit;
}

final class Jacana_AI_Concierge {
  private function endpoint($path) {
    $route = '/' . ltrim((string) $path, '/');
    return esc_url_raw(add_query_arg('rest_route', $route, home_url('/index.php')));
  }

  public function __construct() {
    add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
  }

  public function enqueue_assets() {
    $css_path = __DIR__ . '/assets/concierge.css';
    $js_path = __DIR__ . '/assets/concierge.js';
    wp_enqueue_style('jacana-ai-concierge', plugin_dir_url(__FILE__) . 'assets/concierge.css', array(), file_exists($css_path) ? filemtime($css_path) : '0.1.1');
    wp_enqueue_script('jacana-ai-concierge', plugin_dir_url(__FILE__) . 'assets/concierge.js', array(), file_exists($js_path) ? filemtime($js_path) : '0.1.1', true);
    $uploads = wp_get_upload_dir();
    $custom_icon = (string) get_option('jacana_ai_chatbot_icon', '');
    $logo = $custom_icon !== '' ? $custom_icon : trailingslashit($uploads['baseurl']) . '2026/02/logo.png';
    wp_localize_script('jacana-ai-concierge', 'jacanaConcierge', array(
      'trackEndpoint' => $this->endpoint('jacana/v1/track'),
      'chatEndpoint' => $this->endpoint('jacana/v1/chat'),
      'chatHistoryEndpoint' => $this->endpoint('jacana/v1/chat/history'),
      'chatSessionsEndpoint' => $this->endpoint('jacana/v1/chat/sessions'),
      'chatSessionLabelEndpoint' => $this->endpoint('jacana/v1/chat/session/label'),
      'leadEndpoint' => $this->endpoint('jacana/v1/lead'),
      'profileEndpoint' => $this->endpoint('jacana/v1/profile'),
      'conversionEndpoint' => $this->endpoint('jacana/v1/conversion'),
      'aiEndpoint' => $this->endpoint('jacana/v1/ai'),
      'aiLogEndpoint' => $this->endpoint('jacana/v1/ai-client-log'),
      'whatsapp' => '+264813448866',
      'logo' => esc_url_raw($logo),
      'mapsKey' => esc_attr(get_option('jacana_maps_api_key', '')),
    ));
  }
}

new Jacana_AI_Concierge();
