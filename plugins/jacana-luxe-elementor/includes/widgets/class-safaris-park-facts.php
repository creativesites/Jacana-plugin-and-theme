<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Safaris_Park_Facts extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_safaris_park_facts';
  }

  public function get_title() {
    return __('Safaris Park Facts', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-info-circle';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Etosha National Park', 'jacana-luxe'),
    ));

    $this->add_control('copy', array(
      'label' => __('Copy', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('The Etosha National Park is a nature conservation area in northern Namibia and is one of the most significant game reserves in Africa. In Namibia it is by far the best known and most important national park. Today the park covers an area of nearly 22,912 km² and is completely fenced for the protection of the animals.', 'jacana-luxe'),
    ));

    $this->add_control('meaning', array(
      'label' => __('Name Meaning', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('The name Etosha is derived from the Oshiwambo and means “big, white place”. More than 100 mammal species and 340 different species of birds are found in the park.', 'jacana-luxe'),
    ));

    $this->add_control('water', array(
      'label' => __('Water Holes', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Due to the fencing of the Etosha National Park the animals living in the park were depending on the water and food resources found within the fence. Thus the water supply is granted by water holes, some of natural origin some artificial.', 'jacana-luxe'),
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    ?>
    <section class="section">
      <div class="section-inner">
        <div class="section-header">
          <h2><?php echo esc_html($settings['heading']); ?></h2>
        </div>
        <div class="card-grid">
          <div class="card">
            <p><?php echo esc_html($settings['copy']); ?></p>
          </div>
          <div class="card">
            <p><?php echo esc_html($settings['meaning']); ?></p>
          </div>
          <div class="card">
            <p><?php echo esc_html($settings['water']); ?></p>
          </div>
        </div>
      </div>
    </section>
    <?php
  }
}
