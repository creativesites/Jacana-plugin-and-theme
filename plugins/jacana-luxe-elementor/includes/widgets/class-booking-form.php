<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jacana_Luxe_Booking_Form extends \Elementor\Widget_Base {

	public function get_name() {
		return 'jacana_booking_form';
	}

	public function get_title() {
		return __( 'Booking Form', 'jacana-luxe' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return array( 'jacana-luxe' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'content_section', array( 'label' => __( 'Content', 'jacana-luxe' ) ) );

		$this->add_control( 'heading', array(
			'label'   => __( 'Heading', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Request a Quote', 'jacana-luxe' ),
		) );

		$this->add_control( 'subheading', array(
			'label'   => __( 'Subheading', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'default' => __( 'Tell us what you need and we\'ll put together a personalised proposal within 24 hours.', 'jacana-luxe' ),
		) );

		$this->add_control( 'response_time', array(
			'label'   => __( 'Response Time Note', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'We typically respond within 24 hours.', 'jacana-luxe' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$settings      = $this->get_settings_for_display();
		$heading       = esc_html( $settings['heading'] ?? __( 'Request a Quote', 'jacana-luxe' ) );
		$subheading    = esc_html( $settings['subheading'] ?? '' );
		$response_time = esc_html( $settings['response_time'] ?? '' );
		$nonce         = wp_create_nonce( 'jacana_booking' );
		$endpoint      = esc_url( rest_url( 'jacana/v1/booking-request' ) );
		?>
		<section class="jbf-section">
			<div class="jbf-wrap">

				<!-- Left panel — branding/info -->
				<div class="jbf-side">
					<div class="jbf-side-inner">
						<div class="jbf-brand-kicker"><?php esc_html_e( 'Jacana Safaris &amp; Tours', 'jacana-luxe' ); ?></div>
						<h2 class="jbf-side-heading"><?php echo $heading; ?></h2>
						<p class="jbf-side-copy"><?php echo $subheading; ?></p>

						<ul class="jbf-trust-list">
							<li>
								<span class="jbf-trust-icon" aria-hidden="true">✓</span>
								<?php esc_html_e( 'Every journey is tailor-made', 'jacana-luxe' ); ?>
							</li>
							<li>
								<span class="jbf-trust-icon" aria-hidden="true">✓</span>
								<?php esc_html_e( 'Rates provided on request', 'jacana-luxe' ); ?>
							</li>
							<li>
								<span class="jbf-trust-icon" aria-hidden="true">✓</span>
								<?php echo $response_time; ?>
							</li>
							<li>
								<span class="jbf-trust-icon" aria-hidden="true">✓</span>
								<?php esc_html_e( 'No obligation — just a conversation', 'jacana-luxe' ); ?>
							</li>
						</ul>

						<div class="jbf-side-contact">
							<p><?php esc_html_e( 'Prefer to reach out directly?', 'jacana-luxe' ); ?></p>
							<a href="https://wa.me/264814040878" class="jbf-whatsapp-link" target="_blank" rel="noopener noreferrer">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
								<?php esc_html_e( 'Chat on WhatsApp', 'jacana-luxe' ); ?>
							</a>
						</div>
					</div>
				</div>

				<!-- Right panel — multi-step form -->
				<div class="jbf-form-panel">
					<form class="jbf-form" id="jbf-form" novalidate
						data-endpoint="<?php echo $endpoint; ?>"
						data-nonce="<?php echo $nonce; ?>">

						<!-- Progress bar -->
						<div class="jbf-progress" aria-hidden="true">
							<div class="jbf-progress-steps">
								<div class="jbf-progress-step is-active" data-step-dot="1">
									<span class="jbf-step-num">1</span>
									<span class="jbf-step-label"><?php esc_html_e( 'Services', 'jacana-luxe' ); ?></span>
								</div>
								<div class="jbf-progress-line"></div>
								<div class="jbf-progress-step" data-step-dot="2">
									<span class="jbf-step-num">2</span>
									<span class="jbf-step-label"><?php esc_html_e( 'Trip details', 'jacana-luxe' ); ?></span>
								</div>
								<div class="jbf-progress-line"></div>
								<div class="jbf-progress-step" data-step-dot="3">
									<span class="jbf-step-num">3</span>
									<span class="jbf-step-label"><?php esc_html_e( 'Your details', 'jacana-luxe' ); ?></span>
								</div>
							</div>
						</div>

						<!-- Step 1 — Service selection -->
						<fieldset class="jbf-step" data-step="1">
							<legend class="jbf-step-heading"><?php esc_html_e( 'What do you need?', 'jacana-luxe' ); ?></legend>
							<p class="jbf-step-hint"><?php esc_html_e( 'Select one or more services. You can combine them in a single request.', 'jacana-luxe' ); ?></p>

							<div class="jbf-service-grid" role="group" aria-label="<?php esc_attr_e( 'Services', 'jacana-luxe' ); ?>">
								<?php
								$services = array(
									array( 'value' => 'tailor_made',  'label' => __( 'Tailor-made Tour', 'jacana-luxe' ),        'icon' => '🗺️' ),
									array( 'value' => 'car_rental',   'label' => __( 'Car Rental', 'jacana-luxe' ),               'icon' => '🚙' ),
									array( 'value' => 'transfer',     'label' => __( 'Airport Transfer', 'jacana-luxe' ),         'icon' => '✈️' ),
									array( 'value' => 'game_drive',   'label' => __( 'Game Drive', 'jacana-luxe' ),               'icon' => '🦁' ),
									array( 'value' => 'accommodation','label' => __( 'Accommodation', 'jacana-luxe' ),            'icon' => '🏕️' ),
									array( 'value' => 'flights',      'label' => __( 'Flight Booking', 'jacana-luxe' ),           'icon' => '🛫' ),
									array( 'value' => 'city_tour',    'label' => __( 'City Tour', 'jacana-luxe' ),                'icon' => '🌆' ),
									array( 'value' => 'other',        'label' => __( 'Something else', 'jacana-luxe' ),           'icon' => '💬' ),
								);
								foreach ( $services as $svc ) : ?>
									<label class="jbf-service-option">
										<input type="checkbox" name="services[]" value="<?php echo esc_attr( $svc['value'] ); ?>" class="jbf-sr-only">
										<span class="jbf-service-card">
											<span class="jbf-service-icon" aria-hidden="true"><?php echo $svc['icon']; ?></span>
											<span class="jbf-service-label"><?php echo esc_html( $svc['label'] ); ?></span>
											<span class="jbf-service-check" aria-hidden="true"></span>
										</span>
									</label>
								<?php endforeach; ?>
							</div>
							<p class="jbf-field-error" data-error="services" hidden><?php esc_html_e( 'Please select at least one service.', 'jacana-luxe' ); ?></p>
						</fieldset>

						<!-- Step 2 — Trip details -->
						<fieldset class="jbf-step" data-step="2" hidden>
							<legend class="jbf-step-heading"><?php esc_html_e( 'Trip details', 'jacana-luxe' ); ?></legend>
							<p class="jbf-step-hint"><?php esc_html_e( 'Approximate details are fine — we\'ll refine everything together.', 'jacana-luxe' ); ?></p>

							<div class="jbf-fields">
								<div class="jbf-field jbf-field-half">
									<label for="jbf-travel-dates"><?php esc_html_e( 'Travel dates', 'jacana-luxe' ); ?> <span class="jbf-optional"><?php esc_html_e( '(approximate)', 'jacana-luxe' ); ?></span></label>
									<input type="text" id="jbf-travel-dates" name="travel_dates" placeholder="<?php esc_attr_e( 'e.g. July 2025 or 10–24 Aug', 'jacana-luxe' ); ?>">
								</div>

								<div class="jbf-field jbf-field-half">
									<label for="jbf-party-size"><?php esc_html_e( 'Number of travellers', 'jacana-luxe' ); ?></label>
									<select id="jbf-party-size" name="party_size">
										<option value=""><?php esc_html_e( '— Select —', 'jacana-luxe' ); ?></option>
										<option value="1"><?php esc_html_e( '1 — Solo', 'jacana-luxe' ); ?></option>
										<option value="2"><?php esc_html_e( '2 — Couple', 'jacana-luxe' ); ?></option>
										<option value="3-4"><?php esc_html_e( '3–4', 'jacana-luxe' ); ?></option>
										<option value="5-8"><?php esc_html_e( '5–8', 'jacana-luxe' ); ?></option>
										<option value="9+"><?php esc_html_e( '9 or more', 'jacana-luxe' ); ?></option>
									</select>
								</div>

								<div class="jbf-field">
									<label for="jbf-destinations"><?php esc_html_e( 'Destinations / route of interest', 'jacana-luxe' ); ?> <span class="jbf-optional"><?php esc_html_e( '(optional)', 'jacana-luxe' ); ?></span></label>
									<input type="text" id="jbf-destinations" name="destination_interest" placeholder="<?php esc_attr_e( 'e.g. Etosha, Sossusvlei, Swakopmund', 'jacana-luxe' ); ?>">
								</div>

								<div class="jbf-field jbf-field-half">
									<label for="jbf-budget"><?php esc_html_e( 'Budget style', 'jacana-luxe' ); ?> <span class="jbf-optional"><?php esc_html_e( '(optional)', 'jacana-luxe' ); ?></span></label>
									<select id="jbf-budget" name="budget_tier">
										<option value=""><?php esc_html_e( '— Select —', 'jacana-luxe' ); ?></option>
										<option value="budget"><?php esc_html_e( 'Budget-friendly', 'jacana-luxe' ); ?></option>
										<option value="mid_range"><?php esc_html_e( 'Mid-range', 'jacana-luxe' ); ?></option>
										<option value="luxury"><?php esc_html_e( 'Luxury', 'jacana-luxe' ); ?></option>
										<option value="mixed"><?php esc_html_e( 'Mix of styles', 'jacana-luxe' ); ?></option>
									</select>
								</div>

								<div class="jbf-field jbf-field-half">
									<label for="jbf-accommodation"><?php esc_html_e( 'Accommodation preference', 'jacana-luxe' ); ?> <span class="jbf-optional"><?php esc_html_e( '(optional)', 'jacana-luxe' ); ?></span></label>
									<select id="jbf-accommodation" name="accommodation_style">
										<option value=""><?php esc_html_e( '— Select —', 'jacana-luxe' ); ?></option>
										<option value="Luxury Tented Camps"><?php esc_html_e( 'Luxury Tented Camps', 'jacana-luxe' ); ?></option>
										<option value="Lodges / Hotels"><?php esc_html_e( 'Lodges / Hotels', 'jacana-luxe' ); ?></option>
										<option value="Guest Houses"><?php esc_html_e( 'Guest Houses', 'jacana-luxe' ); ?></option>
										<option value="Backpackers"><?php esc_html_e( 'Backpackers', 'jacana-luxe' ); ?></option>
										<option value="Rooftop Tent"><?php esc_html_e( 'Rooftop Tent (on car)', 'jacana-luxe' ); ?></option>
										<option value="Ground Tent"><?php esc_html_e( 'Ground Tent', 'jacana-luxe' ); ?></option>
										<option value="Mixed"><?php esc_html_e( 'Mix of options', 'jacana-luxe' ); ?></option>
									</select>
								</div>
							</div>
						</fieldset>

						<!-- Step 3 — Contact details -->
						<fieldset class="jbf-step" data-step="3" hidden>
							<legend class="jbf-step-heading"><?php esc_html_e( 'Your details', 'jacana-luxe' ); ?></legend>
							<p class="jbf-step-hint"><?php esc_html_e( 'We\'ll use these to send you a personalised quote.', 'jacana-luxe' ); ?></p>

							<div class="jbf-fields">
								<div class="jbf-field">
									<label for="jbf-name"><?php esc_html_e( 'Full name', 'jacana-luxe' ); ?> <span class="jbf-required" aria-hidden="true">*</span></label>
									<input type="text" id="jbf-name" name="name" autocomplete="name" required placeholder="<?php esc_attr_e( 'Your name', 'jacana-luxe' ); ?>">
									<p class="jbf-field-error" data-error="name" hidden><?php esc_html_e( 'Please enter your name.', 'jacana-luxe' ); ?></p>
								</div>

								<div class="jbf-field jbf-field-half">
									<label for="jbf-email"><?php esc_html_e( 'Email address', 'jacana-luxe' ); ?> <span class="jbf-required" aria-hidden="true">*</span></label>
									<input type="email" id="jbf-email" name="email" autocomplete="email" required placeholder="<?php esc_attr_e( 'your@email.com', 'jacana-luxe' ); ?>">
									<p class="jbf-field-error" data-error="email" hidden><?php esc_html_e( 'Please enter a valid email.', 'jacana-luxe' ); ?></p>
								</div>

								<div class="jbf-field jbf-field-half">
									<label for="jbf-phone"><?php esc_html_e( 'Phone / WhatsApp', 'jacana-luxe' ); ?> <span class="jbf-optional"><?php esc_html_e( '(optional)', 'jacana-luxe' ); ?></span></label>
									<input type="tel" id="jbf-phone" name="phone" autocomplete="tel" placeholder="<?php esc_attr_e( '+264 ...', 'jacana-luxe' ); ?>">
								</div>

								<div class="jbf-field jbf-field-half">
									<label for="jbf-country"><?php esc_html_e( 'Country', 'jacana-luxe' ); ?> <span class="jbf-optional"><?php esc_html_e( '(optional)', 'jacana-luxe' ); ?></span></label>
									<input type="text" id="jbf-country" name="country" autocomplete="country-name" placeholder="<?php esc_attr_e( 'Where are you travelling from?', 'jacana-luxe' ); ?>">
								</div>

								<div class="jbf-field">
									<label for="jbf-message"><?php esc_html_e( 'Message', 'jacana-luxe' ); ?> <span class="jbf-optional"><?php esc_html_e( '(optional)', 'jacana-luxe' ); ?></span></label>
									<textarea id="jbf-message" name="message" rows="4" placeholder="<?php esc_attr_e( 'Anything else we should know? Special requests, must-see places, travel style…', 'jacana-luxe' ); ?>"></textarea>
								</div>
							</div>

							<p class="jbf-privacy-note">
								<?php esc_html_e( 'Your details are used only to prepare your quote. We never sell or share your information.', 'jacana-luxe' ); ?>
							</p>
						</fieldset>

						<!-- Success screen -->
						<div class="jbf-success" data-step="success" hidden>
							<div class="jbf-success-icon" aria-hidden="true">
								<svg width="48" height="48" viewBox="0 0 48 48" fill="none"><circle cx="24" cy="24" r="24" fill="var(--jacana-dunes,#db9751)" opacity=".12"/><path d="M14 24l7 7 13-13" stroke="var(--jacana-dunes,#db9751)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</div>
							<h3 class="jbf-success-heading"><?php esc_html_e( 'Request received!', 'jacana-luxe' ); ?></h3>
							<p class="jbf-success-body"><?php esc_html_e( 'Thank you — we\'ll review your details and send a personalised proposal within 24 hours.', 'jacana-luxe' ); ?></p>
							<p class="jbf-success-whatsapp">
								<?php esc_html_e( 'Want a faster reply?', 'jacana-luxe' ); ?>
								<a href="https://wa.me/264814040878" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Message us on WhatsApp', 'jacana-luxe' ); ?></a>
							</p>
						</div>

						<!-- Navigation buttons -->
						<div class="jbf-nav" data-form-nav>
							<button type="button" class="jbf-btn jbf-btn-back" data-jbf-back hidden>
								<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
								<?php esc_html_e( 'Back', 'jacana-luxe' ); ?>
							</button>
							<button type="button" class="jbf-btn jbf-btn-next" data-jbf-next>
								<?php esc_html_e( 'Next', 'jacana-luxe' ); ?>
								<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</button>
							<button type="submit" class="jbf-btn jbf-btn-submit" data-jbf-submit hidden>
								<?php esc_html_e( 'Send request', 'jacana-luxe' ); ?>
								<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M2 8h12M9 3l5 5-5 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</button>
						</div>

						<p class="jbf-submit-error" data-submit-error hidden><?php esc_html_e( 'Something went wrong. Please try again or contact us directly.', 'jacana-luxe' ); ?></p>

					</form>
				</div>
			</div>
		</section>

		<script>
		(function () {
			var form     = document.getElementById('jbf-form');
			if (!form) return;

			var totalSteps = 3;
			var current    = 1;

			var steps     = form.querySelectorAll('[data-step]');
			var dots      = form.querySelectorAll('[data-step-dot]');
			var lines     = form.querySelectorAll('.jbf-progress-line');
			var btnBack   = form.querySelector('[data-jbf-back]');
			var btnNext   = form.querySelector('[data-jbf-next]');
			var btnSubmit = form.querySelector('[data-jbf-submit]');
			var navWrap   = form.querySelector('[data-form-nav]');
			var submitErr = form.querySelector('[data-submit-error]');

			function showStep(n) {
				steps.forEach(function (el) {
					var s = parseInt(el.getAttribute('data-step'), 10);
					el.hidden = (s !== n && el.getAttribute('data-step') !== 'success');
					if (el.getAttribute('data-step') === 'success') el.hidden = true;
				});

				dots.forEach(function (dot) {
					var d = parseInt(dot.getAttribute('data-step-dot'), 10);
					dot.classList.toggle('is-active', d === n);
					dot.classList.toggle('is-done',   d < n);
				});

				lines.forEach(function (line, i) {
					line.classList.toggle('is-done', (i + 1) < n);
				});

				btnBack.hidden   = (n === 1);
				btnNext.hidden   = (n === totalSteps);
				btnSubmit.hidden = (n !== totalSteps);
				current = n;
			}

			function validateStep(n) {
				if (n === 1) {
					var checked = form.querySelectorAll('input[name="services[]"]:checked');
					var err = form.querySelector('[data-error="services"]');
					if (checked.length === 0) {
						if (err) err.hidden = false;
						return false;
					}
					if (err) err.hidden = true;
					return true;
				}
				if (n === 3) {
					var ok = true;
					var name  = form.querySelector('#jbf-name');
					var email = form.querySelector('#jbf-email');
					var errN  = form.querySelector('[data-error="name"]');
					var errE  = form.querySelector('[data-error="email"]');
					if (!name || !name.value.trim()) {
						if (errN) errN.hidden = false;
						ok = false;
					} else {
						if (errN) errN.hidden = true;
					}
					if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
						if (errE) errE.hidden = false;
						ok = false;
					} else {
						if (errE) errE.hidden = true;
					}
					return ok;
				}
				return true;
			}

			btnNext.addEventListener('click', function () {
				if (validateStep(current)) showStep(current + 1);
			});

			btnBack.addEventListener('click', function () {
				if (current > 1) showStep(current - 1);
			});

			/* Service card toggle */
			form.querySelectorAll('.jbf-service-option input[type="checkbox"]').forEach(function (cb) {
				cb.addEventListener('change', function () {
					var card = cb.closest('.jbf-service-option');
					if (card) card.classList.toggle('is-selected', cb.checked);
				});
			});

			/* Submit */
			form.addEventListener('submit', function (e) {
				e.preventDefault();
				if (!validateStep(3)) return;

				var btn = btnSubmit;
				btn.disabled = true;
				btn.textContent = '<?php echo esc_js( __( 'Sending…', 'jacana-luxe' ) ); ?>';
				if (submitErr) submitErr.hidden = true;

				/* Build payload */
				var services = Array.from(form.querySelectorAll('input[name="services[]"]:checked'))
					.map(function (cb) { return cb.value; });

				var payload = {
					name:               (form.querySelector('#jbf-name')         || {}).value || '',
					email:              (form.querySelector('#jbf-email')        || {}).value || '',
					phone:              (form.querySelector('#jbf-phone')        || {}).value || '',
					country:            (form.querySelector('#jbf-country')      || {}).value || '',
					travel_dates:       (form.querySelector('#jbf-travel-dates') || {}).value || '',
					party_size:         (form.querySelector('#jbf-party-size')   || {}).value || '',
					destination_interest:(form.querySelector('#jbf-destinations')|| {}).value || '',
					budget_tier:        (form.querySelector('#jbf-budget')       || {}).value || '',
					accommodation_style:(form.querySelector('#jbf-accommodation') || {}).value || '',
					message:            (form.querySelector('#jbf-message')      || {}).value || '',
					selected_services:  services,
					service_interest:   services[0] || '',
					source_page:        window.location.href,
					source_widget:      'jacana_booking_form',
				};

				fetch(form.dataset.endpoint, {
					method:  'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce':   form.dataset.nonce,
					},
					body: JSON.stringify(payload),
				})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					if (data && data.ok) {
						steps.forEach(function (el) { el.hidden = true; });
						var success = form.querySelector('[data-step="success"]');
						if (success) success.hidden = false;
						if (navWrap) navWrap.hidden = true;
						var progress = form.querySelector('.jbf-progress');
						if (progress) progress.hidden = true;
					} else {
						if (submitErr) submitErr.hidden = false;
						btn.disabled = false;
						btn.innerHTML = '<?php echo esc_js( __( 'Send request', 'jacana-luxe' ) ); ?> <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M2 8h12M9 3l5 5-5 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>';
					}
				})
				.catch(function () {
					if (submitErr) submitErr.hidden = false;
					btn.disabled = false;
					btn.innerHTML = '<?php echo esc_js( __( 'Send request', 'jacana-luxe' ) ); ?> <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M2 8h12M9 3l5 5-5 5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>';
				});
			});

			showStep(1);
		}());
		</script>
		<?php
	}
}
