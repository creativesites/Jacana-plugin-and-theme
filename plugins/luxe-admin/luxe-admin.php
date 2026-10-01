<?php
/**
 * Plugin Name: Luxe Admin - Premium White-Label Dashboard
 * Plugin URI: https://winstonzulu.com/luxe-admin
 * Description: A high-end, editorial-style WordPress admin customization tool for agencies.
 * Version: 1.2.0
 * Author: Winston Zulu
 * Author URI: https://winstonzulu.com
 * License: GPL2
 * Text Domain: luxe-admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants
define( 'LUXE_ADMIN_VERSION', '1.2.0' );
define( 'LUXE_ADMIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'LUXE_ADMIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Default plugin settings.
 *
 * @return array
 */
function luxe_admin_default_settings() {
	return [
		'theme' => 'default',
		'login' => [
			'enabled'    => 0,
			'logo'       => '',
			'logo_width' => 200,
			'msg'        => '',
			'url'        => home_url( '/' ),
			'title'      => get_bloginfo( 'name' ),
		],
		'menu' => [
			'hide_wp_logo'        => 0,
			'hidden'              => [],
			'hide_help_tabs'      => 0,
			'hide_screen_options' => 0,
		],
		'dashboard' => [
			'hide_default'       => 1,
			'hide_welcome_panel' => 0,
			'welcome_msg'        => 'Welcome to your professional dashboard.',
		],
		'appearance' => [
			'custom_palette' => 0,
			'bg'             => '',
			'sidebar'        => '',
			'accent'         => '',
			'text'           => '',
			'card_bg'        => '',
			'font'           => 'system',
			'radius'         => 12,
			'compact_mode'   => 0,
		],
		'white_label' => [
			'brand_name'            => '',
			'brand_url'             => home_url( '/' ),
			'footer_text'           => '',
			'disable_admin_notices' => 0,
			'disable_update_nag'    => 0,
			'hide_wp_version'       => 0,
		],
	];
}

/**
 * Autoloader for Luxe Admin classes.
 */
spl_autoload_register( function ( $class ) {
	$prefix = 'Luxe_Admin\\';
	$base_dir = LUXE_ADMIN_PATH . 'includes/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	
	// Convert namespace to file path
	// Luxe_Admin\Modules\Login -> modules/class-login.php
	$parts = explode( '\\', $relative_class );
	$file = 'class-' . strtolower( end( $parts ) ) . '.php';
	
	array_pop( $parts );
	$sub_path = ! empty( $parts ) ? strtolower( implode( '/', $parts ) ) . '/' : '';
	
	$path = $base_dir . $sub_path . $file;

	if ( file_exists( $path ) ) {
		require $path;
	}
} );

/**
 * Initialize the Plugin
 */
function luxe_admin_init() {
	if ( is_admin() || strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) !== false ) {
		\Luxe_Admin\Core\Main::get_instance();
	}
}
add_action( 'plugins_loaded', 'luxe_admin_init' );

/**
 * Activation Hook
 */
register_activation_hook( __FILE__, function() {
	$defaults = luxe_admin_default_settings();
	$current  = get_option( 'luxe_admin_settings', [] );
	$merged   = wp_parse_args( is_array( $current ) ? $current : [], $defaults );

	$merged['login']       = wp_parse_args( is_array( $merged['login'] ) ? $merged['login'] : [], $defaults['login'] );
	$merged['menu']        = wp_parse_args( is_array( $merged['menu'] ) ? $merged['menu'] : [], $defaults['menu'] );
	$merged['dashboard']   = wp_parse_args( is_array( $merged['dashboard'] ) ? $merged['dashboard'] : [], $defaults['dashboard'] );
	$merged['appearance']  = wp_parse_args( is_array( $merged['appearance'] ) ? $merged['appearance'] : [], $defaults['appearance'] );
	$merged['white_label'] = wp_parse_args( is_array( $merged['white_label'] ) ? $merged['white_label'] : [], $defaults['white_label'] );

	update_option( 'luxe_admin_settings', $merged );
} );
