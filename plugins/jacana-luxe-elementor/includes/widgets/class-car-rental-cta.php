<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Car_Rental_Cta extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_car_rental_cta';
  }

  public function get_title() {
    return __('Car Rental CTA', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-call-to-action';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Book your vehicle', 'jacana-luxe'),
    ));

    $this->add_control('copy', array(
      'label' => __('Copy', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Tell us your dates and itinerary so we can recommend the right vehicle.', 'jacana-luxe'),
    ));

    $this->add_control('cta_label', array(
      'label' => __('CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Request availability', 'jacana-luxe'),
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
    $cta = $settings['cta_link'];
    ?>
    <section class="section dark">
      <div class="section-inner">
        <div class="section-header">
          <h2><?php echo esc_html($settings['heading']); ?></h2>
          <p><?php echo esc_html($settings['copy']); ?></p>
        </div>
        <a class="button button-primary" href="<?php echo esc_url($cta['url'] ?? '#'); ?>"<?php echo !empty($cta['is_external']) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html($settings['cta_label']); ?></a>
      </div>
    </section>
    <?php
  }
}
