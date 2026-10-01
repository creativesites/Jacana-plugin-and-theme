<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Mission_Vision extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_mission_vision';
  }

  public function get_title() {
    return __('Mission, Vision & Values', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-bullet-list';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('What Guides Us', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label' => __('Intro', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('More than a tour operator — a commitment to genuine, responsible travel in Namibia.', 'jacana-luxe'),
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
          'title' => __('Mission', 'jacana-luxe'),
          'copy' => __('To be competitive with high quality services, exceeding customer expectations by providing customized travelling and accommodation within local and global destinations.', 'jacana-luxe'),
        ),
        array(
          'title' => __('Vision', 'jacana-luxe'),
          'copy' => __('To be a recognized and most preferred travel, accommodation, tour operator and car rentals service provider in Namibia and beyond.', 'jacana-luxe'),
        ),
        array(
          'title' => __('Values & Objectives', 'jacana-luxe'),
          'copy' => __('Integrity, Passion, Respect. Our objective is to determine and meet the travel needs and service delivery expectations of all our clients, all the time, every time.', 'jacana-luxe'),
        ),
      ),
      'title_field' => '{{{ title }}}',
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    ?>
    <section class="jacana-mv-section">
      <div class="jacana-mv-inner">

        <header class="jacana-mv-header jacana-reveal">
          <div class="jacana-mv-kicker"><?php echo esc_html__('Our Guiding Principles', 'jacana-luxe'); ?></div>
          <h2><?php echo esc_html($settings['heading']); ?></h2>
          <p class="jacana-mv-intro"><?php echo esc_html($settings['intro']); ?></p>
        </header>

        <div class="jacana-mv-grid">
          <?php foreach ($settings['cards'] as $index => $card) :
            $numeral = sprintf('%02d', $index + 1);
            $delay   = 80 + ($index * 140);
          ?>
            <div class="jacana-mv-card jacana-reveal" style="--mv-delay: <?php echo esc_attr($delay); ?>ms;">
              <div class="jacana-mv-numeral" aria-hidden="true"><?php echo esc_html($numeral); ?></div>
              <div class="jacana-mv-rule" aria-hidden="true"></div>
              <h3 class="jacana-mv-title"><?php echo esc_html($card['title']); ?></h3>
              <p class="jacana-mv-copy"><?php echo esc_html($card['copy']); ?></p>
            </div>
          <?php endforeach; ?>
        </div>

      </div>
    </section>
    <?php
  }
}
