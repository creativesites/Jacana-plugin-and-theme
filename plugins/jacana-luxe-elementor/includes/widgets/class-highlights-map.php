<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Highlights_Map extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_highlights_map';
  }

  public function get_title() {
    return __('Highlights Map', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-google-maps';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  private function get_hotspot_defaults() {
    return array(
      array(
        'title' => __('Windhoek', 'jacana-luxe'),
        'x' => 43,
        'y' => 50,
        'teaser' => __('Capital gateway to your route', 'jacana-luxe'),
        'description' => __('Start or end your itinerary in Windhoek with transfers, city stays, and route handover support.', 'jacana-luxe'),
      ),
      array(
        'title' => __('Etosha National Park', 'jacana-luxe'),
        'x' => 39.4,
        'y' => 16.6,
        'teaser' => __('Wildlife and iconic waterholes', 'jacana-luxe'),
        'description' => __('Track elephants, lions, rhinos, and antelope with private game-drive options and lodge experiences.', 'jacana-luxe'),
      ),
      array(
        'title' => __('Sossusvlei', 'jacana-luxe'),
        'x' => 36.4,
        'y' => 68.7,
        'teaser' => __('Dunes, sunrise, and desert textures', 'jacana-luxe'),
        'description' => __('Witness the shifting dune light, Deadvlei scenes, and guided desert activities tailored to your comfort.', 'jacana-luxe'),
      ),
      array(
        'title' => __('Fish River Canyon', 'jacana-luxe'),
        'x' => 47,
        'y' => 92,
        'teaser' => __('Epic southern Namibia landscapes', 'jacana-luxe'),
        'description' => __('Explore dramatic viewpoints and remote desert roads while we coordinate accommodation and transfers.', 'jacana-luxe'),
      ),
      array(
        'title' => __('Twyfelfontein', 'jacana-luxe'),
        'x' => 27,
        'y' => 39,
        'teaser' => __('Heritage and rugged scenery', 'jacana-luxe'),
        'description' => __('Add cultural and geological highlights to your itinerary with guided or self-drive options.', 'jacana-luxe'),
      ),
      array(
        'title' => __('Skeleton Coast', 'jacana-luxe'),
        'x' => 24,
        'y' => 29,
        'teaser' => __('Remote coastline drama', 'jacana-luxe'),
        'description' => __('Discover striking Atlantic landscapes and remote coastal experiences built into your route.', 'jacana-luxe'),
      ),
      array(
        'title' => __('Damaraland', 'jacana-luxe'),
        'x' => 29,
        'y' => 26,
        'teaser' => __('Rocky landscapes and wildlife', 'jacana-luxe'),
        'description' => __('Pair desert-adapted wildlife viewing with unique lodges across Namibia’s wild northwest.', 'jacana-luxe'),
      ),
      array(
        'title' => __('Kaokoland', 'jacana-luxe'),
        'x' => 25,
        'y' => 13,
        'teaser' => __('Remote northern frontier', 'jacana-luxe'),
        'description' => __('Integrate remote tracks and cultural encounters with the right vehicle and timing plan.', 'jacana-luxe'),
      ),
      array(
        'title' => __('Swakopmund', 'jacana-luxe'),
        'x' => 29,
        'y' => 50,
        'teaser' => __('Atlantic coast adventures', 'jacana-luxe'),
        'description' => __('Mix marine wildlife, dune activities, and relaxed coastal stays with flexible day planning.', 'jacana-luxe'),
      ),
      array(
        'title' => __('Zambezi Region', 'jacana-luxe'),
        'x' => 81.6,
        'y' => 7.2,
        'teaser' => __('River landscapes and wetlands', 'jacana-luxe'),
        'description' => __('Extend your route into lush north-eastern regions with lodge and transfer coordination.', 'jacana-luxe'),
      ),
      array(
        'title' => __('Luderitz', 'jacana-luxe'),
        'x' => 34,
        'y' => 83,
        'teaser' => __('Southern coast and history', 'jacana-luxe'),
        'description' => __('Combine coastal history and dramatic scenery with custom pacing in southern Namibia.', 'jacana-luxe'),
      ),
      array(
        'title' => __('Waterberg', 'jacana-luxe'),
        'x' => 45,
        'y' => 32,
        'teaser' => __('Plateau landscapes and reserve stays', 'jacana-luxe'),
        'description' => __('Include Waterberg as a highland stopover with tailored lodge and activity options.', 'jacana-luxe'),
      ),
      array(
        'title' => __('Kalahari Desert', 'jacana-luxe'),
        'x' => 53.7,
        'y' => 58,
        'teaser' => __('Open desert and red dunes', 'jacana-luxe'),
        'description' => __('Add Kalahari stays and scenic drives that match your preferred pace and comfort.', 'jacana-luxe'),
      ),
      array(
        'title' => __('Walvisbay', 'jacana-luxe'),
        'x' => 31,
        'y' => 55,
        'teaser' => __('Lagoon and coastal activities', 'jacana-luxe'),
        'description' => __('Combine Walvis Bay marine activities with coast and desert routing in one itinerary.', 'jacana-luxe'),
      ),
      array(
        'title' => __('Spitzkoppe', 'jacana-luxe'),
        'x' => 35.6,
        'y' => 42.2,
        'teaser' => __('Dramatic granite inselbergs and ancient art', 'jacana-luxe'),
        'description' => __('Explore the geological wonders and prehistoric art of the "Matterhorn of Namibia".', 'jacana-luxe'),
      ),
    );
  }

  private function hotspot_key($title) {
    $key = sanitize_title((string) $title);
    $aliases = array(
      'swakopmund-coast' => 'swakopmund',
      'walvis-bay' => 'walvisbay',
      'walvis-bay-namibia' => 'walvisbay',
      'luderitz' => 'luderitz',
      'luderitz-town' => 'luderitz',
    );

    return isset($aliases[$key]) ? $aliases[$key] : $key;
  }

  private function normalize_hotspots($hotspots) {
    $defaults = $this->get_hotspot_defaults();
    $hotspots = is_array($hotspots) ? $hotspots : array();
    $default_keys = array();
    $configured_by_key = array();

    foreach ($defaults as $default) {
      $default_keys[] = $this->hotspot_key($default['title'] ?? '');
    }

    foreach ($hotspots as $item) {
      if (!is_array($item)) {
        continue;
      }
      $key = $this->hotspot_key($item['title'] ?? '');
      if (!empty($key) && !isset($configured_by_key[$key])) {
        $configured_by_key[$key] = $item;
      }
    }

    $merged = array();
    foreach ($defaults as $default) {
      $key = $this->hotspot_key($default['title'] ?? '');
      if (!empty($key) && isset($configured_by_key[$key])) {
        $merged[] = wp_parse_args($configured_by_key[$key], $default);
      } else {
        $merged[] = $default;
      }
    }

    foreach ($hotspots as $item) {
      if (!is_array($item)) {
        continue;
      }
      $key = $this->hotspot_key($item['title'] ?? '');
      if (!empty($key) && !in_array($key, $default_keys, true)) {
        $merged[] = $item;
      }
    }

    return $merged;
  }

  private function clamp_percent($value, $fallback = 50) {
    if ($value === '' || $value === null) {
      return (float) $fallback;
    }

    return max(0, min(100, (float) $value));
  }

  private function get_booking_cta_url() {
    return '#';
  }

  private function get_safari_place_hotspots($settings, $fallback_image) {
    $limit = isset($settings['safari_places_limit']) ? (int) $settings['safari_places_limit'] : -1;
    if (0 === $limit) {
      return array();
    }

    $query = new \WP_Query(array(
      'post_type' => 'jacana_destination',
      'post_status' => 'publish',
      'posts_per_page' => $limit > 0 ? $limit : -1,
      'orderby' => array(
        'menu_order' => 'ASC',
        'title' => 'ASC',
      ),
      'order' => 'ASC',
      'no_found_rows' => true,
    ));

    $items = array();

    if ($query->have_posts()) {
      foreach ($query->posts as $post) {
        $post_id = $post->ID;
        $title = get_the_title($post_id);
        $x = $this->clamp_percent(get_post_meta($post_id, '_jacana_map_x', true), 50);
        $y = $this->clamp_percent(get_post_meta($post_id, '_jacana_map_y', true), 50);

        $teaser = trim((string) get_post_meta($post_id, '_jacana_map_teaser', true));
        if ($teaser === '') {
          $teaser = has_excerpt($post_id) ? wp_strip_all_tags(get_the_excerpt($post_id)) : '';
        }

        $description = trim((string) get_post_meta($post_id, '_jacana_map_description', true));
        if ($description === '') {
          if (has_excerpt($post_id)) {
            $description = wp_strip_all_tags(get_the_excerpt($post_id));
          } else {
            $description = wp_trim_words(wp_strip_all_tags($post->post_content), 32);
          }
        }

        $image = get_the_post_thumbnail_url($post_id, 'large');
        if (empty($image)) {
          $image = $fallback_image;
        }

        $video = trim((string) get_post_meta($post_id, '_jacana_map_video_url', true));

        $items[] = array(
          'title' => $title,
          'x' => $x,
          'y' => $y,
          'teaser' => $teaser,
          'description' => $description,
          'media_image' => array('url' => $image),
          'video_url' => array('url' => $video),
          'cta_label' => !empty($settings['safari_places_cta_label']) ? $settings['safari_places_cta_label'] : __('Open destination page', 'jacana-luxe'),
          'cta_link' => array('url' => get_permalink($post_id)),
          'chat_label' => !empty($settings['safari_places_chat_label']) ? $settings['safari_places_chat_label'] : __('Ask the chatbot', 'jacana-luxe'),
        );
      }
    }

    wp_reset_postdata();

    return $items;
  }

  protected function get_init_settings() {
    $settings = parent::get_init_settings();

    if (is_array($settings)) {
      $settings['hotspots'] = $this->normalize_hotspots(!empty($settings['hotspots']) ? $settings['hotspots'] : array());
    }

    return $settings;
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Highlights of Namibia', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label' => __('Intro', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Tap any hotspot to preview the destination and ask our AI guide about it.', 'jacana-luxe'),
    ));

    $this->add_control('data_source', array(
      'label' => __('Hotspot Source', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::SELECT,
      'default' => 'manual',
      'options' => array(
        'manual' => __('Manual (repeater)', 'jacana-luxe'),
        'safari_places' => __('Destinations (posts)', 'jacana-luxe'),
      ),
    ));

    $this->add_control('map_image', array(
      'label' => __('Map Background Image', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
    ));

    $this->add_control('default_hotspot_image', array(
      'label' => __('Default Hotspot Image', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
      'default' => array(
        'url' => \Elementor\Utils::get_placeholder_image_src(),
      ),
      'description' => __('Used when a hotspot image is not selected.', 'jacana-luxe'),
    ));

    $this->add_control('map_fallback_copy', array(
      'label' => __('Fallback Copy', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Add hotspots to activate the interactive destination panel.', 'jacana-luxe'),
    ));

    $this->add_control('safari_places_limit', array(
      'label' => __('Safari Places Limit', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::NUMBER,
      'default' => -1,
      'min' => -1,
      'step' => 1,
      'description' => __('Use -1 to show all published Destination posts.', 'jacana-luxe'),
      'condition' => array('data_source' => 'safari_places'),
    ));

    $this->add_control('safari_places_cta_label', array(
      'label' => __('Safari Place CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Open destination page', 'jacana-luxe'),
      'condition' => array('data_source' => 'safari_places'),
    ));

    $this->add_control('safari_places_chat_label', array(
      'label' => __('Safari Place Chat Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Ask the chatbot', 'jacana-luxe'),
      'condition' => array('data_source' => 'safari_places'),
    ));

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('title', array(
      'label' => __('Hotspot Title', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
    ));
    $repeater->add_control('x', array(
      'label' => __('Horizontal Position (%)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::NUMBER,
      'min' => 0,
      'max' => 100,
      'step' => 0.1,
      'default' => 50,
    ));
    $repeater->add_control('y', array(
      'label' => __('Vertical Position (%)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::NUMBER,
      'min' => 0,
      'max' => 100,
      'step' => 0.1,
      'default' => 50,
    ));
    $repeater->add_control('teaser', array(
      'label' => __('Teaser', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
    ));
    $repeater->add_control('description', array(
      'label' => __('Description', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
    ));
    $repeater->add_control('media_image', array(
      'label' => __('Panel Image', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
      'default' => array(
        'url' => \Elementor\Utils::get_placeholder_image_src(),
      ),
      'description' => __('This image is shown in the hotspot preview panel.', 'jacana-luxe'),
    ));
    $repeater->add_control('video_url', array(
      'label' => __('Video URL (optional)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'placeholder' => 'https://',
    ));
    $repeater->add_control('cta_label', array(
      'label' => __('CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('I want to know more about this place', 'jacana-luxe'),
    ));
    $repeater->add_control('cta_link', array(
      'label' => __('CTA Link', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'placeholder' => '#',
    ));
    $repeater->add_control('chat_label', array(
      'label' => __('Chat Button Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Ask the chatbot', 'jacana-luxe'),
    ));

    $this->add_control('hotspots', array(
      'label' => __('Hotspots', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::REPEATER,
      'fields' => $repeater->get_controls(),
      'default' => $this->get_hotspot_defaults(),
      'title_field' => '{{{ title }}}',
      'condition' => array('data_source' => 'manual'),
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $map = !empty($settings['map_image']['url']) ? $settings['map_image']['url'] : '';
    $fallback_image = !empty($settings['default_hotspot_image']['url']) ? $settings['default_hotspot_image']['url'] : \Elementor\Utils::get_placeholder_image_src();
    $booking_cta_url = $this->get_booking_cta_url();
    $source = !empty($settings['data_source']) ? $settings['data_source'] : 'manual';
    if ('safari_places' === $source) {
      $hotspots = $this->get_safari_place_hotspots($settings, $fallback_image);
    } else {
      $hotspots = $this->normalize_hotspots(!empty($settings['hotspots']) ? $settings['hotspots'] : array());
    }
    ?>
    <section class="section jacana-zoora-parity jacana-tours-widget jacana-highlights-map">
      <div class="section-inner">

        <!-- Section header -->
        <header class="jacana-map-header">
          <div class="jacana-tailor-story-kicker jacana-reveal">
            <?php echo esc_html__('Explore Namibia', 'jacana-luxe'); ?>
          </div>
          <h2 class="jacana-map-heading jacana-reveal" style="--jacana-delay: 80ms;">
            <?php echo esc_html($settings['heading']); ?>
          </h2>
          <p class="jacana-map-intro jacana-reveal" style="--jacana-delay: 150ms;">
            <?php echo esc_html($settings['intro']); ?>
          </p>
        </header>

        <div class="jacana-highlights-map-grid">
          <div class="jacana-map-canvas jacana-reveal<?php echo empty($map) ? ' is-empty' : ''; ?>">
            <?php if (!empty($map)) : ?>
              <img class="jacana-map-base-image" src="<?php echo esc_url($map); ?>" alt="<?php echo esc_attr($settings['heading']); ?>" loading="lazy">
            <?php endif; ?>
            <?php if (!empty($hotspots)) : ?>
              <?php foreach ($hotspots as $index => $spot) :
                $video = !empty($spot['video_url']['url']) ? $spot['video_url']['url'] : '';
                $image = !empty($spot['media_image']['url']) ? $spot['media_image']['url'] : $fallback_image;
                ?>
                <button
                  class="jacana-map-hotspot"
                  type="button"
                  aria-label="<?php echo esc_attr($spot['title'] ?? __('Destination hotspot', 'jacana-luxe')); ?>"
                  style="left: <?php echo esc_attr($spot['x'] ?? 50); ?>%; top: <?php echo esc_attr($spot['y'] ?? 50); ?>%;"
                  data-title="<?php echo esc_attr($spot['title'] ?? ''); ?>"
                  data-teaser="<?php echo esc_attr($spot['teaser'] ?? ''); ?>"
                  data-description="<?php echo esc_attr($spot['description'] ?? ''); ?>"
                  data-image="<?php echo esc_attr($image); ?>"
                  data-video="<?php echo esc_attr($video); ?>"
                  data-cta-label="<?php echo esc_attr(__('I want to know more about this place', 'jacana-luxe')); ?>"
                  data-cta-link="<?php echo esc_attr($booking_cta_url); ?>"
                  data-chat-label="<?php echo esc_attr($spot['chat_label'] ?? __('Ask the chatbot', 'jacana-luxe')); ?>"
                  data-index="<?php echo esc_attr($index); ?>"
                >
                  <span class="jacana-hotspot-label"><?php echo esc_html($spot['title'] ?? ''); ?></span>
                </button>
              <?php endforeach; ?>
            <?php else : ?>
              <p class="jacana-map-fallback"><?php echo esc_html($settings['map_fallback_copy']); ?></p>
            <?php endif; ?>
          </div>
          <aside class="card jacana-map-panel jacana-reveal jacana-reveal-right" style="--jacana-delay: 160ms;">
            <div class="jacana-map-panel-media">
              <img class="jacana-map-panel-image is-visible" data-map-image src="<?php echo esc_url($fallback_image); ?>" alt="<?php echo esc_attr__('Destination preview', 'jacana-luxe'); ?>" loading="lazy">
              <div class="jacana-map-panel-media-overlay" aria-hidden="true">
                <h6 class="jacana-map-panel-media-title" data-map-title-overlay><?php echo esc_html__('Select a destination', 'jacana-luxe'); ?></h6>
              </div>
              <div class="jacana-map-panel-video" data-map-video-wrap hidden>
                <iframe data-map-video src="" title="<?php echo esc_attr__('Destination video', 'jacana-luxe'); ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
              </div>
            </div>
            <div class="jacana-map-panel-body">
              <p class="jacana-map-panel-teaser" data-map-teaser><?php echo esc_html__('Click any location on the map to preview details.', 'jacana-luxe'); ?></p>
              <p class="jacana-map-panel-description" data-map-description><?php echo esc_html__('We can tailor this stop into your route and prepare a full quote.', 'jacana-luxe'); ?></p>
            </div>
            <div class="jacana-map-actions">
              <button type="button" class="button button-primary jacana-tailor-cta-primary" data-map-chat-cta data-jacana-widget="jacana_highlights_map">
                <?php echo esc_html__('I want to know more about this place', 'jacana-luxe'); ?>
                <span class="jacana-cta-arrow" aria-hidden="true">&rarr;</span>
              </button>
            </div>
          </aside>
        </div><!-- /.jacana-highlights-map-grid -->

        <!-- Places pill list for discoverability -->
        <?php if (!empty($hotspots)) : ?>
          <div class="jacana-map-places jacana-reveal" style="--jacana-delay: 200ms;" aria-label="<?php echo esc_attr__('Browse destinations', 'jacana-luxe'); ?>">
            <?php foreach ($hotspots as $index => $spot) : ?>
              <button class="jacana-map-place-pill" type="button"
                      data-place-index="<?php echo esc_attr($index); ?>">
                <?php echo esc_html($spot['title'] ?? ''); ?>
              </button>
            <?php endforeach; ?>
          </div>

          <?php if (\Elementor\Plugin::$instance->editor->is_edit_mode()) : ?>
          <!-- Export positions utility — editor only -->
          <div class="jacana-map-export-row">
            <button type="button" class="jacana-map-export-btn" data-map-export
                    aria-label="<?php echo esc_attr__('Export all hotspot map positions as JSON', 'jacana-luxe'); ?>">
              <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true" focusable="false">
                <path d="M7 1v8M4 6l3 3 3-3M1 10v1.5A1.5 1.5 0 0 0 2.5 13h9a1.5 1.5 0 0 0 1.5-1.5V10" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <?php echo esc_html__('Export positions', 'jacana-luxe'); ?>
            </button>
          </div>
          <script>
          (function () {
            var btn = document.querySelector('[data-map-export]');
            if (!btn) return;
            btn.addEventListener('click', function () {
              var hotspots = document.querySelectorAll('.jacana-map-hotspot');
              if (!hotspots.length) return;
              var positions = Array.prototype.map.call(hotspots, function (el) {
                var left = parseFloat(el.style.left) || 0;
                var top  = parseFloat(el.style.top)  || 0;
                return {
                  title: el.dataset.title || '',
                  x: Math.round(left * 10) / 10,
                  y: Math.round(top  * 10) / 10,
                };
              });
              var json = JSON.stringify(positions, null, 2);
              var blob = new Blob([json], { type: 'application/json' });
              var url  = URL.createObjectURL(blob);
              var a    = document.createElement('a');
              a.href     = url;
              a.download = 'map-positions.json';
              document.body.appendChild(a);
              a.click();
              document.body.removeChild(a);
              URL.revokeObjectURL(url);
            });
          }());
          </script>
          <?php endif; ?>
        <?php endif; ?>
    <?php
  }

  public function get_raw_data($with_html_content = false) {
    $data = parent::get_raw_data($with_html_content);

    if (!empty($data['settings']) && is_array($data['settings'])) {
      $data['settings']['hotspots'] = $this->normalize_hotspots(!empty($data['settings']['hotspots']) ? $data['settings']['hotspots'] : array());
    }

    return $data;
  }
}
