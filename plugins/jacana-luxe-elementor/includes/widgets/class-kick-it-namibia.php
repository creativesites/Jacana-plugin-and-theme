<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Kick_It_Namibia extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_kick_it_namibia';
  }

  public function get_title() {
    return __('Kick It In Namibia', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-image';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('image_one', array(
      'label' => __('Image One', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
    ));

    $this->add_control('image_two', array(
      'label' => __('Image Two', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $img1 = !empty($settings['image_one']['url']) ? $settings['image_one']['url'] : '';
    $img2 = !empty($settings['image_two']['url']) ? $settings['image_two']['url'] : '';
    ?>
    <section class="fullwidth-panel">
      <?php if (!empty($img1)) : ?>
        <img class="fullwidth-image" src="<?php echo esc_url($img1); ?>" alt="<?php echo esc_attr__('Kick It in Namibia image one', 'jacana-luxe'); ?>">
      <?php endif; ?>
      <?php if (!empty($img2)) : ?>
        <img class="fullwidth-image" src="<?php echo esc_url($img2); ?>" alt="<?php echo esc_attr__('Kick It in Namibia image two', 'jacana-luxe'); ?>">
      <?php endif; ?>
    </section>
    <?php
  }
}
