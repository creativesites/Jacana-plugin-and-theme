<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Site_Footer extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_site_footer';
  }

  public function get_title() {
    return __('Site Footer', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-footer';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {

    /* ── Brand Section ── */
    $this->start_controls_section('brand_section', array(
      'label' => __('Brand', 'jacana-luxe'),
    ));

    $this->add_control('tagline', array(
      'label'   => __('Tagline', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Crafting tailored Namibia adventures — self-drive safaris, guided tours, transfers, and more since 2016.', 'jacana-luxe'),
    ));

    $this->add_control('instagram_url', array(
      'label'   => __('Instagram URL', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::URL,
      'default' => array('url' => 'https://www.instagram.com/jacana_safaris_tours/'),
    ));

    $this->add_control('facebook_url', array(
      'label'   => __('Facebook URL', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::URL,
      'default' => array('url' => 'https://www.facebook.com/Jacanasafaristours'),
    ));

    $this->add_control('tripadvisor_url', array(
      'label'   => __('TripAdvisor URL', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::URL,
      'default' => array('url' => ''),
    ));

    $this->end_controls_section();

    /* ── Navigation Columns ── */
    $this->start_controls_section('nav_section', array(
      'label' => __('Navigation', 'jacana-luxe'),
    ));

    $col1 = new \Elementor\Repeater();
    $col1->add_control('label', array(
      'label'   => __('Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Link', 'jacana-luxe'),
    ));
    $col1->add_control('url', array(
      'label' => __('URL', 'jacana-luxe'),
      'type'  => \Elementor\Controls_Manager::URL,
    ));

    $this->add_control('col1_title', array(
      'label'   => __('Column 1 Title', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Explore', 'jacana-luxe'),
    ));

    $this->add_control('col1_links', array(
      'label'   => __('Column 1 Links', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::REPEATER,
      'fields'  => $col1->get_controls(),
      'default' => array(
        array('label' => __('Tours', 'jacana-luxe')),
        array('label' => __('Destinations', 'jacana-luxe')),
        array('label' => __('Car Rental', 'jacana-luxe')),
        array('label' => __('Services', 'jacana-luxe')),
        array('label' => __('Gallery', 'jacana-luxe')),
      ),
      'title_field' => '{{{ label }}}',
    ));

    $this->add_control('col2_title', array(
      'label'   => __('Column 2 Title', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Company', 'jacana-luxe'),
    ));

    $col2 = new \Elementor\Repeater();
    $col2->add_control('label', array(
      'label'   => __('Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Link', 'jacana-luxe'),
    ));
    $col2->add_control('url', array(
      'label' => __('URL', 'jacana-luxe'),
      'type'  => \Elementor\Controls_Manager::URL,
    ));

    $this->add_control('col2_links', array(
      'label'   => __('Column 2 Links', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::REPEATER,
      'fields'  => $col2->get_controls(),
      'default' => array(
        array('label' => __('About Us', 'jacana-luxe')),
        array('label' => __('Reviews', 'jacana-luxe')),
        array('label' => __('FAQ', 'jacana-luxe')),
        array('label' => __('Contact', 'jacana-luxe')),
      ),
      'title_field' => '{{{ label }}}',
    ));

    $this->end_controls_section();

    /* ── Contact ── */
    $this->start_controls_section('contact_section', array(
      'label' => __('Contact', 'jacana-luxe'),
    ));

    $this->add_control('contact_title', array(
      'label'   => __('Contact Title', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Get in Touch', 'jacana-luxe'),
    ));

    $this->add_control('phone_nam', array(
      'label'   => __('Phone (Namibia)', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => '+264 81 333 2021',
    ));

    $this->add_control('phone_ger', array(
      'label'   => __('Phone (Germany)', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => '+49 152 2317 2898',
    ));

    $this->add_control('email', array(
      'label'   => __('Email', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => 'info@jacanasafaristours.com',
    ));

    $this->add_control('address', array(
      'label'   => __('Address', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __("Sao Tome Street\nAdonai Court\nWindhoek, Namibia", 'jacana-luxe'),
    ));

    $this->end_controls_section();

    /* ── Newsletter CTA ── */
    $this->start_controls_section('cta_section', array(
      'label' => __('Footer CTA', 'jacana-luxe'),
    ));

    $this->add_control('cta_kicker', array(
      'label'   => __('CTA Kicker', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Ready for Namibia?', 'jacana-luxe'),
    ));

    $this->add_control('cta_heading', array(
      'label'   => __('CTA Heading', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Start planning your adventure', 'jacana-luxe'),
    ));

    $this->add_control('cta_button_label', array(
      'label'   => __('Button Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Begin your journey', 'jacana-luxe'),
    ));

    $this->add_control('cta_bg_image', array(
      'label' => __('CTA Background Image', 'jacana-luxe'),
      'type'  => \Elementor\Controls_Manager::MEDIA,
      'default' => array(
        'url' => (wp_get_upload_dir()['baseurl'] ?? '') . '/2026/02/Etosha-Elephants.webp',
      ),
    ));


    $this->end_controls_section();


    /* ── Copyright ── */
    $this->start_controls_section('legal_section', array(
      'label' => __('Copyright', 'jacana-luxe'),
    ));

    $this->add_control('copyright_text', array(
      'label'   => __('Copyright text', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Jacana Safaris & Tours. All rights reserved.', 'jacana-luxe'),
    ));

    $this->end_controls_section();
  }

  private function phone_link($val) {
    return preg_replace('/[^0-9+]/', '', (string) $val);
  }

  protected function render() {
    $s = $this->get_settings_for_display();
    $logo_url = function_exists('jacana_luxe_get_logo_url') ? jacana_luxe_get_logo_url() : '';
    $home_url = home_url('/');
    if (function_exists('jacana_i18n_localize_url')) {
      $home_url = jacana_i18n_localize_url($home_url);
    }
    ?>
    <footer class="jacana-sf-section">

      <!-- CTA Banner -->
      <?php
      $cta_bg = !empty($s['cta_bg_image']['url']) ? $s['cta_bg_image']['url'] : '';
      $banner_style = $cta_bg ? ' style="background-image: url(' . esc_url($cta_bg) . ');"' : '';
      ?>
      <div class="jacana-sf-cta-banner jacana-reveal"<?php echo $banner_style; ?>>

        <div class="jacana-sf-cta-inner">
          <div class="jacana-sf-cta-content">
            <span class="jacana-sf-cta-kicker"><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $s['cta_kicker'], array('area' => 'footer_cta'))); ?></span>
            <h2 class="jacana-sf-cta-heading"><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $s['cta_heading'], array('area' => 'footer_cta'))); ?></h2>
          </div>
          <a href="<?php echo esc_url($s['cta_button_link']['url'] ?? '#'); ?>"
             class="jacana-sf-cta-button"
             <?php echo !empty($s['cta_button_link']['is_external']) ? 'target="_blank" rel="noopener"' : ''; ?>>
            <?php echo esc_html(apply_filters('jacana_i18n_translate_string', $s['cta_button_label'], array('area' => 'footer_cta'))); ?>
            <span class="jacana-sf-arrow" aria-hidden="true">&rarr;</span>
          </a>
        </div>
        <div class="jacana-sf-cta-glow" aria-hidden="true"></div>
      </div>

      <!-- Oshiwambo cultural stripe -->
      <div class="jacana-sf-stripe" aria-hidden="true"></div>

      <!-- Main footer body -->
      <div class="jacana-sf-body">
        <div class="jacana-sf-inner">

          <!-- Brand column -->
          <div class="jacana-sf-brand jacana-reveal">
            <?php if ($logo_url) : ?>
              <a class="jacana-sf-logo-link" href="<?php echo esc_url($home_url); ?>" aria-label="<?php echo esc_attr__('Jacana Safaris & Tours — home', 'jacana-luxe'); ?>">
                <img class="jacana-sf-logo" src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr__('Jacana Safaris & Tours logo', 'jacana-luxe'); ?>">
              </a>
            <?php endif; ?>
            <p class="jacana-sf-tagline"><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $s['tagline'], array('area' => 'footer_brand'))); ?></p>

            <div class="jacana-sf-social" role="list">
              <?php if (!empty($s['instagram_url']['url'])) : ?>
                <a class="jacana-sf-social-link" href="<?php echo esc_url($s['instagram_url']['url']); ?>" target="_blank" rel="noopener noreferrer" role="listitem" aria-label="<?php echo esc_attr__('Instagram', 'jacana-luxe'); ?>">
                  <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                </a>
              <?php endif; ?>
              <?php if (!empty($s['facebook_url']['url'])) : ?>
                <a class="jacana-sf-social-link" href="<?php echo esc_url($s['facebook_url']['url']); ?>" target="_blank" rel="noopener noreferrer" role="listitem" aria-label="<?php echo esc_attr__('Facebook', 'jacana-luxe'); ?>">
                  <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
              <?php endif; ?>
              <?php if (!empty($s['tripadvisor_url']['url'])) : ?>
                <a class="jacana-sf-social-link" href="<?php echo esc_url($s['tripadvisor_url']['url']); ?>" target="_blank" rel="noopener noreferrer" role="listitem" aria-label="<?php echo esc_attr__('TripAdvisor', 'jacana-luxe'); ?>">
                  <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12.006 4.295c-2.67 0-5.338.784-7.645 2.353H0l1.963 2.135a5.997 5.997 0 004.04 10.432 5.98 5.98 0 004.003-1.533l2 2.318 2-2.318a5.983 5.983 0 004.003 1.533 5.998 5.998 0 004.04-10.432L24 6.648h-4.35a13.573 13.573 0 00-7.644-2.353zM6.003 17.215a3.998 3.998 0 110-7.996 3.998 3.998 0 010 7.996zm11.994 0a3.998 3.998 0 110-7.996 3.998 3.998 0 010 7.996z"/></svg>
                </a>
              <?php endif; ?>
            </div>
          </div>

          <!-- Nav Column 1 -->
          <nav class="jacana-sf-nav-col jacana-reveal" style="--jacana-delay: 100ms;" aria-label="<?php echo esc_attr($s['col1_title']); ?>">
            <h3 class="jacana-sf-col-title"><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $s['col1_title'], array('area' => 'footer_nav'))); ?></h3>
            <ul>
              <?php foreach ($s['col1_links'] as $link) : ?>
                <li>
                  <a href="<?php echo esc_url($link['url']['url'] ?? '#'); ?>"
                     <?php echo !empty($link['url']['is_external']) ? 'target="_blank" rel="noopener"' : ''; ?>>
                    <?php echo esc_html(apply_filters('jacana_i18n_translate_string', $link['label'], array('area' => 'footer_link'))); ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </nav>

          <!-- Nav Column 2 -->
          <nav class="jacana-sf-nav-col jacana-reveal" style="--jacana-delay: 200ms;" aria-label="<?php echo esc_attr($s['col2_title']); ?>">
            <h3 class="jacana-sf-col-title"><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $s['col2_title'], array('area' => 'footer_nav'))); ?></h3>
            <ul>
              <?php foreach ($s['col2_links'] as $link) : ?>
                <li>
                  <a href="<?php echo esc_url($link['url']['url'] ?? '#'); ?>"
                     <?php echo !empty($link['url']['is_external']) ? 'target="_blank" rel="noopener"' : ''; ?>>
                    <?php echo esc_html(apply_filters('jacana_i18n_translate_string', $link['label'], array('area' => 'footer_link'))); ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </nav>

          <!-- Contact column -->
          <div class="jacana-sf-contact-col jacana-reveal" style="--jacana-delay: 300ms;">
            <h3 class="jacana-sf-col-title"><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $s['contact_title'], array('area' => 'footer_contact'))); ?></h3>
            <address class="jacana-sf-address">
              <?php if (!empty($s['phone_nam'])) : ?>
                <a href="tel:<?php echo esc_attr($this->phone_link($s['phone_nam'])); ?>" class="jacana-sf-contact-item">
                  <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                  <span><?php echo esc_html($s['phone_nam']); ?></span>
                </a>
              <?php endif; ?>
              <?php if (!empty($s['phone_ger'])) : ?>
                <a href="tel:<?php echo esc_attr($this->phone_link($s['phone_ger'])); ?>" class="jacana-sf-contact-item">
                  <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                  <span><?php echo esc_html($s['phone_ger']); ?></span>
                </a>
              <?php endif; ?>
              <?php if (!empty($s['email'])) : ?>
                <a href="mailto:<?php echo esc_attr($s['email']); ?>" class="jacana-sf-contact-item">
                  <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                  <span><?php echo esc_html($s['email']); ?></span>
                </a>
              <?php endif; ?>
              <?php if (!empty($s['address'])) : ?>
                <div class="jacana-sf-contact-item jacana-sf-contact-item--address">
                  <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                  <span><?php echo nl2br(esc_html($s['address'])); ?></span>
                </div>
              <?php endif; ?>
            </address>
          </div>

        </div>
      </div>

      <!-- Bottom bar -->
      <div class="jacana-sf-bar">
        <div class="jacana-sf-bar-inner">
          <span class="jacana-sf-copyright">&copy; <?php echo esc_html(gmdate('Y') . ' ' . $s['copyright_text']); ?></span>
          
        </div>
      </div>

    </footer>
    <?php
  }
}
