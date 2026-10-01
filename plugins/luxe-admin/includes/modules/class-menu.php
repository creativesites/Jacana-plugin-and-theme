<?php
namespace Luxe_Admin\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles granular control over Admin Menus and the Toolbar.
 */
class Menu {
	private static $instance = null;
	private $hide_screen_options = false;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'modify_admin_menu' ], 999 );
		add_action( 'admin_bar_menu', [ $this, 'modify_toolbar' ], 999 );
		add_action( 'current_screen', [ $this, 'maybe_hide_screen_tabs' ] );
		add_filter( 'screen_options_show_screen', [ $this, 'filter_screen_options_visibility' ], 10, 2 );
	}

	public function modify_admin_menu() {
		global $menu;
		$settings = \Luxe_Admin\Core\Main::get_settings();
		$hidden_items = $settings['menu']['hidden'];

		if ( empty( $hidden_items ) ) {
			return;
		}

		// Hide selected menus for users without site-management capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			foreach ( $menu as $key => $item ) {
				if ( in_array( $item[2], $hidden_items, true ) ) {
					unset( $menu[$key] );
				}
			}
		}
	}

	public function modify_toolbar( $wp_admin_bar ) {
		$settings = \Luxe_Admin\Core\Main::get_settings();
		if ( ! empty( $settings['menu']['hide_wp_logo'] ) ) {
			$wp_admin_bar->remove_node( 'wp-logo' );
		}
	}

	public function maybe_hide_screen_tabs( $screen ) {
		$settings = \Luxe_Admin\Core\Main::get_settings();

		if ( ! empty( $settings['menu']['hide_help_tabs'] ) && is_object( $screen ) && method_exists( $screen, 'remove_help_tabs' ) ) {
			$screen->remove_help_tabs();
		}

		$this->hide_screen_options = ! empty( $settings['menu']['hide_screen_options'] );
	}

	public function filter_screen_options_visibility( $show, $screen ) {
		if ( $this->hide_screen_options ) {
			return false;
		}

		return $show;
	}
}
