<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Booking_Premium_Hero extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_booking_premium_hero';
  }

  public function get_title() {
    return __('Booking Premium Hero', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-form-horizontal';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('kicker', array(
      'label' => __('Kicker', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Booking', 'jacana-luxe'),
    ));

    $this->add_control('title', array(
      'label' => __('Title', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Request your Namibia trip plan in one place', 'jacana-luxe'),
    ));

    $this->add_control('copy', array(
      'label' => __('Copy', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Use this page to request a tailored quote for tours, car rentals, airport transfers, accommodation support, flights, or a combined itinerary. Tell us what you need and we will prepare a non-binding proposal.', 'jacana-luxe'),
    ));

    $this->add_control('background', array(
      'label' => __('Background Image', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
    ));

    $this->add_control('highlights', array(
      'label' => __('Highlights (one per line)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => "One request for multiple services\nTailored, non-binding proposals\nSupport for routes, dates and logistics\nRates on request based on your plan",
    ));

    $this->add_control('service_chips', array(
      'label' => __('Service Chips (one per line)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => "Tailor-made Tours\nCar Rentals\nAirport Transfers\nGame Drives\nAccommodation\nFlights",
    ));

    $this->add_control('primary_cta_label', array(
      'label' => __('Primary CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Start Your Booking Request', 'jacana-luxe'),
    ));

    $this->add_control('primary_cta_link', array(
      'label' => __('Primary CTA Link', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'placeholder' => '#booking-form',
    ));

    $this->add_control('secondary_cta_label', array(
      'label' => __('Secondary CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Talk to Us First', 'jacana-luxe'),
    ));

    $this->add_control('secondary_cta_link', array(
      'label' => __('Secondary CTA Link', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'placeholder' => '#',
    ));

    $this->add_control('stat_one_value', array(
      'label' => __('Stat 1 Value', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('24h', 'jacana-luxe'),
    ));
    $this->add_control('stat_one_label', array(
      'label' => __('Stat 1 Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Typical response window', 'jacana-luxe'),
    ));
    $this->add_control('stat_two_value', array(
      'label' => __('Stat 2 Value', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Multi-Service', 'jacana-luxe'),
    ));
    $this->add_control('stat_two_label', array(
      'label' => __('Stat 2 Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('One request can combine services', 'jacana-luxe'),
    ));
    $this->add_control('stat_three_value', array(
      'label' => __('Stat 3 Value', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Non-binding', 'jacana-luxe'),
    ));
    $this->add_control('stat_three_label', array(
      'label' => __('Stat 3 Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Quote first, then confirm', 'jacana-luxe'),
    ));

    $this->end_controls_section();
  }

  private function parse_lines($input) {
    return array_values(array_filter(array_map('trim', explode("\n", (string) $input))));
  }

  private function render_list($items, $class_name) {
    if (empty($items)) {
      return;
    }

    echo '<ul class="' . esc_attr($class_name) . '">';
    foreach ($items as $item) {
      echo '<li>' . esc_html($item) . '</li>';
    }
    echo '</ul>';
  }

  private function render_link($label, $link, $class_name) {
    $url = !empty($link['url']) ? $link['url'] : '#';
    $attrs = !empty($link['is_external']) ? ' target="_blank" rel="noopener noreferrer"' : '';
    echo '<a class="' . esc_attr($class_name) . '" href="' . esc_url($url) . '"' . $attrs . '>' . esc_html($label) . '</a>';
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $bg = !empty($settings['background']['url']) ? $settings['background']['url'] : '';
    $highlights = $this->parse_lines($settings['highlights'] ?? '');
    $chips = $this->parse_lines($settings['service_chips'] ?? '');
    ?>
    <section class="section jacana-zoora-parity jacana-booking-hero"<?php echo $bg ? ' style="--jacana-booking-hero-bg:url(' . esc_url($bg) . ');"' : ''; ?>>
      <div class="section-inner">
        <div class="jacana-booking-hero-shell">
          <div class="jacana-booking-hero-copy card jacana-reveal">
            <div class="hero-kicker"><?php echo esc_html($settings['kicker']); ?></div>
            <h1 style="color: #f7f2e8;"><?php echo esc_html($settings['title']); ?></h1>
            <p><?php echo esc_html($settings['copy']); ?></p>
            <?php $this->render_list($highlights, 'jacana-booking-hero-list'); ?>
            <div class="jacana-booking-hero-actions">
              <?php $this->render_link($settings['primary_cta_label'] ?? __('Start your request', 'jacana-luxe'), $settings['primary_cta_link'] ?? array(), 'button button-primary'); ?>
              <?php $this->render_link($settings['secondary_cta_label'] ?? __('Talk to us first', 'jacana-luxe'), $settings['secondary_cta_link'] ?? array(), 'button button-ghost jacana-button-soft'); ?>
            </div>
          </div>

          <div class="jacana-booking-hero-side jacana-reveal jacana-reveal-right" style="--jacana-delay: 140ms;">
            <div class="jacana-booking-hero-side-card">
              <div class="jacana-booking-hero-side-head">
                <span><?php echo esc_html__('What can we prepare for you?', 'jacana-luxe'); ?></span>
                <strong><?php echo esc_html__('Booking Request Studio', 'jacana-luxe'); ?></strong>
              </div>
              <?php if (!empty($chips)) : ?>
                <div class="jacana-booking-service-chips" aria-label="<?php echo esc_attr__('Bookable services', 'jacana-luxe'); ?>">
                  <?php foreach ($chips as $chip) : ?>
                    <span><?php echo esc_html($chip); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <div class="jacana-booking-hero-stats">
                <div class="jacana-booking-hero-stat"><strong><?php echo esc_html($settings['stat_one_value']); ?></strong><span><?php echo esc_html($settings['stat_one_label']); ?></span></div>
                <div class="jacana-booking-hero-stat"><strong><?php echo esc_html($settings['stat_two_value']); ?></strong><span><?php echo esc_html($settings['stat_two_label']); ?></span></div>
                <div class="jacana-booking-hero-stat"><strong><?php echo esc_html($settings['stat_three_value']); ?></strong><span><?php echo esc_html($settings['stat_three_label']); ?></span></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <?php
  }
}
