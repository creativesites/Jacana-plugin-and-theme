<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Weather Grid — displays current weather for all jacana_destination posts
 * in a responsive card grid. Data sourced from Jacana_Weather_Service (Open-Meteo).
 */
class Jacana_Luxe_Weather_Grid extends \Elementor\Widget_Base {

	public function get_name()       { return 'jacana_weather_grid'; }
	public function get_title()      { return __( 'Weather Grid', 'jacana-luxe' ); }
	public function get_icon()       { return 'eicon-cloud-upload'; }
	public function get_categories() { return [ 'jacana-luxe' ]; }

	// ── Controls ──────────────────────────────────────────────────────────────

	protected function register_controls() {

		/* Content ─────────────────────────────────────────────────────────── */
		$this->start_controls_section( 'content_section', [
			'label' => __( 'Content', 'jacana-luxe' ),
		] );

		$this->add_control( 'kicker', [
			'label'   => __( 'Kicker', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Live Conditions', 'jacana-luxe' ),
		] );

		$this->add_control( 'heading', [
			'label'   => __( 'Heading', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Namibia Weather Today', 'jacana-luxe' ),
		] );

		$this->add_control( 'intro', [
			'label'   => __( 'Intro Text', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'default' => __( 'Current weather across our key destinations, updated hourly.', 'jacana-luxe' ),
		] );

		$this->end_controls_section();

		/* Display ─────────────────────────────────────────────────────────── */
		$this->start_controls_section( 'display_section', [
			'label' => __( 'Display', 'jacana-luxe' ),
		] );

		$this->add_control( 'show_uv', [
			'label'        => __( 'Show UV Index', 'jacana-luxe' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label_on'     => __( 'Yes', 'jacana-luxe' ),
			'label_off'    => __( 'No', 'jacana-luxe' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'destinations_limit', [
			'label'   => __( 'Limit destinations (0 = all)', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'min'     => 0,
			'max'     => 30,
			'step'    => 1,
			'default' => 0,
		] );

		$this->end_controls_section();
	}

	// ── Render ────────────────────────────────────────────────────────────────

	protected function render() {
		$settings = $this->get_settings_for_display();

		$kicker = esc_html( $settings['kicker'] ?? '' );
		$heading = esc_html( $settings['heading'] ?? '' );
		$intro   = esc_html( $settings['intro']   ?? '' );
		$theme   = 'light';
		$show_uv = ( $settings['show_uv'] ?? '' ) === 'yes';
		$limit   = max( 0, (int) ( $settings['destinations_limit'] ?? 0 ) );

		// In the Elementor editor skip the live API call to avoid hammering
		// Open-Meteo on every control change.
		$is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();

		/* Query destinations ─────────────────────────────────────────────── */
		$query = new WP_Query( [
			'post_type'      => 'jacana_destination',
			'post_status'    => 'publish',
			'posts_per_page' => $limit > 0 ? $limit : -1,
			'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
			'no_found_rows'  => true,
		] );

		if ( ! $query->have_posts() ) {
			echo '<p class="jacana-wx-empty">' . esc_html__( 'No destinations found.', 'jacana-luxe' ) . '</p>';
			return;
		}

		/* Fetch weather (skip in editor) ─────────────────────────────────── */
		$weather_map = [];
		if ( ! $is_editor ) {
			$weather_map = Jacana_Weather_Service::get_for_all_destinations();
		}

		/* Output ─────────────────────────────────────────────────────────── */
		?>
		<section class="jacana-wx-grid-section jacana-wx--<?php echo esc_attr( $theme ); ?>">
			<div class="jacana-wx-grid-inner">

				<?php if ( $kicker || $heading ) : ?>
				<header class="jacana-wx-grid-header">
					<?php if ( $kicker ) : ?>
						<div class="jacana-wx-grid-kicker"><?php echo $kicker; ?></div>
					<?php endif; ?>
					<?php if ( $heading ) : ?>
						<h2 class="jacana-wx-grid-heading"><?php echo $heading; ?></h2>
					<?php endif; ?>
					<?php if ( $intro ) : ?>
						<p class="jacana-wx-grid-intro"><?php echo $intro; ?></p>
					<?php endif; ?>
				</header>
				<?php endif; ?>

				<div class="jacana-wx-grid">

					<?php foreach ( $query->posts as $post ) :
						$id      = $post->ID;
						$name    = get_the_title( $id );
						$link    = get_permalink( $id );
						$thumb   = get_the_post_thumbnail_url( $id, 'medium_large' );
						$region  = '';
						$terms   = get_the_terms( $id, 'destination_region' );
						if ( $terms && ! is_wp_error( $terms ) ) {
							$region = $terms[0]->name;
						}

						$weather = $is_editor
							? $this->editor_placeholder_data()
							: ( $weather_map[ $id ] ?? null );

						if ( ! $weather && ! $is_editor ) continue;
					?>
					<article class="jacana-wx-card<?php echo $weather ? '' : ' jacana-wx-card--unavailable'; ?>"
					         data-wx-post="<?php echo esc_attr( $id ); ?>">

						<?php if ( $thumb ) : ?>
						<div class="jacana-wx-card-img"
						     style="background-image:url('<?php echo esc_url( $thumb ); ?>');"
						     role="img"
						     aria-label="<?php echo esc_attr( $name ); ?>"></div>
						<?php endif; ?>

						<div class="jacana-wx-card-top">
							<div class="jacana-wx-icon" aria-hidden="true">
								<?php echo $weather
									? Jacana_Weather_Service::get_icon_svg( $weather['icon'] )
									: Jacana_Weather_Service::get_icon_svg( 'unknown' ); ?>
							</div>

							<div class="jacana-wx-card-temp">
								<?php if ( $weather && $weather['temp'] !== null ) : ?>
									<span class="jacana-wx-temp"><?php echo esc_html( $weather['temp'] ); ?></span><span class="jacana-wx-unit">°C</span>
								<?php else : ?>
									<span class="jacana-wx-temp jacana-wx-temp--na">—</span>
								<?php endif; ?>
							</div>
						</div>

						<div class="jacana-wx-card-condition">
							<?php echo esc_html( $weather ? $weather['label'] : __( 'Unavailable', 'jacana-luxe' ) ); ?>
						</div>

						<div class="jacana-wx-stats">
							<div class="jacana-wx-stat">
								<span class="jacana-wx-stat-icon" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
										<path d="M8 2 Q12 6 8 10 Q4 6 8 2z"/>
										<line x1="8" y1="10" x2="8" y2="14"/>
									</svg>
								</span>
								<span class="jacana-wx-stat-value">
									<?php echo $weather && $weather['wind'] !== null
										? esc_html( $weather['wind'] ) . ' km/h'
										: '—'; ?>
								</span>
								<span class="jacana-wx-stat-label"><?php esc_html_e( 'Wind', 'jacana-luxe' ); ?></span>
							</div>

							<div class="jacana-wx-stat">
								<span class="jacana-wx-stat-icon" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
										<path d="M8 2 Q11 7 8 12 Q5 7 8 2z"/>
										<line x1="5" y1="10" x2="11" y2="10"/>
									</svg>
								</span>
								<span class="jacana-wx-stat-value">
									<?php echo $weather && $weather['humidity'] !== null
										? esc_html( $weather['humidity'] ) . '%'
										: '—'; ?>
								</span>
								<span class="jacana-wx-stat-label"><?php esc_html_e( 'Humidity', 'jacana-luxe' ); ?></span>
							</div>

							<?php if ( $show_uv ) : ?>
							<div class="jacana-wx-stat">
								<span class="jacana-wx-stat-icon" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
										<circle cx="8" cy="8" r="3"/>
										<line x1="8" y1="1" x2="8" y2="3"/>
										<line x1="8" y1="13" x2="8" y2="15"/>
										<line x1="1" y1="8" x2="3" y2="8"/>
										<line x1="13" y1="8" x2="15" y2="8"/>
									</svg>
								</span>
								<span class="jacana-wx-stat-value">
									<?php echo $weather && $weather['uv'] !== null
										? esc_html( Jacana_Weather_Service::uv_label( $weather['uv'] ) )
										: '—'; ?>
								</span>
								<span class="jacana-wx-stat-label"><?php esc_html_e( 'UV', 'jacana-luxe' ); ?></span>
							</div>
							<?php endif; ?>
						</div>

						<footer class="jacana-wx-card-footer">
							<span class="jacana-wx-dest-name"><?php echo esc_html( $name ); ?></span>
							<?php if ( $region ) : ?>
								<span class="jacana-wx-dest-region"><?php echo esc_html( $region ); ?></span>
							<?php endif; ?>
							<?php if ( $link ) : ?>
								<a href="<?php echo esc_url( $link ); ?>"
								   class="jacana-wx-card-cta"
								   aria-label="<?php echo esc_attr( sprintf( __( 'Explore %s', 'jacana-luxe' ), $name ) ); ?>">
									<?php esc_html_e( 'Explore', 'jacana-luxe' ); ?>
									<span aria-hidden="true">→</span>
								</a>
							<?php endif; ?>
						</footer>

					</article>
					<?php endforeach; ?>

				</div><!-- .jacana-wx-grid -->

				<p class="jacana-wx-attribution">
					<?php esc_html_e( 'Weather data by Open-Meteo · Updated hourly', 'jacana-luxe' ); ?>
				</p>

			</div><!-- .jacana-wx-grid-inner -->
		</section>
		<?php
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	/** Static placeholder so the editor looks populated without hitting the API. */
	private function editor_placeholder_data() {
		return [
			'temp'     => 28,
			'humidity' => 14,
			'wind'     => 18,
			'uv'       => 8.0,
			'wmo'      => 0,
			'label'    => 'Clear Sky',
			'icon'     => 'clear',
			'fetched'  => time(),
		];
	}
}
