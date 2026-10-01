<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Main_Services_Grid extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_main_services_grid';
  }

  public function get_title() {
    return __('Main Services Grid', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-posts-grid';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Everything you need for Namibia', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label' => __('Intro', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Choose one service or combine several in one tailor-made plan. All rates are provided on request so we can match your route, dates, and travel style.', 'jacana-luxe'),
    ));

    $this->add_control('chat_cta_label', array(
      'label' => __('Secondary Chat CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Help me decide', 'jacana-luxe'),
    ));

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('title', array(
      'label' => __('Service Title', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
    ));
    $repeater->add_control('badge', array(
      'label' => __('Badge', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Rates on request', 'jacana-luxe'),
    ));
    $repeater->add_control('description', array(
      'label' => __('Description', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
    ));
    $repeater->add_control('highlights', array(
      'label' => __('Highlights (one per line)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
    ));
    $repeater->add_control('image', array(
      'label' => __('Image', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
    ));
    $repeater->add_control('cta_label', array(
      'label' => __('CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Get more information', 'jacana-luxe'),
    ));
    $repeater->add_control('cta_link', array(
      'label' => __('CTA Link', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'placeholder' => '#',
    ));

    $this->add_control('services', array(
      'label' => __('Service Cards', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::REPEATER,
      'fields' => $repeater->get_controls(),
      'default' => array(
        array(
          'title' => __('Hotel Reservations, Lodges & Campsites', 'jacana-luxe'),
          'badge' => __('Rates on request', 'jacana-luxe'),
          'description' => __('We arrange your Namibian accommodation in hotels, lodges, or campsites based on your route, comfort preferences, and budget.', 'jacana-luxe'),
          'highlights' => "Hotels, lodges and campsites\nRoute-based accommodation planning\nSingle-point booking coordination",
          'cta_label' => __('Request Accommodation Support', 'jacana-luxe'),
        ),
        array(
          'title' => __('Local & International Flight Bookings', 'jacana-luxe'),
          'badge' => __('Rates on request', 'jacana-luxe'),
          'description' => __('We assist with domestic and international flight tickets, including international multi-stop flight bookings in different travel classes.', 'jacana-luxe'),
          'highlights' => "Domestic and international flights\nMulti-stop itinerary support\nAll travel classes",
          'cta_label' => __('Request Flight Support', 'jacana-luxe'),
        ),
        array(
          'title' => __('Airport Transfers & Shuttles', 'jacana-luxe'),
          'badge' => __('Rates on request', 'jacana-luxe'),
          'description' => __('We provide hassle-free shuttle transfers from Windhoek International Airport to Windhoek City or other destinations, plus transfers between destinations in Namibia.', 'jacana-luxe'),
          'highlights' => "Airport pickups and drop-offs\nWindhoek and Namibia-wide transfers\nPrivate shuttle coordination",
          'cta_label' => __('Request Transfer Service', 'jacana-luxe'),
        ),
        array(
          'title' => __('City Tours with Chauffeur', 'jacana-luxe'),
          'badge' => __('Rates on request', 'jacana-luxe'),
          'description' => __('Private day tours from Windhoek to nearby places of interest, including city highlights, townships, lodges, game-drive add-ons, and wellness stops with a professional chauffeur.', 'jacana-luxe'),
          'highlights' => "Windhoek city and township tours\nFlexible day tour combinations\nProfessional chauffeur-guided service",
          'cta_label' => __('Plan a City Tour', 'jacana-luxe'),
        ),
        array(
          'title' => __('Game Drive (Etosha)', 'jacana-luxe'),
          'badge' => __('Rates on request', 'jacana-luxe'),
          'description' => __('Morning and afternoon Etosha game drives in open-sided vehicles with expert guides, searching for lions, elephants, zebras and more across one of Africa\'s most important game reserves.', 'jacana-luxe'),
          'highlights' => "Etosha National Park game drives\nOpen-sided safari vehicles\nExpert local guides",
          'cta_label' => __('Plan a Game Drive', 'jacana-luxe'),
        ),
        array(
          'title' => __('Self-Drive & Guided Tours', 'jacana-luxe'),
          'badge' => __('Rates on request', 'jacana-luxe'),
          'description' => __('We design your tour itinerary and you choose your travel style: self-drive with your own steering wheel, or guided touring with one of our experienced guides.', 'jacana-luxe'),
          'highlights' => "Custom itinerary design\nSelf-drive or guided options\nPre-trip and on-trip support",
          'cta_label' => __('Explore Tour Options', 'jacana-luxe'),
        ),
        array(
          'title' => __('Car Rentals', 'jacana-luxe'),
          'badge' => __('Rates on request', 'jacana-luxe'),
          'description' => __('We help you choose from a wide range of Namibian rental cars, off-road vehicles, campers, and group transport vehicles, with practical travel extras available.', 'jacana-luxe'),
          'highlights' => "Sedans, SUVs, 4x4s, campers and buses\nCamping-equipped vehicles available\nExtras: GPS, child seats, cooler boxes",
          'cta_label' => __('Request a Vehicle Plan', 'jacana-luxe'),
        ),
      ),
      'title_field' => '{{{ title }}}',
    ));

    $this->end_controls_section();
  }

  private function service_key_from_title($title) {
    $value = strtolower((string) $title);
    if (strpos($value, 'hotel') !== false || strpos($value, 'lodge') !== false || strpos($value, 'camp') !== false) {
      return 'accommodation';
    }
    if (strpos($value, 'flight') !== false) {
      return 'flights';
    }
    if (strpos($value, 'transfer') !== false || strpos($value, 'shuttle') !== false || strpos($value, 'airport') !== false) {
      return 'transfers';
    }
    if (strpos($value, 'city') !== false || strpos($value, 'chauffeur') !== false) {
      return 'city_tours';
    }
    if (strpos($value, 'game') !== false || strpos($value, 'etosha') !== false || strpos($value, 'safari') !== false) {
      return 'game_drive';
    }
    if (strpos($value, 'self-drive') !== false || strpos($value, 'guided') !== false || strpos($value, 'tour') !== false) {
      return 'tours';
    }
    if (strpos($value, 'car') !== false || strpos($value, 'rental') !== false || strpos($value, 'vehicle') !== false || strpos($value, '4x4') !== false) {
      return 'car_rental';
    }
    return sanitize_key(sanitize_title((string) $title));
  }

  protected function render() {
    $settings    = $this->get_settings_for_display();
    $chat_label  = !empty($settings['chat_cta_label']) ? $settings['chat_cta_label'] : __('Help me decide', 'jacana-luxe');
    ?>
    <section class="jacana-msg-section" id="services">
      <div class="jacana-msg-inner">

        <header class="jacana-msg-header jacana-reveal">
          <div class="jacana-msg-kicker"><?php echo esc_html__('Our Services', 'jacana-luxe'); ?></div>
          <h2><?php echo esc_html($settings['heading']); ?></h2>
          <p class="jacana-msg-intro"><?php echo esc_html($settings['intro']); ?></p>
        </header>

        <div class="jacana-msg-grid">
          <?php foreach ($settings['services'] as $index => $service) :
            $image       = !empty($service['image']['url']) ? $service['image']['url'] : '';
            $cta         = !empty($service['cta_link']) ? $service['cta_link'] : array();
            $cta_url     = !empty($cta['url']) ? $cta['url'] : '#';
            $cta_ext     = !empty($cta['is_external']);
            $cta_label   = !empty($service['cta_label']) ? $service['cta_label'] : __('Get more information', 'jacana-luxe');
            $badge        = !empty($service['badge']) ? $service['badge'] : '';
            $service_key  = $this->service_key_from_title($service['title'] ?? '');
            $numeral      = sprintf('%02d', $index + 1);
            $delay        = 60 + ($index * 80);
            $highlights   = array_filter(array_map('trim', explode("\n", (string) ($service['highlights'] ?? ''))));
          ?>
            <article class="jacana-msg-card jacana-reveal"
                     style="--msg-delay: <?php echo esc_attr($delay); ?>ms;"
                     data-jacana-service-card
                     data-service-name="<?php echo esc_attr($service['title'] ?? ''); ?>">

              <!-- Image panel -->
              <?php if ($image) : ?>
                <div class="jacana-msg-image-wrap">
                  <img class="jacana-msg-image"
                       src="<?php echo esc_url($image); ?>"
                       alt="<?php echo esc_attr($service['title'] ?? ''); ?>"
                       loading="lazy">
                  <div class="jacana-msg-image-stripe" aria-hidden="true"></div>
                </div>
              <?php endif; ?>

              <!-- Content -->
              <div class="jacana-msg-body">

                <div class="jacana-msg-top">
                  <span class="jacana-msg-numeral" aria-hidden="true"><?php echo esc_html($numeral); ?></span>
                  <?php if ($badge) : ?>
                    <span class="jacana-msg-badge"><?php echo esc_html($badge); ?></span>
                  <?php endif; ?>
                </div>

                <h3 class="jacana-msg-title"><?php echo esc_html($service['title'] ?? ''); ?></h3>

                <?php if (!empty($service['description'])) : ?>
                  <p class="jacana-msg-description"><?php echo esc_html($service['description']); ?></p>
                <?php endif; ?>

                <?php if (!empty($highlights)) : ?>
                  <ul class="jacana-msg-features">
                    <?php foreach ($highlights as $item) : ?>
                      <li><?php echo esc_html($item); ?></li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>

                <div class="jacana-msg-actions">
                  <a class="jacana-msg-cta"
                     href="<?php echo esc_url($cta_url); ?>"
                     <?php echo $cta_ext ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                    <?php echo esc_html($cta_label); ?>
                    <span class="jacana-msg-arrow" aria-hidden="true">&rarr;</span>
                  </a>
                  <button type="button"
                          class="jacana-msg-chat"
                          data-jacana-ai-surface="service"
                          data-jacana-ai-flow="service"
                          data-jacana-widget="jacana_main_services_grid"
                          data-jacana-service="<?php echo esc_attr($service_key); ?>">
                    <?php echo esc_html($chat_label); ?>
                  </button>
                </div>

              </div>
            </article>
          <?php endforeach; ?>
        </div>

      </div>
    </section>
    <?php
  }
}
