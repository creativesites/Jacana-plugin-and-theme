<?php
/**
 * Plugin Name: Jacana Speed Doctor
 * Description: Diagnoses page-by-page performance, audits videos and images, and applies targeted optimisations for the Jacana Safaris & Tours site.
 * Version:     2.1.0
 * Author:      Jacana Safaris & Tours
 */

defined( 'ABSPATH' ) || exit;

define( 'JSD_VERSION', '2.1.0' );
define( 'JSD_DIR',     plugin_dir_path( __FILE__ ) );
define( 'JSD_URL',     plugin_dir_url( __FILE__ ) );

require_once JSD_DIR . 'includes/class-optimizations.php';

// Apply saved optimisations on every request.
Jacana_Speed_Optimizations::boot( get_option( 'jsd_optimizations', [] ) );

// ── WP-Cron bypass for speed-test requests ────────────────────────────────────
// WP Cron is disabled for requests carrying our secret token so that local
// PHP-worker exhaustion (the main cause of page-test timeouts) cannot occur.
add_action( 'plugins_loaded', function () {
	if ( empty( $_GET['jsd_nc'] ) ) return; // phpcs:ignore
	$stored = get_transient( 'jsd_nc_secret' );
	if ( $stored && hash_equals( $stored, sanitize_text_field( wp_unslash( $_GET['jsd_nc'] ) ) ) ) {
		remove_action( 'wp_loaded', 'wp_cron' );
		// Suppress canonical redirects so the extra query param is transparent.
		add_filter( 'redirect_canonical', '__return_false' );
	}
}, 1 );

// ── Admin menu + assets ───────────────────────────────────────────────────────

add_action( 'admin_menu', function () {
	add_menu_page( 'Speed Doctor', 'Speed Doctor', 'manage_options',
		'jacana-speed-doctor', 'jsd_render_page', 'dashicons-performance', 81 );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'toplevel_page_jacana-speed-doctor' !== $hook ) return;
	wp_enqueue_style( 'jsd-admin', JSD_URL . 'assets/admin.css', [], JSD_VERSION );
} );

// ── Shared page tester ────────────────────────────────────────────────────────

function jsd_fetch_page( string $url ): array {
	// Attach a short-lived secret so the early plugins_loaded hook can disable
	// WP Cron for this request — prevents worker-pool deadlocks on Local.
	$secret = get_transient( 'jsd_nc_secret' );
	if ( ! $secret ) {
		$secret = wp_generate_password( 20, false );
		set_transient( 'jsd_nc_secret', $secret, 120 );
	}
	$test_url = add_query_arg( 'jsd_nc', $secret, $url );

	$start    = microtime( true );
	$response = wp_remote_get( $test_url, [
		'timeout'    => 25,
		'sslverify'  => false,
		'user-agent' => 'JacanaSpeedDoctor/2.1',
		'headers'    => [ 'Cache-Control' => 'no-cache, no-store' ],
	] );
	$elapsed  = microtime( true ) - $start;

	if ( is_wp_error( $response ) ) {
		$msg        = $response->get_error_message();
		$is_timeout = ( false !== strpos( $msg, 'timed out' ) || false !== strpos( $msg, 'error 28' ) );
		return [ 'error' => $msg, 'is_timeout' => $is_timeout ];
	}

	$body    = wp_remote_retrieve_body( $response );
	$code    = wp_remote_retrieve_response_code( $response );
	$headers = wp_remote_retrieve_headers( $response );
	$size_kb = round( strlen( $body ) / 1024, 1 );

	$head_html = '';
	$head_pos  = strpos( $body, '</head>' );
	if ( $head_pos ) $head_html = substr( $body, 0, $head_pos );

	$scripts         = substr_count( $body, '<script' );
	$styles          = substr_count( $body, '<link' );
	$images          = substr_count( $body, '<img' );
	$lazy_images     = substr_count( $body, 'loading="lazy"' ) + substr_count( $body, "loading='lazy'" );
	$render_blocking = max( 0, substr_count( $head_html, '<script' )
		- substr_count( $head_html, 'defer' )
		- substr_count( $head_html, 'async' ) );

	// Video detection.
	$has_local_video = (bool) preg_match( '/<video[^>]+src=["\'][^"\']*\/uploads\/[^"\']+/i', $body );
	$has_mov_video   = (bool) preg_match( '/<video[^>]+src=["\'][^"\']*\.mov["\']|data-video-src=["\'][^"\']*\.mov["\']/i', $body );
	$has_large_mp4   = (bool) preg_match( '/<video[^>]+src=["\'][^"\']*\.(mp4|webm)["\']|data-video-src=["\'][^"\']*\.(mp4|webm)["\']/i', $body );

	$encoding  = (string) ( $headers->offsetGet( 'content-encoding' ) ?? '' );
	$gzip      = ( false !== stripos( $encoding, 'gzip' ) || false !== stripos( $encoding, 'br' ) );
	$time_ms   = round( $elapsed * 1000 );
	$ttfb_band = $time_ms > 3000 ? 'error' : ( $time_ms > 1500 ? 'warn' : 'ok' );

	return compact(
		'time_ms', 'ttfb_band', 'size_kb', 'scripts', 'styles',
		'images', 'lazy_images', 'render_blocking', 'gzip', 'encoding',
		'has_local_video', 'has_mov_video', 'has_large_mp4'
	) + [ 'code' => $code, 'http_code' => $code ];
}

// ── AJAX: homepage speed test ─────────────────────────────────────────────────

add_action( 'wp_ajax_jsd_speed_test', function () {
	check_ajax_referer( 'jsd_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised' );
	$result = jsd_fetch_page( home_url( '/' ) );
	isset( $result['error'] ) ? wp_send_json_error( $result['error'] ) : wp_send_json_success( $result );
} );

// ── AJAX: list published pages ────────────────────────────────────────────────

