<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Youtube_Hero_Background extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_youtube_hero_background';
  }

  public function get_title() {
    return __('Video Background Hero', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-youtube';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array(
      'label' => __('Content', 'jacana-luxe'),
    ));

    $this->add_control('hero_title', array(
      'label' => __('Hero Title', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Where every journey writes its own story', 'jacana-luxe'),
    ));

    $this->add_control('hero_description', array(
      'label' => __('Description', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Tailor-made safaris, self-drive adventures and curated stays crafted around your pace.', 'jacana-luxe'),
    ));

    $this->add_control('primary_cta_label', array(
      'label' => __('Primary Button Text', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Plan My Journey', 'jacana-luxe'),
    ));

    $this->add_control('primary_cta_link', array(
      'label' => __('Primary Button Link', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'default' => array('url' => '/contact/'),
      'placeholder' => '/contact/',
    ));

    $this->add_control('secondary_cta_label', array(
      'label' => __('Secondary Button Text', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Contact Us Now', 'jacana-luxe'),
    ));

    $this->add_control('secondary_cta_link', array(
      'label' => __('Secondary Button Link', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'default' => array('url' => '/contact/'),
      'placeholder' => '/contact/',
    ));

    $this->end_controls_section();

    $this->start_controls_section('video_section', array(
      'label' => __('Video', 'jacana-luxe'),
    ));

    $this->add_control('youtube_source', array(
      'label' => __('YouTube URL or Video ID', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => 'd3ZOWkuoD7A',
      'placeholder' => 'https://www.youtube.com/watch?v=d3ZOWkuoD7A',
      'description' => __('Paste a full YouTube link or only the 11-character Video ID.', 'jacana-luxe'),
    ));

    $this->end_controls_section();
  }

  private function extract_youtube_video_id($source) {
    $source = trim((string) $source);
    if ($source === '') {
      return '';
    }

    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $source)) {
      return $source;
    }

    $parts = wp_parse_url($source);
    if (empty($parts['host'])) {
      return '';
    }

    $host = strtolower((string) $parts['host']);
    $path = isset($parts['path']) ? trim((string) $parts['path'], '/') : '';

    if (strpos($host, 'youtu.be') !== false && $path !== '') {
      $id = explode('/', $path)[0];
      return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : '';
    }

    if (!empty($parts['query'])) {
      parse_str((string) $parts['query'], $query);
      if (!empty($query['v']) && preg_match('/^[A-Za-z0-9_-]{11}$/', $query['v'])) {
        return $query['v'];
      }
    }

    if ($path !== '') {
      foreach (array('/embed/', '/shorts/', '/live/') as $needle) {
        $pos = strpos('/' . $path, $needle);
        if ($pos !== false) {
          $id = substr('/' . $path, $pos + strlen($needle));
          $id = explode('/', $id)[0];
          if (preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
            return $id;
          }
        }
      }
    }

    return '';
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $video_id = $this->extract_youtube_video_id($settings['youtube_source'] ?? '');

    $title = isset($settings['hero_title']) ? (string) $settings['hero_title'] : '';
    $description = isset($settings['hero_description']) ? (string) $settings['hero_description'] : '';

    $primary_label = isset($settings['primary_cta_label']) ? (string) $settings['primary_cta_label'] : '';
    $primary_link = $settings['primary_cta_link']['url'] ?? '';
    $primary_ext = !empty($settings['primary_cta_link']['is_external']);

    $secondary_label = isset($settings['secondary_cta_label']) ? (string) $settings['secondary_cta_label'] : '';
    $secondary_link = $settings['secondary_cta_link']['url'] ?? '';
    $secondary_ext = !empty($settings['secondary_cta_link']['is_external']);
    ?>
    <section
      class="jacana-ytbg-hero is-loading"
      data-video-id="<?php echo esc_attr($video_id); ?>"
    >
      <div class="jacana-ytbg-media" aria-hidden="true">
        <div class="jacana-ytbg-player" data-player-host></div>
        <div class="jacana-ytbg-mask jacana-ytbg-mask--top" aria-hidden="true"></div>
        <div class="jacana-ytbg-mask jacana-ytbg-mask--bottom" aria-hidden="true"></div>
      </div>

      <div class="jacana-ytbg-overlay" aria-hidden="true"></div>

      <div class="jacana-ytbg-content">
        <div class="jacana-ytbg-copy">
          <?php if ($title !== '') : ?>
            <h1 class="elementor-heading-title"><?php echo esc_html($title); ?></h1>
          <?php endif; ?>

          <?php if ($description !== '') : ?>
            <p class="elementor-widget-heading"><?php echo esc_html($description); ?></p>
          <?php endif; ?>

          <?php if (($primary_label !== '' && $primary_link !== '') || ($secondary_label !== '' && $secondary_link !== '')) : ?>
            <div class="jacana-ytbg-actions">
              <?php if ($primary_label !== '' && $primary_link !== '') : ?>
                <a class="elementor-button elementor-button-link elementor-size-sm" href="<?php echo esc_url($primary_link); ?>" <?php echo $primary_ext ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                  <span class="elementor-button-content-wrapper">
                    <span class="elementor-button-text"><?php echo esc_html($primary_label); ?></span>
                  </span>
                </a>
              <?php endif; ?>

              <?php if ($secondary_label !== '' && $secondary_link !== '') : ?>
                <a class="elementor-button elementor-button-link elementor-size-sm" href="<?php echo esc_url($secondary_link); ?>" <?php echo $secondary_ext ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                  <span class="elementor-button-content-wrapper">
                    <span class="elementor-button-text"><?php echo esc_html($secondary_label); ?></span>
                  </span>
                </a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
    <?php
  }
}
