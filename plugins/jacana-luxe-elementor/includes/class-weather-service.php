<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Jacana_Weather_Service
 *
 * Fetches current weather for jacana_destination posts via Open-Meteo
 * (no API key required). All HTTP calls are server-side and cached in
 * WordPress transients — nothing is exposed to the browser.
 *
 * Usage:
 *   $data = Jacana_Weather_Service::get_for_post( $post_id );
 *   $all  = Jacana_Weather_Service::get_for_all_destinations();
 */
final class Jacana_Weather_Service {

	const TRANSIENT_PREFIX   = 'jacana_weather_';
	const TRANSIENT_ALL      = 'jacana_weather_all';
	const DEFAULT_TTL        = 3600; // 1 hour
	const API_BASE           = 'https://api.open-meteo.com/v1/forecast';
	const REQUEST_TIMEOUT    = 5;    // seconds

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Return weather data for a single jacana_destination post.
	 * Returns null silently if GPS is missing or the API is unreachable.
	 *
	 * @param int $post_id
	 * @return array|null {
	 *   temp      int    °C
	 *   humidity  int    %
	 *   wind      int    km/h
	 *   uv        float
	 *   wmo       int    WMO weather code
	 *   label     string human-readable condition
	 *   icon      string icon key (e.g. 'clear', 'cloudy')
	 *   fetched   int    Unix timestamp of this fetch
	 * }
	 */
	public static function get_for_post( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return null;
		}

		$key    = self::TRANSIENT_PREFIX . $post_id;
		$cached = get_transient( $key );
		if ( $cached !== false ) {
			return $cached;
		}

		$gps_raw = get_post_meta( $post_id, '_jacana_dest_gps', true );
		if ( empty( $gps_raw ) ) {
			return null;
		}

		$coords = self::parse_gps( (string) $gps_raw );
		if ( ! $coords ) {
			return null;
		}

		$data = self::fetch_from_api( $coords['lat'], $coords['lon'] );
		if ( ! $data ) {
			return null;
		}

		$ttl = (int) get_option( 'jacana_weather_cache_ttl', self::DEFAULT_TTL );
		set_transient( $key, $data, max( 300, $ttl ) );

