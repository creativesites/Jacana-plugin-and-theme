<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Experience_Cards extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_experience_cards';
  }

  public function get_title() {
    return __('Experience Cards', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-check-circle';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Experience the Jacana Difference', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label' => __('Intro', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Luxury travel design paired with deep local knowledge and seamless execution.', 'jacana-luxe'),
    ));

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('title', array(
      'label' => __('Title', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
    ));
    $repeater->add_control('copy', array(
      'label' => __('Copy', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
    ));

    $this->add_control('cards', array(
      'type' => \Elementor\Controls_Manager::REPEATER,
      'fields' => $repeater->get_controls(),
      'default' => array(
        array(
          'title' => __('Tailor-made itineraries', 'jacana-luxe'),
          'copy' => __('Every route is crafted around your pace, budget, and sense of adventure.', 'jacana-luxe'),
        ),
        array(
          'title' => __('Premium stays', 'jacana-luxe'),
          'copy' => __('From elegant lodges to luxury tented camps, we select the finest stays.', 'jacana-luxe'),
        ),
        array(
          'title' => __('Local expertise', 'jacana-luxe'),
          'copy' => __('Namibian hosts who anticipate your needs and elevate every moment.', 'jacana-luxe'),
        ),
      ),
      'title_field' => '{{{ title }}}',
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
          <p><?php echo esc_html($settings['intro']); ?></p>
        </div>
        <div class="card-grid">
          <?php foreach ($settings['cards'] as $card) : ?>
            <div class="card">
              <h3><?php echo esc_html($card['title']); ?></h3>
              <p><?php echo esc_html($card['copy']); ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php
  }
}
