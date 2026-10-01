<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Destinations_Gallery extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_destinations_gallery';
  }

  public function get_title() {
    return __('Destinations Gallery', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-gallery-grid';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section(
      'content_section',
      array(
        'label' => __('Content', 'jacana-luxe'),
      )
    );

    $this->add_control(
      'heading',
      array(
        'label' => __('Heading', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => __('Destinations', 'jacana-luxe'),
      )
    );

    $this->add_control(
      'intro',
      array(
        'label' => __('Intro', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXTAREA,
        'default' => __('From Etosha to the Skeleton Coast, we take you everywhere you want to explore in Namibia and beyond.', 'jacana-luxe'),
      )
    );

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('label', array(
      'label' => __('Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
    ));
    $repeater->add_control('image', array(
      'label' => __('Image', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
    ));

    $this->add_control('items', array(
      'type' => \Elementor\Controls_Manager::REPEATER,
      'fields' => $repeater->get_controls(),
      'default' => array(),
      'title_field' => '{{{ label }}}',
    ));

    $this->add_control(
      'footer_copy',
      array(
        'label' => __('Footer Copy', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXTAREA,
        'default' => __('The listed destinations are the main Namibian attractions and we are not limited to them. Get in touch and we will discover your perfect route.', 'jacana-luxe'),
      )
    );

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    ?>
    <section class="section dark">
      <div class="section-inner">
        <div class="section-header">
          <h2><?php echo esc_html($settings['heading']); ?></h2>
          <p><?php echo esc_html($settings['intro']); ?></p>
        </div>
        <div class="gallery-grid">
          <?php foreach ($settings['items'] as $item) :
            $image_url = !empty($item['image']['url']) ? $item['image']['url'] : '';
            ?>
            <div class="gallery-item has-image" style="background-image: url('<?php echo esc_url($image_url); ?>');">
              <span class="gallery-label"><?php echo esc_html($item['label']); ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <p style="margin-top: 30px;"><?php echo esc_html($settings['footer_copy']); ?></p>
      </div>
    </section>
    <?php
  }
}
