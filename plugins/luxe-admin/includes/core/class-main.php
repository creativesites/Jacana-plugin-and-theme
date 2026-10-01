<?php
namespace Luxe_Admin\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Luxe_Admin Controller
 */
class Main {
	private static $instance = null;
	private $settings;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->settings = self::get_settings();
		$this->init_modules();
		$this->init_hooks();
	}

	public static function get_settings() {
		$defaults = luxe_admin_default_settings();
		$settings = get_option( 'luxe_admin_settings', [] );
		$settings = is_array( $settings ) ? $settings : [];
		$settings = wp_parse_args( $settings, $defaults );

		$settings['login']       = wp_parse_args( is_array( $settings['login'] ) ? $settings['login'] : [], $defaults['login'] );
		$settings['menu']        = wp_parse_args( is_array( $settings['menu'] ) ? $settings['menu'] : [], $defaults['menu'] );
		$settings['dashboard']   = wp_parse_args( is_array( $settings['dashboard'] ) ? $settings['dashboard'] : [], $defaults['dashboard'] );
		$settings['appearance']  = wp_parse_args( is_array( $settings['appearance'] ) ? $settings['appearance'] : [], $defaults['appearance'] );
		$settings['white_label'] = wp_parse_args( is_array( $settings['white_label'] ) ? $settings['white_label'] : [], $defaults['white_label'] );

		if ( ! is_array( $settings['menu']['hidden'] ) ) {
			$settings['menu']['hidden'] = [];
		}

		return $settings;
	}

	private function init_modules() {
		// Initialize modules
		\Luxe_Admin\Modules\Login::get_instance();
		\Luxe_Admin\Modules\Theme::get_instance();
		\Luxe_Admin\Modules\Menu::get_instance();
		\Luxe_Admin\Modules\Dashboard::get_instance();
		\Luxe_Admin\Modules\Whitelabel::get_instance();
	}

	private function init_hooks() {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'wp_ajax_luxe_save_settings', [ $this, 'handle_save_settings' ] );
	}

	public function handle_save_settings() {
		check_ajax_referer( 'luxe_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$payload = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '{}';
		$incoming = json_decode( $payload, true );
		$settings = $this->sanitize_settings( is_array( $incoming ) ? $incoming : [] );
		$this->settings = $settings;

		if ( update_option( 'luxe_admin_settings', $settings ) ) {
			wp_send_json_success( 'Settings saved' );
		} else {
			// If nothing changed, update_option returns false, but we should still send success
			wp_send_json_success( 'Settings unchanged' );
		}
	}

	private function sanitize_settings( $settings ) {
		$defaults = luxe_admin_default_settings();
		$current  = self::get_settings();
		$clean    = wp_parse_args( $settings, $current );

		$allowed_themes = [ 'default', 'midnight', 'glass', 'sandstone', 'emerald', 'sunset', 'graphite' ];
		$clean_theme = isset( $clean['theme'] ) ? sanitize_key( $clean['theme'] ) : $defaults['theme'];
		$clean['theme'] = in_array( $clean_theme, $allowed_themes, true ) ? $clean_theme : $defaults['theme'];

		$clean['login'] = wp_parse_args( is_array( $clean['login'] ) ? $clean['login'] : [], $defaults['login'] );
		$clean['login']['enabled']    = empty( $clean['login']['enabled'] ) ? 0 : 1;
		$clean['login']['logo']       = esc_url_raw( $clean['login']['logo'] );
		$clean['login']['logo_width'] = absint( $clean['login']['logo_width'] );
		$clean['login']['logo_width'] = $clean['login']['logo_width'] > 0 ? $clean['login']['logo_width'] : 200;
		$clean['login']['msg']        = sanitize_text_field( $clean['login']['msg'] );
		$clean['login']['url']        = esc_url_raw( $clean['login']['url'] );
		$clean['login']['title']      = sanitize_text_field( $clean['login']['title'] );

		$clean['menu'] = wp_parse_args( is_array( $clean['menu'] ) ? $clean['menu'] : [], $defaults['menu'] );
		$clean['menu']['hide_wp_logo']        = empty( $clean['menu']['hide_wp_logo'] ) ? 0 : 1;
		$clean['menu']['hide_help_tabs']      = empty( $clean['menu']['hide_help_tabs'] ) ? 0 : 1;
		$clean['menu']['hide_screen_options'] = empty( $clean['menu']['hide_screen_options'] ) ? 0 : 1;
		$clean['menu']['hidden'] = array_values(
			array_filter(
				array_map(
					'sanitize_text_field',
					is_array( $clean['menu']['hidden'] ) ? $clean['menu']['hidden'] : []
				)
			)
		);

		$clean['dashboard'] = wp_parse_args( is_array( $clean['dashboard'] ) ? $clean['dashboard'] : [], $defaults['dashboard'] );
		$clean['dashboard']['hide_default']       = empty( $clean['dashboard']['hide_default'] ) ? 0 : 1;
		$clean['dashboard']['hide_welcome_panel'] = empty( $clean['dashboard']['hide_welcome_panel'] ) ? 0 : 1;
		$clean['dashboard']['welcome_msg']        = sanitize_textarea_field( $clean['dashboard']['welcome_msg'] );

		$clean['appearance'] = wp_parse_args( is_array( $clean['appearance'] ) ? $clean['appearance'] : [], $defaults['appearance'] );
		$clean['appearance']['custom_palette'] = empty( $clean['appearance']['custom_palette'] ) ? 0 : 1;
		$clean['appearance']['bg']             = sanitize_hex_color( $clean['appearance']['bg'] ) ? sanitize_hex_color( $clean['appearance']['bg'] ) : '';
		$clean['appearance']['sidebar']        = sanitize_hex_color( $clean['appearance']['sidebar'] ) ? sanitize_hex_color( $clean['appearance']['sidebar'] ) : '';
		$clean['appearance']['accent']         = sanitize_hex_color( $clean['appearance']['accent'] ) ? sanitize_hex_color( $clean['appearance']['accent'] ) : '';
		$clean['appearance']['text']           = sanitize_hex_color( $clean['appearance']['text'] ) ? sanitize_hex_color( $clean['appearance']['text'] ) : '';
		$clean['appearance']['card_bg']        = sanitize_hex_color( $clean['appearance']['card_bg'] ) ? sanitize_hex_color( $clean['appearance']['card_bg'] ) : '';
		$allowed_fonts = [ 'system', 'modern', 'serif' ];
		$font = sanitize_key( $clean['appearance']['font'] );
		$clean['appearance']['font'] = in_array( $font, $allowed_fonts, true ) ? $font : 'system';
		$clean['appearance']['radius'] = absint( $clean['appearance']['radius'] );
		$clean['appearance']['radius'] = min( max( $clean['appearance']['radius'], 0 ), 24 );
		$clean['appearance']['compact_mode'] = empty( $clean['appearance']['compact_mode'] ) ? 0 : 1;

		$clean['white_label'] = wp_parse_args( is_array( $clean['white_label'] ) ? $clean['white_label'] : [], $defaults['white_label'] );
		$clean['white_label']['brand_name']            = sanitize_text_field( $clean['white_label']['brand_name'] );
		$clean['white_label']['brand_url']             = esc_url_raw( $clean['white_label']['brand_url'] );
		$clean['white_label']['footer_text']           = sanitize_text_field( $clean['white_label']['footer_text'] );
		$clean['white_label']['disable_admin_notices'] = empty( $clean['white_label']['disable_admin_notices'] ) ? 0 : 1;
		$clean['white_label']['disable_update_nag']    = empty( $clean['white_label']['disable_update_nag'] ) ? 0 : 1;
		$clean['white_label']['hide_wp_version']       = empty( $clean['white_label']['hide_wp_version'] ) ? 0 : 1;

		return $clean;
	}

	public function add_settings_page() {
		add_menu_page(
			__( 'Luxe Admin', 'luxe-admin' ),
			__( 'Luxe Admin', 'luxe-admin' ),
			'manage_options',
			'luxe-admin',
			[ $this, 'render_settings_page' ],
			'dashicons-layout',
			60
		);
	}

	public function render_settings_page() {
		include LUXE_ADMIN_PATH . 'includes/views/settings-page.php';
	}

	public function enqueue_admin_assets( $hook ) {
		// Only load our dashboard CSS everywhere, but specific settings JS only on our page
		wp_enqueue_style( 'luxe-admin-core', LUXE_ADMIN_URL . 'assets/css/luxe-admin-ui.css', [], LUXE_ADMIN_VERSION );
		
		if ( 'toplevel_page_luxe-admin' === $hook ) {
			wp_enqueue_style( 'luxe-admin-settings', LUXE_ADMIN_URL . 'assets/css/luxe-settings.css', [], LUXE_ADMIN_VERSION );
			wp_enqueue_script( 'luxe-admin-settings', LUXE_ADMIN_URL . 'assets/js/luxe-settings.js', [ 'jquery' ], LUXE_ADMIN_VERSION, true );
			
			wp_localize_script( 'luxe-admin-settings', 'luxeAdmin', [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'luxe_admin_nonce' ),
				'settings' => $this->settings
			] );
		}
	}
}
