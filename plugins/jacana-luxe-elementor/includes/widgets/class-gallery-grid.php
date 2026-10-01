<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Gallery_Grid extends \Elementor\Widget_Base {
  public function get_name()       { return 'jacana_gallery_grid'; }
  public function get_title()      { return __('Gallery Grid', 'jacana-luxe'); }
  public function get_icon()       { return 'eicon-gallery-grid'; }
  public function get_categories() { return array('jacana-luxe'); }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('kicker', array(
      'label'   => __('Kicker', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Through the Lens', 'jacana-luxe'),
    ));

    $this->add_control('heading', array(
      'label'   => __('Heading', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Namibia Moments', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label'   => __('Intro', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('A curated gallery of landscapes, wildlife sightings, and route inspiration from across Namibia.', 'jacana-luxe'),
    ));

    $this->add_control('show_filters', array(
      'label'        => __('Show Category Filters', 'jacana-luxe'),
      'type'         => \Elementor\Controls_Manager::SWITCHER,
      'return_value' => 'yes',
      'default'      => 'yes',
    ));

    $repeater = new \Elementor\Repeater();

    $repeater->add_control('image', array(
      'label' => __('Image', 'jacana-luxe'),
      'type'  => \Elementor\Controls_Manager::MEDIA,
    ));

    $repeater->add_control('title', array(
      'label'   => __('Title', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Namibia Highlight', 'jacana-luxe'),
    ));

    $repeater->add_control('location', array(
      'label'   => __('Location', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Namibia', 'jacana-luxe'),
    ));

    $repeater->add_control('category', array(
      'label'   => __('Category', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Landscape', 'jacana-luxe'),
    ));

    $repeater->add_control('description', array(
      'label' => __('Description', 'jacana-luxe'),
      'type'  => \Elementor\Controls_Manager::TEXTAREA,
      'rows'  => 3,
    ));

    $repeater->add_control('video_url', array(
      'label'       => __('Video URL (optional)', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::URL,
      'placeholder' => 'https://',
    ));

    $repeater->add_control('size_emphasis', array(
      'label'   => __('Card Size', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::SELECT,
      'default' => 'normal',
      'options' => array(
        'normal'   => __('Normal', 'jacana-luxe'),
        'wide'     => __('Wide', 'jacana-luxe'),
        'tall'     => __('Tall', 'jacana-luxe'),
        'featured' => __('Featured', 'jacana-luxe'),
      ),
    ));

    $this->add_control('images', array(
      'type'        => \Elementor\Controls_Manager::REPEATER,
      'fields'      => $repeater->get_controls(),
      'default'     => array(
        array('title' => __('Etosha Waterhole',   'jacana-luxe'), 'location' => __('Etosha National Park', 'jacana-luxe'), 'category' => __('Wildlife',   'jacana-luxe'), 'size_emphasis' => 'featured'),
        array('title' => __('Dune Light',          'jacana-luxe'), 'location' => __('Sossusvlei',          'jacana-luxe'), 'category' => __('Landscape',  'jacana-luxe'), 'size_emphasis' => 'tall'),
        array('title' => __('Atlantic Mood',       'jacana-luxe'), 'location' => __('Swakopmund',          'jacana-luxe'), 'category' => __('Coast',      'jacana-luxe')),
      ),
      'title_field' => '{{{ title }}}',
    ));

    $this->add_control('empty_copy', array(
      'label'   => __('Empty State Copy', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Add gallery items to display destination visuals.', 'jacana-luxe'),
    ));

    $this->end_controls_section();
  }

  private function filter_slug($value) {
    $slug = sanitize_title((string) $value);
    return $slug !== '' ? $slug : 'uncategorized';
  }

  private function build_aria_label($item) {
    $parts = array();
    if (!empty($item['title']))    { $parts[] = $item['title']; }
    if (!empty($item['location'])) { $parts[] = $item['location']; }
    return implode(', ', $parts) ?: __('Gallery item', 'jacana-luxe');
  }

  protected function render() {
    $settings   = $this->get_settings_for_display();
    $items      = !empty($settings['images']) && is_array($settings['images']) ? $settings['images'] : array();
    $categories = array();

    foreach ($items as $item) {
      if (!is_array($item)) { continue; }
      $label = !empty($item['category']) ? trim((string) $item['category']) : '';
      if ($label === '') { continue; }
      $slug = $this->filter_slug($label);
      if (!isset($categories[$slug])) {
        $categories[$slug] = $label;
      }
    }
    ?>
    <section class="jacana-gg-section jacana-gallery-grid-widget">
      <div class="jacana-gg-inner">

        <!-- ── Header ── -->
        <header class="jacana-dex-header">
          <h2 class="jacana-gg-heading"><?php echo esc_html($settings['heading']); ?></h2>
          <?php if (!empty($settings['intro'])) : ?>
              <p class="jacana-gg-intro"><?php echo esc_html($settings['intro']); ?></p>
            <?php endif; ?>
        </header>

        <!-- ── Filter bar ── -->
        <?php if ('yes' === ($settings['show_filters'] ?? '') && !empty($categories)) : ?>
          <div class="jacana-gallery-filters jacana-reveal" style="--jacana-delay: 130ms;" data-gallery-filters>
            <button type="button" class="jacana-gallery-filter is-active" data-gallery-filter="all" aria-pressed="true">
              <?php echo esc_html__('All', 'jacana-luxe'); ?>
            </button>
            <?php foreach ($categories as $slug => $label) : ?>
              <button type="button"
                      class="jacana-gallery-filter"
                      data-gallery-filter="<?php echo esc_attr($slug); ?>"
                      aria-pressed="false">
                <?php echo esc_html($label); ?>
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- ── Grid ── -->
        <?php if (!empty($items)) : ?>
          <div class="jacana-gallery-curated-grid" data-gallery-grid>
            <?php foreach ($items as $index => $item) :
              $url            = !empty($item['image']['url'])        ? $item['image']['url']        : '';
              if (empty($url)) { continue; }
              $category_label = !empty($item['category'])            ? trim((string) $item['category']) : __('Gallery', 'jacana-luxe');
              $category_slug  = $this->filter_slug($category_label);
              $size           = !empty($item['size_emphasis'])       ? $item['size_emphasis']        : 'normal';
              $video_url      = !empty($item['video_url']['url'])    ? $item['video_url']['url']     : '';
              $desc           = !empty($item['description'])         ? $item['description']          : '';
              $aria_label     = $this->build_aria_label($item);
              $delay          = 50 * ((int) $index + 1);
            ?>
              <article
                class="jacana-gallery-card jacana-gallery-card--<?php echo esc_attr($size); ?> jacana-reveal"
                style="--jacana-delay: <?php echo esc_attr($delay); ?>ms;"
                data-gallery-item
                data-gallery-category="<?php echo esc_attr($category_slug); ?>">

                <button
                  class="jacana-gallery-card-btn jacana-gallery-card-button tour-gallery-button"
                  type="button"
                  aria-label="<?php echo esc_attr($aria_label); ?>"
                  data-lightbox-src="<?php echo esc_url($url); ?>"
                  data-lightbox-video="<?php echo esc_attr($video_url); ?>"
                  data-lightbox-title="<?php echo esc_attr($aria_label); ?>"
                  data-lightbox-desc="<?php echo esc_attr($desc); ?>">

                  <!-- Image -->
                  <span class="jacana-gallery-media" aria-hidden="true">
                    <img src="<?php echo esc_url($url); ?>"
                         alt=""
                         loading="lazy"
                         decoding="async">
                  </span>

                  <!-- Overlay -->
                  <span class="jacana-gallery-card-overlay" aria-hidden="true"></span>

                  <!-- Oshiwambo stripe -->
                  <span class="jacana-gallery-card-stripe" aria-hidden="true"></span>

                  <!-- Meta -->
                  <span class="jacana-gallery-card-meta">
                    <span class="jacana-gallery-card-tags" aria-hidden="true">
                      <span class="jacana-gallery-card-pill"><?php echo esc_html($category_label); ?></span>
                      <?php if (!empty($video_url)) : ?>
                        <span class="jacana-gallery-card-pill jacana-gallery-card-pill--video">
                          <?php echo esc_html__('Video', 'jacana-luxe'); ?>
                        </span>
                      <?php endif; ?>
                    </span>
                    <?php if (!empty($item['title'])) : ?>
                      <span class="jacana-gallery-card-title"><?php echo esc_html($item['title']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($item['location'])) : ?>
                      <span class="jacana-gallery-card-location"><?php echo esc_html($item['location']); ?></span>
                    <?php endif; ?>
                  </span>

                  <!-- Expand hint -->
                  <span class="jacana-gallery-card-expand" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                  </span>

                </button>
              </article>
            <?php endforeach; ?>
          </div>

        <?php else : ?>
          <div class="jacana-gg-empty">
            <p><?php echo esc_html($settings['empty_copy']); ?></p>
          </div>
        <?php endif; ?>

      </div>
    </section>
    <?php
  }
}
