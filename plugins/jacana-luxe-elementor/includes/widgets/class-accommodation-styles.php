<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Accommodation_Styles extends \Elementor\Widget_Base
{
  public function get_name()
  {
    return 'jacana_accommodation_styles';
  }

  public function get_title()
  {
    return __('Accommodation Styles', 'jacana-luxe');
  }

  public function get_icon()
  {
    return 'eicon-hotel';
  }

  public function get_categories()
  {
    return array('jacana-luxe');
  }

  protected function register_controls()
  {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('section_kicker', array(
      'label' => __('Section Kicker', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => '',
    ));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Find your accommodation style.', 'jacana-luxe'),
    ));

    $this->add_control('copy', array(
      'label' => __('Copy', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => '',
    ));

    $repeater = new \Elementor\Repeater();

    $repeater->add_control('style_title', array(
      'label' => __('Title', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Accommodation Style', 'jacana-luxe'),
    ));

    $repeater->add_control('style_desc', array(
      'label' => __('Short Description', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => '',
    ));

    $repeater->add_control('style_image', array(
      'label' => __('Photo', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
      'default' => array(
        'url' => \Elementor\Utils::get_placeholder_image_src(),
      ),
    ));

    $uploads_url = trailingslashit(wp_get_upload_dir()['baseurl']) . '2026/02/';

    $this->add_control('styles', array(
      'label' => __('Accommodation Styles', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::REPEATER,
      'fields' => $repeater->get_controls(),
      'default' => array(
          array(
          'style_title' => __('Luxury Tented Camps', 'jacana-luxe'),
          'style_desc' => '',
          'style_image' => array('url' => $uploads_url . 'Gondwana-Collection-Namibia.jpg'),
        ),
          array(
          'style_title' => __('Lodges/Hotels', 'jacana-luxe'),
          'style_desc' => '',
          'style_image' => array('url' => $uploads_url . 'Spitzkoppe-Rock-Arch.jpg'),
        ),
          array(
          'style_title' => __('Guest Houses', 'jacana-luxe'),
          'style_desc' => '',
          'style_image' => array('url' => $uploads_url . 'Stars-at-Spitzkoppe.jpg'),
        ),
          array(
          'style_title' => __('Backpackers', 'jacana-luxe'),
          'style_desc' => '',
          'style_image' => array('url' => $uploads_url . 'Etosha_waterhole.jpg'),
        ),
          array(
          'style_title' => __('Rooftop Tent (on top of a car)', 'jacana-luxe'),
          'style_desc' => '',
          'style_image' => array('url' => $uploads_url . 'Camping-on-a-4x4-Pickup.jpg'),
        ),
          array(
          'style_title' => __('Ground tent', 'jacana-luxe'),
          'style_desc' => '',
          'style_image' => array('url' => $uploads_url . 'Sandwich_Harbour.jpg'),
        ),
      ),
      'title_field' => '{{{ style_title }}}',
    ));

    $this->add_control('card_title', array(
      'label' => __('CTA Card Title', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Choose your favorite accommodation style and let us know', 'jacana-luxe'),
    ));

    $this->add_control('card_copy', array(
      'label' => __('CTA Card Copy', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => '',
    ));

    $this->add_control('card_button_label', array(
      'label' => __('Button Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Request a quote', 'jacana-luxe'),
    ));

    $this->add_control('card_button_link', array(
      'label' => __('Button Link', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'placeholder' => 'https://',
    ));

    $this->end_controls_section();
  }

  protected function render()
  {
    $settings = $this->get_settings_for_display();
    $section_kicker = isset($settings['section_kicker']) ? trim((string) $settings['section_kicker']) : '';
    if (strcasecmp($section_kicker, 'Sleep in style') === 0) {
      $section_kicker = '';
    }

    $heading = isset($settings['heading']) ? trim((string) $settings['heading']) : '';
    if ($heading === '' || $heading === __('Accommodation Styles', 'jacana-luxe') || $heading === __('Will be arranged according to your taste and wishes.', 'jacana-luxe')) {
      $heading = __('Find your accommodation style.', 'jacana-luxe');
    }

    $copy = isset($settings['copy']) ? trim((string) $settings['copy']) : '';
    // Strip any legacy copy values — heading is enough
    if (in_array($copy, array(
      __('You can choose from:', 'jacana-luxe'),
      __('We design your stay to match your taste and budget - from luxury tented camps under the stars to simple, authentic campsites.', 'jacana-luxe'),
    ), true)) {
      $copy = '';
    }

    $card_title = isset($settings['card_title']) ? trim((string) $settings['card_title']) : '';
    if (
      $card_title === '' ||
      $card_title === __('Tour Dates & Pricing', 'jacana-luxe') ||
      strpos($card_title, 'Will be arranged according to your taste') !== false ||
      strpos($card_title, 'Choose from Luxury Tented Camps') !== false
    ) {
      $card_title = __('Choose your favorite accommodation style and let us know', 'jacana-luxe');
    }

    $card_copy = isset($settings['card_copy']) ? trim((string) $settings['card_copy']) : '';
    // Strip legacy list copy
    if (
      strpos($card_copy, 'Will be arranged according to your taste') !== false ||
      strpos($card_copy, 'Choose from Luxury Tented Camps') !== false ||
      in_array($card_copy, array(
        __('You can choose from Luxury Tented Camps, Lodges/Hotels, Guest Houses, Backpackers, Rooftop Tent (on top of a car), and Ground tent.', 'jacana-luxe'),
        __('Tour dates are arranged according to you and prices are calculated based on tour type, vehicles, accommodation and travel duration.', 'jacana-luxe'),
      ), true)
    ) {
      $card_copy = '';
    }

    $button = !empty($settings['card_button_link']) && is_array($settings['card_button_link']) ? $settings['card_button_link'] : array();
    $styles = array();
    if (isset($settings['styles'])) {
      if (is_array($settings['styles'])) {
        $styles = $settings['styles'];
      } elseif (is_string($settings['styles'])) {
        $decoded_styles = json_decode($settings['styles'], true);
        if (is_array($decoded_styles)) {
          $styles = $decoded_styles;
        }
      }
    }

    $button_rel = array();
    if (!empty($button['nofollow'])) {
      $button_rel[] = 'nofollow';
    }
    if (!empty($button['is_external'])) {
      $button_rel[] = 'noopener';
    }
    $button_rel_attr = !empty($button_rel) ? ' rel="' . esc_attr(implode(' ', array_unique($button_rel))) . '"' : '';
    $button_url = !empty($button['url']) ? $button['url'] : '#';
    if (!empty($button_url) && false !== strpos(untrailingslashit($button_url), untrailingslashit(home_url('/booking')))) {
      $button_url = '#';
    }
?>
<section class="section jacana-zoora-parity jacana-tours-widget jacana-accommodation-styles">
  <div class="section-inner">

    <div class="jacana-accom-header">
      <?php if (!empty($section_kicker)): ?>
      <div class="jacana-tailor-story-kicker jacana-reveal">
        <?php echo esc_html($section_kicker); ?>
      </div>
      <?php endif; ?>
      <h2 class="jacana-accom-heading jacana-reveal" style="--jacana-delay: 80ms;">
        <?php echo esc_html($heading); ?>
      </h2>
      <?php if (!empty($copy)): ?>
      <p class="jacana-accom-intro jacana-reveal" style="--jacana-delay: 150ms;">
        <?php echo esc_html($copy); ?>
      </p>
      <?php
    endif; ?>
    </div>

    <div class="jacana-accom-grid" role="list">
      <?php if (!empty($styles)): ?>
      <?php foreach ($styles as $index => $style):
        if (!is_array($style)) {
          continue;
        }
        $style_image = isset($style['style_image']) ? $style['style_image'] : array();
        $img_id = 0;
        $img_url = '';
        if (is_array($style_image)) {
          $img_id = !empty($style_image['id']) ? (int) $style_image['id'] : 0;
          $img_url = !empty($style_image['url']) ? $style_image['url'] : '';
        } elseif (is_string($style_image)) {
          $img_url = $style_image;
        }
        $title = !empty($style['style_title']) ? trim((string) $style['style_title']) : __('Accommodation Style', 'jacana-luxe');
        if ($title === __('Lodges & Hotels', 'jacana-luxe')) {
          $title = __('Lodges/Hotels', 'jacana-luxe');
        } elseif ($title === __('Rooftop Tent', 'jacana-luxe')) {
          $title = __('Rooftop Tent (on top of a car)', 'jacana-luxe');
        } elseif ($title === __('Ground Tent', 'jacana-luxe')) {
          $title = __('Ground tent', 'jacana-luxe');
        }

        $desc = !empty($style['style_desc']) ? $style['style_desc'] : '';
        $legacy_desc = array(
          __('Canvas luxury under open skies', 'jacana-luxe'),
          __('Curated comfort at key stops', 'jacana-luxe'),
          __('Warm local hospitality', 'jacana-luxe'),
          __('Social and budget-friendly', 'jacana-luxe'),
          __('Sleep elevated above the bush', 'jacana-luxe'),
          __('Back-to-nature camping', 'jacana-luxe'),
        );
        if (in_array((string) $desc, $legacy_desc, true)) {
          $desc = '';
        }

        $delay = 180 + ($index * 80);
        $card_class = 'jacana-accom-card jacana-reveal';
        if ($img_id) {
          $img_html = wp_get_attachment_image($img_id, 'large', false, array(
            'class' => 'jacana-accom-card-img',
            'loading' => 'lazy',
          ));
        }
        elseif (!empty($img_url)) {
          $img_html = '<img src="' . esc_url($img_url) . '" class="jacana-accom-card-img" alt="' . esc_attr($title) . '" loading="lazy">';
        } else {
          $img_html = '<div class="jacana-accom-card-img jacana-accom-card-img-placeholder" aria-hidden="true"></div>';
        }
?>
      <article class="<?php echo esc_attr($card_class); ?>" style="--jacana-delay: <?php echo esc_attr($delay); ?>ms;" role="listitem" data-jacana-widget="jacana_accommodation_styles" data-jacana-service="accommodation" data-jacana-accommodation-style="<?php echo esc_attr($title); ?>">
        <div class="jacana-accom-card-media">
          <?php echo $img_html; ?>
          <div class="jacana-accom-card-overlay"></div>
        </div>
        <div class="jacana-accom-card-content">
          <div class="jacana-accom-card-meta">
            <span class="jacana-accom-card-index"><?php echo esc_html(sprintf('%02d', $index + 1)); ?></span>
            <span class="jacana-accom-card-label"><?php echo esc_html__('Accommodation Style', 'jacana-luxe'); ?></span>
          </div>
          <h3 class="jacana-accom-card-title"><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $title, array('area' => 'accom_title'))); ?></h3>
          <?php if (!empty($desc)): ?>
          <p class="jacana-accom-card-desc">
            <?php echo esc_html(apply_filters('jacana_i18n_translate_string', $desc, array('area' => 'accom_desc'))); ?>
          </p>
          <?php endif; ?>
          <button type="button" class="jacana-accom-card-link" data-jacana-ai-action="style_details" data-jacana-widget="jacana_accommodation_styles" data-jacana-service="accommodation" data-jacana-accommodation-style="<?php echo esc_attr($title); ?>"><?php echo esc_html__('Tell me more about this', 'jacana-luxe'); ?> <span aria-hidden="true">&rarr;</span></button>
        </div>
      </article>
      <?php
      endforeach; ?>
      <?php else: ?>
      <article class="jacana-accom-card jacana-reveal" style="--jacana-delay: 180ms;" role="listitem">
        <div class="jacana-accom-card-media">
          <div class="jacana-accom-card-img jacana-accom-card-img-placeholder" aria-hidden="true"></div>
          <div class="jacana-accom-card-overlay"></div>
        </div>
        <div class="jacana-accom-card-content">
          <div class="jacana-accom-card-meta">
            <span class="jacana-accom-card-index">01</span>
            <span class="jacana-accom-card-label"><?php echo esc_html__('Accommodation Style', 'jacana-luxe'); ?></span>
          </div>
          <h3 class="jacana-accom-card-title"><?php echo esc_html__('Add your accommodation styles', 'jacana-luxe'); ?></h3>
          <p class="jacana-accom-card-desc"><?php echo esc_html__('Use the repeater items to add each style with its image and short description.', 'jacana-luxe'); ?></p>
          <span class="jacana-accom-card-link"><?php echo esc_html__('Ready for curation', 'jacana-luxe'); ?> <span aria-hidden="true">&rarr;</span></span>
        </div>
      </article>
      <?php endif; ?>
    </div>
  </div>

  <div class="jacana-accom-cta-wrap jacana-reveal" style="--jacana-delay: 320ms;">
    <div class="jacana-accom-cta-card">
      <div class="section-inner">
        <div class="jacana-accom-cta-inner">
          <div class="jacana-accom-cta-content">
            <h3 class="jacana-accom-cta-title"><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $card_title, array('area' => 'accom_cta'))); ?></h3>
            <p class="jacana-accom-cta-copy"><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $card_copy, array('area' => 'accom_cta'))); ?></p>
          </div>
          <div class="jacana-accom-cta-actions">
            <a class="button button-primary jacana-tailor-cta-primary" data-jacana-booking-modal="true" data-jacana-widget="jacana_accommodation_styles" data-jacana-service="accommodation" href="<?php echo esc_url($button_url); ?>"<?php echo !empty($button['is_external']) ? ' target="_blank"' : ''; ?><?php echo $button_rel_attr; ?>>
              <?php echo esc_html(apply_filters('jacana_i18n_translate_string', $settings['card_button_label'] ?? '', array('area' => 'accom_cta'))); ?>
              <span class="jacana-cta-arrow" aria-hidden="true">&rarr;</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php
  }
}
