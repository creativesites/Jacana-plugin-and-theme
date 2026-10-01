<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Car_Rental_Premium_Hero extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_car_rental_premium_hero';
  }

  public function get_title() {
    return __('Car Rental Premium Hero', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-banner';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('kicker', array(
      'label' => __('Kicker', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Car Rentals', 'jacana-luxe'),
    ));

    $this->add_control('title', array(
      'label' => __('Title', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Premium vehicle options for every Namibia route', 'jacana-luxe'),
    ));

    $this->add_control('copy', array(
      'label' => __('Copy', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Choose from sedans, SUVs, 4x4s, campers and group vehicles. We help you match the right vehicle to your route, group size and travel style. Rates are always provided on request.', 'jacana-luxe'),
    ));

    $this->add_control('background', array(
      'label' => __('Background Image', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
    ));

    $this->add_control('highlights', array(
      'label' => __('Highlights (one per line)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => "Sedans, SUVs and 4x4s\nCamping-equipped vehicles available\nAirport and city pickup coordination\nRates on request with tailored recommendation",
    ));

    $this->add_control('primary_cta_label', array(
      'label' => __('Primary CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Request a Vehicle Quote', 'jacana-luxe'),
    ));

    $this->add_control('primary_cta_link', array(
      'label' => __('Primary CTA Link', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'placeholder' => '#',
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
      'default' => __('4x4 + SUV', 'jacana-luxe'),
    ));
    $this->add_control('stat_one_label', array(
      'label' => __('Stat 1 Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Adventure-ready options', 'jacana-luxe'),
    ));
    $this->add_control('stat_two_value', array(
      'label' => __('Stat 2 Value', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('1-16 Pax', 'jacana-luxe'),
    ));
    $this->add_control('stat_two_label', array(
      'label' => __('Stat 2 Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Vehicle size range', 'jacana-luxe'),
    ));
    $this->add_control('stat_three_value', array(
      'label' => __('Stat 3 Value', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('On Request', 'jacana-luxe'),
    ));
    $this->add_control('stat_three_label', array(
      'label' => __('Stat 3 Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Tailored pricing', 'jacana-luxe'),
    ));

    $this->end_controls_section();
  }

  private function render_highlights($input) {
    $items = array_filter(array_map('trim', explode("\n", (string) $input)));
    if (empty($items)) {
      return;
    }

    echo '<ul class="jacana-rental-hero-list">';
    foreach ($items as $item) {
      echo '<li>' . esc_html($item) . '</li>';
    }
    echo '</ul>';
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $bg = !empty($settings['background']['url']) ? $settings['background']['url'] : '';
    $primary = !empty($settings['primary_cta_link']) ? $settings['primary_cta_link'] : array();
    $secondary = !empty($settings['secondary_cta_link']) ? $settings['secondary_cta_link'] : array();
    ?>
    <section class="section jacana-zoora-parity jacana-rental-hero-premium"<?php echo $bg ? ' style="--jacana-rental-hero-bg:url(' . esc_url($bg) . ');"' : ''; ?>>
      <div class="section-inner">
        <div class="jacana-rental-hero-shell">
          <div class="jacana-rental-hero-copy card jacana-reveal">
            <div class="hero-kicker"><?php echo esc_html($settings['kicker']); ?></div>
            <h1><?php echo esc_html($settings['title']); ?></h1>
            <p><?php echo esc_html($settings['copy']); ?></p>
            <?php $this->render_highlights($settings['highlights'] ?? ''); ?>
            <div class="jacana-rental-hero-actions">
              <a class="button button-primary" href="<?php echo esc_url($primary['url'] ?? '#'); ?>"<?php echo !empty($primary['is_external']) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
                <?php echo esc_html($settings['primary_cta_label']); ?>
              </a>
              <a class="button button-ghost jacana-button-soft" href="<?php echo esc_url($secondary['url'] ?? '#'); ?>"<?php echo !empty($secondary['is_external']) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
                <?php echo esc_html($settings['secondary_cta_label']); ?>
              </a>
            </div>
          </div>
          <div class="jacana-rental-hero-side jacana-reveal jacana-reveal-right" style="--jacana-delay: 140ms;">
            <div class="jacana-rental-hero-stage">
              <div class="jacana-rental-hero-stage-label"><?php echo esc_html__('Namibia-ready fleet support', 'jacana-luxe'); ?></div>
              <div class="jacana-rental-hero-glow"></div>
            </div>
            <div class="jacana-rental-hero-stats">
              <div class="jacana-rental-hero-stat"><strong><?php echo esc_html($settings['stat_one_value']); ?></strong><span><?php echo esc_html($settings['stat_one_label']); ?></span></div>
              <div class="jacana-rental-hero-stat"><strong><?php echo esc_html($settings['stat_two_value']); ?></strong><span><?php echo esc_html($settings['stat_two_label']); ?></span></div>
              <div class="jacana-rental-hero-stat"><strong><?php echo esc_html($settings['stat_three_value']); ?></strong><span><?php echo esc_html($settings['stat_three_label']); ?></span></div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <?php
  }
}
