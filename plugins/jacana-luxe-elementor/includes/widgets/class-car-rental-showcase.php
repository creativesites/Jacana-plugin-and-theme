<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Car_Rental_Showcase extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_car_rental_showcase';
  }

  public function get_title() {
    return __('Car Rental Showcase', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-media-carousel';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Car Rental Fleet Options', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label' => __('Intro', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Browse vehicle categories for Namibia routes. Rates are provided on request based on dates, route, and rental duration.', 'jacana-luxe'),
    ));

    $this->add_control('panel_kicker', array(
      'label' => __('Details Panel Kicker', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Vehicle Details', 'jacana-luxe'),
    ));

    $this->add_control('default_cta_label', array(
      'label' => __('Default CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Talk to Jacana about this vehicle', 'jacana-luxe'),
    ));

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('group_code', array(
      'label' => __('Group Code', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('GROUP H', 'jacana-luxe'),
    ));
    $repeater->add_control('title', array(
      'label' => __('Vehicle Name', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
    ));
    $repeater->add_control('summary', array(
      'label' => __('Summary', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
    ));
    $repeater->add_control('details', array(
      'label' => __('Details (one per line)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'rows' => 8,
    ));
    $repeater->add_control('image', array(
      'label' => __('Vehicle Image', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
      'description' => __('Add image later in Elementor.', 'jacana-luxe'),
    ));
    $repeater->add_control('cta_label', array(
      'label' => __('CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'placeholder' => __('View More Details', 'jacana-luxe'),
    ));
    $repeater->add_control('cta_link', array(
      'label' => __('CTA Link', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'placeholder' => '#',
    ));

    $this->add_control('vehicles', array(
      'label' => __('Vehicles', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::REPEATER,
      'fields' => $repeater->get_controls(),
      'title_field' => '{{{ group_code }}} - {{{ title }}}',
      'default' => $this->get_default_vehicles(),
    ));

    $this->end_controls_section();
  }

  private function get_default_vehicles() {
    return array(
      array(
        'group_code' => __('GROUP H', 'jacana-luxe'),
        'title' => __('VW Polo hatch or similar', 'jacana-luxe'),
        'summary' => __('Compact economy vehicle for city use and shorter road itineraries.', 'jacana-luxe'),
        'details' => "5 Door\nManual transmission\nAir conditioning\nABS / Airbags\nPower steering / Radio-CD\n4 Adults\nLuggage: 2 Small + 1 Medium",
      ),
      array(
        'group_code' => __('GROUP S', 'jacana-luxe'),
        'title' => __('Toyota Corolla Quest sedan or similar', 'jacana-luxe'),
        'summary' => __('Comfort sedan option for couples or small families on road-based itineraries.', 'jacana-luxe'),
        'details' => "4 Door\nManual transmission\nAir conditioning\nABS / Airbags\nPower steering / Power mirrors / Power windows\nRadio-CD\n4 Adults, 1 Child\nLuggage: 2 Small + 1 Medium/1 Large",
      ),
      array(
        'group_code' => __('GROUP SU', 'jacana-luxe'),
        'title' => __('Toyota RAV4 SUV or similar', 'jacana-luxe'),
        'summary' => __('SUV comfort with more luggage capacity for flexible regional touring.', 'jacana-luxe'),
        'details' => "5 Door\nAutomatic transmission\nAir conditioning\nABS / Airbags\nPower steering / Power mirrors / Power windows\nRadio-CD\n5 Adults\nLuggage: 2 Large + 2 Medium",
      ),
      array(
        'group_code' => __('GROUP SV', 'jacana-luxe'),
        'title' => __('Toyota Fortuner Luxury 4x2 SUV or similar', 'jacana-luxe'),
        'summary' => __('Spacious SUV option for families and comfort-led road trips.', 'jacana-luxe'),
        'details' => "5 Door\nAutomatic transmission\nAir conditioning\nABS / Airbags\nPower steering / Power mirrors / Power windows\nRadio-CD\n5-7 Adults\nLuggage: 3 Large + 2 Medium",
      ),
      array(
        'group_code' => __('GROUP K', 'jacana-luxe'),
        'title' => __('VW Kombi 8 Seater or similar', 'jacana-luxe'),
        'summary' => __('Group transport option for families, small groups, and transfer-heavy itineraries.', 'jacana-luxe'),
        'details' => "5 Door\nManual transmission\nAir conditioning\nABS / Airbags\nPower steering / Power mirrors / Power windows\nRadio-CD\n8 Adults\nLuggage: 3 Large + 2 Medium",
      ),
      array(
        'group_code' => __('GROUP D', 'jacana-luxe'),
        'title' => __('Toyota Hilux 4x4 Double Cab or similar', 'jacana-luxe'),
        'summary' => __('Reliable 4x4 option for more rugged routes and self-drive adventures.', 'jacana-luxe'),
        'details' => "4 Door\nAuto/Manual transmission\nAir conditioning\nABS / Airbags\nPower steering / Power mirrors / Power windows\nRadio-CD\n4 Adults, 1 Child\nLuggage: 2 Small + 1 Medium/1 Large",
      ),
      array(
        'group_code' => __('GROUP M', 'jacana-luxe'),
        'title' => __('Toyota Quantum 14/16 Seater Bus or similar', 'jacana-luxe'),
        'summary' => __('Large group solution for tours, events, and coordinated transfers.', 'jacana-luxe'),
        'details' => "4 Door\nManual transmission\nAir conditioning\nABS / Airbags\nPower steering / Power mirrors / Power windows\nRadio-CD\n14/16 Adults\nLuggage: 3 Large + 2 Medium",
      ),
      array(
        'group_code' => __('GROUP SW', 'jacana-luxe'),
        'title' => __('Toyota Fortuner 2.8 GD-6 4x4 SUV or similar', 'jacana-luxe'),
        'summary' => __('Premium 4x4 SUV for comfort and off-road-capable Namibia routes.', 'jacana-luxe'),
        'details' => "5 Door\nAutomatic transmission\nAir conditioning\nABS / Airbags\nPower steering / Power mirrors / Power windows\nRadio-CD\n5-7 Adults\nLuggage: 3 Large + 2 Medium",
      ),
      array(
        'group_code' => __('GROUP DC2', 'jacana-luxe'),
        'title' => __('Toyota Hilux 4x4 with camping equipment (1-2 pax)', 'jacana-luxe'),
        'summary' => __('Camping-equipped 4x4 option built for self-drive travelers wanting flexibility and remote overnight stops.', 'jacana-luxe'),
        'details' => "Single/Double Cab (similar)\nAuto/Manual transmission\nAir conditioning\nABS / Airbags\nPower steering / Radio-CD\n4 Adults, 1 Child capacity\n1 Roof Tent\nCanopy included",
      ),
      array(
        'group_code' => __('GROUP DC4', 'jacana-luxe'),
        'title' => __('Toyota Hilux 4x4 with camping equipment (3-4 pax)', 'jacana-luxe'),
        'summary' => __('Camping-ready 4x4 configuration for small groups combining mobility and overnight independence.', 'jacana-luxe'),
        'details' => "Single/Double Cab (similar)\nAuto/Manual transmission\nAir conditioning\nABS / Airbags\nPower steering / Radio-CD\n4 Adults, 1 Child capacity\nRoof tent setup\nCanopy included",
      ),
    );
  }

  private function parse_lines($input) {
    return array_filter(array_map('trim', explode("\n", (string) $input)));
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $vehicles = !empty($settings['vehicles']) && is_array($settings['vehicles']) ? $settings['vehicles'] : array();

    if (empty($vehicles)) {
      return;
    }
    ?>
    <section class="section jacana-zoora-parity jacana-tours-widget jacana-car-rental-showcase">
      <div class="section-inner">
        <div class="section-header">
          <h2 class="jacana-reveal"><?php echo esc_html($settings['heading']); ?></h2>
          <p class="jacana-reveal" style="--jacana-delay: 120ms;"><?php echo esc_html($settings['intro']); ?></p>
        </div>

        <div class="jacana-rental-showcase-shell jacana-reveal" style="--jacana-delay: 180ms;" data-rental-showcase>
          <div class="jacana-rental-media-pane" aria-live="polite">
            <div class="jacana-rental-track" data-rental-track>
              <?php foreach ($vehicles as $index => $vehicle) :
                $image = !empty($vehicle['image']['url']) ? $vehicle['image']['url'] : \Elementor\Utils::get_placeholder_image_src();
                $is_active = 0 === $index ? ' is-active' : '';
                ?>
                <figure class="jacana-rental-slide<?php echo esc_attr($is_active); ?>" data-rental-slide>
                  <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($vehicle['title'] ?? __('Vehicle image', 'jacana-luxe')); ?>" loading="lazy">
                  <figcaption class="jacana-rental-slide-caption">
                    <?php if (!empty($vehicle['group_code'])) : ?>
                      <span class="jacana-rental-code"><?php echo esc_html($vehicle['group_code']); ?></span>
                    <?php endif; ?>
                    <strong><?php echo esc_html($vehicle['title'] ?? ''); ?></strong>
                  </figcaption>
                </figure>
              <?php endforeach; ?>
            </div>

            <div class="jacana-rental-controls">
              <button type="button" class="jacana-rental-nav jacana-rental-prev" data-rental-prev aria-label="<?php echo esc_attr__('Previous vehicle', 'jacana-luxe'); ?>">
                <span aria-hidden="true">&#8249;</span>
              </button>
              <div class="jacana-rental-dots" data-rental-dots>
                <?php foreach ($vehicles as $index => $vehicle) : ?>
                  <button
                    type="button"
                    class="jacana-rental-dot<?php echo 0 === $index ? ' is-active' : ''; ?>"
                    data-rental-dot
                    data-index="<?php echo esc_attr($index); ?>"
                    aria-label="<?php echo esc_attr(sprintf(__('Show %s', 'jacana-luxe'), $vehicle['title'] ?? __('vehicle', 'jacana-luxe'))); ?>"
                    aria-pressed="<?php echo 0 === $index ? 'true' : 'false'; ?>"
                  ></button>
                <?php endforeach; ?>
              </div>
              <button type="button" class="jacana-rental-nav jacana-rental-next" data-rental-next aria-label="<?php echo esc_attr__('Next vehicle', 'jacana-luxe'); ?>">
                <span aria-hidden="true">&#8250;</span>
              </button>
            </div>
          </div>

          <div class="jacana-rental-details-pane">
            <?php foreach ($vehicles as $index => $vehicle) :
              $is_active = 0 === $index ? ' is-active' : '';
              $cta = !empty($vehicle['cta_link']) ? $vehicle['cta_link'] : array();
              $lines = $this->parse_lines($vehicle['details'] ?? '');
              $cta_label = !empty($vehicle['cta_label']) ? $vehicle['cta_label'] : ($settings['default_cta_label'] ?? __('View More Details', 'jacana-luxe'));
              ?>
              <article class="jacana-rental-details-card<?php echo esc_attr($is_active); ?>" data-rental-detail data-service-name="<?php echo esc_attr($vehicle['title'] ?? __('Car rental vehicle', 'jacana-luxe')); ?>">
                <div class="jacana-rental-details-kicker"><?php echo esc_html($settings['panel_kicker']); ?></div>
                <header class="jacana-rental-details-header">
                  <?php if (!empty($vehicle['group_code'])) : ?>
                    <span class="jacana-rental-code jacana-rental-code-inline"><?php echo esc_html($vehicle['group_code']); ?></span>
                  <?php endif; ?>
                  <h3><?php echo esc_html($vehicle['title'] ?? ''); ?></h3>
                </header>

                <?php if (!empty($vehicle['summary'])) : ?>
                  <p class="jacana-rental-summary"><?php echo esc_html($vehicle['summary']); ?></p>
                <?php endif; ?>

                <?php if (!empty($lines)) : ?>
                  <ul class="jacana-rental-specs">
                    <?php foreach ($lines as $line) : ?>
                      <li><?php echo esc_html($line); ?></li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>

                <div class="jacana-rental-actions">
                  <span class="jacana-rental-rate-pill"><?php echo esc_html__('Rates on request', 'jacana-luxe'); ?></span>
                  <a class="button button-primary jacana-rental-detail-link" data-jacana-service="<?php echo esc_attr($vehicle['title'] ?? __('Car Rentals', 'jacana-luxe')); ?>" data-jacana-ai-surface="vehicle" data-jacana-ai-flow="vehicle" data-jacana-widget="jacana_car_rental_showcase" data-jacana-vehicle="<?php echo esc_attr($vehicle['title'] ?? __('Car Rentals', 'jacana-luxe')); ?>" href="<?php echo esc_url($cta['url'] ?? '#'); ?>"<?php echo !empty($cta['is_external']) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
                    <?php echo esc_html($cta_label); ?>
                  </a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>
    <?php
  }
}
