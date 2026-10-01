<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Tours_Overview extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_tours_overview';
  }

  public function get_title() {
    return __('Tours Overview', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-map-pin';
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
        'default' => __('Tailor-made Tours', 'jacana-luxe'),
      )
    );

    $this->add_control(
      'intro',
      array(
        'label' => __('Intro', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXTAREA,
        'default' => __('We plan your personalized Namibian adventure according to your wishes.', 'jacana-luxe'),
      )
    );

    $this->add_control(
      'process_copy',
      array(
        'label' => __('Process Copy', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXTAREA,
        'default' => __('Get in touch with us and we will schedule a complimentary consultation call. We’ll craft your itinerary, refine it with your input, then handle reservations and logistics with easy online payment.', 'jacana-luxe'),
      )
    );

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('label', array(
      'label' => __('Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
    ));
    $repeater->add_control('value', array(
      'label' => __('Value', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
    ));

    $this->add_control('tabs', array(
      'type' => \Elementor\Controls_Manager::REPEATER,
      'fields' => $repeater->get_controls(),
      'default' => array(
        array('label' => __('How it works', 'jacana-luxe'), 'value' => __('Consult • Design • Book', 'jacana-luxe')),
        array('label' => __('Dates & Pricing', 'jacana-luxe'), 'value' => __('Custom based on your preferences', 'jacana-luxe')),
        array('label' => __('Accommodation', 'jacana-luxe'), 'value' => __('Luxury camps to rooftop tents', 'jacana-luxe')),
      ),
      'title_field' => '{{{ label }}}',
    ));

    $this->add_control(
      'guided_title',
      array(
        'label' => __('Guided Tour Title', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => __('Guided Tour', 'jacana-luxe'),
      )
    );
    $this->add_control(
      'guided_copy',
      array(
        'label' => __('Guided Tour Copy', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXTAREA,
        'default' => __('Fully planned itinerary with a dedicated guide driving you from the first day to the last.', 'jacana-luxe'),
      )
    );
    $this->add_control(
      'guided_included',
      array(
        'label' => __('Guided Included', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXTAREA,
        'default' => __('Accommodation, meals as per itinerary, transport and fuel, park fees, guide, transfers, activities, car insurance.', 'jacana-luxe'),
      )
    );
    $this->add_control(
      'guided_excluded',
      array(
        'label' => __('Guided Not Included', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXTAREA,
        'default' => __('Travel insurance, visas, tips, beverages, photography accessories.', 'jacana-luxe'),
      )
    );

    $this->add_control(
      'self_title',
      array(
        'label' => __('Self-drive Title', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => __('Self-drive Tour', 'jacana-luxe'),
      )
    );
    $this->add_control(
      'self_copy',
      array(
        'label' => __('Self-drive Copy', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXTAREA,
        'default' => __('We deliver the full itinerary, you take the wheel. Our team stays available throughout your journey.', 'jacana-luxe'),
      )
    );
    $this->add_control(
      'self_included',
      array(
        'label' => __('Self-drive Included', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXTAREA,
        'default' => __('Accommodation, meals as indicated, transport, car insurance.', 'jacana-luxe'),
      )
    );
    $this->add_control(
      'self_excluded',
      array(
        'label' => __('Self-drive Not Included', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXTAREA,
        'default' => __('Travel insurance, visas, tips, beverages, photography accessories, park fees, transfers, guide, fuel.', 'jacana-luxe'),
      )
    );

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    ?>
    <section class="section jacana-zoora-parity jacana-tours-widget jacana-tours-overview" id="tours">
      <div class="section-inner">
        <div class="section-header">
          <h2 class="jacana-reveal"><?php echo esc_html($settings['heading']); ?></h2>
          <p class="jacana-reveal" style="--jacana-delay: 120ms;"><?php echo esc_html($settings['intro']); ?></p>
        </div>
        <div class="split">
          <div>
            <p class="jacana-reveal"><?php echo esc_html($settings['process_copy']); ?></p>
            <div class="tabs">
              <?php foreach ($settings['tabs'] as $index => $tab) : ?>
                <div class="tab jacana-reveal" style="--jacana-delay: <?php echo esc_attr(80 * ((int) $index + 1)); ?>ms;">
                  <span><?php echo esc_html($tab['label']); ?></span>
                  <strong><?php echo esc_html($tab['value']); ?></strong>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="card jacana-reveal jacana-reveal-right" style="--jacana-delay: 220ms;">
            <h3><?php echo esc_html($settings['guided_title']); ?></h3>
            <p><?php echo esc_html($settings['guided_copy']); ?></p>
            <p><strong><?php echo esc_html__('Included:', 'jacana-luxe'); ?></strong> <?php echo esc_html($settings['guided_included']); ?></p>
            <p><strong><?php echo esc_html__('Not included:', 'jacana-luxe'); ?></strong> <?php echo esc_html($settings['guided_excluded']); ?></p>
          </div>
        </div>
        <div class="card jacana-reveal" style="margin-top: 26px; --jacana-delay: 260ms;">
          <h3><?php echo esc_html($settings['self_title']); ?></h3>
          <p><?php echo esc_html($settings['self_copy']); ?></p>
          <p><strong><?php echo esc_html__('Included:', 'jacana-luxe'); ?></strong> <?php echo esc_html($settings['self_included']); ?></p>
          <p><strong><?php echo esc_html__('Not included:', 'jacana-luxe'); ?></strong> <?php echo esc_html($settings['self_excluded']); ?></p>
        </div>
      </div>
    </section>
    <?php
  }
}
