<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Affiliations extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_affiliations';
  }

  public function get_title() {
    return __('Organisations & Affiliations', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-sitemap';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label'   => __('Heading', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Organisations that we are affiliated to and endorse our good work', 'jacana-luxe'),
    ));

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('name', array(
      'label'   => __('Organisation Name', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
    ));
    $repeater->add_control('logo', array(
      'label' => __('Logo', 'jacana-luxe'),
      'type'  => \Elementor\Controls_Manager::MEDIA,
    ));
    $repeater->add_control('link', array(
      'label'       => __('Website Link (optional)', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::URL,
      'placeholder' => 'https://',
    ));

    $this->add_control('orgs', array(
      'label'       => __('Organisations', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::REPEATER,
      'fields'      => $repeater->get_controls(),
      'default'     => array(
        array('name' => __('Namibia Tourism Board', 'jacana-luxe')),
        array('name' => __('FENATA', 'jacana-luxe')),
        array('name' => __('TASA', 'jacana-luxe')),
      ),
      'title_field' => '{{{ name }}}',
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $orgs     = !empty($settings['orgs']) ? $settings['orgs'] : array();
    if (empty($orgs)) {
      return;
    }
    ?>
    <section class="jacana-affiliations-section">
      <div class="jacana-affiliations-inner">
        <?php if (!empty($settings['heading'])) : ?>
          <p class="jacana-affiliations-heading"><?php echo esc_html($settings['heading']); ?></p>
        <?php endif; ?>
        <div class="jacana-affiliations-list">
          <?php foreach ($orgs as $org) :
            $logo = !empty($org['logo']['url']) ? $org['logo']['url'] : '';
            $name = !empty($org['name']) ? $org['name'] : '';
            $url  = !empty($org['link']['url']) ? $org['link']['url'] : '';
            $ext  = !empty($org['link']['is_external']);
            $tag  = $url ? 'a' : 'span';
            $attrs = $url
              ? 'href="' . esc_url($url) . '"' . ($ext ? ' target="_blank" rel="noopener noreferrer"' : '')
              : '';
            ?>
            <<?php echo $tag; ?> class="jacana-affiliation-item" <?php echo $attrs; ?>>
              <?php if ($logo) : ?>
                <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
              <?php else : ?>
                <span class="jacana-affiliation-name"><?php echo esc_html($name); ?></span>
              <?php endif; ?>
            </<?php echo $tag; ?>>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php
  }
}
