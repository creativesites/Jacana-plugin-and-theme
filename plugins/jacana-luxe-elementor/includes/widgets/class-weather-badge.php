<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Weather Badge — compact weather display for a single destination.
 * Designed to sit inside destination cards, the Explorer panel quick-facts bar,
 * or directly on single-jacana_destination.php.
 *
 * If the post has no GPS or the API is unreachable the badge renders nothing —
 * it never shows an error block.
 */
class Jacana_Luxe_Weather_Badge extends \Elementor\Widget_Base {

	public function get_name()       { return 'jacana_weather_badge'; }
	public function get_title()      { return __( 'Weather Badge', 'jacana-luxe' ); }
	public function get_icon()       { return 'eicon-info-box'; }
	public function get_categories() { return [ 'jacana-luxe' ]; }

	// ── Controls ──────────────────────────────────────────────────────────────

	protected function register_controls() {

		$this->start_controls_section( 'content_section', [
			'label' => __( 'Content', 'jacana-luxe' ),
		] );

		$this->add_control( 'source', [
			'label'   => __( 'Destination Source', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => [
				'auto'   => __( 'Auto — current post in loop', 'jacana-luxe' ),
				'manual' => __( 'Manual — choose by post ID', 'jacana-luxe' ),
			],
			'default' => 'auto',
		] );

		$this->add_control( 'destination_id', [
			'label'     => __( 'Destination Post ID', 'jacana-luxe' ),
			'type'      => \Elementor\Controls_Manager::NUMBER,
			'min'       => 1,
			'condition' => [ 'source' => 'manual' ],
			'description' => __( 'Find the ID in Admin → Destinations → edit the post (URL shows post=123).', 'jacana-luxe' ),
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'display_section', [
			'label' => __( 'Display', 'jacana-luxe' ),
		] );

		$this->add_control( 'badge_style', [
			'label'   => __( 'Badge Style', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => [
				'inline'       => __( 'Inline strip (inside a facts bar)', 'jacana-luxe' ),
				'compact-card' => __( 'Compact card (standalone)', 'jacana-luxe' ),
			],
			'default' => 'inline',
		] );

		$this->add_control( 'badge_theme', [
			'label'   => __( 'Colour Theme', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'options' => [
				'light' => __( 'Light', 'jacana-luxe' ),
				'dark'  => __( 'Dark', 'jacana-luxe' ),
			],
			'default' => 'light',
		] );

		$this->add_control( 'show_uv', [
			'label'        => __( 'Show UV Index', 'jacana-luxe' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label_on'     => __( 'Yes', 'jacana-luxe' ),
			'label_off'    => __( 'No', 'jacana-luxe' ),
			'return_value' => 'yes',
			'default'      => '',
		] );

		$this->add_control( 'show_dest_name', [
			'label'        => __( 'Show Destination Name', 'jacana-luxe' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label_on'     => __( 'Yes', 'jacana-luxe' ),
			'label_off'    => __( 'No', 'jacana-luxe' ),
			'return_value' => 'yes',
			'default'      => '',
			'condition'    => [ 'badge_style' => 'compact-card' ],
		] );

		$this->end_controls_section();
	}

	// ── Render ────────────────────────────────────────────────────────────────

	protected function render() {
		$settings = $this->get_settings_for_display();

		$source  = $settings['source'] ?? 'auto';
		$style   = in_array( $settings['badge_style'] ?? 'inline', [ 'inline', 'compact-card' ], true )
		           ? $settings['badge_style'] : 'inline';
		$theme   = in_array( $settings['badge_theme'] ?? 'light', [ 'light', 'dark' ], true )
		           ? $settings['badge_theme'] : 'light';
		$show_uv = ( $settings['show_uv'] ?? '' ) === 'yes';
		$show_name = ( $settings['show_dest_name'] ?? '' ) === 'yes';

		/* Resolve post ID ────────────────────────────────────────────────── */
		if ( $source === 'manual' ) {
			$post_id = (int) ( $settings['destination_id'] ?? 0 );
		} else {
			$post_id = (int) get_the_ID();
		}

		// In the Elementor editor use a placeholder to avoid live API calls.
		$is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();

		if ( $is_editor ) {
			$weather = [
				'temp'     => 28,
				'humidity' => 14,
				'wind'     => 18,
				'uv'       => 8.0,
				'label'    => 'Clear Sky',
				'icon'     => 'clear',
			];
			if ( $show_name && $post_id > 0 ) {
				$dest_name = get_the_title( $post_id ) ?: __( 'Destination', 'jacana-luxe' );
			} else {
				$dest_name = '';
			}
		} else {
			if ( $post_id <= 0 ) {
				return; // Nothing to show
			}
			$weather = Jacana_Weather_Service::get_for_post( $post_id );
			if ( ! $weather ) {
				return; // GPS missing or API down — silently empty
			}
			$dest_name = $show_name ? get_the_title( $post_id ) : '';
		}

		self::render_badge( $weather, $style, $theme, $show_uv, $dest_name );
	}

	// ── Static render helper (also callable from theme templates) ─────────────

	/**
	 * Render the badge HTML.
	 * Called from render() and optionally from jacana_render_weather_badge() in functions.php.
	 *
	 * @param array  $weather   Data array from Jacana_Weather_Service::get_for_post()
	 * @param string $style     'inline' | 'compact-card'
	 * @param string $theme     'light' | 'dark'
	 * @param bool   $show_uv
	 * @param string $dest_name Optional destination label shown in compact-card
	 */
	public static function render_badge( $weather, $style = 'inline', $theme = 'light', $show_uv = false, $dest_name = '' ) {
		$classes = implode( ' ', array_filter( [
			'jacana-wx-badge',
			'jacana-wx-badge--' . $style,
			'jacana-wx--'       . $theme,
		] ) );
		?>
		<div class="<?php echo esc_attr( $classes ); ?>">

			<div class="jacana-wx-badge-icon" aria-hidden="true">
				<?php echo Jacana_Weather_Service::get_icon_svg( $weather['icon'] ?? 'unknown' ); ?>
			</div>

			<div class="jacana-wx-badge-main">
				<?php if ( $weather['temp'] !== null ) : ?>
					<span class="jacana-wx-badge-temp"><?php echo esc_html( $weather['temp'] ); ?><sup class="jacana-wx-badge-unit">°C</sup></span>
				<?php endif; ?>
				<span class="jacana-wx-badge-condition"><?php echo esc_html( $weather['label'] ?? '' ); ?></span>
			</div>

			<div class="jacana-wx-badge-stats">
				<?php if ( $weather['wind'] !== null ) : ?>
					<span class="jacana-wx-badge-stat">
						<span class="jacana-wx-badge-stat-label"><?php esc_html_e( 'Wind', 'jacana-luxe' ); ?></span>
						<span class="jacana-wx-badge-stat-val"><?php echo esc_html( $weather['wind'] ); ?> <span class="jacana-wx-badge-stat-unit">km/h</span></span>
					</span>
				<?php endif; ?>

				<?php if ( $weather['humidity'] !== null ) : ?>
					<span class="jacana-wx-badge-stat">
						<span class="jacana-wx-badge-stat-label"><?php esc_html_e( 'Humidity', 'jacana-luxe' ); ?></span>
						<span class="jacana-wx-badge-stat-val"><?php echo esc_html( $weather['humidity'] ); ?><span class="jacana-wx-badge-stat-unit">%</span></span>
					</span>
				<?php endif; ?>

				<?php if ( $show_uv && $weather['uv'] !== null ) : ?>
					<span class="jacana-wx-badge-stat">
						<span class="jacana-wx-badge-stat-label"><?php esc_html_e( 'UV', 'jacana-luxe' ); ?></span>
						<span class="jacana-wx-badge-stat-val"><?php echo esc_html( Jacana_Weather_Service::uv_label( $weather['uv'] ) ); ?></span>
					</span>
				<?php endif; ?>
			</div>

			<?php if ( $dest_name ) : ?>
				<div class="jacana-wx-badge-dest"><?php echo esc_html( $dest_name ); ?></div>
			<?php endif; ?>

		</div>
		<?php
	}
}
