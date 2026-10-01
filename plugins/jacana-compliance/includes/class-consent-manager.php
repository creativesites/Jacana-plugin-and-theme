<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Jacana_Consent_Manager {

    public static function render_banner() {
        ?>
        <div class="jacana-cb-shell" id="jacana-cb-shell" aria-live="polite" hidden>

            <!-- Main Banner -->
            <div class="jacana-cb-banner" role="region" aria-label="<?php esc_attr_e( 'Cookie consent', 'jacana-compliance' ); ?>">
                <div class="jacana-cb-banner-inner">
                    <div class="jacana-cb-banner-text">
                        <strong class="jacana-cb-banner-title"><?php esc_html_e( 'We use cookies', 'jacana-compliance' ); ?></strong>
                        <span class="jacana-cb-banner-desc"><?php esc_html_e( 'Functional cookies are always on. With your consent, we also use analytics cookies to improve our site. No data is sold.', 'jacana-compliance' ); ?></span>
                    </div>
                    <div class="jacana-cb-banner-actions">
                        <button type="button" class="jacana-cb-btn jacana-cb-btn--ghost" id="jacana-cb-manage">
                            <?php esc_html_e( 'Manage preferences', 'jacana-compliance' ); ?>
                        </button>
                        <button type="button" class="jacana-cb-btn jacana-cb-btn--reject" id="jacana-cb-reject">
                            <?php esc_html_e( 'Reject non-essential', 'jacana-compliance' ); ?>
                        </button>
                        <button type="button" class="jacana-cb-btn jacana-cb-btn--accept" id="jacana-cb-accept">
                            <?php esc_html_e( 'Accept all', 'jacana-compliance' ); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Preferences Panel -->
            <div class="jacana-cb-prefs" id="jacana-cb-prefs" hidden role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Cookie preferences', 'jacana-compliance' ); ?>">
                <div class="jacana-cb-prefs-inner">
                    <button type="button" class="jacana-cb-prefs-close" id="jacana-cb-prefs-close" aria-label="<?php esc_attr_e( 'Close preferences', 'jacana-compliance' ); ?>">
                        <span aria-hidden="true">×</span>
                    </button>

                    <div class="jacana-cb-prefs-kicker"><?php esc_html_e( 'Cookie Preferences', 'jacana-compliance' ); ?></div>
                    <h2 class="jacana-cb-prefs-title"><?php esc_html_e( 'Choose what to allow', 'jacana-compliance' ); ?></h2>
                    <p class="jacana-cb-prefs-desc"><?php esc_html_e( 'Functional cookies are always active. You can opt in or out of the categories below.', 'jacana-compliance' ); ?></p>

                    <div class="jacana-cb-prefs-list">

                        <!-- Functional — always on -->
                        <div class="jacana-cb-pref-row">
                            <div class="jacana-cb-pref-info">
                                <strong><?php esc_html_e( 'Functional', 'jacana-compliance' ); ?></strong>
                                <p><?php esc_html_e( 'Required for the website to function. Cannot be disabled.', 'jacana-compliance' ); ?></p>
                            </div>
                            <div class="jacana-cb-toggle jacana-cb-toggle--locked" aria-label="<?php esc_attr_e( 'Functional cookies — always on', 'jacana-compliance' ); ?>">
                                <span class="jacana-cb-toggle-track is-on">
                                    <span class="jacana-cb-toggle-thumb"></span>
                                </span>
                                <span class="jacana-cb-toggle-label"><?php esc_html_e( 'Always on', 'jacana-compliance' ); ?></span>
                            </div>
                        </div>

                        <!-- Analytics -->
                        <div class="jacana-cb-pref-row">
                            <div class="jacana-cb-pref-info">
                                <strong><?php esc_html_e( 'Analytics', 'jacana-compliance' ); ?></strong>
                                <p><?php esc_html_e( 'Help us understand site usage. Data is anonymised and shared with Google Analytics.', 'jacana-compliance' ); ?></p>
                            </div>
                            <label class="jacana-cb-toggle" aria-label="<?php esc_attr_e( 'Analytics cookies', 'jacana-compliance' ); ?>">
                                <input type="checkbox" class="jacana-cb-toggle-input" id="jacana-cb-analytics" name="analytics">
                                <span class="jacana-cb-toggle-track">
                                    <span class="jacana-cb-toggle-thumb"></span>
                                </span>
                                <span class="jacana-cb-toggle-label jacana-cb-toggle-label--state"></span>
                            </label>
                        </div>

                        <!-- Marketing -->
                        <div class="jacana-cb-pref-row">
                            <div class="jacana-cb-pref-info">
                                <strong><?php esc_html_e( 'Marketing', 'jacana-compliance' ); ?></strong>
                                <p><?php esc_html_e( 'Allow third-party advertising platforms to show you relevant ads based on your visit.', 'jacana-compliance' ); ?></p>
                            </div>
                            <label class="jacana-cb-toggle" aria-label="<?php esc_attr_e( 'Marketing cookies', 'jacana-compliance' ); ?>">
                                <input type="checkbox" class="jacana-cb-toggle-input" id="jacana-cb-marketing" name="marketing">
                                <span class="jacana-cb-toggle-track">
                                    <span class="jacana-cb-toggle-thumb"></span>
                                </span>
                                <span class="jacana-cb-toggle-label jacana-cb-toggle-label--state"></span>
                            </label>
                        </div>

                    </div>

                    <div class="jacana-cb-prefs-footer">
                        <button type="button" class="jacana-cb-btn jacana-cb-btn--accept" id="jacana-cb-save-prefs">
                            <?php esc_html_e( 'Save my preferences', 'jacana-compliance' ); ?>
                        </button>
                        <button type="button" class="jacana-cb-btn jacana-cb-btn--ghost" id="jacana-cb-accept-all-prefs">
                            <?php esc_html_e( 'Accept all', 'jacana-compliance' ); ?>
                        </button>
                    </div>
                </div>
            </div>

        </div>
        <?php
    }
}
