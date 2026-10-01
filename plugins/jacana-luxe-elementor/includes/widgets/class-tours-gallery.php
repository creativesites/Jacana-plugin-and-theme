<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Tours_Gallery extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_tours_gallery';
  }

  public function get_title() {
    return __('Tours Gallery', 'jacana-luxe');
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
      'default' => __('Gallery', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label' => __('Intro', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Nine unforgettable moments from across Namibia.', 'jacana-luxe'),
    ));

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('image', array(
      'label' => __('Image', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
    ));
    $repeater->add_control('title', array(
      'label' => __('Title', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
    ));

    $this->add_control('images', array(
      'type' => \Elementor\Controls_Manager::REPEATER,
      'fields' => $repeater->get_controls(),
      'default' => array(),
      'title_field' => '{{{ title }}}',
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    ?>
    <section class="section jacana-zoora-parity jacana-tours-widget jacana-tours-gallery">
      <div class="section-inner">
        <div class="section-header">
          <h2 class="jacana-reveal"><?php echo esc_html($settings['heading']); ?></h2>
          <p class="jacana-reveal" style="--jacana-delay: 120ms;"><?php echo esc_html($settings['intro']); ?></p>
        </div>
        <div class="tour-gallery">
          <?php foreach ($settings['images'] as $index => $item) :
            $url = !empty($item['image']['url']) ? $item['image']['url'] : '';
            $title = !empty($item['title']) ? $item['title'] : '';
            if (empty($url)) {
              continue;
            }
            ?>
            <div class="tour-gallery-item jacana-reveal" style="background-image: url('<?php echo esc_url($url); ?>'); --jacana-delay: <?php echo esc_attr(60 * ((int) $index + 1)); ?>ms;" data-delay="<?php echo esc_attr($index); ?>">
              <button class="tour-gallery-button" type="button" data-lightbox-src="<?php echo esc_url($url); ?>" data-lightbox-title="<?php echo esc_attr($title); ?>">
                <span class="tour-gallery-caption"><?php echo esc_html($title); ?></span>
              </button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php
  }
}
