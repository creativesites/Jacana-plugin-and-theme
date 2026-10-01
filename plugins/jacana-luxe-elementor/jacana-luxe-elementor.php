<?php
/**
 * Plugin Name: Jacana Luxe Elementor Widgets
 * Description: Custom Elementor widgets for the Jacana Luxe theme.
 * Version: 1.1.3
 * Author: Winston Zulu
 */

if (!defined('ABSPATH')) {
  exit;
}

final class Jacana_Luxe_Elementor {
  const MIN_ELEMENTOR_VERSION = '3.0.0';
  const CF7_BOOKING_META_KEY = '_jacana_cf7_booking_form_key';
  const CF7_BOOKING_MANAGED_KEY = '_jacana_cf7_managed';

  public function __construct() {
    add_action('init', array($this, 'register_safari_place_post_type'));
    add_action('init', array($this, 'register_destination_taxonomies'));
    add_action('init', array($this, 'register_destination_post_type'));
    add_action('init', array($this, 'maybe_seed_destinations'));
    add_action('init', array($this, 'maybe_sync_cf7_booking_forms'), 40);
    add_action('add_meta_boxes', array($this, 'register_safari_place_meta_box'));
    add_action('add_meta_boxes', array($this, 'register_destination_meta_boxes'));
    add_action('save_post_jacana_safari_place', array($this, 'save_safari_place_meta'));
    add_action('save_post_jacana_destination', array($this, 'save_destination_meta'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_destination_admin_scripts'));
    add_action('plugins_loaded', array($this, 'init'));
  }

  public function register_safari_place_post_type() {
    $labels = array(
      'name' => __('Safari Places', 'jacana-luxe'),
      'singular_name' => __('Safari Place', 'jacana-luxe'),
      'add_new' => __('Add Safari Place', 'jacana-luxe'),
      'add_new_item' => __('Add New Safari Place', 'jacana-luxe'),
      'edit_item' => __('Edit Safari Place', 'jacana-luxe'),
      'new_item' => __('New Safari Place', 'jacana-luxe'),
      'view_item' => __('View Safari Place', 'jacana-luxe'),
      'search_items' => __('Search Safari Places', 'jacana-luxe'),
      'not_found' => __('No safari places found.', 'jacana-luxe'),
      'not_found_in_trash' => __('No safari places found in Trash.', 'jacana-luxe'),
      'menu_name' => __('Safari Places', 'jacana-luxe'),
    );

    register_post_type('jacana_safari_place', array(
      'labels' => $labels,
      'public' => true,
      'show_ui' => true,
      'show_in_menu' => true,
      'show_in_rest' => true,
      'menu_position' => 27,
      'menu_icon' => 'dashicons-location-alt',
      'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'),
      'has_archive' => false,
      'rewrite' => array('slug' => 'safari-places'),
    ));
  }

  public function register_destination_taxonomies() {
    register_taxonomy('destination_region', array('jacana_destination'), array(
      'labels' => array(
        'name' => __('Regions', 'jacana-luxe'),
        'singular_name' => __('Region', 'jacana-luxe'),
      ),
      'hierarchical' => true,
      'show_in_rest' => true,
    ));

    register_taxonomy('destination_type', array('jacana_destination'), array(
      'labels' => array(
        'name' => __('Destination Types', 'jacana-luxe'),
        'singular_name' => __('Destination Type', 'jacana-luxe'),
      ),
      'hierarchical' => true,
      'show_in_rest' => true,
    ));

    register_taxonomy('activity_type', array('jacana_destination'), array(
      'labels' => array(
        'name' => __('Activity Types', 'jacana-luxe'),
        'singular_name' => __('Activity Type', 'jacana-luxe'),
      ),
      'hierarchical' => true,
      'show_in_rest' => true,
    ));

    register_taxonomy('service_fit', array('jacana_destination'), array(
      'labels' => array(
        'name' => __('Service Fits', 'jacana-luxe'),
        'singular_name' => __('Service Fit', 'jacana-luxe'),
      ),
      'hierarchical' => true,
      'show_in_rest' => true,
    ));
  }

  public function register_destination_post_type() {
    $labels = array(
      'name' => __('Destinations', 'jacana-luxe'),
      'singular_name' => __('Destination', 'jacana-luxe'),
      'add_new' => __('Add Destination', 'jacana-luxe'),
      'add_new_item' => __('Add New Destination', 'jacana-luxe'),
      'edit_item' => __('Edit Destination', 'jacana-luxe'),
      'new_item' => __('New Destination', 'jacana-luxe'),
      'view_item' => __('View Destination', 'jacana-luxe'),
      'search_items' => __('Search Destinations', 'jacana-luxe'),
      'not_found' => __('No destinations found.', 'jacana-luxe'),
      'menu_name' => __('Destinations', 'jacana-luxe'),
    );

    register_post_type('jacana_destination', array(
      'labels' => $labels,
      'public' => true,
      'show_ui' => true,
      'show_in_menu' => true,
      'show_in_rest' => true,
      'menu_position' => 26,
      'menu_icon' => 'dashicons-palmtree',
      'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'),
      'has_archive' => true,
      'rewrite' => array('slug' => 'explore'),
    ));

    // Force flush on next load
    if (get_option('jacana_destinations_flush_needed')) {
        flush_rewrite_rules();
        delete_option('jacana_destinations_flush_needed');
    }
  }

  public function register_safari_place_meta_box() {
    add_meta_box(
      'jacana_safari_place_map_meta',
      __('Map Hotspot Settings', 'jacana-luxe'),
      array($this, 'render_safari_place_meta_box'),
      'jacana_safari_place',
      'normal',
      'high'
    );
  }

