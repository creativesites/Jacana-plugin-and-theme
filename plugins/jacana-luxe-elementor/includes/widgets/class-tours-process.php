<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Tours_Process extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_tours_process';
  }

  public function get_title() {
    return __('Tours Process', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-flow';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('How does it work?', 'jacana-luxe'),
    ));

    $this->add_control('copy', array(
      'label' => __('Copy', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Tailor-made tours can be individual or group tours. We plan your tour according to your wishes. Get in touch with us and we will schedule a free consultation video call with you. There we will get to know your taste for adventure and give you a first impression of our beautiful destinations in Namibia. Afterwards we will create a first tour itinerary overview and you can make suggestions for changes or of course accept it right away and we will prepare everything for you. You will pay with our easy online payment method without extra charges from your bank.', 'jacana-luxe'),
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    ?>
    <section class="section jacana-zoora-parity jacana-tours-widget jacana-tours-process">
      <div class="section-inner split">
        <div>
          <h2 class="jacana-reveal"><?php echo esc_html($settings['heading']); ?></h2>
          <p class="jacana-reveal" style="--jacana-delay: 120ms;"><?php echo esc_html($settings['copy']); ?></p>
        </div>
        <div class="card jacana-reveal jacana-reveal-right" style="--jacana-delay: 220ms;">
          <h3><?php echo esc_html__('What to expect', 'jacana-luxe'); ?></h3>
          <ul>
            <li><?php echo esc_html__('Free consultation call', 'jacana-luxe'); ?></li>
            <li><?php echo esc_html__('Custom itinerary proposal', 'jacana-luxe'); ?></li>
            <li><?php echo esc_html__('Flexible refinements', 'jacana-luxe'); ?></li>
            <li><?php echo esc_html__('Secure online payment', 'jacana-luxe'); ?></li>
          </ul>
        </div>
      </div>
    </section>
    <?php
  }
}
