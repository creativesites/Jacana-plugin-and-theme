<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Cta_Banner extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_cta_banner';
  }

  public function get_title() {
    return __('CTA Banner', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-call-to-action';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section(
      'content_section',
      array(
        'label' => __('Content', 'jacana-luxe'),
      )
    );

    $this->add_control(
      'heading',
      array(
        'label' => __('Heading', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => __('Begin your Namibia journey', 'jacana-luxe'),
      )
    );

    $this->add_control(
      'copy',
      array(
        'label' => __('Copy', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXTAREA,
        'default' => __('Tell us your dream itinerary and we’ll craft a bespoke proposal within 48 hours.', 'jacana-luxe'),
      )
    );

    $this->add_control(
      'primary_label',
      array(
        'label' => __('Primary Button Label', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => __('Request a private consultation', 'jacana-luxe'),
      )
    );

    $this->add_control(
      'primary_link',
      array(
        'label' => __('Primary Button Link', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::URL,
        'placeholder' => 'https://',
      )
    );

    $this->add_control(
      'secondary_label',
      array(
        'label' => __('Secondary Button Label', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => __('Explore tours', 'jacana-luxe'),
      )
    );

    $this->add_control(
      'secondary_link',
      array(
        'label' => __('Secondary Button Link', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::URL,
        'placeholder' => '#',
      )
    );

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $primary = $settings['primary_link'];
    $secondary = $settings['secondary_link'];
    ?>
    <section class="jacana-luxe-bespoke-cta">
        <div class="blur-blob blob-sky"></div>
        <div class="blur-blob blob-dune"></div>
        
        <div class="section-inner container">
            <div class="cta-content-center jacana-reveal">
                
                <div class="bespoke-tagline">
                    <span class="line"></span>
                    <span class="tag-text">Bespoke Experiences</span>
                    <span class="line"></span>
                </div>
                
                <h2 class="luxe-main-heading">
                    Begin your <span class="italic-dune">Namibia</span> journey
                </h2>
                
                <p class="luxe-description">
                    <?php echo esc_html($settings['copy']); ?>
                </p>
                
                <div class="luxe-button-group">
                    <a class="btn-dune-solid" href="<?php echo esc_url($primary['url'] ?? '#'); ?>">
                        <?php echo esc_html($settings['primary_label']); ?>
                    </a>
                    <a class="btn-luxe-text" href="<?php echo esc_url($secondary['url'] ?? '#'); ?>">
                        <?php echo esc_html($settings['secondary_label']); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="oshi-footer-accent">
            <div class="oshi-stripe-pattern"></div>
        </div>
    </section>
    <?php
}
}
