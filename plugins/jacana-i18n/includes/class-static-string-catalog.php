<?php
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Static String Catalog for Jacana I18n
 * Central registry for all hardcoded strings in theme/widgets
 */
class Jacana_I18n_Static_String_Catalog {

  public static function get_items() {
    $instance = new self();
    return array_merge(
      $instance->get_footer_items(),
      $instance->get_destination_items(),
      $instance->get_team_profiles_items(),
      $instance->get_contact_page_items(),
      $instance->get_accommodation_items(),
      $instance->get_fleet_explorer_items(),
      $instance->get_faq_downloads_items(),
      $instance->get_about_split_items(),
      $instance->get_booking_studio_items(),
      $instance->get_compliance_items()
    );
  }

  /* ── Site Footer ── */
  private function get_footer_items() {
    return array(
      array('key' => 'catalog.footer.tagline',       'source' => 'Crafting tailored Namibia adventures — self-drive safaris, guided tours, transfers, and more since 2016.', 'area' => 'footer'),
      array('key' => 'catalog.footer.explore',       'source' => 'Explore',                   'area' => 'footer'),
      array('key' => 'catalog.footer.company',       'source' => 'Company',                   'area' => 'footer'),
      array('key' => 'catalog.footer.get_in_touch',  'source' => 'Get in Touch',              'area' => 'footer'),
      array('key' => 'catalog.footer.cta_kicker',    'source' => 'Ready for Namibia?',        'area' => 'footer'),
      array('key' => 'catalog.footer.cta_heading',   'source' => 'Start planning your adventure', 'area' => 'footer'),
      array('key' => 'catalog.footer.cta_button',    'source' => 'Begin your journey',        'area' => 'footer'),
      array('key' => 'catalog.footer.copyright',     'source' => 'Jacana Safaris & Tours. All rights reserved.', 'area' => 'footer'),
      array('key' => 'catalog.footer.designed_with', 'source' => 'Designed with',             'area' => 'footer'),
      array('key' => 'catalog.footer.in_namibia',    'source' => 'in Namibia',                'area' => 'footer'),
    );
  }

  /* ── Destination Archive ── */
  private function get_destination_items() {
    return array(
      array('key' => 'catalog.dest.discover',        'source' => 'Discover Namibia',          'area' => 'destinations'),
      array('key' => 'catalog.dest.extraordinary',   'source' => 'Extraordinary destinations designed around your sense of adventure.', 'area' => 'destinations'),
      array('key' => 'catalog.dest.kicker',          'source' => 'Extraordinary Journeys',    'area' => 'destinations'),
      array('key' => 'catalog.dest.all_regions',     'source' => 'All Regions',               'area' => 'destinations'),
      array('key' => 'catalog.dest.explore_btn',     'source' => 'Explore Destination',       'area' => 'destinations'),
      array('key' => 'catalog.dest.no_results',      'source' => 'No destinations found for this selection.', 'area' => 'destinations'),
      array('key' => 'catalog.dest.view_all',        'source' => 'View All Destinations',      'area' => 'destinations'),
    );
  }

  /* ── Team Profiles ── */
  private function get_team_profiles_items() {
    return array(
      array('key' => 'catalog.team.kicker',         'source' => 'The People Behind the Journey',   'area' => 'team_profiles'),
      array('key' => 'catalog.team.education',      'source' => 'Education',                       'area' => 'team_profiles'),
      array('key' => 'catalog.team.experience',     'source' => 'Work experience',                 'area' => 'team_profiles'),
      array('key' => 'catalog.team.testimonials',   'source' => 'What friends & clients say',      'area' => 'team_profiles'),
      array('key' => 'catalog.team.quote',          'source' => 'Favourite quote',                 'area' => 'team_profiles'),
      array('key' => 'catalog.team.languages',      'source' => 'Languages',                       'area' => 'team_profiles'),
      array('key' => 'catalog.team.certs',          'source' => 'Certifications',                  'area' => 'team_profiles'),
      array('key' => 'catalog.team.book_guide',     'source' => 'Book with this guide',            'area' => 'team_profiles'),
    );
  }

