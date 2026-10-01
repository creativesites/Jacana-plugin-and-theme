<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Hero_Slider extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_hero_slider';
  }

  public function get_title() {
    return __('Luxe Hero Slider', 'jacana-luxe');
  }

  private function get_available_menus() {
    $menus = wp_get_nav_menus();
    $options = array(0 => __('Select a Menu', 'jacana-luxe'));
    foreach ($menus as $menu) {
      $options[$menu->term_id] = $menu->name;
    }
    return $options;
  }

  public function get_icon() {
    return 'eicon-slider-full-screen';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  public function get_script_depends() {
    return array('jacana-luxe-widget-js-hero-slider');
  }

  protected function register_controls() {
    $this->start_controls_section(
      'luxe_content_section',
      array(
        'label' => __('Luxe Design Settings', 'jacana-luxe'),
      )
    );

    $this->add_control(
      'subtitle_top',
      array(
        'label' => __('Subtitle (Top)', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => __('Discover the beauty of', 'jacana-luxe'),
      )
    );

    $this->add_control(
      'clipped_title',
      array(
        'label' => __('Clipped Main Title', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => __('MOUNTAINS', 'jacana-luxe'),
        'description' => __('This title uses a background-clip effect.', 'jacana-luxe'),
      )
    );

    $this->add_control(
      'title_bg_image',
      array(
        'label' => __('Title Clipping Image', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::MEDIA,
        'description' => __('The image visible inside the main title text.', 'jacana-luxe'),
      )
    );

    $this->add_control(
      'subtitle_bottom',
      array(
        'label' => __('Subtitle (Bottom)', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => __('Find your path', 'jacana-luxe'),
      )
    );

    $this->add_control(
      'show_nav',
      array(
        'label' => __('Show Integrated Navigation', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::SWITCHER,
        'default' => 'no',
      )
    );

    $this->add_control(
      'nav_menu',
      array(
        'label' => __('Select Menu', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'options' => $this->get_available_menus(),
        'condition' => array(
          'show_nav' => 'yes',
        ),
      )
    );

    $this->add_control(
      'exp_title',
      array(
        'label' => __('Experience List Title', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => __('Experience the beauty', 'jacana-luxe'),
        'separator' => 'before',
      )
    );

    $this->end_controls_section();

    $this->start_controls_section(
      'feature_boxes_section',
      array(
        'label' => __('Feature Boxes (Icon Cards)', 'jacana-luxe'),
      )
    );

    $features_repeater = new \Elementor\Repeater();
    $features_repeater->add_control(
      'label',
      array(
        'label' => __('Label', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => __('Hike', 'jacana-luxe'),
      )
    );
    $features_repeater->add_control(
      'icon_svg',
      array(
        'label' => __('Icon SVG Code', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXTAREA,
        'description' => __('Paste SVG <path> or full <svg> code.', 'jacana-luxe'),
      )
    );
    $features_repeater->add_control(
      'link',
      array(
        'label' => __('Link', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::URL,
      )
    );

    $this->add_control(
      'feature_boxes',
      array(
        'label' => __('Activity Boxes', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::REPEATER,
        'fields' => $features_repeater->get_controls(),
        'default' => array(
          array('label' => __('Hike', 'jacana-luxe')),
          array('label' => __('Ski', 'jacana-luxe')),
          array('label' => __('Explore', 'jacana-luxe')),
        ),
        'title_field' => '{{{ label }}}',
      )
    );

    $this->end_controls_section();

    $this->start_controls_section(
      'slides_section',
      array(
        'label' => __('Slides (Backgrounds & Gallery)', 'jacana-luxe'),
      )
    );

    $repeater = new \Elementor\Repeater();
    $repeater->add_control(
      'kicker',
      array(
        'label' => __('Kicker', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => __('Namibia • Tailor-made Journeys', 'jacana-luxe'),
      )
    );
    $repeater->add_control(
      'title',
      array(
        'label' => __('Title', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => __('Jacana Safaris & Tours', 'jacana-luxe'),
      )
    );
    $repeater->add_control(
      'copy',
      array(
        'label' => __('Description', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXTAREA,
        'default' => __('Mastering the art of perfect adventure for 8+ years in the wild.', 'jacana-luxe'),
      )
    );
    $repeater->add_control(
      'image',
      array(
        'label' => __('Background Image', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::MEDIA,
        'description' => __('Used when no video is set, or as video poster.', 'jacana-luxe'),
      )
    );
    $repeater->add_control(
      'video_url',
      array(
        'label' => __('Background Video URL', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'description' => __('Full URL to an .mp4/.mov file (hosted or from Media Library). Leave empty to use the image.', 'jacana-luxe'),
        'placeholder' => 'https://example.com/video.mp4',
      )
    );

    $this->add_control(
      'slides',
      array(
        'type' => \Elementor\Controls_Manager::REPEATER,
        'fields' => $repeater->get_controls(),
        'default' => array(),
        'title_field' => '{{{ title }}}',
      )
    );

    $this->add_control(
      'primary_label',
      array(
        'label' => __('Primary Button Label', 'jacana-luxe'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => __('Plan your journey', 'jacana-luxe'),
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
        'default' => __('Discover more', 'jacana-luxe'),
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
    $settings      = $this->get_settings_for_display();
    $slides        = $settings['slides'];
    $feature_boxes = $settings['feature_boxes'];
    $show_nav      = 'yes' === $settings['show_nav'];

    if (empty($slides)) {
      if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
        echo '<div style="padding: 40px; text-align: center; background: #eee;">' . esc_html__('Please add at least one slide to the Hero Slider.', 'jacana-luxe') . '</div>';
      }
      return;
    }

    $bg_image_1    = !empty($slides[0]['image']['url']) ? $slides[0]['image']['url'] : '';
    $title_bg_url  = !empty($settings['title_bg_image']['url']) ? $settings['title_bg_image']['url'] : $bg_image_1;
    $subtitle_top  = $settings['subtitle_top'];
    $main_title    = $settings['clipped_title'];
    $subtitle_btm  = $settings['subtitle_bottom'];
    $exp_title     = $settings['exp_title'];

    ?>
    <div class="jacana-luxe-hero-wrapper" id="hero-<?php echo esc_attr($this->get_id()); ?>">
      <div class="container luxe-container" style="--luxe-bg-1: url('<?php echo esc_url($bg_image_1); ?>');">
        
        <?php if ($show_nav) : ?>
          <nav class="glass luxe-nav">
            <ul>
              <li>
                <a href="<?php echo esc_url(home_url()); ?>" aria-label="Jacana Home">
                <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 457.001 457.001" fill="#fff">
                    <path d="M42.755 125.406c.178 2.901 2.583 5.163 5.489 5.163h71.548c2.551 0 4.768-1.754 5.354-4.237.39-1.652.587-3.307.587-4.919 0-11.769-9.575-21.344-21.344-21.344-2.759 0-5.44.523-7.946 1.536-4.61-5.051-11.195-8.036-18.177-8.036-8.98 0-17.272 5.011-21.551 12.753-8.083 2.014-14.012 9.324-14.012 17.935 0 .335.019.664.041.993l.011.156zm18.011-8.62c2.155-.124 4.039-1.497 4.815-3.511 2.01-5.207 7.106-8.705 12.685-8.705 4.883 0 9.417 2.646 11.832 6.905.78 1.376 2.116 2.35 3.665 2.672s3.162-.039 4.427-.99c1.813-1.365 3.956-2.086 6.199-2.086 5.074 0 9.308 3.673 10.179 8.5h-59.21c1.286-1.597 3.215-2.659 5.408-2.785z" />
                    <path d="M456.383 240.537c-1.397-2.696-4.717-3.75-7.415-2.351l-43.688 22.653-49.223-72.849c-1.205-1.783-3.334-2.701-5.462-2.346l-8.334 1.389-48.065-78.827c-.78-1.278-2.047-2.184-3.509-2.507-1.464-.323-2.992-.037-4.238.794l-7.887 5.258-18.572-22.302h35.1c2.725 0 5.038-1.994 5.439-4.689.239-1.603.351-2.972.351-4.311 0-15.958-12.982-28.94-28.94-28.94-3.243 0-6.464.553-9.516 1.621-6.606-9.072-17.148-14.531-28.574-14.531-11.432 0-21.977 5.459-28.583 14.531-3.052-1.068-6.272-1.621-9.517-1.621-15.952 0-28.931 12.982-28.931 28.94 0 1.339.111 2.708.351 4.311.401 2.695 2.715 4.689 5.439 4.689h43.535l-59.828 76.22-7.766-5.177c-2.42-1.613-5.677-1.064-7.433 1.253l-39.643 52.272-9.948-10.695c-1.723-1.852-4.487-2.29-6.698-1.062l-14.494 8.052-61.072-57.255c-2.216-2.077-5.695-1.966-7.774.251-2.077 2.216-1.965 5.697.251 7.774l64 60c1.744 1.635 4.345 1.957 6.433.795l14.235-7.908 77.066 82.847c1.41 1.515 3.553 2.111 5.538 1.542l11.041-3.154 132.763 121.753c1.057.969 2.389 1.447 3.716 1.447 1.488 0 2.971-.6 4.056-1.783 2.053-2.239 1.902-5.718-.336-7.771l-76.698-70.337 86.023-39.427 11.758 5.039c1.514.649 3.238.585 4.699-.173l108-56c2.695-1.399 3.748-4.718 2.35-7.415zM177.93 78.449c.998-8.954 8.609-15.94 17.82-15.94 3.15 0 6.142.801 8.891 2.379 1.296.744 2.841.929 4.274.512 1.436-.417 2.639-1.401 3.334-2.724 4.236-8.066 12.514-13.077 21.601-13.077 9.081 0 17.354 5.011 21.591 13.077.695 1.323 1.898 2.307 3.334 2.724 1.435.418 2.978.233 4.274-.512 2.749-1.579 5.74-2.379 8.891-2.379 9.216 0 16.832 6.986 17.83 15.94h-38.941l-9.057-10.876c-1.063-1.276-2.616-1.983-4.306-1.98-1.661.024-3.222.797-4.247 2.104l-8.44 10.752H177.93zm165.346 214.54-11.609-4.976c-1.427-.612-3.045-.592-4.459.055l-92.454 42.375-49.537-45.428c-1.41-1.293-3.393-1.76-5.229-1.235l-10.759 3.074-60.15-64.662 37.674-49.677 37.492 26.988c.98.719 2.119 1.065 3.248 1.065 1.692 0 3.362-.779 4.44-2.248 1.796-2.449 1.267-5.891-1.183-7.688l-25.417-18.639 72.336-92.155 32.15 38.605-25.925 22.468c-2.295 1.989-2.543 5.463-.554 7.758 1.088 1.255 2.619 1.898 4.158 1.898 1.277 0 2.56-.442 3.601-1.344l36.683-30.403 47.019 77.11c1.17 1.917 3.385 2.933 5.601 2.562l8.516-1.419 46.529 68.862-52.171 27.054z" />
                    <path d="m305.05 235.595-16.414 13.679-32.361-20.594c-2.019-1.284-4.636-1.116-6.475.415l-39 32.5c-2.333 1.945-2.648 5.413-.704 7.746 1.088 1.305 2.652 1.979 4.229 1.979 1.241 0 2.49-.418 3.519-1.275l35.914-29.929 32.361 20.594c2.02 1.284 4.636 1.116 6.475-.415l19.5-16.25c2.333-1.945 2.648-5.413.704-7.746-1.945-2.333-5.413-2.648-7.748-.704zm-78.532 137.466-51.366-25.64c-1.432-.715-3.104-.77-4.578-.153l-17.497 7.317-77.613-49.402c-2.561-1.631-5.962-.876-7.593 1.687-1.631 2.563-.876 5.962 1.687 7.593l80.037 50.945c1.519.967 3.415 1.129 5.075.434l17.824-7.454 49.112 24.515c.788.394 1.626.58 2.452.58 2.019 0 3.962-1.116 4.925-3.045 1.356-2.717.252-6.02-2.465-7.377zm120.013-220.145h64.642c3.037 0 5.5-2.462 5.5-5.5 0-12.009-9.77-21.778-21.778-21.778-3.161 0-6.286.701-9.137 2.02-4.88-4.48-11.218-6.976-17.992-6.976-14.741 0-26.734 11.993-26.734 26.734-.001 3.037 2.462 5.5 5.499 5.5zm21.235-21.235c4.981 0 9.566 2.304 12.578 6.32.894 1.19 2.23 1.968 3.707 2.156 1.479.188 2.965-.231 4.128-1.16 1.932-1.544 4.254-2.36 6.716-2.36 3.936 0 7.385 2.12 9.267 5.278h-51.138c2.234-5.971 7.999-10.234 14.742-10.234z" />
                  </svg>
                </a>
              </li>

              <li class="mob-menu-toggle">
                <a href="#">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill-rule="evenodd" clip-rule="evenodd">
                    <path d="M12 16c1.656 0 3 1.344 3 3s-1.344 3-3 3-3-1.344-3-3 1.344-3 3-3zm0 1c1.104 0 2 .896 2 2s-.896 2-2 2-2-.896-2-2 .896-2 2-2zm0-8c1.656 0 3 1.344 3 3s-1.344 3-3 3-3-1.344-3-3 1.344-3 3-3zm0 1c1.104 0 2 .896 2 2s-.896 2-2 2-2-.896-2-2 .896-2 2-2zm0-8c1.656 0 3 1.344 3 3s-1.344 3-3 3-3-1.344-3-3 1.344-3 3-3zm0 1c1.104 0 2 .896 2 2s-.896 2-2 2-2-.896-2-2 .896-2 2-2z" />
                  </svg>
                </a>
              </li>
            </ul>
            
            <?php 
            if (!empty($settings['nav_menu'])) {
              wp_nav_menu(array(
                'menu' => $settings['nav_menu'],
                'container' => false,
                'items_wrap' => '<ul>%3$s</ul>',
              ));
            } else {
              ?>
              <ul>
                <li><a href="#">Tours</a></li>
                <li><a href="#">Testimonials</a></li>
                <li><a href="#">Gallery</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Contact</a></li>
              </ul>
              <?php
            }
            ?>

            <ul>
              <li>
                <a href="#" class="cta luxe-cta-btn">
                  <span>Book a tour</span>
                  <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 457.001 457.001">
                    <path d="M42.755 125.406c.178 2.901 2.583 5.163 5.489 5.163h71.548c2.551 0 4.768-1.754 5.354-4.237.39-1.652.587-3.307.587-4.919 0-11.769-9.575-21.344-21.344-21.344-2.759 0-5.44.523-7.946 1.536-4.61-5.051-11.195-8.036-18.177-8.036-8.98 0-17.272 5.011-21.551 12.753-8.083 2.014-14.012 9.324-14.012 17.935 0 .335.019.664.041.993l.011.156zm18.011-8.62c2.155-.124 4.039-1.497 4.815-3.511 2.01-5.207 7.106-8.705 12.685-8.705 4.883 0 9.417 2.646 11.832 6.905.78 1.376 2.116 2.35 3.665 2.672s3.162-.039 4.427-.99c1.813-1.365 3.956-2.086 6.199-2.086 5.074 0 9.308 3.673 10.179 8.5h-59.21c1.286-1.597 3.215-2.659 5.408-2.785z" />
                    <path d="M456.383 240.537c-1.397-2.696-4.717-3.75-7.415-2.351l-43.688 22.653-49.223-72.849c-1.205-1.783-3.334-2.701-5.462-2.346l-8.334 1.389-48.065-78.827c-.78-1.278-2.047-2.184-3.509-2.507-1.464-.323-2.992-.037-4.238.794l-7.887 5.258-18.572-22.302h35.1c2.725 0 5.038-1.994 5.439-4.689.239-1.603.351-2.972.351-4.311 0-15.958-12.982-28.94-28.94-28.94-3.243 0-6.464.553-9.516 1.621-6.606-9.072-17.148-14.531-28.574-14.531-11.432 0-21.977 5.459-28.583 14.531-3.052-1.068-6.272-1.621-9.517-1.621-15.952 0-28.931 12.982-28.931 28.94 0 1.339.111 2.708.351 4.311.401 2.695 2.715 4.689 5.439 4.689h43.535l-59.828 76.22-7.766-5.177c-2.42-1.613-5.677-1.064-7.433 1.253l-39.643 52.272-9.948-10.695c-1.723-1.852-4.487-2.29-6.698-1.062l-14.494 8.052-61.072-57.255c-2.216-2.077-5.695-1.966-7.774.251-2.077 2.216-1.965 5.697.251 7.774l64 60c1.744 1.635 4.345 1.957 6.433.795l14.235-7.908 77.066 82.847c1.41 1.515 3.553 2.111 5.538 1.542l11.041-3.154 132.763 121.753c1.057.969 2.389 1.447 3.716 1.447 1.488 0 2.971-.6 4.056-1.783 2.053-2.239 1.902-5.718-.336-7.771l-76.698-70.337 86.023-39.427 11.758 5.039c1.514.649 3.238.585 4.699-.173l108-56c2.695-1.399 3.748-4.718 2.35-7.415zM177.93 78.449c.998-8.954 8.609-15.94 17.82-15.94 3.15 0 6.142.801 8.891 2.379 1.296.744 2.841.929 4.274.512 1.436-.417 2.639-1.401 3.334-2.724 4.236-8.066 12.514-13.077 21.601-13.077 9.081 0 17.354 5.011 21.591 13.077.695 1.323 1.898 2.307 3.334 2.724 1.435.418 2.978.233 4.274-.512 2.749-1.579 5.74-2.379 8.891-2.379 9.216 0 16.832 6.986 17.83 15.94h-38.941l-9.057-10.876c-1.063-1.276-2.616-1.983-4.306-1.98-1.661.024-3.222.797-4.247 2.104l-8.44 10.752H177.93zm165.346 214.54-11.609-4.976c-1.427-.612-3.045-.592-4.459.055l-92.454 42.375-49.537-45.428c-1.41-1.293-3.393-1.76-5.229-1.235l-10.759 3.074-60.15-64.662 37.674-49.677 37.492 26.988c.98.719 2.119 1.065 3.248 1.065 1.692 0 3.362-.779 4.44-2.248 1.796-2.449 1.267-5.891-1.183-7.688l-25.417-18.639 72.336-92.155 32.15 38.605-25.925 22.468c-2.295 1.989-2.543 5.463-.554 7.758 1.088 1.255 2.619 1.898 4.158 1.898 1.277 0 2.56-.442 3.601-1.344l36.683-30.403 47.019 77.11c1.17 1.917 3.385 2.933 5.601 2.562l8.516-1.419 46.529 68.862-52.171 27.054z" />
                    <path d="m305.05 235.595-16.414 13.679-32.361-20.594c-2.019-1.284-4.636-1.116-6.475.415l-39 32.5c-2.333 1.945-2.648 5.413-.704 7.746 1.088 1.305 2.652 1.979 4.229 1.979 1.241 0 2.49-.418 3.519-1.275l35.914-29.929 32.361 20.594c2.02 1.284 4.636 1.116 6.475-.415l19.5-16.25c2.333-1.945 2.648-5.413.704-7.746-1.945-2.333-5.413-2.648-7.748-.704zm-78.532 137.466-51.366-25.64c-1.432-.715-3.104-.77-4.578-.153l-17.497 7.317-77.613-49.402c-2.561-1.631-5.962-.876-7.593 1.687-1.631 2.563-.876 5.962 1.687 7.593l80.037 50.945c1.519.967 3.415 1.129 5.075.434l17.824-7.454 49.112 24.515c.788.394 1.626.58 2.452.58 2.019 0 3.962-1.116 4.925-3.045 1.356-2.717.252-6.02-2.465-7.377zm120.013-220.145h64.642c3.037 0 5.5-2.462 5.5-5.5 0-12.009-9.77-21.778-21.778-21.778-3.161 0-6.286.701-9.137 2.02-4.88-4.48-11.218-6.976-17.992-6.976-14.741 0-26.734 11.993-26.734 26.734-.001 3.037 2.462 5.5 5.499 5.5zm21.235-21.235c4.981 0 9.566 2.304 12.578 6.32.894 1.19 2.23 1.968 3.707 2.156 1.479.188 2.965-.231 4.128-1.16 1.932-1.544 4.254-2.36 6.716-2.36 3.936 0 7.385 2.12 9.267 5.278h-51.138c2.234-5.971 7.999-10.234 14.742-10.234z" />
                  </svg>
                </a>
              </li>
            </ul>
          </nav>
        <?php endif; ?>

        <main class="luxe-main">
          <section>
            <div class="headings luxe-headings">
              <h1 style="background-image: url('<?php echo esc_url($title_bg_url); ?>');"><?php echo esc_html($main_title); ?></h1>
              <h2><?php echo esc_html($subtitle_top); ?></h2>
              <h2><?php echo esc_html($subtitle_btm); ?></h2>
            </div>

            <div class="banner luxe-banner">
              <div class="banner-wrapper luxe-banner-grid">
                <?php foreach ($feature_boxes as $box) : ?>
                  <div class="glass box luxe-feature-box">
                    <p><?php echo esc_html($box['label']); ?></p>
                    <?php if (!empty($box['icon_svg'])) : ?>
                       <div class="luxe-icon-wrap">
                         <?php echo $box['icon_svg']; // Already sanitized/trusted in dashboard or we could use wp_kses if needed ?>
                       </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>

              <div class="mountain-list glass luxe-mountain-list">
                <h3><?php echo esc_html($exp_title); ?></h3>
                <ul>
                  <?php foreach ($slides as $index => $slide) : ?>
                    <li>
                      <img src="<?php echo esc_url($slide['image']['url']); ?>" alt="<?php echo esc_attr($slide['title']); ?>" />
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          </section>
        </main>
        <img src="https://assets.codepen.io/1149983/mountain-lines.svg" class="mountain-lines luxe-contour-lines" alt="" />
      </div>
    </div>
    <?php
  }
}
