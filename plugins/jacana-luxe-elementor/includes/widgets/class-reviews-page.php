<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Reviews_Page extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_reviews_page';
  }

  public function get_title() {
    return __('Reviews Page - CRM Powered', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-testimonial';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Traveler Voices', 'jacana-luxe'),
    ));

    $this->add_control('subheading', array(
      'label' => __('Subheading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Authentic feedback from our community across Namibia and beyond.', 'jacana-luxe'),
    ));

    $this->add_control('service_filter', array(
      'label' => __('Default Service Filter', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => '',
      'description' => __('Optional service key to filter reviews by default.', 'jacana-luxe'),
    ));

    $this->add_control('reviews_per_page', array(
      'label' => __('Reviews Per Page', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::NUMBER,
      'min' => 1,
      'max' => 50,
      'default' => 100,
    ));

    $this->add_control('hero_image', array(
      'label' => __('Hero Image', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
      'default' => array(
        'url' => \Elementor\Utils::get_placeholder_image_src(),
      ),
    ));

    $this->end_controls_section();

    $this->start_controls_section('style_summary_section', array(
      'label' => __('Summary Section Style', 'jacana-luxe'),
      'tab' => \Elementor\Controls_Manager::TAB_STYLE,
    ));

    $this->add_control('summary_bg', array(
      'label' => __('Background Color', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::COLOR,
      'selectors' => array(
        '{{WRAPPER}} .jacana-reviews-summary' => 'background-color: {{VALUE}};',
      ),
    ));

    $this->end_controls_section();
  }

  private function render_stars($rating) {
    $count = max(1, min(5, (int) $rating));
    for ($i = 0; $i < $count; $i++) {
      echo '<span aria-hidden="true">★</span>';
    }
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $service_filter = sanitize_text_field((string) ($settings['service_filter'] ?? ''));
    $limit = max(1, min(50, (int) ($settings['reviews_per_page'] ?? 12)));
    $is_admin = current_user_can('manage_options');
    ?>
    <div class="jacana-rp-root" data-jacana-reviews-page="true" data-service="<?php echo esc_attr($service_filter); ?>" data-limit="<?php echo esc_attr($limit); ?>" data-is-admin="<?php echo $is_admin ? 'true' : 'false'; ?>">

      <!-- Hero / Summary -->
      <section class="jacana-rp-hero">
        <div class="jacana-rp-hero-inner">

          <div class="jacana-rp-hero-content jacana-reveal">
            <div class="jacana-rp-kicker"><?php echo esc_html__('Traveler Voices', 'jacana-luxe'); ?></div>
            <h1 class="jacana-rp-title"><?php echo esc_html($settings['heading']); ?></h1>
            <p class="jacana-rp-subheading"><?php echo esc_html($settings['subheading']); ?></p>
            <button type="button" class="jacana-rp-write-btn" data-jacana-toggle-review-form="true">
              <?php echo esc_html__('Leave a Review', 'jacana-luxe'); ?>
              <span aria-hidden="true">✦</span>
            </button>
          </div>

          <?php if (!empty($settings['hero_image']['url'])) : ?>
            <div class="jacana-rp-hero-image jacana-reveal" style="--jacana-delay: 120ms;">
              <img src="<?php echo esc_url($settings['hero_image']['url']); ?>" alt="">
            </div>
          <?php endif; ?>

        </div>
        <div class="jacana-rp-hero-stripe" aria-hidden="true"></div>
      </section>

      <!-- Write a Review Form -->
      <section class="jacana-rp-form-drawer" data-jacana-review-form-drawer="true" hidden>
        <div class="jacana-rp-form-inner">

          <div class="jacana-rp-form-header">
            <div class="jacana-rp-form-kicker"><?php echo esc_html__('Share Your Experience', 'jacana-luxe'); ?></div>
            <h2 class="jacana-rp-form-title"><?php echo esc_html__('How was your journey?', 'jacana-luxe'); ?></h2>
            <p><?php echo esc_html__('Your feedback helps us refine the Jacana experience for future travelers.', 'jacana-luxe'); ?></p>
          </div>

          <form class="jacana-rp-form" data-jacana-review-form="true" method="post" action="#">

            <!-- Rating — prominent at top -->
            <div class="jacana-rp-rating-row">
              <div class="jacana-rp-rating-label"><?php echo esc_html__('Your Rating', 'jacana-luxe'); ?></div>
              <div data-jacana-rating-picker="true">
                <div class="jacana-rp-stars-interactive">
                  <?php for ($i = 1; $i <= 5; $i++) : ?>
                    <button type="button" class="jacana-rp-star-btn" data-value="<?php echo $i; ?>" aria-label="<?php echo esc_attr(sprintf(__('Rate %d stars', 'jacana-luxe'), $i)); ?>">★</button>
                  <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" value="5">
              </div>
            </div>

            <!-- Review text -->
            <div class="jacana-rp-field">
              <label for="rp_review_text"><?php echo esc_html__('Your Review', 'jacana-luxe'); ?> <span class="jacana-rp-required" aria-hidden="true">*</span></label>
              <textarea id="rp_review_text" name="review_text" rows="4" required placeholder="<?php echo esc_attr__('What was the highlight of your trip?', 'jacana-luxe'); ?>"></textarea>
            </div>

            <!-- Name + Country row -->
            <div class="jacana-rp-fields-row">
              <div class="jacana-rp-field">
                <label for="rp_name"><?php echo esc_html__('Full Name', 'jacana-luxe'); ?> <span class="jacana-rp-required" aria-hidden="true">*</span></label>
                <input type="text" id="rp_name" name="name" required placeholder="<?php echo esc_attr__('e.g. Elena Smith', 'jacana-luxe'); ?>">
              </div>
              <div class="jacana-rp-field">
                <label for="rp_country"><?php echo esc_html__('Country', 'jacana-luxe'); ?> <span class="jacana-rp-optional">(<?php echo esc_html__('optional', 'jacana-luxe'); ?>)</span></label>
                <input type="text" id="rp_country" name="country" placeholder="<?php echo esc_attr__('e.g. Germany', 'jacana-luxe'); ?>">
              </div>
            </div>

            <!-- Consent -->
            <label class="jacana-rp-consent">
              <input type="checkbox" name="consent_public" value="1" required checked>
              <span><?php echo esc_html__('I consent to Jacana displaying this review publicly after approval.', 'jacana-luxe'); ?></span>
            </label>

            <input type="hidden" name="service_interest" value="<?php echo esc_attr($service_filter); ?>">
            <input type="hidden" name="source" value="reviews_page">

            <div class="jacana-rp-form-actions">
              <button type="submit" class="jacana-rp-submit-btn">
                <?php echo esc_html__('Submit Review', 'jacana-luxe'); ?>
                <span aria-hidden="true">→</span>
              </button>
              <button type="button" class="jacana-rp-cancel-btn" data-jacana-toggle-review-form="true">
                <?php echo esc_html__('Cancel', 'jacana-luxe'); ?>
              </button>
              <span class="jacana-rp-status" data-jacana-review-status aria-live="polite"></span>
            </div>

          </form>
        </div>
      </section>

      <!-- Reviews Feed -->
      <section class="jacana-rp-feed">
        <div class="jacana-rp-feed-inner">
          <div class="jacana-rp-feed-grid" data-jacana-reviews-feed="true">
            <div class="jacana-feed-loader"></div>
          </div>
        </div>
      </section>

    </div>
    <?php
  }
}