add_action( 'wp_ajax_jsd_get_pages', function () {
	check_ajax_referer( 'jsd_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised' );

	$ids   = get_posts( [ 'post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ] );
	$pages = array_map( fn( $id ) => [
		'id'    => $id,
		'title' => get_the_title( $id ),
		'url'   => get_permalink( $id ),
	], $ids );

	wp_send_json_success( $pages );
} );

// ── AJAX: test a single page URL ──────────────────────────────────────────────

add_action( 'wp_ajax_jsd_test_page', function () {
	check_ajax_referer( 'jsd_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised' );

	$url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';
	if ( ! $url ) wp_send_json_error( 'No URL' );

	$result = jsd_fetch_page( $url );
	isset( $result['error'] ) ? wp_send_json_error( $result['error'] ) : wp_send_json_success( $result );
} );

// ── AJAX: media scan (videos + large images) ──────────────────────────────────

add_action( 'wp_ajax_jsd_scan_media', function () {
	check_ajax_referer( 'jsd_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised' );

	@set_time_limit( 60 );

	$upload   = wp_upload_dir();
	$base_dir = $upload['basedir'];
	$base_url = $upload['baseurl'];

	$videos     = [];
	$large_imgs = [];
	$vid_exts   = [ 'mp4', 'mov', 'webm', 'avi', 'mkv', 'm4v' ];
	$img_exts   = [ 'jpg', 'jpeg', 'png', 'gif', 'webp' ];

	try {
		$iter = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $base_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);
		$iter->setMaxDepth( 5 );

		foreach ( $iter as $file ) {
			if ( ! $file->isFile() ) continue;
			$ext  = strtolower( $file->getExtension() );
			$size = $file->getSize();
			$rel  = str_replace( $base_dir, '', $file->getPathname() );
			$url  = $base_url . $rel;

			if ( in_array( $ext, $vid_exts, true ) ) {
				$bad_format = in_array( $ext, [ 'mov', 'avi', 'mkv', 'm4v' ], true );
				$oversized  = $size > 15 * 1024 * 1024; // > 15 MB
				$videos[]   = [
					'name'       => $file->getFilename(),
					'size_mb'    => round( $size / ( 1024 * 1024 ), 1 ),
					'ext'        => $ext,
					'url'        => $url,
					'bad_format' => $bad_format,
					'oversized'  => $oversized,
					'critical'   => $bad_format || $oversized,
				];
			} elseif ( in_array( $ext, $img_exts, true ) && $size > 400 * 1024 ) { // > 400 KB
				$large_imgs[] = [
					'name'    => $file->getFilename(),
					'size_kb' => round( $size / 1024 ),
					'ext'     => $ext,
					'url'     => $url,
				];
			}
		}
	} catch ( \Exception $e ) {
		wp_send_json_error( 'Scan failed: ' . $e->getMessage() );
	}

	usort( $videos,     fn( $a, $b ) => $b['size_mb'] <=> $a['size_mb'] );
	usort( $large_imgs, fn( $a, $b ) => $b['size_kb'] <=> $a['size_kb'] );

	$total_video_mb = round( array_sum( array_column( $videos, 'size_mb' ) ), 1 );
	$critical_count = count( array_filter( $videos, fn( $v ) => $v['critical'] ) );

	wp_send_json_success( [
		'videos'          => $videos,
		'large_images'    => array_slice( $large_imgs, 0, 60 ),
		'total_video_mb'  => $total_video_mb,
		'critical_videos' => $critical_count,
		'large_img_count' => count( $large_imgs ),
		'large_img_mb'    => round( array_sum( array_column( $large_imgs, 'size_kb' ) ) / 1024, 1 ),
	] );
} );

// ── AJAX: save optimisation toggles ──────────────────────────────────────────

add_action( 'wp_ajax_jsd_save_opts', function () {
	check_ajax_referer( 'jsd_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised' );

	$opts   = [];
	$posted = ( isset( $_POST['opts'] ) && is_array( $_POST['opts'] ) ) ? $_POST['opts'] : [];
	foreach ( Jacana_Speed_Optimizations::OPTS as $key ) {
		$opts[ $key ] = ! empty( $posted[ $key ] );
	}
	update_option( 'jsd_optimizations', $opts );
	// Always clear CSS cache when settings change.
	Jacana_Speed_Optimizations::clear_css_cache();
	wp_send_json_success( 'Saved' );
} );

// ── AJAX: bulk image optimiser ────────────────────────────────────────────────

add_action( 'wp_ajax_jsd_optimize_image', function () {
	check_ajax_referer( 'jsd_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised' );

	$img_url  = isset( $_POST['img_url'] ) ? esc_url_raw( wp_unslash( $_POST['img_url'] ) ) : '';
	$quality  = isset( $_POST['quality'] ) ? max( 60, min( 95, absint( $_POST['quality'] ) ) ) : 82;
	$make_webp = ! empty( $_POST['make_webp'] );

	if ( ! $img_url ) wp_send_json_error( 'No URL' );

	// Resolve URL → absolute path (must be within uploads).
	$upload     = wp_upload_dir();
	$url_clean  = preg_replace( '#^https?://#', '//', $img_url );
	$base_clean = preg_replace( '#^https?://#', '//', $upload['baseurl'] );
	if ( strpos( $url_clean, $base_clean ) !== 0 ) {
		wp_send_json_error( 'Path not within uploads' );
	}
	$rel_path = substr( $url_clean, strlen( $base_clean ) );
	$abs_path = $upload['basedir'] . $rel_path;
	$real     = realpath( $abs_path );
	$real_base = realpath( $upload['basedir'] );
	if ( ! $real || ! $real_base || strpos( $real, $real_base ) !== 0 ) {
		wp_send_json_error( 'Invalid path' );
	}

	$ext = strtolower( pathinfo( $real, PATHINFO_EXTENSION ) );
	if ( ! in_array( $ext, [ 'jpg', 'jpeg', 'png' ], true ) ) {
		wp_send_json_error( 'Unsupported format: .' . $ext );
	}

	$orig_size = filesize( $real );
	$webp_info = null;

	if ( extension_loaded( 'imagick' ) ) {
		try {
			$img = new \Imagick( $real );
			$img->stripImage();
			if ( in_array( $ext, [ 'jpg', 'jpeg' ], true ) ) {
				$img->setImageCompression( \Imagick::COMPRESSION_JPEG );
				$img->setImageCompressionQuality( $quality );
				$img->setInterlaceScheme( \Imagick::INTERLACE_JPEG );
			} else {
				$img->setImageCompressionQuality( 85 );
			}
			$img->writeImage( $real );
			if ( $make_webp ) {
				$wp = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $real );
				$wi = clone $img;
				$wi->setImageFormat( 'webp' );
				$wi->setImageCompressionQuality( 82 );
				$wi->writeImage( $wp );
				$wi->destroy();
				$webp_info = [ 'size_kb' => round( filesize( $wp ) / 1024 ) ];
			}
			$img->destroy();
		} catch ( \Exception $e ) {
			wp_send_json_error( 'Imagick: ' . $e->getMessage() );
		}
	} elseif ( extension_loaded( 'gd' ) ) {
		if ( in_array( $ext, [ 'jpg', 'jpeg' ], true ) ) {
			$img = @imagecreatefromjpeg( $real );
			if ( ! $img ) wp_send_json_error( 'GD could not open file' );
			imageinterlace( $img, 1 );
			imagejpeg( $img, $real, $quality );
			if ( $make_webp && function_exists( 'imagewebp' ) ) {
				$wp = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $real );
				imagewebp( $img, $wp, 82 );
				$webp_info = [ 'size_kb' => round( filesize( $wp ) / 1024 ) ];
			}
			imagedestroy( $img );
		} elseif ( $ext === 'png' ) {
			$img = @imagecreatefrompng( $real );
			if ( ! $img ) wp_send_json_error( 'GD could not open file' );
			imagepng( $img, $real, 7 );
			if ( $make_webp && function_exists( 'imagewebp' ) ) {
				$wp = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $real );
				imagewebp( $img, $wp, 82 );
				$webp_info = [ 'size_kb' => round( filesize( $wp ) / 1024 ) ];
			}
			imagedestroy( $img );
		}
	} else {
		wp_send_json_error( 'No image library (need Imagick or GD)' );
	}

	clearstatcache( true, $real );
	$new_size = filesize( $real );
	$saved    = $orig_size - $new_size;

	wp_send_json_success( [
		'original_kb' => round( $orig_size / 1024 ),
		'new_kb'      => round( $new_size / 1024 ),
		'saved_kb'    => round( $saved / 1024 ),
		'saved_pct'   => $orig_size > 0 ? round( ( $saved / $orig_size ) * 100 ) : 0,
		'webp'        => $webp_info,
	] );
} );

// ── AJAX: write DISABLE_WP_CRON to wp-config ──────────────────────────────────

add_action( 'wp_ajax_jsd_disable_wpcron', function () {
	check_ajax_referer( 'jsd_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised' );

	$config = ABSPATH . 'wp-config.php';
	if ( ! file_exists( $config ) ) wp_send_json_error( 'wp-config.php not found' );

	$content = file_get_contents( $config );
	if ( false !== strpos( $content, 'DISABLE_WP_CRON' ) ) {
		wp_send_json_success( 'DISABLE_WP_CRON is already defined in wp-config.php' );
	}

	// Insert after the opening <?php line.
	$insert  = "\ndefine( 'DISABLE_WP_CRON', true ); // Added by Jacana Speed Doctor\n";
	$content = preg_replace( '/^<\?php\s*/i', "<?php\n" . $insert, $content, 1 );
	file_put_contents( $config, $content )
		? wp_send_json_success( 'Added DISABLE_WP_CRON to wp-config.php. Set up a real cron job or WP CLI cron to run scheduled tasks.' )
		: wp_send_json_error( 'Could not write to wp-config.php — check file permissions' );
} );

// ── AJAX: clear combined CSS cache ────────────────────────────────────────────

add_action( 'wp_ajax_jsd_clear_css_cache', function () {
	check_ajax_referer( 'jsd_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised' );
	wp_send_json_success( Jacana_Speed_Optimizations::clear_css_cache() ? 'Cache cleared' : 'Could not delete all files' );
} );

// ── AJAX: database cleanup ────────────────────────────────────────────────────

