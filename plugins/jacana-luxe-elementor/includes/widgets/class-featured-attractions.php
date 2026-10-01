<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Featured_Attractions extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_featured_attractions';
  }

  public function get_title() {
    return __('Featured Attractions', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-star';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('section_bg', array(
      'label'   => __('Section Background Image', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::MEDIA,
      'default' => array(
        'url' => $this->get_default_background_url(),
      ),
    ));

    $this->add_control('section_kicker', array(
      'label'   => __('Section Kicker', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Beyond the journey', 'jacana-luxe'),
    ));

    $this->add_control('section_heading', array(
      'label'   => __('Section Heading', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Featured Attractions', 'jacana-luxe'),
    ));

    $this->add_control('section_intro', array(
      'label'   => __('Section Intro', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Discover the services designed to elevate your Namibian adventure from ordinary to extraordinary.', 'jacana-luxe'),
    ));

    $repeater = new \Elementor\Repeater();
    
    $repeater->add_control('icon', array(
      'label' => __('Icon', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::SELECT,
      'options' => array(
        'car' => __('4x4 Rental', 'jacana-luxe'),
        'bed' => __('Accommodation', 'jacana-luxe'),
        'map' => __('Tailored Routes', 'jacana-luxe'),
        'plane' => __('Airport Transfers', 'jacana-luxe'),
      ),
      'default' => 'car',
    ));

    $repeater->add_control('title', array(
      'label'   => __('Title', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Service Title', 'jacana-luxe'),
    ));
    
    $repeater->add_control('copy', array(
      'label'   => __('Description', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Service description goes here.', 'jacana-luxe'),
    ));

    $repeater->add_control('bullets', array(
      'label'   => __('Highlights (one per line)', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => "Premium experience\nExpert guides\nSeamless booking",
    ));
    
    $repeater->add_control('cta_label', array(
      'label'   => __('Button Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Discover more', 'jacana-luxe'),
    ));
    
    $repeater->add_control('cta_link', array(
      'label'   => __('Button Link', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::URL,
      'placeholder' => 'https://',
    ));

    $this->add_control('services', array(
      'label'       => __('Services & Attractions', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::REPEATER,
      'fields'      => $repeater->get_controls(),
      'default'     => array(
        array(
          'icon' => 'car',
          'title' => __('4x4 Car Rental', 'jacana-luxe'),
          'copy' => __('Conquer Namibia’s rugged landscapes with our premium selection of fully-equipped 4x4 vehicles, designed for durability and comfort.', 'jacana-luxe'),
          'bullets' => "Toyota Hilux double cabs\nFull camping gear included\nComprehensive insurance\n24/7 breakdown assistance",
          'cta_label' => __('View 4x4 Fleet', 'jacana-luxe'),
        ),
        array(
          'icon' => 'bed',
          'title' => __('Accommodation', 'jacana-luxe'),
          'copy' => __('Rest in carefully selected lodges, guesthouses, and luxury tented camps that offer authentic hospitality and remarkable locations.', 'jacana-luxe'),
          'bullets' => "Hand-picked luxury lodges\nAuthentic tented camps\nExclusive private reserves\nRemote desert hideaways",
          'cta_label' => __('Explore Lodges', 'jacana-luxe'),
        ),
        array(
          'icon' => 'plane',
          'title' => __('Airport Transfers', 'jacana-luxe'),
          'copy' => __('Start and end your journey seamlessly with our reliable and comfortable airport transfer services.', 'jacana-luxe'),
          'bullets' => "Meet & greet service\nSpacious air-conditioned vehicles\nFlight tracking\nDoor-to-door comfort",
          'cta_label' => __('Book Transfer', 'jacana-luxe'),
        ),
        array(
          'icon' => 'map',
          'title' => __('Tailored Itineraries', 'jacana-luxe'),
          'copy' => __('Experience Namibia your way. We hand-craft custom routes matching your specific interests, pace, and travel style.', 'jacana-luxe'),
          'bullets' => "Custom-designed routes\nExpert destination advice\nPre-booked activities\nDetailed roadbooks",
          'cta_label' => __('Design My Trip', 'jacana-luxe'),
        )
      ),
      'title_field' => '{{{ title }}}',
    ));

    $this->end_controls_section();
  }
  
  private function render_icon($icon_type) {
    if ($icon_type === 'car') {
      return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="10" width="18" height="10" rx="3"></rect><path d="M5 10l2-4h10l2 4"></path><path d="M7 14h.01"></path><path d="M17 14h.01"></path></svg>';
    } elseif ($icon_type === 'bed') {
      return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4v16"></path><path d="M2 8h18a2 2 0 0 1 2 2v10"></path><path d="M2 17h20"></path><path d="M6 8v9"></path></svg>';
    } elseif ($icon_type === 'map') {
      return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line></svg>';
    } else {
      // plane
      return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.2-1.1.7l-1.3 2.6c-.2.4-.1 1 .3 1.3L9 14l-4 4-3-1v3l4 2 2 4h3l-1-3 4-4 3.2 6.3c.3.4.9.5 1.3.3l2.6-1.3c.5-.2.8-.7.7-1.1z"></path></svg>';
    }
  }

  private function get_default_background_url() {
    $uploads = wp_get_upload_dir();
    $base = isset($uploads['baseurl']) ? $uploads['baseurl'] : '';
    if ($base === '') {
      return '';
    }
    return trailingslashit($base) . '2026/02/Etosha1a.jpg';
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $bg_img = !empty($settings['section_bg']['url']) ? $settings['section_bg']['url'] : '';
    $services = !empty($settings['services']) && is_array($settings['services']) ? $settings['services'] : array();
    ?>
    <section class="section jacana-features-widget jacana-featured-attractions" <?php echo $bg_img ? 'style="--jacana-attr-bg: url(\'' . esc_url($bg_img) . '\');"' : ''; ?>>
      <div class="jacana-attr-overlay"></div>
      <div class="section-inner">

        <header class="jacana-attr-header">
          <div class="jacana-attr-kicker jacana-reveal">
            <?php echo esc_html($settings['section_kicker']); ?>
          </div>
          <h2 class="jacana-attr-heading jacana-reveal" style="--jacana-delay: 80ms;">
            <?php echo esc_html($settings['section_heading']); ?>
          </h2>
          <?php if (!empty($settings['section_intro'])) : ?>
            <p class="jacana-attr-intro jacana-reveal" style="--jacana-delay: 150ms;">
              <?php echo esc_html($settings['section_intro']); ?>
            </p>
          <?php endif; ?>
        </header>

        <?php if (!empty($services)) : ?>
          <div class="jacana-attr-grid">
            <?php foreach ($services as $index => $item) : 
              $bullets = array_filter(array_map('trim', explode("\n", (string) ($item['bullets'] ?? ''))));
              $delay = 200 + ($index * 80);
              $cta = !empty($item['cta_link']) && is_array($item['cta_link']) ? $item['cta_link'] : array();
            ?>
              <div class="jacana-attr-card jacana-reveal" style="--jacana-delay: <?php echo esc_attr($delay); ?>ms;">
                <div class="jacana-attr-icon" aria-hidden="true">
                  <?php echo $this->render_icon($item['icon']); ?>
                </div>
                <h3 class="jacana-attr-title"><?php echo esc_html($item['title']); ?></h3>
                <p class="jacana-attr-copy"><?php echo esc_html($item['copy']); ?></p>
                
                <?php if (!empty($bullets)) : ?>
                  <ul class="jacana-attr-list">
                    <?php foreach ($bullets as $bullet) : ?>
                      <li><?php echo esc_html($bullet); ?></li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
                
                <?php if (!empty($item['cta_label'])) : ?>
                  <a class="button button-gold jacana-attr-btn" href="<?php echo esc_url($cta['url'] ?? '#'); ?>">
                    <?php echo esc_html($item['cta_label']); ?>
                  </a>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>
    </section>
    <?php
  }
}
