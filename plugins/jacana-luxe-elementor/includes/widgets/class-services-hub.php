<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Services_Hub extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_services_hub';
  }

  public function get_title() {
    return __('Services Hub', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-posts-grid';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Services built around your journey', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label' => __('Intro', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Choose one service or combine all of them in one tailor-made plan. Rates are provided on request so we can match your exact route and dates.', 'jacana-luxe'),
    ));

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('title', array(
      'label' => __('Service Title', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
    ));
    $repeater->add_control('badge', array(
      'label' => __('Badge', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Rates on request', 'jacana-luxe'),
    ));
    $repeater->add_control('description', array(
      'label' => __('Description', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
    ));
    $repeater->add_control('highlights', array(
      'label' => __('Highlights (one per line)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
    ));
    $repeater->add_control('image', array(
      'label' => __('Image', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
    ));
    $repeater->add_control('cta_label', array(
      'label' => __('CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Get more information', 'jacana-luxe'),
    ));
    $repeater->add_control('cta_link', array(
      'label' => __('CTA Link', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'placeholder' => '#',
    ));

    $this->add_control('services', array(
      'label' => __('Services', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::REPEATER,
      'fields' => $repeater->get_controls(),
      'default' => array(
        array(
          'title' => __('Tailor-made Tours', 'jacana-luxe'),
          'description' => __('Private guided and self-drive tours with routes designed to your pace, interests, and comfort level.', 'jacana-luxe'),
          'highlights' => "Private itineraries\nFlexible travel pace\nAccommodation curation",
          'cta_label' => __('Let the adventure begin', 'jacana-luxe'),
        ),
        array(
          'title' => __('Safari & Game Drives', 'jacana-luxe'),
          'description' => __('Responsible wildlife experiences with local experts in Etosha and other iconic conservation areas.', 'jacana-luxe'),
          'highlights' => "Guided sightings\nConservation-first approach\nFamily-friendly options",
          'cta_label' => __('Book now', 'jacana-luxe'),
        ),
        array(
          'title' => __('Shuttle Service', 'jacana-luxe'),
          'description' => __('Airport, lodge, and city transfers coordinated around your flights and accommodation schedule.', 'jacana-luxe'),
          'highlights' => "Airport pickups\nPoint-to-point transfers\nComfort-focused fleet",
          'cta_label' => __('Contact us now', 'jacana-luxe'),
        ),
        array(
          'title' => __('Car Rental Support', 'jacana-luxe'),
          'description' => __('Modern vehicle options with route advice, practical briefings, and support throughout your drive.', 'jacana-luxe'),
          'highlights' => "4x4 and SUV options\nRoute planning help\nOn-trip support",
          'cta_label' => __('Request a vehicle plan', 'jacana-luxe'),
        ),
      ),
      'title_field' => '{{{ title }}}',
    ));

    $this->end_controls_section();
  }

  protected function render_highlights($items) {
    $lines = array_filter(array_map('trim', explode("\n", (string) $items)));
    if (empty($lines)) {
      return;
    }
    echo '<ul class="jacana-service-features">';
    foreach ($lines as $line) {
      echo '<li>' . esc_html($line) . '</li>';
    }
    echo '</ul>';
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    ?>
    <section class="section jacana-zoora-parity jacana-tours-widget jacana-services-hub">
      <div class="section-inner">
        <div class="section-header">
          <h2 class="jacana-reveal"><?php echo esc_html($settings['heading']); ?></h2>
          <p class="jacana-reveal" style="--jacana-delay: 120ms;"><?php echo esc_html($settings['intro']); ?></p>
        </div>
        <div class="card-grid jacana-services-grid">
          <?php foreach ($settings['services'] as $index => $service) :
            $image = !empty($service['image']['url']) ? $service['image']['url'] : '';
            $cta = !empty($service['cta_link']) ? $service['cta_link'] : array();
            $delay = 80 * ((int) $index + 1);
            ?>
            <article class="card jacana-service-card jacana-reveal" style="--jacana-delay: <?php echo esc_attr($delay); ?>ms;">
              <?php if (!empty($image)) : ?>
                <figure class="jacana-service-media">
                  <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($service['title'] ?? ''); ?>" loading="lazy">
                </figure>
              <?php endif; ?>
              <?php if (!empty($service['badge'])) : ?>
                <span class="jacana-service-badge"><?php echo esc_html($service['badge']); ?></span>
              <?php endif; ?>
              <h3><?php echo esc_html($service['title'] ?? ''); ?></h3>
              <p><?php echo esc_html($service['description'] ?? ''); ?></p>
              <?php $this->render_highlights($service['highlights'] ?? ''); ?>
              <a class="button button-primary jacana-service-button" href="<?php echo esc_url($cta['url'] ?? '#'); ?>"<?php echo !empty($cta['is_external']) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
                <?php echo esc_html($service['cta_label'] ?? __('Get more information', 'jacana-luxe')); ?>
              </a>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php
  }
}
