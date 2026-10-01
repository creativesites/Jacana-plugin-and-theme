<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Windhoek Weather — Full-width cinematic weather display specifically for Windhoek.
 * Data sourced from Jacana_Weather_Service (Open-Meteo).
 */
class Jacana_Luxe_Windhoek_Weather extends \Elementor\Widget_Base {

	public function get_name()       { return 'jacana_windhoek_weather'; }
	public function get_title()      { return __( 'Windhoek Weather', 'jacana-luxe' ); }
	public function get_icon()       { return 'eicon-cloud'; }
	public function get_categories() { return [ 'jacana-luxe' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'content_section', [
			'label' => __( 'Content', 'jacana-luxe' ),
		] );

		$this->add_control( 'kicker', [
			'label'   => __( 'Kicker', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Live from the Capital', 'jacana-luxe' ),
		] );

		$this->add_control( 'heading', [
			'label'   => __( 'Heading', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Windhoek Conditions', 'jacana-luxe' ),
		] );

		$this->add_control( 'background_image', [
			'label'   => __( 'Background Image', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::MEDIA,
			'default' => [ 'url' => '' ],
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$kicker   = esc_html( $settings['kicker'] ?? '' );
		$heading  = esc_html( $settings['heading'] ?? '' );
		$bg_url   = ! empty( $settings['background_image']['url'] ) ? esc_url( $settings['background_image']['url'] ) : '';

		$is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();

		$weather = null;
		if ( $is_editor ) {
			$weather = [
				'temp'     => 28,
				'humidity' => 14,
				'wind'     => 18,
				'uv'       => 8.0,
				'label'    => 'Clear Sky',
				'icon'     => 'clear',
			];
		} else {
			$transient_key = 'jacana_weather_windhoek_cache';
			$weather = get_transient( $transient_key );
			
			if ( false === $weather ) {
				// Windhoek approximate coordinates
				$weather = Jacana_Weather_Service::fetch_from_api( -22.56, 17.06 );
				if ( $weather ) {
					// Cache for 1 hour
					set_transient( $transient_key, $weather, 3600 );
				}
			}
		}

		if ( ! $weather && ! $is_editor ) {
			echo '<p class="jacana-wx-empty">' . esc_html__( 'Weather data unavailable.', 'jacana-luxe' ) . '</p>';
			return;
		}

		$bg_style = $bg_url ? "background-image: url('{$bg_url}');" : '';
		$icon_svg = Jacana_Weather_Service::get_icon_svg( $weather ? $weather['icon'] : 'unknown' );
		?>
		<section class="jacana-whk-wx <?php echo $bg_url ? 'has-bg-img' : ''; ?>" style="<?php echo $bg_style; ?>">
			<div class="jacana-whk-wx-overlay" aria-hidden="true"></div>
			
			<!-- Giant faded background icon watermark -->
			<div class="jacana-whk-wx-watermark" aria-hidden="true">
				<?php echo $icon_svg; ?>
			</div>

			<div class="jacana-whk-wx-inner">
				<header class="jacana-whk-wx-header">
					<?php if ( $kicker ) : ?>
						<span class="jacana-whk-wx-kicker"><?php echo $kicker; ?></span>
					<?php endif; ?>
					<?php if ( $heading ) : ?>
						<h2 class="jacana-whk-wx-heading"><?php echo $heading; ?></h2>
					<?php endif; ?>
				</header>

				<div class="jacana-whk-wx-main">
					<div class="jacana-whk-wx-temp-wrapper">
						<span class="jacana-whk-wx-temp"><?php echo $weather && $weather['temp'] !== null ? esc_html( $weather['temp'] ) : '—'; ?></span>
						<span class="jacana-whk-wx-unit">°C</span>
					</div>
					<div class="jacana-whk-wx-condition">
						<?php echo esc_html( $weather ? $weather['label'] : __( 'Unavailable', 'jacana-luxe' ) ); ?>
					</div>
				</div>

				<div class="jacana-whk-wx-stats">
					<div class="jacana-whk-wx-stat">
						<span class="jacana-whk-wx-stat-val">
							<?php echo $weather && $weather['wind'] !== null ? esc_html( $weather['wind'] ) . ' km/h' : '—'; ?>
						</span>
						<span class="jacana-whk-wx-stat-lbl"><?php esc_html_e( 'Wind', 'jacana-luxe' ); ?></span>
					</div>
					<div class="jacana-whk-wx-stat">
						<span class="jacana-whk-wx-stat-val">
							<?php echo $weather && $weather['humidity'] !== null ? esc_html( $weather['humidity'] ) . '%' : '—'; ?>
						</span>
						<span class="jacana-whk-wx-stat-lbl"><?php esc_html_e( 'Humidity', 'jacana-luxe' ); ?></span>
					</div>
					<div class="jacana-whk-wx-stat">
						<span class="jacana-whk-wx-stat-val">
							<?php echo $weather && $weather['uv'] !== null ? esc_html( Jacana_Weather_Service::uv_label( $weather['uv'] ) ) : '—'; ?>
						</span>
						<span class="jacana-whk-wx-stat-lbl"><?php esc_html_e( 'UV Index', 'jacana-luxe' ); ?></span>
					</div>
				</div>
			</div>
		</section>
		<?php
	}
}