  /* ── Contact Page ── */
  private function get_contact_page_items() {
    return array(
      array('key' => 'catalog.contact.email_label',   'source' => 'Email',             'area' => 'contact'),
      array('key' => 'catalog.contact.call_us',       'source' => 'Call us',            'area' => 'contact'),
      array('key' => 'catalog.contact.direct',        'source' => 'Direct contact',     'area' => 'contact'),
      array('key' => 'catalog.contact.get_in_touch',  'source' => 'Get in touch',       'area' => 'contact'),
      array('key' => 'catalog.contact.location',      'source' => 'Location',           'area' => 'contact'),
      array('key' => 'catalog.contact.enquiry',       'source' => 'Enquiry',            'area' => 'contact'),
      array('key' => 'catalog.contact.start_journey', 'source' => 'Start your journey', 'area' => 'contact'),
      array('key' => 'catalog.contact.cta_copy',      'source' => 'Tell us your dates and traveler count. We handle the rest with tailored guidance.', 'area' => 'contact'),
      array('key' => 'catalog.contact.cta_button',    'source' => 'Get your tailored proposal',  'area' => 'contact'),
      array('key' => 'catalog.contact.proposal',      'source' => 'Proposal',                     'area' => 'contact'),
      array('key' => 'catalog.contact.contact_us',    'source' => 'Contact us',                   'area' => 'contact'),
    );
  }

  /* ── Accommodation & Tours Inclusions ── */
  private function get_accommodation_items() {
    return array(
      array('key' => 'catalog.accom.whats_included',     'source' => "What's included",     'area' => 'accommodation'),
      array('key' => 'catalog.accom.whats_not_included', 'source' => "What's not included", 'area' => 'accommodation'),
      array('key' => 'catalog.accom.included_pill',      'source' => 'INCLUDED',             'area' => 'accommodation'),
      array('key' => 'catalog.accom.not_included_pill',  'source' => 'NOT INCLUDED',         'area' => 'accommodation'),
      array('key' => 'catalog.tours.accommodation',      'source' => 'Accommodation',        'area' => 'tours'),
      array('key' => 'catalog.tours.meals',              'source' => 'Meals as indicated',   'area' => 'tours'),
      array('key' => 'catalog.tours.vehicle',            'source' => 'Vehicle and fuel',     'area' => 'tours'),
      array('key' => 'catalog.tours.park_fees',          'source' => 'Park entrance fees',   'area' => 'tours'),
      array('key' => 'catalog.tours.guide',              'source' => 'Tour guide',           'area' => 'tours'),
      array('key' => 'catalog.tours.support',            'source' => 'Jacana support before & during tour', 'area' => 'tours'),
    );
  }

  /* ── Car Rental Fleet Explorer ── */
  private function get_fleet_explorer_items() {
    return array(
      array('key' => 'catalog.fleet.core_features',    'source' => 'Core Features',    'area' => 'car_rental'),
      array('key' => 'catalog.fleet.optional_extras',  'source' => 'Optional Extras',  'area' => 'car_rental'),
      array('key' => 'catalog.fleet.doors',            'source' => 'Doors',            'area' => 'car_rental'),
      array('key' => 'catalog.fleet.transmission',     'source' => 'Transmission',     'area' => 'car_rental'),
      array('key' => 'catalog.fleet.air_con',          'source' => 'Air Con',          'area' => 'car_rental'),
      array('key' => 'catalog.fleet.passengers',       'source' => 'Passengers',       'area' => 'car_rental'),
      array('key' => 'catalog.fleet.luggage',          'source' => 'Luggage',          'area' => 'car_rental'),
      array('key' => 'catalog.fleet.rates_request',    'source' => 'Rates on request', 'area' => 'car_rental'),
      array('key' => 'catalog.fleet.best_for',         'source' => 'Best For',         'area' => 'car_rental'),
    );
  }

  /* ── FAQ Downloads ── */
  private function get_faq_downloads_items() {
    return array(
      array('key' => 'catalog.faq.weather_title',       'source' => 'Namibia Weather & Seasons',                                            'area' => 'faq'),
      array('key' => 'catalog.faq.weather_desc',        'source' => 'Plan around the seasons — rainfall, temperature, and travel conditions.','area' => 'faq'),
      array('key' => 'catalog.faq.current_weather',     'source' => 'Current Weather',                                                      'area' => 'faq'),
      array('key' => 'catalog.faq.temperature',         'source' => 'Temperature',                                                          'area' => 'faq'),
      array('key' => 'catalog.faq.humidity',            'source' => 'Humidity',                                                              'area' => 'faq'),
      array('key' => 'catalog.faq.conditions',          'source' => 'Conditions',                                                            'area' => 'faq'),
      array('key' => 'catalog.faq.best_time_title',     'source' => 'Best Time to Visit',                                                   'area' => 'faq'),
      array('key' => 'catalog.faq.browse_topic',        'source' => 'Browse by topic',                                                      'area' => 'faq'),
      array('key' => 'catalog.faq.discover_namibia',    'source' => 'Namibia Weather Guide',                                                'area' => 'faq'),
    );
  }

