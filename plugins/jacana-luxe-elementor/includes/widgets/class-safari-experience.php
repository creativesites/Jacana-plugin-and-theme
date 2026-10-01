<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Safari_Experience extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_safari_experience';
  }

  public function get_title() {
    return __('Safari Experience', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-sitemap';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Safari Experience', 'jacana-luxe'),
    ));

    $this->add_control('lead', array(
      'label' => __('Lead', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Game Drive in Etosha National Park. Travel in an open vehicle with an experienced guide on the lookout for lions, elephants, zebras and more.', 'jacana-luxe'),
    ));

    $this->add_control('copy', array(
      'label' => __('Copy', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('The Etosha National Park spans nearly 22,912 km² and is home to over 100 mammal species and 340 bird species.', 'jacana-luxe'),
    ));

    $this->add_control('card_title', array(
      'label' => __('Card Title', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Etosha Highlights', 'jacana-luxe'),
    ));

    $this->add_control('card_copy', array(
      'label' => __('Card Copy', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('The name Etosha means “big, white place.” Water holes sustain wildlife year-round, creating unforgettable viewing moments.', 'jacana-luxe'),
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    ?>
    <section class="section jacana-zoora-parity jacana-tours-widget jacana-safari-experience" id="safari">
      <div class="section-inner split">
        <div>
          <h2 class="jacana-reveal"><?php echo esc_html($settings['heading']); ?></h2>
          <p class="jacana-reveal" style="--jacana-delay: 120ms;"><strong><?php echo esc_html($settings['lead']); ?></strong></p>
          <p class="jacana-reveal" style="--jacana-delay: 180ms;"><?php echo esc_html($settings['copy']); ?></p>
        </div>
        <div class="card jacana-reveal jacana-reveal-right" style="--jacana-delay: 240ms;">
          <h3><?php echo esc_html($settings['card_title']); ?></h3>
          <p><?php echo esc_html($settings['card_copy']); ?></p>
        </div>
      </div>
    </section>
    <?php
  }
}
