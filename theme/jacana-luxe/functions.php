<?php
if (!defined('ABSPATH')) {
  exit;
}

function jacana_luxe_setup() {
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('elementor');
  add_theme_support('elementor-header-footer');
  add_theme_support('custom-logo', array(
    'height' => 80,
    'width' => 240,
    'flex-height' => true,
    'flex-width' => true,
  ));
  register_nav_menus(array(
    'primary' => __('Primary Menu', 'jacana-luxe'),
    'footer' => __('Footer Menu', 'jacana-luxe'),
  ));
}
add_action('after_setup_theme', 'jacana_luxe_setup');

function jacana_luxe_elementor_support() {
  return true;
}
add_filter('elementor_theme_builder_is_theme_supported', 'jacana_luxe_elementor_support');

function jacana_luxe_rest_endpoint($path) {
  $route = '/' . ltrim((string) $path, '/');
  return esc_url_raw(add_query_arg('rest_route', $route, home_url('/index.php')));
}

function jacana_luxe_assets() {
  $theme_dir = get_template_directory();
  wp_enqueue_style('jacana-luxe-fonts', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Sora:wght@300;400;500;600&display=swap', array(), null);
  wp_enqueue_style('jacana-luxe-style', get_stylesheet_uri(), array('jacana-luxe-fonts'), file_exists(get_stylesheet_directory() . '/style.css') ? (string) filemtime(get_stylesheet_directory() . '/style.css') : '1.0.0');
  wp_enqueue_style('jacana-luxe-programmatic-header', get_template_directory_uri() . '/assets/css/programmatic-header.css', array('jacana-luxe-style'), file_exists($theme_dir . '/assets/css/programmatic-header.css') ? (string) filemtime($theme_dir . '/assets/css/programmatic-header.css') : '1.0.0');
  wp_enqueue_style('jacana-luxe-ai-experience', get_template_directory_uri() . '/assets/css/jacana-ai-experience.css', array('jacana-luxe-style'), file_exists($theme_dir . '/assets/css/jacana-ai-experience.css') ? (string) filemtime($theme_dir . '/assets/css/jacana-ai-experience.css') : '1.0.0');
  wp_enqueue_style('jacana-reviews-page', get_template_directory_uri() . '/assets/css/widgets/reviews-page.css', array('jacana-luxe-style'), file_exists($theme_dir . '/assets/css/widgets/reviews-page.css') ? (string) filemtime($theme_dir . '/assets/css/widgets/reviews-page.css') : '1.0.0');
  
  if (is_post_type_archive('jacana_destination') || is_singular('jacana_destination') || is_page('explore') || is_tax(array('destination_region', 'destination_type', 'activity_type', 'service_fit'))) {
    wp_enqueue_style('jacana-luxe-destinations', get_template_directory_uri() . '/assets/css/destinations.css', array('jacana-luxe-style'), file_exists($theme_dir . '/assets/css/destinations.css') ? (string) filemtime($theme_dir . '/assets/css/destinations.css') : '1.0.0');
    
    // Add Highlights Map CSS if it exists in the plugin
    $map_css_plugin = WP_PLUGIN_DIR . '/jacana-luxe-elementor/assets/css/widgets/highlights-map.css';
    if (file_exists($map_css_plugin)) {
       wp_enqueue_style('jacana-luxe-highlights-map', plugins_url('/jacana-luxe-elementor/assets/css/widgets/highlights-map.css'), array('jacana-luxe-style'), '1.0.0');
    }
  }

  wp_enqueue_script('jacana-luxe-script', get_template_directory_uri() . '/assets/js/main.js', array(), file_exists($theme_dir . '/assets/js/main.js') ? (string) filemtime($theme_dir . '/assets/js/main.js') : '1.0.0', true);
  wp_enqueue_script('jacana-luxe-analytics', get_template_directory_uri() . '/assets/js/jacana-analytics.js', array(), file_exists($theme_dir . '/assets/js/jacana-analytics.js') ? (string) filemtime($theme_dir . '/assets/js/jacana-analytics.js') : '1.0.0', true);
  wp_enqueue_script('jacana-luxe-intelligence', get_template_directory_uri() . '/assets/js/jacana-intelligence.js', array('jacana-luxe-analytics'), file_exists($theme_dir . '/assets/js/jacana-intelligence.js') ? (string) filemtime($theme_dir . '/assets/js/jacana-intelligence.js') : '1.0.0', true);
  wp_enqueue_script('jacana-luxe-ai-experience', get_template_directory_uri() . '/assets/js/jacana-ai-experience.js', array('jacana-luxe-analytics', 'jacana-luxe-intelligence'), file_exists($theme_dir . '/assets/js/jacana-ai-experience.js') ? (string) filemtime($theme_dir . '/assets/js/jacana-ai-experience.js') : '1.0.0', true);
  $smart_prompt = array(
    'enabled' => (int) get_option('jacana_smart_prompt_enabled', 1) === 1,
    'delaySeconds' => max(5, min(600, (int) get_option('jacana_smart_prompt_delay_seconds', 30))),
    'minIntent' => max(0, min(100, (int) get_option('jacana_smart_prompt_min_intent', 20))),
    'minInteractions' => max(0, (int) get_option('jacana_smart_prompt_min_interactions', 2)),
    'minScrollDepth' => max(0, min(100, (int) get_option('jacana_smart_prompt_min_scroll_depth', 20))),
    'minDwellSeconds' => max(0, (int) get_option('jacana_smart_prompt_min_dwell_seconds', 20)),
    'oncePerSession' => (int) get_option('jacana_smart_prompt_once_per_session', 1) === 1,
    'promptInstructions' => (string) get_option('jacana_smart_prompt_system_prompt', ''),
  );
  wp_localize_script('jacana-luxe-analytics', 'jacanaAnalytics', array(
    'endpoint' => jacana_luxe_rest_endpoint('jacana/v1/track'),
    'aiEndpoint' => jacana_luxe_rest_endpoint('jacana/v1/ai'),
    'aiLogEndpoint' => jacana_luxe_rest_endpoint('jacana/v1/ai-client-log'),
    'smartPrompt' => $smart_prompt,
  ));
  wp_add_inline_script('jacana-luxe-ai-experience', 'window.jacanaAiExperience=' . wp_json_encode(array(
    'enabled' => (int) get_option('jacana_ai_experience_enabled', 1) === 1,
    'trackEndpoint' => jacana_luxe_rest_endpoint('jacana/v1/ai-touchpoint'),
    'profileEndpoint' => jacana_luxe_rest_endpoint('jacana/v1/profile'),
    'conversionEndpoint' => jacana_luxe_rest_endpoint('jacana/v1/conversion'),
    'leadEndpoint' => jacana_luxe_rest_endpoint('jacana/v1/lead'),
    'appointmentEndpoint' => jacana_luxe_rest_endpoint('jacana/v1/appointment-request'),
    'bookingEndpoint' => jacana_luxe_rest_endpoint('jacana/v1/booking-request'),
    'reviewsEndpoint' => jacana_luxe_rest_endpoint('jacana/v1/reviews'),
    'aiEndpoint' => jacana_luxe_rest_endpoint('jacana/v1/ai'),
    'aiLogEndpoint' => jacana_luxe_rest_endpoint('jacana/v1/ai-client-log'),
    'delaySeconds' => max(6, (int) get_option('jacana_ai_minor_delay_seconds', 8)),
    'showRail' => (int) get_option('jacana_ai_rail_enabled', 0) === 1,
    'railAutoDismissSeconds' => max(5, min(120, (int) get_option('jacana_ai_rail_auto_dismiss_seconds', 14))),
    'maxMajorPrompts' => max(0, (int) get_option('jacana_ai_max_major_prompts', 1)),
    'maxMinorPrompts' => max(0, (int) get_option('jacana_ai_max_minor_prompts', 2)),
    'contextCharLimit' => max(200, min(4000, (int) get_option('jacana_ai_section_context_chars', 900))),
    'prompts' => array(
      'plannerSystem' => (string) get_option('jacana_ai_planner_system_prompt', 'Craft concise, premium in-page planning prompts for Jacana. Use the current section content, page context, recent visitor interactions, and known interests to ask only the most relevant qualifying questions. Keep options concrete, factual, and tailored to the active widget.'),
      'accommodationPlanner' => (string) get_option('jacana_ai_accommodation_prompt', 'When the visitor is in accommodation styles, only use the accommodation styles visible in the current section as options. Focus on taste, comfort level, and trip style. Do not suggest unrelated service options in step one.'),
      'vehiclePlanner' => (string) get_option('jacana_ai_vehicle_prompt', 'When the visitor is comparing vehicles, keep the questions route-aware and practical. Focus on terrain, travelers, luggage, camping setup, and confidence level.'),
      'tailorPlanner' => (string) get_option('jacana_ai_tailor_prompt', 'When the visitor is in tailor-made tours or highlights, ask concise questions that qualify route style, service mix, and the fastest next step to booking or callback.'),
      'bookingPrompt' => (string) get_option('jacana_ai_booking_prompt', 'Jacana already has some of your details. Update only what changed and add any new services or places.'),
      'detailChat' => (string) get_option('jacana_ai_detail_chat_prompt', 'When a visitor asks for details from a card or style, open chat with a factual, helpful message based on that exact item and the current page context. Avoid generic sales language.')
    ),
    'isBookingPage' => is_page('booking'),
    'chatbotIcon' => esc_url((string) get_option('jacana_ai_chatbot_icon', '')),
    'pageTitle' => wp_get_document_title(),
    'pageUrl' => home_url(add_query_arg(array(), $GLOBALS['wp']->request ?? '')),
  )) . ';', 'before');
}
add_action('wp_enqueue_scripts', 'jacana_luxe_assets');

function jacana_luxe_programmatic_header_enabled() {
  if (is_admin() && !wp_doing_ajax()) {
    return false;
  }

  if (wp_doing_cron()) {
    return false;
  }

  if (defined('REST_REQUEST') && REST_REQUEST) {
    return false;
  }

  return true;
}

function jacana_luxe_disable_elementor_header_template($template_id, $location) {
  static $in_filter = false;

  if ('header' !== $location) {
    return $template_id;
  }

  if ($in_filter) {
    return $template_id;
  }

  if (!jacana_luxe_programmatic_header_enabled()) {
    return $template_id;
  }

  $in_filter = true;
  $template_id = 0;
  $in_filter = false;

  return $template_id;
}
add_filter('elementor/theme/get_location_templates/template_id', 'jacana_luxe_disable_elementor_header_template', 10, 2);

function jacana_luxe_programmatic_header_body_class($classes) {
  if (jacana_luxe_programmatic_header_enabled()) {
    $classes[] = 'jacana-programmatic-header-active';
  }

  return $classes;
}
add_filter('body_class', 'jacana_luxe_programmatic_header_body_class');

/**
 * Disable any Elementor Theme Builder footer template so the
 * theme's footer.php (programmatic premium footer) is always used.
 */
function jacana_luxe_disable_elementor_footer_template($template_id, $location) {
  if ('footer' !== $location) {
    return $template_id;
  }
  // Force Elementor to skip the footer location → fall back to theme footer.php
  return 0;
}
// add_filter('elementor/theme/get_location_templates/template_id', 'jacana_luxe_disable_elementor_footer_template', 10, 2);

function jacana_luxe_get_logo_url() {
  $logo_id = get_theme_mod('custom_logo');
  if ($logo_id) {
    $url = wp_get_attachment_image_url($logo_id, 'full');
    if ($url) {
      return $url;
    }
  }
  $uploads = wp_get_upload_dir();
  $uploads_logo = trailingslashit($uploads['basedir']) . '2026/02/logo.png';
  if (file_exists($uploads_logo)) {
    return trailingslashit($uploads['baseurl']) . '2026/02/logo.png';
  }
  return plugins_url('jacana-luxe-elementor/assets/media/logo/Jacana%20Logo-01.png');
}

function jacana_luxe_render_programmatic_header() {
  if (!jacana_luxe_programmatic_header_enabled()) {
    return;
  }

  $logo_url = jacana_luxe_get_logo_url();
  $home_url = home_url('/');
  if (function_exists('jacana_i18n_localize_url')) {
    $home_url = jacana_i18n_localize_url($home_url);
  }
  ?>
  <header class="jacana-top-header" data-jacana-header>
    <div class="jacana-top-header-inner">
      <a class="jacana-top-brand" href="<?php echo esc_url($home_url); ?>" aria-label="<?php echo esc_attr__('Jacana Safaris & Tours home', 'jacana-luxe'); ?>">
        <span class="jacana-top-brand-mark" aria-hidden="true">
          <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr__('Jacana Safaris & Tours logo', 'jacana-luxe'); ?>">
        </span>
        <span class="jacana-top-brand-text">
          <strong><?php echo esc_html__('Jacana', 'jacana-luxe'); ?></strong>
          <small><?php echo esc_html__('Safaris and Tours', 'jacana-luxe'); ?></small>
        </span>
      </a>

      <nav class="primary-nav jacana-top-nav" id="jacana-primary-nav" aria-label="<?php echo esc_attr__('Primary', 'jacana-luxe'); ?>">
        <?php
        wp_nav_menu(array(
          'theme_location' => 'primary',
          'container' => false,
          'fallback_cb' => false,
          'items_wrap' => '<ul>%3$s</ul>',
        ));
        ?>
      </nav>

      <div class="jacana-top-actions">
        <?php if (function_exists('jacana_i18n_get_switcher_html')) : ?>
          <?php echo jacana_i18n_get_switcher_html(array('class' => 'jacana-language-switcher--header', 'variant' => 'dropdown')); ?>
        <?php endif; ?>
        <!-- <button class="button button-outline header-cta header-cta-secondary" type="button" data-jacana-ai-flow="planner" data-jacana-widget="jacana_site_header" data-jacana-service="tailor_made"><?php echo esc_html__('Plan with AI', 'jacana-luxe'); ?></button> -->
        <a class="button button-primary header-cta" data-jacana-booking-modal="true" data-jacana-widget="jacana_site_header" data-jacana-service="tailor_made" href="#"><?php echo esc_html__('Start booking', 'jacana-luxe'); ?></a>
        <button class="nav-toggle jacana-top-toggle" type="button" aria-expanded="false" aria-controls="jacana-primary-nav"><?php echo esc_html__('Menu', 'jacana-luxe'); ?></button>
      </div>
    </div>
  </header>
  <?php
}
add_action('wp_body_open', 'jacana_luxe_render_programmatic_header', 9);

function jacana_luxe_render_ai_layer_root() {
  if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
    return;
  }
  echo '<div id="jacana-ai-layer" aria-live="polite"></div>';
}
add_action('wp_body_open', 'jacana_luxe_render_ai_layer_root', 11);

