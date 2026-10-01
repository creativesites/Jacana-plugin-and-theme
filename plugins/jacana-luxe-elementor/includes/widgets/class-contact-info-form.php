<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Contact_Info_Form extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_contact_info_form';
  }

  public function get_title() {
    return __('Contact Info & Proposal CTA', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-form-horizontal';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    // Info Side
    $this->add_control('info_kicker', array(
      'label'   => __('Info Kicker', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Direct contact', 'jacana-luxe'),
    ));

    $this->add_control('info_title', array(
      'label'   => __('Info Title', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Get in touch', 'jacana-luxe'),
    ));

    // Location
    $this->add_control('loc_headline', array(
        'label'   => __('Location Headline', 'jacana-luxe'),
        'type'    => \Elementor\Controls_Manager::TEXT,
        'default' => __('Location', 'jacana-luxe'),
    ));
    $this->add_control('loc_subline', array(
        'label'   => __('Location Subline', 'jacana-luxe'),
        'type'    => \Elementor\Controls_Manager::TEXT,
        'default' => __('Windhoek, Namibia', 'jacana-luxe'),
    ));
    $this->add_control('loc_address', array(
        'label'   => __('Location Address', 'jacana-luxe'),
        'type'    => \Elementor\Controls_Manager::TEXT,
        'default' => __('Sao Tome Street Adonai Court', 'jacana-luxe'),
    ));

    // Contacts
    $this->add_control('email', array(
      'label'   => __('Email address', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => 'info@jacanasafaristours.com',
    ));

    $this->add_control('phone_nam', array(
        'label'   => __('Phone (Namibia)', 'jacana-luxe'),
        'type'    => \Elementor\Controls_Manager::TEXT,
        'default' => '+264 81 3332 021',
    ));

    $this->add_control('phone_ger', array(
        'label'   => __('Phone (Germany)', 'jacana-luxe'),
        'type'    => \Elementor\Controls_Manager::TEXT,
        'default' => '+49 152 2317 2898',
    ));

    // CTA Side
    $this->add_control('cta_kicker', array(
        'label'   => __('CTA Kicker', 'jacana-luxe'),
        'type'    => \Elementor\Controls_Manager::TEXT,
        'default' => __('Enquiry', 'jacana-luxe'),
    ));

    $this->add_control('cta_title', array(
        'label'   => __('CTA Title', 'jacana-luxe'),
        'type'    => \Elementor\Controls_Manager::TEXT,
        'default' => __('Start your journey', 'jacana-luxe'),
    ));

    $this->add_control('cta_copy', array(
        'label'   => __('CTA Copy', 'jacana-luxe'),
        'type'    => \Elementor\Controls_Manager::TEXTAREA,
        'default' => __('Tell us your dates and traveler count. We handle the rest with tailored guidance.', 'jacana-luxe'),
    ));

    $this->add_control('cta_button_text', array(
        'label'   => __('CTA Button Text', 'jacana-luxe'),
        'type'    => \Elementor\Controls_Manager::TEXT,
        'default' => __('Get your tailored proposal', 'jacana-luxe'),
    ));

    $this->add_control('cta_button_link', array(
        'label'   => __('CTA Button Link', 'jacana-luxe'),
        'type'    => \Elementor\Controls_Manager::URL,
        'default' => array('url' => '/booking-studio/'),
    ));

    $this->end_controls_section();
  }

  private function make_phone_link($value) {
    return preg_replace('/[^0-9+]/', '', (string) $value);
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $email = (string) $settings['email'];
    $phone_nam = (string) $settings['phone_nam'];
    $phone_ger = (string) $settings['phone_ger'];
    
    $phone_nam_link = $this->make_phone_link($phone_nam);
    $phone_ger_link = $this->make_phone_link($phone_ger);
    ?>
    <section class="jacana-cif-section">
      <div class="jacana-cif-inner">
        <div class="jacana-cif-grid">
          
          <!-- Info Column -->
          <div class="jacana-cif-info-panel jacana-reveal">
            <span class="jacana-cif-kicker"><?php echo esc_html($settings['info_kicker']); ?></span>
            <h2 class="jacana-cif-title"><?php echo esc_html($settings['info_title']); ?></h2>
            
            <div class="jacana-cif-cards">
              <!-- Location Card -->
              <div class="jacana-cif-card jacana-cif-card--location">
                <div class="jacana-cif-card-icon" aria-hidden="true">
                  <i class="eicon-map-pin"></i>
                </div>
                <div class="jacana-cif-card-content">
                  <strong><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $settings['loc_headline'], array('area' => 'contact_info'))); ?></strong>
                  <span class="jacana-cif-subline"><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $settings['loc_subline'], array('area' => 'contact_info'))); ?></span>
                  <p><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $settings['loc_address'], array('area' => 'contact_info'))); ?></p>
                </div>
              </div>

              <!-- Email Card -->
              <a href="mailto:<?php echo esc_attr($email); ?>" class="jacana-cif-card jacana-cif-card--email">
                <div class="jacana-cif-card-icon" aria-hidden="true">
                  <i class="eicon-mail"></i>
                </div>
                <div class="jacana-cif-card-content">
                  <strong><?php echo esc_html__('Email', 'jacana-luxe'); ?></strong>
                  <span><?php echo esc_html($email); ?></span>
                </div>
              </a>

              <!-- Phone Card -->
              <div class="jacana-cif-card jacana-cif-card--phone">
                <div class="jacana-cif-card-icon" aria-hidden="true">
                  <i class="eicon-phone"></i>
                </div>
                <div class="jacana-cif-card-content">
                  <strong><?php echo esc_html__('Call us', 'jacana-luxe'); ?></strong>
                  <a href="tel:<?php echo esc_attr($phone_nam_link); ?>"><?php echo esc_html($phone_nam); ?></a>
                  <a href="tel:<?php echo esc_attr($phone_ger_link); ?>"><?php echo esc_html($phone_ger); ?></a>
                </div>
              </div>
            </div>

            <!-- Social Links -->
            <div class="jacana-cif-social">
              <a href="https://www.facebook.com/Jacanasafaristours" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                <i class="fab fa-facebook-f"></i>
              </a>
              <a href="https://www.instagram.com/jacana_safaris_tours/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                <i class="fab fa-instagram"></i>
              </a>
            </div>
          </div>

          <!-- CTA Column -->
          <div class="jacana-cif-cta-panel jacana-reveal jacana-reveal-right" style="--jacana-delay: 200ms;">
             <div class="jacana-cif-cta-card">
                <span class="jacana-cif-kicker"><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $settings['cta_kicker'], array('area' => 'contact_cta'))); ?></span>
                <h3 class="jacana-cif-cta-title"><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $settings['cta_title'], array('area' => 'contact_cta'))); ?></h3>
                <p class="jacana-cif-cta-copy"><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $settings['cta_copy'], array('area' => 'contact_cta'))); ?></p>
                
                <a href="#"
                   class="jacana-cif-cta-button"
                   data-jacana-booking-modal="true"
                   data-jacana-service="tailor_made"
                   data-jacana-widget="jacana_contact_info_form">
                  <?php echo esc_html(apply_filters('jacana_i18n_translate_string', $settings['cta_button_text'], array('area' => 'contact_cta'))); ?>
                  <span class="jacana-cif-arrow" aria-hidden="true">&rarr;</span>
                </a>
             </div>
          </div>

        </div>
      </div>
    </section>
    <?php
  }
}