  /* ── About Split ── */
  private function get_about_split_items() {
    return array(
      array('key' => 'catalog.about.our_services',       'source' => 'Our Services',        'area' => 'about'),
      array('key' => 'catalog.about.what_we_offer',      'source' => 'What We Offer',       'area' => 'about'),
      array('key' => 'catalog.about.learn_more',         'source' => 'Learn more',          'area' => 'about'),
      array('key' => 'catalog.about.tailor_made_tours',  'source' => 'Tailor-Made Tour Design', 'area' => 'about'),
      array('key' => 'catalog.about.car_rental',         'source' => 'Car Rental',          'area' => 'about'),
      array('key' => 'catalog.about.airport_transfers',  'source' => 'Airport Transfers',   'area' => 'about'),
      array('key' => 'catalog.about.game_drives',        'source' => 'Game Drives',         'area' => 'about'),
      array('key' => 'catalog.about.accommodation',      'source' => 'Accommodation',       'area' => 'about'),
      array('key' => 'catalog.about.flight_booking',     'source' => 'Flight Booking',      'area' => 'about'),
      array('key' => 'catalog.about.guided_options',     'source' => 'Guided and Self-Drive Options', 'area' => 'about'),
    );
  }

  /* ── Booking Studio ── */
  private function get_booking_studio_items() {
    return array(
      array('key' => 'catalog.booking.name',          'source' => 'Name',                                                                    'area' => 'concierge'),
      array('key' => 'catalog.booking.email',         'source' => 'Email',                                                                   'area' => 'concierge'),
      array('key' => 'catalog.booking.dates',         'source' => 'Travel dates',                                                            'area' => 'concierge'),
      array('key' => 'catalog.booking.message',       'source' => 'Tell us what you want to explore in Namibia',                              'area' => 'concierge'),
      array('key' => 'catalog.booking.submit',        'source' => 'Let the Adventure Begin',                                                 'area' => 'concierge'),
    );
  }

