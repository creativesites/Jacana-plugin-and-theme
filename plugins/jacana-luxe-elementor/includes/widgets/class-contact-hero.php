<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Contact_Hero extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_contact_hero';
  }

  public function get_title() {
    return __('Contact Hero', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-mail';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('kicker', array(
      'label'   => __('Kicker', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Contact', 'jacana-luxe'),
    ));

    $this->add_control('title', array(
      'label'       => __('Title (first line)', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::TEXT,
      'default'     => __('Let’s plan your', 'jacana-luxe'),
      'description' => __('Displayed in white. The italic accent line below appears in dunes gold.', 'jacana-luxe'),
    ));

    $this->add_control('title_em', array(
      'label'       => __('Title — italic accent (second line)', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::TEXT,
      'default'     => __('Namibian adventure', 'jacana-luxe'),
      'description' => __('Optional. Displayed italic in dunes gold beneath the main title line.', 'jacana-luxe'),
    ));

    $this->add_control('copy', array(
      'label'   => __('Copy', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Reach out by phone, email, or through our booking studio to begin your journey.', 'jacana-luxe'),
    ));

    $this->add_control('background', array(
      'label'       => __('Poster / Fallback Image', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::MEDIA,
      'description' => __('Shown before the video loads and on devices that cannot autoplay video.', 'jacana-luxe'),
    ));

    $this->add_control('video_url', array(
      'label'       => __('Background Video URL (.mp4)', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::TEXT,
      'placeholder' => 'https://example.com/contact-reel.mp4',
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
    $has_stats = !empty($settings['stats']);

    // Style overrides → CSS custom properties on the section element
    $overlay_opacity = isset($settings['overlay_opacity']['size']) ? (float) $settings['overlay_opacity']['size'] : 1;
    $title_color     = !empty($settings['title_color'])    ? $settings['title_color']    : '';
    $title_em_color  = !empty($settings['title_em_color']) ? $settings['title_em_color'] : '';
    $copy_color      = !empty($settings['copy_color'])     ? $settings['copy_color']     : '';

    $css_vars = '--contact-hero-overlay-opacity:' . esc_attr($overlay_opacity);
    if ($title_color)    { $css_vars .= ';--contact-hero-title-color:'    . esc_attr($title_color); }
    if ($title_em_color) { $css_vars .= ';--contact-hero-title-em-color:' . esc_attr($title_em_color); }
    if ($copy_color)     { $css_vars .= ';--contact-hero-copy-color:'     . esc_attr($copy_color); }
    ?>
    <section class="jacana-contact-hero-section" style="<?php echo $css_vars; ?>">

      <!-- Fallback / poster image -->
      <?php if ($bg) : ?>
        <div class="jacana-contact-hero-bg"
             style="background-image: url('<?php echo esc_url($bg); ?>');"
             aria-hidden="true"></div>
      <?php endif; ?>

      <!-- Background video -->
      <?php if ($has_video) : ?>
        <video class="jacana-contact-hero-video"
               src="<?php echo esc_url($video_url); ?>"
               <?php echo $bg ? 'poster="' . esc_url($bg) . '"' : ''; ?>
               autoplay muted loop playsinline
               preload="metadata"
               aria-hidden="true"></video>
      <?php endif; ?>

      <!-- Cinematic overlay -->
      <div class="jacana-contact-hero-overlay" aria-hidden="true"></div>

      <!-- Inner grid -->
      <div class="jacana-contact-hero-inner">

        <!-- Left: editorial content -->
        <div class="jacana-contact-hero-content jacana-reveal">

          <?php if (!empty($settings['kicker'])) : ?>
            <div class="jacana-contact-hero-kicker"><?php echo esc_html($settings['kicker']); ?></div>
          <?php endif; ?>

          <h1 class="jacana-contact-hero-title">
            <?php echo esc_html($settings['title']); ?>
            <?php if (!empty($settings['title_em'])) : ?>
              <em><?php echo esc_html($settings['title_em']); ?></em>
            <?php endif; ?>
          </h1>

          <?php if (!empty($settings['copy'])) : ?>
            <p class="jacana-contact-hero-copy"><?php echo esc_html($settings['copy']); ?></p>
          <?php endif; ?>

        </div><!-- /.jacana-contact-hero-content -->

        <!-- Right: frosted-glass stat cards -->
        <?php if ($has_stats) : ?>
          <div class="jacana-contact-hero-stats">
            <?php foreach ($settings['stats'] as $i => $stat) :
              if (empty($stat['stat_number']) && empty($stat['stat_label'])) {
                continue;
              }
              $delay = 380 + ($i * 130);
              $translated_label = apply_filters('jacana_i18n_translate_string', $stat['stat_label'] ?? '', array('area' => 'contact_hero_stat'));
            ?>
              <div class="jacana-contact-hero-stat jacana-reveal"
                   style="--jacana-delay: <?php echo esc_attr($delay); ?>ms;">
                <span class="jacana-contact-hero-stat-num"><?php echo esc_html($stat['stat_number'] ?? ''); ?></span>
                <span class="jacana-contact-hero-stat-label"><?php echo esc_html($translated_label); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div><!-- /.jacana-contact-hero-inner -->

      <!-- Animated scroll indicator -->
      <div class="jacana-contact-hero-scroll" aria-hidden="true">
        <span class="jacana-contact-hero-scroll-label">Scroll</span>
        <span class="jacana-contact-hero-scroll-line"></span>
      </div>

      <!-- Oshiwambo cultural stripe -->
      <div class="jacana-contact-hero-stripe" aria-hidden="true"></div>

    </section>
    <?php
  }
}