add_action( 'wp_ajax_jsd_db_cleanup', function () {
	check_ajax_referer( 'jsd_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised' );

	global $wpdb;
	$log = [];

	$n = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d", '_transient_timeout_%', time() ) );
	$wpdb->query( "DELETE o FROM {$wpdb->options} o LEFT JOIN {$wpdb->options} t ON t.option_name = REPLACE(o.option_name,'_transient_','_transient_timeout_') WHERE o.option_name LIKE '_transient_%' AND o.option_name NOT LIKE '_transient_timeout_%' AND t.option_id IS NULL" );
	$log[] = "Removed {$n} expired transient(s)";

	$n = $wpdb->query( "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'" );
	$log[] = "Deleted {$n} spam comment(s)";

	$n = $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'" );
	$wpdb->query( "DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.ID IS NULL" );
	$log[] = "Deleted {$n} revision(s) + orphaned postmeta";

	$n = $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'" );
	$log[] = "Deleted {$n} auto-draft(s)";

	$n = $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_status = 'trash' AND post_modified < DATE_SUB(NOW(), INTERVAL 30 DAY)" );
	$log[] = "Deleted {$n} trashed post(s) older than 30 days";

	$tables     = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%'" );
	$table_list = implode( ', ', array_map( fn( $t ) => "`{$t}`", $tables ) );
	if ( $table_list ) {
		$wpdb->query( "OPTIMIZE TABLE {$table_list}" ); // phpcs:ignore
		$log[] = 'Optimised ' . count( $tables ) . ' table(s)';
	}

	wp_send_json_success( implode( "\n", $log ) );
} );

// ── AJAX: write .htaccess performance rules ───────────────────────────────────

add_action( 'wp_ajax_jsd_htaccess_write', function () {
	check_ajax_referer( 'jsd_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised' );

	$file    = ABSPATH . '.htaccess';
	$current = file_exists( $file ) ? file_get_contents( $file ) : '';

	// Remove existing block if present.
	$current = preg_replace( '/\n?# BEGIN Jacana Speed Doctor.*?# END Jacana Speed Doctor\n?/s', '', $current );

	$rules = '
# BEGIN Jacana Speed Doctor
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg        "access plus 1 year"
    ExpiresByType image/jpg         "access plus 1 year"
    ExpiresByType image/png         "access plus 1 year"
    ExpiresByType image/webp        "access plus 1 year"
    ExpiresByType image/gif         "access plus 1 year"
    ExpiresByType image/svg+xml     "access plus 1 year"
    ExpiresByType video/mp4         "access plus 1 year"
    ExpiresByType video/webm        "access plus 1 year"
    ExpiresByType video/quicktime   "access plus 1 year"
    ExpiresByType text/css          "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType font/woff2        "access plus 1 year"
    ExpiresByType font/woff         "access plus 1 year"
    ExpiresByType font/ttf          "access plus 1 year"
</IfModule>
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css application/javascript text/plain application/json application/xml
</IfModule>
<IfModule mod_headers.c>
    <FilesMatch "\.(jpg|jpeg|png|gif|webp|ico|svg|mp4|webm|mov|woff|woff2|ttf|css|js)$">
        Header set Cache-Control "public, max-age=31536000"
    </FilesMatch>
    Header set X-Content-Type-Options "nosniff"
</IfModule>
# END Jacana Speed Doctor
';

	$result = file_put_contents( $file, $rules . $current );
	$result !== false ? wp_send_json_success( 'Rules written to .htaccess' ) : wp_send_json_error( 'Could not write to .htaccess — check file permissions' );
} );

// ── AJAX: remove .htaccess rules ──────────────────────────────────────────────

add_action( 'wp_ajax_jsd_htaccess_remove', function () {
	check_ajax_referer( 'jsd_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised' );

	$file    = ABSPATH . '.htaccess';
	if ( ! file_exists( $file ) ) wp_send_json_success( 'Nothing to remove' );

	$current = file_get_contents( $file );
	$cleaned = preg_replace( '/\n?# BEGIN Jacana Speed Doctor.*?# END Jacana Speed Doctor\n?/s', '', $current );
	file_put_contents( $file, $cleaned );
	wp_send_json_success( 'Rules removed from .htaccess' );
} );

// ── Environment data ──────────────────────────────────────────────────────────

function jsd_collect_env(): array {
	global $wpdb;

	$all_plugins    = get_plugins();
	$active_plugins = get_option( 'active_plugins', [] );

	$tables = $wpdb->get_results(
		"SELECT table_name, ROUND((data_length+index_length)/1024,1) AS size_kb, table_rows
		 FROM information_schema.tables WHERE table_schema=DATABASE()
		 AND table_name LIKE '{$wpdb->prefix}%' ORDER BY (data_length+index_length) DESC LIMIT 20"
	);

	$total_transients   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'" );
	$expired_transients = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d", '_transient_timeout_%', time() ) );
	$revision_count     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='revision'" );
	$spam_count         = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved='spam'" );
	$trash_count        = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status='trash'" );

	$cache_plugins = [];
	$known = [ 'wp-rocket/wp-rocket.php' => 'WP Rocket', 'w3-total-cache/w3-total-cache.php' => 'W3 Total Cache',
	           'wp-super-cache/wp-cache.php' => 'WP Super Cache', 'litespeed-cache/litespeed-cache.php' => 'LiteSpeed Cache',
	           'autoptimize/autoptimize.php' => 'Autoptimize', 'cache-enabler/cache-enabler.php' => 'Cache Enabler' ];
	foreach ( $known as $slug => $name ) {
		if ( in_array( $slug, $active_plugins, true ) ) $cache_plugins[] = $name;
	}

	// Check if JSD .htaccess rules are present.
	$htaccess      = ABSPATH . '.htaccess';
	$htaccess_jsd  = file_exists( $htaccess ) && false !== strpos( file_get_contents( $htaccess ), '# BEGIN Jacana Speed Doctor' );

	// Widget CSS file count.
	$widget_css_dir   = WP_CONTENT_DIR . '/plugins/jacana-luxe-elementor/assets/css/widgets/';
	$widget_css_count = is_dir( $widget_css_dir ) ? count( glob( $widget_css_dir . '*.css' ) ?: [] ) : 0;

	// Combined CSS cache exists?
	$upload      = wp_upload_dir();
	$css_cache   = $upload['basedir'] . '/jsd-css-cache/';
	$css_cached  = is_dir( $css_cache ) && ! empty( glob( $css_cache . 'widgets-*.css' ) ?: [] );

	// wp-config DISABLE_WP_CRON check.
	$config_content  = file_exists( ABSPATH . 'wp-config.php' ) ? file_get_contents( ABSPATH . 'wp-config.php' ) : '';
	$wpcron_disabled = false !== strpos( $config_content, 'DISABLE_WP_CRON' );

	return compact(
		'all_plugins', 'active_plugins', 'tables', 'total_transients', 'expired_transients',
		'revision_count', 'spam_count', 'trash_count', 'cache_plugins', 'htaccess_jsd',
		'widget_css_count', 'css_cached', 'wpcron_disabled'
	) + [
		'memory_limit'  => ini_get( 'memory_limit' ),
		'memory_peak'   => size_format( memory_get_peak_usage( true ) ),
		'db_version'    => $wpdb->get_var( 'SELECT VERSION()' ),
		'max_execution' => ini_get( 'max_execution_time' ),
		'object_cache'  => wp_using_ext_object_cache(),
		'opcache'       => (bool) ini_get( 'opcache.enable' ),
		'has_imagick'   => extension_loaded( 'imagick' ),
		'has_gd'        => extension_loaded( 'gd' ),
		'has_webp'      => function_exists( 'imagewebp' ) || extension_loaded( 'imagick' ),
	];
}

// ── Admin page ────────────────────────────────────────────────────────────────