		return $data;
	}

	/**
	 * Return an array of weather data keyed by post ID for every published
	 * jacana_destination. Results are cached together under TRANSIENT_ALL.
	 *
	 * @return array<int, array|null>
	 */
	public static function get_for_all_destinations() {
		$cached = get_transient( self::TRANSIENT_ALL );
		if ( $cached !== false ) {
			return $cached;
		}

		$query = new WP_Query( [
			'post_type'      => 'jacana_destination',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
			'no_found_rows'  => true,
			'fields'         => 'ids',
		] );

		$results = [];
		foreach ( $query->posts as $id ) {
			$results[ (int) $id ] = self::get_for_post( (int) $id );
		}

		$ttl = (int) get_option( 'jacana_weather_cache_ttl', self::DEFAULT_TTL );
		set_transient( self::TRANSIENT_ALL, $results, max( 300, $ttl ) );

		return $results;
	}

	/**
	 * Parse a GPS string into lat/lon floats.
	 * Handles formats like:
	 *   "-22.5609, 17.0658"
	 *   "-18.8556, 16.3293 (Okaukuejo Entrance)"
	 *
	 * @param  string $gps
	 * @return array|null  ['lat' => float, 'lon' => float]
	 */
	public static function parse_gps( $gps ) {
		$gps = trim( (string) $gps );
		if ( ! preg_match( '/^([-\d.]+)\s*,\s*([-\d.]+)/', $gps, $m ) ) {
			return null;
		}
		$lat = (float) $m[1];
		$lon = (float) $m[2];
		if ( $lat < -90 || $lat > 90 || $lon < -180 || $lon > 180 ) {
			return null;
		}
		return [ 'lat' => $lat, 'lon' => $lon ];
	}

	/**
	 * Delete all weather transients. Useful during development or after
	 * a destination GPS update. Triggered via ?jacana_flush_weather=1 (admin only).
	 */
	public static function flush_all_transients() {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options}
				 WHERE option_name LIKE %s
				    OR option_name LIKE %s",
				'_transient_' . self::TRANSIENT_PREFIX . '%',
				'_transient_timeout_' . self::TRANSIENT_PREFIX . '%'
			)
		);
		delete_transient( self::TRANSIENT_ALL );
		delete_transient( '_transient_timeout_' . self::TRANSIENT_ALL );
	}

	// ── Internal ──────────────────────────────────────────────────────────────

	/**
	 * Call Open-Meteo and return normalised weather data.
	 *
	 * @param  float $lat
	 * @param  float $lon
	 * @return array|null
	 */
	public static function fetch_from_api( $lat, $lon ) {
		$url = add_query_arg( [
			'latitude'         => round( $lat, 4 ),
			'longitude'        => round( $lon, 4 ),
			'current'          => 'temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code,uv_index',
			'wind_speed_unit'  => 'kmh',
			'temperature_unit' => 'celsius',
			'timezone'         => 'Africa/Windhoek',
			'forecast_days'    => 1,
		], self::API_BASE );

		$response = wp_remote_get( $url, [
			'timeout'    => self::REQUEST_TIMEOUT,
			'user-agent' => 'Jacana-Safaris/1.0 (+https://jacana-safaris.com)',
		] );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $body['current'] ) || ! is_array( $body['current'] ) ) {
			return null;
		}

		$cur = $body['current'];
		$wmo = isset( $cur['weather_code'] ) ? (int) $cur['weather_code'] : 0;
		$cond = self::map_wmo_code( $wmo );

		return [
			'temp'     => isset( $cur['temperature_2m'] )        ? (int) round( (float) $cur['temperature_2m'] ) : null,
			'humidity' => isset( $cur['relative_humidity_2m'] )  ? (int) $cur['relative_humidity_2m']            : null,
			'wind'     => isset( $cur['wind_speed_10m'] )        ? (int) round( (float) $cur['wind_speed_10m'] ) : null,
			'uv'       => isset( $cur['uv_index'] )              ? round( (float) $cur['uv_index'], 1 )          : null,
			'wmo'      => $wmo,
			'label'    => $cond['label'],
			'icon'     => $cond['icon'],
			'fetched'  => time(),
		];
	}

	/**
	 * Map a WMO weather interpretation code to a human label and icon key.
	 * Subset tuned for Namibia (predominantly clear/dry/thunderstorm).
	 *
	 * @param  int $code
	 * @return array  ['label' => string, 'icon' => string]
	 */
	public static function map_wmo_code( $code ) {
		if ( $code === 0 )                       return [ 'label' => 'Clear Sky',      'icon' => 'clear'     ];
		if ( $code === 1 )                       return [ 'label' => 'Mainly Clear',   'icon' => 'mostly-clear' ];
		if ( $code === 2 )                       return [ 'label' => 'Partly Cloudy',  'icon' => 'partly-cloudy' ];
		if ( $code === 3 )                       return [ 'label' => 'Overcast',       'icon' => 'cloudy'    ];
		if ( in_array( $code, [ 45, 48 ], true ) ) return [ 'label' => 'Foggy',        'icon' => 'fog'       ];
		if ( $code >= 51 && $code <= 67 )        return [ 'label' => 'Drizzle / Rain', 'icon' => 'rain'      ];
		if ( $code >= 71 && $code <= 77 )        return [ 'label' => 'Snow',           'icon' => 'rain'      ];
		if ( $code >= 80 && $code <= 82 )        return [ 'label' => 'Rain Showers',   'icon' => 'showers'   ];
		if ( $code >= 95 && $code <= 99 )        return [ 'label' => 'Thunderstorm',   'icon' => 'storm'     ];
		return [ 'label' => 'Variable', 'icon' => 'unknown' ];
	}

	/**
	 * Return an inline SVG string for the given icon key.
	 * All icons are single-colour strokes that inherit `currentColor`,
	 * so they respond to CSS `color` declarations.
	 *
	 * @param  string $icon  Icon key from map_wmo_code()
	 * @return string        Safe SVG markup (no user input)
	 */
	public static function get_icon_svg( $icon ) {
		$icons = [

			'clear' =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
					<circle cx="20" cy="20" r="7"/>
					<line x1="20" y1="3"  x2="20" y2="7"/>
					<line x1="20" y1="33" x2="20" y2="37"/>
					<line x1="3"  y1="20" x2="7"  y2="20"/>
					<line x1="33" y1="20" x2="37" y2="20"/>
					<line x1="7.5"  y1="7.5"  x2="10.4" y2="10.4"/>
					<line x1="29.6" y1="29.6" x2="32.5" y2="32.5"/>
					<line x1="32.5" y1="7.5"  x2="29.6" y2="10.4"/>
					<line x1="10.4" y1="29.6" x2="7.5"  y2="32.5"/>
				</svg>',

			'mostly-clear' =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<circle cx="14" cy="13" r="5.5"/>
					<line x1="14" y1="2"    x2="14" y2="5.5"/>
					<line x1="14" y1="20.5" x2="14" y2="24"/>
					<line x1="3"  y1="13"   x2="6.5"  y2="13"/>
					<line x1="21.5" y1="13" x2="25"   y2="13"/>
					<line x1="6.1" y1="5.1"  x2="8.4"  y2="7.4"/>
					<line x1="19.6" y1="18.6" x2="21.9" y2="20.9"/>
					<line x1="21.9" y1="5.1"  x2="19.6" y2="7.4"/>
					<line x1="8.4"  y1="18.6" x2="6.1"  y2="20.9"/>
					<path d="M20 37 Q18 37 18 32 Q18 28 22 27 Q23 23 27 23 Q32 23 32 28 Q36 28 36 32 Q36 37 32 37 Z"/>
				</svg>',

			'partly-cloudy' =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<circle cx="13" cy="13" r="5.5"/>
					<line x1="13" y1="2"    x2="13" y2="5.5"/>
					<line x1="13" y1="20.5" x2="13" y2="24"/>
					<line x1="2"  y1="13"   x2="5.5"  y2="13"/>
					<line x1="20.5" y1="13" x2="24"   y2="13"/>
					<line x1="5.1" y1="5.1"  x2="7.4"  y2="7.4"/>
					<line x1="18.6" y1="18.6" x2="20.9" y2="20.9"/>
					<line x1="20.9" y1="5.1"  x2="18.6" y2="7.4"/>
					<line x1="7.4"  y1="18.6" x2="5.1"  y2="20.9"/>
					<path d="M11 38 Q8 38 8 32 Q8 26 14 25 Q15 19 21 19 Q28 19 28 25 Q34 25 34 31 Q34 38 28 38 Z"/>
				</svg>',

			'cloudy' =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M5 34 Q3 34 3 27 Q3 21 9 20 Q10 13 18 13 Q27 13 28 20 Q35 20 35 26 Q35 34 28 34 Z"/>
				</svg>',

			'fog' =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
					<line x1="6"  y1="14" x2="34" y2="14"/>
					<line x1="6"  y1="20" x2="34" y2="20"/>
					<line x1="6"  y1="26" x2="28" y2="26"/>
					<circle cx="20" cy="10" r="5" opacity="0.4"/>
				</svg>',

			'rain' =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M8 22 a9 9 0 0 1 0-16 7 7 0 0 1 14 2 6 6 0 0 1 8 8 H8z"/>
					<line x1="14" y1="30" x2="12" y2="36"/>
					<line x1="20" y1="30" x2="18" y2="36"/>
					<line x1="26" y1="30" x2="24" y2="36"/>
				</svg>',

			'showers' =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M8 22 a9 9 0 0 1 0-16 7 7 0 0 1 14 2 6 6 0 0 1 8 8 H8z"/>
					<line x1="12" y1="29" x2="16" y2="36"/>
					<line x1="20" y1="29" x2="20" y2="36"/>
					<line x1="28" y1="29" x2="24" y2="36"/>
				</svg>',

			'storm' =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M8 22 a9 9 0 0 1 0-16 7 7 0 0 1 14 2 6 6 0 0 1 8 8 H8z"/>
					<polyline points="22,27 17,34 22,34 17,41"/>
				</svg>',

			'unknown' =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
					<circle cx="20" cy="20" r="16"/>
					<path d="M15 16a5 5 0 0 1 10 0c0 3-5 5-5 9"/>
					<circle cx="20" cy="30" r="1.2" fill="currentColor" stroke="none"/>
				</svg>',
		];

		return $icons[ $icon ] ?? $icons['unknown'];
	}

	/**
	 * Return all icon SVG strings as an associative array keyed by icon name.
	 * Useful for passing the full icon set to JavaScript via wp_json_encode().
	 *
	 * @return array<string, string>
	 */
	public static function get_icon_map() {
		$keys = [ 'clear', 'mostly-clear', 'partly-cloudy', 'cloudy', 'fog', 'rain', 'showers', 'storm', 'unknown' ];
		$map  = [];
		foreach ( $keys as $key ) {
			$map[ $key ] = self::get_icon_svg( $key );
		}
		return $map;
	}

	/**
	 * Return the UV risk label for a given UV index value.
	 *
	 * @param  float|null $uv
	 * @return string
	 */
	public static function uv_label( $uv ) {
		if ( $uv === null ) return '—';
		if ( $uv < 3  ) return 'Low';
		if ( $uv < 6  ) return 'Moderate';
		if ( $uv < 8  ) return 'High';
		if ( $uv < 11 ) return 'Very High';
		return 'Extreme';
	}
}

// Dev flush hook — admin only, ?jacana_flush_weather=1
add_action( 'init', function () {
	if ( ! isset( $_GET['jacana_flush_weather'] ) ) return;
	if ( ! current_user_can( 'manage_options' ) )    return;
	Jacana_Weather_Service::flush_all_transients();
	wp_die( 'Weather cache cleared.', 'Jacana Weather', [ 'response' => 200 ] );
} );
