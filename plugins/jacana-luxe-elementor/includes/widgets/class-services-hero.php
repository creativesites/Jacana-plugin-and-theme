<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Services_Hero extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_services_hero';
  }

  public function get_title() {
    return __('Services Hero', 'jacana-luxe');
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
      'default' => __('What We Do', 'jacana-luxe'),
    ));

    $this->add_control('title', array(
      'label'   => __('Title', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Every Journey Begins with a Conversation', 'jacana-luxe'),
    ));

    $this->add_control('copy', array(
      'label'   => __('Copy', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('From tailor-made tours and game drives to airport transfers, car rentals, and accommodation — we handle the details so you experience Namibia the way it was meant to be seen.', 'jacana-luxe'),
    ));

    $this->add_control('cta_primary_label', array(
      'label'   => __('Primary CTA Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Start planning', 'jacana-luxe'),
    ));

    $this->add_control('cta_primary_link', array(
      'label'       => __('Primary CTA Link', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::URL,
      'placeholder' => '/contact/',
      'default'     => array('url' => '/contact/'),
    ));

    $this->add_control('cta_secondary_label', array(
      'label'   => __('Secondary CTA Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Explore our services', 'jacana-luxe'),
    ));

    $this->add_control('cta_secondary_link', array(
      'label'       => __('Secondary CTA Link', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::URL,
      'placeholder' => '#services',
      'default'     => array('url' => '#services'),
    ));

    $this->add_control('background', array(
      'label'       => __('Background Image', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::MEDIA,
      'description' => __('Full-screen hero background. Also used as video poster.', 'jacana-luxe'),
    ));

    $this->add_control('video_url', array(
      'label'       => __('Background Video URL', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::TEXT,
      'description' => __('Optional .mp4 URL. Leave empty to use static image only.', 'jacana-luxe'),
      'placeholder' => 'https://example.com/services-reel.mp4',
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings   = $this->get_settings_for_display();
    $bg         = !empty($settings['background']['url']) ? $settings['background']['url'] : '';
    $video_url  = !empty($settings['video_url']) ? trim($settings['video_url']) : '';
    $has_video  = !empty($video_url);

    $p_label    = !empty($settings['cta_primary_label'])        ? $settings['cta_primary_label']            : '';
    $p_url      = !empty($settings['cta_primary_link']['url'])   ? $settings['cta_primary_link']['url']       : '/contact/';
    $p_ext      = !empty($settings['cta_primary_link']['is_external']);

    $s_label    = !empty($settings['cta_secondary_label'])       ? $settings['cta_secondary_label']           : '';
    $s_url      = !empty($settings['cta_secondary_link']['url']) ? $settings['cta_secondary_link']['url']     : '#services';
    $s_ext      = !empty($settings['cta_secondary_link']['is_external']);
    ?>
    <section class="jacana-sh-hero<?php echo $has_video ? ' hero--video' : ''; ?>"
             <?php if (!$has_video && $bg) : ?>style="background-image: url('<?php echo esc_url($bg); ?>');"<?php endif; ?>>

      <?php if ($has_video) : ?>
        <video class="jacana-sh-hero-video"
               src="<?php echo esc_url($video_url); ?>"
               <?php echo $bg ? 'poster="' . esc_url($bg) . '"' : ''; ?>
               autoplay muted loop playsinline preload="metadata"
               aria-hidden="true"></video>
      <?php endif; ?>

      <div class="jacana-sh-hero-overlay" aria-hidden="true"></div>

      <div class="jacana-sh-hero-inner">
        <div class="jacana-sh-hero-text jacana-reveal">
          

          <h1><?php echo esc_html($settings['title']); ?></h1>

          <?php if (!empty($settings['copy'])) : ?>
            <p class="jacana-sh-hero-copy"><?php echo esc_html($settings['copy']); ?></p>
          <?php endif; ?>

          <div class="jacana-sh-hero-actions">
            <?php if ($p_label && $p_url) : ?>
              <a class="button button-primary jacana-button-premium"
                 href="<?php echo esc_url($p_url); ?>"
                 <?php echo $p_ext ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                <span class="button-text"><?php echo esc_html($p_label); ?></span>
                <span class="button-shine" aria-hidden="true"></span>
                <span class="jacana-cta-arrow" aria-hidden="true">&rarr;</span>
              </a>
            <?php endif; ?>
            <?php if ($s_label && $s_url) : ?>
              <a class="button button-ghost button-ghost--dark"
                 href="<?php echo esc_url($s_url); ?>"
                 <?php echo $s_ext ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                <?php echo esc_html($s_label); ?>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Oshiwambo stripe at bottom -->
      <div class="jacana-sh-hero-stripe oshi-divider" role="presentation" aria-hidden="true"></div>
    </section>
    <?php
  }
}
