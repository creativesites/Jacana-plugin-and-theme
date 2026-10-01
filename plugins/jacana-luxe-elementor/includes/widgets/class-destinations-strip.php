<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Destinations_Strip extends \Elementor\Widget_Base {
  public function get_name()       { return 'jacana_destinations_strip'; }
  public function get_title()      { return __('Destinations Strip', 'jacana-luxe'); }
  public function get_icon()       { return 'eicon-globe'; }
  public function get_categories() { return array('jacana-luxe'); }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('kicker', array(
      'label'   => __('Kicker', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Where to go', 'jacana-luxe'),
    ));

    $this->add_control('heading', array(
      'label'   => __('Heading', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Discover Namibia', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label'   => __('Intro text', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('From the ancient dunes of Sossusvlei to the wildlife-rich plains of Etosha — every corner of Namibia holds a story worth chasing.', 'jacana-luxe'),
    ));

    $this->add_control('gallery_page_url', array(
      'label'   => __('Fallback URL (when no matching destination post found)', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::URL,
      'default' => array('url' => '/destinations/'),
    ));

    $this->add_control('explore_label', array(
      'label'   => __('Explore CTA Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Explore destination', 'jacana-luxe'),
    ));

    /* ── Destination cards repeater ── */
    $repeater = new \Elementor\Repeater();

    $repeater->add_control('image', array(
      'label' => __('Image', 'jacana-luxe'),
      'type'  => \Elementor\Controls_Manager::MEDIA,
    ));

    $repeater->add_control('title', array(
      'label'   => __('Destination Title', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Destination', 'jacana-luxe'),
    ));

    $repeater->add_control('location', array(
      'label'   => __('Location', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Namibia', 'jacana-luxe'),
    ));

    $repeater->add_control('category', array(
      'label'   => __('Category', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Landscape', 'jacana-luxe'),
    ));

    $repeater->add_control('description', array(
      'label'       => __('Short description (for AI context)', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::TEXTAREA,
      'description' => __('This text is not displayed but passed to the AI when a visitor clicks Explore.', 'jacana-luxe'),
      'rows'        => 3,
    ));

    $repeater->add_control('map_key', array(
      'label'       => __('Highlights Map Pin Title', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::TEXT,
      'description' => __('Exact title of the matching Highlights Map hotspot. Used to auto-activate the pin when visitors arrive on the gallery page. Must match the hotspot title precisely (case-insensitive).', 'jacana-luxe'),
      'placeholder' => 'e.g. Etosha National Park',
    ));

    $repeater->add_control('size', array(
      'label'   => __('Card Size', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::SELECT,
      'default' => 'normal',
      'options' => array(
        'normal'   => __('Normal (1 col)', 'jacana-luxe'),
        'featured' => __('Featured (2 cols)', 'jacana-luxe'),
      ),
    ));

    $this->add_control('destinations', array(
      'label'       => __('Destinations', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::REPEATER,
      'fields'      => $repeater->get_controls(),
      'default'     => array(
        array(
          'title'    => __('Etosha Waterhole',     'jacana-luxe'),
          'location' => __('Etosha National Park', 'jacana-luxe'),
          'category' => __('Wildlife',             'jacana-luxe'),
          'map_key'  => __('Etosha National Park', 'jacana-luxe'),
          'size'     => 'featured',
        ),
        array(
          'title'    => __('Sossusvlei Dunes', 'jacana-luxe'),
          'location' => __('Namib Desert',    'jacana-luxe'),
          'category' => __('Landscape',       'jacana-luxe'),
          'map_key'  => __('Sossusvlei',      'jacana-luxe'),
          'size'     => 'normal',
        ),
        array(
          'title'    => __('Sandwich Harbour', 'jacana-luxe'),
          'location' => __('Walvis Bay',       'jacana-luxe'),
          'category' => __('Coast',            'jacana-luxe'),
          'map_key'  => __('Walvisbay',        'jacana-luxe'),
          'size'     => 'normal',
        ),
        array(
          'title'    => __('Rhino Tracking',       'jacana-luxe'),
          'location' => __('Etosha National Park', 'jacana-luxe'),
          'category' => __('Wildlife',             'jacana-luxe'),
          'map_key'  => __('Etosha National Park', 'jacana-luxe'),
          'size'     => 'normal',
        ),
        array(
          'title'    => __('Flamingo Coast',    'jacana-luxe'),
          'location' => __('Swakopmund',        'jacana-luxe'),
          'category' => __('Coast',             'jacana-luxe'),
          'map_key'  => __('Swakopmund',        'jacana-luxe'),
          'size'     => 'normal',
        ),
        array(
          'title'    => __('Spitzkoppe',   'jacana-luxe'),
          'location' => __('Damaraland',   'jacana-luxe'),
          'category' => __('Landscape',    'jacana-luxe'),
          'map_key'  => __('Damaraland',   'jacana-luxe'),
          'size'     => 'normal',
        ),
      ),
      'title_field' => '{{{ title }}}',
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings       = $this->get_settings_for_display();
    $items          = !empty($settings['destinations']) && is_array($settings['destinations'])
                      ? $settings['destinations'] : array();
    $archive_url    = !empty($settings['gallery_page_url']['url'])
                      ? $settings['gallery_page_url']['url']
                      : (get_post_type_archive_link('jacana_destination') ?: '/destinations/');
    $explore_label  = !empty($settings['explore_label']) ? $settings['explore_label'] : __('Explore destination', 'jacana-luxe');

    // Build a title → permalink map from published destination posts (one query, no per-card queries).
    $dest_posts = get_posts(array(
      'post_type'      => 'jacana_destination',
      'post_status'    => 'publish',
      'posts_per_page' => -1,
      'fields'         => 'all',
    ));
    $dest_permalink_map = array();
    foreach ($dest_posts as $dp) {
      $dest_permalink_map[ strtolower($dp->post_title) ] = get_permalink($dp->ID);
    }
    ?>
    <section class="jacana-ds-section">
      <div class="jacana-ds-inner">

        <!-- ── Header ── -->
        <header class="jacana-ds-header">
          <div class="jacana-ds-header-left jacana-reveal">
            <?php if (!empty($settings['kicker'])) : ?>
              <div class="jacana-ds-kicker"><?php echo esc_html($settings['kicker']); ?></div>
            <?php endif; ?>
            <h2 class="jacana-ds-heading"><?php echo esc_html($settings['heading']); ?></h2>
          </div>
          <?php if (!empty($settings['intro'])) : ?>
            <div class="jacana-ds-header-right jacana-reveal" style="--jacana-delay: 80ms;">
              <p class="jacana-ds-intro"><?php echo esc_html($settings['intro']); ?></p>
            </div>
          <?php endif; ?>
        </header>

        <!-- ── Destination cards ── -->
        <?php if (!empty($items)) : ?>
          <div class="jacana-ds-grid">
            <?php foreach ($items as $index => $item) :
              $url           = !empty($item['image']['url']) ? $item['image']['url'] : '';
              $size          = !empty($item['size']) ? $item['size'] : 'normal';
              $category      = !empty($item['category']) ? trim((string) $item['category']) : '';
              $description   = !empty($item['description']) ? $item['description'] : '';
              $title_attr    = !empty($item['title']) ? trim((string) $item['title']) : '';
              $location_attr = !empty($item['location']) ? trim((string) $item['location']) : '';
              $map_key       = !empty($item['map_key']) ? trim((string) $item['map_key']) : $title_attr;
              $delay         = 60 * ((int) $index + 1);
              // Resolve to individual destination post, falling back to archive.
              $lookup_key    = strtolower($title_attr);
              $lookup_map    = strtolower($map_key);
              $dest_url      = $dest_permalink_map[ $lookup_key ]
                               ?? $dest_permalink_map[ $lookup_map ]
                               ?? $archive_url;
            ?>
              <article
                class="jacana-ds-card jacana-ds-card--<?php echo esc_attr($size); ?> jacana-reveal"
                style="--jacana-delay: <?php echo esc_attr($delay); ?>ms;">

                <a class="jacana-ds-card-link"
                   href="<?php echo esc_url($dest_url); ?>"
                   aria-label="<?php echo esc_attr(
                     $title_attr
                     ? sprintf(__('Explore %s, %s', 'jacana-luxe'), $title_attr, $location_attr)
                     : __('Explore destination', 'jacana-luxe')
                   ); ?>"
                   data-jacana-widget="jacana_destinations_strip"
                   data-jacana-service="tailor_made"
                   data-jacana-destination="<?php echo esc_attr($map_key); ?>"
                   data-jacana-desc="<?php echo esc_attr($description); ?>">

                  <!-- Image -->
                  <span class="jacana-ds-media" aria-hidden="true">
                    <?php if ($url) : ?>
                      <img src="<?php echo esc_url($url); ?>"
                           alt=""
                           loading="lazy"
                           decoding="async">
                    <?php endif; ?>
                  </span>

                  <!-- Overlay -->
                  <span class="jacana-ds-overlay" aria-hidden="true"></span>

                  <!-- Oshiwambo stripe on hover -->
                  <span class="jacana-ds-stripe" aria-hidden="true"></span>

                  <!-- Meta -->
                  <span class="jacana-ds-meta">
                    <?php if ($category) : ?>
                      <span class="jacana-ds-category" aria-hidden="true"><?php echo esc_html($category); ?></span>
                    <?php endif; ?>
                    <?php if ($title_attr) : ?>
                      <span class="jacana-ds-title"><?php echo esc_html($title_attr); ?></span>
                    <?php endif; ?>
                    <?php if ($location_attr) : ?>
                      <span class="jacana-ds-location"><?php echo esc_html($location_attr); ?></span>
                    <?php endif; ?>
                    <span class="jacana-ds-cta" aria-hidden="true">
                      <?php echo esc_html($explore_label); ?>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </span>
                  </span>

                </a>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>
    </section>
    <?php
  }
}
