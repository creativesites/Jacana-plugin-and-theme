<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Car Rental Road Reel — portrait YouTube video + feature content split.
 */
class Jacana_Luxe_Car_Rental_Road_Reel extends \Elementor\Widget_Base {

	public function get_name()       { return 'jacana_car_rental_road_reel'; }
	public function get_title()      { return __( 'Car Rental Road Reel', 'jacana-luxe' ); }
	public function get_icon()       { return 'eicon-youtube'; }
	public function get_categories() { return array( 'jacana-luxe' ); }

	protected function register_controls() {

		// ── Content ──────────────────────────────────────────────────
		$this->start_controls_section( 'content', array( 'label' => __( 'Content', 'jacana-luxe' ) ) );

		$this->add_control( 'kicker', array(
			'label'   => __( 'Kicker', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'BEHIND THE WHEEL', 'jacana-luxe' ),
		) );

		$this->add_control( 'heading', array(
			'label'   => __( 'Heading', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'The road is yours.', 'jacana-luxe' ),
		) );

		$this->add_control( 'heading_em', array(
			'label'   => __( 'Heading Accent (italic line)', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Namibia, unfiltered.', 'jacana-luxe' ),
		) );

		$this->add_control( 'copy', array(
			'label'   => __( 'Copy', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'default' => __( 'Wide open skies, red dunes at sunrise, salt pans that stretch to the horizon — Namibia was made to be driven. Our rental vehicles are prepared for every surface so nothing stands between you and the landscape.', 'jacana-luxe' ),
		) );

		$this->end_controls_section();

		// ── Video ─────────────────────────────────────────────────────
		$this->start_controls_section( 'video_section', array( 'label' => __( 'Video', 'jacana-luxe' ) ) );

		$this->add_control( 'video_id', array(
			'label'       => __( 'YouTube Video ID', 'jacana-luxe' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'd3ZOWkuoD7A',
			'description' => __( 'The ID from the YouTube URL, e.g. d3ZOWkuoD7A', 'jacana-luxe' ),
		) );

		$this->add_control( 'video_caption', array(
			'label'   => __( 'Caption below video', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Car Rental — Down the dunes', 'jacana-luxe' ),
		) );

		$this->end_controls_section();

		// ── Features ──────────────────────────────────────────────────
		$this->start_controls_section( 'features_section', array( 'label' => __( 'Features', 'jacana-luxe' ) ) );

		$repeater = new \Elementor\Repeater();

		$repeater->add_control( 'label', array(
			'label'   => __( 'Label', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Feature', 'jacana-luxe' ),
		) );

		$repeater->add_control( 'detail', array(
			'label'   => __( 'Detail', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'default' => '',
		) );

		$this->add_control( 'features', array(
			'label'       => __( 'Features', 'jacana-luxe' ),
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'default'     => array(
				array(
					'label'  => __( 'Built for Namibia\'s terrain', 'jacana-luxe' ),
					'detail' => __( '4×4 and rooftop-tent-ready vehicles matched to gravel, sand, and tar routes.', 'jacana-luxe' ),
				),
				array(
					'label'  => __( 'Route-matched recommendations', 'jacana-luxe' ),
					'detail' => __( 'We study your itinerary and suggest the vehicle that fits — not just the one available.', 'jacana-luxe' ),
				),
				array(
					'label'  => __( 'Jacana support throughout', 'jacana-luxe' ),
					'detail' => __( 'Our team is reachable for the duration of your journey, not only at pick-up.', 'jacana-luxe' ),
				),
			),
			'title_field' => '{{{ label }}}',
		) );

		$this->end_controls_section();

		// ── CTA ───────────────────────────────────────────────────────
		$this->start_controls_section( 'cta_section', array( 'label' => __( 'CTA', 'jacana-luxe' ) ) );

		$this->add_control( 'cta_label', array(
			'label'   => __( 'CTA Label', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Plan my self-drive', 'jacana-luxe' ),
		) );

		$this->add_control( 'cta_link', array(
			'label'       => __( 'CTA Link', 'jacana-luxe' ),
			'type'        => \Elementor\Controls_Manager::URL,
			'placeholder' => 'https://',
		) );

		$this->add_control( 'cta_secondary_label', array(
			'label'   => __( 'Secondary CTA Label', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'View fleet options', 'jacana-luxe' ),
		) );

		$this->add_control( 'cta_secondary_link', array(
			'label'       => __( 'Secondary CTA Link', 'jacana-luxe' ),
			'type'        => \Elementor\Controls_Manager::URL,
			'placeholder' => 'https://',
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$s           = $this->get_settings_for_display();
		$video_id    = ! empty( $s['video_id'] ) ? sanitize_text_field( $s['video_id'] ) : 'd3ZOWkuoD7A';
		$embed_url   = 'https://www.youtube.com/embed/' . $video_id . '?autoplay=1&mute=1&loop=1&playlist=' . $video_id . '&controls=0&rel=0&modestbranding=1&playsinline=1&iv_load_policy=3&disablekb=1';
		$cta         = $s['cta_link'] ?? array();
		$cta2        = $s['cta_secondary_link'] ?? array();
		$features    = $s['features'] ?? array();
		?>

		<section class="jacana-crr-section">

			<!-- Ambient glows -->
			<div class="jacana-crr-glow jacana-crr-glow--warm" aria-hidden="true"></div>
			<div class="jacana-crr-glow jacana-crr-glow--cool" aria-hidden="true"></div>

			<div class="jacana-crr-inner">

				<!-- ── Video column ── -->
				<div class="jacana-crr-video-col jacana-reveal">

					<div class="jacana-crr-video-panel">

						<div class="jacana-crr-ratio">
							<iframe
								class="jacana-crr-iframe"
								src="<?php echo esc_url( $embed_url ); ?>"
								title="<?php echo esc_attr( $s['video_caption'] ?? 'Car Rental video' ); ?>"
								allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
								aria-hidden="true"
								tabindex="-1"
							></iframe>
						</div>

						<!-- Gradient masks: top hides channel name, bottom adds depth -->
						<div class="jacana-crr-mask jacana-crr-mask--top" aria-hidden="true"></div>
						<div class="jacana-crr-mask jacana-crr-mask--bottom" aria-hidden="true"></div>

						<!-- Cultural stripe -->
						<div class="jacana-crr-stripe" aria-hidden="true"></div>

					</div>

				</div>

				<!-- ── Content column ── -->
				<div class="jacana-crr-content">

					<?php if ( ! empty( $s['kicker'] ) ) : ?>
						<div class="jacana-crr-kicker jacana-reveal"><?php echo esc_html( $s['kicker'] ); ?></div>
					<?php endif; ?>

					<h2 class="jacana-crr-heading jacana-reveal" style="--jacana-delay:80ms;">
						<?php echo esc_html( $s['heading'] ); ?>
						<?php if ( ! empty( $s['heading_em'] ) ) : ?>
							<em><?php echo esc_html( $s['heading_em'] ); ?></em>
						<?php endif; ?>
					</h2>

					<?php if ( ! empty( $s['copy'] ) ) : ?>
						<p class="jacana-crr-copy jacana-reveal" style="--jacana-delay:140ms;">
							<?php echo esc_html( $s['copy'] ); ?>
						</p>
					<?php endif; ?>

					<?php if ( ! empty( $features ) ) : ?>
						<ul class="jacana-crr-features" role="list">
							<?php foreach ( $features as $i => $feat ) :
								$delay = 200 + ( $i * 70 );
								?>
								<li class="jacana-crr-feature jacana-reveal" style="--jacana-delay:<?php echo $delay; ?>ms;">
									<span class="jacana-crr-feature-dot" aria-hidden="true"></span>
									<div class="jacana-crr-feature-text">
										<span class="jacana-crr-feature-label"><?php echo esc_html( $feat['label'] ); ?></span>
										<?php if ( ! empty( $feat['detail'] ) ) : ?>
											<span class="jacana-crr-feature-detail"><?php echo esc_html( $feat['detail'] ); ?></span>
										<?php endif; ?>
									</div>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<div class="jacana-crr-actions jacana-reveal" style="--jacana-delay:420ms;">

						<?php if ( ! empty( $s['cta_label'] ) ) :
							$href    = ! empty( $cta['url'] ) ? $cta['url'] : '#';
							$target  = ! empty( $cta['is_external'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';
							$is_ai   = ( $href === '#' || empty( $cta['url'] ) );
							$ai_attrs = $is_ai ? ' data-jacana-ai-flow="planner" data-jacana-service="car_rental" data-jacana-widget="jacana_car_rental_road_reel"' : '';
							?>
							<a class="jacana-crr-cta"
							   href="<?php echo esc_url( $href ); ?>"
							   <?php echo $target . $ai_attrs; ?>>
								<?php echo esc_html( $s['cta_label'] ); ?>
								<span class="jacana-crr-arrow" aria-hidden="true">→</span>
							</a>
						<?php endif; ?>

						<?php if ( ! empty( $s['cta_secondary_label'] ) ) :
							$href2   = ! empty( $cta2['url'] ) ? $cta2['url'] : '#';
							$target2 = ! empty( $cta2['is_external'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';
							?>
							<a class="jacana-crr-cta-ghost"
							   href="<?php echo esc_url( $href2 ); ?>"
							   <?php echo $target2; ?>>
								<?php echo esc_html( $s['cta_secondary_label'] ); ?>
								<span class="jacana-crr-arrow" aria-hidden="true">→</span>
							</a>
						<?php endif; ?>

					</div>

				</div>

			</div>

		</section>
		<?php
	}
}
