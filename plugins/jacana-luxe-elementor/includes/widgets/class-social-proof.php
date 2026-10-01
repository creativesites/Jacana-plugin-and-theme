<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Social_Proof extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_social_proof';
  }

  public function get_title() {
    return __('Social Proof and Reviews', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-star-o';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Traveler reviews and social updates', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label' => __('Intro', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Share traveler feedback and embed your latest Instagram feed in one section.', 'jacana-luxe'),
    ));

    $this->add_control('use_crm_reviews', array(
      'label' => __('Use CRM Reviews', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::SWITCHER,
      'label_on' => __('Yes', 'jacana-luxe'),
      'label_off' => __('No', 'jacana-luxe'),
      'return_value' => 'yes',
      'default' => 'yes',
    ));

    $this->add_control('service_filter', array(
      'label' => __('Service Filter', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => '',
      'description' => __('Optional service key, for example car_rental or accommodation.', 'jacana-luxe'),
    ));

    $this->add_control('review_limit', array(
      'label' => __('Review Limit', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::NUMBER,
      'min' => 1,
      'max' => 12,
      'default' => 4,
    ));

    $this->add_control('instagram_embed', array(
      'label' => __('Instagram Embed/Shortcode', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'description' => __('Paste a plugin shortcode or embed block. Example: [instagram-feed feed=1]', 'jacana-luxe'),
    ));

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('name', array(
      'label' => __('Traveler Name', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
    ));
    $repeater->add_control('trip', array(
      'label' => __('Trip Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
    ));
    $repeater->add_control('rating', array(
      'label' => __('Rating (1-5)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::NUMBER,
      'min' => 1,
      'max' => 5,
      'default' => 5,
    ));
    $repeater->add_control('review', array(
      'label' => __('Review', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
    ));

    $this->add_control('reviews', array(
      'label' => __('Fallback Manual Reviews', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::REPEATER,
      'fields' => $repeater->get_controls(),
      'default' => array(
        array(
          'name' => __('A. Muller', 'jacana-luxe'),
          'trip' => __('Self-drive desert and coast route', 'jacana-luxe'),
          'rating' => 5,
          'review' => __('Everything was organized down to the smallest detail. We felt supported from arrival to departure.', 'jacana-luxe'),
        ),
        array(
          'name' => __('M. Njoroge', 'jacana-luxe'),
          'trip' => __('Private family safari', 'jacana-luxe'),
          'rating' => 5,
          'review' => __('The itinerary was perfectly adapted for our kids and still gave us top wildlife sightings.', 'jacana-luxe'),
        ),
      ),
      'title_field' => '{{{ name }}}',
    ));

    $this->add_control('feedback_label', array(
      'label' => __('Feedback CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Leave your feedback', 'jacana-luxe'),
    ));

    $this->add_control('feedback_link', array(
      'label' => __('Feedback CTA Link', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'placeholder' => '#',
    ));

    $this->end_controls_section();
  }

  private function render_stars($rating) {
    $count = max(1, min(5, (int) $rating));
    for ($i = 0; $i < $count; $i++) {
      echo '<span aria-hidden="true">★</span>';
    }
  }

  private function get_crm_reviews($service_filter, $limit) {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_reviews';
    $limit = max(1, min(12, (int) $limit));
    $statuses = array('featured', 'approved');
    $where = "status IN ('featured','approved')";
    $params = array();

    if (!empty($service_filter)) {
      $where .= ' AND service_interest = %s';
      $params[] = sanitize_text_field((string) $service_filter);
    }

    $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY FIELD(status, 'featured', 'approved'), approved_at DESC, created_at DESC LIMIT %d";
    $params[] = $limit;
    return $wpdb->get_results($wpdb->prepare($sql, $params));
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $feedback = !empty($settings['feedback_link']) ? $settings['feedback_link'] : array();
    $embed = trim((string) ($settings['instagram_embed'] ?? ''));
    $use_crm_reviews = ($settings['use_crm_reviews'] ?? 'yes') === 'yes';
    $service_filter = sanitize_text_field((string) ($settings['service_filter'] ?? ''));
    $review_limit = max(1, min(12, (int) ($settings['review_limit'] ?? 4)));
    $reviews = array();

    if ($use_crm_reviews) {
      $reviews = $this->get_crm_reviews($service_filter, $review_limit);
    }

    if (!$reviews) {
      $reviews = $settings['reviews'];
    }
    ?>
    <section class="section jacana-zoora-parity jacana-tours-widget jacana-social-proof">
      <div class="section-inner">
        <div class="section-header">
          <h2 class="jacana-reveal"><?php echo esc_html($settings['heading']); ?></h2>
          <p class="jacana-reveal" style="--jacana-delay: 120ms;"><?php echo esc_html($settings['intro']); ?></p>
        </div>
        <div class="jacana-social-proof-grid">
          <div class="card jacana-social-feed jacana-reveal">
            <h3><?php echo esc_html__('Instagram updates', 'jacana-luxe'); ?></h3>
            <?php if (!empty($embed)) : ?>
              <div class="jacana-social-embed">
                <?php echo do_shortcode(wp_kses_post($embed)); ?>
              </div>
            <?php else : ?>
              <p><?php echo esc_html__('Add an Instagram feed shortcode or embed block in the widget settings.', 'jacana-luxe'); ?></p>
            <?php endif; ?>
          </div>
          <div class="jacana-social-reviews" data-jacana-ai-surface="reviews" data-jacana-widget="jacana_social_proof">
            <?php foreach ($reviews as $index => $review) :
              $name = is_object($review) ? ($review->name ?? '') : ($review['name'] ?? '');
              $trip = is_object($review) ? ($review->trip_label ?? '') : ($review['trip'] ?? '');
              $rating = is_object($review) ? ($review->rating ?? 5) : ($review['rating'] ?? 5);
              $copy = is_object($review) ? ($review->review_text ?? '') : ($review['review'] ?? '');
              $service = is_object($review) ? ($review->service_interest ?? '') : '';
              ?>
              <article class="card jacana-review-card jacana-reveal" style="--jacana-delay: <?php echo esc_attr(100 + (60 * ((int) $index + 1))); ?>ms;"<?php echo $service ? ' data-jacana-service="' . esc_attr($service) . '"' : ''; ?>>
                <div class="jacana-review-rating" aria-label="<?php echo esc_attr__('Traveler rating', 'jacana-luxe'); ?>">
                  <?php $this->render_stars($rating); ?>
                </div>
                <p class="jacana-review-copy"><?php echo esc_html($copy); ?></p>
                <p class="jacana-review-meta">
                  <strong><?php echo esc_html($name); ?></strong>
                  <?php if (!empty($trip)) : ?>
                    <span><?php echo esc_html($trip); ?></span>
                  <?php endif; ?>
                </p>
              </article>
            <?php endforeach; ?>

            <div class="jacana-social-review-actions">
              <?php if (!empty($feedback['url'])) : ?>
                <a class="button button-primary jacana-review-feedback" href="<?php echo esc_url($feedback['url']); ?>"<?php echo !empty($feedback['is_external']) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
                  <?php echo esc_html($settings['feedback_label']); ?>
                </a>
              <?php endif; ?>
              <button type="button" class="button button-outline" data-jacana-open-review-form="true"><?php echo esc_html__('Submit review', 'jacana-luxe'); ?></button>
            </div>

            <form class="jacana-review-form" data-jacana-review-form method="post" action="#" hidden>
              <label><span><?php echo esc_html__('Name', 'jacana-luxe'); ?></span><input type="text" name="name" required></label>
              <label><span><?php echo esc_html__('Country', 'jacana-luxe'); ?></span><input type="text" name="country"></label>
              <label><span><?php echo esc_html__('Trip / Service', 'jacana-luxe'); ?></span><input type="text" name="trip_label" placeholder="<?php echo esc_attr__('Etosha safari, airport transfer, tailor-made tour...', 'jacana-luxe'); ?>"></label>
              <input type="hidden" name="service_interest" value="<?php echo esc_attr($service_filter); ?>">
              <label><span><?php echo esc_html__('Rating (1-5)', 'jacana-luxe'); ?></span><input type="number" min="1" max="5" name="rating" value="5"></label>
              <label class="jacana-review-form-wide"><span><?php echo esc_html__('Review', 'jacana-luxe'); ?></span><textarea name="review_text" rows="4" required></textarea></label>
              <label class="jacana-review-form-consent jacana-review-form-wide"><input type="checkbox" name="consent_public" value="1"> <span><?php echo esc_html__('I consent to Jacana displaying this review publicly after approval.', 'jacana-luxe'); ?></span></label>
              <div class="jacana-review-form-actions jacana-review-form-wide">
                <button type="submit" class="button button-primary"><?php echo esc_html__('Submit review for approval', 'jacana-luxe'); ?></button>
                <span class="jacana-review-form-status" data-jacana-review-status></span>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
    <?php
  }
}
