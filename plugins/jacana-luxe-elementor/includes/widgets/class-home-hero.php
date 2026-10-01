<?php
if (!defined('ABSPATH')) {
    exit;
}

class Jacana_Luxe_Home_Hero extends \Elementor\Widget_Base {

    public function get_name() {
        return 'jacana_home_hero';
    }

    public function get_title() {
        return __('Home Hero', 'jacana-luxe');
    }

    public function get_icon() {
        return 'eicon-banner';
    }

    public function get_categories() {
        return ['jacana-luxe'];
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', [
            'label' => __('Content', 'jacana-luxe'),
        ]);

        $this->add_control('kicker', [
            'label'   => __('Kicker', 'jacana-luxe'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Namibia Specialists', 'jacana-luxe'),
        ]);

        $this->add_control('title', [
            'label'       => __('Main Title', 'jacana-luxe'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => __('Where every journey writes its own story', 'jacana-luxe'),
        ]);

        $this->add_control('copy', [
            'label'   => __('Supporting Text', 'jacana-luxe'),
            'type'    => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __('Tailor-made safaris, self-drive adventures and curated stays — crafted around your sense of wonder, your pace, and your Namibia.', 'jacana-luxe'),
        ]);

        $this->add_control('cta_label', [
            'label'   => __('Primary CTA Label', 'jacana-luxe'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Plan my journey', 'jacana-luxe'),
        ]);

        $this->add_control('cta_link', [
            'label'   => __('Primary CTA Link', 'jacana-luxe'),
            'type'    => \Elementor\Controls_Manager::URL,
            'default' => ['url' => '/contact/'],
        ]);

        $this->add_control('cta_secondary_label', [
            'label'   => __('Secondary CTA (text link)', 'jacana-luxe'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Explore destinations', 'jacana-luxe'),
        ]);

        $this->add_control('cta_secondary_link', [
            'label'   => __('Secondary CTA Link', 'jacana-luxe'),
            'type'    => \Elementor\Controls_Manager::URL,
            'default' => ['url' => '/destinations/'],
        ]);

        $this->add_control('background', [
            'label'       => __('Poster / Fallback Image', 'jacana-luxe'),
            'type'        => \Elementor\Controls_Manager::MEDIA,
        ]);

        $this->add_control('video_url', [
            'label'       => __('Background Video URL (.mp4)', 'jacana-luxe'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'https://example.com/namibia-reel.mp4',
        ]);

        // AI Concierge
        $this->add_control('ai_sep', [
            'label'     => __('AI Concierge', 'jacana-luxe'),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('show_ai_pill', [
            'label'        => __('Show AI Concierge Pill', 'jacana-luxe'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('ai_pill_label', [
            'label'     => __('AI Pill Label', 'jacana-luxe'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Ask our AI', 'jacana-luxe'),
            'condition' => ['show_ai_pill' => 'yes'],
        ]);

        // Stats (now horizontal)
        $this->add_control('stats_heading', [
            'label'     => __('Trust Stats', 'jacana-luxe'),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $repeater = new \Elementor\Repeater();
        $repeater->add_control('stat_number', [
            'label'       => __('Number', 'jacana-luxe'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => '8+',
        ]);
        $repeater->add_control('stat_label', [
            'label'       => __('Label', 'jacana-luxe'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'Years',
        ]);

        $this->add_control('stats', [
            'label'       => __('Stats (horizontal bar)', 'jacana-luxe'),
            'type'        => \Elementor\Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'default'     => [
                ['stat_number' => '8+',   'stat_label' => 'Years'],
                ['stat_number' => '500+', 'stat_label' => 'Journeys'],
                ['stat_number' => '100%', 'stat_label' => 'Tailor-made'],
            ],
            'title_field' => '{{{ stat_number }}} {{{ stat_label }}}',
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $bg_image = !empty($settings['background']['url']) ? $settings['background']['url'] : '';
        $video_url = !empty($settings['video_url']) ? trim($settings['video_url']) : '';

        $primary_url = !empty($settings['cta_link']['url']) ? $settings['cta_link']['url'] : '';
        $primary_ext = !empty($settings['cta_link']['is_external']);
        $secondary_url = !empty($settings['cta_secondary_link']['url']) ? $settings['cta_secondary_link']['url'] : '';
        $secondary_ext = !empty($settings['cta_secondary_link']['is_external']);

        ?>
        <section class="jacana-hh-horizon">
            <!-- Video / image background -->
            <?php if ($video_url) : ?>
                <video class="jacana-hh-bg-media"
                       src="<?php echo esc_url($video_url); ?>"
                       <?php echo $bg_image ? 'poster="' . esc_url($bg_image) . '"' : ''; ?>
                       autoplay muted loop playsinline preload="metadata"
                       aria-hidden="true"></video>
            <?php elseif ($bg_image) : ?>
                <div class="jacana-hh-bg-media" style="background-image: url('<?php echo esc_url($bg_image); ?>');" aria-hidden="true"></div>
            <?php endif; ?>

            <!-- Clean, minimal overlay (dark but no colour cast) -->
            <div class="jacana-hh-overlay" aria-hidden="true"></div>

            <!-- Oshiwambo colour overlay -->
            <div class="jacana-hh-oshi-overlay" aria-hidden="true"></div>

            <!-- AI pill (top right, integrated) -->
            <?php if ('yes' === ($settings['show_ai_pill'] ?? '') && !empty($settings['ai_pill_label'])) : ?>
                <button class="jacana-hh-ai-pill" type="button"
                        aria-label="<?php echo esc_attr($settings['ai_pill_label']); ?>"
                        data-jacana-ai-flow="planner">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 2 13.8 8.8 20.5 10 13.8 11.2 12 18 10.2 11.2 3.5 10 10.2 8.8Z" fill="currentColor" stroke="none"/>
                    </svg>
                    <span><?php echo esc_html($settings['ai_pill_label']); ?></span>
                </button>
            <?php endif; ?>

            <!-- Main content – centered -->
            <div class="jacana-hh-center">
                <?php if (!empty($settings['kicker'])) : ?>
                    <div class="jacana-hh-kicker"><?php echo esc_html($settings['kicker']); ?></div>
                <?php endif; ?>

                <h1 class="jacana-hh-title"><?php echo esc_html($settings['title'] ?? ''); ?></h1>

                <?php if (!empty($settings['copy'])) : ?>
                    <p class="jacana-hh-copy"><?php echo esc_html($settings['copy']); ?></p>
                <?php endif; ?>

                <div class="jacana-hh-actions">
                    <?php if (!empty($settings['cta_label']) && $primary_url) : ?>
                        <a class="jacana-hh-cta-primary" href="<?php echo esc_url($primary_url); ?>"
                           <?php echo $primary_ext ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                            <?php echo esc_html($settings['cta_label']); ?>
                            <span aria-hidden="true">→</span>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($settings['cta_secondary_label']) && $secondary_url) : ?>
                        <a class="jacana-hh-cta-secondary" href="<?php echo esc_url($secondary_url); ?>"
                           <?php echo $secondary_ext ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                            <?php echo esc_html($settings['cta_secondary_label']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Horizontal stats bar (floating) -->
            <?php if (!empty($settings['stats'])) : ?>
                <div class="jacana-hh-stats-bar">
                    <?php 
                    $stats = is_array($settings['stats']) ? $settings['stats'] : [];
                    foreach ($stats as $stat) : 
                        if (empty($stat['stat_number']) && empty($stat['stat_label'])) continue;
                    ?>
                        <div class="jacana-hh-stat-item">
                            <span class="jacana-hh-stat-num"><?php echo esc_html($stat['stat_number'] ?? ''); ?></span>
                            <span class="jacana-hh-stat-label"><?php echo esc_html($stat['stat_label'] ?? ''); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Minimal scroll hint -->
            <div class="jacana-hh-scroll" aria-hidden="true">
                <div class="jacana-hh-scroll-line"></div>
            </div>

            <!-- Cultural stripe -->
            <div class="jacana-hh-stripe" aria-hidden="true"></div>
        </section>
        <?php
    }
}