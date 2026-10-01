<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Tours_Dates_Price extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_tours_dates_price';
  }

  public function get_title() {
    return __('Tours Dates & Price', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-price-table';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Dates & Price', 'jacana-luxe'),
    ));

    $this->add_control('copy', array(
      'label' => __('Copy', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Tour dates are arranged according to you and prices are calculated based on the tour type, for example the type of car, accommodation, travel duration etc..', 'jacana-luxe'),
    ));

    $this->add_control('cta_label', array(
      'label' => __('CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Request a quote', 'jacana-luxe'),
    ));

    $this->add_control('cta_link', array(
      'label' => __('CTA Link', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'placeholder' => 'https://',
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $cta = !empty($settings['cta_link']) ? $settings['cta_link'] : array();
    $cta_url = !empty($cta['url']) ? $cta['url'] : '#';
    $is_booking_link = !empty($cta_url) && false !== strpos(untrailingslashit($cta_url), untrailingslashit(home_url('/booking')));
    ?>
    <section class="section jacana-zoora-parity jacana-tours-widget jacana-tours-dates-price">
      <div class="section-inner">
        <div class="card jacana-reveal">
          <h2 class="jacana-reveal"><?php echo esc_html($settings['heading']); ?></h2>
          <p class="jacana-reveal" style="--jacana-delay: 120ms;"><?php echo esc_html($settings['copy']); ?></p>
          <?php if (!empty($cta['is_external']) && !$is_booking_link) : ?>
            <a class="button button-primary" href="<?php echo esc_url($cta_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($settings['cta_label']); ?></a>
          <?php else : ?>
            <a class="button button-primary" data-jacana-booking-modal="true" data-jacana-widget="jacana_tours_dates_price" data-jacana-service="tailor_made" href="#"><?php echo esc_html($settings['cta_label']); ?></a>
          <?php endif; ?>
        </div>
      </div>
    </section>
    <?php
  }
}
