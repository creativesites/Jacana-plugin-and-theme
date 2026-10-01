<?php
namespace Luxe_Admin\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the "Skins" for the WordPress Admin.
 */
class Theme {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_head', [ $this, 'inject_skin_variables' ], 1 );
	}

	public function inject_skin_variables() {
		$settings = \Luxe_Admin\Core\Main::get_settings();
		$theme     = $settings['theme'];
		$appearance = $settings['appearance'];
		$palette   = $this->get_theme_palette( $theme );

		if ( ! empty( $appearance['custom_palette'] ) ) {
			if ( ! empty( $appearance['bg'] ) ) {
				$palette['bg'] = $appearance['bg'];
			}
			if ( ! empty( $appearance['sidebar'] ) ) {
				$palette['sidebar'] = $appearance['sidebar'];
				$palette['toolbar'] = $appearance['sidebar'];
			}
			if ( ! empty( $appearance['accent'] ) ) {
				$palette['accent'] = $appearance['accent'];
			}
			if ( ! empty( $appearance['text'] ) ) {
				$palette['text'] = $appearance['text'];
			}
			if ( ! empty( $appearance['card_bg'] ) ) {
				$palette['card_bg'] = $appearance['card_bg'];
			}
		}

		$radius = isset( $appearance['radius'] ) ? absint( $appearance['radius'] ) : 12;
		$radius = min( max( $radius, 0 ), 24 );
		$accent_contrast = $this->contrast_text_color( $palette['accent'] );
		$font_stack      = $this->get_font_stack( $appearance['font'] );
		$is_compact      = ! empty( $appearance['compact_mode'] );

		?>
		<style type="text/css" id="luxe-admin-skin">
			:root {
				--luxe-bg: <?php echo esc_html( $palette['bg'] ); ?>;
				--luxe-sidebar: <?php echo esc_html( $palette['sidebar'] ); ?>;
				--luxe-toolbar: <?php echo esc_html( $palette['toolbar'] ); ?>;
				--luxe-accent: <?php echo esc_html( $palette['accent'] ); ?>;
				--luxe-accent-contrast: <?php echo esc_html( $accent_contrast ); ?>;
				--luxe-text: <?php echo esc_html( $palette['text'] ); ?>;
				--luxe-border: <?php echo esc_html( $palette['border'] ); ?>;
				--luxe-card-bg: <?php echo esc_html( $palette['card_bg'] ); ?>;
				--luxe-radius: <?php echo esc_html( $radius ); ?>px;
			}

			body.wp-admin {
				background: var(--luxe-bg) !important;
				font-family: <?php echo esc_html( $font_stack ); ?> !important;
			}

			#wpcontent,
			#wpbody-content {
				background: var(--luxe-bg) !important;
			}

			#adminmenuback,
			#adminmenu,
			#adminmenu .wp-submenu {
				background: var(--luxe-sidebar) !important;
			}

			#wpadminbar {
				background: var(--luxe-toolbar) !important;
			}

			#adminmenu a,
			#adminmenu .wp-submenu a,
			#wpadminbar .ab-item,
			#wpadminbar a.ab-item {
				color: var(--luxe-text) !important;
			}

			#adminmenu li.current a.menu-top,
			#adminmenu li.wp-has-current-submenu a.wp-has-current-submenu,
			#adminmenu .wp-submenu li.current a,
			#adminmenu .wp-submenu li.current a:hover {
				background: var(--luxe-accent) !important;
				color: var(--luxe-accent-contrast) !important;
			}

			.wp-core-ui .button-primary {
				background: var(--luxe-accent) !important;
				border-color: var(--luxe-accent) !important;
				color: var(--luxe-accent-contrast) !important;
			}

			.postbox,
			.stuffbox,
			.card,
			.notice {
				background: var(--luxe-card-bg) !important;
				border-color: var(--luxe-border) !important;
			}

			.postbox,
			.stuffbox,
			.wp-core-ui .button,
			.wp-core-ui .button-primary,
			.wp-core-ui select,
			input[type="text"],
			input[type="search"],
			input[type="password"],
			input[type="number"],
			input[type="url"],
			input[type="email"],
			textarea {
				border-radius: var(--luxe-radius) !important;
			}

			<?php if ( 'glass' === $theme ) : ?>
				#adminmenu,
				#adminmenu .wp-submenu {
					backdrop-filter: blur(10px);
					-webkit-backdrop-filter: blur(10px);
				}
			<?php endif; ?>

			<?php if ( $is_compact ) : ?>
				#adminmenu li.menu-top > a,
				.widefat td,
				.widefat th,
				.wp-list-table td,
				.wp-list-table th {
					padding-top: 7px !important;
					padding-bottom: 7px !important;
				}

				.wrap .form-table th,
				.wrap .form-table td {
					padding-top: 9px !important;
					padding-bottom: 9px !important;
				}
			<?php endif; ?>
		</style>
		<?php
	}

	private function get_theme_palette( $theme ) {
		$palettes = [
			'default' => [
				'bg'      => '#f8fafc',
				'sidebar' => '#1d2327',
				'toolbar' => '#1d2327',
				'accent'  => '#2271b1',
				'text'    => '#f0f6fc',
				'border'  => '#d0d7de',
				'card_bg' => '#ffffff',
			],
			'midnight' => [
				'bg'      => '#0f172a',
				'sidebar' => '#1e293b',
				'toolbar' => '#1e293b',
				'accent'  => '#38bdf8',
				'text'    => '#e2e8f0',
				'border'  => '#334155',
				'card_bg' => '#111827',
			],
			'glass' => [
				'bg'      => '#f8fafc',
				'sidebar' => 'rgba(255, 255, 255, 0.72)',
				'toolbar' => '#0f172a',
				'accent'  => '#6366f1',
				'text'    => '#e2e8f0',
				'border'  => '#cbd5e1',
				'card_bg' => 'rgba(255,255,255,0.9)',
			],
			'sandstone' => [
				'bg'      => '#fef9f1',
				'sidebar' => '#92400e',
				'toolbar' => '#78350f',
				'accent'  => '#d97706',
				'text'    => '#ffedd5',
				'border'  => '#f5d0a7',
				'card_bg' => '#fffaf0',
			],
			'emerald' => [
				'bg'      => '#ecfdf5',
				'sidebar' => '#065f46',
				'toolbar' => '#064e3b',
				'accent'  => '#10b981',
				'text'    => '#d1fae5',
				'border'  => '#a7f3d0',
				'card_bg' => '#f0fdf4',
			],
			'sunset' => [
				'bg'      => '#fff7ed',
				'sidebar' => '#7c2d12',
				'toolbar' => '#9a3412',
				'accent'  => '#f97316',
				'text'    => '#ffedd5',
				'border'  => '#fed7aa',
				'card_bg' => '#fffaf5',
			],
			'graphite' => [
				'bg'      => '#111827',
				'sidebar' => '#1f2937',
				'toolbar' => '#111827',
				'accent'  => '#60a5fa',
				'text'    => '#e5e7eb',
				'border'  => '#374151',
				'card_bg' => '#1f2937',
			],
		];

		return isset( $palettes[ $theme ] ) ? $palettes[ $theme ] : $palettes['default'];
	}

	private function get_font_stack( $font ) {
		if ( 'modern' === $font ) {
			return '"Manrope", "Avenir Next", "Segoe UI", sans-serif';
		}

		if ( 'serif' === $font ) {
			return '"Lora", "Iowan Old Style", "Palatino Linotype", serif';
		}

		return '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';
	}

	private function contrast_text_color( $hex ) {
		if ( ! is_string( $hex ) || 0 !== strpos( $hex, '#' ) || 7 !== strlen( $hex ) ) {
			return '#ffffff';
		}

		$r = hexdec( substr( $hex, 1, 2 ) );
		$g = hexdec( substr( $hex, 3, 2 ) );
		$b = hexdec( substr( $hex, 5, 2 ) );

		$luminance = ( 0.299 * $r ) + ( 0.587 * $g ) + ( 0.114 * $b );
		return $luminance > 160 ? '#111111' : '#ffffff';
	}
}
