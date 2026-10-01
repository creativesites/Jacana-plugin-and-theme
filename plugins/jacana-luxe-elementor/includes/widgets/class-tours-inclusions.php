<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Tours_Inclusions extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_tours_inclusions';
  }

  public function get_title() {
    return __('Tours Inclusions', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-check-circle';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('section_kicker', array(
      'label'   => __('Section Kicker', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Choose your style', 'jacana-luxe'),
    ));

    $this->add_control('section_heading', array(
      'label'   => __('Section Heading', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Two ways to explore Namibia', 'jacana-luxe'),
    ));

    $this->add_control('section_intro', array(
      'label'   => __('Section Intro', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Every Jacana journey is fully planned and personally matched to you. Choose the format that fits your travel style.', 'jacana-luxe'),
    ));

    $this->add_control('guided_title', array(
      'label'   => __('Guided Tour Title', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Guided tour', 'jacana-luxe'),
    ));

    $this->add_control('guided_badge', array(
      'label'   => __('Guided Tour Badge', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Most popular', 'jacana-luxe'),
    ));

    $this->add_control('guided_copy', array(
      'label'   => __('Guided Tour Copy', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('We will provide you with a fully planned and well-structured tour itinerary and assign a tour guide to you. The guide will be with you, drive and guide you from the first day of your tour to the last.', 'jacana-luxe'),
    ));

    $this->add_control('guided_included', array(
      'label'   => __('Guided Included (one per line)', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => "Accommodation\nMeals as indicated in the tour itinerary\nTransport and fuel\nNational park entrance fees\nTour guide for the entire tour\nPick-up and drop-off\nActivities as indicated in the tour itinerary\nCar insurance as it will be indicated in the tour contract",
    ));

    $this->add_control('guided_excluded', array(
      'label'   => __('Guided Not Included (one per line)', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => "Travel insurance\nVisa fees and entry clearing fees\nTips and personal shopping\nLiquors and beverages\nPhotography accessories like cameras etc.",
    ));

    $this->add_control('self_title', array(
      'label'   => __('Self-drive Title', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Self-drive tour', 'jacana-luxe'),
    ));

    $this->add_control('self_badge', array(
      'label'   => __('Self-drive Badge', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Freedom &amp; flexibility', 'jacana-luxe'),
    ));

    $this->add_control('self_copy', array(
      'label'   => __('Self-drive Copy', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('We will provide you with a fully planned tour itinerary and you will take over the wheel. We are always available via phone or e-mail during your tour if you need our assistance.', 'jacana-luxe'),
    ));

    $this->add_control('self_included', array(
      'label'   => __('Self-drive Included (one per line)', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => "Accommodation\nMeals as indicated in the tour itinerary\nTransport\nCar insurance as it will be indicated in the tour contract",
    ));

    $this->add_control('self_excluded', array(
      'label'   => __('Self-drive Not Included (one per line)', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => "Travel insurance\nVisa fee and entry clearing fees\nTips and personal shopping\nLiquors and beverages\nSoft drinks\nPhotography accessories like cameras etc.\nNational park entrance fees\nPick-up and Drop-off\nMeals if not indicated in the tour itinerary\nTour guide\nFuel/gas",
    ));

    $this->end_controls_section();
  }

  private function render_checklist($items, $type = 'included') {
    $lines = array_filter(array_map('trim', explode("\n", $items)));
    if (empty($lines)) {
      return;
    }
    $class = $type === 'included' ? 'jacana-incl-list jacana-incl-list--yes' : 'jacana-incl-list jacana-incl-list--no';
    echo '<ul class="' . esc_attr($class) . '">';
    foreach ($lines as $line) {
      echo '<li>' . esc_html($line) . '</li>';
    }
    echo '</ul>';
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    ?>
    <section class="section jacana-zoora-parity jacana-tours-widget jacana-tours-inclusions">
      <div class="section-inner">

        <!-- Section header -->
        <header class="jacana-incl-header">
          <div class="jacana-tailor-story-kicker jacana-reveal">
            <?php echo esc_html($settings['section_kicker']); ?>
          </div>
          <h2 class="jacana-incl-heading jacana-reveal" style="--jacana-delay: 80ms;">
            <?php echo esc_html($settings['section_heading']); ?>
          </h2>
          <?php if (!empty($settings['section_intro'])) : ?>
            <p class="jacana-incl-intro jacana-reveal" style="--jacana-delay: 150ms;">
              <?php echo esc_html($settings['section_intro']); ?>
            </p>
          <?php endif; ?>
        </header>

        <!-- Two tour type cards -->
        <div class="jacana-incl-grid">

          <!-- Guided tour card -->
          <article class="jacana-incl-card jacana-incl-card--guided jacana-reveal" style="--jacana-delay: 200ms;">
            <?php if (!empty($settings['guided_badge'])) : ?>
              <span class="jacana-incl-badge"><?php echo esc_html($settings['guided_badge']); ?></span>
            <?php endif; ?>
            <div class="jacana-incl-card-icon" aria-hidden="true">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z" fill="currentColor"/>
              </svg>
            </div>
            <h3 class="jacana-incl-card-title"><?php echo esc_html($settings['guided_title']); ?></h3>
            <p class="jacana-incl-card-copy"><?php echo esc_html($settings['guided_copy']); ?></p>
            <div class="jacana-incl-lists">
              <div class="jacana-incl-list-group">
                <p class="jacana-incl-list-label jacana-incl-list-label--yes">What&rsquo;s included</p>
                <?php $this->render_checklist($settings['guided_included'], 'included'); ?>
              </div>
              <div class="jacana-incl-list-group">
                <p class="jacana-incl-list-label jacana-incl-list-label--no">What&rsquo;s not included</p>
                <?php $this->render_checklist($settings['guided_excluded'], 'excluded'); ?>
              </div>
            </div>
          </article>

          <!-- Self-drive tour card -->
          <article class="jacana-incl-card jacana-incl-card--self jacana-reveal" style="--jacana-delay: 320ms;">
            <?php if (!empty($settings['self_badge'])) : ?>
              <span class="jacana-incl-badge jacana-incl-badge--alt"><?php echo wp_kses_post($settings['self_badge']); ?></span>
            <?php endif; ?>
            <div class="jacana-incl-card-icon" aria-hidden="true">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.85 7h10.29l1.08 3.11H5.77L6.85 7zM19 17H5v-5h14v5zm-8-4h-2v2h2v-2zm-4 0H5v2h2v-2zm10 0h-2v2h2v-2z" fill="currentColor"/>
              </svg>
            </div>
            <h3 class="jacana-incl-card-title"><?php echo esc_html($settings['self_title']); ?></h3>
            <p class="jacana-incl-card-copy"><?php echo esc_html($settings['self_copy']); ?></p>
            <div class="jacana-incl-lists">
              <div class="jacana-incl-list-group">
                <p class="jacana-incl-list-label jacana-incl-list-label--yes">What&rsquo;s included</p>
                <?php $this->render_checklist($settings['self_included'], 'included'); ?>
              </div>
              <div class="jacana-incl-list-group">
                <p class="jacana-incl-list-label jacana-incl-list-label--no">What&rsquo;s not included</p>
                <?php $this->render_checklist($settings['self_excluded'], 'excluded'); ?>
              </div>
            </div>
          </article>

        </div><!-- /.jacana-incl-grid -->
      </div>
    </section>
    <?php
  }
}
