<?php
namespace Luxe_Admin\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * White-label controls for admin chrome and branding.
 */
class Whitelabel {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_bar_menu', [ $this, 'brand_toolbar' ], 20 );
		add_action( 'in_admin_header', [ $this, 'maybe_disable_admin_notices' ], 1 );
		add_action( 'admin_head', [ $this, 'maybe_disable_update_nag' ], 1 );
		add_action( 'admin_head', [ $this, 'maybe_hide_notice_css' ], 20 );

		add_filter( 'admin_footer_text', [ $this, 'filter_admin_footer_text' ], 99 );
		add_filter( 'update_footer', [ $this, 'filter_update_footer' ], 99 );
	}

	public function brand_toolbar( $wp_admin_bar ) {
		$settings = \Luxe_Admin\Core\Main::get_settings();
		$brand_name = trim( $settings['white_label']['brand_name'] );

		if ( '' === $brand_name ) {
			return;
		}

		$brand_url = ! empty( $settings['white_label']['brand_url'] ) ? $settings['white_label']['brand_url'] : home_url( '/' );

		if ( ! empty( $settings['menu']['hide_wp_logo'] ) ) {
			$wp_admin_bar->add_node(
				[
					'id'    => 'luxe-brand',
					'title' => esc_html( $brand_name ),
					'href'  => esc_url( $brand_url ),
					'meta'  => [ 'class' => 'luxe-brand-node' ],
				]
			);
			return;
		}

		$wp_admin_bar->add_node(
			[
				'id'    => 'wp-logo',
				'title' => esc_html( $brand_name ),
				'href'  => esc_url( $brand_url ),
				'meta'  => [ 'class' => 'luxe-brand-node' ],
			]
		);
	}

	public function maybe_disable_admin_notices() {
		$settings = \Luxe_Admin\Core\Main::get_settings();

		if ( empty( $settings['white_label']['disable_admin_notices'] ) ) {
			return;
		}

		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'network_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
	}

	public function maybe_disable_update_nag() {
		$settings = \Luxe_Admin\Core\Main::get_settings();

		if ( empty( $settings['white_label']['disable_update_nag'] ) ) {
			return;
		}

		remove_action( 'admin_notices', 'update_nag', 3 );
		remove_action( 'network_admin_notices', 'update_nag', 3 );
	}

	public function maybe_hide_notice_css() {
		$settings = \Luxe_Admin\Core\Main::get_settings();

		if ( empty( $settings['white_label']['disable_admin_notices'] ) ) {
			return;
		}
		?>
		<style id="luxe-admin-hide-notices">
			#wpbody-content .notice,
			#wpbody-content .update-nag,
			#wpbody-content .updated,
			#wpbody-content .error {
				display: none !important;
			}
		</style>
		<?php
	}

	public function filter_admin_footer_text( $text ) {
		$settings = \Luxe_Admin\Core\Main::get_settings();
		$footer_text = trim( sanitize_text_field( $settings['white_label']['footer_text'] ) );

		if ( '' !== $footer_text ) {
			return esc_html( $footer_text );
		}

		return $text;
	}

	public function filter_update_footer( $text ) {
		$settings = \Luxe_Admin\Core\Main::get_settings();

		if ( ! empty( $settings['white_label']['hide_wp_version'] ) ) {
			return '';
		}

		return $text;
	}
}
