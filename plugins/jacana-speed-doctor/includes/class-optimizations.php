<?php
defined( 'ABSPATH' ) || exit;

class Jacana_Speed_Optimizations {

	const OPTS = [
		'disable_emojis',
		'clean_head',
		'remove_query_strings',
		'combine_widget_css',
		'defer_scripts',
		'smart_video_loading',
		'heartbeat_control',
		'disable_xmlrpc',
		'preconnect_fonts',
		'gzip_output',
		'lazy_load_images',
		'limit_revisions',
	];

	const LABELS = [
		'disable_emojis'     => 'Disable WordPress Emojis',
		'clean_head'         => 'Clean Up <head> Tag',
		'remove_query_strings' => 'Remove Query Strings from Static Assets',
		'combine_widget_css' => 'Combine 63 Widget CSS Files into One',
		'defer_scripts'      => 'Defer Non-Essential JavaScript',
		'smart_video_loading'=> 'Smart Video Loading (delay + skip on mobile)',
		'heartbeat_control'  => 'Slow Down WP Heartbeat API',
		'disable_xmlrpc'     => 'Disable XML-RPC',
		'preconnect_fonts'   => 'Add Preconnect Hints for Google Fonts',
		'gzip_output'        => 'Enable PHP GZIP Compression',
		'lazy_load_images'   => 'Enforce Lazy Loading on All Images',
		'limit_revisions'    => 'Limit Post Revisions to 3',
	];

	const DESCRIPTIONS = [
		'disable_emojis'     => 'Removes the emoji JS file and DNS-prefetch hint WordPress injects on every page — saves one extra round-trip.',
		'clean_head'         => 'Strips generator meta, RSD link, wlwmanifest link, adjacent-posts links, and shortlink — none are needed on the front end.',
		'remove_query_strings' => 'Removes ?ver= from CSS/JS URLs so reverse proxies and CDNs can cache them.',
		'combine_widget_css' => 'Merges all 63 widget CSS files into one cached file in /uploads/jsd-css-cache/, cutting style requests from 63+ to 1. Cache is auto-invalidated when any widget CSS changes.',
		'defer_scripts'      => 'Adds defer to non-essential <script> tags, unblocking HTML parsing. jQuery and all Elementor handles are excluded.',
		'smart_video_loading'=> 'RECOMMENDED for this site. Delays hero video loading until after page load; skips video entirely on mobile (< 540 px) and slow connections (2G/save-data). Visitors see the poster image immediately. Addresses the 30–36 MB background videos.',
		'heartbeat_control'  => 'Increases the WP Heartbeat interval from 15 s to 60 s, reducing background AJAX load.',
		'disable_xmlrpc'     => 'Disables XML-RPC and removes the X-Pingback header if you are not using remote publishing or Jetpack.',
		'preconnect_fonts'   => 'Injects <link rel="preconnect"> for fonts.googleapis.com and fonts.gstatic.com early in <head>.',
		'gzip_output'        => 'Enables PHP ob_gzhandler if the server is not already compressing output. No effect if mod_deflate is active.',
		'lazy_load_images'   => 'Ensures every <img> tag in page output has loading="lazy" via output buffering.',
		'limit_revisions'    => 'Caps post revisions at 3. Existing revisions are not deleted — use the Database tab to purge them.',
	];

	public static function boot( array $opts ): void {
		foreach ( self::OPTS as $key ) {
			if ( ! empty( $opts[ $key ] ) && method_exists( self::class, $key ) ) {
				self::$key();
			}
		}
	}

	// ── 1. Disable emojis ─────────────────────────────────────────────────────