function jacana_luxe_excerpt_length($length) {
  return 20;
}
add_filter('excerpt_length', 'jacana_luxe_excerpt_length');

/**
 * Force the custom Destination Archive template.
 * Catches both the real CPT archive and any static Page with slug "destinations"
 * that shadows it in the WordPress template hierarchy.
 */
function jacana_luxe_force_destination_template($template) {
    if (
        is_post_type_archive('jacana_destination') ||
        is_page('explore') ||
        is_tax(array('destination_region', 'destination_type', 'activity_type', 'service_fit'))
    ) {
        $custom_template = get_template_directory() . '/archive-jacana_destination.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template;
}
add_filter('template_include', 'jacana_luxe_force_destination_template', 99);

/**
 * When a static Page with slug "destinations" shadows the CPT archive,
 * replace the main query with a jacana_destination archive query so that
 * have_posts() / the_post() return destination posts, not the page.
 */
add_action('pre_get_posts', function (\WP_Query $query) {
    if (is_admin() || ! $query->is_main_query()) {
        return;
    }
    if ($query->is_page() && 'explore' === $query->get('pagename')) {
        $query->set('post_type', 'jacana_destination');
        $query->set('post_status', 'publish');
        $query->set('posts_per_page', -1);
        $query->set('orderby', array('menu_order' => 'ASC', 'title' => 'ASC'));
        $query->set('pagename', '');
        $query->is_page              = false;
        $query->is_singular          = false;
        $query->is_post_type_archive = true;
    }
});