  /* ── Compliance Pages (Privacy Policy & Terms of Service) ── */
  private function get_compliance_items() {
    return array(
      // Shared
      array('key' => 'catalog.compliance.legal',              'source' => 'Legal',                     'area' => 'compliance'),
      array('key' => 'catalog.compliance.jump_to_section',    'source' => 'Jump to section',           'area' => 'compliance'),
      array('key' => 'catalog.compliance.last_updated',       'source' => 'Last updated:',             'area' => 'compliance'),
      array('key' => 'catalog.compliance.privacy_policy',     'source' => 'Privacy Policy',            'area' => 'compliance'),
      array('key' => 'catalog.compliance.terms_of_service',   'source' => 'Terms of Service',         'area' => 'compliance'),
      array('key' => 'catalog.compliance.contact_us',         'source' => 'Contact Us',                'area' => 'compliance'),
      array('key' => 'catalog.compliance.email_colon',        'source' => 'Email:',                    'area' => 'compliance'),
      // Privacy Policy page
      array('key' => 'catalog.compliance.pp.intro',           'source' => 'We are committed to protecting your personal information and your right to privacy. This policy explains what we collect, why, and how we use it.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.toc.who',         'source' => 'Who We Are',                'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.toc.data',        'source' => 'Data We Collect',           'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.toc.use',         'source' => 'How We Use It',             'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.toc.share',       'source' => 'Who We Share With',         'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.toc.retention',   'source' => 'Data Retention',            'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.toc.rights',      'source' => 'Your Rights',               'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.toc.cookies',     'source' => 'Cookies',                   'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.toc.children',    'source' => 'Children',                  'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s1_heading',      'source' => '1. Who We Are',             'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s1_intro',        'source' => 'When we refer to "Jacana", "we", "us", or "our" in this policy, we mean the company named above.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.popia_label',     'source' => 'POPIA Information Officer:', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s2_heading',      'source' => '2. Data We Collect',        'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s2_intro',        'source' => 'We only collect the personal data that is necessary to provide our services. This falls into two categories:', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s2_direct',       'source' => 'Information you provide directly', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s2_auto',         'source' => 'Information collected automatically', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.li_fullname',     'source' => 'Full name',                 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.li_email',        'source' => 'Email address',             'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.li_phone',        'source' => 'Phone number',              'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.li_country',      'source' => 'Country of residence',      'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.li_travel',       'source' => 'Travel dates, party size, budget range, and trip preferences', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.li_forms',        'source' => 'Any additional information you provide in enquiry or booking forms', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.li_reviews',      'source' => 'Reviews and testimonials you submit voluntarily', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.li_ip',           'source' => 'IP address and approximate geographic location', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.li_browser',      'source' => 'Browser type and version',  'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.li_analytics',    'source' => 'Pages visited, time on site, and referral source (via Google Analytics)', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.li_cookie',       'source' => 'An anonymised visitor identifier stored in a browser cookie by our AI concierge (no personally identifiable data is stored in this cookie)', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.no_collect',      'source' => 'We do not collect',         'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.no_collect_body', 'source' => 'payment card numbers (all payments are processed by third-party providers), passport numbers, or sensitive personal information unless legally required.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s3_heading',      'source' => '3. How We Use Your Data',   'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.purpose_tour',    'source' => 'Tour & Booking Fulfilment',  'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.purpose_tour_body', 'source' => 'To process your enquiry or booking, prepare itineraries, confirm reservations with lodges and transport operators, and provide pre-trip information.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.basis_contract',  'source' => 'Legal basis: Contractual necessity', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.purpose_comms',   'source' => 'Communication',             'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.purpose_comms_body', 'source' => 'To respond to your enquiries, send quotes, share travel documentation, and follow up after your trip.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.basis_contract_legit', 'source' => 'Legal basis: Contractual necessity / Legitimate interest', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.purpose_analytics', 'source' => 'Website Analytics',       'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.purpose_analytics_body', 'source' => 'To understand how visitors use our website so we can improve it. Analytics data is anonymised and aggregated — we cannot identify you personally from analytics data.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.basis_legit',     'source' => 'Legal basis: Legitimate interest (with cookie consent)', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.purpose_legal',   'source' => 'Legal & Financial Compliance', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.purpose_legal_body', 'source' => 'To maintain financial records, comply with Namibian tax law, and respond to lawful requests from authorities.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.basis_obligation', 'source' => 'Legal basis: Legal obligation', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s4_heading',      'source' => '4. Who We Share Your Data With', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s4_intro',        'source' => 'We share your personal data only where necessary to deliver your trip. We never sell your data to third parties.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.th_recipient',    'source' => 'Recipient',                  'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.th_reason',       'source' => 'Reason',                    'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.th_data',         'source' => 'Data Shared',               'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_lodges',    'source' => 'Lodges & Camps',            'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_lodges_why', 'source' => 'To confirm accommodation bookings', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_lodges_data', 'source' => 'Name, dates, party size, dietary requirements', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_transport', 'source' => 'Transport & Activity Operators', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_transport_why', 'source' => 'To arrange transfers and guided activities', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_transport_data', 'source' => 'Name, dates, contact number', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_airlines',  'source' => 'Airlines & Ground Handlers', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_airlines_why', 'source' => 'To book domestic or charter flights where included', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_airlines_data', 'source' => 'Name, nationality, travel dates', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_payment',   'source' => 'Payment Processors',        'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_payment_why', 'source' => 'To securely process deposits and final payments', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_payment_data', 'source' => 'Name, email, booking amount', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_hosting',   'source' => 'Website Hosting & IT Providers', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_hosting_why', 'source' => 'To operate and maintain the website infrastructure', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_hosting_data', 'source' => 'Server logs including IP addresses', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_google',    'source' => 'Google LLC (Analytics)',    'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_google_why', 'source' => 'To analyse website usage — only with your consent', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_google_data', 'source' => 'Anonymised usage data and device information', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.share_footer',    'source' => 'All third-party suppliers are required to handle your data securely and only for the purpose stated above.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s5_heading',      'source' => '5. How Long We Keep Your Data', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_th_type',     'source' => 'Data Type',                  'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_th_period',   'source' => 'Retention Period',           'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_th_reason',   'source' => 'Reason',                    'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_booking',     'source' => 'Booking records & correspondence', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_booking_dur', 'source' => '7 years',                   'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_booking_why', 'source' => 'Financial and legal compliance', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_enquiry',     'source' => 'Enquiry / quote requests (not converted)', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_enquiry_dur', 'source' => '2 years',                   'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_enquiry_why', 'source' => 'Legitimate interest in future travel planning', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_reviews',     'source' => 'Submitted reviews',         'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_reviews_dur', 'source' => 'Until removed on request',  'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_reviews_why', 'source' => 'Consent given at submission', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_analytics',   'source' => 'Website analytics data',    'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_analytics_dur', 'source' => '26 months',               'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_analytics_why', 'source' => 'Google Analytics default; data is anonymised', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_ai',          'source' => 'AI concierge session data', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_ai_dur',      'source' => '90 days',                   'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.ret_ai_why',      'source' => 'Session context for trip planning continuity', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s6_heading',      'source' => '6. Your Rights',             'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s6_intro',        'source' => 'Depending on where you are located, you may have the following rights regarding your personal data:', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.popia_box',       'source' => 'South African & Namibian residents', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.popia_body',      'source' => 'Your rights are also protected under the Protection of Personal Information Act (POPIA). You may lodge a complaint with the Information Regulator of South Africa or the relevant authority in Namibia.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s7_heading',      'source' => '7. Cookies',                'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s7_manage',       'source' => 'You can manage or withdraw your cookie consent at any time using the button in our cookie banner.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s8_heading',      'source' => "8. Children's Privacy",     'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s8_body',         'source' => 'Our website is not directed at children under the age of 13. We do not knowingly collect personal data from children. If you believe we have inadvertently collected information from a child, please contact us immediately so we can delete it.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s9_heading',      'source' => '9. Changes to This Policy', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s9_body',         'source' => 'We may update this Privacy Policy from time to time. When we do, we will update the "Last updated" date at the top of this page. Material changes will be communicated via email to users with active bookings or enquiries.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s10_heading',     'source' => '10. Contact Us',             'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s10_body',        'source' => 'If you have any questions about this Privacy Policy or how we handle your personal data, please get in touch:', 'area' => 'compliance'),
      // Terms of Service page
      array('key' => 'catalog.compliance.tos.intro',          'source' => 'Please read these terms carefully before making a booking. By confirming a reservation with Jacana Safaris & Tours you agree to be bound by them.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.toc.acceptance', 'source' => '1. Acceptance',             'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.toc.definitions','source' => '2. Definitions',            'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.toc.booking',    'source' => '3. Booking & Confirmation', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.toc.pricing',    'source' => '4. Pricing & Payment',      'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.toc.cancel_c',   'source' => '5. Cancellation by Client', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.toc.cancel_j',   'source' => '6. Cancellation by Us',     'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.toc.changes',    'source' => '7. Changes to Bookings',    'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.toc.insurance',  'source' => '8. Travel Insurance',       'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.toc.health',     'source' => '9. Health & Fitness',       'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.toc.documents',  'source' => '10. Travel Documents',      'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.toc.liability',  'source' => '11. Liability',             'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.toc.force',      'source' => '12. Force Majeure',         'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.toc.complaints', 'source' => '13. Complaints',            'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.toc.ip',         'source' => '14. Intellectual Property', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.toc.law',        'source' => '15. Governing Law',         'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s1_heading',     'source' => '1. Acceptance of Terms',   'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s1_no_agree',    'source' => 'If you do not agree to these Terms, please do not proceed with a booking.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s2_heading',     'source' => '2. Definitions',            'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.def_booking',    'source' => '"Booking"',                 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.def_booking_body', 'source' => 'A confirmed reservation for a tour, safari, car rental, shuttle transfer, or any other service offered by Jacana.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.def_itinerary',  'source' => '"Itinerary"',               'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.def_itinerary_body', 'source' => 'The written travel plan issued to you upon booking confirmation, detailing dates, destinations, accommodation, and included services.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.def_supplier',   'source' => '"Supplier"',                'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.def_supplier_body', 'source' => 'Any third-party provider of accommodation, transport, activities, or other services forming part of your itinerary.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.def_cost',       'source' => '"Total Trip Cost"',         'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.def_cost_body',  'source' => 'The full amount quoted and agreed for your booking, inclusive of all services listed in the confirmed itinerary.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.def_fm',         'source' => '"Force Majeure"',           'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.def_fm_body',    'source' => 'Any event outside the reasonable control of either party, including but not limited to natural disasters, war, civil unrest, pandemic, government travel restrictions, or acts of terrorism.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s3_heading',     'source' => '3. Booking Process & Confirmation', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s3_not_confirmed', 'source' => 'A booking is not confirmed until:', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s3_condition1',  'source' => 'You have received a written booking confirmation and itinerary from us by email; and', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s3_condition2',  'source' => 'We have received your deposit payment (see Section 4).', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s3_provisional', 'source' => 'Until both conditions are met, all dates, prices, and availability remain provisional. Jacana reserves the right to withdraw or amend a quote at any time before confirmation.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s3_accuracy',    'source' => 'It is the responsibility of the lead Client to ensure that all information provided at the time of booking — including names, travel dates, dietary requirements, and medical conditions — is accurate. Any errors must be notified to us immediately.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s4_heading',     'source' => '4. Pricing & Payment',      'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s4_currency',    'source' => 'Currency & Pricing',        'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s4_currency_body', 'source' => 'All prices are quoted in Namibian Dollars (NAD) or US Dollars (USD) as stated in your itinerary. Prices are per person unless otherwise specified. Prices do not include international airfares, visa fees, travel insurance, items of a personal nature, or tips and gratuities unless explicitly stated.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s4_deposit',     'source' => 'Deposit',                   'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s4_deposit_body', 'source' => 'A non-refundable deposit of 30% of the Total Trip Cost is required to confirm a booking. The deposit amount will be stated in your quote. Payment of the deposit constitutes your acceptance of these Terms.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s4_balance',     'source' => 'Balance Payment',           'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s4_balance_body', 'source' => 'The remaining balance is due no later than 60 days before your departure date. For bookings made within 60 days of departure, the full amount is payable at the time of booking.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s4_price_change', 'source' => 'Price Changes',            'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s4_price_change_body', 'source' => 'Once your deposit has been received, your price is fixed except in the case of significant currency fluctuations (greater than 10%), changes in government-imposed taxes or park fees, or force majeure events. We will notify you of any such changes before requesting additional payment. You may cancel without penalty if a price increase exceeds 8% of the original Total Trip Cost.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s4_payment_methods', 'source' => 'Accepted Payment Methods', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s4_payment_methods_body', 'source' => 'We accept bank transfer (EFT), credit card (subject to a processing fee where applicable), and other methods as communicated in your invoice. All payments must be received in cleared funds.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s5_heading',     'source' => '5. Cancellation by the Client', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s5_intro',       'source' => 'All cancellations must be submitted in writing to our booking office. The date of cancellation is the date we receive your written notice. The following charges apply:', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s5_th_notice',   'source' => 'Notice Period (before departure)', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s5_th_charge',   'source' => 'Cancellation Charge',       'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s5_90plus',      'source' => 'More than 90 days',         'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s5_90plus_fee',  'source' => 'Deposit only (30% of Total Trip Cost)', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s5_61_90',       'source' => '61 – 90 days',              'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s5_61_90_fee',   'source' => '40% of Total Trip Cost',    'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s5_31_60',       'source' => '31 – 60 days',              'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s5_31_60_fee',   'source' => '60% of Total Trip Cost',    'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s5_15_30',       'source' => '15 – 30 days',              'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s5_15_30_fee',   'source' => '80% of Total Trip Cost',    'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s5_0_15',        'source' => 'Fewer than 15 days / no-show', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s5_0_15_fee',    'source' => '100% of Total Trip Cost',   'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s5_suppliers',   'source' => 'Some suppliers (lodges, parks, charter airlines) apply their own cancellation penalties in addition to the above. These will be communicated in your itinerary.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s5_insurance',   'source' => 'We strongly recommend comprehensive travel insurance', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s5_insurance_body', 'source' => 'a good policy will cover cancellation charges in circumstances such as illness, family emergency, or adverse travel advisories.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s6_heading',     'source' => '6. Cancellation or Alteration by Jacana', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s6_intro',       'source' => 'We reserve the right to cancel or significantly alter a booking in the following circumstances:', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s6_li1',         'source' => 'A minimum group size has not been reached (where applicable and stated at the time of booking)', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s6_li2',         'source' => 'A Force Majeure event makes it impossible or unsafe to operate the trip', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s6_li3',         'source' => 'A key supplier cancels or becomes insolvent with no viable alternative', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s6_li4',         'source' => 'You fail to make payment by the due date', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s6_options',     'source' => 'Where we cancel for reasons other than Force Majeure or your own default, we will offer you: (a) a comparable alternative trip of equal or greater value; (b) a trip of lesser value with a refund of the price difference; or (c) a full refund of all monies paid to us.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s6_no_liability', 'source' => 'Jacana is not liable for any costs you may have incurred in preparing for the trip (e.g. international flights, visas, vaccinations) unless we have explicitly agreed otherwise in writing.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s7_heading',     'source' => '7. Changes to Bookings',    'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s7_client',      'source' => 'Changes by the Client',     'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s7_client_body', 'source' => 'Any request to change travel dates, party composition, or itinerary must be made in writing. We will make every effort to accommodate changes, but cannot guarantee availability. Changes may incur an administration fee and additional supplier charges. Name changes on bookings may not be possible once supplier confirmations have been issued.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s7_minor',       'source' => 'Minor Changes by Jacana',   'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s7_minor_body',  'source' => 'We reserve the right to make minor changes to itineraries (e.g. substituting one lodge for a comparable alternative, adjusting route order) where operational circumstances require. We will notify you of any such changes as soon as possible. Minor changes do not entitle the Client to cancel without paying applicable charges.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s8_heading',     'source' => '8. Travel Insurance',       'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s8_intro',       'source' => 'Comprehensive travel insurance is not included in any Jacana package and is not optional — we consider it essential for all travellers. Your policy should cover, at a minimum:', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s8_li1',         'source' => 'Trip cancellation and curtailment', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s8_li2',         'source' => 'Emergency medical treatment and hospitalisation', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s8_li3',         'source' => 'Emergency medical evacuation and repatriation', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s8_li4',         'source' => 'Loss or theft of baggage and personal effects', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s8_li5',         'source' => 'Activities relevant to your itinerary (e.g. game drives, quad biking, light aircraft flights)', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s8_noins',       'source' => 'By confirming your booking without insurance, you acknowledge that you are travelling at your own financial risk. Jacana accepts no liability for costs that would otherwise have been covered by insurance.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s9_heading',     'source' => '9. Health, Fitness & Medical Conditions', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s9_fitness',     'source' => 'It is your responsibility to ensure that all members of your party are medically fit and able to participate in all aspects of the booked itinerary. If you or any member of your party has a relevant medical condition, disability, or dietary requirement, you must disclose this to us in writing before confirming your booking.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s9_vaccines',    'source' => 'You should consult a medical professional regarding recommended vaccinations (e.g. yellow fever, typhoid, hepatitis A & B) and malaria prophylaxis for travel to Namibia.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s9_remove',      'source' => 'Jacana reserves the right to refuse or remove from a tour any person whose conduct or health condition is, in our reasonable opinion, likely to endanger the safety or well-being of themselves or other participants. In such cases, no refund will be issued.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s10_heading',    'source' => '10. Passports, Visas & Entry Requirements', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s10_intro',      'source' => 'It is your sole responsibility to ensure that all members of your party hold valid travel documents. Requirements include:', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s10_li1',        'source' => "A valid passport with a minimum of 6 months' validity beyond your return date", 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s10_li2',        'source' => 'Any visas or entry permits required for Namibia and any transit countries', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s10_li3',        'source' => 'An unabridged birth certificate for minor children travelling with one parent or without their parents, as required by Namibian law', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s10_no_liability', 'source' => 'Jacana may provide general guidance on visa requirements but accepts no liability for refusal of entry or denial of boarding arising from inadequate documentation. We strongly recommend checking with the relevant embassy or high commission before travel.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s11_heading',    'source' => '11. Liability & Responsibility', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s11_responsible', 'source' => 'What We Are Responsible For', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s11_responsible_body', 'source' => 'Jacana accepts responsibility for ensuring that the services included in your booking are supplied as described, and that we act with reasonable skill and care in arranging those services. Where we are found to have been at fault, our liability is limited to twice the cost of the affected service or the Total Trip Cost, whichever is lower.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s11_not_responsible', 'source' => 'What We Are Not Responsible For', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s11_not_responsible_intro', 'source' => 'Jacana is not liable for:', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s11_li1',        'source' => 'The acts or omissions of independent suppliers (lodges, charter operators, car rental companies, activity providers)', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s11_li2',        'source' => 'Death, personal injury, illness, or loss arising from activities inherently risky in nature (game drives, hiking, 4x4 self-drive, water activities)', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s11_li3',        'source' => 'Loss of or damage to personal property, including luggage', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s11_li4',        'source' => 'Consequences of events beyond our reasonable control (Force Majeure)', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s11_li5',        'source' => 'Any loss or additional cost arising from your failure to comply with these Terms', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s11_caveat',     'source' => 'Nothing in these Terms limits our liability for death or personal injury caused by our own negligence, or for any other liability that cannot be excluded by law.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s12_heading',    'source' => '12. Force Majeure',          'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s12_body1',      'source' => 'Neither party will be in breach of these Terms or liable for any failure or delay in performance caused by a Force Majeure event. This includes natural disasters, epidemics, pandemics, war, civil unrest, government travel restrictions, closure of national parks, or any other event outside our reasonable control.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s12_body2',      'source' => 'In a Force Majeure situation, we will make every reasonable effort to provide alternative arrangements or a travel credit. We are not obligated to issue cash refunds for costs already incurred with suppliers. We recommend that all clients hold travel insurance that covers Force Majeure cancellations.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s13_heading',    'source' => '13. Complaints & Dispute Resolution', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s13_body1',      'source' => 'If you experience a problem during your trip, you must report it immediately to your guide, lodge manager, or to our operations team by phone or email. Failure to do so while on tour may limit our ability to investigate and resolve the issue.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s13_body3',      'source' => 'We are committed to resolving disputes promptly and fairly. If we are unable to reach agreement, the dispute shall be referred to mediation in Namibia before either party may pursue litigation.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s14_heading',    'source' => '14. Photography, Reviews & Intellectual Property', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s14_body1',      'source' => 'All content on the Jacana Safaris & Tours website — including text, photographs, itineraries, logos, and video — is the intellectual property of Jacana or its licensors and may not be reproduced without prior written consent.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s14_body2',      'source' => 'By submitting a review, photograph, or testimonial to us, you grant Jacana a non-exclusive, royalty-free licence to use, display, and share that content in our marketing materials. We will always credit you unless you request anonymity.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s14_body3',      'source' => 'Our guides and staff may photograph guests during trips for marketing purposes. You may opt out at any time by notifying your guide.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s15_heading',    'source' => '15. Governing Law',          'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s15_body1',      'source' => 'These Terms are governed by and construed in accordance with the laws of the Republic of Namibia. Any dispute arising from these Terms or a booking will be subject to the exclusive jurisdiction of the courts of Namibia.', 'area' => 'compliance'),
      // sprintf template strings (placeholders must be preserved in translation)
      array('key' => 'catalog.compliance.pp.cookie_policy',   'source' => 'Cookie Policy',             'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s1_operator',     'source' => '%1$s is a tour operator based in %2$s. We design and operate safari tours, self-drive holidays, car rental services, and tailor-made travel itineraries across Namibia and southern Africa.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s6_email',        'source' => 'To exercise any of these rights, please email us at %s. We will respond within 30 days.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.pp.s7_cookies',      'source' => 'We use cookies to operate the site, remember your preferences, and — with your consent — to analyse traffic. For a full list of every cookie we set, please see our %s.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s1_governs',     'source' => 'These Terms of Service ("Terms") govern all bookings made with %s ("Jacana", "we", "us", or "our"). By submitting a booking request, paying a deposit, or otherwise confirming a reservation, you ("the Client") acknowledge that you have read, understood, and agree to these Terms on behalf of yourself and all members of your travelling party.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s3_initiate',    'source' => 'To initiate a booking, email us at %s or use the booking form on our website.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s13_submit',     'source' => 'Any unresolved complaints must be submitted in writing within 30 days of your return, addressed to %s.', 'area' => 'compliance'),
      array('key' => 'catalog.compliance.tos.s15_contact',    'source' => 'If you have any questions about these Terms, please contact us at %s.', 'area' => 'compliance'),
    );
  }
}
