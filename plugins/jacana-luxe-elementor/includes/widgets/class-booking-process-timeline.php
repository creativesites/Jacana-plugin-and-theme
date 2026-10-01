<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Booking_Process_Timeline extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_booking_process_timeline';
  }

  public function get_title() {
    return __('Booking Process Timeline', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-time-line';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('What happens after you submit?', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label' => __('Intro', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('We review your request, clarify any missing details if needed, and prepare a tailored non-binding proposal based on your route, dates and service mix.', 'jacana-luxe'),
    ));

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('step_number', array(
      'label' => __('Step Number', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => '01',
    ));
    $repeater->add_control('title', array(
      'label' => __('Step Title', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Request received', 'jacana-luxe'),
    ));
    $repeater->add_control('copy', array(
      'label' => __('Step Description', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'rows' => 3,
      'default' => __('We review your inquiry and confirm the request scope.', 'jacana-luxe'),
    ));
    $repeater->add_control('meta', array(
      'label' => __('Step Meta / Time', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Initial response', 'jacana-luxe'),
    ));

    $this->add_control('steps', array(
      'label' => __('Steps', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::REPEATER,
      'fields' => $repeater->get_controls(),
      'title_field' => '{{{ step_number }}} - {{{ title }}}',
      'default' => array(
        array(
          'step_number' => '01',
          'title' => __('Request received', 'jacana-luxe'),
          'copy' => __('We review your form submission and check the requested services, dates and destinations.', 'jacana-luxe'),
          'meta' => __('Initial response', 'jacana-luxe'),
        ),
        array(
          'step_number' => '02',
          'title' => __('Clarification (if needed)', 'jacana-luxe'),
          'copy' => __('If key details are missing, we contact you to confirm route, traveler count, vehicle type or service combinations.', 'jacana-luxe'),
          'meta' => __('Only when required', 'jacana-luxe'),
        ),
        array(
          'step_number' => '03',
          'title' => __('Tailored proposal', 'jacana-luxe'),
          'copy' => __('We prepare a non-binding quote or itinerary proposal with services matched to your request.', 'jacana-luxe'),
          'meta' => __('Rates on request', 'jacana-luxe'),
        ),
        array(
          'step_number' => '04',
          'title' => __('Confirm & proceed', 'jacana-luxe'),
          'copy' => __('Once you approve, we move forward with the next booking steps and confirmations.', 'jacana-luxe'),
          'meta' => __('Booking confirmation', 'jacana-luxe'),
        ),
      ),
    ));

    $this->add_control('assurance_heading', array(
      'label' => __('Assurance Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Booking notes', 'jacana-luxe'),
    ));

    $this->add_control('assurances', array(
      'label' => __('Assurance Items (one per line)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => "Non-binding proposals before confirmation\nOne request can combine multiple services\nWe tailor pricing to your route and dates\nWe can follow up by email, phone or WhatsApp",
    ));

    $this->add_control('cta_label', array(
      'label' => __('CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Go to Booking Form', 'jacana-luxe'),
    ));

    $this->add_control('cta_link', array(
      'label' => __('CTA Link', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'placeholder' => '#booking-form',
    ));

    $this->end_controls_section();
  }

  private function parse_lines($value) {
    return array_values(array_filter(array_map('trim', explode("\n", (string) $value))));
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $steps = !empty($settings['steps']) && is_array($settings['steps']) ? $settings['steps'] : array();
    $assurances = $this->parse_lines($settings['assurances'] ?? '');
    $cta = !empty($settings['cta_link']) ? $settings['cta_link'] : array();
    ?>
    <section class="section jacana-zoora-parity jacana-booking-process-timeline">
      <div class="section-inner">
        <div class="jacana-booking-process-shell">
          <div class="jacana-booking-process-main">
            <div class="section-header jacana-booking-process-header">
              <h2 class="jacana-reveal"><?php echo esc_html($settings['heading']); ?></h2>
              <p class="jacana-reveal" style="--jacana-delay: 120ms;"><?php echo esc_html($settings['intro']); ?></p>
            </div>

            <div class="jacana-booking-process-rail">
              <?php foreach ($steps as $index => $step) : ?>
                <article class="jacana-booking-step-card jacana-reveal" style="--jacana-delay: <?php echo esc_attr(80 + (80 * $index)); ?>ms;">
                  <div class="jacana-booking-step-index"><?php echo esc_html($step['step_number'] ?? sprintf('%02d', $index + 1)); ?></div>
                  <div class="jacana-booking-step-body">
                    <h3><?php echo esc_html($step['title'] ?? ''); ?></h3>
                    <p><?php echo esc_html($step['copy'] ?? ''); ?></p>
                    <?php if (!empty($step['meta'])) : ?>
                      <span class="jacana-booking-step-meta"><?php echo esc_html($step['meta']); ?></span>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>

          <aside class="jacana-booking-process-side card jacana-reveal jacana-reveal-right" style="--jacana-delay: 220ms;">
            <div class="jacana-booking-process-side-label"><?php echo esc_html($settings['assurance_heading']); ?></div>
            <?php if (!empty($assurances)) : ?>
              <ul class="jacana-booking-assurance-list">
                <?php foreach ($assurances as $item) : ?>
                  <li><?php echo esc_html($item); ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <a class="button button-primary jacana-booking-process-cta" href="<?php echo esc_url($cta['url'] ?? '#'); ?>"<?php echo !empty($cta['is_external']) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
              <?php echo esc_html($settings['cta_label'] ?? __('Go to Booking Form', 'jacana-luxe')); ?>
            </a>
          </aside>
        </div>
      </div>
    </section>
    <?php
  }
}
