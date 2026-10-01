<?php
/**
 * Plugin Name: Jacana CRM
 * Description: Mini CRM and visitor tracking for Jacana Safaris & Tours.
 * Version: 0.7.8
 * Author: Winston Zulu
 */

if (!defined('ABSPATH')) {
  exit;
}

final class Jacana_CRM
{
  const DB_VERSION = '0.7.6';

  public function __construct()
  {
    register_activation_hook(__FILE__, array($this, 'activate'));
    register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    add_action('admin_menu', array($this, 'register_admin_pages'));
    add_action('admin_init', array($this, 'register_settings'));
    add_action('admin_init', array($this, 'handle_admin_post_actions'));
    add_action('init', array($this, 'maybe_upgrade'));
    add_action('init', array($this, 'ensure_scheduled_events'));
    add_filter('cron_schedules', array($this, 'register_cron_schedules'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    add_action('rest_api_init', array($this, 'register_routes'));
    add_action('wp_head', array($this, 'render_frontend_seo_meta'), 1);
    add_action('jacana_crm_daily_seo_audit', array($this, 'run_daily_seo_audit'));
    add_action('jacana_crm_daily_seo_sync', array($this, 'run_daily_seo_sync'));
    add_action('jacana_crm_daily_competitor_scan', array($this, 'run_daily_competitor_scan'));
    add_action('jacana_crm_daily_content_opportunities', array($this, 'run_daily_content_opportunities'));
    add_action('jacana_crm_weekly_strategic_report', array($this, 'run_weekly_strategic_report'));
  }

  public function activate()
  {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $visitors = $wpdb->prefix . 'jacana_visitors';
    $sessions = $wpdb->prefix . 'jacana_sessions';
    $pageviews = $wpdb->prefix . 'jacana_pageviews';
    $leads = $wpdb->prefix . 'jacana_leads';
    $messages = $wpdb->prefix . 'jacana_chat_messages';
    $events = $wpdb->prefix . 'jacana_events';
    $ai_interactions = $wpdb->prefix . 'jacana_ai_interactions';
    $reviews = $wpdb->prefix . 'jacana_reviews';
    $bookings = $wpdb->prefix . 'jacana_bookings';
    $seo_suggestions = $wpdb->prefix . 'jacana_seo_suggestions';
    $seo_queries = $wpdb->prefix . 'jacana_seo_queries';
    $seo_rankings = $wpdb->prefix . 'jacana_seo_rankings';
    $seo_competitors = $wpdb->prefix . 'jacana_seo_competitors';
    $seo_opportunities = $wpdb->prefix . 'jacana_seo_opportunities';
    $seo_reports = $wpdb->prefix . 'jacana_seo_reports';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta("CREATE TABLE {$visitors} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      visitor_key VARCHAR(64) NOT NULL,
      ip_address VARCHAR(64) DEFAULT '' NOT NULL,
      user_agent TEXT,
      first_seen DATETIME NOT NULL,
      last_seen DATETIME NOT NULL,
      visit_count INT UNSIGNED NOT NULL DEFAULT 0,
      total_time_sec INT UNSIGNED NOT NULL DEFAULT 0,
      PRIMARY KEY  (id),
      UNIQUE KEY visitor_key (visitor_key)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$sessions} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      visitor_id BIGINT UNSIGNED NOT NULL,
      session_key VARCHAR(64) NOT NULL,
      label VARCHAR(160) DEFAULT '' NOT NULL,
      started_at DATETIME NOT NULL,
      last_seen DATETIME NOT NULL,
      total_time_sec INT UNSIGNED NOT NULL DEFAULT 0,
      pageviews INT UNSIGNED NOT NULL DEFAULT 0,
      PRIMARY KEY  (id),
      UNIQUE KEY session_key (session_key),
      KEY visitor_id (visitor_id)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$pageviews} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      session_id BIGINT UNSIGNED NOT NULL,
      url TEXT NOT NULL,
      title TEXT,
      referrer TEXT,
      started_at DATETIME NOT NULL,
      duration_sec INT UNSIGNED NOT NULL DEFAULT 0,
      PRIMARY KEY  (id),
      KEY session_id (session_id)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$leads} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      visitor_id BIGINT UNSIGNED NULL,
      name VARCHAR(200) DEFAULT '' NOT NULL,
      email VARCHAR(200) DEFAULT '' NOT NULL,
      phone VARCHAR(80) DEFAULT '' NOT NULL,
      country VARCHAR(120) DEFAULT '' NOT NULL,
      travel_dates VARCHAR(120) DEFAULT '' NOT NULL,
      duration VARCHAR(80) DEFAULT '' NOT NULL,
      party_size VARCHAR(80) DEFAULT '' NOT NULL,
      budget_tier VARCHAR(80) DEFAULT '' NOT NULL,
      travel_style VARCHAR(80) DEFAULT '' NOT NULL,
      service_interest VARCHAR(120) DEFAULT '' NOT NULL,
      source_page VARCHAR(200) DEFAULT '' NOT NULL,
      conversion_score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
      priority VARCHAR(20) DEFAULT 'normal' NOT NULL,
      internal_notes LONGTEXT,
      next_action_at DATETIME NULL,
      interests TEXT,
      status VARCHAR(40) DEFAULT 'new' NOT NULL,
      source VARCHAR(80) DEFAULT '' NOT NULL,
      summary TEXT,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY visitor_id (visitor_id)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$messages} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      visitor_id BIGINT UNSIGNED NULL,
      session_id BIGINT UNSIGNED NULL,
      role VARCHAR(30) NOT NULL,
      content LONGTEXT NOT NULL,
      created_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY visitor_id (visitor_id),
      KEY session_id (session_id)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$events} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      visitor_id BIGINT UNSIGNED NULL,
      session_id BIGINT UNSIGNED NULL,
      type VARCHAR(80) NOT NULL,
      payload LONGTEXT,
      created_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY visitor_id (visitor_id),
      KEY session_id (session_id)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$ai_interactions} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      visitor_id BIGINT UNSIGNED NULL,
      session_id BIGINT UNSIGNED NULL,
      component_type VARCHAR(80) NOT NULL,
      component_key VARCHAR(120) DEFAULT '' NOT NULL,
      page_url TEXT NULL,
      widget_name VARCHAR(120) DEFAULT '' NOT NULL,
      service_interest VARCHAR(120) DEFAULT '' NOT NULL,
      payload_json LONGTEXT NULL,
      action_type VARCHAR(80) DEFAULT '' NOT NULL,
      created_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY visitor_id (visitor_id),
      KEY session_id (session_id),
      KEY component_type (component_type),
      KEY widget_name (widget_name),
      KEY service_interest (service_interest)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$reviews} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      name VARCHAR(200) DEFAULT '' NOT NULL,
      email VARCHAR(200) DEFAULT '' NOT NULL,
      country VARCHAR(120) DEFAULT '' NOT NULL,
      trip_label VARCHAR(200) DEFAULT '' NOT NULL,
      service_interest VARCHAR(120) DEFAULT '' NOT NULL,
      rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
      review_text LONGTEXT NULL,
      source VARCHAR(80) DEFAULT 'website' NOT NULL,
      status VARCHAR(20) DEFAULT 'pending' NOT NULL,
      consent_public TINYINT(1) NOT NULL DEFAULT 0,
      featured_image_id BIGINT UNSIGNED NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      approved_at DATETIME NULL,
      approved_by BIGINT UNSIGNED NULL,
      PRIMARY KEY  (id),
      KEY status (status),
      KEY service_interest (service_interest),
      KEY rating (rating),
      KEY created_at (created_at)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$bookings} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      lead_id BIGINT UNSIGNED NULL,
      visitor_id BIGINT UNSIGNED NULL,
      service_interest VARCHAR(120) DEFAULT '' NOT NULL,
      vehicle_interest VARCHAR(160) DEFAULT '' NOT NULL,
      destination_interest VARCHAR(160) DEFAULT '' NOT NULL,
      traveler_name VARCHAR(200) DEFAULT '' NOT NULL,
      traveler_email VARCHAR(200) DEFAULT '' NOT NULL,
      traveler_phone VARCHAR(80) DEFAULT '' NOT NULL,
      country VARCHAR(120) DEFAULT '' NOT NULL,
      travel_dates VARCHAR(120) DEFAULT '' NOT NULL,
      duration VARCHAR(80) DEFAULT '' NOT NULL,
      party_size VARCHAR(80) DEFAULT '' NOT NULL,
      budget_tier VARCHAR(80) DEFAULT '' NOT NULL,
      accommodation_style VARCHAR(120) DEFAULT '' NOT NULL,
      selected_services TEXT NULL,
      additional_places LONGTEXT NULL,
      message LONGTEXT NULL,
      source_page VARCHAR(200) DEFAULT '' NOT NULL,
      source_widget VARCHAR(120) DEFAULT '' NOT NULL,
      source_section VARCHAR(200) DEFAULT '' NOT NULL,
      details_json LONGTEXT NULL,
      status VARCHAR(30) DEFAULT 'new' NOT NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY lead_id (lead_id),
      KEY visitor_id (visitor_id),
      KEY service_interest (service_interest),
      KEY status (status),
      KEY created_at (created_at)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$seo_suggestions} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      suggestion_group VARCHAR(64) NOT NULL,
      post_id BIGINT UNSIGNED NOT NULL,
      field_key VARCHAR(80) NOT NULL,
      current_value LONGTEXT NULL,
      suggested_value LONGTEXT NULL,
      notes LONGTEXT NULL,
      status VARCHAR(20) NOT NULL DEFAULT 'new',
      confidence SMALLINT UNSIGNED NOT NULL DEFAULT 0,
      provider VARCHAR(40) NOT NULL DEFAULT 'gemini',
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      applied_at DATETIME NULL,
      PRIMARY KEY  (id),
      KEY post_id (post_id),
      KEY suggestion_group (suggestion_group),
      KEY status (status)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$seo_queries} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      query_text VARCHAR(255) NOT NULL,
      page_url TEXT NOT NULL,
      post_id BIGINT UNSIGNED NULL,
      clicks DECIMAL(12,2) NOT NULL DEFAULT 0,
      impressions DECIMAL(12,2) NOT NULL DEFAULT 0,
      ctr DECIMAL(8,4) NOT NULL DEFAULT 0,
      position_avg DECIMAL(8,2) NOT NULL DEFAULT 0,
      snapshot_date DATE NOT NULL,
      source VARCHAR(40) NOT NULL DEFAULT 'search_console',
      created_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY post_id (post_id),
      KEY snapshot_date (snapshot_date),
      KEY query_text (query_text)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$seo_rankings} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      keyword_text VARCHAR(255) NOT NULL,
      page_url TEXT NOT NULL,
      post_id BIGINT UNSIGNED NULL,
      clicks DECIMAL(12,2) NOT NULL DEFAULT 0,
      impressions DECIMAL(12,2) NOT NULL DEFAULT 0,
      ctr DECIMAL(8,4) NOT NULL DEFAULT 0,
      position_avg DECIMAL(8,2) NOT NULL DEFAULT 0,
      snapshot_date DATE NOT NULL,
      source VARCHAR(40) NOT NULL DEFAULT 'search_console',
      created_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY post_id (post_id),
      KEY snapshot_date (snapshot_date),
      KEY keyword_text (keyword_text)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$seo_competitors} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      keyword_text VARCHAR(255) NOT NULL,
      target_post_id BIGINT UNSIGNED NULL,
      target_page_url TEXT NOT NULL,
      target_position_avg DECIMAL(8,2) NOT NULL DEFAULT 0,
      competitor_position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
      competitor_domain VARCHAR(191) DEFAULT '' NOT NULL,
      competitor_url TEXT NOT NULL,
      competitor_title TEXT NULL,
      competitor_meta_description TEXT NULL,
      content_word_count INT UNSIGNED NOT NULL DEFAULT 0,
      summary LONGTEXT NULL,
      analysis_json LONGTEXT NULL,
      source VARCHAR(40) NOT NULL DEFAULT 'duckduckgo',
      snapshot_date DATE NOT NULL,
      created_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY keyword_text (keyword_text),
      KEY target_post_id (target_post_id),
      KEY snapshot_date (snapshot_date),
      KEY competitor_position (competitor_position)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$seo_opportunities} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      opportunity_type VARCHAR(40) NOT NULL DEFAULT 'page_update',
      target_post_id BIGINT UNSIGNED NULL,
      title VARCHAR(255) NOT NULL,
      target_keyword VARCHAR(255) DEFAULT '' NOT NULL,
      priority_score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
      status VARCHAR(20) NOT NULL DEFAULT 'new',
      summary LONGTEXT NULL,
      outline LONGTEXT NULL,
      draft_content LONGTEXT NULL,
      source_data LONGTEXT NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY status (status),
      KEY opportunity_type (opportunity_type),
      KEY target_post_id (target_post_id),
      KEY priority_score (priority_score)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$seo_reports} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      report_type VARCHAR(40) NOT NULL DEFAULT 'strategic',
      report_period VARCHAR(40) NOT NULL DEFAULT 'weekly',
      title VARCHAR(255) NOT NULL,
      summary LONGTEXT NULL,
      report_body LONGTEXT NULL,
      data_json LONGTEXT NULL,
      created_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY report_type (report_type),
      KEY report_period (report_period),
      KEY created_at (created_at)
    ) {$charset_collate};");

    update_option('jacana_crm_db_version', self::DB_VERSION);
    $this->ensure_scheduled_events();
  }

  public function deactivate()
  {
    wp_clear_scheduled_hook('jacana_crm_daily_seo_audit');
    wp_clear_scheduled_hook('jacana_crm_daily_seo_sync');
    wp_clear_scheduled_hook('jacana_crm_daily_competitor_scan');
    wp_clear_scheduled_hook('jacana_crm_daily_content_opportunities');
    wp_clear_scheduled_hook('jacana_crm_weekly_strategic_report');
  }

  public function maybe_upgrade()
  {
    $version = get_option('jacana_crm_db_version', '0.0.0');
    if (version_compare($version, self::DB_VERSION, '>=')) {
      $this->ensure_runtime_tables();
      return;
    }
    global $wpdb;
    $sessions = $wpdb->prefix . 'jacana_sessions';
    $has_label = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$sessions} LIKE %s", 'label'));
    if (!$has_label) {
      $wpdb->query("ALTER TABLE {$sessions} ADD COLUMN label VARCHAR(160) DEFAULT '' NOT NULL");
    }

    $leads = $wpdb->prefix . 'jacana_leads';
    $has_service_interest = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$leads} LIKE %s", 'service_interest'));
    if (!$has_service_interest) {
      $wpdb->query("ALTER TABLE {$leads} ADD COLUMN service_interest VARCHAR(120) DEFAULT '' NOT NULL");
    }

    $has_source_page = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$leads} LIKE %s", 'source_page'));
    if (!$has_source_page) {
      $wpdb->query("ALTER TABLE {$leads} ADD COLUMN source_page VARCHAR(200) DEFAULT '' NOT NULL");
    }

    $has_conversion_score = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$leads} LIKE %s", 'conversion_score'));
    if (!$has_conversion_score) {
      $wpdb->query("ALTER TABLE {$leads} ADD COLUMN conversion_score SMALLINT UNSIGNED NOT NULL DEFAULT 0");
    }

    $has_priority = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$leads} LIKE %s", 'priority'));
    if (!$has_priority) {
      $wpdb->query("ALTER TABLE {$leads} ADD COLUMN priority VARCHAR(20) DEFAULT 'normal' NOT NULL");
    }

    $has_internal_notes = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$leads} LIKE %s", 'internal_notes'));
    if (!$has_internal_notes) {
      $wpdb->query("ALTER TABLE {$leads} ADD COLUMN internal_notes LONGTEXT NULL");
    }

    $has_next_action_at = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$leads} LIKE %s", 'next_action_at'));
    if (!$has_next_action_at) {
      $wpdb->query("ALTER TABLE {$leads} ADD COLUMN next_action_at DATETIME NULL");
    }

    $charset_collate = $wpdb->get_charset_collate();
    $ai_interactions = $wpdb->prefix . 'jacana_ai_interactions';
    $reviews = $wpdb->prefix . 'jacana_reviews';
    $bookings = $wpdb->prefix . 'jacana_bookings';
    $seo_suggestions = $wpdb->prefix . 'jacana_seo_suggestions';
    $seo_queries = $wpdb->prefix . 'jacana_seo_queries';
    $seo_rankings = $wpdb->prefix . 'jacana_seo_rankings';
    $seo_competitors = $wpdb->prefix . 'jacana_seo_competitors';
    $seo_opportunities = $wpdb->prefix . 'jacana_seo_opportunities';
    $seo_reports = $wpdb->prefix . 'jacana_seo_reports';
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta("CREATE TABLE {$ai_interactions} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      visitor_id BIGINT UNSIGNED NULL,
      session_id BIGINT UNSIGNED NULL,
      component_type VARCHAR(80) NOT NULL,
      component_key VARCHAR(120) DEFAULT '' NOT NULL,
      page_url TEXT NULL,
      widget_name VARCHAR(120) DEFAULT '' NOT NULL,
      service_interest VARCHAR(120) DEFAULT '' NOT NULL,
      payload_json LONGTEXT NULL,
      action_type VARCHAR(80) DEFAULT '' NOT NULL,
      created_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY visitor_id (visitor_id),
      KEY session_id (session_id),
      KEY component_type (component_type),
      KEY widget_name (widget_name),
      KEY service_interest (service_interest)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$reviews} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      name VARCHAR(200) DEFAULT '' NOT NULL,
      email VARCHAR(200) DEFAULT '' NOT NULL,
      country VARCHAR(120) DEFAULT '' NOT NULL,
      trip_label VARCHAR(200) DEFAULT '' NOT NULL,
      service_interest VARCHAR(120) DEFAULT '' NOT NULL,
      rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
      review_text LONGTEXT NULL,
      source VARCHAR(80) DEFAULT 'website' NOT NULL,
      status VARCHAR(20) DEFAULT 'pending' NOT NULL,
      consent_public TINYINT(1) NOT NULL DEFAULT 0,
      featured_image_id BIGINT UNSIGNED NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      approved_at DATETIME NULL,
      approved_by BIGINT UNSIGNED NULL,
      PRIMARY KEY  (id),
      KEY status (status),
      KEY service_interest (service_interest),
      KEY rating (rating),
      KEY created_at (created_at)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$bookings} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      lead_id BIGINT UNSIGNED NULL,
      visitor_id BIGINT UNSIGNED NULL,
      service_interest VARCHAR(120) DEFAULT '' NOT NULL,
      vehicle_interest VARCHAR(160) DEFAULT '' NOT NULL,
      destination_interest VARCHAR(160) DEFAULT '' NOT NULL,
      traveler_name VARCHAR(200) DEFAULT '' NOT NULL,
      traveler_email VARCHAR(200) DEFAULT '' NOT NULL,
      traveler_phone VARCHAR(80) DEFAULT '' NOT NULL,
      country VARCHAR(120) DEFAULT '' NOT NULL,
      travel_dates VARCHAR(120) DEFAULT '' NOT NULL,
      duration VARCHAR(80) DEFAULT '' NOT NULL,
      party_size VARCHAR(80) DEFAULT '' NOT NULL,
      budget_tier VARCHAR(80) DEFAULT '' NOT NULL,
      accommodation_style VARCHAR(120) DEFAULT '' NOT NULL,
      selected_services TEXT NULL,
      additional_places LONGTEXT NULL,
      message LONGTEXT NULL,
      source_page VARCHAR(200) DEFAULT '' NOT NULL,
      source_widget VARCHAR(120) DEFAULT '' NOT NULL,
      source_section VARCHAR(200) DEFAULT '' NOT NULL,
      details_json LONGTEXT NULL,
      status VARCHAR(30) DEFAULT 'new' NOT NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY lead_id (lead_id),
      KEY visitor_id (visitor_id),
      KEY service_interest (service_interest),
      KEY status (status),
      KEY created_at (created_at)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$seo_suggestions} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      suggestion_group VARCHAR(64) NOT NULL,
      post_id BIGINT UNSIGNED NOT NULL,
      field_key VARCHAR(80) NOT NULL,
      current_value LONGTEXT NULL,
      suggested_value LONGTEXT NULL,
      notes LONGTEXT NULL,
      status VARCHAR(20) NOT NULL DEFAULT 'new',
      confidence SMALLINT UNSIGNED NOT NULL DEFAULT 0,
      provider VARCHAR(40) NOT NULL DEFAULT 'gemini',
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      applied_at DATETIME NULL,
      PRIMARY KEY  (id),
      KEY post_id (post_id),
      KEY suggestion_group (suggestion_group),
      KEY status (status)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$seo_queries} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      query_text VARCHAR(255) NOT NULL,
      page_url TEXT NOT NULL,
      post_id BIGINT UNSIGNED NULL,
      clicks DECIMAL(12,2) NOT NULL DEFAULT 0,
      impressions DECIMAL(12,2) NOT NULL DEFAULT 0,
      ctr DECIMAL(8,4) NOT NULL DEFAULT 0,
      position_avg DECIMAL(8,2) NOT NULL DEFAULT 0,
      snapshot_date DATE NOT NULL,
      source VARCHAR(40) NOT NULL DEFAULT 'search_console',
      created_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY post_id (post_id),
      KEY snapshot_date (snapshot_date),
      KEY query_text (query_text)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$seo_rankings} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      keyword_text VARCHAR(255) NOT NULL,
      page_url TEXT NOT NULL,
      post_id BIGINT UNSIGNED NULL,
      clicks DECIMAL(12,2) NOT NULL DEFAULT 0,
      impressions DECIMAL(12,2) NOT NULL DEFAULT 0,
      ctr DECIMAL(8,4) NOT NULL DEFAULT 0,
      position_avg DECIMAL(8,2) NOT NULL DEFAULT 0,
      snapshot_date DATE NOT NULL,
      source VARCHAR(40) NOT NULL DEFAULT 'search_console',
      created_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY post_id (post_id),
      KEY snapshot_date (snapshot_date),
      KEY keyword_text (keyword_text)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$seo_competitors} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      keyword_text VARCHAR(255) NOT NULL,
      target_post_id BIGINT UNSIGNED NULL,
      target_page_url TEXT NOT NULL,
      target_position_avg DECIMAL(8,2) NOT NULL DEFAULT 0,
      competitor_position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
      competitor_domain VARCHAR(191) DEFAULT '' NOT NULL,
      competitor_url TEXT NOT NULL,
      competitor_title TEXT NULL,
      competitor_meta_description TEXT NULL,
      content_word_count INT UNSIGNED NOT NULL DEFAULT 0,
      summary LONGTEXT NULL,
      analysis_json LONGTEXT NULL,
      source VARCHAR(40) NOT NULL DEFAULT 'duckduckgo',
      snapshot_date DATE NOT NULL,
      created_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY keyword_text (keyword_text),
      KEY target_post_id (target_post_id),
      KEY snapshot_date (snapshot_date),
      KEY competitor_position (competitor_position)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$seo_opportunities} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      opportunity_type VARCHAR(40) NOT NULL DEFAULT 'page_update',
      target_post_id BIGINT UNSIGNED NULL,
      title VARCHAR(255) NOT NULL,
      target_keyword VARCHAR(255) DEFAULT '' NOT NULL,
      priority_score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
      status VARCHAR(20) NOT NULL DEFAULT 'new',
      summary LONGTEXT NULL,
      outline LONGTEXT NULL,
      draft_content LONGTEXT NULL,
      source_data LONGTEXT NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY status (status),
      KEY opportunity_type (opportunity_type),
      KEY target_post_id (target_post_id),
      KEY priority_score (priority_score)
    ) {$charset_collate};");

    dbDelta("CREATE TABLE {$seo_reports} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      report_type VARCHAR(40) NOT NULL DEFAULT 'strategic',
      report_period VARCHAR(40) NOT NULL DEFAULT 'weekly',
      title VARCHAR(255) NOT NULL,
      summary LONGTEXT NULL,
      report_body LONGTEXT NULL,
      data_json LONGTEXT NULL,
      created_at DATETIME NOT NULL,
      PRIMARY KEY  (id),
      KEY report_type (report_type),
      KEY report_period (report_period),
      KEY created_at (created_at)
    ) {$charset_collate};");

    update_option('jacana_crm_db_version', self::DB_VERSION);
    $this->ensure_scheduled_events();
  }

  private function ensure_runtime_tables($force = false)
  {
    if (!$force && get_transient('jacana_crm_tables_ok')) {
      return;
    }

    global $wpdb;

    $required = array(
      $wpdb->prefix . 'jacana_ai_interactions',
      $wpdb->prefix . 'jacana_reviews',
      $wpdb->prefix . 'jacana_bookings',
    );

    foreach ($required as $table) {
      $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
      if ($exists !== $table) {
        $this->activate();
        break;
      }
    }

    $bookings = $wpdb->prefix . 'jacana_bookings';
    $required_columns = array('selected_services', 'additional_places', 'source_section', 'details_json');
    foreach ($required_columns as $column) {
      $has_column = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$bookings} LIKE %s", $column));
      if (!$has_column) {
        $this->activate();
        break;
      }
    }

    set_transient('jacana_crm_tables_ok', 1, DAY_IN_SECONDS);
  }

  public function ensure_scheduled_events()
  {
    if (!wp_next_scheduled('jacana_crm_daily_seo_audit')) {
      wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'jacana_crm_daily_seo_audit');
    }
    if (!wp_next_scheduled('jacana_crm_daily_seo_sync')) {
      wp_schedule_event(time() + (2 * HOUR_IN_SECONDS), 'daily', 'jacana_crm_daily_seo_sync');
    }
    if (!wp_next_scheduled('jacana_crm_daily_competitor_scan')) {
      wp_schedule_event(time() + (3 * HOUR_IN_SECONDS), 'daily', 'jacana_crm_daily_competitor_scan');
    }
    if (!wp_next_scheduled('jacana_crm_daily_content_opportunities')) {
      wp_schedule_event(time() + (4 * HOUR_IN_SECONDS), 'daily', 'jacana_crm_daily_content_opportunities');
    }
    if (!wp_next_scheduled('jacana_crm_weekly_strategic_report')) {
      wp_schedule_event(time() + DAY_IN_SECONDS, 'weekly', 'jacana_crm_weekly_strategic_report');
    }
  }

  public function register_cron_schedules($schedules)
  {
    if (!isset($schedules['weekly'])) {
      $schedules['weekly'] = array(
        'interval' => WEEK_IN_SECONDS,
        'display' => __('Once Weekly', 'jacana-luxe'),
      );
    }

    return $schedules;
  }

  public function register_admin_pages()
  {
    add_menu_page(
      'Jacana CRM',
      'Jacana CRM',
      'manage_options',
      'jacana-crm',
      array($this, 'render_dashboard_page'),
      'dashicons-chart-area',
      25
    );

    add_submenu_page(
      'jacana-crm',
      'Dashboard',
      'Dashboard',
      'manage_options',
      'jacana-crm',
      array($this, 'render_dashboard_page')
    );

    add_submenu_page(
      'jacana-crm',
      'Leads',
      'Leads',
      'manage_options',
      'jacana-crm-leads',
      array($this, 'render_leads_page')
    );

    add_submenu_page(
      'jacana-crm',
      'Bookings',
      'Bookings',
      'manage_options',
      'jacana-crm-bookings',
      array($this, 'render_bookings_page')
    );

    add_submenu_page(
      'jacana-crm',
      'Visitors',
      'Visitors',
      'manage_options',
      'jacana-crm-visitors',
      array($this, 'render_visitors_page')
    );

    add_submenu_page(
      'jacana-crm',
      'AI Journeys',
      'AI Journeys',
      'manage_options',
      'jacana-crm-ai-journeys',
      array($this, 'render_ai_journeys_page')
    );

    add_submenu_page(
      'jacana-crm',
      'Reviews',
      'Reviews',
      'manage_options',
      'jacana-crm-reviews',
      array($this, 'render_reviews_page')
    );

    add_submenu_page(
      'jacana-crm',
      'SEO Studio',
      'SEO Studio',
      'manage_options',
      'jacana-crm-seo',
      array($this, 'render_seo_page')
    );

    add_submenu_page(
      'jacana-crm',
      'AI Workbench',
      'AI Workbench',
      'manage_options',
      'jacana-crm-ai-lab',
      array($this, 'render_ai_lab_page')
    );

    add_submenu_page(
      'jacana-crm',
      'AI Settings',
      'AI Settings',
      'manage_options',
      'jacana-crm-ai-settings',
      array($this, 'render_ai_settings_page')
    );

    add_submenu_page(
      'jacana-crm',
      'AI Debug',
      'AI Debug',
      'manage_options',
      'jacana-crm-ai-debug',
      array($this, 'render_ai_debug_page')
    );
  }

  public function register_settings()
  {
    register_setting('jacana_crm_settings', 'jacana_gemini_api_key', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_text_field',
      'default' => ''
    ));
    register_setting('jacana_crm_settings', 'jacana_gemini_model', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_text_field',
      'default' => 'gemini-3.1-pro-preview'
    ));
    register_setting('jacana_crm_settings', 'jacana_maps_api_key', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_text_field',
      'default' => ''
    ));
    register_setting('jacana_crm_settings', 'jacana_smart_prompt_enabled', array(
      'type' => 'boolean',
      'sanitize_callback' => array($this, 'sanitize_checkbox'),
      'default' => 1
    ));
    register_setting('jacana_crm_settings', 'jacana_smart_prompt_delay_seconds', array(
      'type' => 'integer',
      'sanitize_callback' => array($this, 'sanitize_prompt_delay'),
      'default' => 30
    ));
    register_setting('jacana_crm_settings', 'jacana_smart_prompt_min_intent', array(
      'type' => 'integer',
      'sanitize_callback' => array($this, 'sanitize_percent_0_100'),
      'default' => 20
    ));
    register_setting('jacana_crm_settings', 'jacana_smart_prompt_min_interactions', array(
      'type' => 'integer',
      'sanitize_callback' => array($this, 'sanitize_non_negative_int'),
      'default' => 2
    ));
    register_setting('jacana_crm_settings', 'jacana_smart_prompt_min_scroll_depth', array(
      'type' => 'integer',
      'sanitize_callback' => array($this, 'sanitize_percent_0_100'),
      'default' => 20
    ));
    register_setting('jacana_crm_settings', 'jacana_smart_prompt_min_dwell_seconds', array(
      'type' => 'integer',
      'sanitize_callback' => array($this, 'sanitize_non_negative_int'),
      'default' => 20
    ));
    register_setting('jacana_crm_settings', 'jacana_smart_prompt_once_per_session', array(
      'type' => 'boolean',
      'sanitize_callback' => array($this, 'sanitize_checkbox'),
      'default' => 1
    ));
    register_setting('jacana_crm_settings', 'jacana_smart_prompt_system_prompt', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_textarea_field',
      'default' => ''
    ));
    register_setting('jacana_crm_settings', 'jacana_ai_brand_voice', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_textarea_field',
      'default' => ''
    ));
    register_setting('jacana_crm_settings', 'jacana_ai_conversion_goals', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_textarea_field',
      'default' => ''
    ));
    register_setting('jacana_crm_settings', 'jacana_ai_priority_services', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_textarea_field',
      'default' => ''
    ));
    register_setting('jacana_crm_settings', 'jacana_ai_planner_system_prompt', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_textarea_field',
      'default' => ''
    ));
    register_setting('jacana_crm_settings', 'jacana_ai_accommodation_prompt', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_textarea_field',
      'default' => ''
    ));
    register_setting('jacana_crm_settings', 'jacana_ai_vehicle_prompt', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_textarea_field',
      'default' => ''
    ));
    register_setting('jacana_crm_settings', 'jacana_ai_tailor_prompt', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_textarea_field',
      'default' => ''
    ));
    register_setting('jacana_crm_settings', 'jacana_ai_booking_prompt', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_textarea_field',
      'default' => ''
    ));
    register_setting('jacana_crm_settings', 'jacana_ai_detail_chat_prompt', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_textarea_field',
      'default' => ''
    ));
    register_setting('jacana_crm_settings', 'jacana_ai_section_context_chars', array(
      'type' => 'integer',
      'sanitize_callback' => array($this, 'sanitize_non_negative_int'),
      'default' => 900
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_enable_schema', array(
      'type' => 'boolean',
      'sanitize_callback' => array($this, 'sanitize_checkbox'),
      'default' => 1
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_enable_meta_fallback', array(
      'type' => 'boolean',
      'sanitize_callback' => array($this, 'sanitize_checkbox'),
      'default' => 1
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_org_name', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_text_field',
      'default' => get_bloginfo('name')
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_business_phone', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_text_field',
      'default' => ''
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_business_email', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_email',
      'default' => get_option('admin_email')
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_business_address', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_text_field',
      'default' => ''
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_business_city', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_text_field',
      'default' => 'Windhoek'
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_business_country', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_text_field',
      'default' => 'Namibia'
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_whatsapp', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_text_field',
      'default' => ''
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_price_range', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_text_field',
      'default' => '$$$'
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_default_description_suffix', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_text_field',
      'default' => 'Tailor-made Namibia tours, safaris, transfers and car rentals.'
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_daily_audit_enabled', array(
      'type' => 'boolean',
      'sanitize_callback' => array($this, 'sanitize_checkbox'),
      'default' => 1
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_competitor_sampling_mode', array(
      'type' => 'string',
      'sanitize_callback' => array($this, 'sanitize_competitor_sampling_mode'),
      'default' => 'top3_random2'
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_competitor_fixed_positions', array(
      'type' => 'string',
      'sanitize_callback' => array($this, 'sanitize_position_csv'),
      'default' => '1,2,3'
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_competitor_random_min', array(
      'type' => 'integer',
      'sanitize_callback' => array($this, 'sanitize_competitor_random_min'),
      'default' => 6
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_competitor_random_max', array(
      'type' => 'integer',
      'sanitize_callback' => array($this, 'sanitize_competitor_random_max'),
      'default' => 20
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_competitor_random_count', array(
      'type' => 'integer',
      'sanitize_callback' => array($this, 'sanitize_competitor_random_count'),
      'default' => 2
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_competitor_max_sites', array(
      'type' => 'integer',
      'sanitize_callback' => array($this, 'sanitize_competitor_max_sites'),
      'default' => 5
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_target_keywords', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_textarea_field',
      'default' => "namibia safari\nnamibia tours\netosha safari\nnamibia car rental\nwindhoek airport transfer"
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_data_source', array(
      'type' => 'string',
      'sanitize_callback' => array($this, 'sanitize_seo_data_source'),
      'default' => 'search_console'
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_search_console_enabled', array(
      'type' => 'boolean',
      'sanitize_callback' => array($this, 'sanitize_checkbox'),
      'default' => 0
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_gsc_property_url', array(
      'type' => 'string',
      'sanitize_callback' => 'esc_url_raw',
      'default' => home_url('/')
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_gsc_service_account_json', array(
      'type' => 'string',
      'sanitize_callback' => array($this, 'sanitize_service_account_json'),
      'default' => ''
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_sync_window_days', array(
      'type' => 'integer',
      'sanitize_callback' => array($this, 'sanitize_sync_window_days'),
      'default' => 7
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_query_row_limit', array(
      'type' => 'integer',
      'sanitize_callback' => array($this, 'sanitize_query_row_limit'),
      'default' => 50
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_report_email_recipients', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_textarea_field',
      'default' => get_option('admin_email')
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_phase3_automation_enabled', array(
      'type' => 'boolean',
      'sanitize_callback' => array($this, 'sanitize_checkbox'),
      'default' => 1
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_competitor_keyword_limit', array(
      'type' => 'integer',
      'sanitize_callback' => array($this, 'sanitize_competitor_keyword_limit'),
      'default' => 5
    ));
    register_setting('jacana_crm_settings', 'jacana_seo_weekly_report_enabled', array(
      'type' => 'boolean',
      'sanitize_callback' => array($this, 'sanitize_checkbox'),
      'default' => 1
    ));
    register_setting('jacana_crm_settings', 'jacana_ai_experience_enabled', array(
      'type' => 'boolean',
      'sanitize_callback' => array($this, 'sanitize_checkbox'),
      'default' => 1
    ));
    register_setting('jacana_crm_settings', 'jacana_ai_minor_delay_seconds', array(
      'type' => 'integer',
      'sanitize_callback' => array($this, 'sanitize_prompt_delay'),
      'default' => 8
    ));
    register_setting('jacana_crm_settings', 'jacana_ai_max_major_prompts', array(
      'type' => 'integer',
      'sanitize_callback' => array($this, 'sanitize_ai_prompt_limit'),
      'default' => 1
    ));
    register_setting('jacana_crm_settings', 'jacana_ai_max_minor_prompts', array(
      'type' => 'integer',
      'sanitize_callback' => array($this, 'sanitize_ai_prompt_limit'),
      'default' => 2
    ));
    register_setting('jacana_crm_settings', 'jacana_booking_email', array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_email',
      'default' => 'booking@jacanasafaristours.com'
    ));
    register_setting('jacana_crm_settings', 'jacana_ai_chatbot_icon', array(
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default' => ''
    ));
  }

  public function sanitize_checkbox($value)
  {
    return !empty($value) ? 1 : 0;
  }

  public function sanitize_non_negative_int($value)
  {
    return max(0, absint($value));
  }

  public function sanitize_percent_0_100($value)
  {
    return max(0, min(100, absint($value)));
  }

  public function sanitize_prompt_delay($value)
  {
    $seconds = max(5, absint($value));
    return min(600, $seconds);
  }

  public function sanitize_competitor_sampling_mode($value)
  {
    $allowed = array('top3_random2', 'immediately_above', 'above_below', 'custom_positions');
    $value = sanitize_key((string) $value);
    return in_array($value, $allowed, true) ? $value : 'top3_random2';
  }

  public function sanitize_position_csv($value)
  {
    $parts = preg_split('/[\s,]+/', (string) $value);
    $positions = array();
    foreach ((array) $parts as $part) {
      $int = absint($part);
      if ($int >= 1 && $int <= 100) {
        $positions[] = $int;
      }
    }
    $positions = array_values(array_unique($positions));
    sort($positions);
    return implode(',', array_slice($positions, 0, 5));
  }

  public function sanitize_competitor_random_min($value)
  {
    $value = absint($value);
    return max(1, min(100, $value ?: 6));
  }

  public function sanitize_competitor_random_max($value)
  {
    $value = absint($value);
    return max(2, min(100, $value ?: 20));
  }

  public function sanitize_competitor_random_count($value)
  {
    return max(0, min(2, absint($value)));
  }

  public function sanitize_competitor_max_sites($value)
  {
    return max(1, min(5, absint($value ?: 5)));
  }

  public function sanitize_competitor_keyword_limit($value)
  {
    return max(1, min(10, absint($value ?: 5)));
  }

  public function sanitize_seo_data_source($value)
  {
    $allowed = array('search_console', 'manual');
    $value = sanitize_key((string) $value);
    return in_array($value, $allowed, true) ? $value : 'search_console';
  }

  public function sanitize_service_account_json($value)
  {
    $text = trim((string) $value);
    if ($text === '') {
      return '';
    }
    $decoded = json_decode($text, true);
    return is_array($decoded) ? wp_json_encode($decoded) : '';
  }

  public function sanitize_sync_window_days($value)
  {
    return max(1, min(28, absint($value ?: 7)));
  }

  public function sanitize_query_row_limit($value)
  {
    return max(10, min(250, absint($value ?: 50)));
  }

  public function sanitize_ai_prompt_limit($value)
  {
    return max(0, min(6, absint($value ?: 0)));
  }

  private function get_admin_notice()
  {
    $type = sanitize_key((string) ($_GET['jacana_notice'] ?? ''));
    if ($type === '') {
      return array('', '');
    }

    $messages = array(
      'lead_updated' => array('success', __('Lead updated.', 'jacana-luxe')),
      'lead_missing' => array('error', __('Lead could not be found.', 'jacana-luxe')),
      'lead_invalid' => array('error', __('Lead update request was invalid.', 'jacana-luxe')),
      'review_updated' => array('success', __('Review updated.', 'jacana-luxe')),
      'review_invalid' => array('error', __('Review request was invalid.', 'jacana-luxe')),
      'widget_generated' => array('success', __('AI widget content suggestions generated.', 'jacana-luxe')),
      'widget_ai_failed' => array('error', __('AI could not generate widget content suggestions for that page.', 'jacana-luxe')),
      'seo_generated' => array('success', __('AI SEO suggestions generated.', 'jacana-luxe')),
      'seo_applied' => array('success', __('SEO suggestion applied.', 'jacana-luxe')),
      'seo_approved' => array('success', __('SEO suggestion approved.', 'jacana-luxe')),
      'seo_rejected' => array('success', __('SEO suggestion rejected.', 'jacana-luxe')),
      'seo_bulk_applied' => array('success', __('Approved SEO suggestions applied.', 'jacana-luxe')),
      'seo_audit_run' => array('success', __('Daily SEO audit ran successfully.', 'jacana-luxe')),
      'seo_sync_run' => array('success', __('SEO data sync completed.', 'jacana-luxe')),
      'seo_competitor_scan_run' => array('success', __('Competitor intelligence scan completed.', 'jacana-luxe')),
      'seo_opportunities_run' => array('success', __('Content opportunities refreshed.', 'jacana-luxe')),
      'seo_report_run' => array('success', __('Strategic SEO report generated.', 'jacana-luxe')),
      'seo_invalid' => array('error', __('SEO request was invalid.', 'jacana-luxe')),
      'seo_ai_failed' => array('error', __('AI could not generate SEO suggestions for that page.', 'jacana-luxe')),
      'seo_sync_failed' => array('error', __('SEO data sync failed. Check credentials and property access.', 'jacana-luxe')),
      'seo_competitor_scan_failed' => array('error', __('Competitor intelligence scan failed. Check the API key or search fetch response.', 'jacana-luxe')),
      'seo_opportunities_failed' => array('error', __('Content opportunity generation failed.', 'jacana-luxe')),
      'seo_report_failed' => array('error', __('Strategic report generation failed.', 'jacana-luxe')),
    );

    if ($type === 'seo_competitor_scan_failed') {
      $detail = (string) get_option('jacana_seo_last_competitor_scan_error', '');
      if ($detail !== '') {
        $messages[$type][1] .= ' ' . $detail;
      }
    }

    return $messages[$type] ?? array('', '');
  }

  private function admin_page_url($slug, $args = array())
  {
    return add_query_arg($args, admin_url('admin.php?page=' . $slug));
  }

  private function render_admin_shell_start($page_title, $eyebrow = '', $description = '')
  {
    list($notice_type, $notice_message) = $this->get_admin_notice();
    echo '<div class="wrap jacana-admin-wrap">';
    echo '<div class="jacana-admin-shell">';
    echo '<header class="jacana-admin-hero">';
    if ($eyebrow !== '') {
      echo '<span class="jacana-admin-eyebrow">' . esc_html($eyebrow) . '</span>';
    }
    echo '<div class="jacana-admin-hero-copy">';
    echo '<h1>' . esc_html($page_title) . '</h1>';
    if ($description !== '') {
      echo '<p>' . esc_html($description) . '</p>';
    }
    echo '</div>';
    echo '<nav class="jacana-admin-tabs">';
    $pages = array(
      'jacana-crm' => __('Dashboard', 'jacana-luxe'),
      'jacana-crm-leads' => __('Leads', 'jacana-luxe'),
      'jacana-crm-bookings' => __('Bookings', 'jacana-luxe'),
      'jacana-crm-visitors' => __('Visitors', 'jacana-luxe'),
      'jacana-crm-ai-journeys' => __('AI Journeys', 'jacana-luxe'),
      'jacana-crm-reviews' => __('Reviews', 'jacana-luxe'),
      'jacana-crm-seo' => __('SEO Studio', 'jacana-luxe'),
      'jacana-crm-ai-lab' => __('AI Workbench', 'jacana-luxe'),
      'jacana-crm-ai-settings' => __('AI Settings', 'jacana-luxe'),
      'jacana-crm-ai-debug' => __('AI Debug', 'jacana-luxe'),
    );
    $current = sanitize_key((string) ($_GET['page'] ?? 'jacana-crm'));
    foreach ($pages as $slug => $label) {
      $active = $current === $slug ? ' is-active' : '';
      echo '<a class="jacana-admin-tab' . esc_attr($active) . '" href="' . esc_url($this->admin_page_url($slug)) . '">' . esc_html($label) . '</a>';
    }
    echo '</nav>';
    echo '</header>';
    echo '<div class="jacana-admin-notices" aria-live="polite">';
    if ($notice_type && $notice_message) {
      echo '<div class="notice notice-' . esc_attr($notice_type) . ' is-dismissible"><p>' . esc_html($notice_message) . '</p></div>';
    }
    echo '</div>';
  }

  private function render_admin_shell_end()
  {
    echo '</div></div>';
  }

  private function get_count($table, $where_sql = '1=1')
  {
    global $wpdb;
    return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where_sql}");
  }

  private function get_dashboard_metrics()
  {
    global $wpdb;
    $leads = $wpdb->prefix . 'jacana_leads';
    $visitors = $wpdb->prefix . 'jacana_visitors';
    $events = $wpdb->prefix . 'jacana_events';
    $sessions = $wpdb->prefix . 'jacana_sessions';
    $pageviews = $wpdb->prefix . 'jacana_pageviews';

    $metrics = array(
      'total_leads' => $this->get_count($leads),
      'hot_leads' => $this->get_count($leads, 'conversion_score >= 40'),
      'new_leads' => $this->get_count($leads, "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"),
      'active_visitors' => $this->get_count($visitors, "last_seen >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
      'conversion_signals' => $this->get_count($events, "type = 'conversion_signal' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
      'open_sessions' => $this->get_count($sessions, "last_seen >= DATE_SUB(NOW(), INTERVAL 1 DAY)"),
      'pageviews_7d' => $this->get_count($pageviews, "started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
    );

    $metrics['avg_lead_score'] = (int) $wpdb->get_var("SELECT COALESCE(ROUND(AVG(conversion_score)), 0) FROM {$leads}");
    return $metrics;
  }

  private function get_top_services($limit = 5)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_leads';
    return $wpdb->get_results($wpdb->prepare(
      "SELECT service_interest AS label, COUNT(*) AS total
       FROM {$table}
       WHERE service_interest <> ''
       GROUP BY service_interest
       ORDER BY total DESC
       LIMIT %d",
      $limit
    ));
  }

  private function get_top_pages($limit = 6)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_pageviews';
    return $wpdb->get_results($wpdb->prepare(
      "SELECT url, COUNT(*) AS total, MAX(started_at) AS last_seen
       FROM {$table}
       WHERE url <> ''
       GROUP BY url
       ORDER BY total DESC
       LIMIT %d",
      $limit
    ));
  }

  private function get_recent_conversion_events($limit = 8)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_events';
    return $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM {$table}
       WHERE type IN ('conversion_signal', 'lead_submit', 'widget_engagement')
       ORDER BY created_at DESC
       LIMIT %d",
      $limit
    ));
  }

  private function get_recent_leads($limit = 8)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_leads';
    return $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM {$table}
       ORDER BY updated_at DESC
       LIMIT %d",
      $limit
    ));
  }

  private function get_conversion_recommendations($metrics)
  {
    $recommendations = array();
    if ((int) ($metrics['hot_leads'] ?? 0) < 5) {
      $recommendations[] = __('Increase high-intent capture by tightening booking CTAs on top-performing pages.', 'jacana-luxe');
    }
    if ((int) ($metrics['conversion_signals'] ?? 0) > (int) ($metrics['new_leads'] ?? 0) * 3) {
      $recommendations[] = __('Many visitors show intent without converting. Add more direct quote-request prompts on service pages.', 'jacana-luxe');
    }
    if ((int) ($metrics['pageviews_7d'] ?? 0) > 0 && (int) ($metrics['active_visitors'] ?? 0) > 0) {
      $recommendations[] = __('Use the AI workbench to generate new CTA copy for the most visited pages before production launch.', 'jacana-luxe');
    }
    if (!$recommendations) {
      $recommendations[] = __('Traffic and conversion signals look balanced. Use the AI workbench for sharper page copy and SEO cleanup before launch.', 'jacana-luxe');
    }
    return $recommendations;
  }

  public function handle_admin_post_actions()
  {
    if (!is_admin() || !current_user_can('manage_options')) {
      return;
    }

    if (empty($_POST['jacana_crm_action'])) {
      return;
    }

    $action = sanitize_key((string) wp_unslash($_POST['jacana_crm_action']));
    switch ($action) {
      case 'update_lead':
        check_admin_referer('jacana_crm_update_lead');
        $lead_id = absint($_POST['lead_id'] ?? 0);
        if ($lead_id <= 0) {
          wp_safe_redirect($this->admin_page_url('jacana-crm-leads', array('jacana_notice' => 'lead_invalid')));
          exit;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'jacana_leads';
        $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$table} WHERE id = %d", $lead_id));
        if (!$existing) {
          wp_safe_redirect($this->admin_page_url('jacana-crm-leads', array('jacana_notice' => 'lead_missing')));
          exit;
        }

        $status = sanitize_key((string) ($_POST['status'] ?? 'new'));
        $priority = sanitize_key((string) ($_POST['priority'] ?? 'normal'));
        $allowed_status = array('new', 'qualified', 'proposal_sent', 'won', 'lost');
        $allowed_priority = array('low', 'normal', 'high', 'vip');

        $wpdb->update(
          $table,
          array(
            'status' => in_array($status, $allowed_status, true) ? $status : 'new',
            'priority' => in_array($priority, $allowed_priority, true) ? $priority : 'normal',
            'internal_notes' => sanitize_textarea_field((string) ($_POST['internal_notes'] ?? '')),
            'next_action_at' => !empty($_POST['next_action_at']) ? sanitize_text_field((string) $_POST['next_action_at']) : null,
            'updated_at' => current_time('mysql'),
          ),
          array('id' => $lead_id)
        );

        wp_safe_redirect($this->admin_page_url('jacana-crm-leads', array('jacana_notice' => 'lead_updated', 'lead' => $lead_id)));
        exit;

      case 'update_review':
        check_admin_referer('jacana_crm_update_review');
        $review_id = absint($_POST['review_id'] ?? 0);
        if ($review_id <= 0) {
          wp_safe_redirect($this->admin_page_url('jacana-crm-reviews', array('jacana_notice' => 'review_invalid')));
          exit;
        }

        $status = sanitize_key((string) ($_POST['review_status'] ?? 'pending'));
        $allowed_review_status = array('pending', 'approved', 'rejected', 'featured', 'archived');
        if (!in_array($status, $allowed_review_status, true)) {
          $status = 'pending';
        }

        $this->update_review_status($review_id, $status);
        wp_safe_redirect($this->admin_page_url('jacana-crm-reviews', array('jacana_notice' => 'review_updated')));
        exit;

      case 'delete_review':
        check_admin_referer('jacana_crm_delete_review');
        $review_id = absint($_POST['review_id'] ?? 0);
        if ($review_id > 0) {
          $this->delete_review($review_id);
          wp_safe_redirect($this->admin_page_url('jacana-crm-reviews', array('jacana_notice' => 'review_deleted')));
        } else {
          wp_safe_redirect($this->admin_page_url('jacana-crm-reviews', array('jacana_notice' => 'review_invalid')));
        }
        exit;

      case 'generate_seo_suggestions':
        check_admin_referer('jacana_crm_generate_seo');
        $post_id = absint($_POST['post_id'] ?? 0);
        if ($post_id <= 0) {
          wp_safe_redirect($this->admin_page_url('jacana-crm-seo', array('jacana_notice' => 'seo_invalid')));
          exit;
        }
        $result = $this->generate_seo_suggestions_for_post($post_id);
        $notice = is_wp_error($result) ? 'seo_ai_failed' : 'seo_generated';
        wp_safe_redirect($this->admin_page_url('jacana-crm-seo', array('jacana_notice' => $notice, 'post_id' => $post_id)));
        exit;

      case 'generate_widget_suggestions':
        check_admin_referer('jacana_crm_generate_widget_suggestions');
        $post_id = absint($_POST['post_id'] ?? 0);
        if ($post_id <= 0) {
          wp_safe_redirect($this->admin_page_url('jacana-crm-seo', array('jacana_notice' => 'seo_invalid')));
          exit;
        }
        $result = $this->generate_widget_content_suggestions_for_post($post_id);
        $notice = is_wp_error($result) ? 'widget_ai_failed' : 'widget_generated';
        wp_safe_redirect($this->admin_page_url('jacana-crm-seo', array('jacana_notice' => $notice, 'post_id' => $post_id, 'seo_panel' => 'workspace')));
        exit;

      case 'seo_update_suggestion':
        check_admin_referer('jacana_crm_update_seo_suggestion');
        $suggestion_id = absint($_POST['suggestion_id'] ?? 0);
        $status = sanitize_key((string) ($_POST['suggestion_status'] ?? 'reviewed'));
        $post_id = absint($_POST['post_id'] ?? 0);
        if ($suggestion_id <= 0 || !in_array($status, array('approved', 'rejected', 'reviewed'), true)) {
          wp_safe_redirect($this->admin_page_url('jacana-crm-seo', array('jacana_notice' => 'seo_invalid', 'post_id' => $post_id)));
          exit;
        }
        $this->update_seo_suggestion_status($suggestion_id, $status);
        $notice = $status === 'approved' ? 'seo_approved' : ($status === 'rejected' ? 'seo_rejected' : '');
        wp_safe_redirect($this->admin_page_url('jacana-crm-seo', array_filter(array('jacana_notice' => $notice, 'post_id' => $post_id))));
        exit;

      case 'seo_apply_suggestion':
        check_admin_referer('jacana_crm_apply_seo_suggestion');
        $suggestion_id = absint($_POST['suggestion_id'] ?? 0);
        $post_id = absint($_POST['post_id'] ?? 0);
        if ($suggestion_id <= 0) {
          wp_safe_redirect($this->admin_page_url('jacana-crm-seo', array('jacana_notice' => 'seo_invalid', 'post_id' => $post_id)));
          exit;
        }
        $this->apply_seo_suggestion($suggestion_id);
        wp_safe_redirect($this->admin_page_url('jacana-crm-seo', array('jacana_notice' => 'seo_applied', 'post_id' => $post_id)));
        exit;

      case 'seo_apply_approved':
        check_admin_referer('jacana_crm_apply_approved_seo');
        $post_id = absint($_POST['post_id'] ?? 0);
        if ($post_id <= 0) {
          wp_safe_redirect($this->admin_page_url('jacana-crm-seo', array('jacana_notice' => 'seo_invalid')));
          exit;
        }
        $this->apply_approved_suggestions_for_post($post_id);
        wp_safe_redirect($this->admin_page_url('jacana-crm-seo', array('jacana_notice' => 'seo_bulk_applied', 'post_id' => $post_id)));
        exit;

      case 'run_seo_audit':
        check_admin_referer('jacana_crm_run_seo_audit');
        $this->run_daily_seo_audit();
        wp_safe_redirect($this->admin_page_url('jacana-crm-seo', array('seo_panel' => 'performance', 'jacana_notice' => 'seo_audit_run')));
        exit;

      case 'run_seo_sync':
        check_admin_referer('jacana_crm_run_seo_sync');
        $this->run_daily_seo_sync();
        $notice = get_option('jacana_seo_last_sync_error') ? 'seo_sync_failed' : 'seo_sync_run';
        wp_safe_redirect($this->admin_page_url('jacana-crm-seo', array('seo_panel' => 'performance', 'jacana_notice' => $notice)));
        exit;

      case 'run_competitor_scan':
        check_admin_referer('jacana_crm_run_competitor_scan');
        $result = $this->run_daily_competitor_scan(true);
        $notice = is_wp_error($result) ? 'seo_competitor_scan_failed' : 'seo_competitor_scan_run';
        wp_safe_redirect($this->admin_page_url('jacana-crm-seo', array('seo_panel' => 'performance', 'jacana_notice' => $notice)));
        exit;

      case 'generate_content_opportunities':
        check_admin_referer('jacana_crm_generate_content_opportunities');
        $result = $this->run_daily_content_opportunities(true);
        $notice = is_wp_error($result) ? 'seo_opportunities_failed' : 'seo_opportunities_run';
        wp_safe_redirect($this->admin_page_url('jacana-crm-seo', array('seo_panel' => 'performance', 'jacana_notice' => $notice)));
        exit;

      case 'generate_strategic_report':
        check_admin_referer('jacana_crm_generate_strategic_report');
        $result = $this->run_weekly_strategic_report(true);
        $notice = is_wp_error($result) ? 'seo_report_failed' : 'seo_report_run';
        wp_safe_redirect($this->admin_page_url('jacana-crm-seo', array('seo_panel' => 'reports', 'jacana_notice' => $notice)));
        exit;

      case 'export_seo_report_csv':
        check_admin_referer('jacana_crm_export_seo_report');
        $this->export_seo_report('csv');
        exit;

      case 'export_seo_report_excel':
        check_admin_referer('jacana_crm_export_seo_report');
        $this->export_seo_report('excel');
        exit;

      case 'export_seo_report_print':
        check_admin_referer('jacana_crm_export_seo_report');
        $this->export_seo_report('print');
        exit;
    }
  }

  private function get_brand_context_text()
  {
    $parts = array_filter(array(
      'Brand voice: ' . (string) get_option('jacana_ai_brand_voice', ''),
      'Conversion goals: ' . (string) get_option('jacana_ai_conversion_goals', ''),
      'Priority services: ' . (string) get_option('jacana_ai_priority_services', ''),
    ));
    return trim(implode("\n", $parts));
  }

  private function execute_gemini_request($prompt, $json_mode = false, $collect_debug = false)
  {
    $api_key = sanitize_text_field((string) get_option('jacana_gemini_api_key', ''));
    if ($api_key === '') {
      $error = new WP_Error('missing_api_key', __('Gemini API key is missing.', 'jacana-luxe'));
      if ($collect_debug) {
        $error->add_data(array(
          'attempts' => array(),
          'last_error' => 'missing_api_key',
        ));
      }
      return $error;
    }

    $payload = array(
      'contents' => array(
        array('parts' => array(array('text' => (string) $prompt)))
      ),
      'generationConfig' => array(
        'temperature' => 0.55,
      ),
    );
    if ($json_mode) {
      $payload['generationConfig']['responseMimeType'] = 'application/json';
    }

    $attempts = array();
    $last_error = 'ai_failed';
    $last_body = '';
    foreach ($this->get_gemini_models_to_try() as $model) {
      $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . urlencode($api_key);
      $response = wp_remote_post(
        $url,
        array(
          'headers' => array('Content-Type' => 'application/json'),
          'body' => wp_json_encode($payload),
          'timeout' => 45,
        )
      );

      if (is_wp_error($response)) {
        $last_error = $response->get_error_message();
        if ($collect_debug) {
          $attempts[] = array(
            'model' => $model,
            'status' => 0,
            'error' => $last_error,
            'body' => '',
          );
        }
        continue;
      }

      $status = (int) wp_remote_retrieve_response_code($response);
      $body = (string) wp_remote_retrieve_body($response);
      $last_body = $body;
      $data = json_decode($body, true);
      $text = (string) ($data['candidates'][0]['content']['parts'][0]['text'] ?? '');

      if ($status >= 400) {
        $last_error = 'HTTP ' . $status;
        if ($collect_debug) {
          $attempts[] = array(
            'model' => $model,
            'status' => $status,
            'error' => $last_error,
            'body' => $body,
          );
        }
        continue;
      }

      if ($text === '') {
        $last_error = 'empty_candidates';
        if ($collect_debug) {
          $attempts[] = array(
            'model' => $model,
            'status' => $status,
            'error' => $last_error,
            'body' => $body,
          );
        }
        continue;
      }

      if ($collect_debug) {
        $attempts[] = array(
          'model' => $model,
          'status' => $status,
          'error' => '',
          'body' => $body,
        );
      }
      return array(
        'ok' => true,
        'model' => $model,
        'text' => $text,
        'data' => $data,
        'attempts' => $attempts,
      );
    }

    $error = new WP_Error('ai_failed', __('AI generation failed.', 'jacana-luxe'));
    if ($collect_debug) {
      $error->add_data(array(
        'attempts' => $attempts,
        'last_error' => $last_error,
        'last_body' => $last_body,
      ));
    }
    return $error;
  }

  private function call_gemini_text($prompt, $json_mode = false)
  {
    $result = $this->execute_gemini_request($prompt, $json_mode, false);
    if (is_wp_error($result)) {
      return $result;
    }
    return (string) ($result['text'] ?? '');
  }

  private function call_gemini_with_google_search($prompt, $models = array())
  {
    $api_key = get_option('jacana_gemini_api_key', '');
    if (!$api_key) {
      return new WP_Error('missing_api_key', __('Gemini API key is missing.', 'jacana-luxe'));
    }

    $models = array_values(array_filter(array_unique(array_merge(
      (array) $models,
      array('gemini-2.5-flash', 'gemini-2.5-pro', 'gemini-2.0-flash')
    ))));

    $tool_variants = array(
      array('google_search' => new stdClass()),
      array('googleSearch' => new stdClass()),
    );
    $payload_variants = array();
    foreach ($tool_variants as $tool_variant) {
      $payload_variants[] = array(
        'contents' => array(
          array('parts' => array(array('text' => (string) $prompt)))
        ),
        'tools' => array($tool_variant),
        'generationConfig' => array(
          'temperature' => 0.2,
        ),
      );
      $payload_variants[] = array(
        'contents' => array(
          array('parts' => array(array('text' => (string) $prompt)))
        ),
        'config' => array(
          'tools' => array($tool_variant),
          'temperature' => 0.2,
        ),
      );
    }
    $auth_variants = array(
      array(
        'url_suffix' => '',
        'headers' => array('x-goog-api-key' => $api_key),
      ),
      array(
        'url_suffix' => '?key=' . urlencode($api_key),
        'headers' => array(),
      ),
    );
    $last_error = '';

    foreach ($models as $model) {
      foreach ($payload_variants as $payload) {
        foreach ($auth_variants as $auth) {
          $response = wp_remote_post(
            'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent' . $auth['url_suffix'],
            array(
              'headers' => array_merge(
                array('Content-Type' => 'application/json'),
                $auth['headers']
              ),
              'body' => wp_json_encode($payload),
              'timeout' => 45,
            )
          );

          if (is_wp_error($response)) {
            $last_error = $response->get_error_message();
            continue;
          }

          $status = (int) wp_remote_retrieve_response_code($response);
          $body = (string) wp_remote_retrieve_body($response);
          if ($status >= 400) {
            $last_error = 'HTTP ' . $status . ': ' . wp_trim_words(wp_strip_all_tags($body), 24, '...');
            continue;
          }

          $data = json_decode($body, true);
          $parts = (array) ($data['candidates'][0]['content']['parts'] ?? array());
          $text_parts = array();
          foreach ($parts as $part) {
            if (!empty($part['text'])) {
              $text_parts[] = (string) $part['text'];
            }
          }
          $text = trim(implode("\n", $text_parts));
          if ($text !== '') {
            return array(
              'text' => $text,
              'grounding' => (array) ($data['candidates'][0]['groundingMetadata']['groundingChunks'] ?? array()),
            );
          }
        }
      }
    }

    return new WP_Error('ai_search_failed', $last_error !== '' ? $last_error : __('Gemini grounded search failed.', 'jacana-luxe'));
  }

  private function get_page_audit_rows()
  {
    $post_types = get_post_types(array('public' => true), 'names');
    unset($post_types['attachment'], $post_types['elementor_library'], $post_types['e-landing-page']);

    $search = sanitize_text_field((string) ($_GET['seo_s'] ?? ''));
    $type_filter = sanitize_key((string) ($_GET['seo_type'] ?? ''));
    if ($type_filter && isset($post_types[$type_filter])) {
      $post_types = array($type_filter);
    }

    $pages = get_posts(array(
      'post_type' => array_values($post_types),
      'post_status' => array('publish', 'draft', 'pending', 'future'),
      'posts_per_page' => 200,
      'orderby' => 'modified',
      'order' => 'DESC',
      's' => $search,
    ));

    $rows = array();
    foreach ($pages as $page) {
      $rows[] = $this->get_post_current_seo_state($page);
    }

    return $rows;
  }

  private function build_seo_description_for_post($post)
  {
    $text = '';
    if ($post instanceof WP_Post && has_excerpt($post)) {
      $text = trim(wp_strip_all_tags($post->post_excerpt));
    }
    if ($text === '' && $post instanceof WP_Post) {
      $text = trim(wp_trim_words(wp_strip_all_tags($post->post_content), 24, ''));
    }
    if ($text === '') {
      $text = trim((string) get_option('jacana_seo_default_description_suffix', ''));
    }
    return wp_strip_all_tags($text);
  }

  private function has_yoast_seo()
  {
    return defined('WPSEO_VERSION');
  }

  private function get_yoast_meta_map()
  {
    return array(
      'seo_title' => '_yoast_wpseo_title',
      'meta_description' => '_yoast_wpseo_metadesc',
      'focus_keyphrase' => '_yoast_wpseo_focuskw',
      'og_title' => '_yoast_wpseo_opengraph-title',
      'og_description' => '_yoast_wpseo_opengraph-description',
    );
  }

  private function get_applyable_seo_fields()
  {
    return array('seo_title', 'meta_description', 'focus_keyphrase', 'og_title', 'og_description', 'excerpt');
  }

  private function can_apply_suggestion_field($field_key)
  {
    $field_key = (string) $field_key;
    if (strpos($field_key, 'widget::') === 0) {
      return true;
    }

    return in_array($field_key, $this->get_applyable_seo_fields(), true);
  }

  private function get_post_current_seo_state($post)
  {
    $post = $post instanceof WP_Post ? $post : get_post($post);
    if (!$post instanceof WP_Post) {
      return array();
    }

    $content = wp_strip_all_tags((string) $post->post_content);
    $excerpt = has_excerpt($post) ? trim(wp_strip_all_tags((string) $post->post_excerpt)) : '';
    $featured_image = get_the_post_thumbnail_url($post, 'medium_large') ?: '';
    $permalink = get_permalink($post);
    $internal_links = 0;
    if ($post->post_content) {
      preg_match_all('/href=["\']([^"\']+)["\']/i', (string) $post->post_content, $matches);
      foreach ((array) ($matches[1] ?? array()) as $href) {
        if (strpos((string) $href, home_url('/')) === 0 || strpos((string) $href, '/') === 0) {
          $internal_links++;
        }
      }
    }

    $yoast = array();
    foreach ($this->get_yoast_meta_map() as $field => $meta_key) {
      $yoast[$field] = trim((string) get_post_meta($post->ID, $meta_key, true));
    }

    $current_title = $yoast['seo_title'] !== '' ? $yoast['seo_title'] : get_the_title($post);
    $current_description = $yoast['meta_description'] !== '' ? $yoast['meta_description'] : $this->build_seo_description_for_post($post);
    $og_title = $yoast['og_title'] !== '' ? $yoast['og_title'] : $current_title;
    $og_description = $yoast['og_description'] !== '' ? $yoast['og_description'] : $current_description;

    $score = 0;
    $issues = array();
    if ($current_title !== '') {
      $score += 18;
    } else {
      $issues[] = __('Missing SEO title', 'jacana-luxe');
    }
    if ($current_description !== '') {
      $score += 18;
    } else {
      $issues[] = __('Missing meta description', 'jacana-luxe');
    }
    if ($yoast['focus_keyphrase'] !== '') {
      $score += 12;
    } else {
      $issues[] = __('Missing focus keyphrase', 'jacana-luxe');
    }
    if ($excerpt !== '') {
      $score += 10;
    } else {
      $issues[] = __('No excerpt/snippet', 'jacana-luxe');
    }
    if ($featured_image !== '') {
      $score += 10;
    } else {
      $issues[] = __('No featured image', 'jacana-luxe');
    }
    if ($og_title !== '') {
      $score += 8;
    }
    if ($og_description !== '') {
      $score += 8;
    }
    if (str_word_count($content) >= 250) {
      $score += 10;
    } else {
      $issues[] = __('Thin content', 'jacana-luxe');
    }
    if ($internal_links > 0) {
      $score += 6;
    } else {
      $issues[] = __('No internal links detected', 'jacana-luxe');
    }

    return array(
      'id' => (int) $post->ID,
      'title' => get_the_title($post),
      'status' => $post->post_status,
      'type' => $post->post_type,
      'permalink' => $permalink,
      'edit_link' => get_edit_post_link($post->ID),
      'excerpt' => $excerpt,
      'content' => $content,
      'word_count' => str_word_count($content),
      'has_image' => $featured_image !== '',
      'featured_image' => $featured_image,
      'modified' => $post->post_modified,
      'internal_links' => $internal_links,
      'seo_score' => min(100, $score),
      'issues' => $issues,
      'current' => array(
        'seo_title' => $current_title,
        'meta_description' => $current_description,
        'focus_keyphrase' => $yoast['focus_keyphrase'],
        'og_title' => $og_title,
        'og_description' => $og_description,
        'excerpt' => $excerpt,
      ),
      'yoast' => $yoast,
    );
  }

  private function get_widget_content_registry()
  {
    return array(
      'jacana_main_services_grid' => array(
        'label' => __('Main Services Grid', 'jacana-luxe'),
        'fields' => array('heading', 'intro', 'chat_cta_label', 'services.*.title', 'services.*.badge', 'services.*.description', 'services.*.highlights', 'services.*.cta_label'),
      ),
      'jacana_car_rental_showcase' => array(
        'label' => __('Car Rental Showcase', 'jacana-luxe'),
        'fields' => array('heading', 'intro', 'panel_kicker', 'default_cta_label', 'vehicles.*.title', 'vehicles.*.summary', 'vehicles.*.details', 'vehicles.*.cta_label'),
      ),
      'jacana_car_rental_offers' => array(
        'label' => __('Car Rental Offers', 'jacana-luxe'),
        'fields' => array('heading', 'intro', 'vehicles.*.title', 'vehicles.*.copy', 'vehicles.*.cta_label'),
      ),
      'jacana_car_rental_fleet_explorer' => array(
        'label' => __('Car Rental Fleet Explorer', 'jacana-luxe'),
        'fields' => array('heading', 'intro', 'default_cta_label', 'filters_label', 'vehicles.*.title', 'vehicles.*.summary', 'vehicles.*.route_fit', 'vehicles.*.cta_label'),
      ),
      'jacana_social_proof' => array(
        'label' => __('Social Proof', 'jacana-luxe'),
        'fields' => array('heading', 'intro', 'feedback_label'),
      ),
      'jacana_team_profiles' => array(
        'label' => __('Team Profiles', 'jacana-luxe'),
        'fields' => array('heading', 'intro', 'profiles.*.name', 'profiles.*.role', 'profiles.*.bio', 'profiles.*.highlight'),
      ),
      'jacana_tailor_made_story' => array(
        'label' => __('Tailor-Made Story', 'jacana-luxe'),
        'fields' => array('kicker', 'heading', 'story', 'trust_line', 'features.*.title', 'features.*.copy', 'primary_label', 'secondary_label'),
      ),
      'jacana_tours_inclusions' => array(
        'label' => __('Tours Inclusions', 'jacana-luxe'),
        'fields' => array('section_kicker', 'section_heading', 'section_intro', 'guided_title', 'guided_badge', 'guided_copy', 'self_title', 'self_badge', 'self_copy'),
      ),
      'jacana_faq_downloads' => array(
        'label' => __('FAQ Downloads', 'jacana-luxe'),
        'fields' => array('kicker', 'heading', 'intro', 'search_placeholder', 'weather_title', 'weather_copy', 'weather_link_label', 'faq_items.*.question', 'faq_items.*.answer', 'cta_label'),
      ),
      'jacana_downloads_library' => array(
        'label' => __('Downloads Library', 'jacana-luxe'),
        'fields' => array('kicker', 'heading', 'intro', 'support_copy', 'support_cta', 'downloads.*.title', 'downloads.*.description', 'downloads.*.button_label'),
      ),
      'jacana_gallery_hero' => array(
        'label' => __('Gallery Hero', 'jacana-luxe'),
        'fields' => array('kicker', 'title', 'copy', 'highlights', 'primary_cta_label', 'secondary_cta_label', 'stat_one_value', 'stat_one_label', 'stat_two_value', 'stat_two_label', 'stat_three_value', 'stat_three_label', 'accent_images.*.label'),
      ),
      'jacana_gallery_grid' => array(
        'label' => __('Gallery Grid', 'jacana-luxe'),
        'fields' => array('heading', 'intro', 'images.*.title', 'images.*.location', 'images.*.category', 'images.*.description'),
      ),
      'jacana_accommodation_styles' => array(
        'label' => __('Accommodation Styles', 'jacana-luxe'),
        'fields' => array('section_kicker', 'heading', 'copy', 'styles.*.style_title', 'styles.*.style_desc', 'card_title', 'card_copy', 'card_button_label'),
      ),
    );
  }

  private function get_elementor_document_data($post_id)
  {
    $raw = get_post_meta($post_id, '_elementor_data', true);
    if (is_array($raw)) {
      return $raw;
    }
    if (!is_string($raw) || trim($raw) === '') {
      return array();
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : array();
  }

  private function save_elementor_document_data($post_id, $document)
  {
    update_post_meta($post_id, '_elementor_data', wp_slash(wp_json_encode($document)));
    update_post_meta($post_id, '_elementor_edit_mode', 'builder');
    if (class_exists('\Elementor\Plugin')) {
      \Elementor\Plugin::$instance->files_manager->clear_cache();
    }
  }

  private function extract_widget_path_values($settings, $path, $prefix = '')
  {
    $parts = explode('.', (string) $path);
    return $this->extract_widget_path_values_recursive($settings, $parts, $prefix);
  }

  private function extract_widget_path_values_recursive($subject, $parts, $prefix)
  {
    if (empty($parts)) {
      if (is_array($subject)) {
        return array();
      }
      $value = trim(wp_strip_all_tags((string) $subject));
      if ($value === '') {
        return array();
      }
      return array(array(
        'path' => ltrim($prefix, '.'),
        'value' => $value,
      ));
    }

    $segment = array_shift($parts);
    $results = array();

    if ($segment === '*') {
      if (!is_array($subject)) {
        return array();
      }
      foreach ($subject as $index => $item) {
        $results = array_merge($results, $this->extract_widget_path_values_recursive($item, $parts, $prefix . '.' . $index));
      }
      return $results;
    }

    if (is_array($subject) && array_key_exists($segment, $subject)) {
      return $this->extract_widget_path_values_recursive($subject[$segment], $parts, $prefix . '.' . $segment);
    }

    return array();
  }

  private function collect_widget_content_blocks_recursive($elements, $registry, &$blocks)
  {
    foreach ((array) $elements as $element) {
      if (!is_array($element)) {
        continue;
      }

      $widget_type = (string) ($element['widgetType'] ?? '');
      if ($widget_type !== '' && isset($registry[$widget_type])) {
        $settings = isset($element['settings']) && is_array($element['settings']) ? $element['settings'] : array();
        $fields = array();
        foreach ((array) $registry[$widget_type]['fields'] as $path) {
          foreach ($this->extract_widget_path_values($settings, $path) as $field) {
            $fields[] = array(
              'path' => (string) $field['path'],
              'value' => (string) $field['value'],
              'label' => $this->format_widget_path_label((string) $field['path']),
            );
          }
        }
        if ($fields) {
          $blocks[] = array(
            'element_id' => (string) ($element['id'] ?? ''),
            'widget_type' => $widget_type,
            'widget_label' => (string) ($registry[$widget_type]['label'] ?? $widget_type),
            'fields' => $fields,
          );
        }
      }

      if (!empty($element['elements']) && is_array($element['elements'])) {
        $this->collect_widget_content_blocks_recursive($element['elements'], $registry, $blocks);
      }
    }
  }

  private function get_post_widget_content_blocks($post_id)
  {
    $document = $this->get_elementor_document_data($post_id);
    if (!$document) {
      return array();
    }
    $blocks = array();
    $this->collect_widget_content_blocks_recursive($document, $this->get_widget_content_registry(), $blocks);
    return $blocks;
  }

  private function format_widget_path_label($path)
  {
    $parts = explode('.', (string) $path);
    $parts = array_map(static function ($part) {
      return is_numeric($part) ? '#' . ((int) $part + 1) : ucwords(str_replace('_', ' ', (string) $part));
    }, $parts);
    return implode(' / ', array_filter($parts));
  }

  private function flatten_widget_fields_map($blocks)
  {
    $map = array();
    foreach ((array) $blocks as $block) {
      foreach ((array) ($block['fields'] ?? array()) as $field) {
        $key = (string) ($block['element_id'] ?? '') . '::' . (string) ($field['path'] ?? '');
        $map[$key] = (string) ($field['value'] ?? '');
      }
    }
    return $map;
  }

  private function store_widget_suggestion_group($post_id, $payload, $field_map)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_seo_suggestions';
    $group = wp_generate_uuid4();
    $now = current_time('mysql');
    $confidence = max(0, min(100, absint($payload['confidence'] ?? 72)));

    foreach ((array) ($payload['suggestions'] ?? array()) as $suggestion) {
      if (!is_array($suggestion)) {
        continue;
      }
      $element_id = sanitize_text_field((string) ($suggestion['element_id'] ?? ''));
      $path = sanitize_text_field((string) ($suggestion['path'] ?? ''));
      $value = trim((string) ($suggestion['value'] ?? ''));
      $reason = trim((string) ($suggestion['reason'] ?? ''));
      if ($element_id === '' || $path === '' || $value === '') {
        continue;
      }
      $field_key = 'widget::' . $element_id . '::' . $path;
      $current_value = (string) ($field_map[$element_id . '::' . $path] ?? '');

      $wpdb->insert($table, array(
        'suggestion_group' => $group,
        'post_id' => $post_id,
        'field_key' => $field_key,
        'current_value' => $current_value,
        'suggested_value' => $value,
        'notes' => $reason,
        'status' => 'new',
        'confidence' => $confidence,
        'provider' => 'gemini',
        'created_at' => $now,
        'updated_at' => $now,
      ));
    }

    return $group;
  }

  private function generate_widget_content_suggestions_for_post($post_id)
  {
    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
      return new WP_Error('missing_post', __('Page could not be found.', 'jacana-luxe'));
    }

    $blocks = $this->get_post_widget_content_blocks($post_id);
    if (!$blocks) {
      return new WP_Error('missing_widgets', __('No supported Jacana widgets were found in this Elementor page.', 'jacana-luxe'));
    }

    $trimmed_blocks = array_map(static function ($block) {
      return array(
        'element_id' => $block['element_id'],
        'widget_type' => $block['widget_type'],
        'widget_label' => $block['widget_label'],
        'fields' => array_slice((array) $block['fields'], 0, 18),
      );
    }, array_slice($blocks, 0, 12));

    $prompt = "You are optimizing Elementor widget copy for a premium Namibia safari and travel website.\n"
      . "Return JSON only with this schema: "
      . '{"confidence":84,"suggestions":[{"element_id":"abc123","path":"heading","value":"...","reason":"..."},{"element_id":"abc123","path":"services.0.cta_label","value":"...","reason":"..."}]}'
      . "\nRules:\n"
      . "- Suggest only text improvements for the provided widget fields\n"
      . "- Prioritize headings, intros, CTA labels, short descriptions, and trust-building copy\n"
      . "- Keep CTA labels short and conversion-oriented\n"
      . "- Do not invent new fields or change layout/media\n"
      . "- Return at most 24 suggestions\n"
      . "- Do not use markdown fences\n"
      . "\nBrand context:\n" . $this->get_brand_context_text()
      . "\nPage context:\n" . wp_json_encode(array(
        'title' => get_the_title($post),
        'permalink' => get_permalink($post),
        'post_type' => $post->post_type,
        'widgets' => $trimmed_blocks,
      ));

    $result = $this->call_gemini_text($prompt, true);
    if (is_wp_error($result)) {
      return $result;
    }

    $payload = $this->extract_json_from_ai_response($result);
    if (empty($payload['suggestions']) || !is_array($payload['suggestions'])) {
      return new WP_Error('invalid_ai_payload', __('AI response could not be parsed.', 'jacana-luxe'));
    }

    return $this->store_widget_suggestion_group($post_id, $payload, $this->flatten_widget_fields_map($blocks));
  }

  private function update_nested_document_value(&$subject, $path_parts, $value)
  {
    if (empty($path_parts)) {
      $subject = $value;
      return true;
    }

    $segment = array_shift($path_parts);
    if (!is_array($subject) || !array_key_exists($segment, $subject)) {
      return false;
    }

    return $this->update_nested_document_value($subject[$segment], $path_parts, $value);
  }

  private function update_widget_field_in_document(&$elements, $element_id, $path, $value)
  {
    foreach ((array) $elements as &$element) {
      if (!is_array($element)) {
        continue;
      }

      if ((string) ($element['id'] ?? '') === $element_id) {
        if (!isset($element['settings']) || !is_array($element['settings'])) {
          return false;
        }
        return $this->update_nested_document_value($element['settings'], explode('.', $path), $value);
      }

      if (!empty($element['elements']) && is_array($element['elements'])) {
        $updated = $this->update_widget_field_in_document($element['elements'], $element_id, $path, $value);
        if ($updated) {
          return true;
        }
      }
    }

    return false;
  }

  private function format_suggestion_label($field_key)
  {
    $field_key = (string) $field_key;
    if (strpos($field_key, 'widget::') === 0) {
      $parts = explode('::', $field_key, 3);
      $path = $parts[2] ?? '';
      return __('Widget Field', 'jacana-luxe') . ': ' . $this->format_widget_path_label($path);
    }
    return ucwords(str_replace('_', ' ', $field_key));
  }

  private function get_seo_suggestions($args = array())
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_seo_suggestions';
    $where = array('1=1');
    $params = array();

    if (!empty($args['post_id'])) {
      $where[] = 'post_id = %d';
      $params[] = absint($args['post_id']);
    }

    if (!empty($args['status'])) {
      $statuses = (array) $args['status'];
      $placeholders = implode(',', array_fill(0, count($statuses), '%s'));
      $where[] = "status IN ({$placeholders})";
      foreach ($statuses as $status) {
        $params[] = sanitize_key($status);
      }
    }

    $limit = !empty($args['limit']) ? absint($args['limit']) : 30;
    $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC, id DESC LIMIT %d";
    $params[] = $limit;
    return $wpdb->get_results($wpdb->prepare($sql, $params));
  }

  private function extract_json_from_ai_response($text)
  {
    $text = trim((string) $text);
    if ($text === '') {
      return array();
    }

    $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
    $text = preg_replace('/\s*```$/', '', $text);
    $decoded = json_decode($text, true);
    if (is_array($decoded)) {
      return $decoded;
    }

    $start = strpos($text, '{');
    $end = strrpos($text, '}');
    if ($start !== false && $end !== false && $end > $start) {
      $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
      if (is_array($decoded)) {
        return $decoded;
      }
    }

    return array();
  }

  private function store_seo_suggestion_group($post_id, $payload, $current_state)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_seo_suggestions';
    $group = wp_generate_uuid4();
    $fields = array(
      'seo_title',
      'meta_description',
      'focus_keyphrase',
      'og_title',
      'og_description',
      'excerpt',
      'schema_notes',
      'heading_notes',
      'internal_link_ideas',
      'content_update_notes',
    );
    $confidence = max(0, min(100, absint($payload['confidence'] ?? $payload['priority_score'] ?? 72)));
    $now = current_time('mysql');

    foreach ($fields as $field) {
      $field_payload = $payload['fields'][$field] ?? null;
      if (is_array($field_payload)) {
        $value = trim((string) ($field_payload['value'] ?? ''));
        $notes = trim((string) ($field_payload['reason'] ?? ''));
      } else {
        $value = trim((string) $field_payload);
        $notes = '';
      }
      if ($value === '') {
        continue;
      }

      $wpdb->insert(
        $table,
        array(
          'suggestion_group' => $group,
          'post_id' => $post_id,
          'field_key' => $field,
          'current_value' => (string) ($current_state['current'][$field] ?? ''),
          'suggested_value' => $value,
          'notes' => $notes,
          'status' => 'new',
          'confidence' => $confidence,
          'provider' => 'gemini',
          'created_at' => $now,
          'updated_at' => $now,
        )
      );
    }

    return $group;
  }

  private function generate_seo_suggestions_for_post($post_id)
  {
    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
      return new WP_Error('missing_post', __('Page could not be found.', 'jacana-luxe'));
    }

    $state = $this->get_post_current_seo_state($post);
    $prompt = "You are an expert SEO strategist for a premium Namibia safari and travel company.\n"
      . "Return a JSON object only.\n"
      . "Use this schema: "
      . '{"confidence":78,"priority_score":82,"summary":"...","fields":{"seo_title":{"value":"","reason":""},"meta_description":{"value":"","reason":""},"focus_keyphrase":{"value":"","reason":""},"og_title":{"value":"","reason":""},"og_description":{"value":"","reason":""},"excerpt":{"value":"","reason":""},"schema_notes":{"value":"","reason":""},"heading_notes":{"value":"","reason":""},"internal_link_ideas":{"value":"","reason":""},"content_update_notes":{"value":"","reason":""}}}'
      . "\nRequirements:\n"
      . "- keep SEO title under 60 characters when practical\n"
      . "- keep meta description near 150-160 characters\n"
      . "- focus keyphrase must be concise\n"
      . "- excerpt should be conversion-oriented and concise\n"
      . "- internal_link_ideas should be a short semicolon-separated list\n"
      . "- content_update_notes should be high-value and specific\n"
      . "- do not use markdown fences\n"
      . "\nBrand context:\n" . $this->get_brand_context_text()
      . "\nPage context:\n" . wp_json_encode(array(
        'title' => $state['title'],
        'type' => $state['type'],
        'status' => $state['status'],
        'permalink' => $state['permalink'],
        'seo_score' => $state['seo_score'],
        'issues' => $state['issues'],
        'current' => $state['current'],
        'excerpt' => $state['excerpt'],
        'word_count' => $state['word_count'],
        'content' => wp_trim_words($state['content'], 350, ''),
      ));

    $result = $this->call_gemini_text($prompt, true);
    if (is_wp_error($result)) {
      return $result;
    }

    $payload = $this->extract_json_from_ai_response($result);
    if (empty($payload['fields']) || !is_array($payload['fields'])) {
      return new WP_Error('invalid_ai_payload', __('AI response could not be parsed.', 'jacana-luxe'));
    }

    return $this->store_seo_suggestion_group($post_id, $payload, $state);
  }

  private function update_seo_suggestion_status($suggestion_id, $status)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_seo_suggestions';
    $wpdb->update(
      $table,
      array(
        'status' => $status,
        'updated_at' => current_time('mysql'),
      ),
      array('id' => absint($suggestion_id))
    );
  }

  private function apply_seo_suggestion($suggestion_id)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_seo_suggestions';
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $suggestion_id));
    if (!$row) {
      return false;
    }

    $field = (string) $row->field_key;
    $value = (string) $row->suggested_value;
    $meta_map = $this->get_yoast_meta_map();

    if (strpos($field, 'widget::') === 0) {
      $parts = explode('::', $field, 3);
      $element_id = $parts[1] ?? '';
      $path = $parts[2] ?? '';
      if ($element_id === '' || $path === '') {
        return false;
      }
      $document = $this->get_elementor_document_data((int) $row->post_id);
      if (!$document) {
        return false;
      }
      $updated = $this->update_widget_field_in_document($document, $element_id, $path, $value);
      if (!$updated) {
        return false;
      }
      $this->save_elementor_document_data((int) $row->post_id, $document);
    } elseif ($field === 'excerpt') {
      wp_update_post(array(
        'ID' => (int) $row->post_id,
        'post_excerpt' => wp_strip_all_tags($value),
      ));
    } elseif (isset($meta_map[$field])) {
      update_post_meta((int) $row->post_id, $meta_map[$field], $value);
    } else {
      return false;
    }

    $wpdb->update(
      $table,
      array(
        'status' => 'applied',
        'applied_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
      ),
      array('id' => $suggestion_id)
    );

    return true;
  }

  private function apply_approved_suggestions_for_post($post_id)
  {
    foreach ($this->get_seo_suggestions(array(
      'post_id' => $post_id,
      'status' => array('approved'),
      'limit' => 50,
    )) as $row) {
      $this->apply_seo_suggestion((int) $row->id);
    }
  }

  private function get_competitor_sample_positions($current_rank = 8)
  {
    $mode = (string) get_option('jacana_seo_competitor_sampling_mode', 'top3_random2');
    $fixed = array_filter(array_map('absint', explode(',', (string) get_option('jacana_seo_competitor_fixed_positions', '1,2,3'))));
    $random_min = max(1, (int) get_option('jacana_seo_competitor_random_min', 6));
    $random_max = max($random_min, (int) get_option('jacana_seo_competitor_random_max', 20));
    $random_count = max(0, min(2, (int) get_option('jacana_seo_competitor_random_count', 2)));
    $max_sites = max(1, min(5, (int) get_option('jacana_seo_competitor_max_sites', 5)));
    $positions = array();

    if ($mode === 'immediately_above') {
      for ($i = 3; $i >= 1; $i--) {
        if ($current_rank - $i >= 1) {
          $positions[] = $current_rank - $i;
        }
      }
    } elseif ($mode === 'above_below') {
      $positions = array_filter(array($current_rank - 2, $current_rank - 1, $current_rank + 1, $current_rank + 2, 1));
    } elseif ($mode === 'custom_positions') {
      $positions = $fixed;
    } else {
      $positions = array(1, 2, 3);
      $pool = range($random_min, $random_max);
      shuffle($pool);
      $positions = array_merge($positions, array_slice($pool, 0, $random_count));
    }

    $positions = array_values(array_unique(array_filter(array_map('absint', $positions))));
    sort($positions);
    return array_slice($positions, 0, $max_sites);
  }

  public function run_daily_seo_audit()
  {
    if ((int) get_option('jacana_seo_daily_audit_enabled', 1) !== 1) {
      return;
    }

    $rows = $this->get_page_audit_rows();
    $summary = array(
      'ran_at' => current_time('mysql'),
      'total_items' => count($rows),
      'missing_descriptions' => 0,
      'missing_images' => 0,
      'low_score_items' => 0,
      'top_issues' => array(),
      'competitor_sample_preview' => $this->get_competitor_sample_positions(8),
      'target_keywords' => preg_split('/\r\n|\r|\n/', (string) get_option('jacana_seo_target_keywords', ''), -1, PREG_SPLIT_NO_EMPTY),
    );

    $issue_counts = array();
    foreach ($rows as $row) {
      if (empty($row['current']['meta_description'])) {
        $summary['missing_descriptions']++;
      }
      if (empty($row['has_image'])) {
        $summary['missing_images']++;
      }
      if ((int) ($row['seo_score'] ?? 0) < 60) {
        $summary['low_score_items']++;
      }
      foreach ((array) ($row['issues'] ?? array()) as $issue) {
        $issue_counts[$issue] = isset($issue_counts[$issue]) ? $issue_counts[$issue] + 1 : 1;
      }
    }
    arsort($issue_counts);
    $summary['top_issues'] = array_slice($issue_counts, 0, 6, true);

    update_option('jacana_seo_last_audit_summary', $summary, false);
  }

  private function get_search_console_credentials()
  {
    if ((int) get_option('jacana_seo_search_console_enabled', 0) !== 1) {
      return new WP_Error('gsc_disabled', __('Search Console sync is disabled.', 'jacana-luxe'));
    }

    $raw = (string) get_option('jacana_seo_gsc_service_account_json', '');
    $property = (string) get_option('jacana_seo_gsc_property_url', home_url('/'));
    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
      return new WP_Error('gsc_credentials_missing', __('Search Console service account JSON is missing or invalid.', 'jacana-luxe'));
    }

    return array(
      'client_email' => (string) $decoded['client_email'],
      'private_key' => str_replace("\\n", "\n", (string) $decoded['private_key']),
      'token_uri' => !empty($decoded['token_uri']) ? (string) $decoded['token_uri'] : 'https://oauth2.googleapis.com/token',
      'property' => $property,
    );
  }

  private function get_search_console_access_token()
  {
    $creds = $this->get_search_console_credentials();
    if (is_wp_error($creds)) {
      return $creds;
    }

    if (!function_exists('openssl_sign')) {
      return new WP_Error('openssl_missing', __('OpenSSL is required for Search Console authentication.', 'jacana-luxe'));
    }

    $header = rtrim(strtr(base64_encode(wp_json_encode(array('alg' => 'RS256', 'typ' => 'JWT'))), '+/', '-_'), '=');
    $issued = time();
    $claim = array(
      'iss' => $creds['client_email'],
      'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
      'aud' => $creds['token_uri'],
      'iat' => $issued,
      'exp' => $issued + 3600,
    );
    $payload = rtrim(strtr(base64_encode(wp_json_encode($claim)), '+/', '-_'), '=');
    $unsigned = $header . '.' . $payload;
    $signature = '';
    $ok = openssl_sign($unsigned, $signature, $creds['private_key'], 'sha256WithRSAEncryption');
    if (!$ok) {
      return new WP_Error('jwt_failed', __('Could not sign the Search Console JWT.', 'jacana-luxe'));
    }

    $jwt = $unsigned . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    $response = wp_remote_post($creds['token_uri'], array(
      'timeout' => 30,
      'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
      'body' => array(
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt,
      ),
    ));
    if (is_wp_error($response)) {
      return $response;
    }

    $data = json_decode((string) wp_remote_retrieve_body($response), true);
    $token = (string) ($data['access_token'] ?? '');
    if ($token === '') {
      return new WP_Error('token_missing', __('Search Console access token could not be retrieved.', 'jacana-luxe'));
    }

    return array(
      'access_token' => $token,
      'property' => $creds['property'],
    );
  }

  private function fetch_search_console_rows($start_date, $end_date, $dimensions = array('query', 'page'), $limit = 50)
  {
    $auth = $this->get_search_console_access_token();
    if (is_wp_error($auth)) {
      return $auth;
    }

    $url = 'https://searchconsole.googleapis.com/webmasters/v3/sites/' . rawurlencode((string) $auth['property']) . '/searchAnalytics/query';
    $response = wp_remote_post($url, array(
      'timeout' => 45,
      'headers' => array(
        'Authorization' => 'Bearer ' . $auth['access_token'],
        'Content-Type' => 'application/json',
      ),
      'body' => wp_json_encode(array(
        'startDate' => $start_date,
        'endDate' => $end_date,
        'dimensions' => array_values($dimensions),
        'rowLimit' => max(10, min(250, (int) $limit)),
        'dataState' => 'final',
      )),
    ));
    if (is_wp_error($response)) {
      return $response;
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    $data = json_decode((string) wp_remote_retrieve_body($response), true);
    if ($status >= 400) {
      return new WP_Error('gsc_request_failed', (string) ($data['error']['message'] ?? __('Search Console request failed.', 'jacana-luxe')));
    }

    return is_array($data['rows'] ?? null) ? $data['rows'] : array();
  }

  private function find_post_id_by_url($url)
  {
    $post_id = url_to_postid((string) $url);
    if ($post_id > 0) {
      return $post_id;
    }

    $path = wp_parse_url((string) $url, PHP_URL_PATH);
    if (!$path) {
      return 0;
    }

    $page = get_page_by_path(trim($path, '/'), OBJECT, get_post_types(array('public' => true), 'names'));
    return $page instanceof WP_Post ? (int) $page->ID : 0;
  }

  private function store_search_console_query_snapshots($rows, $snapshot_date)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_seo_queries';
    $wpdb->delete($table, array('snapshot_date' => $snapshot_date, 'source' => 'search_console'));
    $now = current_time('mysql');

    foreach ((array) $rows as $row) {
      $keys = (array) ($row['keys'] ?? array());
      $query = (string) ($keys[0] ?? '');
      $page = (string) ($keys[1] ?? '');
      if ($query === '' || $page === '') {
        continue;
      }

      $wpdb->insert($table, array(
        'query_text' => $query,
        'page_url' => $page,
        'post_id' => $this->find_post_id_by_url($page),
        'clicks' => (float) ($row['clicks'] ?? 0),
        'impressions' => (float) ($row['impressions'] ?? 0),
        'ctr' => (float) ($row['ctr'] ?? 0),
        'position_avg' => (float) ($row['position'] ?? 0),
        'snapshot_date' => $snapshot_date,
        'source' => 'search_console',
        'created_at' => $now,
      ));
    }
  }

  private function store_keyword_rankings($snapshot_date)
  {
    global $wpdb;
    $source_table = $wpdb->prefix . 'jacana_seo_queries';
    $rankings_table = $wpdb->prefix . 'jacana_seo_rankings';
    $keywords = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) get_option('jacana_seo_target_keywords', ''))));

    $wpdb->delete($rankings_table, array('snapshot_date' => $snapshot_date, 'source' => 'search_console'));
    $now = current_time('mysql');

    foreach ($keywords as $keyword) {
      $like = '%' . $wpdb->esc_like($keyword) . '%';
      $row = $wpdb->get_row($wpdb->prepare(
        "SELECT query_text, page_url, post_id, SUM(clicks) AS clicks, SUM(impressions) AS impressions,
                AVG(ctr) AS ctr, AVG(position_avg) AS position_avg
         FROM {$source_table}
         WHERE snapshot_date = %s AND source = 'search_console' AND query_text LIKE %s
         GROUP BY page_url, post_id
         ORDER BY impressions DESC, clicks DESC
         LIMIT 1",
        $snapshot_date,
        $like
      ));

      if (!$row) {
        continue;
      }

      $wpdb->insert($rankings_table, array(
        'keyword_text' => $keyword,
        'page_url' => (string) $row->page_url,
        'post_id' => !empty($row->post_id) ? (int) $row->post_id : null,
        'clicks' => (float) $row->clicks,
        'impressions' => (float) $row->impressions,
        'ctr' => (float) $row->ctr,
        'position_avg' => (float) $row->position_avg,
        'snapshot_date' => $snapshot_date,
        'source' => 'search_console',
        'created_at' => $now,
      ));
    }
  }

  public function run_daily_seo_sync()
  {
    if ((string) get_option('jacana_seo_data_source', 'search_console') !== 'search_console') {
      return;
    }

    $window_days = max(1, (int) get_option('jacana_seo_sync_window_days', 7));
    $row_limit = max(10, (int) get_option('jacana_seo_query_row_limit', 50));
    $end = gmdate('Y-m-d', strtotime('-1 day'));
    $start = gmdate('Y-m-d', strtotime('-' . max(1, $window_days - 1) . ' days'));

    $rows = $this->fetch_search_console_rows($start, $end, array('query', 'page'), $row_limit);
    if (is_wp_error($rows)) {
      update_option('jacana_seo_last_sync_error', $rows->get_error_message(), false);
      return;
    }

    $this->store_search_console_query_snapshots($rows, $end);
    $this->store_keyword_rankings($end);

    update_option('jacana_seo_last_sync_summary', array(
      'synced_at' => current_time('mysql'),
      'snapshot_date' => $end,
      'rows' => count($rows),
      'source' => 'search_console',
    ), false);
    delete_option('jacana_seo_last_sync_error');
  }

  private function get_recent_query_rows($limit = 12)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_seo_queries';
    return $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM {$table} ORDER BY snapshot_date DESC, impressions DESC LIMIT %d",
      max(1, absint($limit))
    ));
  }

  private function get_latest_rankings($limit = 20)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_seo_rankings';
    return $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM {$table} ORDER BY snapshot_date DESC, position_avg ASC LIMIT %d",
      max(1, absint($limit))
    ));
  }

  private function get_target_keywords($limit = 0)
  {
    $keywords = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) get_option('jacana_seo_target_keywords', '')))));
    if ($limit > 0) {
      $keywords = array_slice($keywords, 0, $limit);
    }
    return $keywords;
  }

  private function get_keyword_target_context($keyword)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_seo_rankings';
    $row = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$table} WHERE keyword_text = %s ORDER BY snapshot_date DESC, impressions DESC LIMIT 1",
      $keyword
    ));

    if ($row) {
      $post_id = !empty($row->post_id) ? (int) $row->post_id : $this->find_post_id_by_url((string) $row->page_url);
      return array(
        'post_id' => $post_id,
        'title' => $post_id > 0 ? get_the_title($post_id) : (wp_parse_url((string) $row->page_url, PHP_URL_PATH) ?: (string) $row->page_url),
        'url' => (string) $row->page_url,
        'position_avg' => (float) $row->position_avg,
      );
    }

    $posts = get_posts(array(
      'post_type' => get_post_types(array('public' => true), 'names'),
      'post_status' => 'publish',
      's' => $keyword,
      'numberposts' => 1,
    ));

    if (!empty($posts[0]) && $posts[0] instanceof WP_Post) {
      return array(
        'post_id' => (int) $posts[0]->ID,
        'title' => get_the_title($posts[0]),
        'url' => get_permalink($posts[0]),
        'position_avg' => 8.0,
      );
    }

    return array(
      'post_id' => 0,
      'title' => get_bloginfo('name'),
      'url' => home_url('/'),
      'position_avg' => 8.0,
    );
  }

  private function normalize_search_result_url($url)
  {
    $url = html_entity_decode((string) $url, ENT_QUOTES);
    if ($url === '') {
      return '';
    }

    $parts = wp_parse_url($url);
    if (!empty($parts['host']) && strpos((string) $parts['host'], 'duckduckgo.com') !== false && !empty($parts['query'])) {
      parse_str((string) $parts['query'], $query);
      if (!empty($query['uddg'])) {
        $url = rawurldecode((string) $query['uddg']);
      }
    }

    if (strpos($url, '//') === 0) {
      $url = 'https:' . $url;
    }

    return esc_url_raw($url);
  }

  private function fetch_serp_results_for_keyword($keyword, $limit = 20)
  {
    $site_host = preg_replace('/^www\./i', '', (string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    $gemini_prompt = "Search the web for the keyword \"{$keyword}\".\n"
      . "Return JSON only with key results.\n"
      . "Each result must contain: position, url, title, domain.\n"
      . "Use the result order as position, exclude ads, exclude duplicate domains where possible, and exclude the domain {$site_host}. Limit to {$limit} results.";
    $gemini_results = $this->call_gemini_with_google_search($gemini_prompt);
    if (!is_wp_error($gemini_results)) {
      $decoded = $this->extract_json_from_ai_response((string) ($gemini_results['text'] ?? ''));
      if (!empty($decoded['results']) && is_array($decoded['results'])) {
        $results = array();
        foreach ($decoded['results'] as $item) {
          $url = $this->normalize_search_result_url((string) ($item['url'] ?? ''));
          $domain = preg_replace('/^www\./i', '', (string) ($item['domain'] ?? wp_parse_url($url, PHP_URL_HOST)));
          if ($url === '' || $domain === '' || $domain === $site_host) {
            continue;
          }
          $results[] = array(
            'url' => $url,
            'title' => sanitize_text_field((string) ($item['title'] ?? '')),
            'domain' => $domain,
            'position' => max(1, (int) ($item['position'] ?? (count($results) + 1))),
          );
          if (count($results) >= max(1, absint($limit))) {
            break;
          }
        }
        if (!empty($results)) {
          return $results;
        }
      }

      $results = array();
      foreach ((array) ($gemini_results['grounding'] ?? array()) as $chunk) {
        $web = (array) ($chunk['web'] ?? array());
        $url = $this->normalize_search_result_url((string) ($web['uri'] ?? ''));
        $domain = preg_replace('/^www\./i', '', (string) wp_parse_url($url, PHP_URL_HOST));
        if ($url === '' || $domain === '' || $domain === $site_host) {
          continue;
        }
        if (isset($results[$url])) {
          continue;
        }
        $results[$url] = array(
          'url' => $url,
          'title' => sanitize_text_field((string) ($web['title'] ?? '')),
          'domain' => $domain,
          'position' => count($results) + 1,
        );
        if (count($results) >= max(1, absint($limit))) {
          break;
        }
      }
      if (!empty($results)) {
        return array_values($results);
      }
    }

    $response = wp_remote_get(
      'https://html.duckduckgo.com/html/?q=' . rawurlencode((string) $keyword),
      array(
        'timeout' => 20,
        'headers' => array(
          'User-Agent' => 'Mozilla/5.0 (compatible; JacanaCRM/1.0; +' . home_url('/') . ')',
        ),
      )
    );

    if (is_wp_error($response)) {
      return $response;
    }

    $body = (string) wp_remote_retrieve_body($response);
    if ($body === '') {
      return new WP_Error('serp_empty', __('Search result body was empty.', 'jacana-luxe'));
    }

    preg_match_all('/<a[^>]+class="[^"]*result__a[^"]*"[^>]+href="([^"]+)"[^>]*>(.*?)<\/a>/is', $body, $matches, PREG_SET_ORDER);
    $results = array();
    $site_host = wp_parse_url(home_url('/'), PHP_URL_HOST);

    foreach ($matches as $match) {
      $url = $this->normalize_search_result_url((string) ($match[1] ?? ''));
      $title = trim(wp_strip_all_tags((string) ($match[2] ?? '')));
      $host = wp_parse_url($url, PHP_URL_HOST);
      if ($url === '' || !$host || strpos((string) $host, (string) $site_host) !== false) {
        continue;
      }

      if (!preg_match('#^https?://#i', $url)) {
        continue;
      }

      if (isset($results[$url])) {
        continue;
      }

      $results[$url] = array(
        'url' => $url,
        'title' => $title,
        'domain' => preg_replace('/^www\./i', '', (string) $host),
        'position' => count($results) + 1,
      );

      if (count($results) >= max(1, absint($limit))) {
        break;
      }
    }

    return array_values($results);
  }

  private function fetch_remote_page_summary($url)
  {
    $response = wp_remote_get(
      $url,
      array(
        'timeout' => 20,
        'redirection' => 5,
        'headers' => array(
          'User-Agent' => 'Mozilla/5.0 (compatible; JacanaCRM/1.0; +' . home_url('/') . ')',
        ),
      )
    );

    if (is_wp_error($response)) {
      return array();
    }

    $body = (string) wp_remote_retrieve_body($response);
    if ($body === '') {
      return array();
    }

    $body = substr($body, 0, 250000);
    $title = '';
    $meta_description = '';
    $headings = array();

    if (class_exists('DOMDocument')) {
      $internal_errors = libxml_use_internal_errors(true);
      $doc = new DOMDocument();
      $doc->loadHTML($body);

      $titles = $doc->getElementsByTagName('title');
      if ($titles->length > 0) {
        $title = trim((string) $titles->item(0)->textContent);
      }

      $metas = $doc->getElementsByTagName('meta');
      foreach ($metas as $meta) {
        $name = strtolower((string) $meta->getAttribute('name'));
        $property = strtolower((string) $meta->getAttribute('property'));
        if (($name === 'description' || $property === 'og:description') && $meta_description === '') {
          $meta_description = trim((string) $meta->getAttribute('content'));
        }
      }

      foreach (array('h1', 'h2', 'h3') as $tag) {
        foreach ($doc->getElementsByTagName($tag) as $node) {
          $text = trim((string) $node->textContent);
          if ($text !== '') {
            $headings[] = $text;
          }
          if (count($headings) >= 8) {
            break 2;
          }
        }
      }
      libxml_clear_errors();
      libxml_use_internal_errors($internal_errors);
    }

    if ($title === '' && preg_match('/<title[^>]*>(.*?)<\/title>/is', $body, $match)) {
      $title = trim(wp_strip_all_tags((string) $match[1]));
    }
    if ($meta_description === '' && preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/i', $body, $match)) {
      $meta_description = trim((string) $match[1]);
    }

    $clean_html = preg_replace('/<(script|style|noscript|svg)[^>]*>.*?<\/\\1>/is', ' ', $body);
    $text = preg_replace('/\s+/', ' ', wp_strip_all_tags($clean_html));
    $text = trim(html_entity_decode((string) $text, ENT_QUOTES));
    $word_count = str_word_count($text);

    return array(
      'title' => $title,
      'meta_description' => $meta_description,
      'headings' => array_values(array_slice(array_unique(array_filter($headings)), 0, 8)),
      'excerpt' => wp_trim_words($text, 70, '...'),
      'text_sample' => substr($text, 0, 1800),
      'word_count' => $word_count,
    );
  }

  private function build_competitor_fallback_analysis($keyword, $target, $competitors)
  {
    $items = array();
    $actions = array(
      __('Tighten the page title and meta description around the keyword intent.', 'jacana-luxe'),
      __('Expand high-value sections with clearer headings and trust signals.', 'jacana-luxe'),
    );

    foreach ($competitors as $competitor) {
      $gaps = array();
      if ((int) ($competitor['word_count'] ?? 0) > 1200) {
        $gaps[] = __('Competitor content is materially deeper than ours.', 'jacana-luxe');
      }
      if (!empty($competitor['meta_description'])) {
        $gaps[] = __('Competitor is using a clear snippet angle in search.', 'jacana-luxe');
      }
      if (count((array) ($competitor['headings'] ?? array())) >= 5) {
        $gaps[] = __('Competitor uses a stronger heading structure.', 'jacana-luxe');
      }
      $items[] = array(
        'position' => (int) ($competitor['position'] ?? 0),
        'summary' => sprintf(__('Ranks at #%1$d with a %2$s page angle focused on %3$s.', 'jacana-luxe'), (int) ($competitor['position'] ?? 0), !empty($competitor['meta_description']) ? __('clear', 'jacana-luxe') : __('generic', 'jacana-luxe'), $keyword),
        'strengths' => array_values(array_filter(array(
          !empty($competitor['title']) ? __('Strong keyword-relevant page title', 'jacana-luxe') : '',
          !empty($competitor['meta_description']) ? __('Present meta description', 'jacana-luxe') : '',
          (int) ($competitor['word_count'] ?? 0) > 900 ? __('Long-form supporting content', 'jacana-luxe') : '',
        ))),
        'gaps' => $gaps,
        'recommended_response' => __('Strengthen the target page around the exact query, add sharper CTA copy, and expand helpful subtopics.', 'jacana-luxe'),
      );
    }

    return array(
      'overview' => sprintf(__('Top results for %s are leaning on stronger topical coverage and tighter SERP copy.', 'jacana-luxe'), $keyword),
      'overall_actions' => $actions,
      'competitors' => $items,
      'target_page' => $target,
    );
  }

  private function analyze_competitors_for_keyword($keyword, $target, $competitors)
  {
    $prompt = "You are an SEO strategist for a premium Namibia safari company.\n"
      . "Analyze competitor pages for the keyword '{$keyword}'.\n"
      . "Target page:\n" . wp_json_encode($target) . "\n"
      . "Competitors:\n" . wp_json_encode($competitors) . "\n"
      . "Return JSON with keys overview, overall_actions (array), competitors (array of objects with position, summary, strengths, gaps, recommended_response).";

    $result = $this->call_gemini_text($prompt, true);
    if (is_wp_error($result)) {
      return $this->build_competitor_fallback_analysis($keyword, $target, $competitors);
    }

    $decoded = $this->extract_json_from_ai_response((string) $result);
    if (empty($decoded['competitors']) || !is_array($decoded['competitors'])) {
      return $this->build_competitor_fallback_analysis($keyword, $target, $competitors);
    }

    return $decoded;
  }

  private function get_recent_user_questions($limit = 6)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_chat_messages';
    $rows = $wpdb->get_results($wpdb->prepare(
      "SELECT content FROM {$table} WHERE role = 'user' AND content <> '' ORDER BY created_at DESC LIMIT %d",
      max(1, absint($limit))
    ));

    return array_values(array_filter(array_map(static function ($row) {
      $content = trim(wp_strip_all_tags((string) ($row->content ?? '')));
      return $content !== '' ? wp_trim_words($content, 18, '') : '';
    }, (array) $rows)));
  }

  private function get_trend_headlines($keywords, $limit = 6)
  {
    $keywords = array_values(array_filter(array_slice((array) $keywords, 0, 3)));
    if (empty($keywords)) {
      return array();
    }

    $query = implode(' OR ', array_map(static function ($keyword) {
      return '"' . $keyword . '"';
    }, $keywords));

    $response = wp_remote_get(
      'https://news.google.com/rss/search?q=' . rawurlencode($query . ' Namibia travel'),
      array('timeout' => 20)
    );
    if (is_wp_error($response)) {
      return array();
    }

    $body = (string) wp_remote_retrieve_body($response);
    if ($body === '' || !function_exists('simplexml_load_string')) {
      return array();
    }

    $xml = @simplexml_load_string($body);
    if (!$xml || empty($xml->channel->item)) {
      return array();
    }

    $headlines = array();
    foreach ($xml->channel->item as $item) {
      $title = trim((string) $item->title);
      if ($title !== '') {
        $headlines[] = $title;
      }
      if (count($headlines) >= $limit) {
        break;
      }
    }

    return $headlines;
  }

  public function run_daily_competitor_scan($force = false)
  {
    if (!$force && (int) get_option('jacana_seo_phase3_automation_enabled', 1) !== 1) {
      return array('skipped' => true);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'jacana_seo_competitors';
    $keywords = $this->get_target_keywords((int) get_option('jacana_seo_competitor_keyword_limit', 5));
    if (empty($keywords)) {
      update_option('jacana_seo_last_competitor_scan_error', __('No tracked keywords are configured.', 'jacana-luxe'), false);
      return new WP_Error('no_keywords', __('No tracked keywords available for competitor scanning.', 'jacana-luxe'));
    }

    $snapshot_date = current_time('Y-m-d');
    $now = current_time('mysql');
    $stored = 0;
    $errors = array();

    foreach ($keywords as $keyword) {
      $target = $this->get_keyword_target_context($keyword);
      $positions = $this->get_competitor_sample_positions((int) round((float) ($target['position_avg'] ?? 8)));
      $serp_results = $this->fetch_serp_results_for_keyword($keyword, 20);
      if (is_wp_error($serp_results) || empty($serp_results)) {
        $errors[] = is_wp_error($serp_results) ? $keyword . ': ' . $serp_results->get_error_message() : $keyword . ': empty result set';
        continue;
      }

      $selected = array();
      foreach ($positions as $position) {
        $index = (int) $position - 1;
        if (!isset($serp_results[$index])) {
          continue;
        }
        $selected[] = array_merge($serp_results[$index], array('position' => (int) $position));
      }
      if (empty($selected)) {
        continue;
      }

      foreach ($selected as &$competitor) {
        $summary = $this->fetch_remote_page_summary((string) $competitor['url']);
        $competitor['meta_description'] = (string) ($summary['meta_description'] ?? '');
        $competitor['headings'] = (array) ($summary['headings'] ?? array());
        $competitor['excerpt'] = (string) ($summary['excerpt'] ?? '');
        $competitor['text_sample'] = (string) ($summary['text_sample'] ?? '');
        $competitor['word_count'] = (int) ($summary['word_count'] ?? 0);
        if ((string) ($competitor['title'] ?? '') === '') {
          $competitor['title'] = (string) ($summary['title'] ?? '');
        }
      }
      unset($competitor);

      $analysis = $this->analyze_competitors_for_keyword($keyword, $target, $selected);
      $analysis_index = array();
      foreach ((array) ($analysis['competitors'] ?? array()) as $item) {
        $analysis_index[(int) ($item['position'] ?? 0)] = $item;
      }

      $wpdb->delete($table, array('keyword_text' => $keyword, 'snapshot_date' => $snapshot_date));
      foreach ($selected as $competitor) {
        $item_analysis = $analysis_index[(int) $competitor['position']] ?? array();
        $wpdb->insert($table, array(
          'keyword_text' => $keyword,
          'target_post_id' => !empty($target['post_id']) ? (int) $target['post_id'] : null,
          'target_page_url' => (string) ($target['url'] ?? home_url('/')),
          'target_position_avg' => (float) ($target['position_avg'] ?? 0),
          'competitor_position' => (int) $competitor['position'],
          'competitor_domain' => (string) ($competitor['domain'] ?? ''),
          'competitor_url' => (string) $competitor['url'],
          'competitor_title' => (string) ($competitor['title'] ?? ''),
          'competitor_meta_description' => (string) ($competitor['meta_description'] ?? ''),
          'content_word_count' => (int) ($competitor['word_count'] ?? 0),
          'summary' => (string) ($item_analysis['summary'] ?? ''),
          'analysis_json' => wp_json_encode(array(
            'overview' => (string) ($analysis['overview'] ?? ''),
            'overall_actions' => array_values((array) ($analysis['overall_actions'] ?? array())),
            'competitor' => $item_analysis,
          )),
          'source' => 'duckduckgo',
          'snapshot_date' => $snapshot_date,
          'created_at' => $now,
        ));
        $stored++;
      }
    }

    $summary = array(
      'ran_at' => $now,
      'snapshot_date' => $snapshot_date,
      'keywords' => count($keywords),
      'rows' => $stored,
    );
    update_option('jacana_seo_last_competitor_scan_summary', $summary, false);
    if ($stored > 0) {
      delete_option('jacana_seo_last_competitor_scan_error');
      return $summary;
    }

    $error_text = !empty($errors) ? implode(' | ', array_slice($errors, 0, 3)) : __('No competitor results were stored.', 'jacana-luxe');
    update_option('jacana_seo_last_competitor_scan_error', $error_text, false);

    return new WP_Error('competitor_scan_failed', $error_text);
  }

  private function get_recent_competitor_snapshots($limit = 12)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_seo_competitors';
    return $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM {$table} ORDER BY snapshot_date DESC, competitor_position ASC LIMIT %d",
      max(1, absint($limit))
    ));
  }

  private function build_fallback_opportunities()
  {
    $opportunities = array();
    $queries = $this->get_recent_query_rows(20);
    $pages = $this->get_page_audit_rows();

    foreach ($queries as $query) {
      if (count($opportunities) >= 6) {
        break;
      }
      if ((float) $query->impressions < 10 || (float) $query->position_avg > 20) {
        continue;
      }
      $opportunities[] = array(
        'type' => 'page_update',
        'title' => sprintf(__('Improve page for "%s"', 'jacana-luxe'), (string) $query->query_text),
        'target_keyword' => (string) $query->query_text,
        'target_post_id' => !empty($query->post_id) ? (int) $query->post_id : 0,
        'priority_score' => max(55, min(95, (int) round((float) $query->impressions / 2))),
        'summary' => __('This page already surfaces for the query but needs tighter copy, stronger headings, and better CTR packaging.', 'jacana-luxe'),
        'outline' => "- Refresh title and meta\n- Expand the query-specific section\n- Add FAQ or trust block\n- Strengthen CTA",
        'draft_content' => '',
      );
    }

    foreach ($pages as $page) {
      if (count($opportunities) >= 8) {
        break;
      }
      if ((int) $page['seo_score'] >= 70) {
        continue;
      }
      $opportunities[] = array(
        'type' => 'post',
        'title' => sprintf(__('Supporting article for %s', 'jacana-luxe'), (string) $page['title']),
        'target_keyword' => (string) $page['title'],
        'target_post_id' => (int) $page['id'],
        'priority_score' => 60,
        'summary' => __('Create a supporting article that targets adjacent long-tail search intent and internally links to the service page.', 'jacana-luxe'),
        'outline' => "- Context and intent\n- Planning advice\n- FAQs\n- CTA back to main page",
        'draft_content' => '',
      );
    }

    return array('opportunities' => $opportunities);
  }

  private function upsert_content_opportunity($opportunity)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_seo_opportunities';
    $title = sanitize_text_field((string) ($opportunity['title'] ?? ''));
    $type = sanitize_key((string) ($opportunity['type'] ?? 'page_update'));
    $keyword = sanitize_text_field((string) ($opportunity['target_keyword'] ?? ''));
    if ($title === '') {
      return;
    }

    $existing_id = (int) $wpdb->get_var($wpdb->prepare(
      "SELECT id FROM {$table}
       WHERE title = %s AND opportunity_type = %s AND target_keyword = %s
       AND created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
       ORDER BY created_at DESC LIMIT 1",
      $title,
      $type,
      $keyword
    ));

    $payload = array(
      'opportunity_type' => in_array($type, array('post', 'page_update', 'internal_link'), true) ? $type : 'page_update',
      'target_post_id' => !empty($opportunity['target_post_id']) ? (int) $opportunity['target_post_id'] : null,
      'title' => $title,
      'target_keyword' => $keyword,
      'priority_score' => max(1, min(100, (int) ($opportunity['priority_score'] ?? 50))),
      'summary' => wp_kses_post((string) ($opportunity['summary'] ?? '')),
      'outline' => wp_kses_post((string) ($opportunity['outline'] ?? '')),
      'draft_content' => wp_kses_post((string) ($opportunity['draft_content'] ?? '')),
      'source_data' => wp_json_encode($opportunity),
      'updated_at' => current_time('mysql'),
    );

    if ($existing_id > 0) {
      $wpdb->update($table, $payload, array('id' => $existing_id));
      return;
    }

    $payload['status'] = 'new';
    $payload['created_at'] = current_time('mysql');
    $wpdb->insert($table, $payload);
  }

  public function run_daily_content_opportunities($force = false)
  {
    if (!$force && (int) get_option('jacana_seo_phase3_automation_enabled', 1) !== 1) {
      return array('skipped' => true);
    }

    $page_candidates = array_slice(array_map(static function ($page) {
      return array(
        'id' => (int) $page['id'],
        'title' => (string) $page['title'],
        'score' => (int) $page['seo_score'],
        'issues' => array_values((array) $page['issues']),
      );
    }, $this->get_page_audit_rows()), 0, 8);

    $queries = array_map(static function ($row) {
      return array(
        'query' => (string) $row->query_text,
        'page_url' => (string) $row->page_url,
        'post_id' => (int) $row->post_id,
        'clicks' => (float) $row->clicks,
        'impressions' => (float) $row->impressions,
        'ctr' => (float) $row->ctr,
        'position_avg' => (float) $row->position_avg,
      );
    }, $this->get_recent_query_rows(20));

    $competitors = array_map(static function ($row) {
      $analysis = json_decode((string) ($row->analysis_json ?? ''), true);
      return array(
        'keyword' => (string) $row->keyword_text,
        'competitor_domain' => (string) $row->competitor_domain,
        'competitor_position' => (int) $row->competitor_position,
        'summary' => (string) $row->summary,
        'actions' => array_values((array) ($analysis['overall_actions'] ?? array())),
      );
    }, $this->get_recent_competitor_snapshots(15));

    $context = array(
      'brand' => $this->get_brand_context_text(),
      'top_services' => array_map(static function ($row) {
        return (string) ($row->label ?? '');
      }, $this->get_top_services(5)),
      'page_candidates' => $page_candidates,
      'queries' => $queries,
      'competitors' => $competitors,
      'user_questions' => $this->get_recent_user_questions(6),
      'trend_headlines' => $this->get_trend_headlines($this->get_target_keywords(3), 6),
    );

    $prompt = "You are an SEO and content strategist for Jacana Safaris & Tours.\n"
      . "Use the context to suggest up to 8 high-value SEO opportunities.\n"
      . "Return JSON with key opportunities. Each opportunity needs type (post or page_update), title, target_keyword, target_post_id, priority_score, summary, outline, draft_content.\n"
      . "Context:\n" . wp_json_encode($context);

    $result = $this->call_gemini_text($prompt, true);
    $decoded = is_wp_error($result) ? array() : $this->extract_json_from_ai_response((string) $result);
    if (empty($decoded['opportunities']) || !is_array($decoded['opportunities'])) {
      $decoded = $this->build_fallback_opportunities();
    }

    $stored = 0;
    foreach ((array) $decoded['opportunities'] as $opportunity) {
      $this->upsert_content_opportunity((array) $opportunity);
      $stored++;
    }

    $summary = array(
      'ran_at' => current_time('mysql'),
      'rows' => $stored,
      'questions_used' => count($context['user_questions']),
      'trend_headlines' => count($context['trend_headlines']),
    );
    update_option('jacana_seo_last_opportunity_summary', $summary, false);

    return $summary;
  }

  private function get_recent_opportunities($limit = 10)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_seo_opportunities';
    return $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM {$table} ORDER BY priority_score DESC, updated_at DESC LIMIT %d",
      max(1, absint($limit))
    ));
  }

  public function run_weekly_strategic_report($force = false)
  {
    if (!$force && (int) get_option('jacana_seo_weekly_report_enabled', 1) !== 1) {
      return array('skipped' => true);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'jacana_seo_reports';
    $dataset = array(
      'audit' => (array) get_option('jacana_seo_last_audit_summary', array()),
      'sync' => (array) get_option('jacana_seo_last_sync_summary', array()),
      'competitors' => $this->get_recent_competitor_snapshots(10),
      'opportunities' => $this->get_recent_opportunities(8),
      'queries' => $this->get_recent_query_rows(10),
      'rankings' => $this->get_latest_rankings(10),
      'trends' => $this->get_trend_headlines($this->get_target_keywords(3), 6),
    );

    $prompt = "You are the SEO lead for a premium Namibia safari brand.\n"
      . "Write a concise strategic report with sections: Executive Summary, Competitive Pressure, High-Value Opportunities, Recommended Actions This Week, Content Ideas.\n"
      . "Use the JSON context:\n" . wp_json_encode($dataset);
    $result = $this->call_gemini_text($prompt, false);
    $report_body = is_wp_error($result) ? '' : trim((string) $result);

    if ($report_body === '') {
      $report_body = "Executive Summary\n";
      $report_body .= "- Latest audit items: " . (int) ($dataset['audit']['total_items'] ?? 0) . "\n";
      $report_body .= "- Recent competitor snapshots: " . count((array) $dataset['competitors']) . "\n";
      $report_body .= "- New opportunities: " . count((array) $dataset['opportunities']) . "\n\n";
      $report_body .= "Recommended Actions This Week\n";
      foreach (array_slice((array) $dataset['opportunities'], 0, 4) as $item) {
        $report_body .= "- " . (string) ($item->title ?? '') . "\n";
      }
    }

    $summary = wp_trim_words(wp_strip_all_tags($report_body), 45, '...');
    $wpdb->insert($table, array(
      'report_type' => 'strategic',
      'report_period' => 'weekly',
      'title' => sprintf(__('Strategic SEO Report - %s', 'jacana-luxe'), current_time('M j, Y')),
      'summary' => $summary,
      'report_body' => $report_body,
      'data_json' => wp_json_encode($dataset),
      'created_at' => current_time('mysql'),
    ));

    update_option('jacana_seo_last_report_summary', array(
      'generated_at' => current_time('mysql'),
      'summary' => $summary,
    ), false);

    $recipients = array_filter(array_map('sanitize_email', preg_split('/[\r\n,;]+/', (string) get_option('jacana_seo_report_email_recipients', ''))));
    if (!empty($recipients)) {
      wp_mail($recipients, __('Jacana Strategic SEO Report', 'jacana-luxe'), $report_body);
    }

    return array(
      'generated_at' => current_time('mysql'),
      'summary' => $summary,
    );
  }

  private function get_latest_report($type = 'strategic')
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_seo_reports';
    return $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$table} WHERE report_type = %s ORDER BY created_at DESC LIMIT 1",
      $type
    ));
  }

  private function get_report_dataset()
  {
    $pages = $this->get_page_audit_rows();
    $rankings = $this->get_latest_rankings(50);
    $queries = $this->get_recent_query_rows(50);
    $audit = (array) get_option('jacana_seo_last_audit_summary', array());
    $sync = (array) get_option('jacana_seo_last_sync_summary', array());
    $competitors = $this->get_recent_competitor_snapshots(30);
    $opportunities = $this->get_recent_opportunities(20);

    return array(
      'generated_at' => current_time('mysql'),
      'audit' => $audit,
      'sync' => $sync,
      'pages' => $pages,
      'rankings' => $rankings,
      'queries' => $queries,
      'competitors' => $competitors,
      'opportunities' => $opportunities,
    );
  }

  private function output_report_rows_csv($handle, $dataset)
  {
    fputcsv($handle, array('Generated At', $dataset['generated_at']));
    fputcsv($handle, array());
    fputcsv($handle, array('Audit Summary'));
    foreach ((array) ($dataset['audit'] ?? array()) as $key => $value) {
      if (is_array($value)) {
        foreach ($value as $sub_key => $sub_value) {
          fputcsv($handle, array($key . ' - ' . $sub_key, $sub_value));
        }
      } else {
        fputcsv($handle, array($key, $value));
      }
    }
    fputcsv($handle, array());
    fputcsv($handle, array('Page Inventory'));
    fputcsv($handle, array('ID', 'Title', 'Type', 'Status', 'URL', 'SEO Score', 'Words', 'Has Image', 'Issues'));
    foreach ((array) $dataset['pages'] as $row) {
      fputcsv($handle, array(
        $row['id'] ?? '',
        $row['title'] ?? '',
        $row['type'] ?? '',
        $row['status'] ?? '',
        $row['permalink'] ?? '',
        $row['seo_score'] ?? '',
        $row['word_count'] ?? '',
        !empty($row['has_image']) ? 'yes' : 'no',
        implode('; ', (array) ($row['issues'] ?? array())),
      ));
    }
    fputcsv($handle, array());
    fputcsv($handle, array('Keyword Rankings'));
    fputcsv($handle, array('Keyword', 'Page URL', 'Clicks', 'Impressions', 'CTR', 'Avg Position', 'Snapshot Date'));
    foreach ((array) $dataset['rankings'] as $row) {
      fputcsv($handle, array($row->keyword_text, $row->page_url, $row->clicks, $row->impressions, $row->ctr, $row->position_avg, $row->snapshot_date));
    }
    fputcsv($handle, array());
    fputcsv($handle, array('Search Queries'));
    fputcsv($handle, array('Query', 'Page URL', 'Clicks', 'Impressions', 'CTR', 'Avg Position', 'Snapshot Date'));
    foreach ((array) $dataset['queries'] as $row) {
      fputcsv($handle, array($row->query_text, $row->page_url, $row->clicks, $row->impressions, $row->ctr, $row->position_avg, $row->snapshot_date));
    }
    fputcsv($handle, array());
    fputcsv($handle, array('Competitor Snapshots'));
    fputcsv($handle, array('Keyword', 'Target URL', 'Competitor Position', 'Competitor Domain', 'Competitor URL', 'Summary', 'Snapshot Date'));
    foreach ((array) ($dataset['competitors'] ?? array()) as $row) {
      fputcsv($handle, array($row->keyword_text, $row->target_page_url, $row->competitor_position, $row->competitor_domain, $row->competitor_url, $row->summary, $row->snapshot_date));
    }
    fputcsv($handle, array());
    fputcsv($handle, array('Content Opportunities'));
    fputcsv($handle, array('Type', 'Title', 'Target Keyword', 'Priority', 'Status', 'Summary'));
    foreach ((array) ($dataset['opportunities'] ?? array()) as $row) {
      fputcsv($handle, array($row->opportunity_type, $row->title, $row->target_keyword, $row->priority_score, $row->status, $row->summary));
    }
  }

  private function export_seo_report($format = 'csv')
  {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You are not allowed to export this report.', 'jacana-luxe'));
    }

    $dataset = $this->get_report_dataset();
    $filename_base = 'jacana-seo-report-' . gmdate('Ymd-His');

    if ($format === 'csv') {
      nocache_headers();
      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename="' . $filename_base . '.csv"');
      $handle = fopen('php://output', 'w');
      $this->output_report_rows_csv($handle, $dataset);
      fclose($handle);
      return;
    }

    if ($format === 'excel') {
      nocache_headers();
      header('Content-Type: application/vnd.ms-excel; charset=utf-8');
      header('Content-Disposition: attachment; filename="' . $filename_base . '.xls"');
      echo '<table border="1"><tr><th colspan="5">Jacana SEO Report</th></tr>';
      echo '<tr><td>Generated At</td><td colspan="4">' . esc_html($dataset['generated_at']) . '</td></tr>';
      echo '<tr><th colspan="5">Page Inventory</th></tr>';
      echo '<tr><th>ID</th><th>Title</th><th>Type</th><th>SEO Score</th><th>Issues</th></tr>';
      foreach ((array) $dataset['pages'] as $row) {
        echo '<tr><td>' . esc_html($row['id']) . '</td><td>' . esc_html($row['title']) . '</td><td>' . esc_html($row['type']) . '</td><td>' . esc_html($row['seo_score']) . '</td><td>' . esc_html(implode('; ', (array) ($row['issues'] ?? array()))) . '</td></tr>';
      }
      echo '<tr><th colspan="5">Keyword Rankings</th></tr>';
      echo '<tr><th>Keyword</th><th>Page URL</th><th>Clicks</th><th>Impressions</th><th>Avg Position</th></tr>';
      foreach ((array) $dataset['rankings'] as $row) {
        echo '<tr><td>' . esc_html($row->keyword_text) . '</td><td>' . esc_html($row->page_url) . '</td><td>' . esc_html($row->clicks) . '</td><td>' . esc_html($row->impressions) . '</td><td>' . esc_html($row->position_avg) . '</td></tr>';
      }
      echo '<tr><th colspan="5">Content Opportunities</th></tr>';
      echo '<tr><th>Type</th><th>Title</th><th>Target Keyword</th><th>Priority</th><th>Status</th></tr>';
      foreach ((array) ($dataset['opportunities'] ?? array()) as $row) {
        echo '<tr><td>' . esc_html($row->opportunity_type) . '</td><td>' . esc_html($row->title) . '</td><td>' . esc_html($row->target_keyword) . '</td><td>' . esc_html($row->priority_score) . '</td><td>' . esc_html($row->status) . '</td></tr>';
      }
      echo '</table>';
      return;
    }

    nocache_headers();
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Jacana SEO Report</title><style>body{font-family:Arial,sans-serif;padding:32px;color:#241a12}h1,h2{margin:0 0 12px}section{margin:0 0 28px}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.card{border:1px solid #ddd;padding:16px;border-radius:12px;background:#fff}table{width:100%;border-collapse:collapse;margin-top:12px}th,td{border:1px solid #ddd;padding:8px;text-align:left;font-size:13px}small{color:#666}.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}@media print{button{display:none}}</style></head><body>';
    echo '<div class="top"><div><h1>Jacana SEO Report</h1><small>Generated ' . esc_html($dataset['generated_at']) . '</small></div><button onclick="window.print()">Print / Save PDF</button></div>';
    echo '<section><h2>Audit Summary</h2><div class="grid">';
    foreach ((array) ($dataset['audit'] ?? array()) as $key => $value) {
      if (is_array($value)) {
        continue;
      }
      echo '<div class="card"><strong>' . esc_html(ucwords(str_replace('_', ' ', (string) $key))) . '</strong><div>' . esc_html((string) $value) . '</div></div>';
    }
    echo '</div></section>';
    echo '<section><h2>Keyword Rankings</h2><table><thead><tr><th>Keyword</th><th>Page URL</th><th>Clicks</th><th>Impressions</th><th>CTR</th><th>Avg Position</th><th>Date</th></tr></thead><tbody>';
    foreach ((array) $dataset['rankings'] as $row) {
      echo '<tr><td>' . esc_html($row->keyword_text) . '</td><td>' . esc_html($row->page_url) . '</td><td>' . esc_html($row->clicks) . '</td><td>' . esc_html($row->impressions) . '</td><td>' . esc_html($row->ctr) . '</td><td>' . esc_html($row->position_avg) . '</td><td>' . esc_html($row->snapshot_date) . '</td></tr>';
    }
    echo '</tbody></table></section>';
    echo '<section><h2>Page Inventory</h2><table><thead><tr><th>Title</th><th>Type</th><th>Score</th><th>Issues</th></tr></thead><tbody>';
    foreach ((array) $dataset['pages'] as $row) {
      echo '<tr><td>' . esc_html($row['title']) . '</td><td>' . esc_html($row['type']) . '</td><td>' . esc_html($row['seo_score']) . '</td><td>' . esc_html(implode('; ', (array) ($row['issues'] ?? array()))) . '</td></tr>';
    }
    echo '</tbody></table></section>';
    echo '<section><h2>Competitor Snapshots</h2><table><thead><tr><th>Keyword</th><th>Position</th><th>Domain</th><th>Summary</th></tr></thead><tbody>';
    foreach ((array) ($dataset['competitors'] ?? array()) as $row) {
      echo '<tr><td>' . esc_html($row->keyword_text) . '</td><td>' . esc_html($row->competitor_position) . '</td><td>' . esc_html($row->competitor_domain) . '</td><td>' . esc_html($row->summary) . '</td></tr>';
    }
    echo '</tbody></table></section>';
    echo '<section><h2>Content Opportunities</h2><table><thead><tr><th>Type</th><th>Title</th><th>Keyword</th><th>Priority</th><th>Status</th></tr></thead><tbody>';
    foreach ((array) ($dataset['opportunities'] ?? array()) as $row) {
      echo '<tr><td>' . esc_html($row->opportunity_type) . '</td><td>' . esc_html($row->title) . '</td><td>' . esc_html($row->target_keyword) . '</td><td>' . esc_html($row->priority_score) . '</td><td>' . esc_html($row->status) . '</td></tr>';
    }
    echo '</tbody></table></section></body></html>';
  }

  public function render_dashboard_page()
  {
    $metrics = $this->get_dashboard_metrics();
    $top_services = $this->get_top_services();
    $top_pages = $this->get_top_pages();
    $recent_leads = $this->get_recent_leads();
    $recent_events = $this->get_recent_conversion_events();
    $recommendations = $this->get_conversion_recommendations($metrics);

    $this->render_admin_shell_start(
      __('Jacana CRM Dashboard', 'jacana-luxe'),
      __('Site intelligence', 'jacana-luxe'),
      __('Monitor demand, spot high-intent visitors, improve conversions, and prep the site for launch from one place.', 'jacana-luxe')
    );

    echo '<section class="jacana-admin-grid jacana-admin-grid-kpis">';
    $kpis = array(
      __('Total leads', 'jacana-luxe') => (int) $metrics['total_leads'],
      __('Hot leads', 'jacana-luxe') => (int) $metrics['hot_leads'],
      __('Active visitors (7d)', 'jacana-luxe') => (int) $metrics['active_visitors'],
      __('Conversion signals (7d)', 'jacana-luxe') => (int) $metrics['conversion_signals'],
      __('Avg lead score', 'jacana-luxe') => (int) $metrics['avg_lead_score'],
      __('Pageviews (7d)', 'jacana-luxe') => (int) $metrics['pageviews_7d'],
    );
    foreach ($kpis as $label => $value) {
      echo '<article class="jacana-admin-card jacana-kpi-card"><span>' . esc_html($label) . '</span><strong>' . esc_html($value) . '</strong></article>';
    }
    echo '</section>';

    echo '<section class="jacana-admin-grid jacana-admin-grid-main">';
    echo '<article class="jacana-admin-card">';
    echo '<div class="jacana-card-head"><h2>' . esc_html__('Recommended actions', 'jacana-luxe') . '</h2><a class="button button-secondary" href="' . esc_url($this->admin_page_url('jacana-crm-ai-lab')) . '">' . esc_html__('Open AI Workbench', 'jacana-luxe') . '</a></div>';
    echo '<ul class="jacana-insight-list">';
    foreach ($recommendations as $item) {
      echo '<li>' . esc_html($item) . '</li>';
    }
    echo '</ul>';
    echo '</article>';

    echo '<article class="jacana-admin-card">';
    echo '<div class="jacana-card-head"><h2>' . esc_html__('Most requested services', 'jacana-luxe') . '</h2><a href="' . esc_url($this->admin_page_url('jacana-crm-leads')) . '">' . esc_html__('View leads', 'jacana-luxe') . '</a></div>';
    if ($top_services) {
      echo '<div class="jacana-bar-list">';
      $max = max(array_map(static function ($row) { return (int) $row->total; }, $top_services));
      foreach ($top_services as $row) {
        $width = $max > 0 ? max(8, round(((int) $row->total / $max) * 100)) : 0;
        echo '<div class="jacana-bar-item"><div class="jacana-bar-label"><span>' . esc_html($row->label) . '</span><strong>' . esc_html($row->total) . '</strong></div><div class="jacana-bar-track"><span style="width:' . esc_attr($width) . '%;"></span></div></div>';
      }
      echo '</div>';
    } else {
      echo '<p class="jacana-muted">' . esc_html__('No service demand data yet.', 'jacana-luxe') . '</p>';
    }
    echo '</article>';
    echo '</section>';

    echo '<section class="jacana-admin-grid jacana-admin-grid-main">';
    echo '<article class="jacana-admin-card">';
    echo '<div class="jacana-card-head"><h2>' . esc_html__('Top pages', 'jacana-luxe') . '</h2><a href="' . esc_url($this->admin_page_url('jacana-crm-seo')) . '">' . esc_html__('Open SEO Studio', 'jacana-luxe') . '</a></div>';
    if ($top_pages) {
      echo '<div class="jacana-activity-list">';
      foreach ($top_pages as $row) {
        echo '<div class="jacana-activity-item"><div><strong>' . esc_html(wp_parse_url($row->url, PHP_URL_PATH) ?: $row->url) . '</strong><span>' . esc_html__('Most recent visit: ', 'jacana-luxe') . esc_html($row->last_seen) . '</span></div><em>' . esc_html($row->total) . ' ' . esc_html__('views', 'jacana-luxe') . '</em></div>';
      }
      echo '</div>';
    } else {
      echo '<p class="jacana-muted">' . esc_html__('No pageview data yet.', 'jacana-luxe') . '</p>';
    }
    echo '</article>';

    echo '<article class="jacana-admin-card">';
    echo '<div class="jacana-card-head"><h2>' . esc_html__('Recent conversion activity', 'jacana-luxe') . '</h2><a href="' . esc_url($this->admin_page_url('jacana-crm-visitors')) . '">' . esc_html__('Inspect visitors', 'jacana-luxe') . '</a></div>';
    if ($recent_events) {
      echo '<div class="jacana-activity-list">';
      foreach ($recent_events as $row) {
        $payload = json_decode((string) $row->payload, true);
        $label = is_array($payload) ? (string) ($payload['label'] ?? $payload['service'] ?? $row->type) : $row->type;
        echo '<div class="jacana-activity-item"><div><strong>' . esc_html(ucwords(str_replace('_', ' ', $row->type))) . '</strong><span>' . esc_html($label) . '</span></div><em>' . esc_html($row->created_at) . '</em></div>';
      }
      echo '</div>';
    } else {
      echo '<p class="jacana-muted">' . esc_html__('No conversion activity yet.', 'jacana-luxe') . '</p>';
    }
    echo '</article>';
    echo '</section>';

    echo '<section class="jacana-admin-grid jacana-admin-grid-main">';
    echo '<article class="jacana-admin-card">';
    echo '<div class="jacana-card-head"><h2>' . esc_html__('Recent leads', 'jacana-luxe') . '</h2><a href="' . esc_url($this->admin_page_url('jacana-crm-leads')) . '">' . esc_html__('Manage leads', 'jacana-luxe') . '</a></div>';
    if ($recent_leads) {
      echo '<table class="widefat striped jacana-admin-table"><thead><tr><th>' . esc_html__('Lead', 'jacana-luxe') . '</th><th>' . esc_html__('Service', 'jacana-luxe') . '</th><th>' . esc_html__('Score', 'jacana-luxe') . '</th><th>' . esc_html__('Status', 'jacana-luxe') . '</th></tr></thead><tbody>';
      foreach ($recent_leads as $lead) {
        echo '<tr>';
        echo '<td><strong>' . esc_html($lead->name ?: __('Unnamed lead', 'jacana-luxe')) . '</strong><br><span class="jacana-muted">' . esc_html($lead->email ?: $lead->phone) . '</span></td>';
        echo '<td>' . esc_html($lead->service_interest ?: '—') . '</td>';
        echo '<td><span class="jacana-score-badge">' . esc_html((int) $lead->conversion_score) . '</span></td>';
        echo '<td>' . esc_html($lead->status) . '</td>';
        echo '</tr>';
      }
      echo '</tbody></table>';
    } else {
      echo '<p class="jacana-muted">' . esc_html__('No leads yet.', 'jacana-luxe') . '</p>';
    }
    echo '</article>';

    echo '<article class="jacana-admin-card jacana-launch-card">';
    echo '<div class="jacana-card-head"><h2>' . esc_html__('Launch checklist', 'jacana-luxe') . '</h2></div>';
    echo '<ul class="jacana-insight-list">';
    echo '<li>' . esc_html__('Confirm Gemini API key, smart prompt settings, and business contact details.', 'jacana-luxe') . '</li>';
    echo '<li>' . esc_html__('Check top service pages and booking page CTAs before pushing to production.', 'jacana-luxe') . '</li>';
    echo '<li>' . esc_html__('Review SEO Studio for pages missing excerpts or featured images.', 'jacana-luxe') . '</li>';
    echo '<li>' . esc_html__('Use AI Workbench to generate fresh CTA copy, FAQ ideas, and launch messaging.', 'jacana-luxe') . '</li>';
    echo '</ul>';
    echo '</article>';
    echo '</section>';

    $this->render_admin_shell_end();
  }

  public function render_leads_page()
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_leads';
    $search = sanitize_text_field((string) ($_GET['s'] ?? ''));
    $service_filter = sanitize_text_field((string) ($_GET['service'] ?? ''));
    $status_filter = sanitize_key((string) ($_GET['status'] ?? ''));
    $lead_id = absint($_GET['lead'] ?? 0);

    $where = array('1=1');
    if ($search !== '') {
      $like = '%' . $wpdb->esc_like($search) . '%';
      $where[] = $wpdb->prepare('(name LIKE %s OR email LIKE %s OR phone LIKE %s OR summary LIKE %s)', $like, $like, $like, $like);
    }
    if ($service_filter !== '') {
      $where[] = $wpdb->prepare('service_interest = %s', $service_filter);
    }
    if ($status_filter !== '') {
      $where[] = $wpdb->prepare('status = %s', $status_filter);
    }

    $where_sql = implode(' AND ', $where);
    $rows = $wpdb->get_results("SELECT * FROM {$table} WHERE {$where_sql} ORDER BY conversion_score DESC, updated_at DESC LIMIT 80");
    $services = $wpdb->get_col("SELECT DISTINCT service_interest FROM {$table} WHERE service_interest <> '' ORDER BY service_interest ASC");
    $selected_lead = $lead_id > 0 ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $lead_id)) : null;

    $this->render_admin_shell_start(
      __('Lead Console', 'jacana-luxe'),
      __('Sales operations', 'jacana-luxe'),
      __('Filter enquiries, prioritize hot prospects, and keep follow-up notes ready for the production launch.', 'jacana-luxe')
    );

    echo '<section class="jacana-admin-card">';
    echo '<form method="get" class="jacana-filter-bar">';
    echo '<input type="hidden" name="page" value="jacana-crm-leads" />';
    echo '<input type="search" name="s" value="' . esc_attr($search) . '" placeholder="' . esc_attr__('Search leads, emails, phones, notes...', 'jacana-luxe') . '" />';
    echo '<select name="service"><option value="">' . esc_html__('All services', 'jacana-luxe') . '</option>';
    foreach ((array) $services as $service) {
      echo '<option value="' . esc_attr($service) . '"' . selected($service_filter, $service, false) . '>' . esc_html($service) . '</option>';
    }
    echo '</select>';
    echo '<select name="status">';
    $statuses = array('' => __('All statuses', 'jacana-luxe'), 'new' => __('New', 'jacana-luxe'), 'qualified' => __('Qualified', 'jacana-luxe'), 'proposal_sent' => __('Proposal sent', 'jacana-luxe'), 'won' => __('Won', 'jacana-luxe'), 'lost' => __('Lost', 'jacana-luxe'));
    foreach ($statuses as $value => $label) {
      echo '<option value="' . esc_attr($value) . '"' . selected($status_filter, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<button class="button button-primary" type="submit">' . esc_html__('Filter', 'jacana-luxe') . '</button>';
    echo '<a class="button" href="' . esc_url($this->admin_page_url('jacana-crm-leads')) . '">' . esc_html__('Reset', 'jacana-luxe') . '</a>';
    echo '</form>';
    echo '</section>';

    echo '<section class="jacana-admin-grid jacana-admin-grid-main">';
    echo '<article class="jacana-admin-card">';
    echo '<div class="jacana-card-head"><h2>' . esc_html__('Lead pipeline', 'jacana-luxe') . '</h2><span class="jacana-muted">' . esc_html(count($rows)) . ' ' . esc_html__('results', 'jacana-luxe') . '</span></div>';
    if ($rows) {
      echo '<table class="widefat striped jacana-admin-table"><thead><tr>';
      echo '<th>' . esc_html__('Lead', 'jacana-luxe') . '</th><th>' . esc_html__('Service', 'jacana-luxe') . '</th><th>' . esc_html__('Travel brief', 'jacana-luxe') . '</th><th>' . esc_html__('Score', 'jacana-luxe') . '</th><th>' . esc_html__('Status', 'jacana-luxe') . '</th><th>' . esc_html__('Priority', 'jacana-luxe') . '</th><th>' . esc_html__('Updated', 'jacana-luxe') . '</th>';
      echo '</tr></thead><tbody>';
      foreach ($rows as $row) {
        echo '<tr>';
        echo '<td><a href="' . esc_url($this->admin_page_url('jacana-crm-leads', array('lead' => $row->id))) . '"><strong>' . esc_html($row->name ?: __('Unnamed lead', 'jacana-luxe')) . '</strong></a><br><span class="jacana-muted">' . esc_html($row->email ?: $row->phone ?: '—') . '</span></td>';
        echo '<td>' . esc_html($row->service_interest ?: '—') . '</td>';
        echo '<td>' . esc_html($row->travel_dates ?: '—') . '<br><span class="jacana-muted">' . esc_html($row->travel_style ?: __('No style yet', 'jacana-luxe')) . '</span></td>';
        echo '<td><span class="jacana-score-badge">' . esc_html((int) $row->conversion_score) . '</span></td>';
        echo '<td><span class="jacana-status-pill jacana-status-' . esc_attr(sanitize_html_class($row->status)) . '">' . esc_html($row->status) . '</span></td>';
        echo '<td><span class="jacana-priority-pill jacana-priority-' . esc_attr(sanitize_html_class($row->priority ?: 'normal')) . '">' . esc_html($row->priority ?: 'normal') . '</span></td>';
        echo '<td>' . esc_html($row->updated_at) . '</td>';
        echo '</tr>';
      }
      echo '</tbody></table>';
    } else {
      echo '<p class="jacana-muted">' . esc_html__('No leads matched the current filters.', 'jacana-luxe') . '</p>';
    }
    echo '</article>';

    echo '</section>';

    echo '<section class="jacana-admin-grid jacana-admin-grid-main jacana-admin-grid-main-lead-detail">';
    echo '<article class="jacana-admin-card jacana-lead-detail-card">';
    echo '<div class="jacana-card-head"><h2>' . esc_html__('Lead detail', 'jacana-luxe') . '</h2></div>';
    if ($selected_lead) {
      echo '<div class="jacana-detail-list">';
      echo '<div><span>' . esc_html__('Name', 'jacana-luxe') . '</span><strong>' . esc_html($selected_lead->name ?: '—') . '</strong></div>';
      echo '<div><span>' . esc_html__('Email', 'jacana-luxe') . '</span><strong>' . esc_html($selected_lead->email ?: '—') . '</strong></div>';
      echo '<div><span>' . esc_html__('Phone', 'jacana-luxe') . '</span><strong>' . esc_html($selected_lead->phone ?: '—') . '</strong></div>';
      echo '<div><span>' . esc_html__('Service', 'jacana-luxe') . '</span><strong>' . esc_html($selected_lead->service_interest ?: '—') . '</strong></div>';
      echo '<div><span>' . esc_html__('Travel dates', 'jacana-luxe') . '</span><strong>' . esc_html($selected_lead->travel_dates ?: '—') . '</strong></div>';
      echo '<div><span>' . esc_html__('Source page', 'jacana-luxe') . '</span><strong>' . esc_html($selected_lead->source_page ?: '—') . '</strong></div>';
      echo '</div>';
      echo '<form method="post" class="jacana-lead-form">';
      wp_nonce_field('jacana_crm_update_lead');
      echo '<input type="hidden" name="jacana_crm_action" value="update_lead" />';
      echo '<input type="hidden" name="lead_id" value="' . esc_attr($selected_lead->id) . '" />';
      echo '<label><span>' . esc_html__('Status', 'jacana-luxe') . '</span><select name="status">';
      foreach (array('new', 'qualified', 'proposal_sent', 'won', 'lost') as $status) {
        echo '<option value="' . esc_attr($status) . '"' . selected($selected_lead->status, $status, false) . '>' . esc_html($status) . '</option>';
      }
      echo '</select></label>';
      echo '<label><span>' . esc_html__('Priority', 'jacana-luxe') . '</span><select name="priority">';
      foreach (array('low', 'normal', 'high', 'vip') as $priority) {
        echo '<option value="' . esc_attr($priority) . '"' . selected($selected_lead->priority ?: 'normal', $priority, false) . '>' . esc_html($priority) . '</option>';
      }
      echo '</select></label>';
      echo '<label><span>' . esc_html__('Next action', 'jacana-luxe') . '</span><input type="datetime-local" name="next_action_at" value="' . esc_attr(!empty($selected_lead->next_action_at) ? gmdate('Y-m-d\TH:i', strtotime($selected_lead->next_action_at)) : '') . '" /></label>';
      echo '<label><span>' . esc_html__('Internal notes', 'jacana-luxe') . '</span><textarea name="internal_notes" rows="6">' . esc_textarea($selected_lead->internal_notes ?? '') . '</textarea></label>';
      echo '<button class="button button-primary" type="submit">' . esc_html__('Save lead updates', 'jacana-luxe') . '</button>';
      echo '</form>';
    } else {
      echo '<p class="jacana-muted">' . esc_html__('Choose a lead from the table to open follow-up details and notes.', 'jacana-luxe') . '</p>';
    }
    echo '</article>';
    echo '</section>';

    $this->render_admin_shell_end();
  }

  private function get_recent_bookings($limit = 60)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_bookings';
    return $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d",
      max(1, absint($limit))
    ));
  }

  public function render_bookings_page()
  {
    $rows = $this->get_recent_bookings(80);
    $this->render_admin_shell_start(
      __('Bookings', 'jacana-luxe'),
      __('CRM booking requests', 'jacana-luxe'),
      __('All booking-modal requests land here before they are followed up as quotes, proposals, or confirmed itineraries.', 'jacana-luxe')
    );

    echo '<section class="jacana-admin-grid jacana-admin-grid-kpis">';
    echo '<article class="jacana-admin-card jacana-kpi-card"><span>' . esc_html__('Booking requests', 'jacana-luxe') . '</span><strong>' . esc_html(count($rows)) . '</strong></article>';
    echo '<article class="jacana-admin-card jacana-kpi-card"><span>' . esc_html__('Fallback inbox', 'jacana-luxe') . '</span><strong>' . esc_html($this->get_booking_inbox_email()) . '</strong></article>';
    echo '</section>';

    echo '<section class="jacana-admin-card">';
    echo '<div class="jacana-card-head"><h2>' . esc_html__('Recent booking requests', 'jacana-luxe') . '</h2></div>';
    if ($rows) {
      echo '<table class="widefat striped jacana-admin-table"><thead><tr><th>' . esc_html__('Traveler', 'jacana-luxe') . '</th><th>' . esc_html__('Services and route', 'jacana-luxe') . '</th><th>' . esc_html__('Trip details', 'jacana-luxe') . '</th><th>' . esc_html__('Source', 'jacana-luxe') . '</th><th>' . esc_html__('Time', 'jacana-luxe') . '</th></tr></thead><tbody>';
      foreach ($rows as $row) {
        $details = !empty($row->details_json) ? json_decode((string) $row->details_json, true) : array();
        if (!is_array($details)) {
          $details = array();
        }
        $selected_services = !empty($details['selected_services']) && is_array($details['selected_services'])
          ? $details['selected_services']
          : array_filter(array_map('trim', explode(',', (string) ($row->selected_services ?? ''))));
        echo '<tr>';
        echo '<td><strong>' . esc_html((string) $row->traveler_name) . '</strong><br><span class="jacana-muted">' . esc_html((string) $row->traveler_email ?: (string) $row->traveler_phone) . '</span></td>';
        echo '<td><strong>' . esc_html((string) $row->service_interest ?: '—') . '</strong>';
        if (!empty($selected_services)) {
          echo '<br><span class="jacana-muted">' . esc_html__('Selected services:', 'jacana-luxe') . ' ' . esc_html(implode(', ', $selected_services)) . '</span>';
        }
        if (!empty($row->vehicle_interest)) {
          echo '<br><span class="jacana-muted">' . esc_html__('Vehicle:', 'jacana-luxe') . ' ' . esc_html((string) $row->vehicle_interest) . '</span>';
        }
        if (!empty($row->destination_interest)) {
          echo '<br><span class="jacana-muted">' . esc_html__('Destination:', 'jacana-luxe') . ' ' . esc_html((string) $row->destination_interest) . '</span>';
        }
        echo '</td>';
        echo '<td>';
        echo '<strong>' . esc_html((string) $row->travel_dates ?: '—') . '</strong><br><span class="jacana-muted">' . esc_html__('Party:', 'jacana-luxe') . ' ' . esc_html((string) $row->party_size ?: '—') . '</span>';
        if (!empty($row->country)) {
          echo '<br><span class="jacana-muted">' . esc_html__('Country:', 'jacana-luxe') . ' ' . esc_html((string) $row->country) . '</span>';
        }
        if (!empty($row->duration)) {
          echo '<br><span class="jacana-muted">' . esc_html__('Duration:', 'jacana-luxe') . ' ' . esc_html((string) $row->duration) . '</span>';
        }
        if (!empty($row->budget_tier)) {
          echo '<br><span class="jacana-muted">' . esc_html__('Budget:', 'jacana-luxe') . ' ' . esc_html((string) $row->budget_tier) . '</span>';
        }
        if (!empty($row->accommodation_style)) {
          echo '<br><span class="jacana-muted">' . esc_html__('Accommodation:', 'jacana-luxe') . ' ' . esc_html((string) $row->accommodation_style) . '</span>';
        }
        if (!empty($row->additional_places)) {
          echo '<br><span class="jacana-muted">' . esc_html__('Places:', 'jacana-luxe') . ' ' . esc_html(wp_trim_words((string) $row->additional_places, 18, '...')) . '</span>';
        }
        if (!empty($row->message)) {
          echo '<br><span class="jacana-muted">' . esc_html__('Message:', 'jacana-luxe') . ' ' . esc_html(wp_trim_words((string) $row->message, 18, '...')) . '</span>';
        }
        echo '</td>';
        echo '<td>';
        echo '<strong>' . esc_html((string) $row->source_widget ?: '—') . '</strong>';
        if (!empty($row->source_section)) {
          echo '<br><span class="jacana-muted">' . esc_html__('CTA section:', 'jacana-luxe') . ' ' . esc_html((string) $row->source_section) . '</span>';
        }
        if (!empty($row->source_page)) {
          echo '<br><span class="jacana-muted">' . esc_html(parse_url((string) $row->source_page, PHP_URL_PATH) ?: (string) $row->source_page) . '</span>';
        }
        echo '</td>';
        echo '<td>' . esc_html((string) $row->created_at) . '</td>';
        echo '</tr>';
      }
      echo '</tbody></table>';
    } else {
      echo '<p class="jacana-muted">' . esc_html__('No booking requests stored yet.', 'jacana-luxe') . '</p>';
    }
    echo '</section>';

    $this->render_admin_shell_end();
  }

  public function render_ai_journeys_page()
  {
    $rows = $this->get_recent_ai_interactions(80);
    $this->render_admin_shell_start(
      __('AI Journeys', 'jacana-luxe'),
      __('In-page AI conversion layer', 'jacana-luxe'),
      __('See how visitors interact with the non-intrusive AI surfaces before they escalate into chat, booking, or callback requests.', 'jacana-luxe')
    );

    echo '<section class="jacana-admin-grid jacana-admin-grid-kpis">';
    echo '<article class="jacana-admin-card jacana-kpi-card"><span>' . esc_html__('Interactions shown', 'jacana-luxe') . '</span><strong>' . esc_html(count($rows)) . '</strong></article>';
    echo '<article class="jacana-admin-card jacana-kpi-card"><span>' . esc_html__('Major CTA route', 'jacana-luxe') . '</span><strong>' . esc_html__('Decision sheets', 'jacana-luxe') . '</strong></article>';
    echo '<article class="jacana-admin-card jacana-kpi-card"><span>' . esc_html__('Fallback inbox', 'jacana-luxe') . '</span><strong>' . esc_html($this->get_booking_inbox_email()) . '</strong></article>';
    echo '</section>';

    echo '<section class="jacana-admin-card">';
    echo '<div class="jacana-card-head"><h2>' . esc_html__('Recent AI interactions', 'jacana-luxe') . '</h2></div>';
    if ($rows) {
      echo '<table class="widefat striped jacana-admin-table"><thead><tr><th>' . esc_html__('Component', 'jacana-luxe') . '</th><th>' . esc_html__('Widget', 'jacana-luxe') . '</th><th>' . esc_html__('Service', 'jacana-luxe') . '</th><th>' . esc_html__('Action', 'jacana-luxe') . '</th><th>' . esc_html__('Page', 'jacana-luxe') . '</th><th>' . esc_html__('Time', 'jacana-luxe') . '</th></tr></thead><tbody>';
      foreach ($rows as $row) {
        $path = wp_parse_url((string) $row->page_url, PHP_URL_PATH);
        echo '<tr>';
        echo '<td><strong>' . esc_html((string) $row->component_type) . '</strong><br><span class="jacana-muted">' . esc_html((string) $row->component_key) . '</span></td>';
        echo '<td>' . esc_html((string) $row->widget_name ?: '—') . '</td>';
        echo '<td>' . esc_html((string) $row->service_interest ?: '—') . '</td>';
        echo '<td>' . esc_html((string) $row->action_type ?: 'view') . '</td>';
        echo '<td>' . esc_html($path ?: (string) $row->page_url) . '</td>';
        echo '<td>' . esc_html((string) $row->created_at) . '</td>';
        echo '</tr>';
      }
      echo '</tbody></table>';
    } else {
      echo '<p class="jacana-muted">' . esc_html__('No AI journey events have been stored yet.', 'jacana-luxe') . '</p>';
    }
    echo '</section>';

    $this->render_admin_shell_end();
  }

  public function render_reviews_page()
  {
    $rows = $this->get_reviews(array('status' => array('pending', 'approved', 'featured', 'rejected', 'archived'), 'limit' => 80));
    $this->render_admin_shell_start(
      __('Reviews', 'jacana-luxe'),
      __('CRM-backed testimonial system', 'jacana-luxe'),
      __('Moderate traveler reviews once, then reuse approved feedback across service pages, tours, and SEO content planning.', 'jacana-luxe')
    );

    echo '<section class="jacana-admin-grid jacana-admin-grid-kpis">';
    echo '<article class="jacana-admin-card jacana-kpi-card"><span>' . esc_html__('Pending', 'jacana-luxe') . '</span><strong>' . esc_html(count($this->get_reviews(array('status' => array('pending'), 'limit' => 200)))) . '</strong></article>';
    echo '<article class="jacana-admin-card jacana-kpi-card"><span>' . esc_html__('Approved', 'jacana-luxe') . '</span><strong>' . esc_html(count($this->get_reviews(array('status' => array('approved', 'featured'), 'limit' => 200)))) . '</strong></article>';
    echo '<article class="jacana-admin-card jacana-kpi-card"><span>' . esc_html__('Average rating', 'jacana-luxe') . '</span><strong>' . esc_html($this->get_average_review_rating()) . '/5</strong></article>';
    echo '</section>';

    echo '<section class="jacana-admin-card">';
    echo '<div class="jacana-card-head"><h2>' . esc_html__('Moderation queue', 'jacana-luxe') . '</h2></div>';
    if ($rows) {
      echo '<div class="jacana-review-admin-list">';
      foreach ($rows as $row) {
        echo '<article class="jacana-seo-suggestion-card">';
        echo '<div class="jacana-card-head"><h3>' . esc_html((string) $row->name) . '</h3><span class="jacana-status-pill jacana-status-' . esc_attr(sanitize_html_class((string) $row->status)) . '">' . esc_html((string) $row->status) . '</span></div>';
        echo '<p class="jacana-muted">' . esc_html((string) $row->trip_label ?: __('Traveler review', 'jacana-luxe')) . ' • ' . esc_html((string) $row->service_interest ?: __('General', 'jacana-luxe')) . ' • ' . esc_html((int) $row->rating) . '/5</p>';
        echo '<p>' . esc_html(wp_trim_words((string) $row->review_text, 40, '...')) . '</p>';
        echo '<div class="jacana-card-actions">';
        foreach (array(
          'approved' => __('Approve', 'jacana-luxe'),
          'featured' => __('Feature', 'jacana-luxe'),
          'rejected' => __('Reject', 'jacana-luxe'),
          'archived' => __('Archive', 'jacana-luxe'),
        ) as $status => $label) {
          echo '<form method="post" class="jacana-inline-form">';
          wp_nonce_field('jacana_crm_update_review');
          echo '<input type="hidden" name="jacana_crm_action" value="update_review" />';
          echo '<input type="hidden" name="review_id" value="' . esc_attr((int) $row->id) . '" />';
          echo '<input type="hidden" name="review_status" value="' . esc_attr($status) . '" />';
          echo '<button class="button ' . ($status === 'approved' ? 'button-primary' : '') . '" type="submit">' . esc_html($label) . '</button></form>';
        }
        echo '<form method="post" class="jacana-inline-form" onsubmit="return confirm(\'' . esc_js(__('Permanently delete this review?', 'jacana-luxe')) . '\');">';
        wp_nonce_field('jacana_crm_delete_review');
        echo '<input type="hidden" name="jacana_crm_action" value="delete_review" />';
        echo '<input type="hidden" name="review_id" value="' . esc_attr((int) $row->id) . '" />';
        echo '<button class="button button-link-delete" type="submit">' . esc_html__('Delete', 'jacana-luxe') . '</button></form>';
        echo '</div></article>';
      }
      echo '</div>';
    } else {
      echo '<p class="jacana-muted">' . esc_html__('No reviews stored yet.', 'jacana-luxe') . '</p>';
    }
    echo '</section>';

    $this->render_admin_shell_end();
  }

  private function get_average_review_rating()
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_reviews';
    $value = $wpdb->get_var("SELECT COALESCE(ROUND(AVG(rating), 1), 0) FROM {$table} WHERE status IN ('approved', 'featured')");
    return $value !== null ? $value : '0';
  }

  public function render_visitors_page()
  {
    global $wpdb;
    if (isset($_GET['visitor'])) {
      $this->render_visitor_detail(absint($_GET['visitor']));
      return;
    }
    $table = $wpdb->prefix . 'jacana_visitors';
    $leads = $wpdb->prefix . 'jacana_leads';
    $rows = $wpdb->get_results(
      "SELECT v.*, 
        (SELECT l.name FROM {$leads} l WHERE l.visitor_id = v.id ORDER BY l.created_at DESC LIMIT 1) AS lead_name,
        (SELECT l.email FROM {$leads} l WHERE l.visitor_id = v.id ORDER BY l.created_at DESC LIMIT 1) AS lead_email,
        (SELECT l.country FROM {$leads} l WHERE l.visitor_id = v.id ORDER BY l.created_at DESC LIMIT 1) AS lead_country
       FROM {$table} v
       ORDER BY v.last_seen DESC
       LIMIT 50"
    );
    $this->render_admin_shell_start(
      __('Visitor Intelligence', 'jacana-luxe'),
      __('Behavior and sessions', 'jacana-luxe'),
      __('Inspect how visitors move through the site, what they ask Jana, and where conversion signals are strongest.', 'jacana-luxe')
    );
    echo '<div class="jacana-admin-card">';
    echo '<table class="widefat fixed striped jacana-admin-table"><thead><tr>';
    echo '<th>Visitor</th><th>Contact</th><th>Country</th><th>Last Seen</th><th>Sessions</th><th>Total Time</th><th>Details</th>';
    echo '</tr></thead><tbody>';
    if ($rows) {
      foreach ($rows as $row) {
        echo '<tr>';
        $display_name = $row->lead_name ? $row->lead_name : 'Visitor ' . substr($row->visitor_key, 0, 6);
        $contact = $row->lead_email ? $row->lead_email : '—';
        $country = $row->lead_country ? $row->lead_country : '—';
        echo '<td><strong>' . esc_html($display_name) . '</strong><br><span class="jacana-muted">' . esc_html(substr($row->visitor_key, 0, 10)) . '</span></td>';
        echo '<td>' . esc_html($contact) . '</td>';
        echo '<td>' . esc_html($country) . '</td>';
        echo '<td>' . esc_html($row->last_seen) . '</td>';
        echo '<td>' . esc_html($row->visit_count) . '</td>';
        echo '<td>' . esc_html($this->format_duration($row->total_time_sec)) . '</td>';
        echo '<td><a href="' . esc_url(admin_url('admin.php?page=jacana-crm-visitors&visitor=' . $row->id)) . '">View</a></td>';
        echo '</tr>';
      }
    }
    else {
      echo '<tr><td colspan="7">No visitors yet.</td></tr>';
    }
    echo '</tbody></table></div>';
    $this->render_admin_shell_end();
  }

  public function render_seo_page()
  {
    $rows = $this->get_page_audit_rows();
    $selected_post_id = absint($_GET['post_id'] ?? 0);
    $selected_state = $selected_post_id > 0 ? $this->get_post_current_seo_state($selected_post_id) : array();
    $selected_suggestions = $selected_post_id > 0 ? $this->get_seo_suggestions(array('post_id' => $selected_post_id, 'limit' => 50)) : array();
    $widget_blocks = $selected_post_id > 0 ? $this->get_post_widget_content_blocks($selected_post_id) : array();
    $queue = $this->get_seo_suggestions(array('status' => array('new', 'approved', 'reviewed'), 'limit' => 20));
    $audit = get_option('jacana_seo_last_audit_summary', array());
    $sync = get_option('jacana_seo_last_sync_summary', array());
    $sync_error = (string) get_option('jacana_seo_last_sync_error', '');
    $query_rows = $this->get_recent_query_rows(10);
    $ranking_rows = $this->get_latest_rankings(10);
    $competitor_rows = $this->get_recent_competitor_snapshots(10);
    $opportunity_rows = $this->get_recent_opportunities(8);
    $latest_report = $this->get_latest_report('strategic');
    $competitor_summary = (array) get_option('jacana_seo_last_competitor_scan_summary', array());
    $opportunity_summary = (array) get_option('jacana_seo_last_opportunity_summary', array());
    $report_summary = (array) get_option('jacana_seo_last_report_summary', array());
    $yoast_active = $this->has_yoast_seo();
    $allowed_panels = array('overview', 'pages', 'workspace', 'performance', 'reports', 'settings');
    $seo_panel = sanitize_key((string) ($_GET['seo_panel'] ?? ($selected_post_id > 0 ? 'workspace' : 'overview')));
    if (!in_array($seo_panel, $allowed_panels, true)) {
      $seo_panel = 'overview';
    }
    $suggested_fields = array();
    $widget_suggestions = array();
    $seo_field_suggestions = array();
    $issues_total = 0;
    $score_total = 0;
    $pages_needing_attention = array();

    foreach ($selected_suggestions as $suggestion) {
      if (strpos((string) $suggestion->field_key, 'widget::') === 0) {
        $widget_suggestions[] = $suggestion;
      } else {
        $seo_field_suggestions[] = $suggestion;
        if (!isset($suggested_fields[$suggestion->field_key])) {
          $suggested_fields[$suggestion->field_key] = $suggestion;
        }
      }
    }

    foreach ($rows as $row) {
      $issues_total += count((array) ($row['issues'] ?? array()));
      $score_total += (int) ($row['seo_score'] ?? 0);
      if ((int) ($row['seo_score'] ?? 100) < 70) {
        $pages_needing_attention[] = $row;
      }
    }

    $average_score = !empty($rows) ? (int) round($score_total / count($rows)) : 0;
    usort($pages_needing_attention, static function ($left, $right) {
      return ((int) ($left['seo_score'] ?? 100)) <=> ((int) ($right['seo_score'] ?? 100));
    });
    $pages_needing_attention = array_slice($pages_needing_attention, 0, 6);

    $panel_url = function ($panel, $args = array()) use ($selected_post_id) {
      $base_args = array('seo_panel' => $panel);
      if ($panel === 'workspace' && $selected_post_id > 0 && empty($args['post_id'])) {
        $base_args['post_id'] = $selected_post_id;
      }

      return $this->admin_page_url('jacana-crm-seo', array_merge($base_args, $args));
    };

    $this->render_admin_shell_start(
      __('SEO Studio', 'jacana-luxe'),
      __('AI-assisted search operations', 'jacana-luxe'),
      __('Review every page, generate AI SEO improvements, write approved changes into Yoast, and keep a daily audit baseline for launch.', 'jacana-luxe')
    );

    echo '<section class="jacana-seo-panel-nav" aria-label="' . esc_attr__('SEO Studio sections', 'jacana-luxe') . '">';
    foreach (array(
      'overview' => __('Overview', 'jacana-luxe'),
      'pages' => __('Pages', 'jacana-luxe'),
      'workspace' => __('Workspace', 'jacana-luxe'),
      'performance' => __('Performance', 'jacana-luxe'),
      'reports' => __('Reports', 'jacana-luxe'),
      'settings' => __('Settings', 'jacana-luxe'),
    ) as $panel_key => $label) {
      $link_classes = 'jacana-seo-panel-link';
      if ($seo_panel === $panel_key) {
        $link_classes .= ' is-active';
      }
      echo '<a class="' . esc_attr($link_classes) . '" href="' . esc_url($panel_url($panel_key)) . '">' . esc_html($label) . '</a>';
    }
    echo '</section>';

    if (!empty($selected_state)) {
      echo '<section class="jacana-admin-card jacana-seo-context-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('Current page context', 'jacana-luxe') . '</h2><a class="button" href="' . esc_url($panel_url('workspace', array('post_id' => $selected_state['id']))) . '">' . esc_html__('Open workspace', 'jacana-luxe') . '</a></div>';
      echo '<div class="jacana-seo-context-grid">';
      echo '<div><span>' . esc_html__('Selected page', 'jacana-luxe') . '</span><strong>' . esc_html($selected_state['title']) . '</strong></div>';
      echo '<div><span>' . esc_html__('Type', 'jacana-luxe') . '</span><strong>' . esc_html($selected_state['type']) . '</strong></div>';
      echo '<div><span>' . esc_html__('SEO score', 'jacana-luxe') . '</span><strong>' . esc_html((int) $selected_state['seo_score']) . '/100</strong></div>';
      echo '<div><span>' . esc_html__('Suggestions', 'jacana-luxe') . '</span><strong>' . esc_html(count($selected_suggestions)) . '</strong></div>';
      echo '</div></section>';
    }

    if ($seo_panel === 'overview') {
      echo '<section class="jacana-admin-grid jacana-admin-grid-kpis">';
      echo '<article class="jacana-admin-card jacana-kpi-card"><span>' . esc_html__('Pages tracked', 'jacana-luxe') . '</span><strong>' . esc_html(count($rows)) . '</strong></article>';
      echo '<article class="jacana-admin-card jacana-kpi-card"><span>' . esc_html__('Average SEO score', 'jacana-luxe') . '</span><strong>' . esc_html($average_score) . '</strong></article>';
      echo '<article class="jacana-admin-card jacana-kpi-card"><span>' . esc_html__('Open AI items', 'jacana-luxe') . '</span><strong>' . esc_html(count($queue)) . '</strong></article>';
      echo '<article class="jacana-admin-card jacana-kpi-card"><span>' . esc_html__('Tracked issues', 'jacana-luxe') . '</span><strong>' . esc_html($issues_total) . '</strong></article>';
      echo '</section>';

      echo '<section class="jacana-admin-grid jacana-admin-grid-main">';
      echo '<article class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('Priority pages', 'jacana-luxe') . '</h2><a class="button button-secondary" href="' . esc_url($panel_url('pages')) . '">' . esc_html__('Open page inventory', 'jacana-luxe') . '</a></div>';
      if ($pages_needing_attention) {
        echo '<div class="jacana-activity-list">';
        foreach ($pages_needing_attention as $page_row) {
          echo '<div class="jacana-activity-item"><div><strong>' . esc_html($page_row['title']) . '</strong><span>' . esc_html(wp_parse_url($page_row['permalink'], PHP_URL_PATH) ?: $page_row['permalink']) . ' • ' . esc_html((string) $page_row['type']) . '</span></div><a href="' . esc_url($panel_url('workspace', array('post_id' => (int) $page_row['id']))) . '">' . esc_html__('Review', 'jacana-luxe') . '</a></div>';
        }
        echo '</div>';
      } else {
        echo '<p class="jacana-muted">' . esc_html__('No low-score pages found. The current audit baseline looks clean.', 'jacana-luxe') . '</p>';
      }
      echo '</article>';

      echo '<article class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('Studio shortcuts', 'jacana-luxe') . '</h2></div>';
      echo '<div class="jacana-seo-shortcuts">';
      foreach (array(
        array('label' => __('Open workspace', 'jacana-luxe'), 'desc' => __('Inspect one page, review AI suggestions, and apply to Yoast.', 'jacana-luxe'), 'url' => $panel_url('workspace', $selected_post_id > 0 ? array('post_id' => $selected_post_id) : array())),
        array('label' => __('Performance snapshot', 'jacana-luxe'), 'desc' => __('See rankings, query rows, and sync status without leaving the studio.', 'jacana-luxe'), 'url' => $panel_url('performance')),
        array('label' => __('Reporting', 'jacana-luxe'), 'desc' => __('Export launch reports for PDF, Excel, or CSV handoff.', 'jacana-luxe'), 'url' => $panel_url('reports')),
        array('label' => __('Automation settings', 'jacana-luxe'), 'desc' => __('Manage Search Console, competitors, and SEO defaults.', 'jacana-luxe'), 'url' => $panel_url('settings')),
      ) as $shortcut) {
        echo '<a class="jacana-seo-shortcut-card" href="' . esc_url($shortcut['url']) . '"><strong>' . esc_html($shortcut['label']) . '</strong><span>' . esc_html($shortcut['desc']) . '</span></a>';
      }
      echo '</div></article>';
      echo '</section>';

      echo '<section class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('Launch SEO checklist', 'jacana-luxe') . '</h2></div>';
      echo '<ul class="jacana-insight-list">';
      echo '<li>' . esc_html__('Make sure high-value pages have clear excerpts; those are used as fallback descriptions.', 'jacana-luxe') . '</li>';
      echo '<li>' . esc_html__('Add featured images to destination, gallery, booking, and car rental pages for stronger social previews.', 'jacana-luxe') . '</li>';
      echo '<li>' . esc_html__('Approve and apply AI suggestions only after checking the search and social previews.', 'jacana-luxe') . '</li>';
      echo '<li>' . esc_html__('Use AI Workbench to generate tighter meta descriptions and conversion-oriented FAQ entries.', 'jacana-luxe') . '</li>';
      echo '</ul>';
      echo '</section>';
    }

    if ($seo_panel === 'settings') {
      echo '<section class="jacana-admin-grid jacana-admin-grid-main">';
      echo '<article class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('SEO defaults and Yoast integration', 'jacana-luxe') . '</h2><span class="jacana-pill-' . ($yoast_active ? 'ok' : 'warn') . '">' . esc_html($yoast_active ? __('Yoast detected', 'jacana-luxe') : __('Yoast not detected', 'jacana-luxe')) . '</span></div>';
      echo '<form method="post" action="options.php" class="jacana-settings-grid">';
      settings_fields('jacana_crm_settings');
      echo '<label><span>' . esc_html__('Organization name', 'jacana-luxe') . '</span><input type="text" name="jacana_seo_org_name" value="' . esc_attr(get_option('jacana_seo_org_name', get_bloginfo('name'))) . '" /></label>';
      echo '<label><span>' . esc_html__('Business email', 'jacana-luxe') . '</span><input type="email" name="jacana_seo_business_email" value="' . esc_attr(get_option('jacana_seo_business_email', get_option('admin_email'))) . '" /></label>';
      echo '<label><span>' . esc_html__('Business phone', 'jacana-luxe') . '</span><input type="text" name="jacana_seo_business_phone" value="' . esc_attr(get_option('jacana_seo_business_phone', '')) . '" /></label>';
      echo '<label><span>' . esc_html__('WhatsApp', 'jacana-luxe') . '</span><input type="text" name="jacana_seo_whatsapp" value="' . esc_attr(get_option('jacana_seo_whatsapp', '')) . '" /></label>';
      echo '<label class="jacana-span-2"><span>' . esc_html__('Street / lodge address', 'jacana-luxe') . '</span><input type="text" name="jacana_seo_business_address" value="' . esc_attr(get_option('jacana_seo_business_address', '')) . '" /></label>';
      echo '<label><span>' . esc_html__('City', 'jacana-luxe') . '</span><input type="text" name="jacana_seo_business_city" value="' . esc_attr(get_option('jacana_seo_business_city', 'Windhoek')) . '" /></label>';
      echo '<label><span>' . esc_html__('Country', 'jacana-luxe') . '</span><input type="text" name="jacana_seo_business_country" value="' . esc_attr(get_option('jacana_seo_business_country', 'Namibia')) . '" /></label>';
      echo '<label><span>' . esc_html__('Price range', 'jacana-luxe') . '</span><input type="text" name="jacana_seo_price_range" value="' . esc_attr(get_option('jacana_seo_price_range', '$$$')) . '" /></label>';
      echo '<label class="jacana-span-2"><span>' . esc_html__('Default meta description fallback', 'jacana-luxe') . '</span><input type="text" name="jacana_seo_default_description_suffix" value="' . esc_attr(get_option('jacana_seo_default_description_suffix', 'Tailor-made Namibia tours, safaris, transfers and car rentals.')) . '" /></label>';
      echo '<label class="jacana-checkbox"><input type="checkbox" name="jacana_seo_enable_schema" value="1" ' . checked((int) get_option('jacana_seo_enable_schema', 1), 1, false) . ' /><span>' . esc_html__('Output organization JSON-LD schema', 'jacana-luxe') . '</span></label>';
      echo '<label class="jacana-checkbox"><input type="checkbox" name="jacana_seo_enable_meta_fallback" value="1" ' . checked((int) get_option('jacana_seo_enable_meta_fallback', 1), 1, false) . ' /><span>' . esc_html__('Output fallback meta/OG descriptions when no SEO plugin handles them', 'jacana-luxe') . '</span></label>';
      echo '<div class="jacana-span-2">';
      submit_button(__('Save SEO settings', 'jacana-luxe'), 'primary', 'submit', false);
      echo '</div></form>';
      echo '</article>';

      echo '<article class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('Search Console, sync, and competitor sampling', 'jacana-luxe') . '</h2><span class="jacana-muted">' . esc_html__('Phase 2', 'jacana-luxe') . '</span></div>';
      echo '<form method="post" action="options.php" class="jacana-settings-grid">';
      settings_fields('jacana_crm_settings');
      echo '<label><span>' . esc_html__('SEO data source', 'jacana-luxe') . '</span><select name="jacana_seo_data_source">';
      foreach (array(
        'search_console' => __('Google Search Console', 'jacana-luxe'),
        'manual' => __('Manual only', 'jacana-luxe'),
      ) as $value => $label) {
        echo '<option value="' . esc_attr($value) . '"' . selected((string) get_option('jacana_seo_data_source', 'search_console'), $value, false) . '>' . esc_html($label) . '</option>';
      }
      echo '</select></label>';
      echo '<label class="jacana-checkbox"><input type="checkbox" name="jacana_seo_search_console_enabled" value="1" ' . checked((int) get_option('jacana_seo_search_console_enabled', 0), 1, false) . ' /><span>' . esc_html__('Enable Search Console sync', 'jacana-luxe') . '</span></label>';
      echo '<label class="jacana-span-2"><span>' . esc_html__('Search Console property URL', 'jacana-luxe') . '</span><input type="url" name="jacana_seo_gsc_property_url" value="' . esc_attr((string) get_option('jacana_seo_gsc_property_url', home_url('/'))) . '" /></label>';
      echo '<label class="jacana-span-2"><span>' . esc_html__('Service account JSON', 'jacana-luxe') . '</span><textarea name="jacana_seo_gsc_service_account_json" rows="7" placeholder="' . esc_attr__('Paste the Google service account JSON here, then share the Search Console property with that service account email.', 'jacana-luxe') . '">' . esc_textarea((string) get_option('jacana_seo_gsc_service_account_json', '')) . '</textarea></label>';
      echo '<label><span>' . esc_html__('Sync window (days)', 'jacana-luxe') . '</span><input type="number" min="1" max="28" name="jacana_seo_sync_window_days" value="' . esc_attr((int) get_option('jacana_seo_sync_window_days', 7)) . '" /></label>';
      echo '<label><span>' . esc_html__('Query row limit', 'jacana-luxe') . '</span><input type="number" min="10" max="250" name="jacana_seo_query_row_limit" value="' . esc_attr((int) get_option('jacana_seo_query_row_limit', 50)) . '" /></label>';
      echo '<label class="jacana-span-2"><span>' . esc_html__('Report email recipients', 'jacana-luxe') . '</span><textarea name="jacana_seo_report_email_recipients" rows="3">' . esc_textarea((string) get_option('jacana_seo_report_email_recipients', get_option('admin_email'))) . '</textarea></label>';
      echo '<label class="jacana-checkbox"><input type="checkbox" name="jacana_seo_daily_audit_enabled" value="1" ' . checked((int) get_option('jacana_seo_daily_audit_enabled', 1), 1, false) . ' /><span>' . esc_html__('Enable daily SEO audit cron', 'jacana-luxe') . '</span></label>';
      echo '<label><span>' . esc_html__('Competitor sampling mode', 'jacana-luxe') . '</span><select name="jacana_seo_competitor_sampling_mode">';
      foreach (array(
        'top3_random2' => __('Top 3 + Random 2', 'jacana-luxe'),
        'immediately_above' => __('Immediately above us', 'jacana-luxe'),
        'above_below' => __('Above and below us', 'jacana-luxe'),
        'custom_positions' => __('Custom positions', 'jacana-luxe'),
      ) as $value => $label) {
        echo '<option value="' . esc_attr($value) . '"' . selected((string) get_option('jacana_seo_competitor_sampling_mode', 'top3_random2'), $value, false) . '>' . esc_html($label) . '</option>';
      }
      echo '</select></label>';
      echo '<label><span>' . esc_html__('Fixed positions', 'jacana-luxe') . '</span><input type="text" name="jacana_seo_competitor_fixed_positions" value="' . esc_attr(get_option('jacana_seo_competitor_fixed_positions', '1,2,3')) . '" /></label>';
      echo '<label><span>' . esc_html__('Random range start', 'jacana-luxe') . '</span><input type="number" min="1" max="100" name="jacana_seo_competitor_random_min" value="' . esc_attr((int) get_option('jacana_seo_competitor_random_min', 6)) . '" /></label>';
      echo '<label><span>' . esc_html__('Random range end', 'jacana-luxe') . '</span><input type="number" min="2" max="100" name="jacana_seo_competitor_random_max" value="' . esc_attr((int) get_option('jacana_seo_competitor_random_max', 20)) . '" /></label>';
      echo '<label><span>' . esc_html__('Random picks', 'jacana-luxe') . '</span><input type="number" min="0" max="2" name="jacana_seo_competitor_random_count" value="' . esc_attr((int) get_option('jacana_seo_competitor_random_count', 2)) . '" /></label>';
      echo '<label><span>' . esc_html__('Max competitor pages', 'jacana-luxe') . '</span><input type="number" min="1" max="5" name="jacana_seo_competitor_max_sites" value="' . esc_attr((int) get_option('jacana_seo_competitor_max_sites', 5)) . '" /></label>';
      echo '<label class="jacana-span-2"><span>' . esc_html__('Tracked keyword targets', 'jacana-luxe') . '</span><textarea name="jacana_seo_target_keywords" rows="6">' . esc_textarea((string) get_option('jacana_seo_target_keywords', '')) . '</textarea></label>';
      echo '<div class="jacana-span-2">';
      submit_button(__('Save automation settings', 'jacana-luxe'), 'secondary', 'submit', false);
      echo '</div></form>';
      echo '<div class="jacana-card-actions">';
      echo '<form method="post" class="jacana-inline-form">';
      wp_nonce_field('jacana_crm_run_seo_sync');
      echo '<input type="hidden" name="jacana_crm_action" value="run_seo_sync" />';
      echo '<button class="button button-primary" type="submit">' . esc_html__('Run data sync now', 'jacana-luxe') . '</button></form>';
      echo '</div>';
      echo '<div class="jacana-admin-note">';
      echo '<strong>' . esc_html__('Competitor sample preview:', 'jacana-luxe') . '</strong> ' . esc_html(implode(', ', $this->get_competitor_sample_positions(8)));
      if (!empty($sync['synced_at'])) {
        echo '<br><strong>' . esc_html__('Last sync:', 'jacana-luxe') . '</strong> ' . esc_html((string) $sync['synced_at']) . ' • ' . esc_html(sprintf(__('%d rows', 'jacana-luxe'), (int) ($sync['rows'] ?? 0)));
      }
      if ($sync_error !== '') {
        echo '<br><strong>' . esc_html__('Last sync error:', 'jacana-luxe') . '</strong> ' . esc_html($sync_error);
      }
      echo '</div>';
      echo '<ul class="jacana-insight-list">';
      echo '<li>' . esc_html__('Competitor intelligence is capped at 5 pages per keyword run.', 'jacana-luxe') . '</li>';
      echo '<li>' . esc_html__('Search Console sync stores query snapshots and exact-match keyword ranking snapshots from your tracked terms.', 'jacana-luxe') . '</li>';
      echo '</ul>';
      echo '</article>';

      echo '<article class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('Phase 3 automation', 'jacana-luxe') . '</h2><span class="jacana-muted">' . esc_html__('Competitors, opportunities, reports', 'jacana-luxe') . '</span></div>';
      echo '<form method="post" action="options.php" class="jacana-settings-grid">';
      settings_fields('jacana_crm_settings');
      echo '<label class="jacana-checkbox"><input type="checkbox" name="jacana_seo_phase3_automation_enabled" value="1" ' . checked((int) get_option('jacana_seo_phase3_automation_enabled', 1), 1, false) . ' /><span>' . esc_html__('Enable automated competitor scans and opportunity generation', 'jacana-luxe') . '</span></label>';
      echo '<label><span>' . esc_html__('Keyword scan limit', 'jacana-luxe') . '</span><input type="number" min="1" max="10" name="jacana_seo_competitor_keyword_limit" value="' . esc_attr((int) get_option('jacana_seo_competitor_keyword_limit', 5)) . '" /></label>';
      echo '<label class="jacana-checkbox"><input type="checkbox" name="jacana_seo_weekly_report_enabled" value="1" ' . checked((int) get_option('jacana_seo_weekly_report_enabled', 1), 1, false) . ' /><span>' . esc_html__('Email the weekly strategic report to recipients', 'jacana-luxe') . '</span></label>';
      echo '<div class="jacana-span-2">';
      submit_button(__('Save Phase 3 settings', 'jacana-luxe'), 'secondary', 'submit', false);
      echo '</div></form>';
      echo '<div class="jacana-card-actions">';
      echo '<form method="post" class="jacana-inline-form">';
      wp_nonce_field('jacana_crm_run_competitor_scan');
      echo '<input type="hidden" name="jacana_crm_action" value="run_competitor_scan" />';
      echo '<button class="button button-primary" type="submit">' . esc_html__('Run competitor scan', 'jacana-luxe') . '</button></form>';
      echo '<form method="post" class="jacana-inline-form">';
      wp_nonce_field('jacana_crm_generate_content_opportunities');
      echo '<input type="hidden" name="jacana_crm_action" value="generate_content_opportunities" />';
      echo '<button class="button" type="submit">' . esc_html__('Refresh opportunities', 'jacana-luxe') . '</button></form>';
      echo '<form method="post" class="jacana-inline-form">';
      wp_nonce_field('jacana_crm_generate_strategic_report');
      echo '<input type="hidden" name="jacana_crm_action" value="generate_strategic_report" />';
      echo '<button class="button" type="submit">' . esc_html__('Generate strategic report', 'jacana-luxe') . '</button></form>';
      echo '</div>';
      echo '<div class="jacana-admin-note">';
      echo '<strong>' . esc_html__('Last competitor scan:', 'jacana-luxe') . '</strong> ' . esc_html((string) ($competitor_summary['ran_at'] ?? '—'));
      echo '<br><strong>' . esc_html__('Last opportunity refresh:', 'jacana-luxe') . '</strong> ' . esc_html((string) ($opportunity_summary['ran_at'] ?? '—'));
      echo '<br><strong>' . esc_html__('Last strategic report:', 'jacana-luxe') . '</strong> ' . esc_html((string) ($report_summary['generated_at'] ?? '—'));
      echo '</div>';
      echo '</article>';
      echo '</section>';
    }

    if ($seo_panel === 'performance') {
      echo '<section class="jacana-admin-grid jacana-admin-grid-main">';
      echo '<article class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('Daily audit summary', 'jacana-luxe') . '</h2><form method="post" class="jacana-inline-form">';
      wp_nonce_field('jacana_crm_run_seo_audit');
      echo '<input type="hidden" name="jacana_crm_action" value="run_seo_audit" />';
      echo '<button class="button button-secondary" type="submit">' . esc_html__('Run now', 'jacana-luxe') . '</button></form></div>';
      if (!empty($audit)) {
        echo '<div class="jacana-kpi-grid">';
        echo '<div class="jacana-kpi"><span>' . esc_html__('Last run', 'jacana-luxe') . '</span><strong>' . esc_html($audit['ran_at'] ?? '—') . '</strong></div>';
        echo '<div class="jacana-kpi"><span>' . esc_html__('Items audited', 'jacana-luxe') . '</span><strong>' . esc_html((int) ($audit['total_items'] ?? 0)) . '</strong></div>';
        echo '<div class="jacana-kpi"><span>' . esc_html__('Missing descriptions', 'jacana-luxe') . '</span><strong>' . esc_html((int) ($audit['missing_descriptions'] ?? 0)) . '</strong></div>';
        echo '<div class="jacana-kpi"><span>' . esc_html__('Low-score items', 'jacana-luxe') . '</span><strong>' . esc_html((int) ($audit['low_score_items'] ?? 0)) . '</strong></div>';
        echo '</div>';
        if (!empty($audit['top_issues'])) {
          echo '<div class="jacana-bar-list">';
          $max_issue = max(array_values((array) $audit['top_issues']));
          foreach ((array) $audit['top_issues'] as $label => $count) {
            $width = $max_issue > 0 ? max(8, round(((int) $count / $max_issue) * 100)) : 0;
            echo '<div class="jacana-bar-item"><div class="jacana-bar-label"><span>' . esc_html($label) . '</span><strong>' . esc_html($count) . '</strong></div><div class="jacana-bar-track"><span style="width:' . esc_attr($width) . '%;"></span></div></div>';
          }
          echo '</div>';
        }
      } else {
        echo '<p class="jacana-muted">' . esc_html__('No audit summary stored yet. Run the audit once to seed the daily baseline.', 'jacana-luxe') . '</p>';
      }
      echo '</article>';

      echo '<article class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('AI queue and ranking snapshot', 'jacana-luxe') . '</h2><span class="jacana-muted">' . esc_html(count($queue)) . ' ' . esc_html__('open items', 'jacana-luxe') . '</span></div>';
      if ($ranking_rows) {
        echo '<table class="widefat striped jacana-admin-table"><thead><tr><th>' . esc_html__('Keyword', 'jacana-luxe') . '</th><th>' . esc_html__('Page', 'jacana-luxe') . '</th><th>' . esc_html__('Impressions', 'jacana-luxe') . '</th><th>' . esc_html__('CTR', 'jacana-luxe') . '</th><th>' . esc_html__('Avg Position', 'jacana-luxe') . '</th></tr></thead><tbody>';
        foreach ($ranking_rows as $row) {
          echo '<tr><td><strong>' . esc_html($row->keyword_text) . '</strong><br><span class="jacana-muted">' . esc_html($row->snapshot_date) . '</span></td><td>' . esc_html(wp_parse_url($row->page_url, PHP_URL_PATH) ?: $row->page_url) . '</td><td>' . esc_html($row->impressions) . '</td><td>' . esc_html(round(((float) $row->ctr) * 100, 2)) . '%</td><td><span class="jacana-score-badge">' . esc_html(round((float) $row->position_avg, 1)) . '</span></td></tr>';
        }
        echo '</tbody></table>';
      } else {
        echo '<p class="jacana-muted">' . esc_html__('No ranking snapshots yet. Run a Search Console sync after adding credentials.', 'jacana-luxe') . '</p>';
      }
      if ($queue) {
        echo '<div class="jacana-activity-list">';
        foreach ($queue as $row) {
          $post_title = get_the_title((int) $row->post_id);
          echo '<div class="jacana-activity-item"><div><strong>' . esc_html($post_title ?: __('Untitled page', 'jacana-luxe')) . '</strong><span>' . esc_html(ucwords(str_replace('_', ' ', (string) $row->field_key))) . ' • ' . esc_html($row->status) . '</span></div><a href="' . esc_url($panel_url('workspace', array('post_id' => (int) $row->post_id))) . '">' . esc_html__('Review', 'jacana-luxe') . '</a></div>';
        }
        echo '</div>';
      }
      echo '</article>';
      echo '</section>';

      echo '<section class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('Search query performance', 'jacana-luxe') . '</h2><span class="jacana-muted">' . esc_html(count($query_rows)) . ' ' . esc_html__('recent rows', 'jacana-luxe') . '</span></div>';
      if ($query_rows) {
        echo '<table class="widefat striped jacana-admin-table"><thead><tr><th>' . esc_html__('Query', 'jacana-luxe') . '</th><th>' . esc_html__('Page', 'jacana-luxe') . '</th><th>' . esc_html__('Clicks', 'jacana-luxe') . '</th><th>' . esc_html__('Impressions', 'jacana-luxe') . '</th><th>' . esc_html__('CTR', 'jacana-luxe') . '</th><th>' . esc_html__('Avg Position', 'jacana-luxe') . '</th></tr></thead><tbody>';
        foreach ($query_rows as $row) {
          echo '<tr><td><strong>' . esc_html($row->query_text) . '</strong><br><span class="jacana-muted">' . esc_html($row->snapshot_date) . '</span></td><td>' . esc_html(wp_parse_url($row->page_url, PHP_URL_PATH) ?: $row->page_url) . '</td><td>' . esc_html($row->clicks) . '</td><td>' . esc_html($row->impressions) . '</td><td>' . esc_html(round(((float) $row->ctr) * 100, 2)) . '%</td><td>' . esc_html(round((float) $row->position_avg, 1)) . '</td></tr>';
        }
        echo '</tbody></table>';
      } else {
        echo '<p class="jacana-muted">' . esc_html__('No Search Console query rows stored yet.', 'jacana-luxe') . '</p>';
      }
      echo '</section>';

      echo '<section class="jacana-admin-grid jacana-admin-grid-main">';
      echo '<article class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('Competitor intelligence', 'jacana-luxe') . '</h2><form method="post" class="jacana-inline-form">';
      wp_nonce_field('jacana_crm_run_competitor_scan');
      echo '<input type="hidden" name="jacana_crm_action" value="run_competitor_scan" />';
      echo '<button class="button button-secondary" type="submit">' . esc_html__('Run scan', 'jacana-luxe') . '</button></form></div>';
      echo '<div class="jacana-detail-list">';
      echo '<div><span>' . esc_html__('Last run', 'jacana-luxe') . '</span><strong>' . esc_html((string) ($competitor_summary['ran_at'] ?? '—')) . '</strong></div>';
      echo '<div><span>' . esc_html__('Keywords scanned', 'jacana-luxe') . '</span><strong>' . esc_html((int) ($competitor_summary['keywords'] ?? 0)) . '</strong></div>';
      echo '<div><span>' . esc_html__('Rows stored', 'jacana-luxe') . '</span><strong>' . esc_html((int) ($competitor_summary['rows'] ?? 0)) . '</strong></div>';
      echo '<div><span>' . esc_html__('Sample cap', 'jacana-luxe') . '</span><strong>' . esc_html((int) get_option('jacana_seo_competitor_max_sites', 5)) . '</strong></div>';
      echo '</div>';
      if ($competitor_rows) {
        echo '<div class="jacana-activity-list">';
        foreach ($competitor_rows as $row) {
          echo '<div class="jacana-activity-item"><div><strong>' . esc_html($row->keyword_text) . ' • #' . esc_html($row->competitor_position) . '</strong><span>' . esc_html($row->competitor_domain) . ' • ' . esc_html($row->snapshot_date) . '</span></div><span class="jacana-muted">' . esc_html(wp_trim_words((string) $row->summary, 12, '...')) . '</span></div>';
        }
        echo '</div>';
      } else {
        echo '<p class="jacana-muted">' . esc_html__('No competitor snapshots stored yet.', 'jacana-luxe') . '</p>';
      }
      echo '</article>';

      echo '<article class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('Content opportunities', 'jacana-luxe') . '</h2><form method="post" class="jacana-inline-form">';
      wp_nonce_field('jacana_crm_generate_content_opportunities');
      echo '<input type="hidden" name="jacana_crm_action" value="generate_content_opportunities" />';
      echo '<button class="button button-secondary" type="submit">' . esc_html__('Refresh', 'jacana-luxe') . '</button></form></div>';
      echo '<div class="jacana-detail-list">';
      echo '<div><span>' . esc_html__('Last refresh', 'jacana-luxe') . '</span><strong>' . esc_html((string) ($opportunity_summary['ran_at'] ?? '—')) . '</strong></div>';
      echo '<div><span>' . esc_html__('Opportunities stored', 'jacana-luxe') . '</span><strong>' . esc_html((int) ($opportunity_summary['rows'] ?? 0)) . '</strong></div>';
      echo '<div><span>' . esc_html__('Questions used', 'jacana-luxe') . '</span><strong>' . esc_html((int) ($opportunity_summary['questions_used'] ?? 0)) . '</strong></div>';
      echo '<div><span>' . esc_html__('Trend headlines', 'jacana-luxe') . '</span><strong>' . esc_html((int) ($opportunity_summary['trend_headlines'] ?? 0)) . '</strong></div>';
      echo '</div>';
      if ($opportunity_rows) {
        echo '<div class="jacana-seo-suggestions-list">';
        foreach ($opportunity_rows as $row) {
          echo '<article class="jacana-seo-suggestion-card">';
          echo '<div class="jacana-card-head"><h3>' . esc_html($row->title) . '</h3><span class="jacana-score-badge">' . esc_html((int) $row->priority_score) . '</span></div>';
          echo '<p class="jacana-muted">' . esc_html($row->opportunity_type) . ' • ' . esc_html($row->target_keyword) . '</p>';
          echo '<p>' . esc_html(wp_trim_words((string) $row->summary, 24, '...')) . '</p>';
          echo '</article>';
        }
        echo '</div>';
      } else {
        echo '<p class="jacana-muted">' . esc_html__('No content opportunities generated yet.', 'jacana-luxe') . '</p>';
      }
      echo '</article>';
      echo '</section>';
    }

    if ($seo_panel === 'reports') {
      echo '<section class="jacana-admin-grid jacana-admin-grid-main">';
      echo '<article class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('Exportable reporting', 'jacana-luxe') . '</h2><span class="jacana-muted">' . esc_html__('Launch handoff ready', 'jacana-luxe') . '</span></div>';
      echo '<div class="jacana-card-actions">';
      echo '<form method="post" class="jacana-inline-form">';
      wp_nonce_field('jacana_crm_export_seo_report');
      echo '<input type="hidden" name="jacana_crm_action" value="export_seo_report_csv" />';
      echo '<button class="button button-primary" type="submit">' . esc_html__('Export CSV', 'jacana-luxe') . '</button></form>';
      echo '<form method="post" class="jacana-inline-form">';
      wp_nonce_field('jacana_crm_export_seo_report');
      echo '<input type="hidden" name="jacana_crm_action" value="export_seo_report_excel" />';
      echo '<button class="button" type="submit">' . esc_html__('Export Excel', 'jacana-luxe') . '</button></form>';
      echo '<form method="post" class="jacana-inline-form" target="_blank">';
      wp_nonce_field('jacana_crm_export_seo_report');
      echo '<input type="hidden" name="jacana_crm_action" value="export_seo_report_print" />';
      echo '<button class="button" type="submit">' . esc_html__('Open PDF / print report', 'jacana-luxe') . '</button></form>';
      echo '</div>';
      echo '<ul class="jacana-insight-list">';
      echo '<li>' . esc_html__('CSV export includes page inventory, audit summary, rankings, and query data.', 'jacana-luxe') . '</li>';
      echo '<li>' . esc_html__('Excel export uses an Excel-compatible `.xls` report for stakeholders.', 'jacana-luxe') . '</li>';
      echo '<li>' . esc_html__('PDF flow is a print-optimized browser report for save-to-PDF or printing.', 'jacana-luxe') . '</li>';
      echo '</ul>';
      echo '</article>';

      echo '<article class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('Report summary', 'jacana-luxe') . '</h2></div>';
      echo '<div class="jacana-detail-list">';
      echo '<div><span>' . esc_html__('Pages in inventory', 'jacana-luxe') . '</span><strong>' . esc_html(count($rows)) . '</strong></div>';
      echo '<div><span>' . esc_html__('Ranking rows', 'jacana-luxe') . '</span><strong>' . esc_html(count($ranking_rows)) . '</strong></div>';
      echo '<div><span>' . esc_html__('Query rows shown', 'jacana-luxe') . '</span><strong>' . esc_html(count($query_rows)) . '</strong></div>';
      echo '<div><span>' . esc_html__('Last sync', 'jacana-luxe') . '</span><strong>' . esc_html(!empty($sync['synced_at']) ? (string) $sync['synced_at'] : '—') . '</strong></div>';
      echo '</div>';
      if ($sync_error !== '') {
        echo '<p class="jacana-muted">' . esc_html__('Last sync error: ', 'jacana-luxe') . esc_html($sync_error) . '</p>';
      }
      echo '</article>';
      echo '</section>';

      echo '<section class="jacana-admin-grid jacana-admin-grid-main">';
      echo '<article class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('Strategic SEO report', 'jacana-luxe') . '</h2><form method="post" class="jacana-inline-form">';
      wp_nonce_field('jacana_crm_generate_strategic_report');
      echo '<input type="hidden" name="jacana_crm_action" value="generate_strategic_report" />';
      echo '<button class="button button-primary" type="submit">' . esc_html__('Generate now', 'jacana-luxe') . '</button></form></div>';
      if ($latest_report) {
        echo '<p class="jacana-muted">' . esc_html($latest_report->created_at) . '</p>';
        echo '<p>' . esc_html((string) $latest_report->summary) . '</p>';
        echo '<div class="jacana-admin-note jacana-report-body">' . nl2br(esc_html(wp_trim_words((string) $latest_report->report_body, 220, '...'))) . '</div>';
      } else {
        echo '<p class="jacana-muted">' . esc_html__('No strategic report has been generated yet.', 'jacana-luxe') . '</p>';
      }
      echo '</article>';

      echo '<article class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('Phase 3 coverage', 'jacana-luxe') . '</h2></div>';
      echo '<ul class="jacana-insight-list">';
      echo '<li>' . esc_html(sprintf(__('Latest competitor rows available: %d', 'jacana-luxe'), count($competitor_rows))) . '</li>';
      echo '<li>' . esc_html(sprintf(__('Latest opportunity records available: %d', 'jacana-luxe'), count($opportunity_rows))) . '</li>';
      echo '<li>' . esc_html(sprintf(__('Weekly report email recipients configured: %s', 'jacana-luxe'), get_option('jacana_seo_report_email_recipients', get_option('admin_email')))) . '</li>';
      echo '<li>' . esc_html__('Trend-aware opportunity generation uses live news headlines plus CRM and Search Console data.', 'jacana-luxe') . '</li>';
      echo '</ul>';
      echo '</article>';
      echo '</section>';
    }

    if ($seo_panel === 'pages') {
      echo '<section class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('Page inventory', 'jacana-luxe') . '</h2><span class="jacana-muted">' . esc_html(count($rows)) . ' ' . esc_html__('items', 'jacana-luxe') . '</span></div>';
      echo '<form method="get" class="jacana-filter-bar">';
      echo '<input type="hidden" name="page" value="jacana-crm-seo" />';
      echo '<input type="hidden" name="seo_panel" value="pages" />';
      echo '<input type="search" name="seo_s" value="' . esc_attr((string) ($_GET['seo_s'] ?? '')) . '" placeholder="' . esc_attr__('Search titles, URLs, content...', 'jacana-luxe') . '" />';
      echo '<select name="seo_type"><option value="">' . esc_html__('All content types', 'jacana-luxe') . '</option>';
      foreach (array_values(array_unique(array_map(static function ($row) {
        return (string) ($row['type'] ?? '');
      }, $rows))) as $type) {
        if ($type === '') {
          continue;
        }
        echo '<option value="' . esc_attr($type) . '"' . selected((string) ($_GET['seo_type'] ?? ''), $type, false) . '>' . esc_html($type) . '</option>';
      }
      echo '</select>';
      echo '<button class="button button-primary" type="submit">' . esc_html__('Filter', 'jacana-luxe') . '</button>';
      echo '<a class="button" href="' . esc_url($panel_url('pages')) . '">' . esc_html__('Reset', 'jacana-luxe') . '</a>';
      echo '</form>';
      echo '<table class="widefat striped jacana-admin-table"><thead><tr><th>' . esc_html__('Page', 'jacana-luxe') . '</th><th>' . esc_html__('Score', 'jacana-luxe') . '</th><th>' . esc_html__('Yoast', 'jacana-luxe') . '</th><th>' . esc_html__('Excerpt', 'jacana-luxe') . '</th><th>' . esc_html__('Image', 'jacana-luxe') . '</th><th>' . esc_html__('Words', 'jacana-luxe') . '</th><th>' . esc_html__('Issues', 'jacana-luxe') . '</th></tr></thead><tbody>';
      foreach ($rows as $row) {
        $thumb = !empty($row['featured_image']) ? '<img class="jacana-page-thumb" src="' . esc_url($row['featured_image']) . '" alt="" />' : '<span class="jacana-page-thumb jacana-page-thumb-empty">—</span>';
        echo '<tr>';
        echo '<td><div class="jacana-page-title-cell">' . $thumb . '<div><strong><a href="' . esc_url($panel_url('workspace', array('post_id' => $row['id']))) . '">' . esc_html($row['title']) . '</a></strong><br><span class="jacana-muted">' . esc_html(wp_parse_url($row['permalink'], PHP_URL_PATH) ?: $row['permalink']) . '</span><br><span class="jacana-muted">' . esc_html($row['type']) . ' • ' . esc_html($row['status']) . '</span></div></div></td>';
        echo '<td><span class="jacana-score-badge">' . esc_html((int) $row['seo_score']) . '</span></td>';
        echo '<td>' . ($yoast_active ? '<span class="jacana-pill-ok">' . esc_html__('Connected', 'jacana-luxe') . '</span>' : '<span class="jacana-pill-warn">' . esc_html__('Fallback only', 'jacana-luxe') . '</span>') . '</td>';
        echo '<td>' . ($row['excerpt'] !== '' ? '<span class="jacana-pill-ok">' . esc_html__('Ready', 'jacana-luxe') . '</span>' : '<span class="jacana-pill-warn">' . esc_html__('Needs snippet', 'jacana-luxe') . '</span>') . '</td>';
        echo '<td>' . ($row['has_image'] ? '<span class="jacana-pill-ok">' . esc_html__('Ready', 'jacana-luxe') . '</span>' : '<span class="jacana-pill-warn">' . esc_html__('Missing', 'jacana-luxe') . '</span>') . '</td>';
        echo '<td>' . esc_html((int) $row['word_count']) . '</td>';
        echo '<td><span class="jacana-muted">' . esc_html(implode('; ', array_slice((array) $row['issues'], 0, 2))) . '</span></td>';
        echo '</tr>';
      }
      echo '</tbody></table></section>';
    }

    if ($seo_panel === 'workspace') {
      if (!empty($selected_state)) {
        $preview_title = !empty($suggested_fields['seo_title']->suggested_value) ? $suggested_fields['seo_title']->suggested_value : $selected_state['current']['seo_title'];
        $preview_desc = !empty($suggested_fields['meta_description']->suggested_value) ? $suggested_fields['meta_description']->suggested_value : $selected_state['current']['meta_description'];
        $preview_og_title = !empty($suggested_fields['og_title']->suggested_value) ? $suggested_fields['og_title']->suggested_value : $selected_state['current']['og_title'];
        $preview_og_desc = !empty($suggested_fields['og_description']->suggested_value) ? $suggested_fields['og_description']->suggested_value : $selected_state['current']['og_description'];

        echo '<section class="jacana-admin-grid jacana-admin-grid-main jacana-admin-grid-main-seo-detail">';
        echo '<article class="jacana-admin-card jacana-seo-workspace-card">';
        echo '<div class="jacana-card-head"><h2>' . esc_html__('SEO workspace', 'jacana-luxe') . '</h2><a class="button" href="' . esc_url($selected_state['edit_link']) . '">' . esc_html__('Edit content', 'jacana-luxe') . '</a></div>';
        echo '<div class="jacana-detail-list">';
        echo '<div><span>' . esc_html__('Page', 'jacana-luxe') . '</span><strong>' . esc_html($selected_state['title']) . '</strong></div>';
        echo '<div><span>' . esc_html__('URL', 'jacana-luxe') . '</span><strong>' . esc_html(wp_parse_url($selected_state['permalink'], PHP_URL_PATH) ?: $selected_state['permalink']) . '</strong></div>';
        echo '<div><span>' . esc_html__('SEO score', 'jacana-luxe') . '</span><strong>' . esc_html((int) $selected_state['seo_score']) . '/100</strong></div>';
        echo '<div><span>' . esc_html__('Internal links', 'jacana-luxe') . '</span><strong>' . esc_html((int) $selected_state['internal_links']) . '</strong></div>';
        echo '</div>';
        echo '<div class="jacana-seo-current-grid">';
        foreach (array(
          'seo_title' => __('SEO title', 'jacana-luxe'),
          'meta_description' => __('Meta description', 'jacana-luxe'),
          'focus_keyphrase' => __('Focus keyphrase', 'jacana-luxe'),
          'og_title' => __('Open Graph title', 'jacana-luxe'),
          'og_description' => __('Open Graph description', 'jacana-luxe'),
          'excerpt' => __('Excerpt / snippet', 'jacana-luxe'),
        ) as $field => $label) {
          echo '<div class="jacana-seo-field-card"><span>' . esc_html($label) . '</span><strong>' . esc_html($selected_state['current'][$field] ?: '—') . '</strong></div>';
        }
        echo '</div>';
        echo '<form method="post" class="jacana-inline-form">';
        wp_nonce_field('jacana_crm_generate_seo');
        echo '<input type="hidden" name="jacana_crm_action" value="generate_seo_suggestions" />';
        echo '<input type="hidden" name="post_id" value="' . esc_attr($selected_state['id']) . '" />';
        echo '<button class="button button-primary" type="submit">' . esc_html__('Generate AI suggestions', 'jacana-luxe') . '</button>';
        echo '</form>';
        if ($widget_blocks) {
          echo '<form method="post" class="jacana-inline-form" style="margin-top:12px;">';
          wp_nonce_field('jacana_crm_generate_widget_suggestions');
          echo '<input type="hidden" name="jacana_crm_action" value="generate_widget_suggestions" />';
          echo '<input type="hidden" name="post_id" value="' . esc_attr($selected_state['id']) . '" />';
          echo '<button class="button button-secondary" type="submit">' . esc_html__('Generate widget content suggestions', 'jacana-luxe') . '</button>';
          echo '</form>';
        }
        echo '<details class="jacana-collapsible"><summary>' . esc_html__('View page content', 'jacana-luxe') . '</summary><div class="jacana-ai-output">' . esc_html(wp_trim_words($selected_state['content'], 900, '')) . '</div></details>';
        if ($widget_blocks) {
          echo '<details class="jacana-collapsible" open><summary>' . esc_html__('Widget content blocks', 'jacana-luxe') . '</summary><div class="jacana-seo-suggestions-list">';
          foreach ($widget_blocks as $block) {
            echo '<article class="jacana-seo-suggestion-card">';
            echo '<div class="jacana-card-head"><h3>' . esc_html((string) $block['widget_label']) . '</h3><span class="jacana-muted">' . esc_html((string) $block['widget_type']) . '</span></div>';
            echo '<div class="jacana-seo-diff-grid">';
            foreach (array_slice((array) $block['fields'], 0, 8) as $field) {
              echo '<div><span>' . esc_html((string) $field['label']) . '</span><p>' . esc_html((string) $field['value']) . '</p></div>';
            }
            echo '</div>';
            if (count((array) $block['fields']) > 8) {
              echo '<p class="jacana-muted">' . esc_html(sprintf(__('Plus %d more text fields in this widget.', 'jacana-luxe'), count((array) $block['fields']) - 8)) . '</p>';
            }
            echo '</article>';
          }
          echo '</div></details>';
        }
        echo '</article>';

        echo '<article class="jacana-admin-card">';
        echo '<div class="jacana-card-head"><h2>' . esc_html__('Search and social previews', 'jacana-luxe') . '</h2></div>';
        echo '<div class="jacana-seo-previews">';
        echo '<div class="jacana-serp-preview"><span class="jacana-preview-label">' . esc_html__('Google preview', 'jacana-luxe') . '</span><strong>' . esc_html($preview_title) . '</strong><em>' . esc_html($selected_state['permalink']) . '</em><p>' . esc_html($preview_desc) . '</p></div>';
        echo '<div class="jacana-social-preview">';
        if (!empty($selected_state['featured_image'])) {
          echo '<img src="' . esc_url($selected_state['featured_image']) . '" alt="" />';
        }
        echo '<div><span class="jacana-preview-label">' . esc_html__('Social preview', 'jacana-luxe') . '</span><strong>' . esc_html($preview_og_title) . '</strong><p>' . esc_html($preview_og_desc) . '</p></div></div>';
        echo '</div>';
        if ($seo_field_suggestions || $widget_suggestions) {
          echo '<div class="jacana-card-head"><h2>' . esc_html__('AI suggestions for this page', 'jacana-luxe') . '</h2><form method="post" class="jacana-inline-form">';
          wp_nonce_field('jacana_crm_apply_approved_seo');
          echo '<input type="hidden" name="jacana_crm_action" value="seo_apply_approved" />';
          echo '<input type="hidden" name="post_id" value="' . esc_attr($selected_state['id']) . '" />';
          echo '<button class="button button-secondary" type="submit">' . esc_html__('Apply approved suggestions', 'jacana-luxe') . '</button></form></div>';
          echo '<div class="jacana-seo-suggestions-list">';
          foreach (array_merge($seo_field_suggestions, $widget_suggestions) as $suggestion) {
            $can_apply = $this->can_apply_suggestion_field((string) $suggestion->field_key);
            echo '<article class="jacana-seo-suggestion-card">';
            echo '<div class="jacana-card-head"><h3>' . esc_html($this->format_suggestion_label((string) $suggestion->field_key)) . '</h3><span class="jacana-status-pill jacana-status-' . esc_attr(sanitize_html_class((string) $suggestion->status)) . '">' . esc_html((string) $suggestion->status) . '</span></div>';
            echo '<div class="jacana-seo-diff-grid"><div><span>' . esc_html__('Current', 'jacana-luxe') . '</span><p>' . esc_html((string) $suggestion->current_value ?: '—') . '</p></div><div><span>' . esc_html__('Suggested', 'jacana-luxe') . '</span><p>' . esc_html((string) $suggestion->suggested_value) . '</p></div></div>';
            if (!empty($suggestion->notes)) {
              echo '<p class="jacana-muted">' . esc_html((string) $suggestion->notes) . '</p>';
            }
            echo '<div class="jacana-card-actions">';
            if ($suggestion->status !== 'approved' && $suggestion->status !== 'applied') {
              echo '<form method="post" class="jacana-inline-form">';
              wp_nonce_field('jacana_crm_update_seo_suggestion');
              echo '<input type="hidden" name="jacana_crm_action" value="seo_update_suggestion" />';
              echo '<input type="hidden" name="post_id" value="' . esc_attr($selected_state['id']) . '" />';
              echo '<input type="hidden" name="suggestion_id" value="' . esc_attr((int) $suggestion->id) . '" />';
              echo '<input type="hidden" name="suggestion_status" value="approved" />';
              echo '<button class="button button-primary" type="submit">' . esc_html__('Approve', 'jacana-luxe') . '</button></form>';
            }
            if ($suggestion->status !== 'rejected' && $suggestion->status !== 'applied') {
              echo '<form method="post" class="jacana-inline-form">';
              wp_nonce_field('jacana_crm_update_seo_suggestion');
              echo '<input type="hidden" name="jacana_crm_action" value="seo_update_suggestion" />';
              echo '<input type="hidden" name="post_id" value="' . esc_attr($selected_state['id']) . '" />';
              echo '<input type="hidden" name="suggestion_id" value="' . esc_attr((int) $suggestion->id) . '" />';
              echo '<input type="hidden" name="suggestion_status" value="rejected" />';
              echo '<button class="button" type="submit">' . esc_html__('Reject', 'jacana-luxe') . '</button></form>';
            }
            if ($can_apply && $suggestion->status !== 'applied') {
              echo '<form method="post" class="jacana-inline-form">';
              wp_nonce_field('jacana_crm_apply_seo_suggestion');
              echo '<input type="hidden" name="jacana_crm_action" value="seo_apply_suggestion" />';
              echo '<input type="hidden" name="post_id" value="' . esc_attr($selected_state['id']) . '" />';
              echo '<input type="hidden" name="suggestion_id" value="' . esc_attr((int) $suggestion->id) . '" />';
              echo '<button class="button button-secondary" type="submit">' . esc_html__('Apply to Yoast/page', 'jacana-luxe') . '</button></form>';
            }
            echo '</div></article>';
          }
          echo '</div>';
        } else {
          echo '<p class="jacana-muted">' . esc_html__('No AI suggestions yet for this page. Generate them from the workspace card.', 'jacana-luxe') . '</p>';
        }
        echo '</article>';
        echo '</section>';
      } else {
        echo '<section class="jacana-admin-card jacana-seo-empty-state">';
        echo '<h2>' . esc_html__('Select a page to open the workspace', 'jacana-luxe') . '</h2>';
        echo '<p>' . esc_html__('Use the page inventory to choose a page, then generate and apply SEO suggestions without leaving the studio.', 'jacana-luxe') . '</p>';
        echo '<div class="jacana-card-actions"><a class="button button-primary" href="' . esc_url($panel_url('pages')) . '">' . esc_html__('Browse pages', 'jacana-luxe') . '</a></div>';
        echo '</section>';
      }
    }

    $this->render_admin_shell_end();
  }

  public function render_ai_lab_page()
  {
    $output = '';
    $mode = sanitize_key((string) ($_POST['jacana_ai_mode'] ?? ''));
    if (!empty($_POST['jacana_ai_lab_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['jacana_ai_lab_nonce'])), 'jacana_ai_lab')) {
      $context = sanitize_textarea_field((string) ($_POST['jacana_ai_context'] ?? ''));
      $brand_context = $this->get_brand_context_text();
      $metrics = $this->get_dashboard_metrics();
      $top_services = $this->get_top_services();
      $service_labels = implode(', ', array_map(static function ($row) {
        return (string) ($row->label ?? '');
      }, (array) $top_services));
      $prompt = '';

      if ($mode === 'conversion') {
        $prompt = "You are a premium safari travel conversion strategist.\nReturn a concise launch-ready report with sections: Quick diagnosis, Top 5 fixes, CTA copy ideas, Smart prompt ideas.\nBrand context:\n{$brand_context}\nMetrics:\n" . wp_json_encode($metrics) . "\nTop services: {$service_labels}\nExtra context: {$context}";
      } elseif ($mode === 'seo') {
        $prompt = "You are an SEO strategist for a Namibia safari travel company.\nReturn a concise report with sections: Meta description ideas, FAQ opportunities, Internal link ideas, Pages to tighten before launch.\nBrand context:\n{$brand_context}\nPages audit summary count: " . count($this->get_page_audit_rows()) . "\nExtra context: {$context}";
      } elseif ($mode === 'faq') {
        $prompt = "You are writing premium travel FAQs for Jacana Safaris & Tours.\nReturn 8 FAQ ideas with short answers focused on visitor trust and conversion. Use plain text.\nBrand context:\n{$brand_context}\nPriority services: {$service_labels}\nExtra context: {$context}";
      } elseif ($mode === 'copy') {
        $prompt = "You are a luxury travel conversion copywriter.\nReturn: 6 CTA lines, 4 hero subtitle lines, 4 booking reassurance lines. Keep them elegant and specific to Namibia travel.\nBrand context:\n{$brand_context}\nExtra context: {$context}";
      }

      if ($prompt !== '') {
        $result = $this->call_gemini_text($prompt, false);
        $output = is_wp_error($result) ? $result->get_error_message() : (string) $result;
      }
    }

    $this->render_admin_shell_start(
      __('AI Workbench', 'jacana-luxe'),
      __('AI operations', 'jacana-luxe'),
      __('Generate conversion ideas, launch copy, FAQ expansions, and SEO improvements from the CRM data already flowing through the site.', 'jacana-luxe')
    );

    echo '<section class="jacana-admin-grid jacana-admin-grid-main">';
    echo '<article class="jacana-admin-card">';
    echo '<h2>' . esc_html__('Generate AI recommendations', 'jacana-luxe') . '</h2>';
    echo '<form method="post" class="jacana-settings-grid">';
    wp_nonce_field('jacana_ai_lab', 'jacana_ai_lab_nonce');
    echo '<label><span>' . esc_html__('Mode', 'jacana-luxe') . '</span><select name="jacana_ai_mode">';
    $modes = array(
      'conversion' => __('Conversion sprint', 'jacana-luxe'),
      'seo' => __('SEO launch polish', 'jacana-luxe'),
      'faq' => __('New FAQ ideas', 'jacana-luxe'),
      'copy' => __('CTA and page copy', 'jacana-luxe'),
    );
    foreach ($modes as $value => $label) {
      echo '<option value="' . esc_attr($value) . '"' . selected($mode, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></label>';
    echo '<label class="jacana-span-2"><span>' . esc_html__('Context / page notes', 'jacana-luxe') . '</span><textarea name="jacana_ai_context" rows="8" placeholder="' . esc_attr__('Example: optimize the booking page and highlights map for launch. Focus on car rental, game drive, and trust-building.', 'jacana-luxe') . '">' . esc_textarea((string) ($_POST['jacana_ai_context'] ?? '')) . '</textarea></label>';
    echo '<div class="jacana-span-2">';
    submit_button(__('Generate with Gemini', 'jacana-luxe'), 'primary', 'submit', false);
    echo '</div></form>';
    echo '</article>';

    echo '<article class="jacana-admin-card">';
    echo '<h2>' . esc_html__('What this can do today', 'jacana-luxe') . '</h2>';
    echo '<ul class="jacana-insight-list">';
    echo '<li>' . esc_html__('Generate launch-focused CTA copy for service, booking, car rental, and gallery pages.', 'jacana-luxe') . '</li>';
    echo '<li>' . esc_html__('Create SEO tightening recommendations using your current page audit and CRM demand signals.', 'jacana-luxe') . '</li>';
    echo '<li>' . esc_html__('Propose new FAQ entries that reduce travel friction and answer purchase-blocking questions.', 'jacana-luxe') . '</li>';
    echo '<li>' . esc_html__('Suggest smarter on-site prompts and messaging based on visitor behavior data.', 'jacana-luxe') . '</li>';
    echo '</ul>';
    echo '</article>';
    echo '</section>';

    if ($output !== '') {
      echo '<section class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('AI output', 'jacana-luxe') . '</h2></div>';
      echo '<pre class="jacana-ai-output">' . esc_html($output) . '</pre>';
      echo '</section>';
    }

    $this->render_admin_shell_end();
  }

  private function mask_secret_preview($value)
  {
    $value = (string) $value;
    $len = strlen($value);
    if ($len <= 8) {
      return str_repeat('*', max(0, $len));
    }
    return substr($value, 0, 4) . str_repeat('*', max(0, $len - 8)) . substr($value, -4);
  }

  private function get_ai_debug_snapshot()
  {
    $api_key = sanitize_text_field((string) get_option('jacana_gemini_api_key', ''));
    $models = $this->get_gemini_models_to_try();

    $routes = rest_get_server()->get_routes();
    $has_ai_route = isset($routes['/jacana/v1/ai']);
    $has_chat_route = isset($routes['/jacana/v1/chat']);

    $route_probe_code = 0;
    $route_probe_msg = '';
    $route_probe = rest_do_request(new WP_REST_Request('POST', '/jacana/v1/ai'));
    if ($route_probe instanceof WP_REST_Response) {
      $route_probe_code = (int) $route_probe->get_status();
      $probe_data = (array) $route_probe->get_data();
      $route_probe_msg = (string) ($probe_data['error'] ?? '');
    }

    $outbound_probe = wp_remote_get('https://generativelanguage.googleapis.com', array(
      'timeout' => 12,
      'redirection' => 1,
    ));
    $outbound_status = '';
    if (is_wp_error($outbound_probe)) {
      $outbound_status = 'WP_Error: ' . $outbound_probe->get_error_message();
    } else {
      $outbound_status = 'HTTP ' . (int) wp_remote_retrieve_response_code($outbound_probe);
    }

    return array(
      'site_url' => home_url('/'),
      'wp_version' => get_bloginfo('version'),
      'php_version' => PHP_VERSION,
      'ssl_http_supported' => wp_http_supports(array('ssl' => true)) ? 'yes' : 'no',
      'openssl_loaded' => extension_loaded('openssl') ? 'yes' : 'no',
      'curl_loaded' => extension_loaded('curl') ? 'yes' : 'no',
      'api_key_present' => $api_key !== '' ? 'yes' : 'no',
      'api_key_preview' => $api_key !== '' ? $this->mask_secret_preview($api_key) : 'not set',
      'configured_model' => sanitize_text_field((string) get_option('jacana_gemini_model', 'gemini-3.1-pro-preview')),
      'models_to_try' => implode(', ', $models),
      'has_ai_route' => $has_ai_route ? 'yes' : 'no',
      'has_chat_route' => $has_chat_route ? 'yes' : 'no',
      'route_probe' => 'HTTP ' . $route_probe_code . ($route_probe_msg !== '' ? ' (' . $route_probe_msg . ')' : ''),
      'outbound_probe' => $outbound_status,
    );
  }

  public function render_ai_debug_page()
  {
    $snapshot = $this->get_ai_debug_snapshot();
    $test_prompt = '';
    $test_json_mode = 1;
    $test_result = null;
    $test_error = '';
    $attempts = array();

    if (!empty($_POST['jacana_ai_debug_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['jacana_ai_debug_nonce'])), 'jacana_ai_debug')) {
      $test_prompt = sanitize_textarea_field((string) ($_POST['jacana_ai_test_prompt'] ?? ''));
      $test_json_mode = !empty($_POST['jacana_ai_test_json']) ? 1 : 0;

      if ($test_prompt === '') {
        $test_error = __('Please enter a prompt before running the AI test.', 'jacana-luxe');
      } else {
        $started = microtime(true);
        $result = $this->execute_gemini_request($test_prompt, (bool) $test_json_mode, true);
        $elapsed_ms = (int) round((microtime(true) - $started) * 1000);
        if (is_wp_error($result)) {
          $debug = (array) $result->get_error_data();
          $attempts = (array) ($debug['attempts'] ?? array());
          $test_error = $result->get_error_message();
          $test_result = array(
            'ok' => false,
            'elapsed_ms' => $elapsed_ms,
            'last_error' => (string) ($debug['last_error'] ?? ''),
            'last_body' => (string) ($debug['last_body'] ?? ''),
          );
        } else {
          $attempts = (array) ($result['attempts'] ?? array());
          $test_result = array(
            'ok' => true,
            'elapsed_ms' => $elapsed_ms,
            'model' => (string) ($result['model'] ?? ''),
            'text' => (string) ($result['text'] ?? ''),
            'raw' => wp_json_encode((array) ($result['data'] ?? array()), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
          );
        }
      }
    }

    $this->render_admin_shell_start(
      __('AI Debug', 'jacana-luxe'),
      __('Diagnostics', 'jacana-luxe'),
      __('Debug Gemini connectivity, route availability, and model responses directly from CRM on staging or production.', 'jacana-luxe')
    );

    echo '<section class="jacana-admin-grid jacana-admin-grid-kpis">';
    echo '<article class="jacana-admin-card jacana-kpi-card"><span>' . esc_html__('Gemini key present', 'jacana-luxe') . '</span><strong>' . esc_html((string) $snapshot['api_key_present']) . '</strong></article>';
    echo '<article class="jacana-admin-card jacana-kpi-card"><span>' . esc_html__('AI route registered', 'jacana-luxe') . '</span><strong>' . esc_html((string) $snapshot['has_ai_route']) . '</strong></article>';
    echo '<article class="jacana-admin-card jacana-kpi-card"><span>' . esc_html__('Outbound probe', 'jacana-luxe') . '</span><strong>' . esc_html((string) $snapshot['outbound_probe']) . '</strong></article>';
    echo '</section>';

    echo '<section class="jacana-admin-grid jacana-admin-grid-main">';
    echo '<article class="jacana-admin-card">';
    echo '<h2>' . esc_html__('Runtime checks', 'jacana-luxe') . '</h2>';
    echo '<ul class="jacana-insight-list">';
    echo '<li><strong>' . esc_html__('Site URL:', 'jacana-luxe') . '</strong> ' . esc_html((string) $snapshot['site_url']) . '</li>';
    echo '<li><strong>' . esc_html__('WordPress / PHP:', 'jacana-luxe') . '</strong> ' . esc_html((string) $snapshot['wp_version'] . ' / ' . (string) $snapshot['php_version']) . '</li>';
    echo '<li><strong>' . esc_html__('SSL HTTP support:', 'jacana-luxe') . '</strong> ' . esc_html((string) $snapshot['ssl_http_supported']) . '</li>';
    echo '<li><strong>' . esc_html__('OpenSSL / cURL loaded:', 'jacana-luxe') . '</strong> ' . esc_html((string) $snapshot['openssl_loaded'] . ' / ' . (string) $snapshot['curl_loaded']) . '</li>';
    echo '<li><strong>' . esc_html__('Gemini model option:', 'jacana-luxe') . '</strong> ' . esc_html((string) $snapshot['configured_model']) . '</li>';
    echo '<li><strong>' . esc_html__('Models to try:', 'jacana-luxe') . '</strong> ' . esc_html((string) $snapshot['models_to_try']) . '</li>';
    echo '<li><strong>' . esc_html__('API key preview:', 'jacana-luxe') . '</strong> ' . esc_html((string) $snapshot['api_key_preview']) . '</li>';
    echo '<li><strong>' . esc_html__('Route probe:', 'jacana-luxe') . '</strong> ' . esc_html((string) $snapshot['route_probe']) . '</li>';
    echo '</ul>';
    echo '</article>';

    echo '<article class="jacana-admin-card">';
    echo '<h2>' . esc_html__('AI test runner', 'jacana-luxe') . '</h2>';
    echo '<form method="post" class="jacana-settings-grid">';
    wp_nonce_field('jacana_ai_debug', 'jacana_ai_debug_nonce');
    echo '<label class="jacana-span-2"><span>' . esc_html__('Prompt', 'jacana-luxe') . '</span><textarea name="jacana_ai_test_prompt" rows="6" placeholder="' . esc_attr__('Write a short test prompt, for example: Return JSON with greeting and one itinerary tip for Namibia.', 'jacana-luxe') . '">' . esc_textarea($test_prompt) . '</textarea></label>';
    echo '<label class="jacana-checkbox"><input type="checkbox" name="jacana_ai_test_json" value="1" ' . checked($test_json_mode, 1, false) . ' /><span>' . esc_html__('Request JSON mode', 'jacana-luxe') . '</span></label>';
    echo '<div class="jacana-span-2">';
    submit_button(__('Run AI test', 'jacana-luxe'), 'primary', 'submit', false);
    echo '</div>';
    echo '</form>';
    echo '</article>';
    echo '</section>';

    if ($test_error !== '') {
      echo '<section class="jacana-admin-card"><p class="jacana-muted" style="color:#a02a2a;">' . esc_html($test_error) . '</p></section>';
    }

    if (is_array($test_result)) {
      echo '<section class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('AI test result', 'jacana-luxe') . '</h2></div>';
      echo '<ul class="jacana-insight-list">';
      echo '<li><strong>' . esc_html__('Success:', 'jacana-luxe') . '</strong> ' . esc_html(!empty($test_result['ok']) ? 'yes' : 'no') . '</li>';
      echo '<li><strong>' . esc_html__('Elapsed:', 'jacana-luxe') . '</strong> ' . esc_html((string) ($test_result['elapsed_ms'] ?? 0)) . ' ms</li>';
      if (!empty($test_result['model'])) {
        echo '<li><strong>' . esc_html__('Model:', 'jacana-luxe') . '</strong> ' . esc_html((string) $test_result['model']) . '</li>';
      }
      if (!empty($test_result['last_error'])) {
        echo '<li><strong>' . esc_html__('Last error:', 'jacana-luxe') . '</strong> ' . esc_html((string) $test_result['last_error']) . '</li>';
      }
      echo '</ul>';
      if (!empty($test_result['text'])) {
        echo '<h3>' . esc_html__('Text output', 'jacana-luxe') . '</h3>';
        echo '<pre class="jacana-ai-output">' . esc_html((string) $test_result['text']) . '</pre>';
      }
      if (!empty($test_result['raw'])) {
        echo '<h3>' . esc_html__('Raw response data', 'jacana-luxe') . '</h3>';
        echo '<pre class="jacana-ai-output">' . esc_html((string) $test_result['raw']) . '</pre>';
      }
      if (!empty($test_result['last_body'])) {
        echo '<h3>' . esc_html__('Last response body excerpt', 'jacana-luxe') . '</h3>';
        echo '<pre class="jacana-ai-output">' . esc_html((string) $test_result['last_body']) . '</pre>';
      }
      echo '</section>';
    }

    if (!empty($attempts)) {
      echo '<section class="jacana-admin-card">';
      echo '<div class="jacana-card-head"><h2>' . esc_html__('Per-model attempts', 'jacana-luxe') . '</h2></div>';
      echo '<table class="widefat striped jacana-admin-table"><thead><tr><th>' . esc_html__('Model', 'jacana-luxe') . '</th><th>' . esc_html__('HTTP', 'jacana-luxe') . '</th><th>' . esc_html__('Error', 'jacana-luxe') . '</th></tr></thead><tbody>';
      foreach ($attempts as $attempt) {
        echo '<tr>';
        echo '<td>' . esc_html((string) ($attempt['model'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($attempt['status'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($attempt['error'] ?? '')) . '</td>';
        echo '</tr>';
      }
      echo '</tbody></table>';
      echo '</section>';
    }

    $frontend_errors = $this->get_recent_frontend_ai_errors(120);
    echo '<section class="jacana-admin-card">';
    echo '<div class="jacana-card-head"><h2>' . esc_html__('Frontend AI / Chatbot Errors', 'jacana-luxe') . '</h2></div>';
    if (empty($frontend_errors)) {
      echo '<p class="jacana-muted">' . esc_html__('No frontend AI errors have been logged yet. Trigger an AI/chat action on the site, then refresh this page.', 'jacana-luxe') . '</p>';
    } else {
      echo '<table class="widefat striped jacana-admin-table"><thead><tr>';
      echo '<th>' . esc_html__('Time', 'jacana-luxe') . '</th>';
      echo '<th>' . esc_html__('Source', 'jacana-luxe') . '</th>';
      echo '<th>' . esc_html__('Message', 'jacana-luxe') . '</th>';
      echo '<th>' . esc_html__('Endpoint', 'jacana-luxe') . '</th>';
      echo '<th>' . esc_html__('Status', 'jacana-luxe') . '</th>';
      echo '<th>' . esc_html__('Page URL', 'jacana-luxe') . '</th>';
      echo '<th>' . esc_html__('Visitor/Session', 'jacana-luxe') . '</th>';
      echo '<th>' . esc_html__('Meta', 'jacana-luxe') . '</th>';
      echo '</tr></thead><tbody>';
      foreach ($frontend_errors as $row) {
        $payload = json_decode((string) ($row->payload ?? ''), true);
        if (!is_array($payload)) {
          $payload = array();
        }
        $source = sanitize_text_field((string) ($payload['source'] ?? 'frontend'));
        $message = sanitize_text_field((string) ($payload['message'] ?? 'error'));
        $endpoint = esc_url_raw((string) ($payload['endpoint'] ?? ''));
        $status = absint($payload['status'] ?? 0);
        $page_url = esc_url_raw((string) ($payload['page_url'] ?? ''));
        $meta = isset($payload['meta']) && is_array($payload['meta']) ? $payload['meta'] : array();
        $meta_text = $meta ? wp_json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        if (is_string($meta_text) && strlen($meta_text) > 220) {
          $meta_text = substr($meta_text, 0, 220) . '...';
        }
        $identity = trim((string) ($row->visitor_key ?? '')) . ' / ' . trim((string) ($row->session_key ?? ''));

        echo '<tr>';
        echo '<td>' . esc_html((string) ($row->created_at ?? '')) . '</td>';
        echo '<td>' . esc_html($source) . '</td>';
        echo '<td>' . esc_html($message) . '</td>';
        echo '<td><code>' . esc_html($endpoint !== '' ? $endpoint : '-') . '</code></td>';
        echo '<td>' . esc_html($status > 0 ? (string) $status : '-') . '</td>';
        echo '<td><code>' . esc_html($page_url !== '' ? $page_url : '-') . '</code></td>';
        echo '<td><code>' . esc_html(trim($identity, ' /')) . '</code></td>';
        echo '<td><code>' . esc_html($meta_text !== '' ? $meta_text : '-') . '</code></td>';
        echo '</tr>';
      }
      echo '</tbody></table>';
    }
    echo '</section>';

    $this->render_admin_shell_end();
  }

  public function render_ai_settings_page()
  {
    $this->render_admin_shell_start(
      __('AI Settings', 'jacana-luxe'),
      __('Configuration', 'jacana-luxe'),
      __('Control the Gemini integration, smart prompts, and the brand context that powers your on-site AI experiences.', 'jacana-luxe')
    );

    echo '<section class="jacana-admin-card">';
    echo '<form method="post" action="options.php" class="jacana-settings-grid">';
    settings_fields('jacana_crm_settings');
    echo '<label><span>' . esc_html__('Gemini API key', 'jacana-luxe') . '</span><input type="password" id="jacana_gemini_api_key" name="jacana_gemini_api_key" value="' . esc_attr(get_option('jacana_gemini_api_key', '')) . '" /></label>';
    echo '<label><span>' . esc_html__('Gemini model', 'jacana-luxe') . '</span><input type="text" id="jacana_gemini_model" name="jacana_gemini_model" value="' . esc_attr(get_option('jacana_gemini_model', 'gemini-3.1-pro-preview')) . '" /></label>';
    echo '<label><span>' . esc_html__('Google Maps API key', 'jacana-luxe') . '</span><input type="password" id="jacana_maps_api_key" name="jacana_maps_api_key" value="' . esc_attr(get_option('jacana_maps_api_key', '')) . '" /></label>';
    echo '<label><span>' . esc_html__('Brand voice', 'jacana-luxe') . '</span><textarea name="jacana_ai_brand_voice" rows="4">' . esc_textarea(get_option('jacana_ai_brand_voice', 'Elegant, credible, and practical luxury safari guidance.')) . '</textarea></label>';
    echo '<label><span>' . esc_html__('Conversion goals', 'jacana-luxe') . '</span><textarea name="jacana_ai_conversion_goals" rows="4">' . esc_textarea(get_option('jacana_ai_conversion_goals', 'Increase booking enquiries, surface high-intent visitors, and reduce friction on booking/service pages.')) . '</textarea></label>';
    echo '<label><span>' . esc_html__('Priority services', 'jacana-luxe') . '</span><textarea name="jacana_ai_priority_services" rows="4">' . esc_textarea(get_option('jacana_ai_priority_services', 'Tailor-made tours, car rentals, game drives, airport transfers, accommodation support')) . '</textarea></label>';
    echo '<label class="jacana-span-2"><span>' . esc_html__('Decision sheet system prompt', 'jacana-luxe') . '</span><textarea name="jacana_ai_planner_system_prompt" rows="5">' . esc_textarea(get_option('jacana_ai_planner_system_prompt', 'Craft concise, premium in-page planning prompts for Jacana. Use the current section content, page context, recent visitor interactions, and known interests to ask only the most relevant qualifying questions. Keep options concrete, factual, and tailored to the active widget.')) . '</textarea></label>';
    echo '<label class="jacana-span-2"><span>' . esc_html__('Accommodation planner prompt', 'jacana-luxe') . '</span><textarea name="jacana_ai_accommodation_prompt" rows="5">' . esc_textarea(get_option('jacana_ai_accommodation_prompt', 'When the visitor is in accommodation styles, only use the accommodation styles visible in the current section as options. Focus on taste, comfort level, and trip style. Do not suggest unrelated service options in step one.')) . '</textarea></label>';
    echo '<label class="jacana-span-2"><span>' . esc_html__('Vehicle planner prompt', 'jacana-luxe') . '</span><textarea name="jacana_ai_vehicle_prompt" rows="5">' . esc_textarea(get_option('jacana_ai_vehicle_prompt', 'When the visitor is comparing vehicles, keep the questions route-aware and practical. Focus on terrain, travelers, luggage, camping setup, and confidence level.')) . '</textarea></label>';
    echo '<label class="jacana-span-2"><span>' . esc_html__('Tailor-made route prompt', 'jacana-luxe') . '</span><textarea name="jacana_ai_tailor_prompt" rows="5">' . esc_textarea(get_option('jacana_ai_tailor_prompt', 'When the visitor is in tailor-made tours or highlights, ask concise questions that qualify route style, service mix, and the fastest next step to booking or callback.')) . '</textarea></label>';
    echo '<label class="jacana-span-2"><span>' . esc_html__('Booking modal prompt', 'jacana-luxe') . '</span><textarea name="jacana_ai_booking_prompt" rows="4">' . esc_textarea(get_option('jacana_ai_booking_prompt', 'Jacana already has some of your details. Update only what changed and add any new services or places.')) . '</textarea></label>';
    echo '<label class="jacana-span-2"><span>' . esc_html__('Detail-to-chat prompt', 'jacana-luxe') . '</span><textarea name="jacana_ai_detail_chat_prompt" rows="5">' . esc_textarea(get_option('jacana_ai_detail_chat_prompt', 'When a visitor asks for details from a card or style, open chat with a factual, helpful message based on that exact item and the current page context. Avoid generic sales language.')) . '</textarea></label>';
    echo '<label class="jacana-checkbox"><input type="checkbox" id="jacana_ai_experience_enabled" name="jacana_ai_experience_enabled" value="1" ' . checked((int) get_option('jacana_ai_experience_enabled', 1), 1, false) . ' /><span>' . esc_html__('Enable sitewide in-page AI experience layer', 'jacana-luxe') . '</span></label>';
    echo '<label><span>' . esc_html__('Minor insight delay (seconds)', 'jacana-luxe') . '</span><input type="number" min="5" max="600" step="1" id="jacana_ai_minor_delay_seconds" name="jacana_ai_minor_delay_seconds" value="' . esc_attr((int) get_option('jacana_ai_minor_delay_seconds', 8)) . '" /></label>';
    echo '<label><span>' . esc_html__('Max major prompts per page session', 'jacana-luxe') . '</span><input type="number" min="0" max="6" step="1" id="jacana_ai_max_major_prompts" name="jacana_ai_max_major_prompts" value="' . esc_attr((int) get_option('jacana_ai_max_major_prompts', 1)) . '" /></label>';
    echo '<label><span>' . esc_html__('Max minor insights per page session', 'jacana-luxe') . '</span><input type="number" min="0" max="6" step="1" id="jacana_ai_max_minor_prompts" name="jacana_ai_max_minor_prompts" value="' . esc_attr((int) get_option('jacana_ai_max_minor_prompts', 2)) . '" /></label>';
    echo '<label><span>' . esc_html__('Section context length (characters)', 'jacana-luxe') . '</span><input type="number" min="200" max="4000" step="50" id="jacana_ai_section_context_chars" name="jacana_ai_section_context_chars" value="' . esc_attr((int) get_option('jacana_ai_section_context_chars', 900)) . '" /></label>';
    echo '<label><span>' . esc_html__('Fallback booking email', 'jacana-luxe') . '</span><input type="email" id="jacana_booking_email" name="jacana_booking_email" value="' . esc_attr(get_option('jacana_booking_email', 'booking@jacanasafaristours.com')) . '" /></label>';
    echo '<div class="jacana-span-2 jacana-media-upload-field" data-field-id="jacana_ai_chatbot_icon">';
    echo '<span>' . esc_html__('Chatbot icon', 'jacana-luxe') . '</span>';
    echo '<div class="jacana-media-control-row">';
    $icon_url = get_option('jacana_ai_chatbot_icon', '');
    echo '<div class="jacana-media-preview' . ($icon_url ? ' has-image' : '') . '" id="jacana_ai_chatbot_icon_preview">';
    if ($icon_url) {
      echo '<img src="' . esc_url($icon_url) . '" alt="" />';
    }
    echo '</div>';
    echo '<div class="jacana-media-actions">';
    echo '<input type="text" id="jacana_ai_chatbot_icon" name="jacana_ai_chatbot_icon" value="' . esc_attr($icon_url) . '" placeholder="https://..." />';
    echo '<div class="jacana-card-actions">';
    echo '<button type="button" class="button jacana-media-upload-button">' . esc_html__('Upload / Select Icon', 'jacana-luxe') . '</button>';
    echo '<button type="button" class="button jacana-media-remove-button"' . (!$icon_url ? ' style="display:none;"' : '') . '>' . esc_html__('Remove', 'jacana-luxe') . '</button>';
    echo '</div>';
    echo '<small>' . esc_html__('Upload an image to replace the default AI concierge label in the discovery rail.', 'jacana-luxe') . '</small>';
    echo '</div></div></div>';
    echo '<label class="jacana-checkbox"><input type="checkbox" id="jacana_smart_prompt_enabled" name="jacana_smart_prompt_enabled" value="1" ' . checked((int) get_option('jacana_smart_prompt_enabled', 1), 1, false) . ' /><span>' . esc_html__('Enable smart prompt system', 'jacana-luxe') . '</span></label>';
    echo '<label><span>' . esc_html__('Prompt delay (seconds)', 'jacana-luxe') . '</span><input type="number" min="5" max="600" step="1" id="jacana_smart_prompt_delay_seconds" name="jacana_smart_prompt_delay_seconds" value="' . esc_attr((int) get_option('jacana_smart_prompt_delay_seconds', 30)) . '" /></label>';
    echo '<label><span>' . esc_html__('Min intent score', 'jacana-luxe') . '</span><input type="number" min="0" max="100" step="1" id="jacana_smart_prompt_min_intent" name="jacana_smart_prompt_min_intent" value="' . esc_attr((int) get_option('jacana_smart_prompt_min_intent', 20)) . '" /></label>';
    echo '<label><span>' . esc_html__('Min interactions', 'jacana-luxe') . '</span><input type="number" min="0" step="1" id="jacana_smart_prompt_min_interactions" name="jacana_smart_prompt_min_interactions" value="' . esc_attr((int) get_option('jacana_smart_prompt_min_interactions', 2)) . '" /></label>';
    echo '<label><span>' . esc_html__('Min scroll depth (%)', 'jacana-luxe') . '</span><input type="number" min="0" max="100" step="1" id="jacana_smart_prompt_min_scroll_depth" name="jacana_smart_prompt_min_scroll_depth" value="' . esc_attr((int) get_option('jacana_smart_prompt_min_scroll_depth', 20)) . '" /></label>';
    echo '<label><span>' . esc_html__('Min dwell time (seconds)', 'jacana-luxe') . '</span><input type="number" min="0" step="1" id="jacana_smart_prompt_min_dwell_seconds" name="jacana_smart_prompt_min_dwell_seconds" value="' . esc_attr((int) get_option('jacana_smart_prompt_min_dwell_seconds', 20)) . '" /></label>';
    echo '<label class="jacana-checkbox"><input type="checkbox" id="jacana_smart_prompt_once_per_session" name="jacana_smart_prompt_once_per_session" value="1" ' . checked((int) get_option('jacana_smart_prompt_once_per_session', 1), 1, false) . ' /><span>' . esc_html__('Show prompt at most once per session', 'jacana-luxe') . '</span></label>';
    echo '<label class="jacana-span-2"><span>' . esc_html__('Smart prompt instruction override', 'jacana-luxe') . '</span><textarea id="jacana_smart_prompt_system_prompt" name="jacana_smart_prompt_system_prompt" rows="6">' . esc_textarea(get_option('jacana_smart_prompt_system_prompt', '')) . '</textarea></label>';
    echo '<div class="jacana-span-2">';
    submit_button(__('Save AI settings', 'jacana-luxe'), 'primary', 'submit', false);
    echo '</div></form>';
    echo '</section>';

    $this->render_admin_shell_end();
  }

  private function render_visitor_detail($visitor_id)
  {
    global $wpdb;
    $visitors = $wpdb->prefix . 'jacana_visitors';
    $sessions = $wpdb->prefix . 'jacana_sessions';
    $pageviews = $wpdb->prefix . 'jacana_pageviews';
    $messages = $wpdb->prefix . 'jacana_chat_messages';
    $events = $wpdb->prefix . 'jacana_events';
    $leads = $wpdb->prefix . 'jacana_leads';

    $visitor = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$visitors} WHERE id = %d", $visitor_id));
    if (!$visitor) {
      $this->render_admin_shell_start(__('Visitor Intelligence', 'jacana-luxe'));
      echo '<div class="jacana-admin-card"><h2>' . esc_html__('Visitor not found', 'jacana-luxe') . '</h2></div>';
      $this->render_admin_shell_end();
      return;
    }

    $lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$leads} WHERE visitor_id = %d ORDER BY created_at DESC LIMIT 1", $visitor_id));
    $display_name = $lead && $lead->name ? $lead->name : 'Anonymous Visitor';

    $this->render_admin_shell_start(
      __('Visitor Details', 'jacana-luxe'),
      __('Full journey view', 'jacana-luxe'),
      __('Trace this visitor from pageviews to chat to lead capture so follow-up is based on real behavior.', 'jacana-luxe')
    );
    echo '<p><a href="' . esc_url($this->admin_page_url('jacana-crm-visitors')) . '">' . esc_html__('← Back to visitors', 'jacana-luxe') . '</a></p>';
    echo '<div class="jacana-admin-grid">';
    echo '<div class="jacana-admin-card">';
    echo '<h2>' . esc_html($display_name) . '</h2>';
    echo '<p class="jacana-muted">Visitor key: ' . esc_html($visitor->visitor_key) . '</p>';
    echo '<div class="jacana-kpi-grid">';
    echo '<div class="jacana-kpi"><span>Sessions</span><strong>' . esc_html($visitor->visit_count) . '</strong></div>';
    echo '<div class="jacana-kpi"><span>Total time</span><strong>' . esc_html($this->format_duration($visitor->total_time_sec)) . '</strong></div>';
    echo '<div class="jacana-kpi"><span>Last seen</span><strong>' . esc_html($visitor->last_seen) . '</strong></div>';
    echo '</div>';
    echo '</div>';
    echo '<div class="jacana-admin-card">';
    echo '<h3>Captured Details</h3>';
    echo '<div class="jacana-detail-list">';
    echo '<div><span>Name</span><strong>' . esc_html($lead && $lead->name ? $lead->name : '—') . '</strong></div>';
    echo '<div><span>Email</span><strong>' . esc_html($lead && $lead->email ? $lead->email : '—') . '</strong></div>';
    echo '<div><span>Country</span><strong>' . esc_html($lead && $lead->country ? $lead->country : '—') . '</strong></div>';
    echo '<div><span>Travel style</span><strong>' . esc_html($lead && $lead->travel_style ? $lead->travel_style : '—') . '</strong></div>';
    echo '<div><span>Service</span><strong>' . esc_html($lead && $lead->service_interest ? $lead->service_interest : '—') . '</strong></div>';
    echo '<div><span>Conversion score</span><strong>' . esc_html($lead ? (int) $lead->conversion_score : 0) . '</strong></div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    $sessions_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$sessions} WHERE visitor_id = %d ORDER BY started_at DESC", $visitor_id));
    echo '<div class="jacana-admin-card">';
    echo '<h2>Chat Sessions</h2>';
    if ($sessions_rows) {
      echo '<div class="jacana-chat-shell">';
      echo '<aside class="jacana-chat-sidebar"><div class="jacana-chat-sidebar-title">Sessions</div>';
      foreach ($sessions_rows as $index => $session) {
        $label = $session->label ? $session->label : 'Session';
        $preview_row = $wpdb->get_row($wpdb->prepare("SELECT content FROM {$messages} WHERE session_id = %d AND role = 'user' ORDER BY created_at ASC LIMIT 1", $session->id));
        $preview = $preview_row ? wp_strip_all_tags($preview_row->content) : 'No messages yet.';
        $active = $index === 0 ? ' is-active' : '';
        echo '<button type="button" class="jacana-session-item' . esc_attr($active) . '" data-session="' . esc_attr($session->id) . '">';
        echo '<div class="jacana-session-title">' . esc_html($label) . '</div>';
        echo '<div class="jacana-session-preview">' . esc_html($preview) . '</div>';
        echo '<div class="jacana-session-meta">Last active: ' . esc_html($session->last_seen) . '</div>';
        echo '</button>';
      }
      echo '</aside>';
      echo '<section class="jacana-chat-pane">';
      foreach ($sessions_rows as $index => $session) {
        $label = $session->label ? $session->label : 'Session';
        $pane_active = $index === 0 ? ' is-active' : '';
        echo '<div class="jacana-chat-session' . esc_attr($pane_active) . '" data-session="' . esc_attr($session->id) . '">';
        echo '<div class="jacana-session-header">';
        echo '<div><strong>' . esc_html($label) . '</strong><span class="jacana-muted"> • ' . esc_html($session->started_at) . '</span></div>';
        echo '<div class="jacana-session-meta">Last active: ' . esc_html($session->last_seen) . ' • ' . esc_html($session->pageviews) . ' pageviews • ' . esc_html($this->format_duration($session->total_time_sec)) . '</div>';
        echo '</div>';

        $chat_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$messages} WHERE session_id = %d ORDER BY created_at ASC LIMIT 400", $session->id));
        if ($chat_rows) {
          echo '<div class="jacana-chat-thread">';
          foreach ($chat_rows as $chat) {
            $is_user = $chat->role === 'user';
            $bubble_class = $is_user ? 'jacana-chat-bubble user' : 'jacana-chat-bubble assistant';
            $content = $this->render_chat_content($chat->content, $chat->role);
            $name = $is_user ? ($lead && $lead->name ? $lead->name : 'Visitor') : 'Jacana';
            echo '<div class="' . esc_attr($bubble_class) . '">';
            echo '<div class="jacana-chat-role">' . esc_html($name) . '</div>';
            echo '<div class="jacana-chat-content">' . $content . '</div>';
            echo '</div>';
          }
          echo '</div>';
        }
        else {
          echo '<p class="jacana-muted">No chat messages found for this session.</p>';
        }
        echo '</div>';
      }
      echo '</section>';
      echo '</div>';
    }
    else {
      echo '<p>No sessions found.</p>';
    }
    echo '</div>';

    $pv_page = max(1, absint($_GET['pv_page'] ?? 1));
    $pv_per_page = 25;
    $pv_offset = ($pv_page - 1) * $pv_per_page;
    $pv_total = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$pageviews} WHERE session_id IN (SELECT id FROM {$sessions} WHERE visitor_id = %d)", $visitor_id));
    $pageviews_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$pageviews} WHERE session_id IN (SELECT id FROM {$sessions} WHERE visitor_id = %d) ORDER BY started_at DESC LIMIT %d OFFSET %d", $visitor_id, $pv_per_page, $pv_offset));

    echo '<details class="jacana-collapsible"><summary>Pageviews (' . esc_html($pv_total) . ')</summary>';
    if ($pageviews_rows) {
      echo '<table class="widefat fixed striped jacana-admin-table"><thead><tr><th>URL</th><th>Title</th><th>Referrer</th><th>Started</th><th>Duration</th></tr></thead><tbody>';
      foreach ($pageviews_rows as $pv) {
        echo '<tr>';
        echo '<td>' . esc_html($pv->url) . '</td>';
        echo '<td>' . esc_html($pv->title) . '</td>';
        echo '<td>' . esc_html($pv->referrer) . '</td>';
        echo '<td>' . esc_html($pv->started_at) . '</td>';
        echo '<td>' . esc_html($this->format_duration($pv->duration_sec)) . '</td>';
        echo '</tr>';
      }
      echo '</tbody></table>';
      echo $this->render_pagination('pv_page', $pv_page, $pv_total, $pv_per_page, $visitor_id);
    }
    else {
      echo '<p class="jacana-muted">No pageviews found.</p>';
    }
    echo '</details>';

    $ev_page = max(1, absint($_GET['ev_page'] ?? 1));
    $ev_per_page = 25;
    $ev_offset = ($ev_page - 1) * $ev_per_page;
    $ev_total = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$events} WHERE visitor_id = %d", $visitor_id));
    $event_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$events} WHERE visitor_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d", $visitor_id, $ev_per_page, $ev_offset));

    echo '<details class="jacana-collapsible"><summary>Events (' . esc_html($ev_total) . ')</summary>';
    if ($event_rows) {
      echo '<table class="widefat fixed striped jacana-admin-table"><thead><tr><th>Type</th><th>Payload</th><th>Time</th></tr></thead><tbody>';
      foreach ($event_rows as $event) {
        echo '<tr>';
        echo '<td>' . esc_html($event->type) . '</td>';
        echo '<td><code>' . esc_html($event->payload) . '</code></td>';
        echo '<td>' . esc_html($event->created_at) . '</td>';
        echo '</tr>';
      }
      echo '</tbody></table>';
      echo $this->render_pagination('ev_page', $ev_page, $ev_total, $ev_per_page, $visitor_id);
    }
    else {
      echo '<p class="jacana-muted">No events found.</p>';
    }
    echo '</details>';

    echo '</div>';
    $this->render_admin_shell_end();
  }

  private function render_chat_content($content, $role)
  {
    $decoded = json_decode($content, true);
    if (!is_array($decoded) || empty($decoded['components'])) {
      $extracted = $this->extract_embedded_components($content);
      if (!empty($extracted)) {
        return $extracted;
      }
      return wp_kses_post(nl2br(esc_html($content)));
    }
    $html = '';
    foreach ($decoded['components'] as $component) {
      if (!is_array($component) || empty($component['type'])) {
        continue;
      }
      $type = $component['type'];
      if ($type === 'text') {
        $html .= '<div class="jacana-chat-text">' . esc_html($component['content'] ?? '') . '</div>';
      }
      elseif ($type === 'options') {
        $options = $component['options'] ?? array();
        $html .= '<div class="jacana-chat-options">';
        foreach ($options as $opt) {
          if (is_array($opt)) {
            $label = $opt['label'] ?? ($opt['value'] ?? '');
          }
          else {
            $label = $opt;
          }
          $html .= '<span class="jacana-chat-chip">' . esc_html($label) . '</span>';
        }
        $html .= '</div>';
      }
      elseif ($type === 'card') {
        $html .= '<div class="jacana-chat-card"><div class="jacana-chat-card-title">' . esc_html($component['title'] ?? '') . '</div><div>' . esc_html($component['content'] ?? '') . '</div></div>';
      }
      elseif ($type === 'list') {
        $html .= '<div class="jacana-chat-card"><div class="jacana-chat-card-title">' . esc_html($component['title'] ?? '') . '</div><ul>';
        foreach (($component['items'] ?? array()) as $item) {
          $html .= '<li>' . esc_html($item) . '</li>';
        }
        $html .= '</ul></div>';
      }
      elseif ($type === 'pricing') {
        $html .= '<div class="jacana-chat-card"><div class="jacana-chat-card-title">Estimated Range</div><div><strong>' . esc_html($component['range'] ?? '') . '</strong><br>' . esc_html($component['note'] ?? '') . '</div></div>';
      }
      elseif ($type === 'tier') {
        $html .= '<div class="jacana-chat-card"><div class="jacana-chat-card-title">Accommodation Tiers</div><ul>';
        foreach (($component['options'] ?? array()) as $opt) {
          $label = $opt['label'] ?? '';
          $detail = $opt['detail'] ?? '';
          $html .= '<li><strong>' . esc_html($label) . ':</strong> ' . esc_html($detail) . '</li>';
        }
        $html .= '</ul></div>';
      }
      elseif ($type === 'timing') {
        $html .= '<div class="jacana-chat-card"><div class="jacana-chat-card-title">Best Months</div><div class="jacana-chat-options">';
        foreach (($component['months'] ?? array()) as $month) {
          $html .= '<span class="jacana-chat-chip">' . esc_html($month) . '</span>';
        }
        $html .= '</div><div>' . esc_html($component['note'] ?? '') . '</div></div>';
      }
      elseif ($type === 'trip_style') {
        $html .= '<div class="jacana-chat-card"><div class="jacana-chat-card-title">Trip Style</div><ul>';
        foreach (($component['options'] ?? array()) as $opt) {
          $label = $opt['label'] ?? '';
          $detail = $opt['detail'] ?? '';
          $html .= '<li><strong>' . esc_html($label) . ':</strong> ' . esc_html($detail) . '</li>';
        }
        $html .= '</ul></div>';
      }
      elseif ($type === 'itinerary') {
        $html .= '<div class="jacana-chat-card"><div class="jacana-chat-card-title">' . esc_html($component['title'] ?? 'Itinerary Preview') . '</div><ul>';
        foreach (($component['days'] ?? array()) as $day) {
          $title = $day['title'] ?? '';
          $detail = $day['detail'] ?? '';
          $label = $day['day'] ?? '';
          $html .= '<li><strong>Day ' . esc_html($label) . ':</strong> ' . esc_html($title) . ' — ' . esc_html($detail) . '</li>';
        }
        $html .= '</ul></div>';
      }
      elseif ($type === 'map_route') {
        $html .= $this->render_map_component('route', $component['stops'] ?? array());
      }
      elseif ($type === 'map_places') {
        $html .= $this->render_map_component('places', $component['places'] ?? array());
      }
      elseif ($type === 'cta') {
        $html .= '<span class="jacana-chat-badge">' . esc_html($component['text'] ?? 'Continue') . '</span>';
      }
      elseif ($type === 'form') {
        $html .= '<div class="jacana-chat-card"><div class="jacana-chat-card-title">' . esc_html($component['title'] ?? 'Form') . '</div>';
        foreach (($component['fields'] ?? array()) as $field) {
          $label = is_array($field) ? ($field['label'] ?? $field['name'] ?? '') : $field;
          $html .= '<div class="jacana-chat-chip">' . esc_html($label) . '</div>';
        }
        $html .= '</div>';
      }
      else {
        $html .= '<div class="jacana-chat-text">' . esc_html(wp_json_encode($component)) . '</div>';
      }
    }
    return $html;
  }

  private function render_map_component($type, $items)
  {
    $clean = array();
    foreach ((array)$items as $item) {
      if (!is_array($item)) {
        continue;
      }
      if (!isset($item['lat'], $item['lng'])) {
        continue;
      }
      $clean[] = array(
        'label' => sanitize_text_field($item['label'] ?? ''),
        'lat' => (float)$item['lat'],
        'lng' => (float)$item['lng'],
      );
    }
    if (!$clean) {
      return '<div class="jacana-chat-map-fallback">Map data unavailable.</div>';
    }
    $data = esc_attr(wp_json_encode($clean));
    $fallback = '<div class="jacana-chat-map-fallback">' . esc_html($this->map_fallback_text($clean)) . '</div>';
    return '<div class="jacana-chat-map" data-map-type="' . esc_attr($type) . '" data-map-data="' . $data . '">' . $fallback . '</div>';
  }

  private function map_fallback_text($items)
  {
    $labels = array();
    foreach ($items as $item) {
      if (!empty($item['label'])) {
        $labels[] = $item['label'];
      }
    }
    if (!$labels) {
      return 'Map loading…';
    }
    return 'Route: ' . implode(' → ', $labels);
  }

  private function extract_embedded_components($content)
  {
    $text = (string)$content;
    if (strpos($text, '{') === false) {
      return '';
    }
    $matches = array();
    preg_match_all('/\\{\\s*\"type\"\\s*:\\s*\"(map_route|map_places)\"[^}]*\\}/', $text, $matches);
    if (empty($matches[0])) {
      return '';
    }
    $html = '<div class="jacana-chat-text">' . esc_html(trim(preg_replace('/\\{\\s*\"type\"\\s*:\\s*\"(map_route|map_places)\"[^}]*\\}/', '', $text))) . '</div>';
    foreach ($matches[0] as $chunk) {
      $decoded = json_decode($chunk, true);
      if (!$decoded || empty($decoded['type'])) {
        continue;
      }
      if ($decoded['type'] === 'map_route') {
        $html .= $this->render_map_component('route', $decoded['stops'] ?? array());
      }
      elseif ($decoded['type'] === 'map_places') {
        $html .= $this->render_map_component('places', $decoded['places'] ?? array());
      }
    }
    return $html;
  }

  private function render_pagination($key, $current, $total, $per_page, $visitor_id)
  {
    if ($total <= $per_page)
      return '';
    $total_pages = (int)ceil($total / $per_page);
    $html = '<div class="tablenav"><div class="tablenav-pages">';
    $base_url = admin_url('admin.php?page=jacana-crm-visitors&visitor=' . $visitor_id);
    $prev = max(1, $current - 1);
    $next = min($total_pages, $current + 1);
    $html .= '<span class="pagination-links">';
    $html .= '<a class="button ' . ($current <= 1 ? 'disabled' : '') . '" href="' . esc_url(add_query_arg($key, $prev, $base_url)) . '">‹</a>';
    $html .= '<span class="paging-input">' . esc_html($current) . ' of ' . esc_html($total_pages) . '</span>';
    $html .= '<a class="button ' . ($current >= $total_pages ? 'disabled' : '') . '" href="' . esc_url(add_query_arg($key, $next, $base_url)) . '">›</a>';
    $html .= '</span></div></div>';
    return $html;
  }

  private function format_duration($seconds)
  {
    $seconds = (int)$seconds;
    if ($seconds <= 0)
      return '0s';
    if ($seconds < 60)
      return $seconds . 's';
    $minutes = floor($seconds / 60);
    if ($minutes < 60)
      return $minutes . 'm';
    $hours = floor($minutes / 60);
    $minutes = $minutes % 60;
    return $hours . 'h ' . $minutes . 'm';
  }

  public function render_frontend_seo_meta()
  {
    if (is_admin() || is_feed()) {
      return;
    }

    $has_seo_plugin = defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('SEOPRESS_VERSION');
    $description = '';
    $title = wp_get_document_title();
    $url = home_url(add_query_arg(array(), $GLOBALS['wp']->request ?? ''));
    $image = '';

    if (is_singular()) {
      $post = get_queried_object();
      if ($post instanceof WP_Post) {
        $description = $this->build_seo_description_for_post($post);
        if (has_post_thumbnail($post)) {
          $image = get_the_post_thumbnail_url($post, 'full') ?: '';
        }
        $url = get_permalink($post);
      }
    } else {
      $description = (string) get_option('jacana_seo_default_description_suffix', '');
    }

    if (!$has_seo_plugin && (int) get_option('jacana_seo_enable_meta_fallback', 1) === 1 && $description !== '') {
      echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
      echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
      echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
      echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
      echo '<meta property="og:type" content="' . esc_attr(is_singular() ? 'article' : 'website') . '">' . "\n";
      if ($image !== '') {
        echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
      }
    }

    if ((int) get_option('jacana_seo_enable_schema', 1) !== 1) {
      return;
    }

    $org = array(
      '@context' => 'https://schema.org',
      '@type' => 'TravelAgency',
      'name' => (string) get_option('jacana_seo_org_name', get_bloginfo('name')),
      'url' => home_url('/'),
      'email' => (string) get_option('jacana_seo_business_email', get_option('admin_email')),
      'telephone' => (string) get_option('jacana_seo_business_phone', ''),
      'priceRange' => (string) get_option('jacana_seo_price_range', '$$$'),
      'address' => array(
        '@type' => 'PostalAddress',
        'streetAddress' => (string) get_option('jacana_seo_business_address', ''),
        'addressLocality' => (string) get_option('jacana_seo_business_city', 'Windhoek'),
        'addressCountry' => (string) get_option('jacana_seo_business_country', 'Namibia'),
      ),
    );

    if ($image !== '') {
      $org['image'] = $image;
    }

    echo '<script type="application/ld+json">' . wp_json_encode($org, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
  }

  public function enqueue_admin_styles($hook)
  {
    if (strpos($hook, 'jacana-crm') === false) {
      return;
    }

    wp_enqueue_media();

    wp_enqueue_style(
      'jacana-crm-admin',
      plugin_dir_url(__FILE__) . 'assets/admin.css',
      array(),
      self::DB_VERSION
    );

    wp_enqueue_script(
      'jacana-crm-admin',
      plugin_dir_url(__FILE__) . 'assets/admin.js',
      array('jquery'),
      self::DB_VERSION,
      true
    );

    $maps_key = get_option('jacana_maps_api_key', '');
    if ($maps_key) {
      wp_enqueue_script('jacana-admin-maps', 'https://maps.googleapis.com/maps/api/js?key=' . urlencode($maps_key), array(), null, true);
    }
    wp_add_inline_script(
      'jacana-crm-admin',
      'window.jacanaCrmAdmin=' . wp_json_encode(array(
        'hasMaps' => !empty($maps_key),
      )) . ';',
      'before'
    );
  }

  public function register_routes()
  {
    register_rest_route('jacana/v1', '/track', array(
      'methods' => 'POST',
      'callback' => array($this, 'handle_track'),
      'permission_callback' => '__return_true',
    ));

    register_rest_route('jacana/v1', '/chat', array(
      'methods' => 'POST',
      'callback' => array($this, 'handle_chat'),
      'permission_callback' => '__return_true',
    ));

    register_rest_route('jacana/v1', '/chat/history', array(
      'methods' => 'GET',
      'callback' => array($this, 'handle_chat_history'),
      'permission_callback' => '__return_true',
    ));

    register_rest_route('jacana/v1', '/chat/sessions', array(
      'methods' => 'GET',
      'callback' => array($this, 'handle_chat_sessions'),
      'permission_callback' => '__return_true',
    ));

    register_rest_route('jacana/v1', '/chat/session/label', array(
      'methods' => 'POST',
      'callback' => array($this, 'handle_chat_session_label'),
      'permission_callback' => '__return_true',
    ));

    register_rest_route('jacana/v1', '/ai', array(
      'methods' => 'POST',
      'callback' => array($this, 'handle_ai'),
      'permission_callback' => '__return_true',
    ));

    register_rest_route('jacana/v1', '/lead', array(
      'methods' => 'POST',
      'callback' => array($this, 'handle_lead'),
      'permission_callback' => '__return_true',
    ));

    register_rest_route('jacana/v1', '/profile', array(
      'methods' => 'GET',
      'callback' => array($this, 'handle_profile'),
      'permission_callback' => '__return_true',
    ));

    register_rest_route('jacana/v1', '/conversion', array(
      'methods' => 'POST',
      'callback' => array($this, 'handle_conversion'),
      'permission_callback' => '__return_true',
    ));

    register_rest_route('jacana/v1', '/ai-touchpoint', array(
      'methods' => 'POST',
      'callback' => array($this, 'handle_ai_touchpoint'),
      'permission_callback' => '__return_true',
    ));

    register_rest_route('jacana/v1', '/ai-client-log', array(
      'methods' => 'POST',
      'callback' => array($this, 'handle_ai_client_log'),
      'permission_callback' => '__return_true',
    ));

    register_rest_route('jacana/v1', '/appointment-request', array(
      'methods' => 'POST',
      'callback' => array($this, 'handle_appointment_request'),
      'permission_callback' => '__return_true',
    ));

    register_rest_route('jacana/v1', '/booking-request', array(
      'methods' => 'POST',
      'callback' => array($this, 'handle_booking_request'),
      'permission_callback' => '__return_true',
    ));

    register_rest_route('jacana/v1', '/reviews', array(
      array(
        'methods' => 'GET',
        'callback' => array($this, 'handle_reviews_get'),
        'permission_callback' => '__return_true',
      ),
      array(
        'methods' => 'POST',
        'callback' => array($this, 'handle_reviews_submit'),
        'permission_callback' => '__return_true',
      ),
    ));

    register_rest_route('jacana/v1', '/reviews/(?P<id>\d+)', array(
      array(
        'methods' => 'DELETE',
        'callback' => array($this, 'handle_review_delete'),
        'permission_callback' => function () {
          return current_user_can('manage_options');
        },
      ),
    ));

    register_rest_route('jacana/v1', '/reviews/stats', array(
      'methods' => 'GET',
      'callback' => array($this, 'handle_reviews_stats'),
      'permission_callback' => '__return_true',
    ));
  }

  private function get_or_create_visitor($visitor_key)
  {
    global $wpdb;
    $visitors = $wpdb->prefix . 'jacana_visitors';
    $now = current_time('mysql');

    // Avoid SELECT-then-INSERT races under concurrent REST calls.
    $wpdb->query(
      $wpdb->prepare(
        "INSERT INTO {$visitors} (visitor_key, ip_address, user_agent, first_seen, last_seen, visit_count, total_time_sec)
         VALUES (%s, %s, %s, %s, %s, 0, 0)
         ON DUPLICATE KEY UPDATE last_seen = VALUES(last_seen)",
        $visitor_key,
        sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
        sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
        $now,
        $now
      )
    );

    return (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$visitors} WHERE visitor_key = %s", $visitor_key));
  }

  private function get_or_create_session($visitor_id, $session_key)
  {
    global $wpdb;
    $sessions = $wpdb->prefix . 'jacana_sessions';
    $now = current_time('mysql');

    // Avoid SELECT-then-INSERT races under concurrent REST calls.
    $result = $wpdb->query(
      $wpdb->prepare(
        "INSERT INTO {$sessions} (visitor_id, session_key, started_at, last_seen, total_time_sec, pageviews)
         VALUES (%d, %s, %s, %s, 0, 0)
         ON DUPLICATE KEY UPDATE last_seen = VALUES(last_seen)",
        $visitor_id,
        $session_key,
        $now,
        $now
      )
    );

    // Only increment visit_count when we actually created a new session row.
    if ((int) $result === 1) {
      $visitors = $wpdb->prefix . 'jacana_visitors';
      $wpdb->query($wpdb->prepare("UPDATE {$visitors} SET visit_count = visit_count + 1 WHERE id = %d", $visitor_id));
    }

    return (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$sessions} WHERE session_key = %s", $session_key));
  }

  private function sanitize_list_or_text($value)
  {
    if (is_array($value)) {
      $value = array_filter(array_map('sanitize_text_field', $value));
      return implode(', ', $value);
    }

    return sanitize_text_field((string) $value);
  }

  private function sanitize_event_payload($payload)
  {
    if (!is_array($payload)) {
      return array();
    }

    $clean = array();
    foreach ($payload as $key => $value) {
      $safe_key = sanitize_key((string) $key);
      if ($safe_key === '') {
        continue;
      }

      if (is_array($value)) {
        $clean[$safe_key] = $this->sanitize_event_payload($value);
      } elseif (is_bool($value) || is_numeric($value)) {
        $clean[$safe_key] = $value;
      } else {
        $clean[$safe_key] = sanitize_text_field((string) $value);
      }
    }

    return $clean;
  }

  private function resolve_visitor_and_session($visitor_key, $session_key)
  {
    $visitor_id = 0;
    $session_id = 0;

    if (!empty($visitor_key)) {
      $visitor_id = $this->get_or_create_visitor($visitor_key);
    }

    if ($visitor_id > 0 && !empty($session_key)) {
      $session_id = $this->get_or_create_session($visitor_id, $session_key);
    }

    return array($visitor_id, $session_id);
  }

  private function log_event_row($visitor_id, $session_id, $event_type, $payload = array())
  {
    global $wpdb;
    if (empty($event_type)) {
      return;
    }

    $events = $wpdb->prefix . 'jacana_events';
    $wpdb->insert(
      $events,
      array(
        'visitor_id' => $visitor_id > 0 ? $visitor_id : null,
        'session_id' => $session_id > 0 ? $session_id : null,
        'type' => sanitize_key((string) $event_type),
        'payload' => wp_json_encode($this->sanitize_event_payload((array) $payload)),
        'created_at' => current_time('mysql'),
      )
    );
  }

  private function get_booking_inbox_email()
  {
    $email = sanitize_email((string) get_option('jacana_booking_email', 'booking@jacanasafaristours.com'));
    return $email !== '' ? $email : 'booking@jacanasafaristours.com';
  }

  private function log_ai_interaction($visitor_id, $session_id, $component_type, $component_key, $page_url, $widget_name, $service_interest, $action_type, $payload = array())
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_ai_interactions';
    $clean_payload = $this->sanitize_event_payload((array) $payload);

    $wpdb->insert(
      $table,
      array(
        'visitor_id' => $visitor_id > 0 ? $visitor_id : null,
        'session_id' => $session_id > 0 ? $session_id : null,
        'component_type' => sanitize_key((string) $component_type),
        'component_key' => sanitize_text_field((string) $component_key),
        'page_url' => esc_url_raw((string) $page_url),
        'widget_name' => sanitize_text_field((string) $widget_name),
        'service_interest' => sanitize_text_field((string) $service_interest),
        'payload_json' => wp_json_encode($clean_payload),
        'action_type' => sanitize_key((string) $action_type),
        'created_at' => current_time('mysql'),
      )
    );

    $this->log_event_row($visitor_id, $session_id, 'ai_touchpoint', array(
      'component_type' => $component_type,
      'component_key' => $component_key,
      'widget_name' => $widget_name,
      'service_interest' => $service_interest,
      'action_type' => $action_type,
      'page_url' => $page_url,
      'meta' => $clean_payload,
    ));
  }

  private function get_recent_ai_interactions($limit = 40)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_ai_interactions';
    return $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d",
      max(1, absint($limit))
    ));
  }

  private function get_recent_frontend_ai_errors($limit = 120)
  {
    global $wpdb;
    $events = $wpdb->prefix . 'jacana_events';
    $visitors = $wpdb->prefix . 'jacana_visitors';
    $sessions = $wpdb->prefix . 'jacana_sessions';

    return $wpdb->get_results($wpdb->prepare(
      "SELECT e.id, e.visitor_id, e.session_id, e.payload, e.created_at, v.visitor_key, s.session_key
       FROM {$events} e
       LEFT JOIN {$visitors} v ON v.id = e.visitor_id
       LEFT JOIN {$sessions} s ON s.id = e.session_id
       WHERE e.type = %s
       ORDER BY e.created_at DESC
       LIMIT %d",
      'frontend_ai_error',
      max(1, absint($limit))
    ));
  }

  private function get_reviews($args = array())
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_reviews';
    $where = array('1=1');
    $params = array();

    if (!empty($args['status'])) {
      $statuses = (array) $args['status'];
      $placeholders = implode(',', array_fill(0, count($statuses), '%s'));
      $where[] = "status IN ({$placeholders})";
      foreach ($statuses as $status) {
        $params[] = sanitize_key((string) $status);
      }
    }

    if (!empty($args['service_interest'])) {
      $where[] = 'service_interest = %s';
      $params[] = sanitize_text_field((string) $args['service_interest']);
    }

    $limit = !empty($args['limit']) ? max(1, absint($args['limit'])) : 20;
    $offset = !empty($args['offset']) ? absint($args['offset']) : 0;
    $params[] = $limit;
    $params[] = $offset;

    $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC LIMIT %d OFFSET %d";
    return $wpdb->get_results($wpdb->prepare($sql, $params));
  }

  private function update_review_status($review_id, $status)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_reviews';
    $data = array(
      'status' => $status,
      'updated_at' => current_time('mysql'),
    );

    if ($status === 'approved' || $status === 'featured') {
      $data['approved_at'] = current_time('mysql');
      $data['approved_by'] = get_current_user_id();
    }

    $wpdb->update($table, $data, array('id' => absint($review_id)));
  }

  private function maybe_send_appointment_notifications($details)
  {
    $inbox = $this->get_booking_inbox_email();
    $name = sanitize_text_field((string) ($details['name'] ?? 'Traveler'));
    $email = sanitize_email((string) ($details['email'] ?? ''));
    $phone = sanitize_text_field((string) ($details['phone'] ?? ''));
    $service = sanitize_text_field((string) ($details['service_interest'] ?? ''));
    $page = esc_url_raw((string) ($details['source_page'] ?? ''));
    $widget = sanitize_text_field((string) ($details['source_widget'] ?? ''));
    $section = sanitize_text_field((string) ($details['source_section'] ?? ''));
    $vehicle = sanitize_text_field((string) ($details['vehicle_interest'] ?? ''));
    $destination = sanitize_text_field((string) ($details['destination_interest'] ?? ''));
    $travel_dates = sanitize_text_field((string) ($details['travel_dates'] ?? ''));
    $duration = sanitize_text_field((string) ($details['duration'] ?? ''));
    $party_size = sanitize_text_field((string) ($details['party_size'] ?? ''));
    $budget_tier = sanitize_text_field((string) ($details['budget_tier'] ?? ''));
    $accommodation_style = sanitize_text_field((string) ($details['accommodation_style'] ?? ''));
    $preferred_time = sanitize_text_field((string) ($details['preferred_time'] ?? ''));
    $route = sanitize_key((string) ($details['route'] ?? 'appointment_request'));
    $selected_services = array_values(array_filter(array_map('sanitize_text_field', (array) ($details['selected_services'] ?? array()))));
    $additional_places = sanitize_textarea_field((string) ($details['additional_places'] ?? ''));
    $summary = sanitize_textarea_field((string) ($details['summary'] ?? ''));

    $subject = $route === 'booking_request'
      ? sprintf(__('New Jacana booking request: %s', 'jacana-luxe'), $service !== '' ? $service : __('Travel planning', 'jacana-luxe'))
      : sprintf(__('New Jacana AI appointment request: %s', 'jacana-luxe'), $service !== '' ? $service : __('Travel planning', 'jacana-luxe'));
    $message = $route === 'booking_request'
      ? "A visitor submitted a booking request through the AI booking flow.\n\n"
      : "A visitor requested a planning call / quote handoff.\n\n";
    $message .= "Name: {$name}\n";
    $message .= "Email: {$email}\n";
    $message .= "Phone: {$phone}\n";
    $message .= "Service: {$service}\n";
    $message .= "Vehicle: {$vehicle}\n";
    $message .= "Destination: {$destination}\n";
    $message .= "Travel dates: {$travel_dates}\n";
    $message .= "Duration: {$duration}\n";
    $message .= "Party size: {$party_size}\n";
    $message .= "Budget tier: {$budget_tier}\n";
    $message .= "Accommodation style: {$accommodation_style}\n";
    $message .= "Selected services: " . (!empty($selected_services) ? implode(', ', $selected_services) : '') . "\n";
    $message .= "Additional places: {$additional_places}\n";
    $message .= "Preferred callback time: {$preferred_time}\n";
    $message .= "Source page: {$page}\n";
    $message .= "Source widget: {$widget}\n";
    $message .= "CTA section: {$section}\n";
    $message .= "Summary: {$summary}\n";

    wp_mail($inbox, $subject, $message);

    if ($email !== '') {
      $visitor_subject = __('We received your Jacana planning request', 'jacana-luxe');
      $visitor_message = "Hi {$name},\n\n";
      $visitor_message .= "Thanks for planning with Jacana Safaris & Tours.\n";
      $visitor_message .= "We have received your request and will follow up shortly.\n\n";
      if ($service !== '') {
        $visitor_message .= "Service of interest: {$service}\n";
      }
      if ($travel_dates !== '') {
        $visitor_message .= "Travel dates: {$travel_dates}\n";
      }
      if ($duration !== '') {
        $visitor_message .= "Duration: {$duration}\n";
      }
      if ($party_size !== '') {
        $visitor_message .= "Party size: {$party_size}\n";
      }
      $visitor_message .= "\nIf you prefer, you can also continue on the booking page or reply to this email.\n\n";
      $visitor_message .= "Regards,\nJacana Safaris & Tours";
      wp_mail($email, $visitor_subject, $visitor_message);
    }
  }

  private function get_latest_lead_for_visitor($visitor_id)
  {
    global $wpdb;
    if ($visitor_id <= 0) {
      return null;
    }
    $leads = $wpdb->prefix . 'jacana_leads';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$leads} WHERE visitor_id = %d ORDER BY updated_at DESC LIMIT 1", $visitor_id));
  }

  private function get_latest_lead_by_email($email)
  {
    global $wpdb;
    if (empty($email)) {
      return null;
    }
    $leads = $wpdb->prefix . 'jacana_leads';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$leads} WHERE email = %s ORDER BY updated_at DESC LIMIT 1", $email));
  }

  private function calculate_conversion_increment($stage)
  {
    $weights = array(
      'chat_open' => 2,
      'session_resume' => 3,
      'service_interest' => 6,
      'vehicle_interest' => 7,
      'map_interest' => 7,
      'booking_start' => 12,
      'whatsapp_click' => 10,
      'lead_submit' => 20,
    );
    return isset($weights[$stage]) ? (int) $weights[$stage] : 4;
  }

  private function priority_from_score($score)
  {
    $score = (int) $score;
    if ($score >= 70) {
      return 'vip';
    }
    if ($score >= 45) {
      return 'high';
    }
    if ($score <= 10) {
      return 'low';
    }
    return 'normal';
  }

  private function calculate_conversion_score_for_visitor($visitor_id)
  {
    global $wpdb;
    $visitor_id = (int) $visitor_id;
    if ($visitor_id <= 0) {
      return 0;
    }

    $events = $wpdb->prefix . 'jacana_events';
    $rows = $wpdb->get_results($wpdb->prepare(
      "SELECT payload FROM {$events}
       WHERE visitor_id = %d AND type = %s
       ORDER BY created_at DESC
       LIMIT 250",
      $visitor_id,
      'conversion_signal'
    ));

    $score = 0;
    foreach ((array) $rows as $row) {
      $payload = json_decode((string) ($row->payload ?? ''), true);
      if (!is_array($payload)) {
        continue;
      }
      $stage = sanitize_key((string) ($payload['stage'] ?? ''));
      if ($stage === '') {
        continue;
      }
      $score += $this->calculate_conversion_increment($stage);
      if ($score >= 100) {
        return 100;
      }
    }

    return min(100, (int) $score);
  }

  private function maybe_send_lead_autoresponder($name, $email, $travel_dates, $duration, $party_size, $travel_style, $budget_tier, $interests)
  {
    if (empty($email)) {
      return;
    }

    $subject = 'Your Jacana Safaris & Tours inquiry';
    $message = "Hi {$name},\n\nThanks for reaching out to Jacana Safaris & Tours.\n\n";
    $message .= "Here is a quick summary of your request:\n";
    $message .= "- Travel dates: {$travel_dates}\n";
    $message .= "- Duration: {$duration}\n";
    $message .= "- Party size: {$party_size}\n";
    $message .= "- Travel style: {$travel_style}\n";
    $message .= "- Budget tier: {$budget_tier}\n";
    $message .= "- Interests: {$interests}\n\n";
    $message .= "We will reply shortly with a personalized proposal.\n\n";
    $message .= "Warm regards,\nJacana Safaris & Tours";
    wp_mail($email, $subject, $message);
  }

  public function handle_lead($request)
  {
    global $wpdb;
    $params = $request->get_json_params();

    $visitor_key = sanitize_text_field($params['visitor_key'] ?? '');
    $session_key = sanitize_text_field($params['session_key'] ?? '');
    list($visitor_id, $session_id) = $this->resolve_visitor_and_session($visitor_key, $session_key);

    $name = sanitize_text_field($params['name'] ?? '');
    $email = sanitize_email($params['email'] ?? '');
    $phone = sanitize_text_field($params['phone'] ?? '');
    $country = sanitize_text_field($params['country'] ?? '');
    $travel_dates = sanitize_text_field($params['travel_dates'] ?? '');
    $duration = sanitize_text_field($params['duration'] ?? '');
    $party_size = sanitize_text_field($params['party_size'] ?? '');
    $budget_tier = sanitize_text_field($params['budget_tier'] ?? '');
    $travel_style = sanitize_text_field($params['travel_style'] ?? '');
    $service_interest = sanitize_text_field($params['service_interest'] ?? '');
    $source_page = esc_url_raw($params['source_page'] ?? '');
    $interests = $this->sanitize_list_or_text($params['interests'] ?? '');
    $summary = sanitize_textarea_field($params['summary'] ?? '');
    $source = sanitize_text_field($params['source'] ?? 'concierge');

    if (!$email && !$phone) {
      return new WP_REST_Response(array('ok' => false, 'error' => 'email_or_phone_required'), 400);
    }

    $leads = $wpdb->prefix . 'jacana_leads';
    $now = current_time('mysql');
    $existing = $this->get_latest_lead_by_email($email);
    if (!$existing && $visitor_id > 0) {
      $existing = $this->get_latest_lead_for_visitor($visitor_id);
    }

    $inserted = false;
    if ($existing) {
      $update = array('updated_at' => $now);
      $field_map = array(
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'country' => $country,
        'travel_dates' => $travel_dates,
        'duration' => $duration,
        'party_size' => $party_size,
        'budget_tier' => $budget_tier,
        'travel_style' => $travel_style,
        'service_interest' => $service_interest,
        'source_page' => $source_page,
        'interests' => $interests,
        'summary' => $summary,
        'source' => $source,
      );

      foreach ($field_map as $field => $value) {
        if (!empty($value)) {
          $update[$field] = $value;
        }
      }

      if ($visitor_id > 0 && empty($existing->visitor_id)) {
        $update['visitor_id'] = $visitor_id;
      }

      if (!empty($existing->conversion_score)) {
        $update['priority'] = $this->priority_from_score((int) $existing->conversion_score);
      }

      $wpdb->update($leads, $update, array('id' => $existing->id));
      $lead_id = (int) $existing->id;
    } else {
      $inserted = true;
      $wpdb->insert(
        $leads,
        array(
          'visitor_id' => $visitor_id > 0 ? $visitor_id : null,
          'name' => $name,
          'email' => $email,
          'phone' => $phone,
          'country' => $country,
          'travel_dates' => $travel_dates,
          'duration' => $duration,
          'party_size' => $party_size,
          'budget_tier' => $budget_tier,
          'travel_style' => $travel_style,
          'service_interest' => $service_interest,
          'source_page' => $source_page,
          'interests' => $interests,
          'priority' => 'normal',
          'status' => 'new',
          'source' => $source,
          'summary' => $summary,
          'created_at' => $now,
          'updated_at' => $now,
        )
      );
      $lead_id = (int) $wpdb->insert_id;
    }

    if ($lead_id <= 0) {
      return new WP_REST_Response(array('ok' => false, 'error' => 'lead_save_failed'), 500);
    }

    $this->log_event_row($visitor_id, $session_id, 'lead_submit', array(
      'lead_id' => $lead_id,
      'source' => $source,
      'service_interest' => $service_interest,
      'source_page' => $source_page,
    ));

    // Backfill a baseline score from previous signals so the lead record reflects intent even if
    // the visitor hadn't submitted details yet.
    if ($visitor_id > 0) {
      $baseline = min(100, $this->calculate_conversion_score_for_visitor($visitor_id) + $this->calculate_conversion_increment('lead_submit'));
      $current_score = $existing ? (int) $existing->conversion_score : 0;
      if ($baseline > $current_score) {
        $wpdb->update(
          $leads,
          array(
            'conversion_score' => $baseline,
            'priority' => $this->priority_from_score($baseline),
            'updated_at' => current_time('mysql')
          ),
          array('id' => $lead_id)
        );
      }
    }

    if ($inserted) {
      $this->maybe_send_lead_autoresponder($name, $email, $travel_dates, $duration, $party_size, $travel_style, $budget_tier, $interests);
    }

    return new WP_REST_Response(array('ok' => true, 'lead_id' => $lead_id, 'status' => $inserted ? 'created' : 'updated'), 200);
  }

  public function handle_conversion($request)
  {
    global $wpdb;
    $params = $request->get_json_params();

    $visitor_key = sanitize_text_field($params['visitor_key'] ?? '');
    $session_key = sanitize_text_field($params['session_key'] ?? '');
    $stage = sanitize_key($params['stage'] ?? '');
    $service = sanitize_text_field($params['service'] ?? '');
    $label = sanitize_text_field($params['label'] ?? '');
    $page_url = esc_url_raw($params['page_url'] ?? '');
    $intent_score = absint($params['intent_score'] ?? 0);
    $persona = sanitize_key($params['persona'] ?? '');

    if (!$visitor_key || !$session_key || !$stage) {
      return new WP_REST_Response(array('ok' => false, 'error' => 'missing_required'), 400);
    }

    list($visitor_id, $session_id) = $this->resolve_visitor_and_session($visitor_key, $session_key);
    if ($visitor_id <= 0 || $session_id <= 0) {
      return new WP_REST_Response(array('ok' => false, 'error' => 'session_resolution_failed'), 500);
    }

    $meta = isset($params['meta']) && is_array($params['meta']) ? $params['meta'] : array();
    $payload = array(
      'stage' => $stage,
      'service' => $service,
      'label' => $label,
      'page_url' => $page_url,
      'intent_score' => $intent_score,
      'persona' => $persona,
      'meta' => $meta,
    );
    $this->log_event_row($visitor_id, $session_id, 'conversion_signal', $payload);

    $lead = $this->get_latest_lead_for_visitor($visitor_id);
    $updated_score = 0;
    if ($lead) {
      $increment = $this->calculate_conversion_increment($stage);
      $updated_score = min(100, ((int) $lead->conversion_score) + $increment);
      $update = array(
        'conversion_score' => $updated_score,
        'priority' => $this->priority_from_score($updated_score),
        'updated_at' => current_time('mysql'),
      );
      if (!empty($service) && empty($lead->service_interest)) {
        $update['service_interest'] = $service;
      }
      if (!empty($page_url) && empty($lead->source_page)) {
        $update['source_page'] = $page_url;
      }
      $wpdb->update($wpdb->prefix . 'jacana_leads', $update, array('id' => $lead->id));
    }
    else {
      // No lead captured yet; still return a best-effort score so the UI can react.
      $updated_score = min(100, $this->calculate_conversion_score_for_visitor($visitor_id) + $this->calculate_conversion_increment($stage));
    }

    return new WP_REST_Response(array('ok' => true, 'conversion_score' => $updated_score), 200);
  }

  public function handle_ai_touchpoint($request)
  {
    $this->ensure_runtime_tables();
    $params = $request->get_json_params();
    $visitor_key = sanitize_text_field($params['visitor_key'] ?? '');
    $session_key = sanitize_text_field($params['session_key'] ?? '');
    $component_type = sanitize_key($params['component_type'] ?? '');
    $component_key = sanitize_text_field($params['component_key'] ?? '');
    $page_url = esc_url_raw($params['page_url'] ?? '');
    $widget_name = sanitize_text_field($params['widget_name'] ?? '');
    $service_interest = sanitize_text_field($params['service_interest'] ?? '');
    $action_type = sanitize_key($params['action_type'] ?? 'view');
    $payload = isset($params['payload']) && is_array($params['payload']) ? $params['payload'] : array();

    if (!$visitor_key || !$session_key || $component_type === '') {
      return new WP_REST_Response(array('ok' => false, 'error' => 'missing_required'), 400);
    }

    list($visitor_id, $session_id) = $this->resolve_visitor_and_session($visitor_key, $session_key);
    if ($visitor_id <= 0 || $session_id <= 0) {
      return new WP_REST_Response(array('ok' => false, 'error' => 'session_resolution_failed'), 500);
    }

    $this->log_ai_interaction($visitor_id, $session_id, $component_type, $component_key, $page_url, $widget_name, $service_interest, $action_type, $payload);

    if ($service_interest !== '') {
      $this->log_event_row($visitor_id, $session_id, 'widget_engagement', array(
        'service' => $service_interest,
        'widget' => $widget_name,
        'component_type' => $component_type,
        'component_key' => $component_key,
        'action' => $action_type,
      ));
    }

    return new WP_REST_Response(array('ok' => true), 200);
  }

  public function handle_ai_client_log($request)
  {
    $this->ensure_runtime_tables();
    $params = $request->get_json_params();

    $visitor_key = sanitize_text_field($params['visitor_key'] ?? '');
    $session_key = sanitize_text_field($params['session_key'] ?? '');
    $source = sanitize_key($params['source'] ?? 'frontend');
    $level = sanitize_key($params['level'] ?? 'error');
    $message = sanitize_textarea_field($params['message'] ?? '');
    $endpoint = esc_url_raw($params['endpoint'] ?? '');
    $status = absint($params['status'] ?? 0);
    $page_url = esc_url_raw($params['page_url'] ?? '');
    $meta = isset($params['meta']) && is_array($params['meta']) ? $params['meta'] : array();

    if ($message === '') {
      return new WP_REST_Response(array('ok' => false, 'error' => 'message_required'), 400);
    }

    $visitor_id = 0;
    $session_id = 0;
    if ($visitor_key !== '' && $session_key !== '') {
      list($visitor_id, $session_id) = $this->resolve_visitor_and_session($visitor_key, $session_key);
    }

    $payload = array(
      'source' => $source,
      'level' => $level,
      'message' => $message,
      'endpoint' => $endpoint,
      'status' => $status,
      'page_url' => $page_url,
      'meta' => $meta,
    );
    $this->log_event_row($visitor_id, $session_id, 'frontend_ai_error', $payload);

    return new WP_REST_Response(array('ok' => true), 200);
  }

  public function handle_appointment_request($request)
  {
    global $wpdb;
    $this->ensure_runtime_tables();
    $params = $request->get_json_params();

    $visitor_key = sanitize_text_field($params['visitor_key'] ?? '');
    $session_key = sanitize_text_field($params['session_key'] ?? '');
    list($visitor_id, $session_id) = $this->resolve_visitor_and_session($visitor_key, $session_key);

    $name = sanitize_text_field($params['name'] ?? '');
    $email = sanitize_email($params['email'] ?? '');
    $phone = sanitize_text_field($params['phone'] ?? '');
    $country = sanitize_text_field($params['country'] ?? '');
    $travel_dates = sanitize_text_field($params['travel_dates'] ?? '');
    $party_size = sanitize_text_field($params['party_size'] ?? '');
    $service_interest = sanitize_text_field($params['service_interest'] ?? '');
    $vehicle_interest = sanitize_text_field($params['vehicle_interest'] ?? '');
    $destination_interest = sanitize_text_field($params['destination_interest'] ?? '');
    $preferred_time = sanitize_text_field($params['preferred_time'] ?? '');
    $source_page = esc_url_raw($params['source_page'] ?? '');
    $source_widget = sanitize_text_field($params['source_widget'] ?? '');
    $source_section = sanitize_text_field($params['source_section'] ?? '');
    $summary = sanitize_textarea_field($params['summary'] ?? '');
    $route = sanitize_key($params['route'] ?? 'appointment_request');

    if ($email === '' && $phone === '') {
      return new WP_REST_Response(array('ok' => false, 'error' => 'email_or_phone_required'), 400);
    }

    $leads = $wpdb->prefix . 'jacana_leads';
    $now = current_time('mysql');
    $existing = $this->get_latest_lead_by_email($email);
    if (!$existing && $visitor_id > 0) {
      $existing = $this->get_latest_lead_for_visitor($visitor_id);
    }

    $lead_data = array(
      'name' => $name,
      'email' => $email,
      'phone' => $phone,
      'country' => $country,
      'travel_dates' => $travel_dates,
      'party_size' => $party_size,
      'service_interest' => $service_interest !== '' ? $service_interest : $vehicle_interest,
      'source_page' => $source_page,
      'source' => 'ai_' . $route,
      'summary' => $summary,
      'interests' => implode(', ', array_filter(array($service_interest, $vehicle_interest, $destination_interest))),
      'updated_at' => $now,
    );

    if ($existing) {
      if ($visitor_id > 0 && empty($existing->visitor_id)) {
        $lead_data['visitor_id'] = $visitor_id;
      }
      $lead_data['status'] = 'qualified';
      $wpdb->update($leads, $lead_data, array('id' => (int) $existing->id));
      $lead_id = (int) $existing->id;
    } else {
      $lead_data['visitor_id'] = $visitor_id > 0 ? $visitor_id : null;
      $lead_data['priority'] = 'high';
      $lead_data['status'] = 'qualified';
      $lead_data['created_at'] = $now;
      $wpdb->insert($leads, $lead_data);
      $lead_id = (int) $wpdb->insert_id;
    }

    if ($lead_id <= 0) {
      return new WP_REST_Response(array('ok' => false, 'error' => 'lead_save_failed'), 500);
    }

    $increment_stage = $route === 'booking_form_handoff' ? 'booking_start' : 'lead_submit';
    $increment = $this->calculate_conversion_increment($increment_stage);
    if ($lead_id > 0) {
      $lead = $wpdb->get_row($wpdb->prepare("SELECT conversion_score FROM {$leads} WHERE id = %d", $lead_id));
      $score = min(100, (int) ($lead->conversion_score ?? 0) + $increment);
      $wpdb->update(
        $leads,
        array(
          'conversion_score' => $score,
          'priority' => $this->priority_from_score($score),
          'updated_at' => current_time('mysql'),
        ),
        array('id' => $lead_id)
      );
    }

    if ($visitor_id > 0 && $session_id > 0) {
      $this->log_ai_interaction($visitor_id, $session_id, 'booking_sheet', $route, $source_page, $source_widget, $service_interest, 'submit', array(
        'lead_id' => $lead_id,
        'vehicle_interest' => $vehicle_interest,
        'destination_interest' => $destination_interest,
        'preferred_time' => $preferred_time,
        'source_section' => $source_section,
      ));
      $this->log_event_row($visitor_id, $session_id, 'conversion_signal', array(
        'stage' => $increment_stage,
        'service' => $service_interest,
        'label' => $source_widget,
        'page_url' => $source_page,
        'meta' => array(
          'route' => $route,
          'vehicle_interest' => $vehicle_interest,
          'destination_interest' => $destination_interest,
        ),
      ));
    }

    if ($route !== 'booking_form_handoff') {
      $this->maybe_send_appointment_notifications(array(
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'service_interest' => $service_interest,
        'vehicle_interest' => $vehicle_interest,
        'destination_interest' => $destination_interest,
        'travel_dates' => $travel_dates,
        'party_size' => $party_size,
        'preferred_time' => $preferred_time,
        'source_page' => $source_page,
        'source_widget' => $source_widget,
        'source_section' => $source_section,
        'summary' => $summary,
        'route' => $route,
      ));
    }

    return new WP_REST_Response(array('ok' => true, 'lead_id' => $lead_id), 200);
  }

  public function handle_booking_request($request)
  {
    global $wpdb;
    $this->ensure_runtime_tables();
    $params = $request->get_json_params();

    $visitor_key = sanitize_text_field($params['visitor_key'] ?? '');
    $session_key = sanitize_text_field($params['session_key'] ?? '');
    list($visitor_id, $session_id) = $this->resolve_visitor_and_session($visitor_key, $session_key);

    $name = sanitize_text_field($params['name'] ?? '');
    $email = sanitize_email($params['email'] ?? '');
    $phone = sanitize_text_field($params['phone'] ?? '');
    $country = sanitize_text_field($params['country'] ?? '');
    $travel_dates = sanitize_text_field($params['travel_dates'] ?? '');
    $duration = sanitize_text_field($params['duration'] ?? '');
    $party_size = sanitize_text_field($params['party_size'] ?? '');
    $budget_tier = sanitize_text_field($params['budget_tier'] ?? '');
    $accommodation_style = sanitize_text_field($params['accommodation_style'] ?? '');
    $service_interest = sanitize_text_field($params['service_interest'] ?? '');
    $vehicle_interest = sanitize_text_field($params['vehicle_interest'] ?? '');
    $destination_interest = sanitize_text_field($params['destination_interest'] ?? '');
    $source_page = esc_url_raw($params['source_page'] ?? '');
    $source_widget = sanitize_text_field($params['source_widget'] ?? '');
    $source_section = sanitize_text_field($params['source_section'] ?? '');
    $selected_services = array_values(array_filter(array_map('sanitize_text_field', (array) ($params['selected_services'] ?? array()))));
    $additional_places = sanitize_textarea_field($params['additional_places'] ?? '');
    $message = sanitize_textarea_field($params['message'] ?? '');

    if ($service_interest === '') {
      if ($vehicle_interest !== '') {
        $service_interest = 'car_rental';
      }
      elseif ($destination_interest !== '') {
        $service_interest = 'tailor_made';
      }
      else {
        $service_interest = 'general_booking';
      }
    }

    if ($service_interest !== '' && !in_array($service_interest, $selected_services, true)) {
      array_unshift($selected_services, $service_interest);
    }

    if ($name === '' || ($email === '' && $phone === '')) {
      return new WP_REST_Response(array('ok' => false, 'error' => 'missing_required'), 400);
    }

    $lead_summary_parts = array_filter(array(
      $message,
      $additional_places !== '' ? sprintf(__('Additional places: %s', 'jacana-luxe'), $additional_places) : '',
      $source_section !== '' ? sprintf(__('CTA section: %s', 'jacana-luxe'), $source_section) : '',
    ));
    $lead_summary = implode("\n", $lead_summary_parts);
    $details_json = wp_json_encode(array(
      'selected_services' => $selected_services,
      'additional_places' => $additional_places,
      'source_section' => $source_section,
      'source_page' => $source_page,
      'source_widget' => $source_widget,
      'service_interest' => $service_interest,
      'vehicle_interest' => $vehicle_interest,
      'destination_interest' => $destination_interest,
      'submitted_from' => 'booking_modal',
    ));

    $lead_request = new class(array(
      'visitor_key' => $visitor_key,
      'session_key' => $session_key,
      'name' => $name,
      'email' => $email,
      'phone' => $phone,
      'country' => $country,
      'travel_dates' => $travel_dates,
      'duration' => $duration,
      'party_size' => $party_size,
      'budget_tier' => $budget_tier,
      'service_interest' => $service_interest,
      'source_page' => $source_page,
      'interests' => array_filter(array($service_interest, $vehicle_interest, $destination_interest)),
      'summary' => $lead_summary,
      'source' => 'booking_modal',
    )) {
      private $params;

      public function __construct($params)
      {
        $this->params = $params;
      }

      public function get_json_params()
      {
        return $this->params;
      }
    };
    $lead_response = $this->handle_lead($lead_request);
    $lead_data = $lead_response instanceof WP_REST_Response ? $lead_response->get_data() : array();
    $lead_id = (int) ($lead_data['lead_id'] ?? 0);

    $bookings = $wpdb->prefix . 'jacana_bookings';
    $now = current_time('mysql');
    $inserted = $wpdb->insert($bookings, array(
      'lead_id' => $lead_id > 0 ? $lead_id : null,
      'visitor_id' => $visitor_id > 0 ? $visitor_id : null,
      'service_interest' => $service_interest,
      'vehicle_interest' => $vehicle_interest,
      'destination_interest' => $destination_interest,
      'traveler_name' => $name,
      'traveler_email' => $email,
      'traveler_phone' => $phone,
      'country' => $country,
      'travel_dates' => $travel_dates,
      'duration' => $duration,
      'party_size' => $party_size,
      'budget_tier' => $budget_tier,
      'accommodation_style' => $accommodation_style,
      'selected_services' => !empty($selected_services) ? implode(', ', $selected_services) : '',
      'additional_places' => $additional_places,
      'message' => $message,
      'source_page' => $source_page,
      'source_widget' => $source_widget,
      'source_section' => $source_section,
      'details_json' => $details_json,
      'status' => 'new',
      'created_at' => $now,
      'updated_at' => $now,
    ));

    $booking_id = (int) $wpdb->insert_id;
    if (!$inserted || $booking_id <= 0) {
      return new WP_REST_Response(array('ok' => false, 'error' => 'booking_save_failed', 'message' => (string) $wpdb->last_error), 500);
    }

    if ($visitor_id > 0 && $session_id > 0) {
      $this->log_ai_interaction($visitor_id, $session_id, 'booking_modal', 'booking_request', $source_page, $source_widget, $service_interest, 'submit', array(
        'booking_id' => $booking_id,
        'vehicle_interest' => $vehicle_interest,
        'destination_interest' => $destination_interest,
        'selected_services' => $selected_services,
        'source_section' => $source_section,
      ));
      $this->log_event_row($visitor_id, $session_id, 'conversion_signal', array(
        'stage' => 'booking_start',
        'service' => $service_interest,
        'label' => $source_widget,
        'page_url' => $source_page,
        'meta' => array(
          'booking_id' => $booking_id,
          'vehicle_interest' => $vehicle_interest,
          'destination_interest' => $destination_interest,
          'selected_services' => $selected_services,
          'source_section' => $source_section,
        ),
      ));
    }

    $this->maybe_send_appointment_notifications(array(
      'name' => $name,
      'email' => $email,
      'phone' => $phone,
      'service_interest' => $service_interest,
      'vehicle_interest' => $vehicle_interest,
      'destination_interest' => $destination_interest,
      'travel_dates' => $travel_dates,
      'duration' => $duration,
      'party_size' => $party_size,
      'budget_tier' => $budget_tier,
      'accommodation_style' => $accommodation_style,
      'source_page' => $source_page,
      'source_widget' => $source_widget,
      'source_section' => $source_section,
      'selected_services' => $selected_services,
      'additional_places' => $additional_places,
      'summary' => $message,
      'route' => 'booking_request',
    ));

    return new WP_REST_Response(array('ok' => true, 'booking_id' => $booking_id, 'lead_id' => $lead_id), 200);
  }

  public function handle_reviews_submit($request)
  {
    global $wpdb;
    $this->ensure_runtime_tables();
    $params = $request->get_json_params();
    $name = sanitize_text_field($params['name'] ?? '');
    $email = sanitize_email($params['email'] ?? '');
    $country = sanitize_text_field($params['country'] ?? '');
    $trip_label = sanitize_text_field($params['trip_label'] ?? '');
    $service_interest = sanitize_text_field($params['service_interest'] ?? '');
    $rating = max(1, min(5, absint($params['rating'] ?? 5)));
    $review_text = sanitize_textarea_field($params['review_text'] ?? '');
    $source = sanitize_text_field($params['source'] ?? 'website');
    $consent_public = !empty($params['consent_public']) ? 1 : 0;

    if ($name === '' || $review_text === '') {
      return new WP_REST_Response(array('ok' => false, 'error' => 'missing_required'), 400);
    }

    $table = $wpdb->prefix . 'jacana_reviews';
    $now = current_time('mysql');
    $inserted = $wpdb->insert($table, array(
      'name' => $name,
      'email' => $email,
      'country' => $country,
      'trip_label' => $trip_label,
      'service_interest' => $service_interest,
      'rating' => $rating,
      'review_text' => $review_text,
      'source' => $source,
      'status' => 'pending',
      'consent_public' => $consent_public,
      'created_at' => $now,
      'updated_at' => $now,
    ));

    if (!$inserted || (int) $wpdb->insert_id <= 0) {
      return new WP_REST_Response(array('ok' => false, 'error' => 'review_save_failed', 'message' => (string) $wpdb->last_error), 500);
    }

    return new WP_REST_Response(array('ok' => true, 'review_id' => (int) $wpdb->insert_id), 200);
  }

  public function handle_review_delete($request)
  {
    $id = absint($request->get_param('id'));
    if ($id <= 0) {
      return new WP_REST_Response(array('ok' => false, 'error' => 'invalid_id'), 400);
    }

    $deleted = $this->delete_review($id);
    return new WP_REST_Response(array('ok' => $deleted), $deleted ? 200 : 500);
  }

  private function delete_review($id)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_reviews';
    return (bool) $wpdb->delete($table, array('id' => absint($id)), array('%d'));
  }

  public function handle_reviews_get($request)
  {
    $service_interest = sanitize_text_field($request->get_param('service_interest') ?? '');
    $limit = max(1, min(100, absint($request->get_param('limit') ?? 8)));
    $offset = absint($request->get_param('offset') ?? 0);
    $status = $request->get_param('status');
    $statuses = $status ? array_map('sanitize_key', (array) $status) : array('approved', 'featured');
    $rows = $this->get_reviews(array(
      'status' => $statuses,
      'service_interest' => $service_interest,
      'limit' => $limit,
      'offset' => $offset,
    ));

    $items = array_map(static function ($row) {
      return array(
        'id' => (int) $row->id,
        'name' => (string) $row->name,
        'country' => (string) $row->country,
        'trip_label' => (string) $row->trip_label,
        'service_interest' => (string) $row->service_interest,
        'rating' => (int) $row->rating,
        'review_text' => (string) $row->review_text,
        'status' => (string) $row->status,
      );
    }, (array) $rows);

    return new WP_REST_Response(array(
      'ok' => true,
      'items' => array_values($items),
      'count' => count($items),
      'has_more' => count($items) >= $limit
    ), 200);
  }

  public function handle_reviews_stats($request)
  {
    global $wpdb;
    $table = $wpdb->prefix . 'jacana_reviews';
    $service = sanitize_text_field($request->get_param('service_interest') ?? '');

    $where = "status IN ('approved', 'featured')";
    $params = array();
    if ($service) {
      $where .= " AND service_interest = %s";
      $params[] = $service;
    }

    $stats = $wpdb->get_row($wpdb->prepare(
      "SELECT COUNT(*) as total, AVG(rating) as average FROM {$table} WHERE {$where}",
      $params
    ));

    $breakdown = array();
    for ($i = 5; $i >= 1; $i--) {
      $count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE {$where} AND rating = %d",
        array_merge($params, array($i))
      ));
      $breakdown[$i] = $count;
    }

    return new WP_REST_Response(array(
      'ok' => true,
      'total' => (int) ($stats->total ?? 0),
      'average' => round((float) ($stats->average ?? 0), 1),
      'breakdown' => $breakdown
    ), 200);
  }

  public function handle_profile($request)
  {
    global $wpdb;
    $visitor_key = sanitize_text_field($request->get_param('visitor_key') ?? '');
    $session_key = sanitize_text_field($request->get_param('session_key') ?? '');
    $profile = array(
      'persona' => 'observer',
      'intent' => 0,
      'service_focus' => array(),
      'recent_page' => '',
      'greeting' => __('Welcome back. Ready to continue planning your Namibia journey?', 'jacana-luxe'),
      'missing_fields' => array('name', 'email_or_phone', 'travel_dates', 'service_interest'),
    );

    if (!$visitor_key) {
      return new WP_REST_Response(array('ok' => true, 'profile' => $profile), 200);
    }

    $visitors = $wpdb->prefix . 'jacana_visitors';
    $events = $wpdb->prefix . 'jacana_events';
    $sessions = $wpdb->prefix . 'jacana_sessions';
    $pageviews = $wpdb->prefix . 'jacana_pageviews';

    $visitor = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$visitors} WHERE visitor_key = %s", $visitor_key));
    if (!$visitor) {
      return new WP_REST_Response(array('ok' => true, 'profile' => $profile), 200);
    }

    $visitor_id = (int) $visitor->id;
    $session_id = 0;
    if ($session_key) {
      $session = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$sessions} WHERE session_key = %s AND visitor_id = %d", $session_key, $visitor_id));
      if ($session) {
        $session_id = (int) $session->id;
      }
    }

    $lead = $this->get_latest_lead_for_visitor($visitor_id);
    if ($lead && !empty($lead->name)) {
      $profile['greeting'] = sprintf(__('Welcome back %s. We can continue refining your itinerary.', 'jacana-luxe'), $lead->name);
    }

    if ($lead && !empty($lead->service_interest)) {
      $profile['service_focus'][] = $lead->service_interest;
    }

    $event_rows = $wpdb->get_results($wpdb->prepare("SELECT type, payload FROM {$events} WHERE visitor_id = %d ORDER BY created_at DESC LIMIT 80", $visitor_id));
    $services = array();
    foreach ((array) $event_rows as $event) {
      $payload = json_decode((string) $event->payload, true);
      if (!is_array($payload)) {
        continue;
      }
      if ($event->type === 'persona_intent') {
        if (isset($payload['persona'])) {
          $profile['persona'] = sanitize_key((string) $payload['persona']);
        }
        if (isset($payload['intent'])) {
          $profile['intent'] = max($profile['intent'], absint($payload['intent']));
        }
      }
      if ($event->type === 'conversion_signal' || $event->type === 'widget_engagement') {
        $service = sanitize_text_field((string) ($payload['service'] ?? ''));
        if ($service !== '') {
          $services[] = $service;
        }
      }
    }

    if (!empty($services)) {
      $services = array_values(array_unique($services));
      $profile['service_focus'] = array_slice($services, 0, 3);
    }

    if ($session_id > 0) {
      $recent_page = $wpdb->get_var($wpdb->prepare("SELECT url FROM {$pageviews} WHERE session_id = %d ORDER BY started_at DESC LIMIT 1", $session_id));
    } else {
      $recent_page = $wpdb->get_var($wpdb->prepare("SELECT p.url
        FROM {$pageviews} p
        INNER JOIN {$sessions} s ON s.id = p.session_id
        WHERE s.visitor_id = %d
        ORDER BY p.started_at DESC LIMIT 1", $visitor_id));
    }
    $profile['recent_page'] = esc_url_raw((string) $recent_page);

    $missing = array();
    if (!$lead || empty($lead->name)) {
      $missing[] = 'name';
    }
    if (!$lead || (empty($lead->email) && empty($lead->phone))) {
      $missing[] = 'email_or_phone';
    }
    if (!$lead || empty($lead->travel_dates)) {
      $missing[] = 'travel_dates';
    }
    if (!$lead || empty($lead->service_interest)) {
      $missing[] = 'service_interest';
    }
    $profile['missing_fields'] = $missing;
    $profile['lead'] = $lead ? array(
      'name' => (string) $lead->name,
      'email' => (string) $lead->email,
      'phone' => (string) $lead->phone,
      'travel_dates' => (string) $lead->travel_dates,
      'party_size' => (string) $lead->party_size,
      'service_interest' => (string) $lead->service_interest,
      'conversion_score' => (int) $lead->conversion_score,
    ) : null;

    return new WP_REST_Response(array('ok' => true, 'profile' => $profile), 200);
  }

  public function handle_track($request)
  {
    global $wpdb;
    $params = $request->get_json_params();

    $visitor_key = sanitize_text_field($params['visitor_key'] ?? '');
    $session_key = sanitize_text_field($params['session_key'] ?? '');
    $event_type = sanitize_key($params['type'] ?? '');

    if (!$visitor_key || !$session_key || !$event_type) {
      return new WP_REST_Response(array('ok' => false), 400);
    }

    list($visitor_id, $session_id) = $this->resolve_visitor_and_session($visitor_key, $session_key);
    if ($visitor_id <= 0 || $session_id <= 0) {
      return new WP_REST_Response(array('ok' => false, 'error' => 'session_resolution_failed'), 500);
    }

    $payload = isset($params['payload']) && is_array($params['payload']) ? $params['payload'] : array();
    $this->log_event_row($visitor_id, $session_id, $event_type, $payload);

    if ($event_type === 'pageview_end') {
      $pageviews = $wpdb->prefix . 'jacana_pageviews';
      $duration = absint($payload['duration_sec'] ?? 0);
      $wpdb->insert(
        $pageviews,
        array(
        'session_id' => $session_id,
        'url' => esc_url_raw($payload['url'] ?? ''),
        'title' => sanitize_text_field($payload['title'] ?? ''),
        'referrer' => esc_url_raw($payload['referrer'] ?? ''),
        'started_at' => sanitize_text_field($payload['started_at'] ?? current_time('mysql')),
        'duration_sec' => $duration,
      )
      );

      $sessions = $wpdb->prefix . 'jacana_sessions';
      $wpdb->query($wpdb->prepare("UPDATE {$sessions} SET total_time_sec = total_time_sec + %d, pageviews = pageviews + 1 WHERE id = %d", $duration, $session_id));
      $visitors = $wpdb->prefix . 'jacana_visitors';
      $wpdb->query($wpdb->prepare("UPDATE {$visitors} SET total_time_sec = total_time_sec + %d WHERE id = %d", $duration, $visitor_id));
    }

    return new WP_REST_Response(array('ok' => true, 'visitor_id' => $visitor_id, 'session_id' => $session_id), 200);
  }

  public function handle_chat($request)
  {
    global $wpdb;
    $params = $request->get_json_params();

    $visitor_key = sanitize_text_field($params['visitor_key'] ?? '');
    $session_key = sanitize_text_field($params['session_key'] ?? '');
    $role = sanitize_key($params['role'] ?? '');
    $content_raw = $params['content'] ?? '';
    $content = is_string($content_raw) ? trim(wp_check_invalid_utf8($content_raw)) : '';
    if ($content === '' && is_array($content_raw)) {
      $content = wp_json_encode($content_raw);
    }

    if (!$visitor_key || !$session_key || !$role || !$content) {
      return new WP_REST_Response(array('ok' => false), 400);
    }

    if ($role === 'user') {
      $content = sanitize_textarea_field($content);
    }

    list($visitor_id, $session_id) = $this->resolve_visitor_and_session($visitor_key, $session_key);
    if ($visitor_id <= 0 || $session_id <= 0) {
      return new WP_REST_Response(array('ok' => false, 'error' => 'session_resolution_failed'), 500);
    }

    $messages = $wpdb->prefix . 'jacana_chat_messages';
    $wpdb->insert(
      $messages,
      array(
      'visitor_id' => $visitor_id,
      'session_id' => $session_id,
      'role' => $role,
      'content' => $content,
      'created_at' => current_time('mysql'),
    )
    );

    return new WP_REST_Response(array('ok' => true), 200);
  }

  public function handle_chat_history($request)
  {
    global $wpdb;
    $visitor_key = sanitize_text_field($request->get_param('visitor_key') ?? '');
    $session_key = sanitize_text_field($request->get_param('session_key') ?? '');
    if (!$visitor_key) {
      return new WP_REST_Response(array('ok' => false, 'error' => 'visitor_key_required'), 400);
    }
    $visitors = $wpdb->prefix . 'jacana_visitors';
    $messages = $wpdb->prefix . 'jacana_chat_messages';

    $visitor = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$visitors} WHERE visitor_key = %s", $visitor_key));
    if (!$visitor) {
      return new WP_REST_Response(array('ok' => true, 'messages' => array()), 200);
    }

    if ($session_key) {
      $sessions = $wpdb->prefix . 'jacana_sessions';
      $session = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$sessions} WHERE session_key = %s AND visitor_id = %d", $session_key, $visitor->id));
      if (!$session) {
        return new WP_REST_Response(array('ok' => true, 'messages' => array()), 200);
      }
      $rows = $wpdb->get_results($wpdb->prepare("SELECT role, content, created_at FROM {$messages} WHERE visitor_id = %d AND session_id = %d ORDER BY created_at ASC LIMIT 200", $visitor->id, $session->id));
    }
    else {
      $rows = $wpdb->get_results($wpdb->prepare("SELECT role, content, created_at FROM {$messages} WHERE visitor_id = %d ORDER BY created_at ASC LIMIT 200", $visitor->id));
    }
    return new WP_REST_Response(array('ok' => true, 'messages' => $rows), 200);
  }

  public function handle_chat_sessions($request)
  {
    global $wpdb;
    $visitor_key = sanitize_text_field($request->get_param('visitor_key') ?? '');
    if (!$visitor_key) {
      return new WP_REST_Response(array('ok' => false, 'error' => 'visitor_key_required'), 400);
    }
    $visitors = $wpdb->prefix . 'jacana_visitors';
    $sessions = $wpdb->prefix . 'jacana_sessions';
    $messages = $wpdb->prefix . 'jacana_chat_messages';

    $visitor = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$visitors} WHERE visitor_key = %s", $visitor_key));
    if (!$visitor) {
      return new WP_REST_Response(array('ok' => true, 'sessions' => array()), 200);
    }

    $rows = $wpdb->get_results($wpdb->prepare(
      "SELECT s.session_key, s.label, s.started_at, s.last_seen, s.pageviews, s.total_time_sec,
        (SELECT m.content FROM {$messages} m WHERE m.session_id = s.id AND m.role = 'user' ORDER BY m.created_at ASC LIMIT 1) AS first_message
       FROM {$sessions} s
       WHERE s.visitor_id = %d
       ORDER BY s.started_at DESC
       LIMIT 20",
      $visitor->id
    ));
    return new WP_REST_Response(array('ok' => true, 'sessions' => $rows), 200);
  }

  public function handle_chat_session_label($request)
  {
    global $wpdb;
    $params = $request->get_json_params();
    $visitor_key = sanitize_text_field($params['visitor_key'] ?? '');
    $session_key = sanitize_text_field($params['session_key'] ?? '');
    $label = sanitize_text_field($params['label'] ?? '');

    if (!$visitor_key || !$session_key) {
      return new WP_REST_Response(array('ok' => false, 'error' => 'missing_required'), 400);
    }

    $visitors = $wpdb->prefix . 'jacana_visitors';
    $sessions = $wpdb->prefix . 'jacana_sessions';
    $visitor = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$visitors} WHERE visitor_key = %s", $visitor_key));
    if (!$visitor) {
      return new WP_REST_Response(array('ok' => false, 'error' => 'visitor_not_found'), 404);
    }

    $session = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$sessions} WHERE session_key = %s AND visitor_id = %d", $session_key, $visitor->id));
    if (!$session) {
      return new WP_REST_Response(array('ok' => false, 'error' => 'session_not_found'), 404);
    }

    $wpdb->update(
      $sessions,
      array('label' => $label),
      array('id' => $session->id)
    );

    return new WP_REST_Response(array('ok' => true), 200);
  }

  private function get_gemini_models_to_try()
  {
    $configured = sanitize_text_field((string) get_option('jacana_gemini_model', 'gemini-3.1-pro-preview'));
    $models = array_filter(array_unique(array(
      $configured ?: 'gemini-3.1-pro-preview',
      'gemini-3.1-pro-preview',
      'gemini-2.5-flash',
    )));
    return array_values($models);
  }

  public function handle_ai($request)
  {
    $params = $request->get_json_params();
    $prompt = sanitize_textarea_field($params['prompt'] ?? '');
    if (!$prompt) {
      error_log('[Jacana AI] Missing prompt.');
      return new WP_REST_Response(array('ok' => false, 'error' => 'prompt_required'), 400);
    }
    $result = $this->execute_gemini_request($prompt, true, true);
    if (is_wp_error($result)) {
      $debug = (array) $result->get_error_data();
      $last_error = (string) ($debug['last_error'] ?? $result->get_error_message());
      $last_body = (string) ($debug['last_body'] ?? '');
      error_log('[Jacana AI] Request failed: ' . $last_error . ' | body: ' . substr($last_body, 0, 800));
      return new WP_REST_Response(array(
        'ok' => false,
        'error' => $result->get_error_code() === 'missing_api_key' ? 'missing_api_key' : 'ai_request_failed',
        'msg' => $last_error,
        'body' => substr($last_body, 0, 1200),
        'attempts' => (array) ($debug['attempts'] ?? array()),
      ), $result->get_error_code() === 'missing_api_key' ? 400 : 500);
    }

    return new WP_REST_Response(array(
      'ok' => true,
      'model' => (string) ($result['model'] ?? ''),
      'data' => (array) ($result['data'] ?? array())
    ), 200);
  }
}

new Jacana_CRM();
