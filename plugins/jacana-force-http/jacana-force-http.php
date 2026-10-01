<?php
/**
 * Plugin Name: Jacana Force HTTP
 * Description: Forces HTTP across the site for staging environments with self-signed SSL / 503 issues. DISABLE before going live.
 * Version:     1.0.0
 * Author:      Jacana Safaris & Tours
 */

defined( 'ABSPATH' ) || exit;

/**
 * Strip https:// → http:// from any URL string.
 */
function jacana_to_http( $url ) {
    return str_replace( 'https://', 'http://', $url );
}

// Downgrade core URL functions.
add_filter( 'home_url',     'jacana_to_http' );
add_filter( 'site_url',     'jacana_to_http' );
add_filter( 'plugins_url',  'jacana_to_http' );
add_filter( 'content_url',  'jacana_to_http' );
add_filter( 'includes_url', 'jacana_to_http' );
add_filter( 'stylesheet_directory_uri', 'jacana_to_http' );
add_filter( 'template_directory_uri',   'jacana_to_http' );
add_filter( 'theme_root_uri',           'jacana_to_http' );
add_filter( 'upload_dir', function ( $dirs ) {
    foreach ( array( 'url', 'baseurl' ) as $key ) {
        if ( isset( $dirs[ $key ] ) ) {
            $dirs[ $key ] = jacana_to_http( $dirs[ $key ] );
        }
    }
    return $dirs;
} );

// Downgrade any redirect targets.
add_filter( 'wp_redirect', 'jacana_to_http', 1 );

// Prevent WordPress from auto-redirecting to HTTPS.
add_filter( 'redirect_canonical', function ( $redirect_url, $requested_url ) {
    if ( jacana_to_http( $redirect_url ) === jacana_to_http( $requested_url ) ) {
        return false;
    }
    return jacana_to_http( $redirect_url );
}, 10, 2 );

// Tell WordPress the request is plain HTTP so it does not inject https: URLs.
add_action( 'init', function () {
    $_SERVER['HTTPS']                    = '';
    $_SERVER['HTTP_X_FORWARDED_PROTO']   = 'http';
    $_SERVER['SERVER_PORT']              = '80';
}, 0 );

// Disable SSL enforcement for the admin area.
if ( ! defined( 'FORCE_SSL_ADMIN' ) ) {
    define( 'FORCE_SSL_ADMIN', false );
}

// Admin notice so no one forgets this plugin is active.
add_action( 'admin_notices', function () {
    ?>
    <div class="notice notice-warning">
        <p>
            <strong>Jacana Force HTTP</strong> is active &mdash;
            all URLs are being served over plain HTTP.
            <em>Deactivate this plugin before going live.</em>
        </p>
    </div>
    <?php
} );
