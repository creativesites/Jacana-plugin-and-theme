<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Shuttle_Services extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_shuttle_services';
  }

  public function get_title() {
    return __('Shuttle Services', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-table';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Shuttle Services', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label' => __('Intro', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Private transfers between airports, Windhoek city, and lodges.', 'jacana-luxe'),
    ));

    $this->add_control('rows', array(
      'label' => __('Rows (Route | Pax | Rate)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => "Between Hosea Kutako International Airport and Windhoek City | 1 person | N$400 per person\nBetween Hosea Kutako International Airport and Windhoek City | 2 persons | N$350 per person\nBetween Hosea Kutako International Airport and Windhoek City | 3 or more persons | N$300 per person\nEros airport to Windhoek accommodation | Any No. of pax | N$200 per person\nWindhoek City to lodges within the 30 kilometres radius | 1 person | N$400 per person\nWindhoek City to lodges within the 30 kilometres radius | 2 persons | N$350 per person\nWindhoek City to lodges within the 30 kilometres radius | 3 or more persons | N$300 per person\nPlaces beyond the 30 kilometres radius from or to Windhoek City | 1 person | N$400 per person plus N$9.00 per km\nPlaces beyond the 30 kilometres radius from or to Windhoek City | 2 persons | N$350 per person plus N$9.00 per km\nPlaces beyond the 30 kilometres radius from or to Windhoek City | 3 or more persons | N$300 per person plus N$9.00 per km",
    ));

    $this->end_controls_section();
  }

  private function parse_rows($input) {
    $rows = array_filter(array_map('trim', explode("\n", $input)));
    $parsed = array();
    foreach ($rows as $row) {
      $parts = array_map('trim', explode('|', $row));
      if (count($parts) < 3) {
        continue;
      }
      $parsed[] = array($parts[0], $parts[1], $parts[2]);
    }
    return $parsed;
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $rows = $this->parse_rows($settings['rows']);
    ?>
    <section class="section">
      <div class="section-inner">
        <div class="section-header">
          <h2><?php echo esc_html($settings['heading']); ?></h2>
          <p><?php echo esc_html($settings['intro']); ?></p>
        </div>
        <div class="pricing-table-wrap">
          <table class="pricing-table">
            <thead>
              <tr>
                <th><?php echo esc_html__('Route', 'jacana-luxe'); ?></th>
                <th><?php echo esc_html__('No. of PAX', 'jacana-luxe'); ?></th>
                <th><?php echo esc_html__('Rate per person', 'jacana-luxe'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row) : ?>
                <tr>
                  <td><?php echo esc_html($row[0]); ?></td>
                  <td><?php echo esc_html($row[1]); ?></td>
                  <td><?php echo esc_html($row[2]); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
    <?php
  }
}
