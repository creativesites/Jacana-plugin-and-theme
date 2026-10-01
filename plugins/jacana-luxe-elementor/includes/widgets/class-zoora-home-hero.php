<?php
if (!defined('ABSPATH')) {
    exit;
}

class Jacana_Luxe_Zoora_Home_Hero extends \Elementor\Widget_Base {

    public function get_name() {
        return 'jacana_zoora_home_hero';
    }

    public function get_title() {
        return __('Zoora Home Hero (Split)', 'jacana-luxe');
    }

    public function get_icon() {
        return 'eicon-columns';
    }

    public function get_categories() {
        return ['jacana-luxe'];
    }

	public function get_style_depends() {
		return ['jacana-luxe-widget-zoora-home-hero'];
	}

    protected function register_controls() {
        $this->start_controls_section('content_section', [
            'label' => __('Content', 'jacana-luxe'),
        ]);

        $this->add_control('title', [
            'label'       => __('Main Title (H1)', 'jacana-luxe'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => __('Tailor-Made Safaris Across Namibia', 'jacana-luxe'),
			'label_block' => true,
        ]);

        $this->add_control('subtitle', [
            'label'   => __('Subtitle / Description (H4)', 'jacana-luxe'),
            'type'    => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __('Private guided and self-drive journeys, crafted around your pace, style, and budget.', 'jacana-luxe'),
        ]);

        $this->add_control('cta_label', [
            'label'   => __('Button Label', 'jacana-luxe'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Plan Your Journey', 'jacana-luxe'),
        ]);

        $this->add_control('cta_link', [
            'label'   => __('Button Link', 'jacana-luxe'),
            'type'    => \Elementor\Controls_Manager::URL,
            'default' => ['url' => '/booking'],
        ]);

        $this->add_control('hero_image', [
            'label'       => __('Hero Image', 'jacana-luxe'),
            'type'        => \Elementor\Controls_Manager::MEDIA,
            'default'     => [
				'url' => \Elementor\Utils::get_placeholder_image_src(),
			],
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $title = !empty($settings['title']) ? $settings['title'] : '';
        $subtitle = !empty($settings['subtitle']) ? $settings['subtitle'] : '';
        $cta_label = !empty($settings['cta_label']) ? $settings['cta_label'] : '';
        $cta_url = !empty($settings['cta_link']['url']) ? $settings['cta_link']['url'] : '';
        
        $image_html = '';
        if (!empty($settings['hero_image']['id'])) {
            $image_html = wp_get_attachment_image($settings['hero_image']['id'], 'full');
        } elseif (!empty($settings['hero_image']['url'])) {
            $image_html = '<img src="' . esc_url($settings['hero_image']['url']) . '" alt="" loading="lazy"/>';
        }

        $this->add_render_attribute('wrapper', 'class', ['jacana-zoora-home-hero', 'e-con-full', 'e-flex', 'e-con', 'e-parent']);
        $this->add_render_attribute('left_col', 'class', ['jacana-z-hero-content', 'e-con-full', 'e-flex', 'e-con', 'e-child']);
        $this->add_render_attribute('right_col', 'class', ['jacana-z-hero-image', 'e-con-full', 'e-flex', 'e-con', 'e-child']);
        ?>
        <div <?php $this->print_render_attribute_string('wrapper'); ?>>
            <div <?php $this->print_render_attribute_string('left_col'); ?>>
                <?php if ($title) : ?>
                    <div class="elementor-element elementor-widget elementor-widget-heading">
                        <h1 class="elementor-heading-title elementor-size-default"><?php echo wp_kses_post($title); ?></h1>
                    </div>
                <?php endif; ?>
                
                <?php if ($subtitle) : ?>
                    <div class="elementor-element elementor-widget elementor-widget-heading">
                        <h4 class="elementor-heading-title elementor-size-default"><?php echo wp_kses_post($subtitle); ?></h4>
                    </div>
                <?php endif; ?>
                
                <?php if ($cta_label && $cta_url) : 
                    $target = $settings['cta_link']['is_external'] ? ' target="_blank"' : '';
                    $nofollow = $settings['cta_link']['nofollow'] ? ' rel="nofollow"' : '';
                ?>
                    <div class="elementor-element elementor-widget elementor-widget-button jacana-z-hero-button">
                        <a class="elementor-button elementor-button-link elementor-size-sm" href="<?php echo esc_url($cta_url); ?>" <?php echo $target . $nofollow; ?>>
                            <span class="elementor-button-content-wrapper">
                                <span class="elementor-button-text"><?php echo esc_html($cta_label); ?></span>
                            </span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div <?php $this->print_render_attribute_string('right_col'); ?>>
                <div class="elementor-element elementor-widget elementor-widget-image">
                    <?php echo $image_html; // phpcs:ignore ?>
                </div>
            </div>
        </div>
        <?php
    }
}
