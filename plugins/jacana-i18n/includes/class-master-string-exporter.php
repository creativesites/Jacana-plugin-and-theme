<?php

if (!defined('ABSPATH')) {
  exit;
}

final class Jacana_I18n_Master_String_Exporter {
  /** @var Jacana_I18n_Elementor_String_Extractor */
  private $extractor;

  /** @var Jacana_I18n_Static_String_Catalog */
  private $catalog;

  /** @var string */
  private $output_file;

  public function __construct(Jacana_I18n_Elementor_String_Extractor $extractor, Jacana_I18n_Static_String_Catalog $catalog, $output_file) {
    $this->extractor = $extractor;
    $this->catalog = $catalog;
    $this->output_file = (string) $output_file;
  }

  public function export() {
    global $wpdb;

    $rows = $wpdb->get_results(
      "SELECT p.ID, p.post_title, p.post_type, p.post_status, pm.meta_value
       FROM {$wpdb->posts} p
       INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
       WHERE pm.meta_key = '_elementor_data'
         AND p.post_status NOT IN ('auto-draft', 'trash', 'inherit')
       ORDER BY p.ID ASC",
      ARRAY_A
    );

    if (!is_array($rows)) {
      $rows = array();
    }

    $items_by_key = array();
    $post_count = 0;
    $widget_count = 0;

    foreach ($rows as $row) {
      $document = json_decode((string) $row['meta_value'], true);

      if (!is_array($document)) {
        continue;
      }

      $post_count++;

      $this->extractor->walk_document($document, function ($node, $strings) use (&$items_by_key, &$widget_count, $row) {
        $widget_id = isset($node['id']) ? (string) $node['id'] : '';
        $widget_type = isset($node['widgetType']) ? (string) $node['widgetType'] : '';

        if ($widget_id === '' || $widget_type === '') {
          return;
        }

        $widget_count++;

        foreach ($strings as $path => $source) {
          $key = sprintf(
            'post_%d.widget_%s.settings.%s',
            (int) $row['ID'],
            $widget_id,
            $path
          );

          $items_by_key[$key] = array(
            'key' => $key,
            'source' => $source,
            'postId' => (int) $row['ID'],
            'postTitle' => (string) $row['post_title'],
            'postType' => (string) $row['post_type'],
            'postStatus' => (string) $row['post_status'],
            'widgetId' => $widget_id,
            'widgetType' => $widget_type,
            'path' => (string) $path,
          );
        }
      });
    }

    // --- Destination Export ---
    $destinations = $wpdb->get_results(
      "SELECT ID, post_title, post_content, post_excerpt, post_type, post_status
       FROM {$wpdb->posts}
       WHERE post_type = 'jacana_destination'
         AND post_status NOT IN ('auto-draft', 'trash', 'inherit')
       ORDER BY ID ASC",
      ARRAY_A
    );

    $translatable_meta_keys = array(
      '_jacana_dest_subtitle',
      '_jacana_dest_summary',
      '_jacana_dest_why_visit',
      '_jacana_dest_best_months',
      '_jacana_dest_climate',
      '_jacana_dest_roads',
      '_jacana_dest_family',
      '_jacana_dest_access',
      '_jacana_dest_activities_json',
      '_jacana_dest_accom_styles',
      '_jacana_dest_typical_stay',
      '_jacana_dest_nearby_bases',
      '_jacana_dest_booking_notes',
      '_jacana_dest_trip_length',
      '_jacana_dest_combine_with',
      '_jacana_dest_route_pos',
      '_jacana_dest_transfers',
      '_jacana_dest_permits',
      '_jacana_dest_wildlife',
      '_jacana_dest_landscape',
      '_jacana_dest_culture',
      '_jacana_dest_safety',
      '_jacana_dest_cta',
      '_jacana_dest_lead_tags',
      '_jacana_dest_faq_json',
    );

    foreach ($destinations as $dest) {
      $post_id = (int) $dest['ID'];
      $post_title = (string) $dest['post_title'];
      $post_content = (string) $dest['post_content'];
      $post_excerpt = (string) $dest['post_excerpt'];

      // Title
      $title_key = sprintf('post_%d.title', $post_id);
      $items_by_key[$title_key] = array(
        'key' => $title_key,
        'source' => $post_title,
        'postId' => $post_id,
        'postTitle' => $post_title,
        'postType' => 'jacana_destination',
        'field' => 'title',
      );

      // Content
      if ($post_content !== '') {
        $content_key = sprintf('post_%d.content', $post_id);
        $items_by_key[$content_key] = array(
          'key' => $content_key,
          'source' => $post_content,
          'postId' => $post_id,
          'postTitle' => $post_title,
          'postType' => 'jacana_destination',
          'field' => 'content',
        );
      }

      // Excerpt
      if ($post_excerpt !== '') {
        $excerpt_key = sprintf('post_%d.excerpt', $post_id);
        $items_by_key[$excerpt_key] = array(
          'key' => $excerpt_key,
          'source' => $post_excerpt,
          'postId' => $post_id,
          'postTitle' => $post_title,
          'postType' => 'jacana_destination',
          'field' => 'excerpt',
        );
      }

      // Meta
      foreach ($translatable_meta_keys as $meta_key) {
        $meta_val = get_post_meta($post_id, $meta_key, true);
        if (is_string($meta_val) && $meta_val !== '') {
          $key = sprintf('post_%d.meta.%s', $post_id, $meta_key);
          $items_by_key[$key] = array(
            'key' => $key,
            'source' => $meta_val,
            'postId' => $post_id,
            'postTitle' => $post_title,
            'postType' => 'jacana_destination',
            'metaKey' => $meta_key,
          );
        }
      }
    }

    // --- Legal Pages Export (Privacy Policy, Terms) ---
    $legal_slugs = array('privacy-policy', 'terms-and-conditions', 'terms-of-service', 'terms', 'privacy');
    $legal_slug_placeholders = implode(',', array_fill(0, count($legal_slugs), '%s'));

    $legal_pages = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT ID, post_title, post_content, post_excerpt, post_name, post_type, post_status
         FROM {$wpdb->posts}
         WHERE post_type = 'page'
           AND post_name IN ($legal_slug_placeholders)
           AND post_status NOT IN ('auto-draft', 'trash', 'inherit')
         ORDER BY ID ASC",
        ...$legal_slugs
      ),
      ARRAY_A
    );

    foreach ($legal_pages as $page) {
      $post_id      = (int)    $page['ID'];
      $post_title   = (string) $page['post_title'];
      $post_content = (string) $page['post_content'];
      $post_excerpt = (string) $page['post_excerpt'];
      $post_slug    = (string) $page['post_name'];

      $title_key = sprintf('post_%d.title', $post_id);
      $items_by_key[$title_key] = array(
        'key'        => $title_key,
        'source'     => $post_title,
        'postId'     => $post_id,
        'postTitle'  => $post_title,
        'postSlug'   => $post_slug,
        'postType'   => 'page',
        'postStatus' => (string) $page['post_status'],
        'field'      => 'title',
      );

      if ($post_content !== '') {
        $content_key = sprintf('post_%d.content', $post_id);
        $items_by_key[$content_key] = array(
          'key'        => $content_key,
          'source'     => $post_content,
          'postId'     => $post_id,
          'postTitle'  => $post_title,
          'postSlug'   => $post_slug,
          'postType'   => 'page',
          'postStatus' => (string) $page['post_status'],
          'field'      => 'content',
        );
      }

      if ($post_excerpt !== '') {
        $excerpt_key = sprintf('post_%d.excerpt', $post_id);
        $items_by_key[$excerpt_key] = array(
          'key'        => $excerpt_key,
          'source'     => $post_excerpt,
          'postId'     => $post_id,
          'postTitle'  => $post_title,
          'postSlug'   => $post_slug,
          'postType'   => 'page',
          'postStatus' => (string) $page['post_status'],
          'field'      => 'excerpt',
        );
      }
    }

    foreach ($this->catalog->get_items() as $item) {
      if (!is_array($item) || empty($item['key']) || empty($item['source'])) {
        continue;
      }

      $key = (string) $item['key'];
      $items_by_key[$key] = array(
        'key' => $key,
        'source' => (string) $item['source'],
        'origin' => 'catalog',
        'area' => (string) ($item['area'] ?? ''),
      );
    }

    ksort($items_by_key);

    $payload = array(
      'generatedAt' => gmdate('c'),
      'siteUrl' => home_url('/'),
      'counts' => array(
        'documents' => $post_count,
        'widgets' => $widget_count,
        'strings' => count($items_by_key),
      ),
      'items' => array_values($items_by_key),
    );

    $json = wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if (!is_string($json)) {
      return null;
    }

    wp_mkdir_p(dirname($this->output_file));
    file_put_contents($this->output_file, $json . PHP_EOL);

    return $payload;
  }

  public function read_export() {
    if (!file_exists($this->output_file)) {
      return array();
    }

    $raw = file_get_contents($this->output_file);
    $decoded = is_string($raw) ? json_decode($raw, true) : null;

    return is_array($decoded) ? $decoded : array();
  }
}
