<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Home_Journey extends \Elementor\Widget_Base {
  public function get_name()       { return 'jacana_home_journey'; }
  public function get_title()      { return __('Home Journey Process', 'jacana-luxe'); }
  public function get_icon()       { return 'eicon-flow'; }
  public function get_categories() { return array('jacana-luxe'); }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('kicker', array(
      'label'   => __('Kicker', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('How it works', 'jacana-luxe'),
    ));

    $this->add_control('heading', array(
      'label'   => __('Heading', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Your journey, designed around you', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label'   => __('Intro', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('From the moment you reach out, every detail is handled. We plan the route, coordinate the stays, and put a full roadbook in your hands before you board.', 'jacana-luxe'),
    ));

    /* ── Steps repeater ── */
    $repeater = new \Elementor\Repeater();

    $repeater->add_control('step_number', array(
      'label'   => __('Step Number', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => '01',
    ));

    $repeater->add_control('step_title', array(
      'label'   => __('Step Title', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Share your vision', 'jacana-luxe'),
    ));

    $repeater->add_control('step_body', array(
      'label'   => __('Step Description', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'rows'    => 3,
    ));

    $this->add_control('steps', array(
      'label'       => __('Steps', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::REPEATER,
      'fields'      => $repeater->get_controls(),
      'default'     => array(
        array(
          'step_number' => '01',
          'step_title'  => __('Share your vision', 'jacana-luxe'),
          'step_body'   => __('Tell us your travel style, dream destinations, dates, and group size — via our AI concierge or a short message. No brief is too specific.', 'jacana-luxe'),
        ),
        array(
          'step_number' => '02',
          'step_title'  => __('We design your route', 'jacana-luxe'),
          'step_body'   => __('Within 48 hours you receive a personalised itinerary covering drives, curated stays, activities, and logistics — all priced transparently.', 'jacana-luxe'),
        ),
        array(
          'step_number' => '03',
          'step_title'  => __('You discover Namibia', 'jacana-luxe'),
          'step_body'   => __('Arrive with a full roadbook, pre-confirmed reservations, and real-time support on call. Your only job is to take it in.', 'jacana-luxe'),
        ),
      ),
      'title_field' => '{{{ step_number }}} — {{{ step_title }}}',
    ));

    /* ── CTAs ── */
    $this->add_control('cta_sep', array(
      'label'     => __('Call to Action', 'jacana-luxe'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ));

    $this->add_control('cta_label', array(
      'label'   => __('Primary CTA Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Start planning', 'jacana-luxe'),
    ));

    $this->add_control('cta_link', array(
      'label'   => __('Primary CTA Link', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::URL,
      'default' => array('url' => '/contact/'),
    ));

    $this->add_control('cta_secondary_label', array(
      'label'   => __('Secondary CTA Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Browse our tours', 'jacana-luxe'),
    ));

    $this->add_control('cta_secondary_link', array(
      'label'   => __('Secondary CTA Link', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::URL,
      'default' => array('url' => '/tours/'),
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings  = $this->get_settings_for_display();
    $steps     = !empty($settings['steps']) && is_array($settings['steps']) ? $settings['steps'] : array();
    $cta_url   = !empty($settings['cta_link']['url'])           ? $settings['cta_link']['url']           : '';
    $cta_ext   = !empty($settings['cta_link']['is_external']);
    $sec_url   = !empty($settings['cta_secondary_link']['url']) ? $settings['cta_secondary_link']['url'] : '';
    $sec_ext   = !empty($settings['cta_secondary_link']['is_external']);
    $step_count = count($steps);
    ?>
    <section class="jacana-hj-section">

      <!-- Ambient glows -->
      <div class="jacana-hj-glow jacana-hj-glow--warm" aria-hidden="true"></div>
      <div class="jacana-hj-glow jacana-hj-glow--cool" aria-hidden="true"></div>

      <div class="jacana-hj-inner">

        <!-- ── Header ── -->
        <div class="jacana-hj-header jacana-reveal">
          <?php if (!empty($settings['kicker'])) : ?>
            <div class="jacana-hj-kicker"><?php echo esc_html($settings['kicker']); ?></div>
          <?php endif; ?>
          <h2 class="jacana-hj-heading"><?php echo esc_html($settings['heading']); ?></h2>
          <?php if (!empty($settings['intro'])) : ?>
            <p class="jacana-hj-intro jacana-reveal" style="--jacana-delay: 80ms;"><?php echo esc_html($settings['intro']); ?></p>
          <?php endif; ?>
        </div>

        <!-- ── Steps ── -->
        <?php if (!empty($steps)) : ?>
          <div class="jacana-hj-steps" data-step-count="<?php echo esc_attr($step_count); ?>">
            <?php foreach ($steps as $i => $step) :
              $delay = 100 + ($i * 160);
              $is_last = ($i === $step_count - 1);
            ?>
              <div class="jacana-hj-step jacana-reveal" style="--jacana-delay: <?php echo esc_attr($delay); ?>ms;">

                <!-- Number -->
                <div class="jacana-hj-step-num" aria-hidden="true">
                  <?php echo esc_html($step['step_number'] ?? sprintf('%02d', $i + 1)); ?>
                </div>

                <!-- Connector line (not on last step) -->
                <?php if (!$is_last) : ?>
                  <div class="jacana-hj-connector" aria-hidden="true">
                    <span class="jacana-hj-connector-line"></span>
                    <span class="jacana-hj-connector-dot"></span>
                  </div>
                <?php endif; ?>

                <!-- Content -->
                <div class="jacana-hj-step-body">
                  <?php if (!empty($step['step_title'])) : ?>
                    <h3 class="jacana-hj-step-title"><?php echo esc_html($step['step_title']); ?></h3>
                  <?php endif; ?>
                  <?php if (!empty($step['step_body'])) : ?>
                    <p class="jacana-hj-step-desc"><?php echo esc_html($step['step_body']); ?></p>
                  <?php endif; ?>
                </div>

              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- ── CTAs ── -->
        <div class="jacana-hj-actions jacana-reveal" style="--jacana-delay: 560ms;">
          <?php if (!empty($settings['cta_label']) && $cta_url) : ?>
            <a class="jacana-hj-cta-primary"
               href="<?php echo esc_url($cta_url); ?>"
               <?php echo $cta_ext ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
               data-jacana-ai-flow="planner"
               data-jacana-widget="jacana_home_journey"
               data-jacana-service="tailor_made">
              <?php echo esc_html($settings['cta_label']); ?>
              <span class="jacana-hj-arrow" aria-hidden="true">&rarr;</span>
            </a>
          <?php endif; ?>

          <?php if (!empty($settings['cta_secondary_label']) && $sec_url) : ?>
            <a class="jacana-hj-cta-secondary"
               href="<?php echo esc_url($sec_url); ?>"
               <?php echo $sec_ext ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
              <?php echo esc_html($settings['cta_secondary_label']); ?>
              <span class="jacana-hj-arrow" aria-hidden="true">&rarr;</span>
            </a>
          <?php endif; ?>
        </div>

      </div><!-- /.jacana-hj-inner -->

      <!-- Oshiwambo stripe -->
      <div class="jacana-hj-stripe" aria-hidden="true"></div>

    </section>
    <?php
  }
}
