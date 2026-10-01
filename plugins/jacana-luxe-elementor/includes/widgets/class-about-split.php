<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_About_Split extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_about_split';
  }

  public function get_title() {
    return __('About Story', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-columns';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('kicker', array(
      'label'   => __('Kicker', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Who We Are', 'jacana-luxe'),
    ));

    $this->add_control('heading', array(
      'label'   => __('Heading', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Connecting You to the Wonders of Namibia', 'jacana-luxe'),
    ));

    $this->add_control('body', array(
      'label'   => __('Body', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'rows'    => 5,
      'default' => __('Jacana Safaris and Tours designs tailor-made journeys across Namibia for individuals, couples, families, and groups. We plan your route, accommodation, and activities around your travel style — then deliver friendly, reliable support from your first inquiry to your final day.', 'jacana-luxe'),
    ));

    $this->add_control('highlight', array(
      'label'   => __('Highlight / Founding Statement', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Founded in 2018 by Namibians with 8+ years in tourism and hospitality. Growing under experienced local leadership eager to share the very best of Namibia with the world.', 'jacana-luxe'),
    ));

    $this->add_control('services_label', array(
      'label'   => __('Services Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('What we offer', 'jacana-luxe'),
    ));

    $this->add_control('services', array(
      'label'   => __('Services (one per line)', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => "Tailor-Made Tour Design\nGuided and Self-Drive Options\nPremium Lodges and Camps\nMultilingual Support (EN, DE, FR, AF)\nTrusted Local Expertise\nReliable End-to-End Planning",
    ));

    $this->add_control('photo', array(
      'label'       => __('Photo', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::MEDIA,
      'description' => __('Use the "Simon Game Drive" photo. Upload to Media Library first.', 'jacana-luxe'),
    ));

    $this->add_control('photo_caption', array(
      'label'   => __('Photo Caption', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Simon on a game drive in Etosha National Park', 'jacana-luxe'),
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $services = array_filter(array_map('trim', explode("\n", $settings['services'])));
    $photo    = !empty($settings['photo']['url']) ? $settings['photo']['url'] : '';
    ?>
    <section class="section jacana-about-story">
      <div class="section-inner jacana-about-story-inner">

        <!-- Left: text -->
        <div class="jacana-about-story-text">
          <?php if (!empty($settings['kicker'])) : ?>
            <div class="jacana-about-story-kicker jacana-reveal"><?php echo esc_html($settings['kicker']); ?></div>
          <?php endif; ?>

          <h2 class="jacana-reveal" style="--jacana-delay: 60ms;"><?php echo esc_html($settings['heading']); ?></h2>

          <?php if (!empty($settings['body'])) : ?>
            <p class="jacana-about-story-body jacana-reveal" style="--jacana-delay: 110ms;"><?php echo esc_html($settings['body']); ?></p>
          <?php endif; ?>

          <?php if (!empty($settings['highlight'])) : ?>
            <div class="jacana-about-story-highlight jacana-reveal" style="--jacana-delay: 160ms;">
              <p><?php echo esc_html($settings['highlight']); ?></p>
            </div>
          <?php endif; ?>

          <?php if (!empty($services)) : ?>
            <div class="jacana-about-story-services jacana-reveal" style="--jacana-delay: 200ms;">
              <?php if (!empty($settings['services_label'])) : ?>
                <p class="jacana-about-story-services-label"><?php echo esc_html($settings['services_label']); ?></p>
              <?php endif; ?>
              <ul class="jacana-about-story-checklist">
                <?php foreach ($services as $item) : 
                  $translated_item = apply_filters('jacana_i18n_translate_string', $item, array('area' => 'about_services'));
                ?>
                  <li><?php echo esc_html($translated_item); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
        </div>

        <!-- Right: photo -->
        <div class="jacana-about-story-media jacana-reveal jacana-reveal-right" style="--jacana-delay: 80ms;">
          <?php if ($photo) : ?>
            <figure class="jacana-about-story-figure">
              <img src="<?php echo esc_url($photo); ?>"
                   alt="<?php echo esc_attr($settings['photo_caption']); ?>"
                   loading="lazy">
              <?php if (!empty($settings['photo_caption'])) : ?>
                <figcaption class="jacana-about-story-figcaption"><?php echo esc_html($settings['photo_caption']); ?></figcaption>
              <?php endif; ?>
            </figure>
          <?php else : ?>
            <div class="jacana-about-story-photo-placeholder" aria-hidden="true">
              <span><?php echo esc_html__('Upload the "Simon Game Drive" photo in Elementor', 'jacana-luxe'); ?></span>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </section>
    <?php
  }
}
