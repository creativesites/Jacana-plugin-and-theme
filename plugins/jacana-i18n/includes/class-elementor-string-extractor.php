<?php

if (!defined('ABSPATH')) {
  exit;
}

final class Jacana_I18n_Elementor_String_Extractor {
  public function walk_document(array $nodes, callable $callback) {
    foreach ($nodes as $node) {
      if (!is_array($node)) {
        continue;
      }

      $settings = isset($node['settings']) && is_array($node['settings']) ? $node['settings'] : array();
      $widget_type = isset($node['widgetType']) ? (string) $node['widgetType'] : '';

      if ($widget_type !== '' && !empty($settings)) {
        $strings = $this->extract_widget_strings($settings);

        if (!empty($strings)) {
          $callback($node, $strings);
        }
      }

      if (!empty($node['elements']) && is_array($node['elements'])) {
        $this->walk_document($node['elements'], $callback);
      }
    }
  }

  public function extract_widget_strings(array $settings, $path = '') {
    $results = array();

    foreach ($settings as $key => $value) {
      $segment = is_int($key) ? (string) $key : sanitize_key((string) $key);
      $child_path = $path === '' ? $segment : $path . '.' . $segment;

      if ($this->should_skip_path($child_path)) {
        continue;
      }

      if (is_string($value)) {
        if ($this->is_translatable_leaf($value, $child_path)) {
          $results[$child_path] = $value;
        }
        continue;
      }

      if (is_array($value)) {
        $results = array_merge($results, $this->extract_widget_strings($value, $child_path));
      }
    }

    return $results;
  }

  private function is_translatable_leaf($value, $path) {
    $value = trim((string) $value);

    if ($value === '') {
      return false;
    }

    if (!preg_match('/\p{L}/u', $value)) {
      return false;
    }

    if (filter_var($value, FILTER_VALIDATE_URL) || filter_var($value, FILTER_VALIDATE_EMAIL)) {
      return false;
    }

    if (($value[0] === '/' || $value[0] === '#') && strpos($value, ' ') === false) {
      return false;
    }

    if (preg_match('/^#[0-9a-f]{3,8}$/i', $value)) {
      return false;
    }

    if (preg_match('/^(left|right|center|top|bottom|yes|no|true|false|default|inherit|auto|normal|solid|dashed|none|full|boxed|inline|stacked|above|below|stretch|grow|side|row|column|grid|flex|block|hidden|visible)$/i', $value)) {
      return false;
    }

    if (is_numeric($value)) {
      return false;
    }

    if ($this->should_skip_path($path)) {
      return false;
    }

    return true;
  }

  private function should_skip_path($path) {
    $segments = array_map('strtolower', explode('.', (string) $path));

    foreach ($segments as $segment) {
      if ($segment === '') {
        continue;
      }

      if (in_array($segment, array(
        'url', 'href', 'link', 'image', 'img', 'icon', 'id', 'class', 'classes', 'css', 'style',
        // Elementor layout / enum controls whose values are programmatic, not
        // display text (e.g. content_width="full", view="stacked", _skin="…").
        // Translating these corrupts widget rendering.
        'content_width', '_skin', 'layout', 'gap', 'breakpoint',
      ), true)) {
        return true;
      }

      foreach (array('_url', '_href', '_link', '_image', '_img', '_icon', '_id', '_class') as $suffix) {
        if (substr($segment, -strlen($suffix)) === $suffix) {
          return true;
        }
      }
    }

    $skip_fragments = array(
      '.url',
      '.link',
      '.href',
      '.image',
      '.img',
      '.icon',
      '.id',
      '.class',
      '.classes',
      '.css',
      '.style',
      '.color',
      '.background',
      '.padding',
      '.margin',
      '.border',
      '.width',
      '.height',
      '.alignment',
      '.align',
      '.position',
      '.size',
      '.unit',
      '.typography',
      '.font',
      '.animation',
      '.delay',
      '.duration',
      '.speed',
      '.view',
      '.content_width',
      '.skin',
      'selected_icon',
      '__globals__',
    );

    foreach ($skip_fragments as $fragment) {
      if (strpos($path, $fragment) !== false) {
        return true;
      }
    }

    return false;
  }
}