  public function render_safari_place_meta_box($post) {
    wp_nonce_field('jacana_safari_place_meta', 'jacana_safari_place_meta_nonce');

    $x = get_post_meta($post->ID, '_jacana_map_x', true);
    $y = get_post_meta($post->ID, '_jacana_map_y', true);
    $teaser = get_post_meta($post->ID, '_jacana_map_teaser', true);
    $description = get_post_meta($post->ID, '_jacana_map_description', true);
    $video_url = get_post_meta($post->ID, '_jacana_map_video_url', true);
    ?>
    <p><?php echo esc_html__('These values are used by the Highlights Map widget when "Safari Places (posts)" is selected.', 'jacana-luxe'); ?></p>
    <table class="form-table" role="presentation">
      <tbody>
        <tr>
          <th scope="row"><label for="jacana_map_x"><?php echo esc_html__('Horizontal Position (%)', 'jacana-luxe'); ?></label></th>
          <td>
            <input type="number" min="0" max="100" step="0.1" id="jacana_map_x" name="jacana_map_x" value="<?php echo esc_attr($x); ?>" class="small-text">
            <p class="description"><?php echo esc_html__('Place the hotspot left-to-right on the map image.', 'jacana-luxe'); ?></p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="jacana_map_y"><?php echo esc_html__('Vertical Position (%)', 'jacana-luxe'); ?></label></th>
          <td>
            <input type="number" min="0" max="100" step="0.1" id="jacana_map_y" name="jacana_map_y" value="<?php echo esc_attr($y); ?>" class="small-text">
            <p class="description"><?php echo esc_html__('Place the hotspot top-to-bottom on the map image.', 'jacana-luxe'); ?></p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="jacana_map_teaser"><?php echo esc_html__('Map Teaser', 'jacana-luxe'); ?></label></th>
          <td>
            <input type="text" id="jacana_map_teaser" name="jacana_map_teaser" value="<?php echo esc_attr($teaser); ?>" class="regular-text">
            <p class="description"><?php echo esc_html__('Short line shown under the panel title. Falls back to excerpt if empty.', 'jacana-luxe'); ?></p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="jacana_map_description"><?php echo esc_html__('Map Panel Description', 'jacana-luxe'); ?></label></th>
          <td>
            <textarea id="jacana_map_description" name="jacana_map_description" rows="4" class="large-text"><?php echo esc_textarea($description); ?></textarea>
            <p class="description"><?php echo esc_html__('Optional custom summary for the map panel. Falls back to excerpt/content if empty.', 'jacana-luxe'); ?></p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="jacana_map_video_url"><?php echo esc_html__('Video URL (optional)', 'jacana-luxe'); ?></label></th>
          <td>
            <input type="url" id="jacana_map_video_url" name="jacana_map_video_url" value="<?php echo esc_attr($video_url); ?>" class="regular-text">
            <p class="description"><?php echo esc_html__('YouTube/Vimeo URL for the panel video. Featured image is still used as preview image.', 'jacana-luxe'); ?></p>
          </td>
        </tr>
      </tbody>
    </table>
    <p class="description">
      <?php echo esc_html__('Use the post Featured Image for the hotspot panel image. The post permalink will be used as the hotspot CTA link.', 'jacana-luxe'); ?>
    </p>
    <?php
  }

