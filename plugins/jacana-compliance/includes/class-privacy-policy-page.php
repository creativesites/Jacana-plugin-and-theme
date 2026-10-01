<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Jacana_Privacy_Policy_Page extends \Elementor\Widget_Base {

    public function get_name()       { return 'jacana_privacy_policy_page'; }
    public function get_title()      { return __( 'Privacy Policy Page', 'jacana-compliance' ); }
    public function get_icon()       { return 'eicon-lock-user'; }
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

        $this->add_control( 'company_name', array(
            'label'   => __( 'Company Name', 'jacana-compliance' ),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __( 'Jacana Safaris & Tours (Pty) Ltd', 'jacana-compliance' ),
        ) );

        $this->add_control( 'company_address', array(
            'label'   => __( 'Registered Address', 'jacana-compliance' ),
            'type'    => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __( 'Windhoek, Namibia', 'jacana-compliance' ),
        ) );

        $this->add_control( 'privacy_email', array(
            'label'   => __( 'Privacy Contact Email', 'jacana-compliance' ),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'privacy@jacanasafaristours.com',
        ) );

        $this->add_control( 'dpo_name', array(
            'label'       => __( 'Information Officer (POPIA)', 'jacana-compliance' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'Full name of your POPIA Information Officer',
        ) );

        $this->add_control( 'cookie_policy_url', array(
            'label'   => __( 'Cookie Policy Page URL', 'jacana-compliance' ),
            'type'    => \Elementor\Controls_Manager::URL,
            'default' => array( 'url' => '/cookie-policy' ),
        ) );

        $this->end_controls_section();
    }

    protected function render() {
        wp_enqueue_style(
            'jacana-privacy-policy',
            JACANA_COMP_URL . 'assets/css/privacy-policy.css',
            array(),
            JACANA_COMP_VERSION
        );

        $s             = $this->get_settings_for_display();
        $company       = esc_html( $s['company_name'] );
        $address       = esc_html( $s['company_address'] );
        $email         = sanitize_email( $s['privacy_email'] );
        $dpo           = esc_html( $s['dpo_name'] );
        $updated       = esc_html( $s['last_updated'] );
        $cookie_url    = esc_url( $s['cookie_policy_url']['url'] ?? '/cookie-policy' );
        ?>
        <div class="jacana-pp-root">

            <!-- Page Header -->
            <section class="jacana-pp-hero">
                <div class="jacana-pp-hero-inner">
                    <div class="jacana-pp-kicker"><?php esc_html_e( 'Legal', 'jacana-compliance' ); ?></div>
                    <h1 class="jacana-pp-title"><?php esc_html_e( 'Privacy Policy', 'jacana-compliance' ); ?></h1>
                    <p class="jacana-pp-intro"><?php esc_html_e( 'We are committed to protecting your personal information and your right to privacy. This policy explains what we collect, why, and how we use it.', 'jacana-compliance' ); ?></p>
                    <p class="jacana-pp-meta"><?php echo esc_html__( 'Last updated:', 'jacana-compliance' ) . ' ' . $updated; ?></p>
                </div>
                <div class="jacana-pp-stripe" aria-hidden="true"></div>
            </section>

            <!-- Policy Body -->
            <section class="jacana-pp-body">
                <div class="jacana-pp-body-inner">

                    <!-- Quick Links -->
                    <nav class="jacana-pp-toc" aria-label="<?php esc_attr_e( 'Table of contents', 'jacana-compliance' ); ?>">
                        <div class="jacana-pp-toc-label"><?php esc_html_e( 'Jump to section', 'jacana-compliance' ); ?></div>
                        <ul>
                            <li><a href="#pp-who-we-are"><?php esc_html_e( 'Who We Are', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#pp-data-we-collect"><?php esc_html_e( 'Data We Collect', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#pp-how-we-use"><?php esc_html_e( 'How We Use It', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#pp-who-we-share"><?php esc_html_e( 'Who We Share With', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#pp-retention"><?php esc_html_e( 'Data Retention', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#pp-your-rights"><?php esc_html_e( 'Your Rights', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#pp-cookies"><?php esc_html_e( 'Cookies', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#pp-children"><?php esc_html_e( 'Children', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#pp-contact"><?php esc_html_e( 'Contact Us', 'jacana-compliance' ); ?></a></li>
                        </ul>
                    </nav>

                    <!-- 1. Who We Are -->
                    <article class="jacana-pp-section" id="pp-who-we-are">
                        <h2><?php esc_html_e( '1. Who We Are', 'jacana-compliance' ); ?></h2>
                        <p><?php echo sprintf(
                            esc_html__( '%1$s is a tour operator based in %2$s. We design and operate safari tours, self-drive holidays, car rental services, and tailor-made travel itineraries across Namibia and southern Africa.', 'jacana-compliance' ),
                            '<strong>' . $company . '</strong>',
                            $address
                        ); ?></p>
                        <p><?php esc_html_e( 'When we refer to "Jacana", "we", "us", or "our" in this policy, we mean the company named above.', 'jacana-compliance' ); ?></p>
                        <?php if ( $dpo ) : ?>
                            <div class="jacana-pp-highlight">
                                <strong><?php esc_html_e( 'POPIA Information Officer:', 'jacana-compliance' ); ?></strong>
                                <?php echo esc_html( $dpo ); ?><br>
                                <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
                            </div>
                        <?php endif; ?>
                    </article>

                    <!-- 2. Data We Collect -->
                    <article class="jacana-pp-section" id="pp-data-we-collect">
                        <h2><?php esc_html_e( '2. Data We Collect', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'We only collect the personal data that is necessary to provide our services. This falls into two categories:', 'jacana-compliance' ); ?></p>

                        <h3><?php esc_html_e( 'Information you provide directly', 'jacana-compliance' ); ?></h3>
                        <ul>
                            <li><?php esc_html_e( 'Full name', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Email address', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Phone number', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Country of residence', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Travel dates, party size, budget range, and trip preferences', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Any additional information you provide in enquiry or booking forms', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Reviews and testimonials you submit voluntarily', 'jacana-compliance' ); ?></li>
                        </ul>

                        <h3><?php esc_html_e( 'Information collected automatically', 'jacana-compliance' ); ?></h3>
                        <ul>
                            <li><?php esc_html_e( 'IP address and approximate geographic location', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Browser type and version', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Pages visited, time on site, and referral source (via Google Analytics)', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'An anonymised visitor identifier stored in a browser cookie by our AI concierge (no personally identifiable data is stored in this cookie)', 'jacana-compliance' ); ?></li>
                        </ul>

                        <div class="jacana-pp-note">
                            <strong><?php esc_html_e( 'We do not collect', 'jacana-compliance' ); ?>:</strong>
                            <?php esc_html_e( 'payment card numbers (all payments are processed by third-party providers), passport numbers, or sensitive personal information unless legally required.', 'jacana-compliance' ); ?>
                        </div>
                    </article>

                    <!-- 3. How We Use It -->
                    <article class="jacana-pp-section" id="pp-how-we-use">
                        <h2><?php esc_html_e( '3. How We Use Your Data', 'jacana-compliance' ); ?></h2>
                        <div class="jacana-pp-purpose-grid">
                            <div class="jacana-pp-purpose-card">
                                <div class="jacana-pp-purpose-icon" aria-hidden="true">🗺</div>
                                <h3><?php esc_html_e( 'Tour & Booking Fulfilment', 'jacana-compliance' ); ?></h3>
                                <p><?php esc_html_e( 'To process your enquiry or booking, prepare itineraries, confirm reservations with lodges and transport operators, and provide pre-trip information.', 'jacana-compliance' ); ?></p>
                                <span class="jacana-pp-legal-basis"><?php esc_html_e( 'Legal basis: Contractual necessity', 'jacana-compliance' ); ?></span>
                            </div>
                            <div class="jacana-pp-purpose-card">
                                <div class="jacana-pp-purpose-icon" aria-hidden="true">✉</div>
                                <h3><?php esc_html_e( 'Communication', 'jacana-compliance' ); ?></h3>
                                <p><?php esc_html_e( 'To respond to your enquiries, send quotes, share travel documentation, and follow up after your trip.', 'jacana-compliance' ); ?></p>
                                <span class="jacana-pp-legal-basis"><?php esc_html_e( 'Legal basis: Contractual necessity / Legitimate interest', 'jacana-compliance' ); ?></span>
                            </div>
                            <div class="jacana-pp-purpose-card">
                                <div class="jacana-pp-purpose-icon" aria-hidden="true">📊</div>
                                <h3><?php esc_html_e( 'Website Analytics', 'jacana-compliance' ); ?></h3>
                                <p><?php esc_html_e( 'To understand how visitors use our website so we can improve it. Analytics data is anonymised and aggregated — we cannot identify you personally from analytics data.', 'jacana-compliance' ); ?></p>
                                <span class="jacana-pp-legal-basis"><?php esc_html_e( 'Legal basis: Legitimate interest (with cookie consent)', 'jacana-compliance' ); ?></span>
                            </div>
                            <div class="jacana-pp-purpose-card">
                                <div class="jacana-pp-purpose-icon" aria-hidden="true">⚖</div>
                                <h3><?php esc_html_e( 'Legal & Financial Compliance', 'jacana-compliance' ); ?></h3>
                                <p><?php esc_html_e( 'To maintain financial records, comply with Namibian tax law, and respond to lawful requests from authorities.', 'jacana-compliance' ); ?></p>
                                <span class="jacana-pp-legal-basis"><?php esc_html_e( 'Legal basis: Legal obligation', 'jacana-compliance' ); ?></span>
                            </div>
                        </div>
                    </article>

                    <!-- 4. Who We Share With -->
                    <article class="jacana-pp-section" id="pp-who-we-share">
                        <h2><?php esc_html_e( '4. Who We Share Your Data With', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'We share your personal data only where necessary to deliver your trip. We never sell your data to third parties.', 'jacana-compliance' ); ?></p>

                        <div class="jacana-pp-table-wrap">
                            <table class="jacana-pp-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Recipient', 'jacana-compliance' ); ?></th>
                                        <th><?php esc_html_e( 'Reason', 'jacana-compliance' ); ?></th>
                                        <th><?php esc_html_e( 'Data Shared', 'jacana-compliance' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php esc_html_e( 'Lodges & Camps', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'To confirm accommodation bookings', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'Name, dates, party size, dietary requirements', 'jacana-compliance' ); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e( 'Transport & Activity Operators', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'To arrange transfers and guided activities', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'Name, dates, contact number', 'jacana-compliance' ); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e( 'Airlines & Ground Handlers', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'To book domestic or charter flights where included', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'Name, nationality, travel dates', 'jacana-compliance' ); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e( 'Payment Processors', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'To securely process deposits and final payments', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'Name, email, booking amount', 'jacana-compliance' ); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e( 'Website Hosting & IT Providers', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'To operate and maintain the website infrastructure', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'Server logs including IP addresses', 'jacana-compliance' ); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e( 'Google LLC (Analytics)', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'To analyse website usage — only with your consent', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'Anonymised usage data and device information', 'jacana-compliance' ); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p><?php esc_html_e( 'All third-party suppliers are required to handle your data securely and only for the purpose stated above.', 'jacana-compliance' ); ?></p>
                    </article>

                    <!-- 5. Data Retention -->
                    <article class="jacana-pp-section" id="pp-retention">
                        <h2><?php esc_html_e( '5. How Long We Keep Your Data', 'jacana-compliance' ); ?></h2>
                        <div class="jacana-pp-table-wrap">
                            <table class="jacana-pp-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Data Type', 'jacana-compliance' ); ?></th>
                                        <th><?php esc_html_e( 'Retention Period', 'jacana-compliance' ); ?></th>
                                        <th><?php esc_html_e( 'Reason', 'jacana-compliance' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php esc_html_e( 'Booking records & correspondence', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( '7 years', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'Financial and legal compliance', 'jacana-compliance' ); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e( 'Enquiry / quote requests (not converted)', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( '2 years', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'Legitimate interest in future travel planning', 'jacana-compliance' ); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e( 'Submitted reviews', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'Until removed on request', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'Consent given at submission', 'jacana-compliance' ); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e( 'Website analytics data', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( '26 months', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'Google Analytics default; data is anonymised', 'jacana-compliance' ); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e( 'AI concierge session data', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( '90 days', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'Session context for trip planning continuity', 'jacana-compliance' ); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <!-- 6. Your Rights -->
                    <article class="jacana-pp-section" id="pp-your-rights">
                        <h2><?php esc_html_e( '6. Your Rights', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'Depending on where you are located, you may have the following rights regarding your personal data:', 'jacana-compliance' ); ?></p>
                        <div class="jacana-pp-rights-grid">
                            <?php
                            $rights = array(
                                array( 'Access',       'Request a copy of the personal data we hold about you.' ),
                                array( 'Rectification','Ask us to correct inaccurate or incomplete data.' ),
                                array( 'Erasure',      'Ask us to delete your data, subject to legal retention requirements.' ),
                                array( 'Restriction',  'Ask us to limit how we use your data while a dispute is resolved.' ),
                                array( 'Portability',  'Receive your data in a machine-readable format.' ),
                                array( 'Objection',    'Object to processing based on legitimate interest.' ),
                                array( 'Withdraw Consent', 'Withdraw consent for marketing or non-essential cookies at any time.' ),
                            );
                            foreach ( $rights as $right ) : ?>
                                <div class="jacana-pp-right-item">
                                    <strong><?php echo esc_html( $right[0] ); ?></strong>
                                    <p><?php echo esc_html( $right[1] ); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="jacana-pp-highlight">
                            <strong><?php esc_html_e( 'South African & Namibian residents', 'jacana-compliance' ); ?></strong><br>
                            <?php esc_html_e( 'Your rights are also protected under the Protection of Personal Information Act (POPIA). You may lodge a complaint with the Information Regulator of South Africa or the relevant authority in Namibia.', 'jacana-compliance' ); ?>
                        </div>
                        <p><?php echo sprintf(
                            /* translators: %s is the privacy contact email */
                            esc_html__( 'To exercise any of these rights, please email us at %s. We will respond within 30 days.', 'jacana-compliance' ),
                            '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>'
                        ); ?></p>
                    </article>

                    <!-- 7. Cookies -->
                    <article class="jacana-pp-section" id="pp-cookies">
                        <h2><?php esc_html_e( '7. Cookies', 'jacana-compliance' ); ?></h2>
                        <p><?php echo sprintf(
                            /* translators: %s is the cookie policy link */
                            esc_html__( 'We use cookies to operate the site, remember your preferences, and — with your consent — to analyse traffic. For a full list of every cookie we set, please see our %s.', 'jacana-compliance' ),
                            '<a href="' . $cookie_url . '">' . esc_html__( 'Cookie Policy', 'jacana-compliance' ) . '</a>'
                        ); ?></p>
                        <p><?php esc_html_e( 'You can manage or withdraw your cookie consent at any time using the button in our cookie banner.', 'jacana-compliance' ); ?></p>
                    </article>

                    <!-- 8. Children -->
                    <article class="jacana-pp-section" id="pp-children">
                        <h2><?php esc_html_e( '8. Children\'s Privacy', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'Our website is not directed at children under the age of 13. We do not knowingly collect personal data from children. If you believe we have inadvertently collected information from a child, please contact us immediately so we can delete it.', 'jacana-compliance' ); ?></p>
                    </article>

                    <!-- 9. Changes -->
                    <article class="jacana-pp-section">
                        <h2><?php esc_html_e( '9. Changes to This Policy', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'We may update this Privacy Policy from time to time. When we do, we will update the "Last updated" date at the top of this page. Material changes will be communicated via email to users with active bookings or enquiries.', 'jacana-compliance' ); ?></p>
                    </article>

                    <!-- 10. Contact -->
                    <article class="jacana-pp-section jacana-pp-section--contact" id="pp-contact">
                        <h2><?php esc_html_e( '10. Contact Us', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'If you have any questions about this Privacy Policy or how we handle your personal data, please get in touch:', 'jacana-compliance' ); ?></p>
                        <div class="jacana-pp-contact-block">
                            <strong><?php echo $company; ?></strong><br>
                            <?php echo nl2br( $address ); ?><br>
                            <?php esc_html_e( 'Email:', 'jacana-compliance' ); ?> <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
                        </div>
                    </article>

                </div>
            </section>

        </div>
        <?php
    }
}