	public static function disable_emojis(): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', fn( $p ) => array_diff( $p ?? [], [ 'wpemoji' ] ) );
		add_filter( 'wp_resource_hints', function ( $urls, $type ) {
			if ( 'dns-prefetch' === $type ) {
				$urls = array_filter( $urls, fn( $u ) => false === strpos( $u, 'emoji' ) );
			}
			return $urls;
		}, 10, 2 );
	}

	// ── 2. Clean head ─────────────────────────────────────────────────────────

	public static function clean_head(): void {
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
		add_filter( 'the_generator', '__return_empty_string' );
	}

	// ── 3. Remove query strings ───────────────────────────────────────────────

	public static function remove_query_strings(): void {
		add_filter( 'style_loader_src',  [ self::class, '_strip_ver' ], 10 );
		add_filter( 'script_loader_src', [ self::class, '_strip_ver' ], 10 );
	}

	public static function _strip_ver( string $src ): string {
		return $src ? remove_query_arg( 'ver', $src ) : $src;
	}

	// ── 4. Combine widget CSS ─────────────────────────────────────────────────
	// Merges all jacana-luxe-* stylesheet handles into one cached file.

	public static function combine_widget_css(): void {
		add_action( 'wp_enqueue_scripts', function () {
			global $wp_styles;
			if ( ! isset( $wp_styles->registered ) ) return;

			$handles = [];
			$paths   = [];

			foreach ( $wp_styles->registered as $handle => $style ) {
				if ( strpos( $handle, 'jacana-luxe-' ) !== 0 ) continue;
				if ( empty( $style->src ) ) continue;
				$path = self::_url_to_path( $style->src );
				if ( ! $path || ! file_exists( $path ) ) continue;
				$handles[] = $handle;
				$paths[]   = $path;
			}

			if ( count( $handles ) < 2 ) return;

			// Cache key = hash of all file paths + mtimes.
			$mtimes   = array_map( 'filemtime', $paths );
			$hash     = substr( md5( implode( ',', $paths ) . implode( ',', $mtimes ) ), 0, 12 );

			$upload   = wp_upload_dir();
			$dir      = $upload['basedir'] . '/jsd-css-cache/';
			$file     = $dir . 'widgets-' . $hash . '.css';
			$url      = $upload['baseurl'] . '/jsd-css-cache/widgets-' . $hash . '.css';

			if ( ! file_exists( $file ) ) {
				// Purge stale combined files.
				wp_mkdir_p( $dir );
				foreach ( glob( $dir . 'widgets-*.css' ) ?: [] as $old ) {
					@unlink( $old );
				}

				$css = "/* Jacana Speed Doctor — Combined Widget CSS [{$hash}] */\n";
				foreach ( $paths as $i => $p ) {
					$css .= "\n/* --- " . basename( $p ) . " --- */\n";
					$css .= file_get_contents( $p ) . "\n";
				}
				file_put_contents( $file, $css );
			}

			// Swap out individual enqueues.
			foreach ( $handles as $h ) {
				wp_dequeue_style( $h );
			}
			wp_enqueue_style( 'jsd-widgets-combined', $url, [], null );
		}, 9999 );
	}

	// ── 5. Defer scripts ──────────────────────────────────────────────────────

	public static function defer_scripts(): void {
		add_filter( 'script_loader_tag', function ( $tag, $handle ) {
			$skip = [ 'jquery', 'jquery-core', 'jquery-migrate' ];
			if ( in_array( $handle, $skip, true ) ) return $tag;
			if ( false !== strpos( $handle, 'elementor' ) ) return $tag;
			if ( false !== strpos( $tag, 'defer' ) || false !== strpos( $tag, 'async' ) ) return $tag;
			return str_replace( ' src=', ' defer src=', $tag );
		}, 10, 2 );
	}

	// ── 6. Smart video loading ────────────────────────────────────────────────
	// Delays background video loading until after page load.
	// Skips video on mobile (≤ 540 px) and slow/save-data connections.

	public static function smart_video_loading(): void {
		if ( is_admin() ) return;

		// Move video src → data-video-src and disable Elementor background video autoplay on mobile
		add_action( 'template_redirect', function () {
			ob_start( function ( $html ) {
				// 1. Prevent Elementor background video from streaming on mobile
				$html = str_replace(
					'"background_play_on_mobile":"yes"',
					'"background_play_on_mobile":"no"',
					$html
				);

				// 2. Intercept video tags
				return preg_replace_callback(
					'/<video(\s[^>]*)>(.*?)<\/video>/is',
					function ( $m ) {
						$attrs = $m[1];
						$inner = $m[2];

						// Force preload="none".
						if ( preg_match( '/\bpreload\s*=\s*["\'][^"\']*["\']/i', $attrs ) ) {
							$attrs = preg_replace( '/\bpreload\s*=\s*["\'][^"\']*["\']/i', 'preload="none"', $attrs );
						} else {
							$attrs .= ' preload="none"';
						}

						// Stash src in data attribute.
						$attrs = preg_replace_callback(
							'/\bsrc\s*=\s*"([^"]*)"/i',
							function ( $sm ) {
								return 'data-video-src="' . esc_attr( $sm[1] ) . '"';
							},
							$attrs
						);

						// Also handle nested <source> tags
						$inner = preg_replace_callback(
							'/<source(\s[^>]*)\bsrc\s*=\s*"([^"]*)"/i',
							function ( $sm ) {
								return '<source' . $sm[1] . ' data-src="' . esc_attr( $sm[2] ) . '"';
							},
							$inner
						);

						return '<video' . $attrs . '>' . $inner . '</video>';
					},
					$html
				);
			} );
		} );

		// Inject the JS loader in the footer.
		add_action( 'wp_footer', function () {
			?>
			<script>
			(function () {
				var conn    = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
				var isSlow  = conn && (conn.saveData || ['slow-2g', '2g'].indexOf(conn.effectiveType) !== -1);
				var isTiny  = window.matchMedia('(max-width: 768px)').matches;

				function loadVideos() {
					if (isSlow || isTiny) {
						// Mobile / slow connection — keep poster image, don't download heavy videos.
						document.querySelectorAll('.elementor-background-video-container video, video').forEach(function(v) {
							v.preload = 'none';
							v.pause();
						});
						return;
					}

					document.querySelectorAll('video[data-video-src]').forEach(function (v) {
						var src = v.getAttribute('data-video-src');
						if (!src) return;
						v.setAttribute('src', src);
						v.setAttribute('preload', 'auto');
						v.load();
						v.play().catch(function () {});
					});

					document.querySelectorAll('video source[data-src]').forEach(function (s) {
						var src = s.getAttribute('data-src');
						if (!src) return;
						s.setAttribute('src', src);
						var v = s.closest('video');
						if (v) {
							v.load();
							v.play().catch(function () {});
						}
					});
				}

				if (document.readyState === 'complete') {
					loadVideos();
				} else {
					window.addEventListener('load', loadVideos);
				}
			}());
			</script>
			<?php
		}, 99 );
	}

	// ── 7. Heartbeat control ───────────────────────────────────────────────────

	public static function heartbeat_control(): void {
		add_filter( 'heartbeat_settings', function ( $s ) {
			$s['interval'] = 60;
			return $s;
		} );
	}

	// ── 8. Disable XML-RPC ────────────────────────────────────────────────────

	public static function disable_xmlrpc(): void {
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'wp_headers', function ( $h ) { unset( $h['X-Pingback'] ); return $h; } );
	}

	// ── 9. Preconnect fonts ────────────────────────────────────────────────────

	public static function preconnect_fonts(): void {
		add_action( 'wp_head', function () {
			echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
			echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
		}, 1 );
	}

	// ── 10. PHP GZIP output ────────────────────────────────────────────────────

	public static function gzip_output(): void {
		if ( is_admin() || headers_sent() ) return;
		if ( ! extension_loaded( 'zlib' ) || ini_get( 'zlib.output_compression' ) ) return;
		if ( ob_get_level() === 0 ) {
			add_action( 'init', function () { ob_start( 'ob_gzhandler' ); }, 0 );
		}
	}

	// ── 11. Lazy load images ───────────────────────────────────────────────────

	public static function lazy_load_images(): void {
		add_action( 'template_redirect', function () {
			ob_start( function ( $html ) {
				return preg_replace_callback(
					'/<img(\s[^>]*)>/i',
					function ( $m ) {
						if ( false !== strpos( $m[1], 'loading=' ) ) return $m[0];
						return '<img loading="lazy"' . $m[1] . '>';
					},
					$html
				);
			} );
		} );
	}

	// ── 12. Limit revisions ────────────────────────────────────────────────────

	public static function limit_revisions(): void {
		if ( ! defined( 'WP_POST_REVISIONS' ) ) {
			define( 'WP_POST_REVISIONS', 3 );
		}
	}

	// ── Helpers ────────────────────────────────────────────────────────────────

	private static function _url_to_path( string $url ): string {
		$url = strtok( $url, '?' ); // strip query string
		if ( strpos( $url, home_url() ) === 0 ) {
			return ABSPATH . ltrim( substr( $url, strlen( home_url() ) ), '/' );
		}
		if ( strpos( $url, '/' ) === 0 ) {
			return ABSPATH . ltrim( $url, '/' );
		}
		return '';
	}

	// ── CSS cache invalidation (called from admin AJAX) ────────────────────────

	public static function clear_css_cache(): bool {
		$upload = wp_upload_dir();
		$dir    = $upload['basedir'] . '/jsd-css-cache/';
		if ( ! is_dir( $dir ) ) return true;
		$deleted = true;
		foreach ( glob( $dir . '*.css' ) ?: [] as $f ) {
			if ( ! @unlink( $f ) ) $deleted = false;
		}
		return $deleted;
	}
}
