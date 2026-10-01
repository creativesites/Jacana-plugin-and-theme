<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Tours_Hero extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_tours_hero';
  }

  public function get_title() {
    return __('Tours Hero', 'jacana-luxe');
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
      'label'   => __('Kicker', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Tours', 'jacana-luxe'),
    ));

    $this->add_control('title', array(
      'label'       => __('Title (first line)', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::TEXT,
      'default'     => __('We plan your', 'jacana-luxe'),
      'description' => __('Displayed in white. The italic accent line below appears in dunes gold.', 'jacana-luxe'),
    ));

    $this->add_control('title_em', array(
      'label'       => __('Title — italic accent (second line)', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::TEXT,
      'default'     => __('personalized Namibian adventure', 'jacana-luxe'),
      'description' => __('Optional. Displayed italic in dunes gold beneath the main title line.', 'jacana-luxe'),
    ));

    $this->add_control('copy', array(
      'label'   => __('Copy', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Custom itineraries, curated stays, and seamless logistics built around your pace and travel style.', 'jacana-luxe'),
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
      'default' => __('Browse itineraries', 'jacana-luxe'),
    ));

    $this->add_control('cta_secondary_link', array(
      'label'   => __('Secondary CTA Link', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::URL,
      'default' => array('url' => '#itineraries'),
    ));

    $this->add_control('background', array(
      'label'       => __('Poster / Fallback Image', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::MEDIA,
      'description' => __('Shown before the video loads and on devices that cannot autoplay video.', 'jacana-luxe'),
    ));

    $this->add_control('video_url', array(
      'label'       => __('Background Video URL (.mp4)', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::TEXT,
      'placeholder' => 'https://example.com/namibia-reel.mp4',
    ));

    /* ── Style ── */
    $this->add_control('style_sep', array(
      'label'     => __('Style Options', 'jacana-luxe'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ));

    $this->add_control('overlay_opacity', array(
      'label'      => __('Overlay Opacity', 'jacana-luxe'),
      'type'       => \Elementor\Controls_Manager::SLIDER,
      'size_units' => array(''),
      'range'      => array('' => array('min' => 0, 'max' => 1, 'step' => 0.05)),
      'default'    => array('size' => 1),
      'description'=> __('0 = transparent (full video visible), 1 = full overlay (default).', 'jacana-luxe'),
    ));

    $this->add_control('title_color', array(
      'label'   => __('Title Color', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::COLOR,
      'default' => 'rgba(255, 250, 228, 0.97)',
    ));

    $this->add_control('title_em_color', array(
      'label'   => __('Title Accent Color (italic line)', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::COLOR,
      'default' => '#db9751',
    ));

    $this->add_control('copy_color', array(
      'label'   => __('Description Color', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::COLOR,
      'default' => 'rgba(255, 250, 228, 0.56)',
    ));

    /* ── Stats ── */
    $this->add_control('stats_heading', array(
      'label'     => __('Trust Stats', 'jacana-luxe'),
      'type'      => \Elementor\Controls_Manager::HEADING,
      'separator' => 'before',
    ));

    $repeater = new \Elementor\Repeater();

    $repeater->add_control('stat_number', array(
      'label'       => __('Number / Value', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::TEXT,
      'placeholder' => '8+',
    ));

    $repeater->add_control('stat_label', array(
      'label'       => __('Label', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::TEXT,
      'placeholder' => 'Years in Namibia',
    ));

    $this->add_control('stats', array(
      'label'       => __('Stats', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::REPEATER,
      'fields'      => $repeater->get_controls(),
      'default'     => array(
        array('stat_number' => '8+',   'stat_label' => 'Years in Namibia'),
        array('stat_number' => '500+', 'stat_label' => 'Journeys planned'),
        array('stat_number' => '100%', 'stat_label' => 'Personalised'),
      ),
      'title_field' => '{{{ stat_number }}} {{{ stat_label }}}',
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings  = $this->get_settings_for_display();
    $bg        = !empty($settings['background']['url'])         ? $settings['background']['url']         : '';
    $video_url = !empty($settings['video_url'])                 ? trim($settings['video_url'])            : '';
    $has_video = !empty($video_url);
    $cta_label = !empty($settings['cta_label'])                 ? $settings['cta_label']                 : '';
    $cta_url   = !empty($settings['cta_link']['url'])           ? $settings['cta_link']['url']           : '';
    $cta_ext   = !empty($settings['cta_link']['is_external']);
    $sec_label = !empty($settings['cta_secondary_label'])       ? $settings['cta_secondary_label']       : '';
    $sec_url   = !empty($settings['cta_secondary_link']['url']) ? $settings['cta_secondary_link']['url'] : '';
    $sec_ext   = !empty($settings['cta_secondary_link']['is_external']);
    $has_stats = !empty($settings['stats']);

    // Style overrides → CSS custom properties on the section element
    $overlay_opacity = isset($settings['overlay_opacity']['size']) ? (float) $settings['overlay_opacity']['size'] : 1;
    $title_color     = !empty($settings['title_color'])    ? $settings['title_color']    : '';
    $title_em_color  = !empty($settings['title_em_color']) ? $settings['title_em_color'] : '';
    $copy_color      = !empty($settings['copy_color'])     ? $settings['copy_color']     : '';

    $css_vars = '--tours-hero-overlay-opacity:' . esc_attr($overlay_opacity);
    if ($title_color)    { $css_vars .= ';--tours-hero-title-color:'    . esc_attr($title_color); }
    if ($title_em_color) { $css_vars .= ';--tours-hero-title-em-color:' . esc_attr($title_em_color); }
    if ($copy_color)     { $css_vars .= ';--tours-hero-copy-color:'     . esc_attr($copy_color); }
    ?>
    <section class="jacana-tours-hero-section" style="<?php echo $css_vars; ?>">

      <!-- Fallback / poster image -->
      <?php if ($bg) : ?>
        <div class="jacana-tours-hero-bg"
             style="background-image: url('<?php echo esc_url($bg); ?>');"
             aria-hidden="true"></div>
      <?php endif; ?>

      <!-- Background video -->
      <?php if ($has_video) : ?>
        <video class="jacana-tours-hero-video"
               src="<?php echo esc_url($video_url); ?>"
               <?php echo $bg ? 'poster="' . esc_url($bg) . '"' : ''; ?>
               autoplay muted loop playsinline
               preload="metadata"
               aria-hidden="true"></video>
      <?php endif; ?>

      <!-- Cinematic overlay -->
      <div class="jacana-tours-hero-overlay" aria-hidden="true"></div>

      <!-- Inner grid -->
      <div class="jacana-tours-hero-inner">

        <!-- Left: editorial content -->
        <div class="jacana-tours-hero-content jacana-reveal">

          

          <h1 class="jacana-tours-hero-title">
            <?php echo esc_html($settings['title']); ?>
            
          </h1>

          <?php if (!empty($settings['copy'])) : ?>
            <p class="jacana-tours-hero-copy"><?php echo esc_html($settings['copy']); ?></p>
          <?php endif; ?>

          <div class="jacana-tours-hero-actions">
            <?php if ($cta_label && $cta_url) : ?>
              <a class="jacana-tours-hero-cta-primary"
                 href="<?php echo esc_url($cta_url); ?>"
                 <?php echo $cta_ext ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                <?php echo esc_html($cta_label); ?>
                <span class="jacana-tours-hero-arrow" aria-hidden="true">&rarr;</span>
              </a>
            <?php endif; ?>

            <?php if ($sec_label && $sec_url) : ?>
              <a class="jacana-tours-hero-cta-secondary"
                 href="<?php echo esc_url($sec_url); ?>"
                 <?php echo $sec_ext ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                <?php echo esc_html($sec_label); ?>
                <span class="jacana-tours-hero-arrow" aria-hidden="true">&rarr;</span>
              </a>
            <?php endif; ?>
          </div>

        </div><!-- /.jacana-tours-hero-content -->

        <!-- Right: frosted-glass stat cards -->
        <?php if ($has_stats) : ?>
          <div class="jacana-tours-hero-stats">
            <?php 
            $stats = is_array($settings['stats']) ? $settings['stats'] : [];
            foreach ($stats as $i => $stat) : 
              if (empty($stat['stat_number']) && empty($stat['stat_label'])) {
                continue;
              }
              $delay = 380 + ($i * 130);
            ?>
              <div class="jacana-tours-hero-stat jacana-reveal"
                   style="--jacana-delay: <?php echo esc_attr($delay); ?>ms;">
                <span class="jacana-tours-hero-stat-num"><?php echo esc_html($stat['stat_number'] ?? ''); ?></span>
                <span class="jacana-tours-hero-stat-label"><?php echo esc_html($stat['stat_label'] ?? ''); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div><!-- /.jacana-tours-hero-inner -->

      <!-- Animated scroll indicator -->
      <div class="jacana-tours-hero-scroll" aria-hidden="true">
        <span class="jacana-tours-hero-scroll-label">Scroll</span>
        <span class="jacana-tours-hero-scroll-line"></span>
      </div>

      <!-- Oshiwambo cultural stripe -->
      <div class="jacana-tours-hero-stripe" aria-hidden="true"></div>

    </section>
    <?php
  }
}
