<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Downloads_Library extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_downloads_library';
  }

  public function get_title() {
    return __('Downloads Library', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-download-kit';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('kicker', array(
      'label' => __('Kicker', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Travel Resources', 'jacana-luxe'),
    ));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Downloads', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label' => __('Intro', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Download essential trip documents before you travel. Start with your Namibia packing list PDF and add more files over time.', 'jacana-luxe'),
    ));

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('title', array(
      'label' => __('Title', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Namibia Packing List', 'jacana-luxe'),
    ));
    $repeater->add_control('description', array(
      'label' => __('Description', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Clothing, safari essentials, travel documents and practical items for Namibia conditions.', 'jacana-luxe'),
    ));
    $repeater->add_control('file_badge', array(
      'label' => __('File Badge', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('PDF', 'jacana-luxe'),
    ));
    $repeater->add_control('file_size', array(
      'label' => __('File Size Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Updated resource', 'jacana-luxe'),
    ));
    $repeater->add_control('file', array(
      'label' => __('File', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
      'media_types' => array('application/pdf'),
      'description' => __('Upload/select your PDF file.', 'jacana-luxe'),
    ));
    $repeater->add_control('fallback_url', array(
      'label' => __('Fallback URL', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'placeholder' => 'https://',
      'description' => __('Used when no media file is selected.', 'jacana-luxe'),
    ));
    $repeater->add_control('button_label', array(
      'label' => __('Button Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Download PDF', 'jacana-luxe'),
    ));

    $this->add_control('downloads', array(
      'label' => __('Download Items', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::REPEATER,
      'fields' => $repeater->get_controls(),
      'default' => array(
        array(
          'title' => __('Namibia Packing List', 'jacana-luxe'),
          'description' => __('Your practical checklist for clothing, safari gear and travel documents for Namibia.', 'jacana-luxe'),
          'file_badge' => __('PDF', 'jacana-luxe'),
          'file_size' => __('Starter file', 'jacana-luxe'),
          'button_label' => __('Download Packing List', 'jacana-luxe'),
        ),
      ),
      'title_field' => '{{{ title }}}',
    ));

    $this->add_control('support_copy', array(
      'label' => __('Support Copy', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Need a custom version? Ask Jacana Concierge and we will tailor your checklist.', 'jacana-luxe'),
    ));

    $this->add_control('support_cta', array(
      'label' => __('Support CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Yes, I am ready', 'jacana-luxe'),
    ));

    $this->end_controls_section();
  }

  protected function resolve_download_link($item) {
    $media = !empty($item['file']['url']) ? $item['file']['url'] : '';
    if (!empty($media)) {
      return $media;
    }
    return !empty($item['fallback_url']['url']) ? $item['fallback_url']['url'] : '';
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $downloads = !empty($settings['downloads']) && is_array($settings['downloads']) ? $settings['downloads'] : array();
    $featured = !empty($downloads) ? $downloads[0] : null;
    $download_count = count($downloads);
    ?>
    <section class="section jacana-zoora-parity jacana-tours-widget jacana-downloads-library-widget">
      <div class="section-inner">
        <header class="section-header jacana-downloads-header">
          <div class="jacana-downloads-head-copy">
            <span class="jacana-downloads-kicker jacana-reveal"><?php echo esc_html($settings['kicker']); ?></span>
            <h2 class="jacana-reveal" style="--jacana-delay: 90ms;"><?php echo esc_html($settings['heading']); ?></h2>
          </div>
          <div class="jacana-downloads-head-card jacana-reveal" style="--jacana-delay: 140ms;">
            <span class="jacana-downloads-head-stat"><?php echo esc_html(sprintf(_n('%d resource ready', '%d resources ready', $download_count, 'jacana-luxe'), $download_count)); ?></span>
            <p><?php echo esc_html($settings['intro']); ?></p>
          </div>
        </header>

        <div class="jacana-downloads-shell">
          <?php if ($featured) :
            $featured_link = $this->resolve_download_link($featured);
            ?>
            <aside class="jacana-downloads-spotlight card jacana-reveal" style="--jacana-delay: 100ms;">
              <span class="jacana-downloads-spotlight-label"><?php echo esc_html__('Featured resource', 'jacana-luxe'); ?></span>
              <h3><?php echo esc_html($featured['title'] ?? __('Travel Resource', 'jacana-luxe')); ?></h3>
              <p><?php echo esc_html($featured['description'] ?? ''); ?></p>
              <div class="jacana-downloads-spotlight-meta">
                <span><?php echo esc_html($featured['file_badge'] ?? __('PDF', 'jacana-luxe')); ?></span>
                <?php if (!empty($featured['file_size'])) : ?>
                  <span><?php echo esc_html($featured['file_size']); ?></span>
                <?php endif; ?>
              </div>
              <div class="jacana-downloads-spotlight-actions">
                <?php if (!empty($featured_link)) : ?>
                  <a
                    class="button button-primary jacana-download-button"
                    href="<?php echo esc_url($featured_link); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    data-jacana-download="<?php echo esc_attr($featured['title'] ?? __('Travel Resource', 'jacana-luxe')); ?>"
                  >
                    <?php echo esc_html(!empty($featured['button_label']) ? $featured['button_label'] : __('Download File', 'jacana-luxe')); ?>
                  </a>
                <?php endif; ?>
                <button type="button" class="button button-outline" data-jacana-open-chat="<?php echo esc_attr($featured['title'] ?? 'downloads'); ?>"><?php echo esc_html__('Need a tailored version?', 'jacana-luxe'); ?></button>
              </div>
            </aside>
          <?php endif; ?>

          <div class="jacana-downloads-grid">
          <?php foreach ($downloads as $index => $item) :
            if (0 === (int) $index && $featured) {
              continue;
            }
            $title = $item['title'] ?? '';
            $description = $item['description'] ?? '';
            $badge = $item['file_badge'] ?? __('PDF', 'jacana-luxe');
            $meta = $item['file_size'] ?? '';
            $label = !empty($item['button_label']) ? $item['button_label'] : __('Download File', 'jacana-luxe');
            $link = $this->resolve_download_link($item);
            $delay = 100 + (70 * ((int) $index + 1));
            ?>
            <article class="card jacana-download-card jacana-reveal" style="--jacana-delay: <?php echo esc_attr($delay); ?>ms;" data-jacana-download-item>
              <div class="jacana-download-card-top">
                <span class="jacana-download-badge"><?php echo esc_html($badge); ?></span>
                <?php if (!empty($meta)) : ?>
                  <span class="jacana-download-meta"><?php echo esc_html($meta); ?></span>
                <?php endif; ?>
              </div>

              <div class="jacana-download-card-icon" aria-hidden="true"><?php echo esc_html(sprintf('%02d', $index + 1)); ?></div>
              <h3><?php echo esc_html($title); ?></h3>
              <p><?php echo esc_html($description); ?></p>

              <div class="jacana-download-card-actions">
                <?php if (!empty($link)) : ?>
                  <a
                    class="button button-primary jacana-download-button"
                    href="<?php echo esc_url($link); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    data-jacana-download="<?php echo esc_attr($title); ?>"
                  >
                    <?php echo esc_html($label); ?>
                  </a>
                  <button type="button" class="button button-ghost jacana-download-help" data-jacana-open-chat="<?php echo esc_attr($title); ?>"><?php echo esc_html__('Ask Jana', 'jacana-luxe'); ?></button>
                <?php else : ?>
                  <span class="jacana-download-missing"><?php echo esc_html__('PDF not added yet - upload file in widget settings.', 'jacana-luxe'); ?></span>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
          </div>
        </div>

        <div class="jacana-downloads-support jacana-reveal" style="--jacana-delay: 220ms;">
          <p><?php echo esc_html($settings['support_copy']); ?></p>
          <button type="button" class="button button-outline" data-jacana-open-chat="downloads"><?php echo esc_html($settings['support_cta']); ?></button>
        </div>
      </div>
    </section>
    <?php
  }
}
