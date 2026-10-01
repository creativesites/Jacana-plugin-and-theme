<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Contact_Map extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_contact_map';
  }

  public function get_title() {
    return __('Contact Map', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-google-maps';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('address', array(
      'label'   => __('Address', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Sao Tome Street Adonai Court, Windhoek, Namibia', 'jacana-luxe'),
    ));

    $this->add_control('zoom', array(
      'label'   => __('Zoom Level', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::SLIDER,
      'default' => array('size' => 15),
      'range'   => array('px' => array('min' => 1, 'max' => 20)),
    ));

    $this->add_control('height', array(
      'label'   => __('Map Height', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::SLIDER,
      'default' => array('size' => 450),
      'range'   => array('px' => array('min' => 200, 'max' => 800)),
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $address  = urlencode($settings['address']);
    $zoom     = (int) $settings['zoom']['size'];
    $height   = (int) $settings['height']['size'];
    ?>
    <section class="jacana-map-section">
      <div class="jacana-map-inner">
        <div class="jacana-map-wrapper" style="height: <?php echo esc_attr($height); ?>px;">
          <iframe
            width="100%"
            height="100%"
            frameborder="0"
            scrolling="no"
            marginheight="0"
            marginwidth="0"
            src="https://maps.google.com/maps?q=<?php echo $address; ?>&t=&z=<?php echo $zoom; ?>&ie=UTF8&iwloc=&output=embed"
            aria-label="<?php echo esc_attr($settings['address']); ?>">
          </iframe>
        </div>
      </div>
    </section>
    <?php
  }
}
