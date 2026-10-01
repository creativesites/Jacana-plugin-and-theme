<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Expos extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_expos';
  }

  public function get_title() {
    return __('Expos & International Fairs', 'jacana-luxe');
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
      'label'   => __('Heading', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Representing Namibia at international fairs', 'jacana-luxe'),
    ));

    $this->add_control('subheading', array(
      'label'   => __('Subheading', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Jacana Safaris and Tours represents Namibia at international travel fairs and expos — connecting with partners, agents, and travellers across the globe.', 'jacana-luxe'),
    ));

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('image', array(
      'label' => __('Photo', 'jacana-luxe'),
      'type'  => \Elementor\Controls_Manager::MEDIA,
    ));

    $this->add_group_control(
      \Elementor\Group_Control_Image_Size::get_type(),
      array(
        'name'    => 'image',
        'default' => 'medium_large',
      )
    );

    $repeater->add_control('caption', array(
      'label'   => __('Caption', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('International Travel Expo', 'jacana-luxe'),
    ));
    $repeater->add_control('location', array(
      'label'   => __('Location / Year', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => '',
    ));

    $this->add_control('photos', array(
      'label'       => __('Expo Photos', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::REPEATER,
      'fields'      => $repeater->get_controls(),
      'default'     => array(
        array('caption' => __('Berlin Travel Festival', 'jacana-luxe'), 'location' => __('Berlin · December 2023', 'jacana-luxe')),
        array('caption' => __('Fernweh Festival Erlangen', 'jacana-luxe'), 'location' => __('Erlangen · November 2024', 'jacana-luxe')),
        array('caption' => __('IFTM Top Resa', 'jacana-luxe'), 'location' => __('Paris · September 2025', 'jacana-luxe')),
        array('caption' => __('Reiselust Bremen', 'jacana-luxe'), 'location' => __('Bremen · November 2025', 'jacana-luxe')),
        array('caption' => __('ITB Berlin', 'jacana-luxe'), 'location' => __('Berlin · March 2026', 'jacana-luxe')),
      ),
      'title_field' => '{{{ caption }}}',
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $photos   = $settings['photos'];
    if (empty($photos)) {
      return;
    }
    ?>
    <section class="section jacana-expos-section">
      <div class="section-inner">

        <div class="jacana-expos-header jacana-reveal">
          <h2><?php echo esc_html($settings['heading']); ?></h2>
          <?php if (!empty($settings['subheading'])) : ?>
            <p class="jacana-expos-intro"><?php echo esc_html($settings['subheading']); ?></p>
          <?php endif; ?>
        </div>

        <div class="jacana-expos-grid">
          <?php foreach ($photos as $index => $photo) :
            $caption  = !empty($photo['caption'])  ? $photo['caption']  : '';
            $location = !empty($photo['location']) ? $photo['location'] : '';
            $delay    = 60 + ($index * 80);
            ?>
            <figure class="jacana-expo-item jacana-reveal" style="--jacana-delay: <?php echo esc_attr($delay); ?>ms;">
              <?php if (!empty($photo['image']['id']) || !empty($photo['image']['url'])) : ?>
                <div class="jacana-expo-img-wrap">
                  <?php echo \Elementor\Group_Control_Image_Size::get_attachment_image_html($photo, 'image'); ?>
                </div>
              <?php else : ?>
                <div class="jacana-expo-img-wrap jacana-expo-placeholder" aria-hidden="true"></div>
              <?php endif; ?>
              <?php if ($caption || $location) : ?>
                <figcaption class="jacana-expo-caption">
                  <?php if ($caption)  : ?><span class="jacana-expo-caption-title"><?php echo esc_html($caption); ?></span><?php endif; ?>
                  <?php if ($location) : ?><span class="jacana-expo-caption-loc"><?php echo esc_html($location); ?></span><?php endif; ?>
                </figcaption>
              <?php endif; ?>
            </figure>
          <?php endforeach; ?>
        </div>


      </div>
    </section>
    <?php
  }
}