function jsd_render_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) return;

	global $wp_version;
	$env   = jsd_collect_env();
	$opts  = get_option( 'jsd_optimizations', [] );
	$nonce = wp_create_nonce( 'jsd_nonce' );
	$act   = count( $env['active_plugins'] );
	?>
	<div class="wrap jsd-wrap">
	<h1>Speed Doctor <span class="jsd-sub">Jacana Safaris &amp; Tours</span></h1>

	<div class="jsd-tabs">
		<button class="jsd-tab active"  data-tab="overview">Overview</button>
		<button class="jsd-tab"         data-tab="pages">Pages</button>
		<button class="jsd-tab"         data-tab="optimisations">Optimisations</button>
		<button class="jsd-tab"         data-tab="media">Media Audit</button>
		<button class="jsd-tab"         data-tab="server">Server</button>
		<button class="jsd-tab"         data-tab="database">Database</button>
	</div>

	<?php /* ═══════════════════════ OVERVIEW ═══════════════════════ */ ?>
	<div class="jsd-panel active" id="jsd-tab-overview">

		<div class="jsd-grid">
			<div class="jsd-card">
				<h3>PHP</h3>
				<div class="jsd-metric <?php echo version_compare( PHP_VERSION, '8.0', '>=' ) ? 'jsd-metric--ok' : 'jsd-metric--warn'; ?>"><?php echo esc_html( PHP_VERSION ); ?></div>
				<p class="jsd-meta">Memory: <?php echo esc_html( $env['memory_limit'] ); ?> / <?php echo esc_html( $env['memory_peak'] ); ?> peak</p>
			</div>
			<div class="jsd-card">
				<h3>WordPress</h3>
				<div class="jsd-metric"><?php echo esc_html( $wp_version ); ?></div>
				<p class="jsd-meta">Max exec: <?php echo esc_html( $env['max_execution'] ); ?>s</p>
			</div>
			<div class="jsd-card">
				<h3>MySQL</h3>
				<div class="jsd-metric"><?php echo esc_html( $env['db_version'] ); ?></div>
			</div>
			<div class="jsd-card">
				<h3>Active Plugins</h3>
				<div class="jsd-metric <?php echo $act > 25 ? 'jsd-metric--warn' : ''; ?>"><?php echo esc_html( $act ); ?><span class="jsd-metric-sub">/ <?php echo count( $env['all_plugins'] ); ?></span></div>
			</div>
			<div class="jsd-card">
				<h3>Widget CSS Files</h3>
				<div class="jsd-metric <?php echo $env['widget_css_count'] > 10 ? 'jsd-metric--warn' : ''; ?>"><?php echo esc_html( $env['widget_css_count'] ); ?></div>
				<p class="jsd-meta"><?php echo $env['css_cached'] ? '✓ Combined cache exists' : 'Each file = 1 HTTP request'; ?></p>
			</div>
			<div class="jsd-card">
				<h3>OPcache</h3>
				<div class="jsd-metric <?php echo $env['opcache'] ? 'jsd-metric--ok' : 'jsd-metric--warn'; ?>"><?php echo $env['opcache'] ? 'On' : 'Off'; ?></div>
			</div>
		</div>

		<?php /* Quick Wins — site-specific */ ?>
		<div class="jsd-section">
			<div class="jsd-section-head"><h2>Site-Specific Quick Wins</h2></div>
			<div class="jsd-quick-wins">
				<div class="jsd-qw jsd-qw--critical">
					<div class="jsd-qw-icon">📹</div>
					<div>
						<strong>Background videos are in .mov / oversized .mp4 format</strong>
						<p>Three hero videos are .mov files (32–36 MB each). Browsers do not play MOV natively and must download the full file. Convert to H.264 MP4 + VP9 WebM, target &lt; 8 MB. Enable <em>Smart Video Loading</em> in Optimisations immediately to prevent the videos loading at all on mobile and slow connections.</p>
						<code>ffmpeg -i input.mov -c:v libx264 -crf 26 -preset slow -vf scale=1920:-2 -an output.mp4</code>
					</div>
				</div>
				<div class="jsd-qw jsd-qw--warn">
					<div class="jsd-qw-icon">🎨</div>
					<div>
						<strong><?php echo esc_html( $env['widget_css_count'] ); ?> CSS files loaded on every page</strong>
						<p>The Elementor plugin enqueues one CSS file per widget. Enable <em>Combine Widget CSS</em> to merge all <?php echo esc_html( $env['widget_css_count'] ); ?> files into one cached file — reduces style-related HTTP requests by ~<?php echo $env['widget_css_count'] - 1; ?>.</p>
					</div>
				</div>
				<div class="jsd-qw jsd-qw--warn">
					<div class="jsd-qw-icon">🖼</div>
					<div>
						<strong>Many large unoptimised images in uploads</strong>
						<p>Run the <strong>Media Audit</strong> tab to see which images are over 400 KB. Use <a href="https://squoosh.app" target="_blank">Squoosh</a> or a CDN with on-the-fly WebP conversion. Also consider the <em>Lazy Loading</em> optimisation.</p>
					</div>
				</div>
				<div class="jsd-qw <?php echo ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'jsd-qw--warn' : 'jsd-qw--ok'; ?>">
					<div class="jsd-qw-icon"><?php echo ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '⚠' : '✓'; ?></div>
					<div>
						<strong>WP_DEBUG is <?php echo ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'ON — disable before going live' : 'off'; ?></strong>
						<p><?php echo ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'Set define(\'WP_DEBUG\', false) in wp-config.php. Debug mode logs every PHP notice and adds measurable overhead.' : 'Good — debug logging is off.'; ?></p>
					</div>
				</div>
				<div class="jsd-qw <?php echo $env['htaccess_jsd'] ? 'jsd-qw--ok' : 'jsd-qw--warn'; ?>">
					<div class="jsd-qw-icon"><?php echo $env['htaccess_jsd'] ? '✓' : '⚠'; ?></div>
					<div>
						<strong>Browser caching for static assets is <?php echo $env['htaccess_jsd'] ? 'configured' : 'not configured'; ?></strong>
						<p><?php echo $env['htaccess_jsd'] ? 'Cache-Control and Expires rules are active in .htaccess.' : 'Without cache headers, every visit re-downloads all videos, images, CSS, and JS. Go to the Server tab to write .htaccess rules in one click.'; ?></p>
					</div>
				</div>
			</div>
		</div>

		<?php /* Homepage speed test */ ?>
		<div class="jsd-section">
			<div class="jsd-section-head"><h2>Homepage Speed Test</h2></div>
			<p class="jsd-meta" style="margin-bottom:14px">Fetches <code><?php echo esc_url( home_url( '/' ) ); ?></code> server-side and measures response time, page weight, and asset counts.</p>
			<div class="jsd-test-bar">
				<button id="jsd-run-test" class="button button-primary">&#9654; Run Test</button>
				<span id="jsd-test-loading" style="display:none" class="jsd-loading">Fetching&hellip;</span>
			</div>
			<div id="jsd-test-error" style="display:none" class="jsd-error-box"></div>
			<div id="jsd-test-results" style="display:none">
				<div class="jsd-grid jsd-grid--4" style="margin-top:16px">
					<div class="jsd-card"><h3>Response Time</h3><div class="jsd-metric" id="jsd-r-time">—</div><p class="jsd-meta" id="jsd-r-time-note"></p></div>
					<div class="jsd-card"><h3>Page Size</h3><div class="jsd-metric" id="jsd-r-size">—</div></div>
					<div class="jsd-card"><h3>GZIP</h3><div class="jsd-metric" id="jsd-r-gzip">—</div><p class="jsd-meta" id="jsd-r-enc"></p></div>
					<div class="jsd-card"><h3>HTTP Status</h3><div class="jsd-metric" id="jsd-r-code">—</div></div>
					<div class="jsd-card"><h3>Script Tags</h3><div class="jsd-metric" id="jsd-r-scripts">—</div></div>
					<div class="jsd-card"><h3>Render-Blocking Scripts</h3><div class="jsd-metric" id="jsd-r-blocking">—</div><p class="jsd-meta">In &lt;head&gt;, no defer/async</p></div>
					<div class="jsd-card"><h3>Style Tags</h3><div class="jsd-metric" id="jsd-r-styles">—</div></div>
					<div class="jsd-card"><h3>Images (lazy)</h3><div class="jsd-metric" id="jsd-r-images">—</div><p class="jsd-meta" id="jsd-r-lazy"></p></div>
				</div>
				<div id="jsd-test-advice" style="margin-top:14px"></div>
			</div>
		</div>

	</div><?php /* end overview */ ?>

	<?php /* ═══════════════════════ PAGES ═══════════════════════════ */ ?>
	<div class="jsd-panel" id="jsd-tab-pages">
		<div class="jsd-section">
			<div class="jsd-section-head"><h2>Per-Page Speed Test</h2></div>
			<p class="jsd-meta" style="margin-bottom:10px">Tests every published page. Pages are fetched 3 at a time. Results appear as they complete.</p>
			<?php if ( ! $env['wpcron_disabled'] ): ?>
			<div class="jsd-qw jsd-qw--warn" style="margin-bottom:14px">
				<div class="jsd-qw-icon">⏱</div>
				<div>
					<strong>WP Cron is active — pages may time out on Local by Flywheel</strong>
					<p>WordPress spawns background cron jobs (including external Google Gemini API calls from jacana-crm) during page loads. On a local server with limited PHP workers this causes worker-pool deadlock and 25-second timeouts. Speed Doctor automatically disables cron for its own test requests, but if you are still seeing timeouts go to the <strong>Server tab</strong> and click <em>Disable WP Cron in wp-config</em>.</p>
				</div>
			</div>
			<?php else: ?>
			<div class="jsd-qw jsd-qw--ok" style="margin-bottom:14px">
				<div class="jsd-qw-icon">✓</div>
				<div><strong>DISABLE_WP_CRON is active</strong> — cron-related timeouts will not occur.</div>
			</div>
			<?php endif; ?>
			<div class="jsd-test-bar">
				<button id="jsd-test-all" class="button button-primary">&#9654; Test All Pages</button>
				<button id="jsd-stop-test" class="button" style="display:none">&#9646;&#9646; Stop</button>
				<span id="jsd-pages-loading" style="display:none" class="jsd-loading">Loading page list&hellip;</span>
			</div>
			<div id="jsd-pages-progress" style="display:none;margin:14px 0">
				<div class="jsd-progress-bar"><div class="jsd-progress-fill" id="jsd-prog-fill" style="width:0%"></div></div>
				<p class="jsd-meta" id="jsd-prog-label"></p>
			</div>
			<div id="jsd-pages-error" class="jsd-error-box" style="display:none"></div>
			<div id="jsd-pages-table-wrap" style="display:none;margin-top:16px">
				<table class="jsd-table" id="jsd-pages-table">
					<thead>
						<tr>
							<th>Page</th>
							<th>Time</th>
							<th>Size</th>
							<th>HTTP</th>
							<th>Scripts</th>
							<th>Block.</th>
							<th>Notes</th>
						</tr>
					</thead>
					<tbody id="jsd-pages-tbody"></tbody>
				</table>
			</div>
		</div>
	</div>

	<?php /* ═══════════════════════ OPTIMISATIONS ════════════════════ */ ?>
	<div class="jsd-panel" id="jsd-tab-optimisations">
		<div class="jsd-section">
			<div class="jsd-section-head">
				<h2>Performance Optimisations</h2>
				<?php if ( ! empty( $opts['combine_widget_css'] ) ): ?>
					<button id="jsd-clear-cache" class="button" style="margin-left:auto">&#128465; Clear CSS Cache</button>
				<?php endif; ?>
			</div>
			<p class="jsd-meta" style="margin-bottom:18px">Changes apply on the next front-end page load after saving.</p>
			<form id="jsd-opts-form">
				<div class="jsd-opts-list">
				<?php foreach ( Jacana_Speed_Optimizations::OPTS as $key ):
					$recommended = in_array( $key, [ 'smart_video_loading', 'combine_widget_css', 'disable_emojis', 'clean_head', 'preconnect_fonts', 'lazy_load_images' ], true );
					?>
					<div class="jsd-opt-row">
						<label class="jsd-toggle">
							<input type="checkbox" name="opts[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $opts[ $key ] ) ); ?>>
							<span class="jsd-toggle-slider"></span>
						</label>
						<div class="jsd-opt-info">
							<strong><?php echo esc_html( Jacana_Speed_Optimizations::LABELS[ $key ] ?? $key ); ?>
								<?php if ( $recommended ): ?><span class="jsd-badge">Recommended for this site</span><?php endif; ?>
							</strong>
							<p><?php echo esc_html( Jacana_Speed_Optimizations::DESCRIPTIONS[ $key ] ?? '' ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
				</div>
				<div class="jsd-save-row">
					<button type="submit" class="button button-primary">Save Optimisations</button>
					<span id="jsd-save-status"></span>
				</div>
			</form>
		</div>
	</div>

	<?php /* ═══════════════════════ MEDIA AUDIT ══════════════════════ */ ?>
	<div class="jsd-panel" id="jsd-tab-media">
		<div class="jsd-section">
			<div class="jsd-section-head"><h2>Media Audit</h2></div>
			<p class="jsd-meta" style="margin-bottom:14px">Scans <code>wp-content/uploads</code> for video files and large images. May take a few seconds.</p>
			<button id="jsd-scan-media" class="button button-primary">&#128269; Scan Media Files</button>
			<span id="jsd-media-loading" style="display:none" class="jsd-loading">Scanning uploads&hellip;</span>
			<div id="jsd-media-results" style="display:none">

				<div class="jsd-grid" style="margin-top:20px" id="jsd-media-summary"></div>

				<div class="jsd-section" id="jsd-video-section">
					<div class="jsd-section-head"><h2>Video Files</h2></div>
					<div id="jsd-video-advice" class="jsd-advice-box" style="display:none"></div>
					<table class="jsd-table" style="margin-top:12px">
						<thead><tr><th></th><th>Filename</th><th>Size</th><th>Format</th><th>Issue</th></tr></thead>
						<tbody id="jsd-video-tbody"></tbody>
					</table>
					<div class="jsd-ffmpeg-box" style="margin-top:16px">
						<strong>Convert with ffmpeg (run in your terminal):</strong>
						<pre>
# MOV → web-optimised MP4 (H.264, no audio)
ffmpeg -i input.mov -c:v libx264 -crf 26 -preset slow -vf "scale=1920:-2" -an output.mp4

# MP4 → WebM (VP9) for smaller file size (~40% smaller)
ffmpeg -i input.mp4 -c:v libvpx-vp9 -crf 33 -b:v 0 -an output.webm

# Then use both in your &lt;video&gt; tag:
# &lt;source src="output.webm" type="video/webm"&gt;
# &lt;source src="output.mp4"  type="video/mp4"&gt;</pre>
					</div>
				</div>

				<div class="jsd-section" id="jsd-image-section">
					<div class="jsd-section-head"><h2>Large Images (over 400 KB)</h2></div>

					<?php /* Optimizer panel — shown after scan */ ?>
					<div class="jsd-optimizer-panel">
						<div class="jsd-opt-caps">
							<span class="jsd-badge jsd-badge--ok">Imagick <?php echo $env['has_imagick'] ? '✓' : '✗'; ?></span>
							<span class="jsd-badge <?php echo $env['has_gd'] ? 'jsd-badge--ok' : 'jsd-badge--error'; ?>">GD <?php echo $env['has_gd'] ? '✓' : '✗'; ?></span>
							<span class="jsd-badge <?php echo $env['has_webp'] ? 'jsd-badge--ok' : ''; ?>">WebP <?php echo $env['has_webp'] ? '✓' : '✗'; ?></span>
						</div>
						<div class="jsd-optimizer-controls">
							<label class="jsd-opt-label">
								JPEG quality
								<select id="jsd-img-quality">
									<option value="90">90 — high (conservative)</option>
									<option value="82" selected>82 — recommended</option>
									<option value="75">75 — aggressive</option>
								</select>
							</label>
							<?php if ( $env['has_webp'] ): ?>
							<label class="jsd-opt-label jsd-opt-label--check">
								<input type="checkbox" id="jsd-make-webp" checked>
								Also create .webp copies
							</label>
							<?php endif; ?>
							<button id="jsd-optimize-all" class="button button-primary" disabled>&#9881; Optimise All Large Images</button>
						</div>
						<div id="jsd-img-progress" style="display:none;margin-top:12px">
							<div class="jsd-progress-bar"><div class="jsd-progress-fill" id="jsd-img-prog-fill" style="width:0%"></div></div>
							<p class="jsd-meta" id="jsd-img-prog-label"></p>
						</div>
						<div id="jsd-img-totals" style="display:none" class="jsd-savings-bar"></div>
					</div>

					<p class="jsd-meta" style="margin:12px 0">Target &lt; 150 KB for content images, &lt; 300 KB for full-width hero images. JPEG quality 82 typically saves 40–70% with no visible loss.</p>
					<table class="jsd-table">
						<thead><tr><th>Filename</th><th>Original</th><th>Optimised</th><th>Saved</th><th>Format</th></tr></thead>
						<tbody id="jsd-image-tbody"></tbody>
					</table>
				</div>

			</div>
		</div>
	</div>

	<?php /* ═══════════════════════ SERVER ════════════════════════════ */ ?>
	<div class="jsd-panel" id="jsd-tab-server">
		<div class="jsd-section">
			<div class="jsd-section-head"><h2>Apache .htaccess — Browser Caching &amp; Compression</h2></div>
			<p class="jsd-meta" style="margin-bottom:4px">
				<?php if ( $env['htaccess_jsd'] ): ?>
					<span style="color:#1a7a3c;font-weight:600">✓ Speed Doctor rules are active in .htaccess.</span> Videos, images, CSS, and JS will be cached for 1 year by the browser after the first visit.
				<?php else: ?>
					<span style="color:#b45309;font-weight:600">⚠ No browser caching rules found in .htaccess.</span> Without these, every visit re-downloads all videos (30–36 MB each), images, and scripts.
				<?php endif; ?>
			</p>
			<p class="jsd-meta" style="margin:8px 0 18px">Rules are written between <code># BEGIN Jacana Speed Doctor</code> / <code># END Jacana Speed Doctor</code> markers and can be removed at any time.</p>
			<div style="display:flex;gap:10px;flex-wrap:wrap">
				<button id="jsd-htaccess-write" class="button button-primary">&#9998; Write Cache &amp; GZIP Rules</button>
				<button id="jsd-htaccess-remove" class="button">&#128465; Remove Rules</button>
				<span id="jsd-htaccess-status" style="align-self:center;font-weight:600;font-size:13px"></span>
			</div>
		</div>

		<div class="jsd-section">
			<div class="jsd-section-head"><h2>Current .htaccess</h2></div>
			<pre class="jsd-code-block"><?php
				$htaccess_path = ABSPATH . '.htaccess';
				echo esc_html( file_exists( $htaccess_path ) ? file_get_contents( $htaccess_path ) : '(file not found)' );
			?></pre>
		</div>

		<div class="jsd-section">
			<div class="jsd-section-head"><h2>WP Cron — Fix Page Test Timeouts</h2></div>
			<?php if ( $env['wpcron_disabled'] ): ?>
			<p style="color:#1a7a3c;font-weight:600;font-size:13px">✓ DISABLE_WP_CRON is already defined in wp-config.php.</p>
			<?php else: ?>
			<p style="font-size:13px;color:#555;margin:0 0 12px">
				WordPress fires background cron jobs (SEO audits, competitor scans, Gemini API calls from jacana-crm) during front-end page loads.
				On Local by Flywheel this exhausts the PHP worker pool and causes the speed test timeouts you saw.
				<strong>Disabling WP Cron</strong> fixes this; cron will still run whenever you access the WP Admin.
			</p>
			<div style="display:flex;gap:10px;align-items:center">
				<button id="jsd-disable-wpcron" class="button button-primary">&#9998; Add DISABLE_WP_CRON to wp-config.php</button>
				<span id="jsd-wpcron-status" style="font-size:13px;font-weight:600"></span>
			</div>
			<?php endif; ?>
		</div>

		<div class="jsd-section">
			<div class="jsd-section-head"><h2>Additional Server Recommendations</h2></div>
			<table class="jsd-table">
				<thead><tr><th></th><th>Recommendation</th><th>How</th></tr></thead>
				<tbody>
					<tr><td>📹</td><td><strong>Serve videos via a CDN</strong></td><td>Cloudflare (free) or Bunny.net — offloads 30–36 MB video delivery from your server entirely</td></tr>
					<tr><td>🖼</td><td><strong>Enable WebP via server</strong></td><td>Apache: use <code>mod_rewrite</code> to serve .webp if the browser accepts it and a .webp file exists</td></tr>
					<tr><td>🚀</td><td><strong>Enable HTTP/2</strong></td><td>Reduces latency when many assets load in parallel — check with your host</td></tr>
					<tr><td>💾</td><td><strong>Add a page cache</strong></td><td>LiteSpeed Cache (free) or WP Rocket — eliminates PHP + DB overhead on repeat visits</td></tr>
					<tr><td>🔧</td><td><strong>Enable PHP OPcache</strong></td><td>Set <code>opcache.enable=1</code> in php.ini — caches compiled PHP bytecode</td></tr>
				</tbody>
			</table>
		</div>
	</div>

	<?php /* ═══════════════════════ DATABASE ═══════════════════════════ */ ?>
	<div class="jsd-panel" id="jsd-tab-database">

		<div class="jsd-grid">
			<?php foreach ( [
				[ 'Post Revisions',    $env['revision_count'],     100 ],
				[ 'Expired Transients',$env['expired_transients'], 20  ],
				[ 'Spam Comments',     $env['spam_count'],         0   ],
				[ 'Trashed Posts',     $env['trash_count'],        0   ],
			] as [ $label, $count, $threshold ] ): ?>
			<div class="jsd-card">
				<h3><?php echo esc_html( $label ); ?></h3>
				<div class="jsd-metric <?php echo $count > $threshold ? 'jsd-metric--warn' : ''; ?>">
					<?php echo number_format( $count ); ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<div class="jsd-section">
			<div class="jsd-section-head"><h2>Database Cleanup</h2></div>
			<p class="jsd-meta" style="margin-bottom:14px">Deletes expired transients, spam, all post revisions, auto-drafts, trashed posts &gt; 30 days, and orphaned postmeta. Then runs <code>OPTIMIZE TABLE</code> on every table.</p>
			<button id="jsd-run-cleanup" class="button button-primary">&#128465; Run Cleanup</button>
			<div id="jsd-cleanup-result" style="display:none" class="jsd-cleanup-log"></div>
		</div>

		<div class="jsd-section">
			<div class="jsd-section-head"><h2>Largest Tables</h2></div>
			<table class="jsd-table">
				<thead><tr><th>Table</th><th>Approx. Rows</th><th>Size (KB)</th></tr></thead>
				<tbody>
				<?php foreach ( $env['tables'] as $t ): 
					$t = (object) array_change_key_case( (array) $t, CASE_LOWER );
				?>
					<tr>
						<td><?php echo esc_html( $t->table_name ?? '' ); ?></td>
						<td><?php echo number_format( (int) ($t->table_rows ?? 0) ); ?></td>
						<td><?php echo number_format( (float) ($t->size_kb ?? 0), 1 ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>

	</div><?php /* end database */ ?>

	</div><?php /* end .jsd-wrap */ ?>

	<script>
	(function ($) {
		var nonce     = '<?php echo esc_js( $nonce ); ?>';
		var stopFlag  = false;

		/* ── Tabs ──────────────────────────────────────────────── */
		$('.jsd-tab').on('click', function () {
			$('.jsd-tab').removeClass('active');
			$('.jsd-panel').removeClass('active');
			$(this).addClass('active');
			$('#jsd-tab-' + $(this).data('tab')).addClass('active');
		});

		/* ── Homepage speed test ───────────────────────────────── */
		function applyMetric($el, value, okMax, warnMax) {
			$el.removeClass('jsd-metric--ok jsd-metric--warn jsd-metric--error');
			if (value <= okMax)   $el.addClass('jsd-metric--ok');
			else if (value <= warnMax) $el.addClass('jsd-metric--warn');
			else                  $el.addClass('jsd-metric--error');
		}

		function renderSpeedResult(d, prefix) {
			prefix = prefix || 'jsd-r-';
			applyMetric($('#' + prefix + 'time'), d.time_ms, 1500, 3000);
			$('#' + prefix + 'time').text(d.time_ms + ' ms');
			if ($('#' + prefix + 'time-note').length) {
				$('#' + prefix + 'time-note').text(d.time_ms > 3000 ? '🔴 Slow (> 3 s)' : d.time_ms > 1500 ? '⚠ Moderate' : '✓ Good');
			}
			applyMetric($('#' + prefix + 'size'), d.size_kb, 1500, 3000);
			$('#' + prefix + 'size').text(d.size_kb + ' KB');
			$('#' + prefix + 'gzip').text(d.gzip ? '✓ On' : '✗ Off').toggleClass('jsd-metric--ok', d.gzip).toggleClass('jsd-metric--warn', !d.gzip);
			if ($('#' + prefix + 'enc').length) $('#' + prefix + 'enc').text(d.encoding);
			$('#' + prefix + 'code').text(d.http_code || d.code);
			applyMetric($('#' + prefix + 'scripts'), d.scripts, 15, 25);
			$('#' + prefix + 'scripts').text(d.scripts);
			applyMetric($('#' + prefix + 'blocking'), d.render_blocking, 2, 5);
			$('#' + prefix + 'blocking').text(d.render_blocking);
			if ($('#' + prefix + 'styles').length) {
				applyMetric($('#' + prefix + 'styles'), d.styles, 15, 25);
				$('#' + prefix + 'styles').text(d.styles);
			}
			if ($('#' + prefix + 'images').length) {
				$('#' + prefix + 'images').text(d.images);
				var pct = d.images > 0 ? Math.round((d.lazy_images / d.images) * 100) : 100;
				$('#' + prefix + 'lazy').text(d.lazy_images + '/' + d.images + ' lazy (' + pct + '%)');
			}
		}

		$('#jsd-run-test').on('click', function () {
			$(this).prop('disabled', true);
			$('#jsd-test-results, #jsd-test-error').hide();
			$('#jsd-test-loading').show();
			$.post(ajaxurl, { action: 'jsd_speed_test', _ajax_nonce: nonce }, function (res) {
				$('#jsd-test-loading').hide();
				$('#jsd-run-test').prop('disabled', false);
				if (!res.success) { $('#jsd-test-error').text(res.data).show(); return; }
				renderSpeedResult(res.data);
				var d = res.data, adv = [];
				if (d.has_mov_video)    adv.push('🔴 .mov video detected — convert to MP4/WebM. Enable Smart Video Loading.');
				if (d.size_kb > 3000)   adv.push('⚠ Page exceeds 3 MB — check for unoptimised images.');
				if (d.render_blocking > 2) adv.push('⚠ ' + d.render_blocking + ' render-blocking script(s) — enable Defer Scripts.');
				if (!d.gzip)            adv.push('⚠ No compression detected — write .htaccess rules on the Server tab.');
				if (d.scripts > 25)     adv.push('⚠ ' + d.scripts + ' script tags — combine or defer JS.');
				$('#jsd-test-advice').html(adv.length
					? '<ul style="margin:0;padding:0 0 0 20px;color:#555;font-size:13px;line-height:1.9">' + adv.map(function(a){return '<li>'+a+'</li>';}).join('') + '</ul>'
					: '<p style="color:#1a7a3c;font-weight:600">✓ No obvious issues detected.</p>');
				$('#jsd-test-results').show();
			});
		});

		/* ── Per-page test ─────────────────────────────────────── */
		function statusBadge(time_ms, size_kb, has_mov) {
			if (has_mov) return '<span class="jsd-badge jsd-badge--error">MOV!</span>';
			if (time_ms > 3000 || size_kb > 4000) return '<span class="jsd-badge jsd-badge--error">Slow</span>';
			if (time_ms > 1500 || size_kb > 2000) return '<span class="jsd-badge jsd-badge--warn">Moderate</span>';
			return '<span class="jsd-badge jsd-badge--ok">OK</span>';
		}

		function timeClass(ms) {
			return ms > 3000 ? 'jsd-metric--error' : ms > 1500 ? 'jsd-metric--warn' : 'jsd-metric--ok';
		}

		function addPageRow(page, data) {
			var $tr = $('<tr>');
			var title = $('<span>').text(page.title).html();
			var link  = '<a href="' + page.url + '" target="_blank">' + title + '</a>';

			if (data.error) {
				var isTimeout = data.is_timeout;
				$tr.html(
					'<td>' + link + '</td>' +
					'<td colspan="6" style="color:' + (isTimeout ? '#b45309' : '#b91c1c') + ';font-size:12px">' +
					(isTimeout
						? '⏱ Timed out (25 s) — WP Cron is likely blocking a PHP worker. See Server tab → <em>Disable WP Cron</em>.'
						: '✗ ' + data.error)
					+ '</td>'
				);
			} else {
				var notes = [];
				if (data.has_mov_video)       notes.push('<span class="jsd-badge jsd-badge--error">MOV video</span>');
				if (data.has_local_video)     notes.push('📹 Video');
				if (data.render_blocking > 3) notes.push('⚠ ' + data.render_blocking + ' blocking');
				if (!data.gzip)               notes.push('⚠ no gzip');

				$tr.html(
					'<td>' + link + '</td>' +
					'<td class="' + timeClass(data.time_ms) + '" style="font-weight:700">' + data.time_ms + ' ms</td>' +
					'<td>' + data.size_kb + ' KB</td>' +
					'<td>' + (data.http_code || data.code) + '</td>' +
					'<td class="' + (data.scripts > 25 ? 'jsd-metric--warn' : '') + '">' + data.scripts + '</td>' +
					'<td class="' + (data.render_blocking > 3 ? 'jsd-metric--error' : data.render_blocking > 1 ? 'jsd-metric--warn' : '') + '">' + data.render_blocking + '</td>' +
					'<td>' + (notes.join(' ') || '—') + '</td>'
				);
			}
			$('#jsd-pages-tbody').append($tr);
		}

		function testPage(page) {
			var def = $.Deferred();
			if (stopFlag) { def.resolve(); return def; }
			$.post(ajaxurl, { action: 'jsd_test_page', url: page.url, _ajax_nonce: nonce }, function (res) {
				if (res.success) addPageRow(page, res.data);
				else {
					$('#jsd-pages-tbody').append(
						'<tr><td><a href="' + page.url + '" target="_blank">' + $('<span>').text(page.title).html() + '</a></td><td colspan="6" style="color:#b91c1c">Error: ' + res.data + '</td></tr>'
					);
				}
				def.resolve();
			}).fail(function () { def.resolve(); });
			return def;
		}

		function testBatch(pages, idx) {
			if (stopFlag || idx >= pages.length) {
				$('#jsd-stop-test').hide();
				$('#jsd-prog-label').text(stopFlag ? 'Stopped.' : 'All pages tested.');
				return;
			}
			var batch = pages.slice(idx, idx + 3);
			$.when.apply($, batch.map(testPage)).always(function () {
				var done = Math.min(idx + 3, pages.length);
				$('#jsd-prog-fill').css('width', Math.round((done / pages.length) * 100) + '%');
				$('#jsd-prog-label').text(done + ' / ' + pages.length + ' pages tested');
				testBatch(pages, idx + 3);
			});
		}

		$('#jsd-test-all').on('click', function () {
			stopFlag = false;
			$('#jsd-pages-tbody').empty();
			$('#jsd-pages-table-wrap').hide();
			$('#jsd-pages-error').hide();
			$('#jsd-pages-loading').show();
			$(this).prop('disabled', true);

			$.post(ajaxurl, { action: 'jsd_get_pages', _ajax_nonce: nonce }, function (res) {
				$('#jsd-pages-loading').hide();
				$('#jsd-test-all').prop('disabled', false);
				if (!res.success || !res.data.length) {
					$('#jsd-pages-error').text('No published pages found.').show(); return;
				}
				var pages = res.data;
				$('#jsd-prog-fill').css('width', '0%');
				$('#jsd-prog-label').text('0 / ' + pages.length + ' pages tested');
				$('#jsd-pages-progress').show();
				$('#jsd-pages-table-wrap').show();
				$('#jsd-stop-test').show();
				testBatch(pages, 0);
			});
		});

		$('#jsd-stop-test').on('click', function () { stopFlag = true; });

		/* ── Media scan ────────────────────────────────────────── */
		$('#jsd-scan-media').on('click', function () {
			$(this).prop('disabled', true);
			$('#jsd-media-results').hide();
			$('#jsd-media-loading').show();

			$.post(ajaxurl, { action: 'jsd_scan_media', _ajax_nonce: nonce }, function (res) {
				$('#jsd-media-loading').hide();
				$('#jsd-scan-media').prop('disabled', false);
				if (!res.success) return;
				var d = res.data;

				// Summary cards.
				$('#jsd-media-summary').html(
					'<div class="jsd-card"><h3>Total Video Size</h3><div class="jsd-metric ' + (d.total_video_mb > 50 ? 'jsd-metric--error' : d.total_video_mb > 20 ? 'jsd-metric--warn' : '') + '">' + d.total_video_mb + ' MB</div></div>' +
					'<div class="jsd-card"><h3>Problem Videos</h3><div class="jsd-metric ' + (d.critical_videos > 0 ? 'jsd-metric--error' : 'jsd-metric--ok') + '">' + d.critical_videos + '</div><p class="jsd-meta">MOV format or &gt; 15 MB</p></div>' +
					'<div class="jsd-card"><h3>Large Images</h3><div class="jsd-metric ' + (d.large_img_count > 20 ? 'jsd-metric--warn' : '') + '">' + d.large_img_count + '</div><p class="jsd-meta">' + d.large_img_mb + ' MB over 400 KB</p></div>'
				);

				// Video advice.
				if (d.critical_videos > 0) {
					$('#jsd-video-advice').html(
						'<strong>🔴 Action required:</strong> ' + d.critical_videos + ' video(s) are in the wrong format or too large. MOV files are not a web format — browsers may not play them and will always download the full file. Convert using the ffmpeg commands below, then re-upload and update the video URL in each hero widget.'
					).show();
				}

				// Video rows.
				var vHtml = '';
				$.each(d.videos, function (i, v) {
					var icon  = v.critical ? '🔴' : (v.size_mb > 8 ? '⚠' : '✓');
					var issue = v.bad_format ? 'Wrong format (not web-compatible)' : v.oversized ? 'Too large (> 15 MB)' : '—';
					vHtml += '<tr><td>' + icon + '</td><td>' + $('<span>').text(v.name).html() + '</td><td style="font-weight:' + (v.size_mb > 15 ? '700' : 'normal') + ';color:' + (v.size_mb > 15 ? '#b91c1c' : 'inherit') + '">' + v.size_mb + ' MB</td><td><code>.' + v.ext + '</code></td><td>' + issue + '</td></tr>';
				});
				$('#jsd-video-tbody').html(vHtml || '<tr><td colspan="5">No video files found.</td></tr>');

				// Image rows — 5 columns: name, original, optimised, saved, format
				var imageData = d.large_images;
				var iHtml = '';
				$.each(imageData, function (i, img) {
					var sizeColor = img.size_kb > 2000 ? '#b91c1c' : img.size_kb > 800 ? '#b45309' : 'inherit';
					iHtml += '<tr id="jsd-img-row-' + i + '">' +
						'<td>' + $('<span>').text(img.name).html() + '</td>' +
						'<td class="jsd-img-orig" style="color:' + sizeColor + ';font-weight:700">' + img.size_kb + ' KB</td>' +
						'<td class="jsd-img-new">—</td>' +
						'<td class="jsd-img-saved">—</td>' +
						'<td>.' + img.ext + '</td>' +
					'</tr>';
				});
				$('#jsd-image-tbody').html(iHtml || '<tr><td colspan="5">No large images found.</td></tr>');

				// Enable optimizer button if there are images to optimize.
				if (imageData.length > 0) {
					$('#jsd-optimize-all').prop('disabled', false).text('⚙ Optimise All ' + imageData.length + ' Images');
				}

				// Store image list for the optimizer.
				$('#jsd-optimize-all').data('images', imageData);

				$('#jsd-media-results').show();
			});
		});

		/* ── Save optimisations ────────────────────────────────── */
		$('#jsd-opts-form').on('submit', function (e) {
			e.preventDefault();
			var data = { action: 'jsd_save_opts', _ajax_nonce: nonce, opts: {} };
			$(this).find('input[type=checkbox]').each(function () {
				data.opts[$(this).attr('name').replace('opts[','').replace(']','')] = this.checked ? '1' : '0';
			});
			$.post(ajaxurl, data, function (res) {
				var $s = $('#jsd-save-status');
				$s.removeClass('jsd-saved jsd-error').addClass(res.success ? 'jsd-saved' : 'jsd-error')
				  .text(res.success ? '✓ Saved. Reload the front end to see changes.' : '✗ Could not save.');
				setTimeout(function () { $s.text(''); }, 5000);
			});
		});

		/* ── Clear CSS cache ───────────────────────────────────── */
		$('#jsd-clear-cache').on('click', function () {
			$(this).prop('disabled', true).text('Clearing…');
			$.post(ajaxurl, { action: 'jsd_clear_css_cache', _ajax_nonce: nonce }, function (res) {
				$('#jsd-clear-cache').prop('disabled', false).text('🗑 Clear CSS Cache');
				alert(res.success ? '✓ ' + res.data : '✗ ' + res.data);
			});
		});

		/* ── .htaccess ─────────────────────────────────────────── */
		function htaccessAction(action) {
			$('#jsd-htaccess-write, #jsd-htaccess-remove').prop('disabled', true);
			$.post(ajaxurl, { action: action, _ajax_nonce: nonce }, function (res) {
				$('#jsd-htaccess-write, #jsd-htaccess-remove').prop('disabled', false);
				var $s = $('#jsd-htaccess-status');
				$s.css('color', res.success ? '#1a7a3c' : '#b91c1c')
				  .text(res.success ? '✓ ' + res.data : '✗ ' + res.data);
				if (res.success) setTimeout(function(){location.reload();}, 1500);
			});
		}
		$('#jsd-htaccess-write').on('click',  function () { htaccessAction('jsd_htaccess_write'); });
		$('#jsd-htaccess-remove').on('click', function () { htaccessAction('jsd_htaccess_remove'); });

		/* ── Bulk image optimizer ──────────────────────────────── */
		$('#jsd-optimize-all').on('click', function () {
			var images  = $(this).data('images');
			if (!images || !images.length) return;

			$(this).prop('disabled', true).text('Running…');
			$('#jsd-img-progress').show();

			var quality  = parseInt($('#jsd-img-quality').val(), 10) || 82;
			var makeWebp = $('#jsd-make-webp').is(':checked') ? '1' : '0';
			var total    = images.length;
			var done     = 0;
			var totalSavedKb = 0;
			var webpCount    = 0;

			function updateProgress() {
				var pct = Math.round((done / total) * 100);
				$('#jsd-img-prog-fill').css('width', pct + '%');
				$('#jsd-img-prog-label').text(done + ' / ' + total + ' images processed — saved ' + totalSavedKb + ' KB total');
			}

			function optimizeOne(img, idx) {
				var def = $.Deferred();
				$.post(ajaxurl, {
					action:    'jsd_optimize_image',
					_ajax_nonce: nonce,
					img_url:   img.url,
					quality:   quality,
					make_webp: makeWebp,
				}, function (res) {
					done++;
					var $row = $('#jsd-img-row-' + idx);
					if (res.success) {
						var d = res.data;
						totalSavedKb += d.saved_kb;
						if (d.webp) webpCount++;
						$row.find('.jsd-img-orig').text(d.original_kb + ' KB');
						$row.find('.jsd-img-new').text(d.new_kb + ' KB').css('color', '#1a7a3c');
						$row.find('.jsd-img-saved').html('<strong style="color:#1a7a3c">-' + d.saved_pct + '%</strong> (' + d.saved_kb + ' KB)');
					} else {
						$row.find('.jsd-img-new').text('Error').css('color', '#b91c1c');
					}
					updateProgress();
					def.resolve();
				}).fail(function () { done++; updateProgress(); def.resolve(); });
				return def;
			}

			function processBatch(idx) {
				if (idx >= images.length) {
					$('#jsd-optimize-all').text('✓ Done');
					var summary = 'Total saved: ' + totalSavedKb + ' KB (' + Math.round(totalSavedKb / 1024 * 10) / 10 + ' MB)';
					if (webpCount > 0) summary += ' — ' + webpCount + ' .webp copies created';
					$('#jsd-img-totals').text(summary).show();
					return;
				}
				var batch = images.slice(idx, idx + 2); // 2 concurrent
				$.when.apply($, batch.map(function (img, i) { return optimizeOne(img, idx + i); }))
				 .always(function () { processBatch(idx + 2); });
			}

			processBatch(0);
		});

		/* ── Disable WP Cron ───────────────────────────────────── */
		$('#jsd-disable-wpcron').on('click', function () {
			$(this).prop('disabled', true).text('Writing…');
			$.post(ajaxurl, { action: 'jsd_disable_wpcron', _ajax_nonce: nonce }, function (res) {
				$('#jsd-disable-wpcron').prop('disabled', false).text('⚙ Add DISABLE_WP_CRON');
				var $s = $('#jsd-wpcron-status');
				$s.css('color', res.success ? '#1a7a3c' : '#b91c1c')
				  .text(res.success ? '✓ ' + res.data : '✗ ' + res.data);
				if (res.success) setTimeout(function () { location.reload(); }, 2000);
			});
		});

		/* ── DB cleanup ────────────────────────────────────────── */
		$('#jsd-run-cleanup').on('click', function () {
			if (!confirm('Run database cleanup?\n\nThis permanently deletes all post revisions, expired transients, spam comments, and auto-drafts.')) return;
			$(this).prop('disabled', true).text('Running…');
			$.post(ajaxurl, { action: 'jsd_db_cleanup', _ajax_nonce: nonce }, function (res) {
				$('#jsd-run-cleanup').prop('disabled', false).text('🗑 Run Cleanup');
				$('#jsd-cleanup-result').html(res.success
					? '<strong>Done:</strong><pre>' + res.data + '</pre>'
					: '<span style="color:#b91c1c"><strong>Error:</strong> ' + res.data + '</span>'
				).show();
			});
		});

	}(jQuery));
	</script>
	<?php
}
