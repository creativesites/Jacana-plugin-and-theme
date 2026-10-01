<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Car_Rental_Fleet_Explorer extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_car_rental_fleet_explorer';
  }

  public function get_title() {
    return __('Car Rental Fleet Explorer', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-gallery-grid';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Explore the Fleet', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label' => __('Intro', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Compare vehicle options by category and route style. Select a vehicle to view detailed specifications and request a tailored quote.', 'jacana-luxe'),
    ));

    $this->add_control('contact_url', array(
      'label'   => __('Contact Page URL', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => '/contact/',
      'description' => __('URL for the "Contact us now" button shown on each vehicle.', 'jacana-luxe'),
    ));

    $this->add_control('filters_label', array(
      'label' => __('Filters Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Vehicle Categories', 'jacana-luxe'),
    ));

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('group_code', array(
      'label' => __('Group Code', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('GROUP H', 'jacana-luxe'),
    ));
    $repeater->add_control('category', array(
      'label' => __('Category', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::SELECT,
      'default' => 'economy',
      'options' => array(
        'economy' => __('Economy', 'jacana-luxe'),
        'sedan' => __('Sedan', 'jacana-luxe'),
        'suv' => __('SUV', 'jacana-luxe'),
        '4x4' => __('4x4', 'jacana-luxe'),
        'camping' => __('Camping 4x4', 'jacana-luxe'),
        'group' => __('Group Transport', 'jacana-luxe'),
      ),
    ));
    $repeater->add_control('title', array(
      'label' => __('Vehicle Name', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
    ));
    $repeater->add_control('summary', array(
      'label' => __('Summary', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'rows' => 3,
    ));
    $repeater->add_control('image', array(
      'label' => __('Vehicle Image', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
    ));
    $repeater->add_control('doors', array(
      'label' => __('Doors', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => '5 Door',
    ));
    $repeater->add_control('transmission', array(
      'label' => __('Transmission', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Manual', 'jacana-luxe'),
    ));
    $repeater->add_control('ac', array(
      'label' => __('Air Conditioning', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Yes', 'jacana-luxe'),
    ));
    $repeater->add_control('passengers', array(
      'label' => __('Passengers', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('4 Adults', 'jacana-luxe'),
    ));
    $repeater->add_control('luggage', array(
      'label' => __('Luggage', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('2 Small + 1 Medium', 'jacana-luxe'),
    ));
    $repeater->add_control('core_features', array(
      'label' => __('Core Features (one per line)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'rows' => 5,
      'default' => "ABS\nAirbags\nPower steering\nRadio/CD",
    ));
    $repeater->add_control('route_fit', array(
      'label' => __('Best For', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'rows' => 3,
      'default' => __('Ideal for a flexible Namibia road trip based on route, luggage and group size.', 'jacana-luxe'),
    ));
    $repeater->add_control('extras', array(
      'label' => __('Optional Extras (one per line)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'rows' => 4,
      'default' => "GPS Navigation\nChild Safety Seats\nCooler Box",
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
        'group_code' => 'GROUP SU',
        'category' => 'suv',
        'title' => __('Toyota Rav 4 SUV', 'jacana-luxe'),
        'summary' => __('Comfort SUV for mixed routes, added luggage capacity and easy touring.', 'jacana-luxe'),
        'doors' => '5 Door',
        'transmission' => __('Automatic', 'jacana-luxe'),
        'ac' => __('Air conditioning', 'jacana-luxe'),
        'passengers' => __('5 Adults', 'jacana-luxe'),
        'luggage' => __('2 Large + 2 Medium', 'jacana-luxe'),
        'core_features' => "ABS\nAirbags\nPower steering\nPower windows\nPower mirrors\nRadio/CD",
      ),
      array(
        'group_code' => 'GROUP K',
        'category' => 'group',
        'title' => __('VW Kombi 8 Seater', 'jacana-luxe'),
        'summary' => __('Practical group transport for families and small travel groups.', 'jacana-luxe'),
        'doors' => '5 Door',
        'transmission' => __('Manual', 'jacana-luxe'),
        'ac' => __('Air conditioning', 'jacana-luxe'),
        'passengers' => __('8 Adults', 'jacana-luxe'),
        'luggage' => __('3 Large + 2 Medium', 'jacana-luxe'),
        'core_features' => "ABS\nAirbags\nPower steering\nPower windows\nPower mirrors\nRadio/CD",
      ),
      array(
        'group_code' => 'GROUP D',
        'category' => '4x4',
        'title' => __('Toyota Hilux 4x4 Double Cab', 'jacana-luxe'),
        'summary' => __('Reliable 4x4 for tougher routes and classic Namibia self-drive adventures.', 'jacana-luxe'),
        'doors' => '4 Door',
        'transmission' => __('Auto/Manual', 'jacana-luxe'),
        'ac' => __('Air conditioning', 'jacana-luxe'),
        'passengers' => __('4 Adults, 1 Child', 'jacana-luxe'),
        'luggage' => __('2 Small + 1 Medium/1 Large', 'jacana-luxe'),
        'core_features' => "ABS\nAirbags\nPower steering\nPower windows\nPower mirrors\nRadio/CD",
      ),
      array(
        'group_code' => 'GROUP M',
        'category' => 'group',
        'title' => __('Toyota Quantum 14/16 Seater Bus', 'jacana-luxe'),
        'summary' => __('Large-group vehicle for tours, transfers and coordinated travel.', 'jacana-luxe'),
        'doors' => '4 Door',
        'transmission' => __('Manual', 'jacana-luxe'),
        'ac' => __('Air conditioning', 'jacana-luxe'),
        'passengers' => __('14/16 Adults', 'jacana-luxe'),
        'luggage' => __('3 Large + 2 Medium', 'jacana-luxe'),
        'core_features' => "ABS\nAirbags\nPower steering\nPower windows\nPower mirrors\nRadio/CD",
      ),
      array(
        'group_code' => 'GROUP SW',
        'category' => '4x4',
        'title' => __('Toyota Fortuner 4x4 SUV', 'jacana-luxe'),
        'summary' => __('Premium 4x4 SUV for comfortable off-road-capable travel.', 'jacana-luxe'),
        'doors' => '5 Door',
        'transmission' => __('Automatic', 'jacana-luxe'),
        'ac' => __('Air conditioning', 'jacana-luxe'),
        'passengers' => __('5-7 Adults', 'jacana-luxe'),
        'luggage' => __('3 Large + 2 Medium', 'jacana-luxe'),
        'core_features' => "ABS\nAirbags\nPower steering\nPower windows\nPower mirrors\nRadio/CD",
      ),
      array(
        'group_code' => 'GROUP DC2',
        'category' => 'camping',
        'title' => __('Toyota Hilux 4x4 (Camping Equip) 1-4 PAX', 'jacana-luxe'),
        'summary' => __('Camping-equipped 4x4 setup for 1-4 travelers wanting flexible overnight routes.', 'jacana-luxe'),
        'doors' => '4 Door',
        'transmission' => __('Automatic/Manual', 'jacana-luxe'),
        'ac' => __('Air conditioning', 'jacana-luxe'),
        'passengers' => __('4 Adults, 1 Child (vehicle capacity)', 'jacana-luxe'),
        'luggage' => __('Camping setup incl. canopy', 'jacana-luxe'),
        'core_features' => "ABS\nAirbags\nPower steering\nRadio/CD\n1 Roof Tent\nCanopy included",
        'route_fit' => __('Ideal for self-drive camping routes for 1-4 passengers.', 'jacana-luxe'),
      ),
    );
  }

  private function parse_lines($value) {
    return array_filter(array_map('trim', explode("\n", (string) $value)));
  }

  private function get_category_label($value) {
    $labels = array(
      'economy' => __('Economy', 'jacana-luxe'),
      'sedan' => __('Sedan', 'jacana-luxe'),
      'suv' => __('SUV', 'jacana-luxe'),
      '4x4' => __('4x4', 'jacana-luxe'),
      'camping' => __('Camping 4x4', 'jacana-luxe'),
      'group' => __('Group Transport', 'jacana-luxe'),
    );
    return isset($labels[$value]) ? $labels[$value] : ucfirst((string) $value);
  }

  protected function render() {
    $settings    = $this->get_settings_for_display();
    $vehicles    = !empty($settings['vehicles']) && is_array($settings['vehicles']) ? $settings['vehicles'] : array();
    $contact_url = !empty($settings['contact_url']) ? esc_url($settings['contact_url']) : '/contact/';
    if (empty($vehicles)) {
      return;
    }

    $categories = array();
    foreach ($vehicles as $vehicle) {
      $key = !empty($vehicle['category']) ? (string) $vehicle['category'] : 'other';
      if (!isset($categories[$key])) {
        $categories[$key] = $this->get_category_label($key);
      }
    }
    ?>
    <section class="section jacana-zoora-parity jacana-tours-widget jacana-rental-fleet-explorer" data-rental-fleet>
      <div class="section-inner">
        <div class="section-header">
          <h2 class="jacana-reveal"><?php echo esc_html($settings['heading']); ?></h2>
          <p class="jacana-reveal" style="--jacana-delay: 120ms;"><?php echo esc_html($settings['intro']); ?></p>
        </div>

        <div class="jacana-rental-fleet-filters jacana-reveal" style="--jacana-delay: 160ms;">
          <span class="jacana-rental-filters-label"><?php echo esc_html($settings['filters_label']); ?></span>
          <div class="jacana-rental-filters-list">
            <button type="button" class="jacana-rental-filter is-active" data-rental-filter="all" aria-pressed="true"><?php echo esc_html__('All', 'jacana-luxe'); ?></button>
            <?php foreach ($categories as $key => $label) : ?>
              <button type="button" class="jacana-rental-filter" data-rental-filter="<?php echo esc_attr($key); ?>" aria-pressed="false"><?php echo esc_html($label); ?></button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="jacana-rental-fleet-shell">
          <div class="jacana-rental-fleet-list" data-rental-fleet-list>
            <?php foreach ($vehicles as $index => $vehicle) :
              $img = !empty($vehicle['image']['url']) ? $vehicle['image']['url'] : \Elementor\Utils::get_placeholder_image_src();
              $category = !empty($vehicle['category']) ? (string) $vehicle['category'] : 'other';
              ?>
              <button
                type="button"
                class="jacana-rental-fleet-item<?php echo 0 === $index ? ' is-active' : ''; ?>"
                data-rental-item
                data-rental-index="<?php echo esc_attr($index); ?>"
                data-rental-category="<?php echo esc_attr($category); ?>"
                aria-pressed="<?php echo 0 === $index ? 'true' : 'false'; ?>"
              >
                <span class="jacana-rental-fleet-thumb">
                  <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($vehicle['title'] ?? __('Vehicle', 'jacana-luxe')); ?>" loading="lazy">
                </span>
                <span class="jacana-rental-fleet-item-copy">
                  <?php if (!empty($vehicle['group_code'])) : ?>
                    <span class="jacana-rental-fleet-code"><?php echo esc_html($vehicle['group_code']); ?></span>
                  <?php endif; ?>
                  <strong><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $vehicle['title'] ?? '', array('area' => 'fleet_title'))); ?></strong>
                  <?php if (!empty($vehicle['summary'])) : ?>
                    <small><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $vehicle['summary'], array('area' => 'fleet_summary'))); ?></small>
                  <?php endif; ?>
                </span>
              </button>
            <?php endforeach; ?>
          </div>

          <div class="jacana-rental-fleet-detail-wrap">
            <?php foreach ($vehicles as $index => $vehicle) :
              $img      = !empty($vehicle['image']['url']) ? $vehicle['image']['url'] : \Elementor\Utils::get_placeholder_image_src();
              $features = $this->parse_lines($vehicle['core_features'] ?? '');
              $extras   = $this->parse_lines($vehicle['extras'] ?? '');
              ?>
              <article class="jacana-rental-fleet-detail<?php echo 0 === $index ? ' is-active' : ''; ?>" data-rental-detail data-rental-index="<?php echo esc_attr($index); ?>">
                <div class="jacana-rental-fleet-detail-media">
                  <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($vehicle['title'] ?? __('Vehicle image', 'jacana-luxe')); ?>" loading="lazy">
                  <div class="jacana-rental-fleet-detail-overlay"></div>
                  <div class="jacana-rental-fleet-detail-media-meta">
                    <?php if (!empty($vehicle['group_code'])) : ?>
                      <span class="jacana-rental-code"><?php echo esc_html($vehicle['group_code']); ?></span>
                    <?php endif; ?>
                    <h3><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $vehicle['title'] ?? '', array('area' => 'fleet_title'))); ?></h3>
                    <p><?php echo esc_html($this->get_category_label($vehicle['category'] ?? '')); ?></p>
                  </div>
                </div>

                <div class="jacana-rental-fleet-detail-body">
                  <?php if (!empty($vehicle['summary'])) : ?>
                    <p class="jacana-rental-fleet-summary"><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $vehicle['summary'], array('area' => 'fleet_summary'))); ?></p>
                  <?php endif; ?>

                  <div class="jacana-rental-fleet-spec-grid">
                    <div class="jacana-rental-fleet-spec"><span><?php echo esc_html__('Doors', 'jacana-luxe'); ?></span><strong><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $vehicle['doors'] ?? '-', array('area' => 'fleet_spec'))); ?></strong></div>
                    <div class="jacana-rental-fleet-spec"><span><?php echo esc_html__('Transmission', 'jacana-luxe'); ?></span><strong><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $vehicle['transmission'] ?? '-', array('area' => 'fleet_spec'))); ?></strong></div>
                    <div class="jacana-rental-fleet-spec"><span><?php echo esc_html__('Air Con', 'jacana-luxe'); ?></span><strong><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $vehicle['ac'] ?? '-', array('area' => 'fleet_spec'))); ?></strong></div>
                    <div class="jacana-rental-fleet-spec"><span><?php echo esc_html__('Passengers', 'jacana-luxe'); ?></span><strong><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $vehicle['passengers'] ?? '-', array('area' => 'fleet_spec'))); ?></strong></div>
                    <div class="jacana-rental-fleet-spec jacana-rental-fleet-spec-wide"><span><?php echo esc_html__('Luggage', 'jacana-luxe'); ?></span><strong><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $vehicle['luggage'] ?? '-', array('area' => 'fleet_spec'))); ?></strong></div>
                  </div>

                  <div class="jacana-rental-fleet-columns">
                    <div class="jacana-rental-fleet-panel">
                      <h4><?php echo esc_html__('Core Features', 'jacana-luxe'); ?></h4>
                      <ul>
                        <?php foreach ($features as $line) : ?>
                          <li><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $line, array('area' => 'fleet_features'))); ?></li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                    <div class="jacana-rental-fleet-panel">
                      <h4><?php echo esc_html__('Optional Extras', 'jacana-luxe'); ?></h4>
                      <ul>
                        <?php foreach ($extras as $line) : ?>
                          <li><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $line, array('area' => 'fleet_extras'))); ?></li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                  </div>

                  

                  <div class="jacana-rental-fleet-actions">
                    <span class="jacana-rental-rate-pill"><?php echo esc_html__('Rates on request', 'jacana-luxe'); ?></span>
                    <a class="button button-primary jacana-rental-contact-cta"
                       href="<?php echo $contact_url; ?>">
                      <?php echo esc_html__('Contact us now', 'jacana-luxe'); ?>
                      <span aria-hidden="true">&rarr;</span>
                    </a>
                  </div>
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
