<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Jacana_Terms_Of_Service_Page extends \Elementor\Widget_Base {

    public function get_name()       { return 'jacana_terms_of_service_page'; }
    public function get_title()      { return __( 'Terms of Service Page', 'jacana-compliance' ); }
    public function get_icon()       { return 'eicon-document-file'; }
    public function get_categories() { return array( 'jacana-luxe' ); }

    protected function register_controls() {
        $this->start_controls_section( 'section_meta', array(
            'label' => __( 'Terms Details', 'jacana-compliance' ),
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

        $this->add_control( 'contact_email', array(
            'label'   => __( 'Contact Email', 'jacana-compliance' ),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'info@jacanasafaristours.com',
        ) );

        $this->add_control( 'booking_email', array(
            'label'   => __( 'Booking Email', 'jacana-compliance' ),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'booking@jacanasafaristours.com',
        ) );

        $this->add_control( 'privacy_policy_url', array(
            'label'   => __( 'Privacy Policy URL', 'jacana-compliance' ),
            'type'    => \Elementor\Controls_Manager::URL,
            'default' => array( 'url' => '/privacy-policy' ),
        ) );

        $this->end_controls_section();
    }

    protected function render() {
        wp_enqueue_style(
            'jacana-terms-of-service',
            JACANA_COMP_URL . 'assets/css/terms-of-service.css',
            array(),
            JACANA_COMP_VERSION
        );

        $s           = $this->get_settings_for_display();
        $company     = esc_html( $s['company_name'] );
        $address     = esc_html( $s['company_address'] );
        $email       = sanitize_email( $s['contact_email'] );
        $booking_email = sanitize_email( $s['booking_email'] );
        $updated     = esc_html( $s['last_updated'] );
        $privacy_url = esc_url( $s['privacy_policy_url']['url'] ?? '/privacy-policy' );
        ?>
        <div class="jacana-tos-root">

            <!-- Hero -->
            <section class="jacana-tos-hero">
                <div class="jacana-tos-hero-inner">
                    <div class="jacana-tos-kicker"><?php esc_html_e( 'Legal', 'jacana-compliance' ); ?></div>
                    <h1 class="jacana-tos-title"><?php esc_html_e( 'Terms of Service', 'jacana-compliance' ); ?></h1>
                    <p class="jacana-tos-intro"><?php esc_html_e( 'Please read these terms carefully before making a booking. By confirming a reservation with Jacana Safaris & Tours you agree to be bound by them.', 'jacana-compliance' ); ?></p>
                    <p class="jacana-tos-meta"><?php echo esc_html__( 'Last updated:', 'jacana-compliance' ) . ' ' . $updated; ?></p>
                </div>
                <div class="jacana-tos-stripe" aria-hidden="true"></div>
            </section>

            <!-- Body -->
            <section class="jacana-tos-body">
                <div class="jacana-tos-body-inner">

                    <!-- Table of Contents -->
                    <nav class="jacana-tos-toc" aria-label="<?php esc_attr_e( 'Table of contents', 'jacana-compliance' ); ?>">
                        <div class="jacana-tos-toc-label"><?php esc_html_e( 'Jump to section', 'jacana-compliance' ); ?></div>
                        <ul>
                            <li><a href="#tos-acceptance"><?php esc_html_e( '1. Acceptance', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#tos-definitions"><?php esc_html_e( '2. Definitions', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#tos-booking"><?php esc_html_e( '3. Booking & Confirmation', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#tos-pricing"><?php esc_html_e( '4. Pricing & Payment', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#tos-cancellation-client"><?php esc_html_e( '5. Cancellation by Client', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#tos-cancellation-jacana"><?php esc_html_e( '6. Cancellation by Us', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#tos-changes"><?php esc_html_e( '7. Changes to Bookings', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#tos-insurance"><?php esc_html_e( '8. Travel Insurance', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#tos-health"><?php esc_html_e( '9. Health & Fitness', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#tos-documents"><?php esc_html_e( '10. Travel Documents', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#tos-liability"><?php esc_html_e( '11. Liability', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#tos-force-majeure"><?php esc_html_e( '12. Force Majeure', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#tos-complaints"><?php esc_html_e( '13. Complaints', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#tos-ip"><?php esc_html_e( '14. Intellectual Property', 'jacana-compliance' ); ?></a></li>
                            <li><a href="#tos-governing-law"><?php esc_html_e( '15. Governing Law', 'jacana-compliance' ); ?></a></li>
                        </ul>
                    </nav>

                    <!-- 1. Acceptance -->
                    <article class="jacana-tos-section" id="tos-acceptance">
                        <h2><?php esc_html_e( '1. Acceptance of Terms', 'jacana-compliance' ); ?></h2>
                        <p><?php echo sprintf(
                            esc_html__( 'These Terms of Service ("Terms") govern all bookings made with %s ("Jacana", "we", "us", or "our"). By submitting a booking request, paying a deposit, or otherwise confirming a reservation, you ("the Client") acknowledge that you have read, understood, and agree to these Terms on behalf of yourself and all members of your travelling party.', 'jacana-compliance' ),
                            '<strong>' . $company . '</strong>'
                        ); ?></p>
                        <p><?php esc_html_e( 'If you do not agree to these Terms, please do not proceed with a booking.', 'jacana-compliance' ); ?></p>
                    </article>

                    <!-- 2. Definitions -->
                    <article class="jacana-tos-section" id="tos-definitions">
                        <h2><?php esc_html_e( '2. Definitions', 'jacana-compliance' ); ?></h2>
                        <dl class="jacana-tos-definitions">
                            <div class="jacana-tos-def-row">
                                <dt><?php esc_html_e( '"Booking"', 'jacana-compliance' ); ?></dt>
                                <dd><?php esc_html_e( 'A confirmed reservation for a tour, safari, car rental, shuttle transfer, or any other service offered by Jacana.', 'jacana-compliance' ); ?></dd>
                            </div>
                            <div class="jacana-tos-def-row">
                                <dt><?php esc_html_e( '"Itinerary"', 'jacana-compliance' ); ?></dt>
                                <dd><?php esc_html_e( 'The written travel plan issued to you upon booking confirmation, detailing dates, destinations, accommodation, and included services.', 'jacana-compliance' ); ?></dd>
                            </div>
                            <div class="jacana-tos-def-row">
                                <dt><?php esc_html_e( '"Supplier"', 'jacana-compliance' ); ?></dt>
                                <dd><?php esc_html_e( 'Any third-party provider of accommodation, transport, activities, or other services forming part of your itinerary.', 'jacana-compliance' ); ?></dd>
                            </div>
                            <div class="jacana-tos-def-row">
                                <dt><?php esc_html_e( '"Total Trip Cost"', 'jacana-compliance' ); ?></dt>
                                <dd><?php esc_html_e( 'The full amount quoted and agreed for your booking, inclusive of all services listed in the confirmed itinerary.', 'jacana-compliance' ); ?></dd>
                            </div>
                            <div class="jacana-tos-def-row">
                                <dt><?php esc_html_e( '"Force Majeure"', 'jacana-compliance' ); ?></dt>
                                <dd><?php esc_html_e( 'Any event outside the reasonable control of either party, including but not limited to natural disasters, war, civil unrest, pandemic, government travel restrictions, or acts of terrorism.', 'jacana-compliance' ); ?></dd>
                            </div>
                        </dl>
                    </article>

                    <!-- 3. Booking Process -->
                    <article class="jacana-tos-section" id="tos-booking">
                        <h2><?php esc_html_e( '3. Booking Process & Confirmation', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'A booking is not confirmed until:', 'jacana-compliance' ); ?></p>
                        <ol>
                            <li><?php esc_html_e( 'You have received a written booking confirmation and itinerary from us by email; and', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'We have received your deposit payment (see Section 4).', 'jacana-compliance' ); ?></li>
                        </ol>
                        <p><?php esc_html_e( 'Until both conditions are met, all dates, prices, and availability remain provisional. Jacana reserves the right to withdraw or amend a quote at any time before confirmation.', 'jacana-compliance' ); ?></p>
                        <p><?php esc_html_e( 'It is the responsibility of the lead Client to ensure that all information provided at the time of booking — including names, travel dates, dietary requirements, and medical conditions — is accurate. Any errors must be notified to us immediately.', 'jacana-compliance' ); ?></p>
                        <div class="jacana-tos-highlight">
                            <?php echo sprintf(
                                esc_html__( 'To initiate a booking, email us at %s or use the booking form on our website.', 'jacana-compliance' ),
                                '<a href="mailto:' . esc_attr( $booking_email ) . '">' . esc_html( $booking_email ) . '</a>'
                            ); ?>
                        </div>
                    </article>

                    <!-- 4. Pricing & Payment -->
                    <article class="jacana-tos-section" id="tos-pricing">
                        <h2><?php esc_html_e( '4. Pricing & Payment', 'jacana-compliance' ); ?></h2>

                        <h3><?php esc_html_e( 'Currency & Pricing', 'jacana-compliance' ); ?></h3>
                        <p><?php esc_html_e( 'All prices are quoted in Namibian Dollars (NAD) or US Dollars (USD) as stated in your itinerary. Prices are per person unless otherwise specified. Prices do not include international airfares, visa fees, travel insurance, items of a personal nature, or tips and gratuities unless explicitly stated.', 'jacana-compliance' ); ?></p>

                        <h3><?php esc_html_e( 'Deposit', 'jacana-compliance' ); ?></h3>
                        <p><?php esc_html_e( 'A non-refundable deposit of 30% of the Total Trip Cost is required to confirm a booking. The deposit amount will be stated in your quote. Payment of the deposit constitutes your acceptance of these Terms.', 'jacana-compliance' ); ?></p>

                        <h3><?php esc_html_e( 'Balance Payment', 'jacana-compliance' ); ?></h3>
                        <p><?php esc_html_e( 'The remaining balance is due no later than 60 days before your departure date. For bookings made within 60 days of departure, the full amount is payable at the time of booking.', 'jacana-compliance' ); ?></p>

                        <h3><?php esc_html_e( 'Price Changes', 'jacana-compliance' ); ?></h3>
                        <p><?php esc_html_e( 'Once your deposit has been received, your price is fixed except in the case of significant currency fluctuations (greater than 10%), changes in government-imposed taxes or park fees, or force majeure events. We will notify you of any such changes before requesting additional payment. You may cancel without penalty if a price increase exceeds 8% of the original Total Trip Cost.', 'jacana-compliance' ); ?></p>

                        <h3><?php esc_html_e( 'Accepted Payment Methods', 'jacana-compliance' ); ?></h3>
                        <p><?php esc_html_e( 'We accept bank transfer (EFT), credit card (subject to a processing fee where applicable), and other methods as communicated in your invoice. All payments must be received in cleared funds.', 'jacana-compliance' ); ?></p>
                    </article>

                    <!-- 5. Cancellation by Client -->
                    <article class="jacana-tos-section" id="tos-cancellation-client">
                        <h2><?php esc_html_e( '5. Cancellation by the Client', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'All cancellations must be submitted in writing to our booking office. The date of cancellation is the date we receive your written notice. The following charges apply:', 'jacana-compliance' ); ?></p>

                        <div class="jacana-tos-table-wrap">
                            <table class="jacana-tos-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Notice Period (before departure)', 'jacana-compliance' ); ?></th>
                                        <th><?php esc_html_e( 'Cancellation Charge', 'jacana-compliance' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php esc_html_e( 'More than 90 days', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( 'Deposit only (30% of Total Trip Cost)', 'jacana-compliance' ); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e( '61 – 90 days', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( '40% of Total Trip Cost', 'jacana-compliance' ); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e( '31 – 60 days', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( '60% of Total Trip Cost', 'jacana-compliance' ); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e( '15 – 30 days', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( '80% of Total Trip Cost', 'jacana-compliance' ); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e( 'Fewer than 15 days / no-show', 'jacana-compliance' ); ?></td>
                                        <td><?php esc_html_e( '100% of Total Trip Cost', 'jacana-compliance' ); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p><?php esc_html_e( 'Some suppliers (lodges, parks, charter airlines) apply their own cancellation penalties in addition to the above. These will be communicated in your itinerary.', 'jacana-compliance' ); ?></p>
                        <div class="jacana-tos-note">
                            <strong><?php esc_html_e( 'We strongly recommend comprehensive travel insurance', 'jacana-compliance' ); ?></strong> —
                            <?php esc_html_e( 'a good policy will cover cancellation charges in circumstances such as illness, family emergency, or adverse travel advisories.', 'jacana-compliance' ); ?>
                        </div>
                    </article>

                    <!-- 6. Cancellation by Jacana -->
                    <article class="jacana-tos-section" id="tos-cancellation-jacana">
                        <h2><?php esc_html_e( '6. Cancellation or Alteration by Jacana', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'We reserve the right to cancel or significantly alter a booking in the following circumstances:', 'jacana-compliance' ); ?></p>
                        <ul>
                            <li><?php esc_html_e( 'A minimum group size has not been reached (where applicable and stated at the time of booking)', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'A Force Majeure event makes it impossible or unsafe to operate the trip', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'A key supplier cancels or becomes insolvent with no viable alternative', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'You fail to make payment by the due date', 'jacana-compliance' ); ?></li>
                        </ul>
                        <p><?php esc_html_e( 'Where we cancel for reasons other than Force Majeure or your own default, we will offer you: (a) a comparable alternative trip of equal or greater value; (b) a trip of lesser value with a refund of the price difference; or (c) a full refund of all monies paid to us.', 'jacana-compliance' ); ?></p>
                        <p><?php esc_html_e( 'Jacana is not liable for any costs you may have incurred in preparing for the trip (e.g. international flights, visas, vaccinations) unless we have explicitly agreed otherwise in writing.', 'jacana-compliance' ); ?></p>
                    </article>

                    <!-- 7. Changes -->
                    <article class="jacana-tos-section" id="tos-changes">
                        <h2><?php esc_html_e( '7. Changes to Bookings', 'jacana-compliance' ); ?></h2>
                        <h3><?php esc_html_e( 'Changes by the Client', 'jacana-compliance' ); ?></h3>
                        <p><?php esc_html_e( 'Any request to change travel dates, party composition, or itinerary must be made in writing. We will make every effort to accommodate changes, but cannot guarantee availability. Changes may incur an administration fee and additional supplier charges. Name changes on bookings may not be possible once supplier confirmations have been issued.', 'jacana-compliance' ); ?></p>
                        <h3><?php esc_html_e( 'Minor Changes by Jacana', 'jacana-compliance' ); ?></h3>
                        <p><?php esc_html_e( 'We reserve the right to make minor changes to itineraries (e.g. substituting one lodge for a comparable alternative, adjusting route order) where operational circumstances require. We will notify you of any such changes as soon as possible. Minor changes do not entitle the Client to cancel without paying applicable charges.', 'jacana-compliance' ); ?></p>
                    </article>

                    <!-- 8. Travel Insurance -->
                    <article class="jacana-tos-section" id="tos-insurance">
                        <h2><?php esc_html_e( '8. Travel Insurance', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'Comprehensive travel insurance is not included in any Jacana package and is not optional — we consider it essential for all travellers. Your policy should cover, at a minimum:', 'jacana-compliance' ); ?></p>
                        <ul>
                            <li><?php esc_html_e( 'Trip cancellation and curtailment', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Emergency medical treatment and hospitalisation', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Emergency medical evacuation and repatriation', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Loss or theft of baggage and personal effects', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Activities relevant to your itinerary (e.g. game drives, quad biking, light aircraft flights)', 'jacana-compliance' ); ?></li>
                        </ul>
                        <p><?php esc_html_e( 'By confirming your booking without insurance, you acknowledge that you are travelling at your own financial risk. Jacana accepts no liability for costs that would otherwise have been covered by insurance.', 'jacana-compliance' ); ?></p>
                    </article>

                    <!-- 9. Health -->
                    <article class="jacana-tos-section" id="tos-health">
                        <h2><?php esc_html_e( '9. Health, Fitness & Medical Conditions', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'It is your responsibility to ensure that all members of your party are medically fit and able to participate in all aspects of the booked itinerary. If you or any member of your party has a relevant medical condition, disability, or dietary requirement, you must disclose this to us in writing before confirming your booking.', 'jacana-compliance' ); ?></p>
                        <p><?php esc_html_e( 'You should consult a medical professional regarding recommended vaccinations (e.g. yellow fever, typhoid, hepatitis A & B) and malaria prophylaxis for travel to Namibia.', 'jacana-compliance' ); ?></p>
                        <p><?php esc_html_e( 'Jacana reserves the right to refuse or remove from a tour any person whose conduct or health condition is, in our reasonable opinion, likely to endanger the safety or well-being of themselves or other participants. In such cases, no refund will be issued.', 'jacana-compliance' ); ?></p>
                    </article>

                    <!-- 10. Travel Documents -->
                    <article class="jacana-tos-section" id="tos-documents">
                        <h2><?php esc_html_e( '10. Passports, Visas & Entry Requirements', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'It is your sole responsibility to ensure that all members of your party hold valid travel documents. Requirements include:', 'jacana-compliance' ); ?></p>
                        <ul>
                            <li><?php esc_html_e( 'A valid passport with a minimum of 6 months\' validity beyond your return date', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Any visas or entry permits required for Namibia and any transit countries', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'An unabridged birth certificate for minor children travelling with one parent or without their parents, as required by Namibian law', 'jacana-compliance' ); ?></li>
                        </ul>
                        <p><?php esc_html_e( 'Jacana may provide general guidance on visa requirements but accepts no liability for refusal of entry or denial of boarding arising from inadequate documentation. We strongly recommend checking with the relevant embassy or high commission before travel.', 'jacana-compliance' ); ?></p>
                    </article>

                    <!-- 11. Liability -->
                    <article class="jacana-tos-section" id="tos-liability">
                        <h2><?php esc_html_e( '11. Liability & Responsibility', 'jacana-compliance' ); ?></h2>

                        <h3><?php esc_html_e( 'What We Are Responsible For', 'jacana-compliance' ); ?></h3>
                        <p><?php esc_html_e( 'Jacana accepts responsibility for ensuring that the services included in your booking are supplied as described, and that we act with reasonable skill and care in arranging those services. Where we are found to have been at fault, our liability is limited to twice the cost of the affected service or the Total Trip Cost, whichever is lower.', 'jacana-compliance' ); ?></p>

                        <h3><?php esc_html_e( 'What We Are Not Responsible For', 'jacana-compliance' ); ?></h3>
                        <p><?php esc_html_e( 'Jacana is not liable for:', 'jacana-compliance' ); ?></p>
                        <ul>
                            <li><?php esc_html_e( 'The acts or omissions of independent suppliers (lodges, charter operators, car rental companies, activity providers)', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Death, personal injury, illness, or loss arising from activities inherently risky in nature (game drives, hiking, 4x4 self-drive, water activities)', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Loss of or damage to personal property, including luggage', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Consequences of events beyond our reasonable control (Force Majeure)', 'jacana-compliance' ); ?></li>
                            <li><?php esc_html_e( 'Any loss or additional cost arising from your failure to comply with these Terms', 'jacana-compliance' ); ?></li>
                        </ul>
                        <p><?php esc_html_e( 'Nothing in these Terms limits our liability for death or personal injury caused by our own negligence, or for any other liability that cannot be excluded by law.', 'jacana-compliance' ); ?></p>
                    </article>

                    <!-- 12. Force Majeure -->
                    <article class="jacana-tos-section" id="tos-force-majeure">
                        <h2><?php esc_html_e( '12. Force Majeure', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'Neither party will be in breach of these Terms or liable for any failure or delay in performance caused by a Force Majeure event. This includes natural disasters, epidemics, pandemics, war, civil unrest, government travel restrictions, closure of national parks, or any other event outside our reasonable control.', 'jacana-compliance' ); ?></p>
                        <p><?php esc_html_e( 'In a Force Majeure situation, we will make every reasonable effort to provide alternative arrangements or a travel credit. We are not obligated to issue cash refunds for costs already incurred with suppliers. We recommend that all clients hold travel insurance that covers Force Majeure cancellations.', 'jacana-compliance' ); ?></p>
                    </article>

                    <!-- 13. Complaints -->
                    <article class="jacana-tos-section" id="tos-complaints">
                        <h2><?php esc_html_e( '13. Complaints & Dispute Resolution', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'If you experience a problem during your trip, you must report it immediately to your guide, lodge manager, or to our operations team by phone or email. Failure to do so while on tour may limit our ability to investigate and resolve the issue.', 'jacana-compliance' ); ?></p>
                        <p><?php echo sprintf(
                            esc_html__( 'Any unresolved complaints must be submitted in writing within 30 days of your return, addressed to %s.', 'jacana-compliance' ),
                            '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>'
                        ); ?></p>
                        <p><?php esc_html_e( 'We are committed to resolving disputes promptly and fairly. If we are unable to reach agreement, the dispute shall be referred to mediation in Namibia before either party may pursue litigation.', 'jacana-compliance' ); ?></p>
                    </article>

                    <!-- 14. Intellectual Property -->
                    <article class="jacana-tos-section" id="tos-ip">
                        <h2><?php esc_html_e( '14. Photography, Reviews & Intellectual Property', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'All content on the Jacana Safaris & Tours website — including text, photographs, itineraries, logos, and video — is the intellectual property of Jacana or its licensors and may not be reproduced without prior written consent.', 'jacana-compliance' ); ?></p>
                        <p><?php esc_html_e( 'By submitting a review, photograph, or testimonial to us, you grant Jacana a non-exclusive, royalty-free licence to use, display, and share that content in our marketing materials. We will always credit you unless you request anonymity.', 'jacana-compliance' ); ?></p>
                        <p><?php esc_html_e( 'Our guides and staff may photograph guests during trips for marketing purposes. You may opt out at any time by notifying your guide.', 'jacana-compliance' ); ?></p>
                    </article>

                    <!-- 15. Governing Law -->
                    <article class="jacana-tos-section jacana-tos-section--last" id="tos-governing-law">
                        <h2><?php esc_html_e( '15. Governing Law', 'jacana-compliance' ); ?></h2>
                        <p><?php esc_html_e( 'These Terms are governed by and construed in accordance with the laws of the Republic of Namibia. Any dispute arising from these Terms or a booking will be subject to the exclusive jurisdiction of the courts of Namibia.', 'jacana-compliance' ); ?></p>
                        <p><?php echo sprintf(
                            esc_html__( 'If you have any questions about these Terms, please contact us at %s.', 'jacana-compliance' ),
                            '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>'
                        ); ?></p>

                        <div class="jacana-tos-contact-block">
                            <strong><?php echo $company; ?></strong><br>
                            <?php echo nl2br( $address ); ?>
                        </div>

                        <div class="jacana-tos-related-links">
                            <a href="<?php echo $privacy_url; ?>" class="jacana-tos-related-link"><?php esc_html_e( 'Privacy Policy', 'jacana-compliance' ); ?></a>
                        </div>
                    </article>

                </div>
            </section>

        </div>
        <?php
    }
}
