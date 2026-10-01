<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Adventure_Cards extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_adventure_cards';
  }

  public function get_title() {
    return __('Adventure Cards', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-info-circle';
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
        'default' => __('The Adventure', 'jacana-luxe'),
      )
    );

    $this->add_control(
      'intro',
      array(
        'label' => __('Intro', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXTAREA,
        'default' => __('Jacana Safaris & Tours is a Namibian-owned company founded in 2018, crafted by experts who live and breathe the rhythm of the wild.', 'jacana-luxe'),
      )
    );

    $repeater = new \Elementor\Repeater();
    $repeater->add_control(
      'title',
      array(
        'label' => __('Title', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXT,
      )
    );
    $repeater->add_control(
      'copy',
      array(
        'label' => __('Copy', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXTAREA,
      )
    );

    $this->add_control(
      'cards',
      array(
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
      )
    );

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
