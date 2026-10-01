<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Jacana_Cookie_Policy_Page extends \Elementor\Widget_Base {

    public function get_name()       { return 'jacana_cookie_policy_page'; }
    public function get_title()      { return __( 'Cookie Policy Page', 'jacana-compliance' ); }
    public function get_icon()       { return 'eicon-info-circle-o'; }
    public function get_categories() { return array( 'jacana-luxe' ); }

    protected function register_controls() {
        $this->start_controls_section( 'section_meta', array(
            'label' => __( 'Policy Details', 'jacana-compliance' ),
        ) );

        $this->add_control( 'last_updated', array(
            'label'   => __( 'Last Updated', 'jacana-compliance' ),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __( 'April 2025', 'jacana-compliance' ),
        ) );

        $this->add_control( 'privacy_policy_url', array(
            'label'   => __( 'Privacy Policy Page URL', 'jacana-compliance' ),
            'type'    => \Elementor\Controls_Manager::URL,
            'default' => array( 'url' => '/privacy-policy' ),
        ) );

        $this->end_controls_section();
    }

    private function cookie_table( array $cookies ) {
        echo '<div class="jacana-cp-table-wrap"><table class="jacana-cp-table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Cookie Name', 'jacana-compliance' ) . '</th>';
        echo '<th>' . esc_html__( 'Purpose', 'jacana-compliance' ) . '</th>';
        echo '<th>' . esc_html__( 'Expiry', 'jacana-compliance' ) . '</th>';
        echo '<th>' . esc_html__( 'Set By', 'jacana-compliance' ) . '</th>';
        echo '</tr></thead><tbody>';
        foreach ( $cookies as $c ) {
            echo '<tr>';
            echo '<td><code>' . esc_html( $c[0] ) . '</code></td>';
            echo '<td>' . esc_html( $c[1] ) . '</td>';
            echo '<td>' . esc_html( $c[2] ) . '</td>';
            echo '<td>' . esc_html( $c[3] ) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    protected function render() {
        wp_enqueue_style(
            'jacana-cookie-policy',
            JACANA_COMP_URL . 'assets/css/cookie-policy.css',
            array(),
            JACANA_COMP_VERSION
        );

        $s           = $this->get_settings_for_display();
        $updated     = esc_html( $s['last_updated'] );
        $privacy_url = esc_url( $s['privacy_policy_url']['url'] ?? '/privacy-policy' );
        ?>
        <div class="jacana-cp-root">

            <!-- Header -->
            <section class="jacana-cp-hero">
                <div class="jacana-cp-hero-inner">
                    <div class="jacana-cp-kicker"><?php esc_html_e( 'Legal', 'jacana-compliance' ); ?></div>
                    <h1 class="jacana-cp-title"><?php esc_html_e( 'Cookie Policy', 'jacana-compliance' ); ?></h1>
                    <p class="jacana-cp-intro"><?php esc_html_e( 'This page lists every cookie set by this website, its purpose, and how long it is stored in your browser.', 'jacana-compliance' ); ?></p>
                    <p class="jacana-cp-meta"><?php echo esc_html__( 'Last updated:', 'jacana-compliance' ) . ' ' . $updated; ?></p>
                </div>
                <div class="jacana-cp-stripe" aria-hidden="true"></div>
            </section>

            <!-- Body -->
            <section class="jacana-cp-body">
                <div class="jacana-cp-body-inner">

                    <!-- What Are Cookies -->
                    <article class="jacana-cp-section">
                        <h2><?php esc_html_e( 'What Are Cookies?', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'Cookies are small text files placed on your device when you visit a website. They let the site remember information about your visit — for example, your preferred language, or whether you have already given consent — so you don\'t have to re-enter it every time.', 'jacana-compliance' ); ?></p>
                        <p><?php esc_html_e( 'Cookies cannot run programs or deliver viruses to your computer. They cannot access other data on your device.', 'jacana-compliance' ); ?></p>
                    </article>

                    <!-- Categories -->
                    <article class="jacana-cp-section">
                        <h2><?php esc_html_e( 'Cookie Categories We Use', 'jacana-compliance' ); ?></h2>
                        <div class="jacana-cp-category-grid">
                            <div class="jacana-cp-category-card jacana-cp-category--functional">
                                <div class="jacana-cp-category-badge"><?php esc_html_e( 'Always On', 'jacana-compliance' ); ?></div>
                                <h3><?php esc_html_e( 'Functional', 'jacana-compliance' ); ?></h3>
                                <p><?php esc_html_e( 'Essential for the site to work. These include cookies that remember your consent preferences, maintain your login session, and enable our AI concierge to recognise returning visitors. You cannot opt out of these.', 'jacana-compliance' ); ?></p>
                            </div>
                            <div class="jacana-cp-category-card jacana-cp-category--analytics">
                                <div class="jacana-cp-category-badge jacana-cp-category-badge--opt"><?php esc_html_e( 'Opt In', 'jacana-compliance' ); ?></div>
                                <h3><?php esc_html_e( 'Analytics', 'jacana-compliance' ); ?></h3>
                                <p><?php esc_html_e( 'Help us understand how visitors use the website by collecting anonymised, aggregated data. We use Google Analytics. No personally identifiable data is sent to Google.', 'jacana-compliance' ); ?></p>
                            </div>
                            <div class="jacana-cp-category-card jacana-cp-category--marketing">
                                <div class="jacana-cp-category-badge jacana-cp-category-badge--opt"><?php esc_html_e( 'Opt In', 'jacana-compliance' ); ?></div>
                                <h3><?php esc_html_e( 'Marketing', 'jacana-compliance' ); ?></h3>
                                <p><?php esc_html_e( 'Used by third-party advertising platforms (e.g. Meta Pixel, Google Ads) to show you relevant ads after you leave our site. We only activate these if you give explicit consent.', 'jacana-compliance' ); ?></p>
                            </div>
                        </div>
                    </article>

                    <!-- Functional Cookies Table -->
                    <article class="jacana-cp-section">
                        <h2><?php esc_html_e( 'Functional Cookies', 'jacana-compliance' ); ?></h2>
                        <?php $this->cookie_table( array(
                            array( 'jacana_consent',       'Stores your cookie consent preferences so we do not ask again.',     '1 year',        'Jacana Compliance' ),
                            array( 'jacana_visitor_key',   'An anonymous identifier for the AI trip-planning concierge.',         '1 year',        'Jacana CRM' ),
                            array( 'jacana_session_key',   'Tracks the current browser session for the AI concierge.',            'Session',       'Jacana CRM' ),
                            array( 'wordpress_logged_in_*','Keeps WordPress administrators logged in.',                           'Session / 14d', 'WordPress' ),
                            array( 'wp-settings-*',        'Stores WordPress admin interface preferences.',                       '1 year',        'WordPress' ),
                            array( 'PHPSESSID',             'Maintains the server-side PHP session.',                             'Session',       'PHP / Server' ),
                        ) ); ?>
                    </article>

                    <!-- Analytics Cookies Table -->
                    <article class="jacana-cp-section">
                        <h2><?php esc_html_e( 'Analytics Cookies', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'These cookies are only set if you accept Analytics cookies in the consent banner.', 'jacana-compliance' ); ?></p>
                        <?php $this->cookie_table( array(
                            array( '_ga',        'Google Analytics — distinguishes unique visitors using a randomly generated ID.', '2 years',   'Google Analytics' ),
                            array( '_gid',       'Google Analytics — identifies a browser session.',                                '24 hours',  'Google Analytics' ),
                            array( '_gat_gtag_*','Google Analytics — throttles the request rate to Google servers.',                '1 minute',  'Google Analytics' ),
                        ) ); ?>
                    </article>

                    <!-- Marketing Cookies Table -->
                    <article class="jacana-cp-section">
                        <h2><?php esc_html_e( 'Marketing Cookies', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'These cookies are only set if you accept Marketing cookies in the consent banner. If you have not installed the relevant third-party scripts, these cookies will not be present.', 'jacana-compliance' ); ?></p>
                        <?php $this->cookie_table( array(
                            array( '_fbp',    'Facebook Pixel — tracks visits across websites for ad targeting.', '3 months', 'Meta / Facebook' ),
                            array( '_gcl_au', 'Google Ads — stores and tracks conversions.',                      '3 months', 'Google Ads' ),
                        ) ); ?>
                    </article>

                    <!-- Managing Preferences -->
                    <article class="jacana-cp-section">
                        <h2><?php esc_html_e( 'Managing Your Cookie Preferences', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'You have several options for controlling cookies:', 'jacana-compliance' ); ?></p>
                        <ul>
                            <li><?php esc_html_e( 'Use the "Manage Preferences" button in our cookie banner to update your choices at any time.', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Clear cookies in your browser settings — most browsers allow you to delete or block cookies from specific websites.', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Opt out of Google Analytics across all sites using the Google Analytics Opt-out Browser Add-on.', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Use your browser\'s Private / Incognito mode — no cookies are retained after you close the window.', 'jacana-compliance' ); ?></li>
                        </ul>
                        <div class="jacana-cp-note">
                            <?php esc_html_e( 'Withdrawing consent or blocking functional cookies may prevent parts of the website from working correctly — in particular, the AI trip-planning concierge will not be able to remember your preferences between visits.', 'jacana-compliance' ); ?>
                        </div>
                    </article>

                    <!-- Audit Checklist -->
                    <article class="jacana-cp-section jacana-cp-section--checklist">
                        <h2><?php esc_html_e( 'Cookie Audit Checklist', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'Use this checklist to keep your site\'s cookie compliance up to date.', 'jacana-compliance' ); ?></p>
                        <ol class="jacana-cp-checklist">
                            <li>
                                <span class="jacana-cp-check" aria-hidden="true">✓</span>
                                <div>
                                    <strong><?php esc_html_e( 'Audit live cookies', 'jacana-compliance' ); ?></strong>
                                    <p><?php esc_html_e( 'Open your site in an incognito window, open DevTools → Application → Cookies, and compare the actual cookies set against the tables above.', 'jacana-compliance' ); ?></p>
                                </div>
                            </li>
                            <li>
                                <span class="jacana-cp-check" aria-hidden="true">✓</span>
                                <div>
                                    <strong><?php esc_html_e( 'Verify consent blocking', 'jacana-compliance' ); ?></strong>
                                    <p><?php esc_html_e( 'Confirm that Analytics and Marketing cookies are absent before consent is given and present only after "Accept" is clicked.', 'jacana-compliance' ); ?></p>
                                </div>
                            </li>
                            <li>
                                <span class="jacana-cp-check" aria-hidden="true">✓</span>
                                <div>
                                    <strong><?php esc_html_e( 'Review third-party scripts', 'jacana-compliance' ); ?></strong>
                                    <p><?php esc_html_e( 'Every new plugin or embed may set additional cookies. Re-run this audit whenever you add a new script or integration.', 'jacana-compliance' ); ?></p>
                                </div>
                            </li>
                            <li>
                                <span class="jacana-cp-check" aria-hidden="true">✓</span>
                                <div>
                                    <strong><?php esc_html_e( 'Google Tag Manager (if applicable)', 'jacana-compliance' ); ?></strong>
                                    <p><?php esc_html_e( 'If you use GTM, ensure all analytics and marketing tags are gated behind a consent trigger that reads the jacana_consent value.', 'jacana-compliance' ); ?></p>
                                </div>
                            </li>
                            <li>
                                <span class="jacana-cp-check" aria-hidden="true">✓</span>
                                <div>
                                    <strong><?php esc_html_e( 'Annual review', 'jacana-compliance' ); ?></strong>
                                    <p><?php esc_html_e( 'Schedule a cookie audit and policy review once a year — or after any major change to the site\'s scripts and plugins.', 'jacana-compliance' ); ?></p>
                                </div>
                            </li>
                        </ol>
                    </article>

                    <!-- Related Policy -->
                    <div class="jacana-cp-related">
                        <a href="<?php echo $privacy_url; ?>" class="jacana-cp-related-link">
                            <?php esc_html_e( '← Read our full Privacy Policy', 'jacana-compliance' ); ?>
                        </a>
                    </div>

                </div>
            </section>

        </div>
        <?php
    }
}
