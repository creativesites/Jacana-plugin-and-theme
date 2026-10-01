<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_About_Hero extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_about_hero';
  }

  public function get_title() {
    return __('About Hero', 'jacana-luxe');
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
      'default' => __('About Us', 'jacana-luxe'),
    ));

    $this->add_control('title', array(
      'label'   => __('Title', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Connecting You to the Wonders of Namibia', 'jacana-luxe'),
    ));

    $this->add_control('copy', array(
      'label'   => __('Copy', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Founded in 2018 by Namibians with 8+ years in tourism and hospitality. Jacana designs tailor-made journeys across Namibia for individuals, couples, families, and groups — and delivers friendly, reliable support from your first inquiry to your final day.', 'jacana-luxe'),
    ));

    $this->add_control('cta_label', array(
      'label'   => __('CTA Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Start planning', 'jacana-luxe'),
    ));

    $this->add_control('cta_link', array(
      'label'       => __('CTA Link', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::URL,
      'placeholder' => '/contact/',
      'default'     => array('url' => '/contact/'),
    ));

    $this->add_control('background', array(
      'label'       => __('Background Image', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::MEDIA,
      'description' => __('Used as static background or as video poster.', 'jacana-luxe'),
    ));

    $this->add_control('video_url', array(
      'label'       => __('Background Video URL', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::TEXT,
      'description' => __('Full URL to an .mp4/.mov file. Leave empty until header video is ready.', 'jacana-luxe'),
      'placeholder' => 'https://example.com/header.mp4',
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings  = $this->get_settings_for_display();
    $bg        = !empty($settings['background']['url']) ? $settings['background']['url'] : '';
    $video_url = !empty($settings['video_url']) ? trim($settings['video_url']) : '';
    $has_video = !empty($video_url);
    $cta_label = !empty($settings['cta_label']) ? $settings['cta_label'] : '';
    $cta_url   = !empty($settings['cta_link']['url']) ? $settings['cta_link']['url'] : '/contact/';
    $cta_ext   = !empty($settings['cta_link']['is_external']);
    ?>
    <section class="jacana-about-hero-section<?php echo $has_video ? ' hero--video' : ''; ?>"
             <?php if (!$has_video && $bg) : ?>style="background-image: url('<?php echo esc_url($bg); ?>');"<?php endif; ?>>

      <?php if ($has_video) : ?>
        <video class="jacana-about-hero-video"
               src="<?php echo esc_url($video_url); ?>"
               <?php echo $bg ? 'poster="' . esc_url($bg) . '"' : ''; ?>
               autoplay muted loop playsinline preload="metadata"
               aria-hidden="true"></video>
      <?php endif; ?>

      <div class="jacana-about-hero-overlay" aria-hidden="true"></div>

      <div class="jacana-about-hero-inner">
        <div class="jacana-about-hero-text jacana-reveal">
         
          <h1><?php echo esc_html($settings['title']); ?></h1>
          <p class="jacana-about-hero-copy"><?php echo esc_html($settings['copy']); ?></p>
          <?php if ($cta_label && $cta_url) : ?>
            <div class="jacana-about-hero-actions">
              <a class="button button-primary jacana-button-premium"
                 href="<?php echo esc_url($cta_url); ?>"
                 <?php echo $cta_ext ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                <span class="button-text"><?php echo esc_html($cta_label); ?></span>
                <span class="button-shine" aria-hidden="true"></span>
                <span class="jacana-cta-arrow" aria-hidden="true">&rarr;</span>
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Oshiwambo stripe at bottom -->
      <div class="jacana-about-hero-stripe oshi-divider" role="presentation" aria-hidden="true"></div>
    </section>
    <?php
  }
}