  public function save_safari_place_meta($post_id) {
    if (!isset($_POST['jacana_safari_place_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['jacana_safari_place_meta_nonce'])), 'jacana_safari_place_meta')) {
      return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }

    if (!current_user_can('edit_post', $post_id)) {
      return;
    }

    $numeric_fields = array(
      'jacana_map_x' => '_jacana_map_x',
      'jacana_map_y' => '_jacana_map_y',
    );

    foreach ($numeric_fields as $request_key => $meta_key) {
      if (!isset($_POST[$request_key]) || $_POST[$request_key] === '') {
        delete_post_meta($post_id, $meta_key);
        continue;
      }
      $value = (float) wp_unslash($_POST[$request_key]);
      $value = max(0, min(100, $value));
      update_post_meta($post_id, $meta_key, $value);
    }

    $text_fields = array(
      'jacana_map_teaser' => '_jacana_map_teaser',
    );

    foreach ($text_fields as $request_key => $meta_key) {
      $value = isset($_POST[$request_key]) ? sanitize_text_field(wp_unslash($_POST[$request_key])) : '';
      if ($value === '') {
        delete_post_meta($post_id, $meta_key);
      } else {
        update_post_meta($post_id, $meta_key, $value);
      }
    }

    $textarea_fields = array(
      'jacana_map_description' => '_jacana_map_description',
    );

    foreach ($textarea_fields as $request_key => $meta_key) {
      $value = isset($_POST[$request_key]) ? sanitize_textarea_field(wp_unslash($_POST[$request_key])) : '';
      if ($value === '') {
        delete_post_meta($post_id, $meta_key);
      } else {
        update_post_meta($post_id, $meta_key, $value);
      }
    }

    $video_url = isset($_POST['jacana_map_video_url']) ? esc_url_raw(wp_unslash($_POST['jacana_map_video_url'])) : '';
    if (empty($video_url)) {
      delete_post_meta($post_id, '_jacana_map_video_url');
    } else {
      update_post_meta($post_id, '_jacana_map_video_url', $video_url);
    }
  }

  public function enqueue_destination_admin_scripts($hook) {
    global $post;
    if (!in_array($hook, array('post.php', 'post-new.php'), true)) return;
    if (!$post || 'jacana_destination' !== $post->post_type) return;

    wp_enqueue_media();
    wp_add_inline_script('media-editor', <<<'JS'
(function ($) {
  var frame;

  $(document).on('click', '[data-gallery-add]', function () {
    var $field   = $(this).closest('[data-gallery-field]');
    var $input   = $field.find('input[type=hidden]');
    var $preview = $field.find('[data-gallery-preview]');
    var currentIds = $input.val() ? $input.val().split(',').map(Number).filter(Boolean) : [];

    frame = wp.media({
      title   : 'Select Gallery Images',
      button  : { text: 'Use these images' },
      multiple: true,
      library : { type: 'image' },
    });

    frame.on('open', function () {
      var sel = frame.state().get('selection');
      currentIds.forEach(function (id) { sel.add(wp.media.attachment(id)); });
    });

    frame.on('select', function () {
      var attachments = frame.state().get('selection').toArray();
      $input.val(attachments.map(function (a) { return a.id; }).join(','));
      $preview.empty();
      attachments.forEach(function (a) {
        var url = (a.attributes.sizes && a.attributes.sizes.thumbnail)
          ? a.attributes.sizes.thumbnail.url
          : a.attributes.url;
        $preview.append(
          '<div class="jacana-gf-thumb" data-id="' + a.id + '">' +
          '<img src="' + url + '" alt="">' +
          '<button type="button" class="jacana-gf-remove" data-remove="' + a.id +
          '" aria-label="Remove">&times;</button></div>'
        );
      });
    });

    frame.open();
  });

  $(document).on('click', '.jacana-gf-remove', function () {
    var $field = $(this).closest('[data-gallery-field]');
    var $input = $field.find('input[type=hidden]');
    var removeId = parseInt($(this).data('remove'), 10);
    var ids = $input.val() ? $input.val().split(',').map(Number).filter(Boolean) : [];
    $input.val(ids.filter(function (id) { return id !== removeId; }).join(','));
    $(this).closest('.jacana-gf-thumb').remove();
  });
}(jQuery));
JS);
  }

  public function register_destination_meta_boxes($post) {
    if (file_exists(__DIR__ . '/includes/fields-destination.php')) {
      require_once __DIR__ . '/includes/fields-destination.php';
      Jacana_Luxe_Destination_Fields::register_meta_boxes($post);
    }
  }

  public function save_destination_meta($post_id) {
    if (file_exists(__DIR__ . '/includes/fields-destination.php')) {
      require_once __DIR__ . '/includes/fields-destination.php';
      Jacana_Luxe_Destination_Fields::save_meta($post_id);
    }
  }

  public function maybe_seed_destinations() {
    if (!isset($_GET['jacana_seed']) || !current_user_can('manage_options')) {
      return;
    }

    $data_file = __DIR__ . '/includes/data-destinations.php';
    if (!file_exists($data_file)) {
      return;
    }

    require_once $data_file;
    $destinations = jacana_luxe_get_initial_destinations();

    foreach ($destinations as $dest) {
      $existing = get_page_by_title($dest['title'], OBJECT, 'jacana_destination');
      
      $post_data = array(
        'post_title'    => $dest['title'],
        'post_name'     => $dest['slug'],
        'post_content'  => (string) ($dest['overview'] ?? ''),
        'post_excerpt'  => (string) ($dest['summary'] ?? ''),
        'post_status'   => 'publish',
        'post_type'     => 'jacana_destination',
      );
      
      if ($existing) {
        $post_data['ID'] = $existing->ID;
        $post_id = wp_update_post($post_data);
      } else {
        $post_id = wp_insert_post($post_data);
      }
      
      if (is_wp_error($post_id)) {
        continue;
      }
      
      $meta = array(
        '_jacana_dest_subtitle' => $dest['subtitle'] ?? '',
        '_jacana_dest_summary' => $dest['summary'] ?? '',
        '_jacana_dest_why_visit' => $dest['why_visit'] ?? '',
        '_jacana_dest_gps' => $dest['gps'] ?? '',
        '_jacana_dest_drive_times' => $dest['drive_times'] ?? '',
        '_jacana_map_x' => $dest['map_x'] ?? 50,
        '_jacana_map_y' => $dest['map_y'] ?? 50,
        '_jacana_map_teaser' => $dest['map_teaser'] ?? '',
        '_jacana_map_description' => $dest['map_description'] ?? '',
        '_jacana_dest_best_months' => $dest['best_months'] ?? '',
        '_jacana_dest_climate' => $dest['climate'] ?? '',
        '_jacana_dest_roads' => $dest['roads'] ?? '',
        '_jacana_dest_family' => $dest['family'] ?? '',
        '_jacana_dest_access' => $dest['access'] ?? '',
        '_jacana_dest_activities_json' => $dest['activities'] ?? '',
        '_jacana_dest_accom_styles' => $dest['accom_styles'] ?? '',
        '_jacana_dest_typical_stay' => $dest['typical_stay'] ?? '',
        '_jacana_dest_nearby_bases' => $dest['nearby_bases'] ?? '',
        '_jacana_dest_booking_notes' => $dest['booking_notes'] ?? '',
        '_jacana_dest_trip_length' => $dest['trip_length'] ?? '',
        '_jacana_dest_combine_with' => $dest['combine_with'] ?? '',
        '_jacana_dest_route_pos' => $dest['route_pos'] ?? '',
        '_jacana_dest_transfers' => $dest['transfers'] ?? '',
        '_jacana_dest_permits' => $dest['permits'] ?? '',
        '_jacana_dest_wildlife' => $dest['wildlife'] ?? '',
        '_jacana_dest_culture' => $dest['culture'] ?? '',
        '_jacana_dest_safety' => $dest['safety'] ?? '',
        '_jacana_dest_faq_json' => $dest['faq'] ?? '',
        '_jacana_dest_cta' => $dest['cta'] ?? '',
        '_jacana_dest_lead_tags' => $dest['lead_tags'] ?? '',
      );
      
      foreach ($meta as $key => $val) {
        update_post_meta($post_id, $key, $val);
      }
      
      if (!empty($dest['taxonomies'])) {
        foreach ($dest['taxonomies'] as $tax => $terms) {
          wp_set_object_terms($post_id, $terms, $tax);
        }
      }
    }

    update_option('jacana_destinations_flush_needed', 1);
    echo '<div class="notice notice-success is-dismissible"><p>Destinations seeded successfully.</p></div>';
  }

  public function init() {
    if (!did_action('elementor/loaded')) {
      return;
    }

    if (!version_compare(ELEMENTOR_VERSION, self::MIN_ELEMENTOR_VERSION, '>=')) {
      return;
    }

    add_action('elementor/elements/categories_registered', array($this, 'register_category'));
    add_action('elementor/widgets/register', array($this, 'register_widgets'));
    add_action('wp_enqueue_scripts', array($this, 'register_widget_assets'), 5);
    add_action('wp_enqueue_scripts', array($this, 'enqueue_widget_assets_frontend'), 20);
    add_action('elementor/editor/before_enqueue_styles', array($this, 'enqueue_widget_assets_for_editor'));
    add_action('elementor/preview/enqueue_styles', array($this, 'enqueue_widget_assets_for_editor'));
    add_action('elementor/editor/before_enqueue_scripts', array($this, 'enqueue_widget_assets_for_editor'));
    // Cache invalidation — clears per-page widget transients and combined CSS on save.
    add_action('elementor/editor/after_save', array($this, 'on_elementor_page_save'), 10, 2);
    add_action('save_post', array($this, 'on_post_save'), 10, 1);
  }

  private function get_widget_styles_base_handle() {
    return 'jacana-luxe-widgets-base';
  }

  private function get_widget_styles_base_path() {
    return plugin_dir_path(__FILE__) . 'assets/css/widgets-base.css';
  }

  private function get_widget_styles_base_url() {
    return plugin_dir_url(__FILE__) . 'assets/css/widgets-base.css';
  }

  private function get_widget_styles_dir_path() {
    return plugin_dir_path(__FILE__) . 'assets/css/widgets/';
  }

  private function get_widget_styles_dir_url() {
    return plugin_dir_url(__FILE__) . 'assets/css/widgets/';
  }

  private function get_widget_style_handle_from_slug($slug) {
    return 'jacana-luxe-widget-' . sanitize_key((string) $slug);
  }

  private function get_widget_script_handle_from_slug($slug) {
    return 'jacana-luxe-widget-js-' . sanitize_key((string) $slug);
  }

  private function get_widget_scripts_dir_path() {
    return plugin_dir_path(__FILE__) . 'assets/js/widgets/';
  }

  private function get_widget_scripts_dir_url() {
    return plugin_dir_url(__FILE__) . 'assets/js/widgets/';
  }

  // ── Per-page smart CSS loading helpers ──────────────────────────────────────

  /**
   * Recursively walks Elementor's _elementor_data JSON and returns every
   * unique widgetType found on the page.
   */
  private function get_elementor_page_widget_types( int $post_id ): array {
    $raw = get_post_meta( $post_id, '_elementor_data', true );
    if ( empty( $raw ) ) return [];
    $data = json_decode( $raw, true );
    if ( ! is_array( $data ) ) return [];

    $types = [];
    $walk  = function ( array $elements ) use ( &$walk, &$types ) {
      foreach ( $elements as $el ) {
        if ( ! empty( $el['widgetType'] ) ) {
          $types[ $el['widgetType'] ] = true;
        }
        if ( ! empty( $el['elements'] ) ) {
          $walk( $el['elements'] );
        }
      }
    };
    $walk( $data );
    return array_keys( $types );
  }

  /**
   * Maps a widget type name (e.g. 'jacana_tours_hero') to its CSS file slug
   * (e.g. 'tours-hero') by stripping the 'jacana_' prefix and converting
   * remaining underscores to hyphens.
   */
  private function widget_type_to_css_slug( string $type ): string {
    return str_replace( '_', '-', preg_replace( '/^jacana_/', '', $type ) );
  }

  /**
   * Minifies a CSS string: strips comments, collapses whitespace, and removes
   * redundant semicolons. Achieves ~30-40% reduction with no semantic change.
   */
  private function minify_css( string $css ): string {
    // Strip block comments.
    $css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );
    // Collapse runs of whitespace (including newlines) to a single space.
    $css = preg_replace( '/\s+/', ' ', $css );
    // Remove spaces around structural punctuation.
    $css = preg_replace( '/\s*([\{\};:,>\+~])\s*/', '$1', $css );
    // Remove trailing semicolons before closing brace.
    $css = str_replace( ';}', '}', $css );
    return trim( $css );
  }

  /**
   * Generates (or retrieves from disk cache) a single combined + minified CSS
   * file containing only the widget styles needed for this page.
   *
   * Cache filename is a hash of the slug set + source file mtimes, so it
   * auto-invalidates whenever any source file is edited.
   *
   * Returns the public URL of the combined file, or null on failure.
   */
  private function get_page_combined_css( array $slugs ): ?string {
    $css_dir = $this->get_widget_styles_dir_path();

    // Resolve to existing files and record their mtimes for the hash.
    $files  = [];
    $mtimes = [];
    $sorted = $slugs;
    sort( $sorted );
    foreach ( $sorted as $slug ) {
      $path = $css_dir . $slug . '.css';
      if ( file_exists( $path ) ) {
        $files[ $slug ] = $path;
        $mtimes[]       = (string) filemtime( $path );
      }
    }
    if ( empty( $files ) ) return null;

    $hash       = substr( md5( implode( ',', array_keys( $files ) ) . '|' . implode( ',', $mtimes ) ), 0, 12 );
    $upload     = wp_upload_dir();
    $cache_dir  = $upload['basedir'] . '/jlw-css-cache/';
    $cache_file = $cache_dir . $hash . '.css';
    $cache_url  = $upload['baseurl'] . '/jlw-css-cache/' . $hash . '.css';

    if ( ! file_exists( $cache_file ) ) {
      if ( ! wp_mkdir_p( $cache_dir ) ) return null;

      // Purge stale cache files that exceed 30 to keep the directory lean.
      $existing = glob( $cache_dir . '*.css' ) ?: [];
      if ( count( $existing ) > 30 ) {
        array_map( function( $f ) { @unlink( $f ); }, $existing );
      }

      $combined = '';
      foreach ( $files as $slug => $path ) {
        $combined .= $this->minify_css( file_get_contents( $path ) );
      }
      if ( file_put_contents( $cache_file, $combined ) === false ) return null;
    }

    return $cache_url;
  }

  /**
   * Clears all combined CSS cache files and per-page widget-list transients.
   * Called on Elementor/post save and can be triggered externally.
   */
  public function clear_page_css_cache(): void {
    $upload    = wp_upload_dir();
    $cache_dir = $upload['basedir'] . '/jlw-css-cache/';
    if ( is_dir( $cache_dir ) ) {
      array_map( function( $f ) { @unlink( $f ); }, glob( $cache_dir . '*.css' ) ?: [] );
    }
    // Clear all per-page widget-list transients.
    global $wpdb;
    $wpdb->query(
      "DELETE FROM {$wpdb->options}
       WHERE option_name LIKE '_transient_jlw_widgets_%'
          OR option_name LIKE '_transient_timeout_jlw_widgets_%'"
    );
  }

  /** Elementor editor save — clear combined CSS and the saved page's transient. */
  public function on_elementor_page_save( int $post_id ): void {
    $this->clear_page_widget_transient( $post_id );
    $this->clear_page_css_cache();
  }

  /** Post save — clear just the transient for that post. */
  public function on_post_save( int $post_id ): void {
    $this->clear_page_widget_transient( $post_id );
  }

  /** Deletes the widget-list transient pair for a specific post. */
  private function clear_page_widget_transient( int $post_id ): void {
    global $wpdb;
    $wpdb->query( $wpdb->prepare(
      "DELETE FROM {$wpdb->options}
       WHERE option_name LIKE %s
          OR option_name LIKE %s",
      '_transient_jlw_widgets_' . $post_id . '_%',
      '_transient_timeout_jlw_widgets_' . $post_id . '_%'
    ) );
  }

  public function register_widget_assets() {
    $base_path = $this->get_widget_styles_base_path();
    if (file_exists($base_path)) {
      wp_register_style(
        $this->get_widget_styles_base_handle(),
        $this->get_widget_styles_base_url(),
        array(),
        (string) filemtime($base_path)
      );
    }

    // Register Individual Widget Styles
    $css_dir_path = $this->get_widget_styles_dir_path();
    if (is_dir($css_dir_path)) {
      $css_files = glob($css_dir_path . '*.css');
      if (!empty($css_files)) {
        foreach ($css_files as $file_path) {
          $slug = basename($file_path, '.css');
          $handle = $this->get_widget_style_handle_from_slug($slug);
          $src = $this->get_widget_styles_dir_url() . $slug . '.css';
          wp_register_style(
            $handle,
            $src,
            array($this->get_widget_styles_base_handle()),
            (string) filemtime($file_path)
          );
        }
      }
    }

    // Register Scripts
    $js_dir_path = $this->get_widget_scripts_dir_path();
    if (is_dir($js_dir_path)) {
      $js_files = glob($js_dir_path . '*.js');
      if (!empty($js_files)) {
        foreach ($js_files as $file_path) {
          $slug = basename($file_path, '.js');
          $handle = $this->get_widget_script_handle_from_slug($slug);
          $src = $this->get_widget_scripts_dir_url() . $slug . '.js';
          wp_register_script($handle, $src, array('jquery'), (string) filemtime($file_path), true);
        }
      }
    }
  }

  private function enqueue_all_widget_assets() {
    $this->register_widget_assets();

    if (wp_style_is($this->get_widget_styles_base_handle(), 'registered')) {
      wp_enqueue_style($this->get_widget_styles_base_handle());
    }

    $css_dir_path = $this->get_widget_styles_dir_path();
    if (is_dir($css_dir_path)) {
      $files = glob($css_dir_path . '*.css');
      if (!empty($files)) {
        foreach ($files as $file_path) {
          $slug = basename($file_path, '.css');
          $handle = $this->get_widget_style_handle_from_slug($slug);
          if (wp_style_is($handle, 'registered')) {
            wp_enqueue_style($handle);
          }
        }
      }
    }

    $js_dir_path = $this->get_widget_scripts_dir_path();
    if (is_dir($js_dir_path)) {
      $files = glob($js_dir_path . '*.js');
      if (!empty($files)) {
        foreach ($files as $file_path) {
          $slug = basename($file_path, '.js');
          $handle = $this->get_widget_script_handle_from_slug($slug);
          if (wp_script_is($handle, 'registered')) {
            wp_enqueue_script($handle);
          }
        }
      }
    }
  }

  public function enqueue_widget_assets_frontend() {
    if ( is_admin() ) return;

    // Always enqueue the base (shared) stylesheet.
    if ( wp_style_is( $this->get_widget_styles_base_handle(), 'registered' ) ) {
      wp_enqueue_style( $this->get_widget_styles_base_handle() );
    }

    // Always enqueue widget JS files (small, individually registered).
    $js_dir = $this->get_widget_scripts_dir_path();
    if ( is_dir( $js_dir ) ) {
      foreach ( glob( $js_dir . '*.js' ) ?: [] as $file ) {
        $h = $this->get_widget_script_handle_from_slug( basename( $file, '.js' ) );
        if ( wp_script_is( $h, 'registered' ) ) wp_enqueue_script( $h );
      }
    }

    // Determine which page we are on.
    $post_id = get_queried_object_id();
    if ( ! $post_id ) {
      $this->enqueue_all_widget_assets();
      return;
    }

    // Cache widget types per post, keyed by modified time so it auto-busts on save.
    $modified     = (int) get_post_modified_time( 'U', false, $post_id );
    $cache_key    = 'jlw_widgets_' . $post_id . '_' . $modified;
    $widget_types = get_transient( $cache_key );
    if ( false === $widget_types ) {
      $widget_types = $this->get_elementor_page_widget_types( $post_id );
      set_transient( $cache_key, $widget_types, WEEK_IN_SECONDS );
    }

    // No Elementor data found — fall back to loading everything.
    if ( empty( $widget_types ) ) {
      $this->enqueue_all_widget_assets();
      return;
    }

    // Build the list of CSS slugs required by this page.
    $css_dir = $this->get_widget_styles_dir_path();
    $slugs   = [ '_a11y', 'site-footer' ]; // always-on stylesheets
    foreach ( $widget_types as $type ) {
      if ( strpos( $type, 'jacana_' ) !== 0 ) continue;
      $slug = $this->widget_type_to_css_slug( $type );
      if ( file_exists( $css_dir . $slug . '.css' ) ) {
        $slugs[] = $slug;
      }
    }
    $slugs = array_unique( $slugs );

    // Try to serve a single combined+minified CSS file for this page.
    $combined_url = $this->get_page_combined_css( $slugs );
    if ( $combined_url ) {
      wp_enqueue_style( 'jlw-widgets-page', $combined_url, [ $this->get_widget_styles_base_handle() ], null );
    } else {
      // Cache write failed — fall back to enqueueing individual files.
      foreach ( $slugs as $slug ) {
        $h = $this->get_widget_style_handle_from_slug( $slug );
        if ( wp_style_is( $h, 'registered' ) ) wp_enqueue_style( $h );
      }
    }
  }

  public function enqueue_widget_assets_for_editor() {
    $this->enqueue_all_widget_assets();
  }

  public function maybe_sync_cf7_booking_forms($force = false) {
    if (!is_admin() && !$force) {
      return;
    }

    if (!post_type_exists('wpcf7_contact_form')) {
      return;
    }

    $current_hash = md5(serialize(self::get_cf7_booking_form_definitions()));
    $saved_hash   = get_option('jacana_cf7_booking_forms_hash', '');
    if (!$force && $saved_hash === $current_hash) {
      return;
    }

    foreach (self::get_cf7_booking_form_definitions() as $key => $definition) {
      $this->ensure_cf7_booking_form($key, $definition);
    }

    update_option('jacana_cf7_booking_forms_hash', $current_hash, false);
  }

  public static function get_cf7_booking_form_choices() {
    return array(
      'booking_unified' => __('Unified Booking Request', 'jacana-luxe'),
      'booking_tours' => __('Tailor-made Tour Request', 'jacana-luxe'),
      'booking_car_rental' => __('Car Rental Quote Request', 'jacana-luxe'),
      'booking_transfer' => __('Airport Transfer / Shuttle Request', 'jacana-luxe'),
      'booking_game_drive' => __('Game Drive Request', 'jacana-luxe'),
      'booking_accommodation_flights' => __('Accommodation + Flights Request', 'jacana-luxe'),
      'booking_multi_service' => __('Multi-Service Itinerary Request', 'jacana-luxe'),
    );
  }

  public static function get_cf7_booking_shortcode($key = 'booking_unified') {
    if (!post_type_exists('wpcf7_contact_form')) {
      return '';
    }

    $post = self::find_cf7_booking_form_post($key);
    if (!$post) {
      return '';
    }

    return sprintf('[contact-form-7 id="%d"]', (int) $post->ID);
  }

  private static function get_cf7_booking_form_definitions() {
    $admin_email = get_option('admin_email');
    $domain = wp_parse_url(home_url(), PHP_URL_HOST);
    if (empty($domain)) {
      $domain = 'example.com';
    }

    $base_mail = array(
      'active' => true,
      'recipient' => $admin_email,
      'sender' => 'Jacana Website <wordpress@' . $domain . '>',
      'subject' => '[Jacana Booking] [service_interest] - [full_name]',
      'body' => "New booking request submitted\n\nRequest Type: [service_interest]\nServices Needed: [services_needed]\nName: [full_name]\nEmail: [your_email]\nPhone / WhatsApp: [phone_whatsapp]\nTravel Dates: [travel_dates]\nTravelers: [travelers]\nRoute / Destinations: [route_destinations]\nArrival / Flight Details: [arrival_details]\nVehicle / Transfer / Tour Notes: [service_notes]\nBudget / Style: [budget_style]\nMessage:\n[your_message]\n\nSource Page: [source_page]\nSource URL: [source_url]",
      'additional_headers' => 'Reply-To: [full_name] <[your_email]>',
      'attachments' => '',
      'use_html' => false,
      'exclude_blank' => false,
    );

    $mail_2 = array(
      'active' => true,
      'recipient' => '[your_email]',
      'sender' => 'Jacana Safaris & Tours <booking@jacanasafaristours.com>',
      'subject' => 'We received your booking request',
      'body' => "Hello [full_name],\n\nThank you for your booking request. We have received your inquiry for [service_interest]. Our team will review your details and reply with the next steps and a tailored, non-binding proposal.\n\nTo speed things up, simply reply to this email if you would like to add any extra details.\n\nRegards,\nJacana Safaris & Tours",
      'additional_headers' => '',
      'attachments' => '',
      'use_html' => false,
      'exclude_blank' => false,
    );

    $messages = array(
      'mail_sent_ok' => __('Thank you. Your booking request is in. We will review it and get back to you shortly with the next steps.', 'jacana-luxe'),
      'mail_sent_ng' => __('There was an error trying to send your request. Please try again later.', 'jacana-luxe'),
      'validation_error' => __('One or more fields have an error. Please check and try again.', 'jacana-luxe'),
      'spam' => __('There was an error trying to send your request. Please try again later.', 'jacana-luxe'),
      'accept_terms' => __('Please accept the terms to proceed.', 'jacana-luxe'),
      'invalid_required' => __('Please fill in this field.', 'jacana-luxe'),
    );

    $base_form = implode("\n", array(
      '<label>Full Name',
      '  [text* full_name autocomplete:name placeholder "Your full name"]',
      '</label>',
      '',
      '<label>Email Address',
      '  [email* your_email autocomplete:email placeholder "name@example.com"]',
      '</label>',
      '',
      '<label>Phone / WhatsApp (recommended)',
      '  [tel phone_whatsapp autocomplete:tel placeholder "+264 / +49 / WhatsApp number"]',
      '</label>',
      '',
      '<label>Main Service',
      '  [select* service_interest "Tailor-made Tour" "Car Rental" "Airport Transfer / Shuttle" "Game Drive" "Accommodation + Flights" "Multi-Service Itinerary"]',
      '</label>',
      '',
      '<label>Additional Services Needed (optional)',
      '  [checkbox services_needed use_label_element "Accommodation" "Flights" "Car Rental" "Transfer" "Game Drive" "Guided Tour" "Self-drive Tour"]',
      '</label>',
      '',
      '<label>Travel Dates / Date Range',
      '  [text travel_dates placeholder "e.g. 12-20 Aug 2026"]',
      '</label>',
      '',
      '<label>Number of Travelers',
      '  [text travelers placeholder "Example: 2 adults, 1 child"]',
      '</label>',
      '',
      '<label>Route / Destinations',
      '  [text route_destinations placeholder "Windhoek, Sossusvlei, Swakopmund, Etosha..."]',
      '</label>',
      '',
      '<label>Arrival / Flight Details',
      '  [text arrival_details placeholder "Airport, airline, flight number, arrival time"]',
      '</label>',
      '',
      '<label>Vehicle / Transfer / Tour Notes',
      '  [text service_notes placeholder "Vehicle type, pickup/drop-off, game drive preference, etc."]',
      '</label>',
      '',
      '<label>Budget / Travel Style (optional)',
      '  [text budget_style placeholder "Comfort level, budget range, lodge/camping preference"]',
      '</label>',
      '',
      '<label>Your Message',
      '  [textarea* your_message placeholder "Tell us what you want to book, any must-see destinations, and any special requests."]',
      '</label>',
      '',
      '[hidden source_page]',
      '[hidden source_url]',
      '',
      '[submit "Send Booking Request"]',
    ));

    $make_form = function ($title, $service, $subject_prefix, $button_label, $extra_notes = '') use ($base_form, $base_mail, $mail_2, $messages) {
      $form = $base_form;

      if (!empty($service)) {
        $form = str_replace(
          '[select* service_interest "Tailor-made Tour" "Car Rental" "Airport Transfer / Shuttle" "Game Drive" "Accommodation + Flights" "Multi-Service Itinerary"]',
          '[text* service_interest readonly default:"' . esc_attr($service) . '"]',
          $form
        );
      }

      if (!empty($extra_notes)) {
        $form = str_replace(
          '<label>Vehicle / Transfer / Tour Notes' . "\n" . '  [text service_notes placeholder "Vehicle type, pickup/drop-off, game drive preference, etc."]' . "\n" . '</label>',
          '<label>Vehicle / Transfer / Tour Notes' . "\n" . '  [text service_notes placeholder "' . esc_attr($extra_notes) . '"]' . "\n" . '</label>',
          $form
        );
      }

      $form = preg_replace('/\\[submit \"[^\"]+\"\\]/', '[submit "' . esc_attr($button_label) . '"]', $form, 1);

      $mail = $base_mail;
      $mail['subject'] = '[Jacana ' . $subject_prefix . '] [full_name]';

      return array(
        'title' => $title,
        'form' => $form,
        'mail' => $mail,
        'mail_2' => $mail_2,
        'messages' => $messages,
        'additional_settings' => "subscribers_only: false\nskip_mail: false",
      );
    };

    return array(
      'booking_unified' => $make_form(__('Jacana Booking Request (Unified)', 'jacana-luxe'), '', 'Booking Request', __('Send Booking Request', 'jacana-luxe')),
      'booking_tours' => $make_form(__('Jacana Booking Request (Tours)', 'jacana-luxe'), 'Tailor-made Tour', 'Tour Request', __('Send Tour Request', 'jacana-luxe'), 'Guided or self-drive, route highlights, accommodation style'),
      'booking_car_rental' => $make_form(__('Jacana Booking Request (Car Rental)', 'jacana-luxe'), 'Car Rental', 'Car Rental Quote', __('Send Car Rental Request', 'jacana-luxe'), 'Vehicle type, route conditions, pickup/drop-off and extras'),
      'booking_transfer' => $make_form(__('Jacana Booking Request (Transfer)', 'jacana-luxe'), 'Airport Transfer / Shuttle', 'Transfer Request', __('Send Transfer Request', 'jacana-luxe'), 'Airport, pickup time, destination and return transfer details'),
      'booking_game_drive' => $make_form(__('Jacana Booking Request (Game Drive)', 'jacana-luxe'), 'Game Drive', 'Game Drive Request', __('Send Game Drive Request', 'jacana-luxe'), 'Preferred date, Etosha location, morning/afternoon, guests'),
      'booking_accommodation_flights' => $make_form(__('Jacana Booking Request (Accommodation & Flights)', 'jacana-luxe'), 'Accommodation + Flights', 'Accommodation & Flights', __('Send Accommodation/Flight Request', 'jacana-luxe'), 'Hotels/lodges/campsites, domestic/international flights, class'),
      'booking_multi_service' => $make_form(__('Jacana Booking Request (Multi-Service)', 'jacana-luxe'), 'Multi-Service Itinerary', 'Multi-Service Request', __('Send Multi-Service Request', 'jacana-luxe'), 'List all services needed in one combined itinerary'),
    );
  }

  private static function find_cf7_booking_form_post($key) {
    $query = new WP_Query(array(
      'post_type' => 'wpcf7_contact_form',
      'post_status' => 'any',
      'posts_per_page' => 1,
      'no_found_rows' => true,
      'fields' => 'all',
      'meta_query' => array(
        array(
          'key' => self::CF7_BOOKING_META_KEY,
          'value' => (string) $key,
        ),
      ),
    ));

    if (!empty($query->posts)) {
      return $query->posts[0];
    }

    return null;
  }

  private function ensure_cf7_booking_form($key, $definition) {
    $existing = self::find_cf7_booking_form_post($key);
    if ($existing && (int) $existing->ID > 0) {
      $managed = (int) get_post_meta($existing->ID, self::CF7_BOOKING_MANAGED_KEY, true);
      if ($managed === 1) {
        $expected_title   = sanitize_text_field($definition['title'] ?? (string) $key);
        $expected_content = (string) ($definition['form'] ?? '');
        if ($existing->post_title !== $expected_title || $existing->post_content !== $expected_content) {
          wp_update_post(array(
            'ID' => (int) $existing->ID,
            'post_title' => $expected_title,
            'post_content' => $expected_content,
          ));
          update_post_meta($existing->ID, '_form', $expected_content);
          update_post_meta($existing->ID, '_mail', (array) ($definition['mail'] ?? array()));
          update_post_meta($existing->ID, '_mail_2', (array) ($definition['mail_2'] ?? array()));
          update_post_meta($existing->ID, '_messages', (array) ($definition['messages'] ?? array()));
          update_post_meta($existing->ID, '_additional_settings', (string) ($definition['additional_settings'] ?? ''));
        }
      }
      return (int) $existing->ID;
    }

    $post_id = wp_insert_post(array(
      'post_type' => 'wpcf7_contact_form',
      'post_status' => 'publish',
      'post_title' => sanitize_text_field($definition['title'] ?? (string) $key),
      'post_content' => (string) ($definition['form'] ?? ''),
    ), true);

    if (is_wp_error($post_id) || empty($post_id)) {
      return 0;
    }

    update_post_meta($post_id, self::CF7_BOOKING_META_KEY, (string) $key);
    update_post_meta($post_id, self::CF7_BOOKING_MANAGED_KEY, 1);
    update_post_meta($post_id, '_form', (string) ($definition['form'] ?? ''));
    update_post_meta($post_id, '_mail', (array) ($definition['mail'] ?? array()));
    update_post_meta($post_id, '_mail_2', (array) ($definition['mail_2'] ?? array()));
    update_post_meta($post_id, '_messages', (array) ($definition['messages'] ?? array()));
    update_post_meta($post_id, '_additional_settings', (string) ($definition['additional_settings'] ?? ''));
    update_post_meta($post_id, '_locale', determine_locale());

    return (int) $post_id;
  }

  public function register_category($elements_manager) {
    $elements_manager->add_category(
      'jacana-luxe',
      array(
        'title' => __('Jacana Luxe', 'jacana-luxe'),
        'icon' => 'fa fa-map',
      )
    );
  }

  private function load_widgets() {
    require_once __DIR__ . '/includes/widgets/class-hero-slider.php';
    require_once __DIR__ . '/includes/widgets/class-adventure-cards.php';
    require_once __DIR__ . '/includes/widgets/class-about-split.php';
    require_once __DIR__ . '/includes/widgets/class-experience-cards.php';
    require_once __DIR__ . '/includes/widgets/class-about-hero.php';
    require_once __DIR__ . '/includes/widgets/class-mission-vision.php';
    require_once __DIR__ . '/includes/widgets/class-team-profiles.php';
    require_once __DIR__ . '/includes/widgets/class-expos.php';
    require_once __DIR__ . '/includes/widgets/class-affiliations.php';
    require_once __DIR__ . '/includes/widgets/class-tailor-made-story.php';
    require_once __DIR__ . '/includes/widgets/class-tours-hero.php';
    require_once __DIR__ . '/includes/widgets/class-tours-process.php';
    require_once __DIR__ . '/includes/widgets/class-tours-inclusions.php';
    require_once __DIR__ . '/includes/widgets/class-destinations-list.php';
    require_once __DIR__ . '/includes/widgets/class-tours-gallery.php';
    require_once __DIR__ . '/includes/widgets/class-tours-dates-price.php';
    require_once __DIR__ . '/includes/widgets/class-kick-it-namibia.php';
    require_once __DIR__ . '/includes/widgets/class-safaris-hero.php';
    require_once __DIR__ . '/includes/widgets/class-safaris-park-facts.php';
    require_once __DIR__ . '/includes/widgets/class-shuttle-services.php';
    require_once __DIR__ . '/includes/widgets/class-car-rental-hero.php';
    require_once __DIR__ . '/includes/widgets/class-car-rental-offers.php';
    require_once __DIR__ . '/includes/widgets/class-car-rental-cta.php';
    require_once __DIR__ . '/includes/widgets/class-car-rental-premium-hero.php';
    require_once __DIR__ . '/includes/widgets/class-car-rental-fleet-explorer.php';
    require_once __DIR__ . '/includes/widgets/class-car-rental-showcase.php';
    require_once __DIR__ . '/includes/widgets/class-car-rental-road-reel.php';
    require_once __DIR__ . '/includes/widgets/class-gallery-hero.php';
    require_once __DIR__ . '/includes/widgets/class-gallery-grid.php';
    require_once __DIR__ . '/includes/widgets/class-featured-attractions.php';
    require_once __DIR__ . '/includes/widgets/class-contact-hero.php';
    require_once __DIR__ . '/includes/widgets/class-contact-map.php';
    require_once __DIR__ . '/includes/widgets/class-contact-info-form.php';
    require_once __DIR__ . '/includes/widgets/class-tours-overview.php';
    require_once __DIR__ . '/includes/widgets/class-destinations-gallery.php';
    require_once __DIR__ . '/includes/widgets/class-accommodation-styles.php';
    require_once __DIR__ . '/includes/widgets/class-safari-experience.php';
    require_once __DIR__ . '/includes/widgets/class-cta-banner.php';
    require_once __DIR__ . '/includes/widgets/class-services-hero.php';
    require_once __DIR__ . '/includes/widgets/class-services-hub.php';
    require_once __DIR__ . '/includes/widgets/class-main-services-grid.php';
    require_once __DIR__ . '/includes/widgets/class-highlights-map.php';
    require_once __DIR__ . '/includes/widgets/class-destination-explorer.php';
    require_once __DIR__ . '/includes/widgets/class-faq-hero.php';
    require_once __DIR__ . '/includes/widgets/class-faq-downloads.php';
    require_once __DIR__ . '/includes/widgets/class-downloads-library.php';
    require_once __DIR__ . '/includes/widgets/class-reviews-page.php';
    require_once __DIR__ . '/includes/widgets/class-social-proof.php';
    require_once __DIR__ . '/includes/widgets/class-booking-premium-hero.php';
    require_once __DIR__ . '/includes/widgets/class-booking-request-studio.php';
    require_once __DIR__ . '/includes/widgets/class-booking-form.php';
    require_once __DIR__ . '/includes/widgets/class-booking-process-timeline.php';
    require_once __DIR__ . '/includes/widgets/class-tour-itineraries.php';
    require_once __DIR__ . '/includes/widgets/class-home-hero.php';
    require_once __DIR__ . '/includes/widgets/class-youtube-hero-background.php';
    require_once __DIR__ . '/includes/widgets/class-destinations-strip.php';
    require_once __DIR__ . '/includes/widgets/class-home-journey.php';
    require_once __DIR__ . '/includes/widgets/class-zoora-home-hero.php';
    // require_once __DIR__ . '/includes/widgets/class-site-footer.php';
    require_once __DIR__ . '/includes/class-weather-service.php';
    require_once __DIR__ . '/includes/widgets/class-weather-grid.php';
    require_once __DIR__ . '/includes/widgets/class-weather-badge.php';
    require_once __DIR__ . '/includes/widgets/class-windhoek-weather.php';
  }

  public function register_widgets($widgets_manager) {
    $this->load_widgets();
    if (method_exists($widgets_manager, 'register')) {
      $widgets_manager->register(new Jacana_Luxe_Hero_Slider());
      $widgets_manager->register(new Jacana_Luxe_Adventure_Cards());
      $widgets_manager->register(new Jacana_Luxe_About_Split());
      $widgets_manager->register(new Jacana_Luxe_Experience_Cards());
      $widgets_manager->register(new Jacana_Luxe_About_Hero());
      $widgets_manager->register(new Jacana_Luxe_Mission_Vision());
      $widgets_manager->register(new Jacana_Luxe_Team_Profiles());
      $widgets_manager->register(new Jacana_Luxe_Expos());
      $widgets_manager->register(new Jacana_Luxe_Affiliations());
      $widgets_manager->register(new Jacana_Luxe_Tailor_Made_Story());
      $widgets_manager->register(new Jacana_Luxe_Tours_Hero());
      $widgets_manager->register(new Jacana_Luxe_Tours_Process());
      $widgets_manager->register(new Jacana_Luxe_Tours_Inclusions());
      $widgets_manager->register(new Jacana_Luxe_Destinations_List());
      $widgets_manager->register(new Jacana_Luxe_Tours_Gallery());
      $widgets_manager->register(new Jacana_Luxe_Tours_Dates_Price());
      $widgets_manager->register(new Jacana_Luxe_Kick_It_Namibia());
      $widgets_manager->register(new Jacana_Luxe_Safaris_Hero());
      $widgets_manager->register(new Jacana_Luxe_Safaris_Park_Facts());
      $widgets_manager->register(new Jacana_Luxe_Shuttle_Services());
      $widgets_manager->register(new Jacana_Luxe_Car_Rental_Hero());
      $widgets_manager->register(new Jacana_Luxe_Car_Rental_Offers());
      $widgets_manager->register(new Jacana_Luxe_Car_Rental_Cta());
      $widgets_manager->register(new Jacana_Luxe_Car_Rental_Premium_Hero());
      $widgets_manager->register(new Jacana_Luxe_Car_Rental_Fleet_Explorer());
      $widgets_manager->register(new Jacana_Luxe_Car_Rental_Showcase());
      $widgets_manager->register(new Jacana_Luxe_Car_Rental_Road_Reel());
      $widgets_manager->register(new Jacana_Luxe_Gallery_Hero());
      $widgets_manager->register(new Jacana_Luxe_Gallery_Grid());
      $widgets_manager->register(new Jacana_Luxe_Featured_Attractions());
      $widgets_manager->register(new Jacana_Luxe_Contact_Hero());
      $widgets_manager->register(new Jacana_Luxe_Contact_Map());
      $widgets_manager->register(new Jacana_Luxe_Contact_Info_Form());
      $widgets_manager->register(new Jacana_Luxe_Tours_Overview());
      $widgets_manager->register(new Jacana_Luxe_Destinations_Gallery());
      $widgets_manager->register(new Jacana_Luxe_Accommodation_Styles());
      $widgets_manager->register(new Jacana_Luxe_Safari_Experience());
      $widgets_manager->register(new Jacana_Luxe_Cta_Banner());
      $widgets_manager->register(new Jacana_Luxe_Services_Hero());
      $widgets_manager->register(new Jacana_Luxe_Services_Hub());
      $widgets_manager->register(new Jacana_Luxe_Main_Services_Grid());
      $widgets_manager->register(new Jacana_Luxe_Highlights_Map());
      $widgets_manager->register(new Jacana_Luxe_Destination_Explorer());
      $widgets_manager->register(new Jacana_Luxe_Faq_Hero());
      $widgets_manager->register(new Jacana_Luxe_Faq_Downloads());
      $widgets_manager->register(new Jacana_Luxe_Downloads_Library());
      $widgets_manager->register(new Jacana_Luxe_Reviews_Page());
      $widgets_manager->register(new Jacana_Luxe_Social_Proof());
      $widgets_manager->register(new Jacana_Luxe_Booking_Premium_Hero());
      $widgets_manager->register(new Jacana_Luxe_Booking_Request_Studio());
      $widgets_manager->register(new Jacana_Luxe_Booking_Form());
      $widgets_manager->register(new Jacana_Luxe_Booking_Process_Timeline());
      $widgets_manager->register(new Jacana_Luxe_Tour_Itineraries());
      $widgets_manager->register(new Jacana_Luxe_Home_Hero());
      $widgets_manager->register(new Jacana_Luxe_Youtube_Hero_Background());
      $widgets_manager->register(new Jacana_Luxe_Destinations_Strip());
      $widgets_manager->register(new Jacana_Luxe_Home_Journey());
      $widgets_manager->register(new Jacana_Luxe_Zoora_Home_Hero());
      // $widgets_manager->register(new Jacana_Luxe_Site_Footer());
      $widgets_manager->register(new Jacana_Luxe_Weather_Grid());
      $widgets_manager->register(new Jacana_Luxe_Weather_Badge());
      $widgets_manager->register(new Jacana_Luxe_Windhoek_Weather());
    }
  }

  public function register_widgets_legacy() {
    if (!class_exists('\\Elementor\\Plugin')) {
      return;
    }
    $this->load_widgets();
    $widgets_manager = \Elementor\Plugin::$instance->widgets_manager;
    if (method_exists($widgets_manager, 'register_widget_type')) {
      $widgets_manager->register_widget_type(new Jacana_Luxe_Hero_Slider());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Adventure_Cards());
      $widgets_manager->register_widget_type(new Jacana_Luxe_About_Split());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Experience_Cards());
      $widgets_manager->register_widget_type(new Jacana_Luxe_About_Hero());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Mission_Vision());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Team_Profiles());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Expos());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Affiliations());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Tailor_Made_Story());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Tours_Hero());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Tours_Process());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Tours_Inclusions());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Destinations_List());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Tours_Gallery());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Tours_Dates_Price());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Kick_It_Namibia());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Safaris_Hero());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Safaris_Park_Facts());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Shuttle_Services());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Car_Rental_Hero());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Car_Rental_Offers());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Car_Rental_Cta());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Car_Rental_Premium_Hero());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Car_Rental_Fleet_Explorer());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Car_Rental_Showcase());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Car_Rental_Road_Reel());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Gallery_Hero());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Gallery_Grid());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Featured_Attractions());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Contact_Hero());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Contact_Map());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Contact_Info_Form());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Tours_Overview());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Destinations_Gallery());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Accommodation_Styles());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Safari_Experience());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Cta_Banner());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Services_Hero());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Services_Hub());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Main_Services_Grid());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Highlights_Map());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Destination_Explorer());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Faq_Hero());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Faq_Downloads());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Downloads_Library());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Reviews_Page());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Social_Proof());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Booking_Premium_Hero());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Booking_Request_Studio());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Booking_Form());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Booking_Process_Timeline());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Tour_Itineraries());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Home_Hero());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Youtube_Hero_Background());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Destinations_Strip());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Home_Journey());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Zoora_Home_Hero());
      // $widgets_manager->register_widget_type(new Jacana_Luxe_Site_Footer());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Weather_Grid());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Weather_Badge());
      $widgets_manager->register_widget_type(new Jacana_Luxe_Windhoek_Weather());
    }
  }
}

new Jacana_Luxe_Elementor();
