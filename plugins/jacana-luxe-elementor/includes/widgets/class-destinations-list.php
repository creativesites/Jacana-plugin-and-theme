<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Destinations_List extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_destinations_list';
  }

  public function get_title() {
    return __('Destinations List', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-list-bullet';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Destinations', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label' => __('Intro', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('The listed destinations are the main Namibian attractions and we are not limited to them. We will take you around every corner of Namibia that you wish to see.', 'jacana-luxe'),
    ));

    $this->add_control('data_source', array(
      'label' => __('Source', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::SELECT,
      'default' => 'manual',
      'options' => array(
        'manual' => __('Manual (textarea)', 'jacana-luxe'),
        'posts' => __('Destinations (CPT)', 'jacana-luxe'),
      ),
    ));

    $this->add_control('destinations', array(
      'label' => __('Destinations (one per line)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => "Windhoek\nEtosha National Park\nSossusvlei\nFish River Canyon\nTwyfelfontein\nSkeleton Coast\nDamaraland\nKaokoland\nSwakopmund\nZambezi Region\nLüderitz\nWaterberg\nKalahari Desert\nWalvisbay\nSpitzkoppe",
      'condition' => array('data_source' => 'manual'),
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $source = !empty($settings['data_source']) ? $settings['data_source'] : 'manual';
    
    // items: array of ['title' => string, 'url' => string|null]
    $items = array();
    if ('posts' === $source) {
      $dest_posts = get_posts(array(
        'post_type'      => 'jacana_destination',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
      ));
      foreach ($dest_posts as $dp) {
        $items[] = array('title' => $dp->post_title, 'url' => get_permalink($dp->ID));
      }
    } else {
      foreach (array_filter(array_map('trim', explode("\n", $settings['destinations']))) as $name) {
        $items[] = array('title' => $name, 'url' => null);
      }
    }
    $archive_url = get_post_type_archive_link('jacana_destination') ?: '/destinations/';
    ?>
    <section class="section jacana-zoora-parity jacana-tours-widget jacana-destinations-list">
      <div class="section-inner">
        <div class="section-header">
          <h2 class="jacana-reveal"><?php echo esc_html($settings['heading']); ?></h2>
          <p class="jacana-reveal" style="--jacana-delay: 120ms;"><?php echo esc_html($settings['intro']); ?></p>
        </div>
        <div class="destinations-list">
          <?php foreach ($items as $index => $item) :
            $pill_url = !empty($item['url']) ? $item['url'] : null;
            $delay    = esc_attr(40 * ((int) $index + 1));
          ?>
            <?php if ($pill_url) : ?>
              <a href="<?php echo esc_url($pill_url); ?>"
                 class="destination-pill jacana-reveal"
                 style="--jacana-delay: <?php echo $delay; ?>ms;"><?php echo esc_html($item['title']); ?></a>
            <?php else : ?>
              <div class="destination-pill jacana-reveal" style="--jacana-delay: <?php echo $delay; ?>ms;"><?php echo esc_html($item['title']); ?></div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php
  }
}
