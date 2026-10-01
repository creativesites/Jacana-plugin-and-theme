<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Car_Rental_Offers extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_car_rental_offers';
  }

  public function get_title() {
    return __('Car Rental Offers', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-gallery-grid';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Choose your ride', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label' => __('Intro', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Select the vehicle that matches your itinerary and terrain.', 'jacana-luxe'),
    ));

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('title', array(
      'label' => __('Title', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
    ));
    $repeater->add_control('copy', array(
      'label' => __('Copy', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
    ));
    $repeater->add_control('image', array(
      'label' => __('Image', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
    ));
    $repeater->add_control('link', array(
      'label' => __('Link', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'placeholder' => 'https://',
    ));
    $repeater->add_control('cta_label', array(
      'label' => __('CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Talk to Jacana about this vehicle', 'jacana-luxe'),
    ));

    $this->add_control('vehicles', array(
      'type' => \Elementor\Controls_Manager::REPEATER,
      'fields' => $repeater->get_controls(),
      'default' => array(
        array(
          'title' => __('4x4 Adventure', 'jacana-luxe'),
          'copy' => __('Ideal for rugged terrain and long-distance exploration.', 'jacana-luxe'),
          'cta_label' => __('Talk to Jacana about this vehicle', 'jacana-luxe'),
        ),
        array(
          'title' => __('Comfort SUV', 'jacana-luxe'),
          'copy' => __('A smooth ride for city and park transfers.', 'jacana-luxe'),
          'cta_label' => __('Talk to Jacana about this vehicle', 'jacana-luxe'),
        ),
        array(
          'title' => __('Touring Van', 'jacana-luxe'),
          'copy' => __('Perfect for families and group travel.', 'jacana-luxe'),
          'cta_label' => __('Talk to Jacana about this vehicle', 'jacana-luxe'),
        ),
      ),
      'title_field' => '{{{ title }}}',
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    ?>
    <section class="section jacana-car-rental-offers">
      <div class="section-inner">
        <div class="section-header">
          <h2><?php echo esc_html($settings['heading']); ?></h2>
          <p><?php echo esc_html($settings['intro']); ?></p>
        </div>
        <div class="card-grid">
          <?php foreach ($settings['vehicles'] as $vehicle) :
            $image = !empty($vehicle['image']['url']) ? $vehicle['image']['url'] : '';
            $link = $vehicle['link'] ?? array();
            $has_link = !empty($link['url']);
            $link_attrs = !empty($link['is_external']) ? ' target="_blank" rel="noopener noreferrer"' : '';
            ?>
            <article class="card jacana-rental-offer-card" data-jacana-ai-surface="vehicle-card" data-jacana-widget="jacana_car_rental_offers" data-jacana-vehicle="<?php echo esc_attr($vehicle['title'] ?? __('Vehicle', 'jacana-luxe')); ?>">
              <?php if (!empty($image)) : ?>
                <?php if ($has_link) : ?>
                  <a class="jacana-rental-offer-media" href="<?php echo esc_url($link['url']); ?>"<?php echo $link_attrs; ?>>
                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($vehicle['title']); ?>">
                  </a>
                <?php else : ?>
                  <div class="jacana-rental-offer-media">
                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($vehicle['title']); ?>">
                  </div>
                <?php endif; ?>
              <?php endif; ?>

              <div class="jacana-rental-offer-copy">
                <h3><?php echo esc_html($vehicle['title']); ?></h3>
                <?php if (!empty($vehicle['copy'])) : ?>
                  <p><?php echo esc_html($vehicle['copy']); ?></p>
                <?php endif; ?>
              </div>

              <?php if ($has_link) : ?>
                <a class="button button-primary jacana-rental-offer-link" href="<?php echo esc_url($link['url']); ?>"<?php echo $link_attrs; ?> data-jacana-ai-surface="vehicle" data-jacana-ai-flow="vehicle" data-jacana-widget="jacana_car_rental_offers" data-jacana-vehicle="<?php echo esc_attr($vehicle['title'] ?? __('Vehicle', 'jacana-luxe')); ?>">
                  <?php echo esc_html(!empty($vehicle['cta_label']) ? $vehicle['cta_label'] : __('Talk to Jacana about this vehicle', 'jacana-luxe')); ?>
                </a>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php
  }
}
