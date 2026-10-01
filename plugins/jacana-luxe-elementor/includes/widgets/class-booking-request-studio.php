<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Booking_Request_Studio extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_booking_request_studio';
  }

  public function get_title() {
    return __('Booking Request Studio', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-wizard';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Build Your Booking Request', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label' => __('Intro', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Select the main service you need, review what information helps us quote accurately, then submit your request. You can combine multiple services in one form.', 'jacana-luxe'),
    ));

    $this->add_control('selector_label', array(
      'label' => __('Service Selector Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Choose your main request type', 'jacana-luxe'),
    ));

    $this->add_control('prep_label', array(
      'label' => __('Preparation Panel Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Helpful details to include', 'jacana-luxe'),
    ));

    $this->add_control('prep_items', array(
      'label' => __('Preparation Items (one per line)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => "Travel dates or date range\nNumber of travelers (adults/children)\nRoute or destinations of interest\nArrival/departure airport\nPreferred travel style and budget level",
    ));

    $this->add_control('form_anchor_id', array(
      'label' => __('Form Anchor ID', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => 'booking-form',
      'description' => __('Used by hero/buttons to jump to this form section.', 'jacana-luxe'),
    ));

    $this->add_control('form_kicker', array(
      'label' => __('Form Kicker', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Request Form', 'jacana-luxe'),
    ));

    $this->add_control('form_heading', array(
      'label' => __('Form Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Send your booking request', 'jacana-luxe'),
    ));

    $this->add_control('form_intro', array(
      'label' => __('Form Intro', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Use the form below to send your request. Mention multiple services in the same message if you want one combined proposal.', 'jacana-luxe'),
    ));

    $this->add_control('service_field_selector', array(
      'label' => __('Prefill Field Selector (optional)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'placeholder' => 'input[name="service_interest"]',
      'description' => __('Optional CSS selector for a form field that should receive the selected service title.', 'jacana-luxe'),
    ));

    $this->add_control('form_shortcode', array(
      'label' => __('Form Shortcode', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'placeholder' => '[contact-form-7 id="123"]',
    ));

    $choices = array('booking_unified' => __('Unified Booking Request', 'jacana-luxe'));
    if (class_exists('Jacana_Luxe_Elementor') && method_exists('Jacana_Luxe_Elementor', 'get_cf7_booking_form_choices')) {
      $choices = Jacana_Luxe_Elementor::get_cf7_booking_form_choices();
    }

    $this->add_control('auto_cf7_form_key', array(
      'label' => __('Auto CF7 Form', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::SELECT,
      'default' => 'booking_unified',
      'options' => $choices,
      'description' => __('If Form Shortcode is empty, the widget will try to render this programmatically managed Contact Form 7 form.', 'jacana-luxe'),
    ));

    $this->add_control('response_note', array(
      'label' => __('Response Note', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Typical response: within 24 hours.', 'jacana-luxe'),
    ));

    $this->add_control('quote_note', array(
      'label' => __('Quote Note', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('All pricing is tailored and provided on request.', 'jacana-luxe'),
    ));

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('tag', array(
      'label' => __('Tag', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Popular', 'jacana-luxe'),
    ));
    $repeater->add_control('title', array(
      'label' => __('Service Title', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Tailor-made Tour', 'jacana-luxe'),
    ));
    $repeater->add_control('summary', array(
      'label' => __('Summary', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'rows' => 3,
    ));
    $repeater->add_control('ideal_for', array(
      'label' => __('Ideal For', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Travelers wanting a custom Namibia route', 'jacana-luxe'),
    ));
    $repeater->add_control('turnaround', array(
      'label' => __('Quote Turnaround', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Usually within 24 hours', 'jacana-luxe'),
    ));
    $repeater->add_control('checklist', array(
      'label' => __('Service-specific checklist (one per line)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'rows' => 5,
      'default' => "Dates or travel window\nNumber of travelers\nMust-see destinations\nAccommodation level\nGuided or self-drive preference",
    ));

    $this->add_control('services', array(
      'label' => __('Service Types', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::REPEATER,
      'fields' => $repeater->get_controls(),
      'title_field' => '{{{ title }}}',
      'default' => $this->get_default_services(),
    ));

    $this->end_controls_section();
  }

  private function get_default_services() {
    return array(
      array(
        'tag' => __('Most Requested', 'jacana-luxe'),
        'title' => __('Tailor-made Tour', 'jacana-luxe'),
        'summary' => __('Request a custom Namibia itinerary with route planning, accommodation coordination, activities and optional transport support.', 'jacana-luxe'),
        'ideal_for' => __('Travelers planning a private Namibia journey', 'jacana-luxe'),
        'turnaround' => __('Usually within 24 hours', 'jacana-luxe'),
        'checklist' => "Travel dates or date range\nNumber of travelers\nGuided or self-drive preference\nMust-see destinations\nAccommodation style (lodge/campsite/mix)",
      ),
      array(
        'tag' => __('Fleet', 'jacana-luxe'),
        'title' => __('Car Rental', 'jacana-luxe'),
        'summary' => __('Get a vehicle recommendation and quote based on route conditions, passenger count, luggage and camping requirements.', 'jacana-luxe'),
        'ideal_for' => __('Self-drive travelers and road-trip itineraries', 'jacana-luxe'),
        'turnaround' => __('Vehicle options confirmed on request', 'jacana-luxe'),
        'checklist' => "Pickup and drop-off location\nTravel dates\nPassengers + luggage\nRoute (tar/gravel/4x4)\nExtras needed (GPS/child seat/cooler)",
      ),
      array(
        'tag' => __('Transfer', 'jacana-luxe'),
        'title' => __('Airport Transfer / Shuttle', 'jacana-luxe'),
        'summary' => __('Arrange airport pickups, city transfers or point-to-point transport between destinations in Namibia.', 'jacana-luxe'),
        'ideal_for' => __('Airport arrivals and fixed route transfers', 'jacana-luxe'),
        'turnaround' => __('Fast confirmation once route/time is clear', 'jacana-luxe'),
        'checklist' => "Arrival airport and flight details\nPickup date and time\nPassengers + luggage\nDestination(s)\nReturn transfer needed?",
      ),
      array(
        'tag' => __('Etosha', 'jacana-luxe'),
        'title' => __('Game Drive', 'jacana-luxe'),
        'summary' => __('Request a morning or afternoon Etosha game drive with an experienced guide in an open-sided vehicle.', 'jacana-luxe'),
        'ideal_for' => __('Travelers adding safari activity to a Namibia trip', 'jacana-luxe'),
        'turnaround' => __('Subject to date and lodge/route availability', 'jacana-luxe'),
        'checklist' => "Preferred date(s)\nMorning or afternoon drive\nNumber of guests\nPickup location/lodge\nAny mobility or family considerations",
      ),
      array(
        'tag' => __('Combined', 'jacana-luxe'),
        'title' => __('Multi-Service Itinerary', 'jacana-luxe'),
        'summary' => __('Combine tours, accommodation, flights, transfers and car rental in one booking request so we can build a single coordinated proposal.', 'jacana-luxe'),
        'ideal_for' => __('Travelers wanting one coordinated point of contact', 'jacana-luxe'),
        'turnaround' => __('We confirm scope first, then build a tailored proposal', 'jacana-luxe'),
        'checklist' => "Travel dates and duration\nArrival and departure details\nMain route/destinations\nServices needed (list all)\nBudget range and travel style",
      ),
    );
  }

  private function parse_lines($value) {
    return array_values(array_filter(array_map('trim', explode("\n", (string) $value))));
  }

  private function render_prep_list($items) {
    if (empty($items)) {
      return;
    }

    echo '<ul class="jacana-booking-prep-list">';
    foreach ($items as $item) {
      echo '<li>' . esc_html($item) . '</li>';
    }
    echo '</ul>';
  }

  private function resolve_form_shortcode($settings) {
    $manual = !empty($settings['form_shortcode']) ? trim((string) $settings['form_shortcode']) : '';
    if ($manual !== '') {
      return $manual;
    }

    $key = !empty($settings['auto_cf7_form_key']) ? (string) $settings['auto_cf7_form_key'] : 'booking_unified';
    if (class_exists('Jacana_Luxe_Elementor') && method_exists('Jacana_Luxe_Elementor', 'get_cf7_booking_shortcode')) {
      return (string) Jacana_Luxe_Elementor::get_cf7_booking_shortcode($key);
    }

    return '';
  }

  private function render_form_content($shortcode, $auto_key = '') {
    if (!empty($shortcode)) {
      echo do_shortcode($shortcode);
      return;
    }

    echo '<div class="jacana-booking-form-placeholder">';
    echo '<p><strong>' . esc_html__('Add your booking form shortcode', 'jacana-luxe') . '</strong></p>';
    if (!empty($auto_key)) {
      echo '<p>' . esc_html__('The widget also tried to load the programmatic Contact Form 7 booking form, but no matching CF7 form was found. Make sure Contact Form 7 is installed and active, then reload the page.', 'jacana-luxe') . '</p>';
    } else {
      echo '<p>' . esc_html__('Use a Contact Form 7 or Elementor form shortcode here. Add a service field and map it with the optional Prefill Field Selector to automatically receive the selected request type.', 'jacana-luxe') . '</p>';
    }
    echo '</div>';
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $services = !empty($settings['services']) && is_array($settings['services']) ? array_values($settings['services']) : array();
    if (empty($services)) {
      return;
    }

    $prep_items = $this->parse_lines($settings['prep_items'] ?? '');
    $first = $services[0];
    $first_checklist = $this->parse_lines($first['checklist'] ?? '');
    $anchor = sanitize_html_class($settings['form_anchor_id'] ?? 'booking-form');
    $field_selector = !empty($settings['service_field_selector']) ? (string) $settings['service_field_selector'] : '';
    $auto_key = !empty($settings['auto_cf7_form_key']) ? (string) $settings['auto_cf7_form_key'] : 'booking_unified';
    $resolved_shortcode = $this->resolve_form_shortcode($settings);
    ?>
    <section class="section jacana-zoora-parity jacana-booking-studio" data-booking-studio<?php echo $field_selector ? ' data-booking-field-selector="' . esc_attr($field_selector) . '"' : ''; ?>>
      <div class="section-inner">
        <div class="section-header">
          <h2 class="jacana-reveal"><?php echo esc_html($settings['heading']); ?></h2>
          <p class="jacana-reveal" style="--jacana-delay: 120ms;"><?php echo esc_html($settings['intro']); ?></p>
        </div>

        <div class="jacana-booking-studio-shell">
          <aside class="jacana-booking-studio-left jacana-reveal" style="--jacana-delay: 140ms;">
            <div class="jacana-booking-panel-card">
              <div class="jacana-booking-step-chip"><?php echo esc_html__('Step 1', 'jacana-luxe'); ?></div>
              <div class="jacana-booking-panel-label"><?php echo esc_html($settings['selector_label']); ?></div>
              <p class="jacana-booking-panel-intro"><?php echo esc_html__('Start with the service that matters most. You can still combine other services in the form.', 'jacana-luxe'); ?></p>
              <div class="jacana-booking-service-buttons" role="tablist" aria-label="<?php echo esc_attr($settings['selector_label']); ?>">
                <?php foreach ($services as $index => $service) : ?>
                  <?php $checklist_items = $this->parse_lines($service['checklist'] ?? ''); ?>
                  <button
                    type="button"
                    class="jacana-booking-service-button<?php echo 0 === $index ? ' is-active' : ''; ?>"
                    data-jacana-service="<?php echo esc_attr($service['title'] ?? ''); ?>"
                    data-booking-option
                    data-booking-index="<?php echo esc_attr($index); ?>"
                    data-booking-tag="<?php echo esc_attr($service['tag'] ?? ''); ?>"
                    data-booking-title="<?php echo esc_attr($service['title'] ?? ''); ?>"
                    data-booking-summary="<?php echo esc_attr($service['summary'] ?? ''); ?>"
                    data-booking-ideal="<?php echo esc_attr($service['ideal_for'] ?? ''); ?>"
                    data-booking-turnaround="<?php echo esc_attr($service['turnaround'] ?? ''); ?>"
                    data-booking-checklist="<?php echo esc_attr(wp_json_encode($checklist_items)); ?>"
                    role="tab"
                    aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
                  >
                    <?php if (!empty($service['tag'])) : ?>
                      <span class="jacana-booking-service-tag"><?php echo esc_html($service['tag']); ?></span>
                    <?php endif; ?>
                    <strong><?php echo esc_html($service['title'] ?? ''); ?></strong>
                    <?php if (!empty($service['summary'])) : ?>
                      <small><?php echo esc_html($service['summary']); ?></small>
                    <?php endif; ?>
                  </button>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="jacana-booking-panel-card jacana-booking-prep-card">
              <div class="jacana-booking-step-chip jacana-booking-step-chip-muted"><?php echo esc_html__('Prepare', 'jacana-luxe'); ?></div>
              <div class="jacana-booking-panel-label"><?php echo esc_html($settings['prep_label']); ?></div>
              <?php $this->render_prep_list($prep_items); ?>
              <div class="jacana-booking-meta-notes">
                <?php if (!empty($settings['response_note'])) : ?><p><?php echo esc_html($settings['response_note']); ?></p><?php endif; ?>
                <?php if (!empty($settings['quote_note'])) : ?><p><?php echo esc_html($settings['quote_note']); ?></p><?php endif; ?>
              </div>
            </div>
          </aside>

          <div class="jacana-booking-studio-right jacana-reveal jacana-reveal-right" style="--jacana-delay: 180ms;">
            <div class="jacana-booking-selected-service card" data-booking-preview>
              <div class="jacana-booking-step-chip"><?php echo esc_html__('Step 2', 'jacana-luxe'); ?></div>
              <div class="jacana-booking-selected-top">
                <span class="jacana-booking-selected-tag" data-booking-preview-tag><?php echo esc_html($first['tag'] ?? ''); ?></span>
                <span class="jacana-booking-rate-pill"><?php echo esc_html__('Rates on request', 'jacana-luxe'); ?></span>
              </div>
              <h3 data-booking-preview-title><?php echo esc_html($first['title'] ?? ''); ?></h3>
              <p class="jacana-booking-selected-summary" data-booking-preview-summary><?php echo esc_html($first['summary'] ?? ''); ?></p>

              <div class="jacana-booking-selected-facts">
                <div class="jacana-booking-fact">
                  <span><?php echo esc_html__('Ideal For', 'jacana-luxe'); ?></span>
                  <strong data-booking-preview-ideal><?php echo esc_html($first['ideal_for'] ?? ''); ?></strong>
                </div>
                <div class="jacana-booking-fact">
                  <span><?php echo esc_html__('Quote Turnaround', 'jacana-luxe'); ?></span>
                  <strong data-booking-preview-turnaround><?php echo esc_html($first['turnaround'] ?? ''); ?></strong>
                </div>
              </div>

              <div class="jacana-booking-selected-checklist">
                <h4><?php echo esc_html__('Best information to include for this request', 'jacana-luxe'); ?></h4>
                <ul data-booking-preview-checklist>
                  <?php foreach ($first_checklist as $line) : ?>
                    <li><?php echo esc_html($line); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>

            <div class="jacana-booking-form-card card" id="<?php echo esc_attr($anchor); ?>">
              <div class="jacana-booking-form-head">
                <div class="jacana-booking-form-service-pill" data-booking-form-service-pill data-booking-form-service-label="<?php echo esc_attr__('Selected request:', 'jacana-luxe'); ?>">
                  <?php echo esc_html(sprintf(__('Selected request: %s', 'jacana-luxe'), $first['title'] ?? __('Booking request', 'jacana-luxe'))); ?>
                </div>
                <?php if (!empty($settings['form_kicker'])) : ?><span><?php echo esc_html($settings['form_kicker']); ?></span><?php endif; ?>
                <h3><?php echo esc_html($settings['form_heading']); ?></h3>
                <?php if (!empty($settings['form_intro'])) : ?><p><?php echo esc_html($settings['form_intro']); ?></p><?php endif; ?>
              </div>
              <div class="jacana-booking-form-shell" data-booking-form-shell>
                <?php $this->render_form_content($resolved_shortcode, $auto_key); ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <?php
  }
}
