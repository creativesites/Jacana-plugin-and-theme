<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Tailor_Made_Story extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_tailor_made_story';
  }

  public function get_title() {
    return __('Tailor-made Story Intro', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-parallax';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label'   => __('Heading', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Imagine landing in Namibia and your perfect adventure is already waiting.', 'jacana-luxe'),
    ));

    $this->add_control('story', array(
      'label'   => __('Story Copy', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('After ten hours in the air, you should not be worrying about logistics. Jacana designs every route around your pace and travel style so you can step into Namibia with confidence from day one.', 'jacana-luxe'),
    ));

    $this->add_control('trust_line', array(
      'label'   => __('Trust Line', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('10+ years delivering private journeys, seamless support, and local expertise from arrival to departure.', 'jacana-luxe'),
    ));

    $this->add_control('feature_image', array(
      'label' => __('Feature Image', 'jacana-luxe'),
      'type'  => \Elementor\Controls_Manager::MEDIA,
    ));

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('title', array(
      'label' => __('Feature Title', 'jacana-luxe'),
      'type'  => \Elementor\Controls_Manager::TEXT,
    ));
    $repeater->add_control('copy', array(
      'label' => __('Feature Copy', 'jacana-luxe'),
      'type'  => \Elementor\Controls_Manager::TEXTAREA,
    ));

    $this->add_control('features', array(
      'label'       => __('Feature Highlights', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::REPEATER,
      'fields'      => $repeater->get_controls(),
      'default'     => array(
        array(
          'title' => __('100% tailored itineraries', 'jacana-luxe'),
          'copy'  => __('Every route, lodge, and activity is matched to your interests and comfort level.', 'jacana-luxe'),
        ),
        array(
          'title' => __('Private guided and self-drive options', 'jacana-luxe'),
          'copy'  => __('Choose guided support or freedom with a self-drive itinerary and local backup.', 'jacana-luxe'),
        ),
        array(
          'title' => __('End-to-end coordination', 'jacana-luxe'),
          'copy'  => __('Transfers, stays, park planning, and support are handled in one place.', 'jacana-luxe'),
        ),
      ),
      'title_field' => '{{{ title }}}',
    ));

    $this->add_control('primary_label', array(
      'label'   => __('Primary CTA Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Yes, I am ready', 'jacana-luxe'),
    ));

    $this->add_control('primary_link', array(
      'label'       => __('Primary CTA Link', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::URL,
      'placeholder' => '#',
    ));

    $this->add_control('secondary_label', array(
      'label'   => __('Secondary CTA Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Get more information', 'jacana-luxe'),
    ));

    $this->add_control('secondary_link', array(
      'label'       => __('Secondary CTA Link', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::URL,
      'placeholder' => '#',
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings  = $this->get_settings_for_display();
    $image     = !empty($settings['feature_image']['url']) ? $settings['feature_image']['url'] : '';
    $primary   = !empty($settings['primary_link'])   ? $settings['primary_link']   : array();
    $secondary = !empty($settings['secondary_link']) ? $settings['secondary_link'] : array();
    ?>
    
    <section class="section jacana-zoora-parity jacana-tours-widget jacana-tailor-made-story">

      <div class="section-inner jacana-tailor-story-grid">

        <!-- Heading spans both columns via grid-column: 1 / -1 -->
        <h2 class="jacana-tailor-story-heading jacana-reveal" style="--jacana-delay: 140ms;">
          <?php echo esc_html($settings['heading']); ?>
        </h2>

        <!-- LEFT: Content column -->
        <div class="jacana-tailor-story-content">
          <!-- Body copy -->
          <p class="jacana-tailor-story-body jacana-reveal" style="--jacana-delay: 210ms;">
            <?php echo esc_html($settings['story']); ?>
          </p>

          <!-- Decorative divider -->
          <div class="jacana-tailor-story-divider jacana-reveal" aria-hidden="true" style="--jacana-delay: 290ms;">
            <span></span><span>&#10022;</span><span></span>
          </div>

          <!-- CTAs -->
          <div class="jacana-tailor-story-actions jacana-reveal" style="--jacana-delay: 360ms;">
            <a class="button button-primary jacana-tailor-cta-primary"
               data-jacana-ai-flow="planner"
               data-jacana-widget="jacana_tailor_made_story"
               data-jacana-service="tailor_made"
               href="<?php echo esc_url($primary['url'] ?? '#'); ?>"
               <?php echo !empty($primary['is_external']) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
              <?php echo esc_html($settings['primary_label']); ?>
              <span class="jacana-cta-arrow" aria-hidden="true">&rarr;</span>
            </a>
            <!-- <a class="button button-ghost jacana-button-soft"
               data-jacana-ai-flow="planner"
               data-jacana-widget="jacana_tailor_made_story"
               data-jacana-service="tailor_made"
               href="<?php echo esc_url($secondary['url'] ?? '#'); ?>"
               <?php echo !empty($secondary['is_external']) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
              <?php echo esc_html($settings['secondary_label']); ?>
            </a> -->
          </div>

        </div><!-- /.jacana-tailor-story-content -->

        <!-- RIGHT: Media + feature cards -->
        <div class="jacana-tailor-story-media">

          <?php if (!empty($image)) : ?>
            <figure class="jacana-tailor-story-figure jacana-reveal jacana-reveal-right" style="--jacana-delay: 180ms;">
              <div class="jacana-tailor-story-figure-inner">
                <img src="<?php echo esc_url($image); ?>"
                     alt="<?php echo esc_attr($settings['heading']); ?>"
                     loading="lazy">
              </div>
            </figure>
          <?php endif; ?>

          <?php if (!empty($settings['features'])) : ?>
            <div class="jacana-tailor-story-features">
              <?php foreach ($settings['features'] as $index => $feature) : ?>
                <article class="jacana-tailor-story-card card jacana-reveal"
                         data-feature-index="<?php echo esc_attr($index + 1); ?>"
                         style="--jacana-delay: <?php echo esc_attr(260 + (90 * ((int) $index + 1))); ?>ms;">
                  <h3><?php echo esc_html($feature['title'] ?? ''); ?></h3>
                  <p><?php echo esc_html($feature['copy'] ?? ''); ?></p>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        </div><!-- /.jacana-tailor-story-media -->

      </div><!-- /.jacana-tailor-story-grid -->

      <!-- Centered Trust Banner at bottom -->
      <div class="section-inner jacana-tailor-story-footer">
        <blockquote class="jacana-tailor-story-trust-banner jacana-reveal" style="--jacana-delay: 280ms;">
          <p><?php echo esc_html($settings['trust_line']); ?></p>
        </blockquote>
      </div>

    </section>
    <?php
  }
}
