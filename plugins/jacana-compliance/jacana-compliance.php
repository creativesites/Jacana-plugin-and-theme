<?php
/**
 * Plugin Name:  Jacana Compliance
 * Description:  Privacy Policy & Cookie Policy pages plus a GDPR/POPIA-ready Consent Manager for Jacana Safaris & Tours.
 * Version:      1.0.1
 * Author:       Jacana Safaris & Tours
 * Text Domain:  jacana-compliance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'JACANA_COMP_VERSION', '1.0.1' );
define( 'JACANA_COMP_DIR',     plugin_dir_path( __FILE__ ) );
define( 'JACANA_COMP_URL',     plugin_dir_url( __FILE__ ) );

final class Jacana_Compliance {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_enqueue_scripts',              array( $this, 'enqueue_frontend' ) );
        add_action( 'wp_footer',                       array( $this, 'render_consent_banner' ), 99 );
        add_action( 'elementor/widgets/register',      array( $this, 'register_widgets' ) );
        add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widgets_legacy' ) );
        add_action( 'admin_menu',                      array( $this, 'register_admin_pages' ) );
    }

    public function enqueue_frontend() {
        wp_enqueue_style(
            'jacana-consent-banner',
            JACANA_COMP_URL . 'assets/css/consent-banner.css',
            array(),
            JACANA_COMP_VERSION
        );
        wp_enqueue_script(
            'jacana-consent-manager',
            JACANA_COMP_URL . 'assets/js/consent-manager.js',
            array(),
            JACANA_COMP_VERSION,
            array( 'strategy' => 'defer', 'in_footer' => true )
        );
    }

    public function render_consent_banner() {
        require_once JACANA_COMP_DIR . 'includes/class-consent-manager.php';
        Jacana_Consent_Manager::render_banner();
    }

    public function register_widgets( $widgets_manager ) {
        $this->load_widget_classes();
        $widgets_manager->register( new Jacana_Privacy_Policy_Page() );
        $widgets_manager->register( new Jacana_Cookie_Policy_Page() );
        $widgets_manager->register( new Jacana_Terms_Of_Service_Page() );
    }

    public function register_widgets_legacy( $widgets_manager ) {
        $this->load_widget_classes();
        $widgets_manager->register_widget_type( new Jacana_Privacy_Policy_Page() );
        $widgets_manager->register_widget_type( new Jacana_Cookie_Policy_Page() );
        $widgets_manager->register_widget_type( new Jacana_Terms_Of_Service_Page() );
    }

    private function load_widget_classes() {
        static $loaded = false;
        if ( $loaded ) return;
        $loaded = true;
        require_once JACANA_COMP_DIR . 'includes/class-privacy-policy-page.php';
        require_once JACANA_COMP_DIR . 'includes/class-cookie-policy-page.php';
        require_once JACANA_COMP_DIR . 'includes/class-terms-of-service-page.php';
    }

    public function register_admin_pages() {
        add_menu_page(
            __( 'Jacana Compliance', 'jacana-compliance' ),
            __( 'Compliance', 'jacana-compliance' ),
            'manage_options',
            'jacana-compliance',
            array( $this, 'render_admin_dashboard' ),
            'dashicons-shield',
            58
        );
        add_submenu_page(
            'jacana-compliance',
            __( 'Cookie Audit', 'jacana-compliance' ),
            __( 'Cookie Audit', 'jacana-compliance' ),
            'manage_options',
            'jacana-compliance-cookies',
            array( $this, 'render_cookie_audit' )
        );
    }

    public function render_admin_dashboard() {
        $audited = (array) get_option( 'jacana_compliance_audited_cookies', array() );
        $checklist = (array) get_option( 'jacana_compliance_checklist', array() );

        if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
            check_admin_referer( 'jacana_compliance_save' );
            $checklist = array_map( 'sanitize_key', (array) ( $_POST['checklist'] ?? array() ) );
            update_option( 'jacana_compliance_checklist', $checklist );
        }

        $tasks = array(
            'audit_cookies'     => 'Complete cookie audit in the Cookie Audit tab',
            'add_privacy_page'  => 'Create a Privacy Policy page using the Elementor widget',
            'add_cookie_page'   => 'Create a Cookie Policy page using the Elementor widget',
            'test_banner'       => 'Test consent banner in a private/incognito window',
            'add_popia_notice'  => 'Add POPIA information officer name to the privacy policy',
            'review_forms'      => 'Review all forms — ensure they only collect necessary data',
            'retention_policy'  => 'Set and document data retention periods',
            'ga_consent_block'  => 'Confirm Google Analytics only fires after analytics consent',
            'link_footer'       => 'Add Privacy Policy and Cookie Policy links to the site footer',
            'review_annually'   => 'Schedule an annual compliance review',
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Jacana Compliance — Dashboard', 'jacana-compliance' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Track your privacy and cookie compliance tasks. Check items off as you complete them.', 'jacana-compliance' ); ?></p>

            <form method="post">
                <?php wp_nonce_field( 'jacana_compliance_save' ); ?>
                <table class="wp-list-table widefat fixed striped" style="max-width:720px;margin-top:20px;">
                    <thead>
                        <tr>
                            <th style="width:40px;">Done</th>
                            <th>Task</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $tasks as $key => $label ) : ?>
                            <tr>
                                <td><input type="checkbox" name="checklist[]" value="<?php echo esc_attr( $key ); ?>"<?php checked( in_array( $key, $checklist, true ) ); ?>></td>
                                <td><?php echo esc_html( $label ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Progress', 'jacana-compliance' ); ?></button></p>
            </form>

            <hr>
            <h2><?php esc_html_e( 'Plugin Overview', 'jacana-compliance' ); ?></h2>
            <ul style="list-style:disc;padding-left:20px;">
                <li><?php esc_html_e( 'Three Elementor widgets registered: Privacy Policy Page, Cookie Policy Page, Terms of Service Page', 'jacana-compliance' ); ?></li>
                <li><?php esc_html_e( 'Cookie consent banner renders in the footer on all front-end pages', 'jacana-compliance' ); ?></li>
                <li><?php esc_html_e( 'Consent stored in localStorage under key jacana_consent', 'jacana-compliance' ); ?></li>
                <li><?php esc_html_e( 'Custom event jacana:consent:update fires whenever preferences change', 'jacana-compliance' ); ?></li>
                <li><?php esc_html_e( 'Banner is hidden for logged-in WordPress users', 'jacana-compliance' ); ?></li>
            </ul>
        </div>
        <?php
    }

    public function render_cookie_audit() {
        $known_cookies = array(
            'Functional' => array(
                array(
                    'name'    => 'jacana_consent',
                    'purpose' => 'Stores the visitor\'s cookie consent preferences.',
                    'expiry'  => '1 year',
                    'set_by'  => 'Jacana Compliance plugin',
                ),
                array(
                    'name'    => 'jacana_visitor_key',
                    'purpose' => 'Anonymous identifier used by the AI concierge to recognise returning visitors.',
                    'expiry'  => '1 year',
                    'set_by'  => 'Jacana CRM plugin',
                ),
                array(
                    'name'    => 'jacana_session_key',
                    'purpose' => 'Tracks the current browser session for the AI concierge.',
                    'expiry'  => 'Session',
                    'set_by'  => 'Jacana CRM plugin',
                ),
                array(
                    'name'    => 'wordpress_logged_in_*',
                    'purpose' => 'Keeps WordPress users logged in across page loads.',
                    'expiry'  => 'Session / 2 weeks',
                    'set_by'  => 'WordPress core',
                ),
                array(
                    'name'    => 'wp-settings-*',
                    'purpose' => 'Stores WordPress admin UI preferences.',
                    'expiry'  => '1 year',
                    'set_by'  => 'WordPress core',
                ),
                array(
                    'name'    => 'PHPSESSID',
                    'purpose' => 'Maintains the server-side PHP session.',
                    'expiry'  => 'Session',
                    'set_by'  => 'PHP / Server',
                ),
            ),
            'Analytics' => array(
                array(
                    'name'    => '_ga',
                    'purpose' => 'Google Analytics — distinguishes unique visitors.',
                    'expiry'  => '2 years',
                    'set_by'  => 'Google Analytics',
                ),
                array(
                    'name'    => '_gid',
                    'purpose' => 'Google Analytics — identifies a session.',
                    'expiry'  => '24 hours',
                    'set_by'  => 'Google Analytics',
                ),
                array(
                    'name'    => '_gat_gtag_*',
                    'purpose' => 'Google Analytics — throttles request rate.',
                    'expiry'  => '1 minute',
                    'set_by'  => 'Google Analytics',
                ),
            ),
            'Marketing' => array(
                array(
                    'name'    => '_fbp',
                    'purpose' => 'Facebook Pixel — tracks visits across websites for ad targeting.',
                    'expiry'  => '3 months',
                    'set_by'  => 'Meta / Facebook (if Pixel is installed)',
                ),
                array(
                    'name'    => '_gcl_au',
                    'purpose' => 'Google Ads — conversion tracking.',
                    'expiry'  => '3 months',
                    'set_by'  => 'Google Ads (if tag is installed)',
                ),
            ),
        );

        $audited = (array) get_option( 'jacana_compliance_audited_cookies', array() );

        if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
            check_admin_referer( 'jacana_cookie_audit_save' );
            $audited = array_map( 'sanitize_text_field', (array) ( $_POST['audited'] ?? array() ) );
            update_option( 'jacana_compliance_audited_cookies', $audited );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Cookie Audit', 'jacana-compliance' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Review every cookie the site sets. Check "Verified" once you have confirmed the cookie is present and its details are accurate.', 'jacana-compliance' ); ?></p>
            <form method="post">
                <?php wp_nonce_field( 'jacana_cookie_audit_save' ); ?>
                <?php foreach ( $known_cookies as $category => $cookies ) : ?>
                    <h2><?php echo esc_html( $category ); ?></h2>
                    <table class="wp-list-table widefat fixed striped" style="max-width:900px;margin-bottom:28px;">
                        <thead>
                            <tr>
                                <th style="width:40px;">Verified</th>
                                <th style="width:200px;">Cookie Name</th>
                                <th>Purpose</th>
                                <th style="width:100px;">Expiry</th>
                                <th style="width:200px;">Set By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $cookies as $c ) : ?>
                                <tr>
                                    <td><input type="checkbox" name="audited[]" value="<?php echo esc_attr( $c['name'] ); ?>"<?php checked( in_array( $c['name'], $audited, true ) ); ?>></td>
                                    <td><code><?php echo esc_html( $c['name'] ); ?></code></td>
                                    <td><?php echo esc_html( $c['purpose'] ); ?></td>
                                    <td><?php echo esc_html( $c['expiry'] ); ?></td>
                                    <td><?php echo esc_html( $c['set_by'] ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
                <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Audit', 'jacana-compliance' ); ?></button></p>
            </form>

            <hr>
            <h2><?php esc_html_e( 'Next Steps', 'jacana-compliance' ); ?></h2>
            <ol style="list-style:decimal;padding-left:20px;max-width:720px;">
                <li><?php esc_html_e( 'Open your site in an incognito window and use browser DevTools (Application → Cookies) to compare the actual cookies set against this list.', 'jacana-compliance' ); ?></li>
                <li><?php esc_html_e( 'Add any undiscovered cookies to this list and update the Cookie Policy page widget accordingly.', 'jacana-compliance' ); ?></li>
                <li><?php esc_html_e( 'Confirm that Analytics and Marketing cookies are only set AFTER the visitor accepts them in the consent banner.', 'jacana-compliance' ); ?></li>
                <li><?php esc_html_e( 'If you add Google Tag Manager, route all analytics/marketing tags through a consent trigger.', 'jacana-compliance' ); ?></li>
                <li><?php esc_html_e( 'Repeat this audit whenever you install a new plugin or add a third-party script.', 'jacana-compliance' ); ?></li>
            </ol>
        </div>
        <?php
    }
}

Jacana_Compliance::instance();
