<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Safaris_Hero extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_safaris_hero';
  }

  public function get_title() {
    return __('Safaris Hero', 'jacana-luxe');
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
      'default' => __('Safaris', 'jacana-luxe'),
    ));

    $this->add_control('title', array(
      'label' => __('Title', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Game Drive in Etosha National Park', 'jacana-luxe'),
    ));

    $this->add_control('copy', array(
      'label' => __('Copy', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('A Game Drive will take you around the Etosha National Park on the search for wild animals. You will be in an open vehicle with an experienced guide on the lookout for lions, elephants, zebras and more.', 'jacana-luxe'),
    ));

    $this->add_control('background', array(
      'label' => __('Background Image', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $bg = !empty($settings['background']['url']) ? $settings['background']['url'] : '';
    ?>
    <section class="hero" style="background-image: url('<?php echo esc_url($bg); ?>');">
      <div class="hero-inner">
        <div>
          <div class="hero-kicker"><?php echo esc_html($settings['kicker']); ?></div>
          <h1><?php echo esc_html($settings['title']); ?></h1>
          <p><?php echo esc_html($settings['copy']); ?></p>
        </div>
        <div class="hero-card">
          <h3><?php echo esc_html__('Etosha Highlights', 'jacana-luxe'); ?></h3>
          <ul>
            <li><?php echo esc_html__('Over 100 mammal species', 'jacana-luxe'); ?></li>
            <li><?php echo esc_html__('340 bird species', 'jacana-luxe'); ?></li>
            <li><?php echo esc_html__('Water holes for year-round viewing', 'jacana-luxe'); ?></li>
          </ul>
        </div>
      </div>
    </section>
    <?php
  }
}
