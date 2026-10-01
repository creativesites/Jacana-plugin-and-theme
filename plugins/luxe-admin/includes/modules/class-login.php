<?php
namespace Luxe_Admin\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Login {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'login_enqueue_scripts', [ $this, 'customize_login_page' ] );
		add_filter( 'login_headerurl', [ $this, 'filter_login_header_url' ] );
		add_filter( 'login_headertext', [ $this, 'filter_login_header_text' ] );
		add_filter( 'login_message', [ $this, 'filter_login_message' ] );
	}

	public function customize_login_page() {
		$settings = \Luxe_Admin\Core\Main::get_settings();
		if ( empty( $settings['login']['enabled'] ) ) {
			return;
		}

		$logo       = $settings['login']['logo'];
		$logo_width = absint( $settings['login']['logo_width'] );
		$logo_width = $logo_width > 0 ? $logo_width : 200;

		?>
		<style type="text/css">
			body.login { background: #fdfdfd; display: flex; align-items: center; justify-content: center; }
			#login { padding: 40px; background: #fff; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); }
			.login h1 a {
				<?php if ( ! empty( $logo ) ) : ?>
				background-image: url('<?php echo esc_url( $logo ); ?>');
				<?php endif; ?>
				background-size: contain;
				background-repeat: no-repeat;
				width: <?php echo esc_html( $logo_width ); ?>px;
				max-width: 100%;
			}
			#loginform { border: none; box-shadow: none; padding: 0; }
			.wp-core-ui .button-primary { background: #111 !important; border: none !important; border-radius: 6px !important; transition: all 0.3s ease; }
			.wp-core-ui .button-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
			.luxe-login-message { border-left-color: #111 !important; }
		</style>
		<?php
	}

	public function filter_login_header_url( $url ) {
		$settings = \Luxe_Admin\Core\Main::get_settings();

		if ( ! empty( $settings['login']['enabled'] ) && ! empty( $settings['login']['url'] ) ) {
			return esc_url( $settings['login']['url'] );
		}

		return $url;
	}

	public function filter_login_header_text( $text ) {
		$settings = \Luxe_Admin\Core\Main::get_settings();

		if ( ! empty( $settings['login']['enabled'] ) && ! empty( $settings['login']['title'] ) ) {
			return sanitize_text_field( $settings['login']['title'] );
		}

		return $text;
	}

	public function filter_login_message( $message ) {
		$settings = \Luxe_Admin\Core\Main::get_settings();

		if ( ! empty( $settings['login']['enabled'] ) && ! empty( $settings['login']['msg'] ) ) {
			$custom_message = '<p class="message luxe-login-message">' . esc_html( $settings['login']['msg'] ) . '</p>';
			return $custom_message . $message;
		}

		return $message;
	}
}
