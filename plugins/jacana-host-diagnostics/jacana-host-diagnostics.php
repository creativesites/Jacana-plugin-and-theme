<?php
/**
 * Plugin Name: Jacana Host Diagnostics
 * Description: Runs targeted hosting, database, and runtime checks to diagnose intermittent 503 and database connection failures.
 * Version: 1.1.0
 * Author: Jacana Safaris & Tours
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Jacana_Host_Diagnostics {
	const OPTION_LAST_REPORT = 'jacana_host_diag_last_report';
	const OPTION_DISABLE_SSL_VERIFY = 'jacana_host_diag_disable_ssl_verify';
	const CAPABILITY = 'manage_options';
	const MAX_LOG_BYTES = 524288; // 512 KB.
	const MAX_LOG_LINES = 2500;

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_post_jacana_host_diag_run', array( $this, 'handle_run' ) );
		add_action( 'admin_post_jacana_host_diag_export', array( $this, 'handle_export' ) );
		add_action( 'admin_post_jacana_host_diag_ssl', array( $this, 'handle_ssl_setting' ) );
		add_filter( 'http_request_args', array( $this, 'maybe_disable_ssl_verify' ), 99, 2 );
	}

	public function register_page() {
		add_management_page(
			__( 'Jacana Host Diagnostics', 'jacana-host-diagnostics' ),
			__( 'Jacana Diagnostics', 'jacana-host-diagnostics' ),
			self::CAPABILITY,
			'jacana-host-diagnostics',
			array( $this, 'render_page' )
		);
	}

	public function handle_run() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to run diagnostics.', 'jacana-host-diagnostics' ) );
		}

		check_admin_referer( 'jacana_host_diag_run' );

		$report = $this->run_diagnostics();
		update_option( self::OPTION_LAST_REPORT, $report, false );

		$redirect = add_query_arg(
			array(
				'page'    => 'jacana-host-diagnostics',
				'ran'     => '1',
				'updated' => time(),
			),
			admin_url( 'tools.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	public function handle_export() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to export diagnostics.', 'jacana-host-diagnostics' ) );
		}

		check_admin_referer( 'jacana_host_diag_export' );

		$report = get_option( self::OPTION_LAST_REPORT, array() );
		if ( empty( $report ) || empty( $report['checks'] ) ) {
			wp_die( esc_html__( 'No diagnostics report found. Run diagnostics first.', 'jacana-host-diagnostics' ) );
		}

		$timestamp = gmdate( 'Ymd-His' );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="jacana-diagnostics-' . $timestamp . '.txt"' );

		echo $this->build_export_text( $report ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	public function handle_ssl_setting() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to change SSL diagnostics settings.', 'jacana-host-diagnostics' ) );
		}

		check_admin_referer( 'jacana_host_diag_ssl' );

		$disable = ! empty( $_POST['disable_ssl_verify'] ) ? 1 : 0;
		update_option( self::OPTION_DISABLE_SSL_VERIFY, $disable, false );

		$redirect = add_query_arg(
			array(
				'page'         => 'jacana-host-diagnostics',
				'ssl_updated'  => '1',
				'ssl_disabled' => (string) $disable,
			),
			admin_url( 'tools.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	public function maybe_disable_ssl_verify( $args, $url ) {
		if ( array_key_exists( 'jacana_diag_force_ssl', $args ) ) {
			$args['sslverify'] = (bool) $args['jacana_diag_force_ssl'];
			unset( $args['jacana_diag_force_ssl'] );
			return $args;
		}

		$disabled = (int) get_option( self::OPTION_DISABLE_SSL_VERIFY, 0 ) === 1;
		if ( ! $disabled ) {
			return $args;
		}

		$parsed = wp_parse_url( (string) $url );
		$scheme = isset( $parsed['scheme'] ) ? strtolower( (string) $parsed['scheme'] ) : '';
		if ( 'https' !== $scheme ) {
			return $args;
		}

		$args['sslverify'] = false;
		return $args;
	}

	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'jacana-host-diagnostics' ) );
		}

		$report = get_option( self::OPTION_LAST_REPORT, array() );
		$ran    = isset( $_GET['ran'] ) ? sanitize_text_field( wp_unslash( $_GET['ran'] ) ) : '';
		$ssl_updated = isset( $_GET['ssl_updated'] ) ? sanitize_text_field( wp_unslash( $_GET['ssl_updated'] ) ) : '';
		$ssl_disabled = (int) get_option( self::OPTION_DISABLE_SSL_VERIFY, 0 ) === 1;

		$summary = array(
			'ok'   => 0,
			'warn' => 0,
			'fail' => 0,
		);

		if ( ! empty( $report['checks'] ) && is_array( $report['checks'] ) ) {
			foreach ( $report['checks'] as $check ) {
				$status = isset( $check['status'] ) ? $check['status'] : 'warn';
				if ( isset( $summary[ $status ] ) ) {
					$summary[ $status ]++;
				}
			}
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Jacana Host Diagnostics', 'jacana-host-diagnostics' ); ?></h1>
			<p><?php esc_html_e( 'Run focused checks for intermittent 503 and database connectivity issues.', 'jacana-host-diagnostics' ); ?></p>

			<?php if ( '1' === $ran ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Diagnostics completed.', 'jacana-host-diagnostics' ); ?></p></div>
			<?php endif; ?>
			<?php if ( '1' === $ssl_updated ) : ?>
				<div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'SSL diagnostics setting updated.', 'jacana-host-diagnostics' ); ?></p></div>
			<?php endif; ?>

			<div style="margin-top:12px;padding:12px;background:#fff;border:1px solid #ccd0d4;max-width:900px;">
				<strong><?php esc_html_e( 'Temporary SSL Override', 'jacana-host-diagnostics' ); ?></strong>
				<p style="margin:8px 0 10px;">
					<?php esc_html_e( 'When enabled, WordPress HTTP requests to HTTPS URLs will skip certificate verification. Use only for temporary diagnostics on staging, then disable it.', 'jacana-host-diagnostics' ); ?>
				</p>
				<p style="margin:0 0 8px;">
					<strong><?php esc_html_e( 'Current status:', 'jacana-host-diagnostics' ); ?></strong>
					<?php echo esc_html( $ssl_disabled ? 'Enabled (insecure temporary mode)' : 'Disabled (normal secure mode)' ); ?>
				</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="jacana_host_diag_ssl">
					<?php wp_nonce_field( 'jacana_host_diag_ssl' ); ?>
					<label>
						<input type="checkbox" name="disable_ssl_verify" value="1" <?php checked( $ssl_disabled ); ?>>
						<?php esc_html_e( 'Disable SSL certificate verification for all WordPress HTTPS HTTP requests (temporary).', 'jacana-host-diagnostics' ); ?>
					</label>
					<p style="margin-top:8px;">
						<button type="submit" class="button"><?php esc_html_e( 'Save SSL Setting', 'jacana-host-diagnostics' ); ?></button>
					</p>
				</form>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px;">
				<input type="hidden" name="action" value="jacana_host_diag_run">
				<?php wp_nonce_field( 'jacana_host_diag_run' ); ?>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Run Diagnostics', 'jacana-host-diagnostics' ); ?></button>
			</form>

			<?php if ( ! empty( $report['checks'] ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:10px;">
					<input type="hidden" name="action" value="jacana_host_diag_export">
					<?php wp_nonce_field( 'jacana_host_diag_export' ); ?>
					<button type="submit" class="button"><?php esc_html_e( 'Export Report (.txt)', 'jacana-host-diagnostics' ); ?></button>
				</form>

				<div style="margin-top:16px;padding:12px;background:#fff;border:1px solid #ccd0d4;max-width:900px;">
					<strong><?php esc_html_e( 'Last Run (UTC):', 'jacana-host-diagnostics' ); ?></strong>
					<?php echo esc_html( isset( $report['generated_at_utc'] ) ? $report['generated_at_utc'] : '-' ); ?>
					&nbsp; | &nbsp;
					<strong><?php esc_html_e( 'OK', 'jacana-host-diagnostics' ); ?>:</strong> <?php echo esc_html( (string) $summary['ok'] ); ?>
					&nbsp; | &nbsp;
					<strong><?php esc_html_e( 'WARN', 'jacana-host-diagnostics' ); ?>:</strong> <?php echo esc_html( (string) $summary['warn'] ); ?>
					&nbsp; | &nbsp;
					<strong><?php esc_html_e( 'FAIL', 'jacana-host-diagnostics' ); ?>:</strong> <?php echo esc_html( (string) $summary['fail'] ); ?>
				</div>

				<table class="widefat striped" style="margin-top:14px;max-width:1200px;">
					<thead>
						<tr>
							<th style="width:90px;"><?php esc_html_e( 'Status', 'jacana-host-diagnostics' ); ?></th>
							<th style="width:260px;"><?php esc_html_e( 'Check', 'jacana-host-diagnostics' ); ?></th>
							<th><?php esc_html_e( 'Details', 'jacana-host-diagnostics' ); ?></th>
							<th><?php esc_html_e( 'Recommendation', 'jacana-host-diagnostics' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $report['checks'] as $check ) : ?>
						<?php
						$status = isset( $check['status'] ) ? $check['status'] : 'warn';
						$label  = strtoupper( $status );
						$color  = '#d63638';
						if ( 'ok' === $status ) {
							$color = '#1d7f2a';
						} elseif ( 'warn' === $status ) {
							$color = '#b26200';
						}
						?>
						<tr>
							<td><strong style="color:<?php echo esc_attr( $color ); ?>;"><?php echo esc_html( $label ); ?></strong></td>
							<td><strong><?php echo esc_html( isset( $check['label'] ) ? $check['label'] : '' ); ?></strong></td>
							<td><?php echo esc_html( isset( $check['details'] ) ? $check['details'] : '' ); ?></td>
							<td><?php echo esc_html( isset( $check['recommendation'] ) ? $check['recommendation'] : '' ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( ! empty( $report['signals'] ) && is_array( $report['signals'] ) ) : ?>
					<h2 style="margin-top:20px;"><?php esc_html_e( 'Recent Error Signals (from debug.log)', 'jacana-host-diagnostics' ); ?></h2>
					<pre style="white-space:pre-wrap;max-height:380px;overflow:auto;background:#fff;border:1px solid #ccd0d4;padding:10px;"><?php echo esc_html( implode( "\n", $report['signals'] ) ); ?></pre>
				<?php endif; ?>
			<?php else : ?>
				<p style="margin-top:14px;"><?php esc_html_e( 'No report yet. Click "Run Diagnostics" to generate one.', 'jacana-host-diagnostics' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private function run_diagnostics() {
		global $wpdb;

		$checks = array();
		$now    = time();

		$checks[] = $this->check_wp_environment_flags();
		$checks[] = $this->check_ssl_override_state();
		$checks[] = $this->check_database_connectivity();
		$checks[] = $this->check_core_tables();
		$checks[] = $this->check_custom_tables();
		$checks[] = $this->check_autoloaded_options_size();
		$checks[] = $this->check_dns_resolution();
		$checks[] = $this->check_tls_certificate_health();
		$checks[] = $this->check_loopback_request();
		$checks[] = $this->check_wp_cron_spawn();
		$checks[] = $this->check_filesystem_and_disk();
		$checks[] = $this->check_php_runtime_limits();
		$checks[] = $this->check_maintenance_mode();
		$checks[] = $this->check_plugin_conflicts();
		$checks[] = $this->check_htaccess_error_document();

		$log_analysis = $this->analyze_debug_log();
		$checks[]     = $this->evaluate_fatal_errors( $log_analysis );
		$checks[]     = $this->evaluate_database_errors( $log_analysis );
		$checks[]     = $this->evaluate_cron_failures( $log_analysis );

		$report = array(
			'generated_at_utc' => gmdate( 'Y-m-d H:i:s', $now ),
			'site'             => array(
				'home_url'   => home_url( '/' ),
				'site_url'   => site_url( '/' ),
				'wp_version' => get_bloginfo( 'version' ),
				'php'        => PHP_VERSION,
				'mysql'      => $this->safe_get_var( 'SELECT VERSION()' ),
				'db_host'    => $this->mask_sensitive( defined( 'DB_HOST' ) ? DB_HOST : '' ),
				'db_name'    => $this->mask_sensitive( defined( 'DB_NAME' ) ? DB_NAME : '' ),
				'db_user'    => $this->mask_sensitive( defined( 'DB_USER' ) ? DB_USER : '' ),
				'ssl_verify_disabled' => (int) get_option( self::OPTION_DISABLE_SSL_VERIFY, 0 ) === 1 ? 'yes' : 'no',
			),
			'checks'           => $checks,
			'signals'          => $log_analysis['signals'],
			'log_window_utc'   => $log_analysis['window'],
			'log_counts'       => $log_analysis['counts'],
		);

		return $report;
	}

	private function check_wp_environment_flags() {
		$home_opt = (string) get_option( 'home', '' );
		$site_opt = (string) get_option( 'siteurl', '' );
		$env      = defined( 'WP_ENVIRONMENT_TYPE' ) ? (string) WP_ENVIRONMENT_TYPE : 'production';
		$issues   = array();
		$status   = 'ok';

		if ( empty( $home_opt ) || empty( $site_opt ) ) {
			$status   = 'fail';
			$issues[] = 'home/siteurl is empty.';
		}

		$home_host = wp_parse_url( $home_opt, PHP_URL_HOST );
		$site_host = wp_parse_url( $site_opt, PHP_URL_HOST );
		if ( $home_host && $site_host && $home_host !== $site_host ) {
			$status   = 'warn';
			$issues[] = 'home and siteurl hosts differ.';
		}

		if ( 'production' === $env && defined( 'DB_USER' ) && 'root' === DB_USER ) {
			$status   = 'fail';
			$issues[] = 'DB_USER is root on production.';
		}

		$details = 'WP_ENVIRONMENT_TYPE=' . $env . '; home=' . $home_opt . '; siteurl=' . $site_opt;
		if ( ! empty( $issues ) ) {
			$details .= ' Issues: ' . implode( ' ', $issues );
		}

		return $this->check_result(
			'wp_environment',
			'WordPress Environment Flags',
			$status,
			$details,
			'Ensure home/siteurl and DB credentials exactly match the GoDaddy staging environment.'
		);
	}

	private function check_ssl_override_state() {
		$disabled = (int) get_option( self::OPTION_DISABLE_SSL_VERIFY, 0 ) === 1;
		if ( $disabled ) {
			return $this->check_result(
				'ssl_override',
				'SSL Verification Override',
				'warn',
				'SSL certificate verification is disabled for WordPress HTTPS HTTP requests.',
				'Keep this enabled only while diagnosing certificate issues. Re-enable verification once SSL is fixed.'
			);
		}

		return $this->check_result(
			'ssl_override',
			'SSL Verification Override',
			'ok',
			'SSL certificate verification is enabled (default secure mode).',
			'None.'
		);
	}

	private function check_dns_resolution() {
		$host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		if ( empty( $host ) ) {
			return $this->check_result(
				'dns_resolution',
				'DNS Resolution',
				'fail',
				'Unable to parse host from home URL.',
				'Verify WordPress home URL configuration.'
			);
		}

		$resolved_ip = gethostbyname( $host );
		if ( $resolved_ip === $host ) {
			return $this->check_result(
				'dns_resolution',
				'DNS Resolution',
				'fail',
				'Host did not resolve via gethostbyname: ' . $host,
				'Fix DNS records or nameserver propagation before further HTTP diagnostics.'
			);
		}

		$records = function_exists( 'dns_get_record' ) ? @dns_get_record( $host, DNS_A + DNS_AAAA + DNS_CNAME ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$record_count = is_array( $records ) ? count( $records ) : 0;
		$status = $record_count > 0 ? 'ok' : 'warn';

		return $this->check_result(
			'dns_resolution',
			'DNS Resolution',
			$status,
			'Host=' . $host . '; resolved_ip=' . $resolved_ip . '; dns_records=' . $record_count . '.',
			'If DNS records are missing or stale, 503/loopback errors can be intermittent during propagation.'
		);
	}

	private function check_tls_certificate_health() {
		$home   = home_url( '/' );
		$scheme = wp_parse_url( $home, PHP_URL_SCHEME );
		$host   = wp_parse_url( $home, PHP_URL_HOST );

		if ( 'https' !== strtolower( (string) $scheme ) || empty( $host ) ) {
			return $this->check_result(
				'tls_certificate',
				'TLS Certificate Health',
				'warn',
				'Home URL is not HTTPS or host could not be parsed.',
				'Use HTTPS with a valid certificate chain on staging and production.'
			);
		}

		if ( ! function_exists( 'stream_socket_client' ) || ! function_exists( 'openssl_x509_parse' ) ) {
			return $this->check_result(
				'tls_certificate',
				'TLS Certificate Health',
				'warn',
				'TLS introspection functions are unavailable in this PHP build.',
				'Use external SSL checks (e.g. SSL Labs) to validate certificate chain and expiration.'
			);
		}

		$context = stream_context_create(
			array(
				'ssl' => array(
					'capture_peer_cert' => true,
					'verify_peer'       => false,
					'verify_peer_name'  => false,
				),
			)
		);
		$socket = @stream_socket_client( // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			'ssl://' . $host . ':443',
			$errno,
			$errstr,
			10,
			STREAM_CLIENT_CONNECT,
			$context
		);

		if ( false === $socket ) {
			return $this->check_result(
				'tls_certificate',
				'TLS Certificate Health',
				'warn',
				'Could not open TLS socket to ' . $host . ':443 (' . $errno . ' ' . $errstr . ').',
				'Check firewall, vhost routing, and whether HTTPS is correctly enabled for the hostname.'
			);
		}

		$params = stream_context_get_params( $socket );
		fclose( $socket );

		if ( empty( $params['options']['ssl']['peer_certificate'] ) ) {
			return $this->check_result(
				'tls_certificate',
				'TLS Certificate Health',
				'warn',
				'Peer certificate could not be captured from TLS handshake.',
				'Check SSL termination configuration on the host.'
			);
		}

		$cert = openssl_x509_parse( $params['options']['ssl']['peer_certificate'] );
		if ( ! is_array( $cert ) ) {
			return $this->check_result(
				'tls_certificate',
				'TLS Certificate Health',
				'warn',
				'Certificate was captured but could not be parsed.',
				'Check the certificate chain and format on the web host.'
			);
		}

		$subject_cn    = isset( $cert['subject']['CN'] ) ? (string) $cert['subject']['CN'] : '(unknown)';
		$issuer_cn     = isset( $cert['issuer']['CN'] ) ? (string) $cert['issuer']['CN'] : '(unknown)';
		$valid_to      = isset( $cert['validTo_time_t'] ) ? (int) $cert['validTo_time_t'] : 0;
		$days_left     = $valid_to > 0 ? (int) floor( ( $valid_to - time() ) / DAY_IN_SECONDS ) : null;
		$self_signed   = ( isset( $cert['issuer'] ) && isset( $cert['subject'] ) && $cert['issuer'] === $cert['subject'] );
		$status        = 'ok';

		if ( $self_signed || ( null !== $days_left && $days_left < 0 ) ) {
			$status = 'fail';
		} elseif ( null !== $days_left && $days_left < 15 ) {
			$status = 'warn';
		}

		$details = 'subject_cn=' . $subject_cn . '; issuer_cn=' . $issuer_cn . '; self_signed=' . ( $self_signed ? 'yes' : 'no' );
		if ( null !== $days_left ) {
			$details .= '; days_to_expiry=' . $days_left;
		}

		return $this->check_result(
			'tls_certificate',
			'TLS Certificate Health',
			$status,
			$details,
			'Install a trusted full certificate chain for the exact hostname used by WordPress.'
		);
	}

	private function check_database_connectivity() {
		global $wpdb;

		$start = microtime( true );
		$value = $wpdb->get_var( 'SELECT 1' );
		$ms    = round( ( microtime( true ) - $start ) * 1000, 2 );

		if ( '1' !== (string) $value ) {
			$error = $wpdb->last_error ? $wpdb->last_error : 'Unknown database error.';
			return $this->check_result(
				'db_connectivity',
				'Database Connectivity',
				'fail',
				'SELECT 1 failed: ' . $error,
				'Verify DB_HOST/DB_NAME/DB_USER/DB_PASSWORD and confirm MySQL is reachable from the web server.'
			);
		}

		$status = 'ok';
		if ( $ms > 250 ) {
			$status = 'warn';
		}

		return $this->check_result(
			'db_connectivity',
			'Database Connectivity',
			$status,
			'SELECT 1 succeeded in ' . $ms . ' ms.',
			'If latency is high, check MySQL load, remote DB network latency, and connection limits.'
		);
	}

	private function check_core_tables() {
		global $wpdb;

		$core_table = $wpdb->prefix . 'options';
		$exists     = $this->table_exists( $core_table );

		if ( ! $exists ) {
			return $this->check_result(
				'core_tables',
				'Core Tables',
				'fail',
				'Missing expected table: ' . $core_table,
				'Confirm table prefix and imported database integrity.'
			);
		}

		return $this->check_result(
			'core_tables',
			'Core Tables',
			'ok',
			'Found expected table: ' . $core_table,
			'None.'
		);
	}

	private function check_custom_tables() {
		global $wpdb;

		$active_plugins = (array) get_option( 'active_plugins', array() );
		$required       = array();

		if ( in_array( 'jacana-crm/jacana-crm.php', $active_plugins, true ) ) {
			$required = array_merge(
				$required,
				array(
					$wpdb->prefix . 'jacana_visitors',
					$wpdb->prefix . 'jacana_sessions',
					$wpdb->prefix . 'jacana_pageviews',
					$wpdb->prefix . 'jacana_leads',
					$wpdb->prefix . 'jacana_chat_messages',
					$wpdb->prefix . 'jacana_events',
					$wpdb->prefix . 'jacana_ai_interactions',
					$wpdb->prefix . 'jacana_reviews',
					$wpdb->prefix . 'jacana_bookings',
				)
			);
		}

		if ( in_array( 'pojo-accessibility/pojo-accessibility.php', $active_plugins, true ) ) {
			$required[] = $wpdb->prefix . 'ea11y_page_scanned';
		}

		$required = array_unique( $required );
		if ( empty( $required ) ) {
			return $this->check_result(
				'custom_tables',
				'Custom Plugin Tables',
				'ok',
				'No custom plugin table requirements detected.',
				'None.'
			);
		}

		$missing = array();
		foreach ( $required as $table ) {
			if ( ! $this->table_exists( $table ) ) {
				$missing[] = $table;
			}
		}

		if ( ! empty( $missing ) ) {
			return $this->check_result(
				'custom_tables',
				'Custom Plugin Tables',
				'fail',
				'Missing tables: ' . implode( ', ', $missing ),
				'Re-run plugin install/upgrade routines or restore missing tables from a known-good backup.'
			);
		}

		return $this->check_result(
			'custom_tables',
			'Custom Plugin Tables',
			'ok',
			'All required custom tables are present.',
			'None.'
		);
	}

	private function check_autoloaded_options_size() {
		global $wpdb;

		$options_table = $wpdb->options;
		$size          = $wpdb->get_var( "SELECT SUM(LENGTH(option_value)) FROM {$options_table} WHERE autoload='yes'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( null === $size ) {
			return $this->check_result(
				'autoload_size',
				'Autoloaded Options Size',
				'warn',
				'Unable to calculate autoloaded options size.',
				'Check database permissions and wp_options table health.'
			);
		}

		$bytes = (int) $size;
		$human = $this->format_bytes( $bytes );
		$status = 'ok';

		if ( $bytes > 10 * 1024 * 1024 ) {
			$status = 'fail';
		} elseif ( $bytes > 5 * 1024 * 1024 ) {
			$status = 'warn';
		}

		return $this->check_result(
			'autoload_size',
			'Autoloaded Options Size',
			$status,
			'Total autoloaded option payload: ' . $human . '.',
			'Keep autoload under ~5 MB by disabling autoload on large non-critical options.'
		);
	}

	private function check_loopback_request() {
		$home       = home_url( '/' );
		$https_url  = add_query_arg( array( 'jacana_diag_probe' => '1' ), $home );
		$http_url   = preg_replace( '#^https://#i', 'http://', $https_url );

		$probe_secure  = $this->run_http_probe( $https_url, true );
		$probe_insecure = $this->run_http_probe( $https_url, false );
		$probe_http    = ( $http_url && $http_url !== $https_url ) ? $this->run_http_probe( $http_url, false ) : null;

		$status = 'ok';
		$details_parts = array(
			'https_sslverify_on=' . $this->format_probe( $probe_secure ),
			'https_sslverify_off=' . $this->format_probe( $probe_insecure ),
		);

		if ( $probe_http ) {
			$details_parts[] = 'http=' . $this->format_probe( $probe_http );
		}

		$secure_ok   = (int) $probe_secure['code'] >= 200 && (int) $probe_secure['code'] < 400;
		$insecure_ok = (int) $probe_insecure['code'] >= 200 && (int) $probe_insecure['code'] < 400;

		if ( ! $secure_ok ) {
			$status = 'fail';
		}

		$secure_error = isset( $probe_secure['error'] ) ? (string) $probe_secure['error'] : '';
		if ( ! $secure_ok && $insecure_ok && false !== stripos( $secure_error, 'SSL certificate problem' ) ) {
			return $this->check_result(
				'loopback',
				'HTTP Loopback Matrix',
				'fail',
				implode( '; ', $details_parts ) . '; likely_cause=certificate trust mismatch',
				'Your 503 symptoms are likely secondary. Install a trusted certificate chain, or keep temporary SSL override enabled only until cert is corrected.'
			);
		}

		if ( $secure_ok && (float) $probe_secure['ms'] > 3500 ) {
			$status = 'warn';
		}

		if ( ! $secure_ok && ! $insecure_ok ) {
			$status = 'fail';
		}

		return $this->check_result(
			'loopback',
			'HTTP Loopback Matrix',
			$status,
			implode( '; ', $details_parts ),
			'If HTTPS fails and HTTP works, focus on SSL/vhost. If both fail, focus on host-level 503 capacity/WAF/upstream errors.'
		);
	}

	private function check_wp_cron_spawn() {
		$url = add_query_arg(
			array(
				'doing_wp_cron' => sprintf( '%.22F', microtime( true ) ),
			),
			site_url( 'wp-cron.php' )
		);

		$probe = $this->run_http_probe( $url, true, 'POST' );
		$ok    = (int) $probe['code'] >= 200 && (int) $probe['code'] < 400;
		$status = $ok ? 'ok' : 'fail';

		if ( ! $ok && ! empty( $probe['error'] ) && false !== stripos( (string) $probe['error'], 'SSL certificate problem' ) ) {
			$status = 'fail';
		}

		return $this->check_result(
			'wp_cron_spawn',
			'WP-Cron Spawn Endpoint',
			$status,
			'wp-cron probe=' . $this->format_probe( $probe ),
			'If this fails, scheduled jobs may back up and increase site instability under load.'
		);
	}

	private function run_http_probe( $url, $sslverify = true, $method = 'GET' ) {
		$start    = microtime( true );
		$response = wp_remote_request(
			$url,
			array(
				'method'      => $method,
				'timeout'     => 12,
				'redirection' => 3,
				'sslverify'   => $sslverify,
				'jacana_diag_force_ssl' => $sslverify,
				'headers'     => array( 'Cache-Control' => 'no-cache' ),
			)
		);
		$ms       = round( ( microtime( true ) - $start ) * 1000, 2 );

		if ( is_wp_error( $response ) ) {
			return array(
				'code'  => 0,
				'ms'    => $ms,
				'error' => $response->get_error_message(),
			);
		}

		return array(
			'code'  => (int) wp_remote_retrieve_response_code( $response ),
			'ms'    => $ms,
			'error' => '',
		);
	}

	private function format_probe( $probe ) {
		$code = isset( $probe['code'] ) ? (int) $probe['code'] : 0;
		$ms   = isset( $probe['ms'] ) ? (float) $probe['ms'] : 0;
		$err  = isset( $probe['error'] ) ? (string) $probe['error'] : '';

		if ( 0 === $code ) {
			return 'error="' . $err . '",time=' . $ms . 'ms';
		}

		return 'code=' . $code . ',time=' . $ms . 'ms';
	}

	private function check_filesystem_and_disk() {
		$wp_content_writable = is_writable( WP_CONTENT_DIR );
		$uploads_dir_data    = wp_upload_dir();
		$uploads_dir         = isset( $uploads_dir_data['basedir'] ) ? $uploads_dir_data['basedir'] : WP_CONTENT_DIR . '/uploads';
		$uploads_writable    = is_dir( $uploads_dir ) ? is_writable( $uploads_dir ) : false;
		$free                = @disk_free_space( ABSPATH ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$status              = 'ok';
		$details             = array();

		$details[] = 'wp-content writable=' . ( $wp_content_writable ? 'yes' : 'no' );
		$details[] = 'uploads writable=' . ( $uploads_writable ? 'yes' : 'no' );

		if ( false !== $free ) {
			$details[] = 'free disk=' . $this->format_bytes( (int) $free );
			if ( $free < 500 * 1024 * 1024 ) {
				$status = 'warn';
			}
			if ( $free < 100 * 1024 * 1024 ) {
				$status = 'fail';
			}
		}

		if ( ! $wp_content_writable || ! $uploads_writable ) {
			$status = 'fail';
		}

		return $this->check_result(
			'filesystem',
			'Filesystem & Disk',
			$status,
			implode( '; ', $details ),
			'Fix permissions and ensure enough free disk. Low disk/permissions often break cron and cache writes.'
		);
	}

	private function check_php_runtime_limits() {
		$memory_limit  = (string) ini_get( 'memory_limit' );
		$max_execution = (int) ini_get( 'max_execution_time' );
		$post_max      = (string) ini_get( 'post_max_size' );
		$upload_max    = (string) ini_get( 'upload_max_filesize' );
		$status        = 'ok';

		$memory_bytes = $this->to_bytes( $memory_limit );
		if ( $memory_bytes > 0 && $memory_bytes < 128 * 1024 * 1024 ) {
			$status = 'warn';
		}
		if ( $max_execution > 0 && $max_execution < 30 ) {
			$status = 'warn';
		}

		$details = sprintf(
			'memory_limit=%s; max_execution_time=%ss; post_max_size=%s; upload_max_filesize=%s',
			$memory_limit,
			(string) $max_execution,
			$post_max,
			$upload_max
		);

		return $this->check_result(
			'php_limits',
			'PHP Runtime Limits',
			$status,
			$details,
			'For Elementor-heavy sites, memory >= 256M and max_execution_time >= 60s is usually safer.'
		);
	}

	private function check_maintenance_mode() {
		$file = ABSPATH . '.maintenance';
		if ( file_exists( $file ) ) {
			return $this->check_result(
				'maintenance_file',
				'Maintenance Mode Flag',
				'fail',
				'Found .maintenance file at site root.',
				'Remove stale .maintenance file if no update is in progress.'
			);
		}

		return $this->check_result(
			'maintenance_file',
			'Maintenance Mode Flag',
			'ok',
			'No .maintenance file found.',
			'None.'
		);
	}

	private function check_plugin_conflicts() {
		$active_plugins = (array) get_option( 'active_plugins', array() );
		$has_el_pro     = in_array( 'elementor-pro/elementor-pro.php', $active_plugins, true );
		$has_nulled     = in_array( 'nulled-elementor-pro-main/elementor-pro.php', $active_plugins, true );

		if ( $has_el_pro && $has_nulled ) {
			return $this->check_result(
				'plugin_conflict',
				'Plugin Conflict Risk',
				'fail',
				'Both official Elementor Pro and nulled Elementor Pro are active.',
				'Deactivate/remove the nulled variant. Dual Pro variants can trigger fatals, update conflicts, and 503 incidents.'
			);
		}

		return $this->check_result(
			'plugin_conflict',
			'Plugin Conflict Risk',
			'ok',
			'No high-risk duplicate Elementor Pro variants detected.',
			'None.'
		);
	}

	private function check_htaccess_error_document() {
		$path = ABSPATH . '.htaccess';
		if ( ! file_exists( $path ) ) {
			return $this->check_result(
				'htaccess_error_document',
				'.htaccess ErrorDocument',
				'warn',
				'.htaccess file not found in site root.',
				'If hosting uses custom 503 handlers, validate ErrorDocument rules in Apache/vhost config.'
			);
		}

		$content = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false !== stripos( $content, 'ErrorDocument 503' ) ) {
			return $this->check_result(
				'htaccess_error_document',
				'.htaccess ErrorDocument',
				'ok',
				'Found ErrorDocument 503 directive in .htaccess.',
				'Ensure the target file exists and is accessible without PHP dependencies.'
			);
		}

		return $this->check_result(
			'htaccess_error_document',
			'.htaccess ErrorDocument',
			'warn',
			'No ErrorDocument 503 directive found in .htaccess.',
			'If you still see ErrorDocument-related 503 messages, the handler is likely defined at host/vhost level.'
		);
	}

	private function analyze_debug_log() {
		$path        = WP_CONTENT_DIR . '/debug.log';
		$window_from = time() - DAY_IN_SECONDS;
		$counts      = array(
			'fatal_24h'        => 0,
			'db_error_24h'     => 0,
			'lock_wait_24h'    => 0,
			'cron_could_24h'   => 0,
			'elementor_403_24h'=> 0,
		);
		$signals     = array();

		if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
			$signals[] = 'debug.log is missing or unreadable.';

			return array(
				'counts'  => $counts,
				'signals' => $signals,
				'window'  => gmdate( 'Y-m-d H:i:s', $window_from ) . ' to ' . gmdate( 'Y-m-d H:i:s' ),
			);
		}

		$lines = $this->tail_lines( $path, self::MAX_LOG_BYTES, self::MAX_LOG_LINES );
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}

			$timestamp = $this->extract_log_timestamp( $line );
			if ( $timestamp && $timestamp < $window_from ) {
				continue;
			}

			if ( false !== stripos( $line, 'PHP Fatal error' ) || false !== stripos( $line, 'Uncaught ' ) ) {
				$counts['fatal_24h']++;
				$this->append_signal( $signals, $line );
			}

			if ( false !== stripos( $line, 'WordPress database error' ) ) {
				$counts['db_error_24h']++;
				$this->append_signal( $signals, $line );
			}

			if ( false !== stripos( $line, 'Lock wait timeout exceeded' ) ) {
				$counts['lock_wait_24h']++;
				$this->append_signal( $signals, $line );
			}

			if ( false !== stripos( $line, 'Cron reschedule event error' ) && false !== stripos( $line, 'could_not_set' ) ) {
				$counts['cron_could_24h']++;
				$this->append_signal( $signals, $line );
			}

			if ( false !== stripos( $line, 'elementor/modules/cloud-library/module.php' ) && false !== stripos( $line, '403 Forbidden' ) ) {
				$counts['elementor_403_24h']++;
				$this->append_signal( $signals, $line );
			}
		}

		return array(
			'counts'  => $counts,
			'signals' => $signals,
			'window'  => gmdate( 'Y-m-d H:i:s', $window_from ) . ' to ' . gmdate( 'Y-m-d H:i:s' ),
		);
	}

	private function evaluate_fatal_errors( $log_analysis ) {
		$counts        = isset( $log_analysis['counts'] ) ? $log_analysis['counts'] : array();
		$fatal_count   = isset( $counts['fatal_24h'] ) ? (int) $counts['fatal_24h'] : 0;
		$elementor_403 = isset( $counts['elementor_403_24h'] ) ? (int) $counts['elementor_403_24h'] : 0;

		$status = 'ok';
		if ( $fatal_count > 0 ) {
			$status = 'fail';
		}

		$details = 'Fatal/uncaught errors in last 24h: ' . $fatal_count . '.';
		if ( $elementor_403 > 0 ) {
			$details .= ' Elementor cloud-library 403 fatals: ' . $elementor_403 . '.';
		}

		return $this->check_result(
			'log_fatal_errors',
			'Recent Fatal Errors',
			$status,
			$details,
			'Address fatal stack traces first; recurring fatals are a direct cause of 503 responses.'
		);
	}

	private function evaluate_database_errors( $log_analysis ) {
		$counts      = isset( $log_analysis['counts'] ) ? $log_analysis['counts'] : array();
		$db_errors   = isset( $counts['db_error_24h'] ) ? (int) $counts['db_error_24h'] : 0;
		$lock_waits  = isset( $counts['lock_wait_24h'] ) ? (int) $counts['lock_wait_24h'] : 0;
		$status      = 'ok';

		if ( $db_errors > 0 ) {
			$status = 'warn';
		}
		if ( $db_errors > 10 || $lock_waits > 0 ) {
			$status = 'fail';
		}

		return $this->check_result(
			'log_database_errors',
			'Recent Database Errors',
			$status,
			'DB errors in last 24h: ' . $db_errors . '; lock waits: ' . $lock_waits . '.',
			'Investigate lock contention and missing custom tables; both can cascade into 503 under load.'
		);
	}

	private function evaluate_cron_failures( $log_analysis ) {
		$counts = isset( $log_analysis['counts'] ) ? $log_analysis['counts'] : array();
		$cron   = isset( $counts['cron_could_24h'] ) ? (int) $counts['cron_could_24h'] : 0;
		$status = $cron > 0 ? 'fail' : 'ok';

		return $this->check_result(
			'log_cron_failures',
			'Recent Cron Persistence Failures',
			$status,
			'Cron could_not_set events in last 24h: ' . $cron . '.',
			'This usually indicates DB write/locking problems. Fix database stability first, then cron health.'
		);
	}

	private function check_result( $id, $label, $status, $details, $recommendation ) {
		return array(
			'id'             => (string) $id,
			'label'          => (string) $label,
			'status'         => in_array( $status, array( 'ok', 'warn', 'fail' ), true ) ? $status : 'warn',
			'details'        => (string) $details,
			'recommendation' => (string) $recommendation,
		);
	}

	private function table_exists( $table_name ) {
		global $wpdb;
		$sql = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );
		return ( $wpdb->get_var( $sql ) === $table_name );
	}

	private function safe_get_var( $sql ) {
		global $wpdb;
		$value = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return null !== $value ? (string) $value : '';
	}

	private function build_export_text( $report ) {
		$lines   = array();
		$lines[] = 'Jacana Host Diagnostics Report';
		$lines[] = 'Generated (UTC): ' . ( isset( $report['generated_at_utc'] ) ? $report['generated_at_utc'] : '-' );
		$lines[] = '';

		if ( ! empty( $report['site'] ) && is_array( $report['site'] ) ) {
			$lines[] = 'Environment:';
			foreach ( $report['site'] as $key => $value ) {
				$lines[] = '- ' . $key . ': ' . $value;
			}
			$lines[] = '';
		}

		$lines[] = 'Checks:';
		if ( ! empty( $report['checks'] ) && is_array( $report['checks'] ) ) {
			foreach ( $report['checks'] as $check ) {
				$lines[] = sprintf(
					'[%s] %s',
					strtoupper( isset( $check['status'] ) ? $check['status'] : 'warn' ),
					isset( $check['label'] ) ? $check['label'] : 'Unknown Check'
				);
				$lines[] = '  Details: ' . ( isset( $check['details'] ) ? $check['details'] : '' );
				$lines[] = '  Recommendation: ' . ( isset( $check['recommendation'] ) ? $check['recommendation'] : '' );
			}
		}

		if ( ! empty( $report['signals'] ) && is_array( $report['signals'] ) ) {
			$lines[] = '';
			$lines[] = 'Recent Error Signals:';
			foreach ( $report['signals'] as $signal ) {
				$lines[] = '- ' . $signal;
			}
		}

		$lines[] = '';
		$lines[] = 'End of report.';

		return implode( "\n", $lines ) . "\n";
	}

	private function mask_sensitive( $value ) {
		$value = (string) $value;
		if ( '' === $value ) {
			return '';
		}

		$len = strlen( $value );
		if ( $len <= 2 ) {
			return str_repeat( '*', $len );
		}

		return substr( $value, 0, 1 ) . str_repeat( '*', $len - 2 ) . substr( $value, -1 );
	}

	private function format_bytes( $bytes ) {
		$bytes = max( 0, (int) $bytes );
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		$index = 0;
		while ( $bytes >= 1024 && $index < ( count( $units ) - 1 ) ) {
			$bytes /= 1024;
			$index++;
		}
		return round( $bytes, 2 ) . ' ' . $units[ $index ];
	}

	private function to_bytes( $size ) {
		$size = trim( (string) $size );
		if ( '' === $size ) {
			return 0;
		}

		$unit  = strtolower( substr( $size, -1 ) );
		$value = (float) $size;

		switch ( $unit ) {
			case 'g':
				$value *= 1024;
				// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
			case 'm':
				$value *= 1024;
				// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
			case 'k':
				$value *= 1024;
		}

		return (int) $value;
	}

	private function tail_lines( $path, $max_bytes, $max_lines ) {
		$size = filesize( $path );
		if ( false === $size ) {
			return array();
		}

		$fp = fopen( $path, 'rb' );
		if ( false === $fp ) {
			return array();
		}

		$read_bytes = min( (int) $size, (int) $max_bytes );
		if ( $size > $read_bytes ) {
			fseek( $fp, $size - $read_bytes );
		}

		$buffer = stream_get_contents( $fp );
		fclose( $fp );

		if ( false === $buffer || '' === $buffer ) {
			return array();
		}

		$lines = preg_split( "/\r\n|\n|\r/", $buffer );
		if ( ! is_array( $lines ) ) {
			return array();
		}

		if ( count( $lines ) > $max_lines ) {
			$lines = array_slice( $lines, -1 * $max_lines );
		}

		return $lines;
	}

	private function extract_log_timestamp( $line ) {
		if ( ! preg_match( '/^\[([0-9]{2}-[A-Za-z]{3}-[0-9]{4}\s+[0-9:]{8}\s+UTC)\]/', $line, $matches ) ) {
			return 0;
		}

		$timestamp = strtotime( $matches[1] );
		return $timestamp ? (int) $timestamp : 0;
	}

	private function append_signal( &$signals, $line ) {
		if ( count( $signals ) >= 80 ) {
			return;
		}

		$signals[] = $line;
	}
}

Jacana_Host_Diagnostics::instance();
