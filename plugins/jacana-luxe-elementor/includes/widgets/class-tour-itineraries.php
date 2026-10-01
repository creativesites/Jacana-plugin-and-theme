<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Tour_Itineraries extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_tour_itineraries';
  }

  public function get_title() {
    return __('Tour Itineraries', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-photo-library';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('kicker', array(
      'label'   => __('Kicker', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Guided by experience', 'jacana-luxe'),
    ));

    $this->add_control('heading', array(
      'label'   => __('Heading', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Our tour itineraries', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label'   => __('Intro', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Each journey is tailor-made, but these itineraries give you a starting point. Explore the full route, then tell us what you would change.', 'jacana-luxe'),
    ));

    $this->add_control('trust_line', array(
      'label'   => __('Trust Line (below intro)', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('All itineraries fully customisable', 'jacana-luxe'),
    ));

    $this->add_control('cta_label', array(
      'label'   => __('Card CTA Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Explore the whole itinerary', 'jacana-luxe'),
    ));

    $repeater = new \Elementor\Repeater();

    $repeater->add_control('tour_name', array(
      'label'   => __('Tour Name', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Tour Name', 'jacana-luxe'),
    ));

    $repeater->add_control('tour_image', array(
      'label' => __('Photo', 'jacana-luxe'),
      'type'  => \Elementor\Controls_Manager::MEDIA,
    ));

    $repeater->add_control('tour_duration', array(
      'label'       => __('Duration', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::TEXT,
      'placeholder' => __('e.g. 14 days', 'jacana-luxe'),
    ));

    $repeater->add_control('tour_type', array(
      'label'       => __('Tour Type', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::TEXT,
      'placeholder' => __('e.g. Guided / Self-drive', 'jacana-luxe'),
    ));

    $repeater->add_control('tour_link', array(
      'label'       => __('Itinerary Link', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::URL,
      'placeholder' => 'https://',
      'description' => __('Link to the itinerary page or PDF.', 'jacana-luxe'),
    ));

    $this->add_control('tours', array(
      'label'       => __('Tours', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::REPEATER,
      'fields'      => $repeater->get_controls(),
      'default'     => array(
        array('tour_name' => __('Highlights of Namibia',  'jacana-luxe'), 'tour_duration' => '14 days', 'tour_type' => 'Guided'),
        array('tour_name' => __('Wild Namibia',           'jacana-luxe'), 'tour_duration' => '10 days', 'tour_type' => 'Guided'),
        array('tour_name' => __('Favourite Namibia Tour', 'jacana-luxe'), 'tour_duration' => '12 days', 'tour_type' => 'Self-drive'),
        array('tour_name' => __('Endless Horizons',       'jacana-luxe'), 'tour_duration' => '16 days', 'tour_type' => 'Self-drive'),
      ),
      'title_field' => '{{{ tour_name }}}',
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings  = $this->get_settings_for_display();
    $cta_label = !empty($settings['cta_label']) ? $settings['cta_label'] : __('Explore the whole itinerary', 'jacana-luxe');
    $kicker    = !empty($settings['kicker']) ? $settings['kicker'] : __('Guided by experience', 'jacana-luxe');
    ?>
    <section class="jacana-ti-section">
      <div class="jacana-ti-inner">

        <!-- Editorial split header -->
        <header class="jacana-ti-header jacana-reveal">

          <div class="jacana-ti-header-left">
            <div class="jacana-ti-kicker"><?php echo esc_html($kicker); ?></div>
            <h2><?php echo esc_html($settings['heading']); ?></h2>
          </div>

          <div class="jacana-ti-header-right">
            <?php if (!empty($settings['intro'])) : ?>
              <p class="jacana-ti-intro"><?php echo esc_html($settings['intro']); ?></p>
            <?php endif; ?>
            <?php if (!empty($settings['trust_line'])) : ?>
              <div class="jacana-ti-trust">
                <span class="jacana-ti-trust-dot" aria-hidden="true"></span>
                <?php echo esc_html($settings['trust_line']); ?>
              </div>
            <?php endif; ?>
          </div>

        </header>

        <!-- Tour cards grid -->
        <div class="jacana-ti-grid">
          <?php foreach ($settings['tours'] as $index => $tour) :
            $img      = !empty($tour['tour_image']['url'])  ? $tour['tour_image']['url']  : '';
            $name     = !empty($tour['tour_name'])          ? $tour['tour_name']          : '';
            $duration = !empty($tour['tour_duration'])      ? $tour['tour_duration']      : '';
            $type     = !empty($tour['tour_type'])          ? $tour['tour_type']          : '';
            $link_url = !empty($tour['tour_link']['url'])   ? $tour['tour_link']['url']   : '#';
            $is_ext   = !empty($tour['tour_link']['is_external']);
            $delay    = 80 + ($index * 90);
          ?>
            <article class="jacana-ti-card jacana-reveal" style="--ti-delay: <?php echo esc_attr($delay); ?>ms;">

              <a class="jacana-ti-link"
                 href="<?php echo esc_url($link_url); ?>"
                 <?php echo $is_ext ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
                 aria-label="<?php echo esc_attr($name . ' — ' . $cta_label); ?>">

                <!-- Full-fill image -->
                <div class="jacana-ti-media" aria-hidden="true">
                  <?php if ($img) : ?>
                    <img src="<?php echo esc_url($img); ?>"
                         alt=""
                         loading="lazy"
                         decoding="async"
                         class="jacana-ti-photo">
                  <?php else : ?>
                    <div class="jacana-ti-placeholder">
                      <span><?php echo esc_html(mb_substr($name, 0, 1)); ?></span>
                    </div>
                  <?php endif; ?>
                </div>

                <!-- Cinematic overlay -->
                <div class="jacana-ti-overlay" aria-hidden="true"></div>

                <!-- Oshiwambo stripe -->
                <div class="jacana-ti-stripe" aria-hidden="true"></div>

                <!-- Top: type + duration badges -->
                <div class="jacana-ti-top">
                  <?php if ($type) : ?>
                    <span class="jacana-ti-badge jacana-ti-badge--type"><?php echo esc_html($type); ?></span>
                  <?php else : ?>
                    <span></span>
                  <?php endif; ?>
                  <?php if ($duration) : ?>
                    <span class="jacana-ti-badge jacana-ti-badge--duration"><?php echo esc_html($duration); ?></span>
                  <?php endif; ?>
                </div>

                <!-- Bottom: name + CTA -->
                <div class="jacana-ti-content">
                  <h3 class="jacana-ti-name"><?php echo esc_html($name); ?></h3>
                  <span class="jacana-ti-cta" aria-hidden="true">
                    <?php echo esc_html($cta_label); ?>
                    <span class="jacana-ti-arrow">&rarr;</span>
                  </span>
                </div>

              </a>

            </article>
          <?php endforeach; ?>
        </div>

      </div>
    </section>
    <?php
  }
}
