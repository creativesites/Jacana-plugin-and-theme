<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Faq_Downloads extends \Elementor\Widget_Base {
  public function get_name()       { return 'jacana_faq_downloads'; }
  public function get_title()      { return __('FAQ - Good to Know', 'jacana-luxe'); }
  public function get_icon()       { return 'eicon-help-o'; }
  public function get_categories() { return array('jacana-luxe'); }

  protected function register_controls() {

    /* ── Header ── */
    $this->start_controls_section('content_section', array('label' => __('Header', 'jacana-luxe')));

    $this->add_control('kicker', array(
      'label'   => __('Kicker', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Good to Know', 'jacana-luxe'),
    ));

    $this->add_control('heading', array(
      'label'   => __('Heading', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Good to know before your trip', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label'   => __('Intro', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Your practical Namibia guide: weather, road conditions, payments, vaccinations, and more. Updated regularly so you arrive prepared.', 'jacana-luxe'),
    ));

    $this->add_control('search_placeholder', array(
      'label'   => __('Search Placeholder', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Search topics (roads, weather, license, payment…)', 'jacana-luxe'),
    ));

    $this->end_controls_section();

    /* ── Weather ── */
    $this->start_controls_section('weather_section', array('label' => __('Weather & Seasons', 'jacana-luxe')));

    $this->add_control('weather_title', array(
      'label'   => __('Weather Card Title', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Namibia Weather & Seasons', 'jacana-luxe'),
    ));

    $this->add_control('dry_season_label', array(
      'label'   => __('Dry Season Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Dry Season · May – October', 'jacana-luxe'),
    ));

    $this->add_control('dry_season_copy', array(
      'label'   => __('Dry Season Copy', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::WYSIWYG,
      'rows'    => 10,
      'default' => __("Little to no rainfall and low humidity. Wildlife will gather around waterholes and rivers when other water sources dry up.\n\nMay – end of summer. The rains have stopped, but the scenery is still lovely and green. Nights aren't cold yet, and daytime temperatures are around 24–28°C.\n\nJune – nights are getting cold and can drop below 10°C. In desert areas, it can be freezing. Daytime temperatures are still pleasant around 20–24°C.\n\nJuly & August – average maximum 21–25°C, average minimum around 7°C, but can fall to below freezing at night in the deserts and higher areas. Pack warm clothing for morning game drives.\n\nSeptember & October – the chill in the mornings is becoming less. It is dry and the skies are clear. During October, the green vegetation is fading and the heat gradually builds up.", 'jacana-luxe'),
    ));

    $this->add_control('wet_season_label', array(
      'label'   => __('Wet Season Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Wet Season · November – April', 'jacana-luxe'),
    ));

    $this->add_control('wet_season_copy', array(
      'label'   => __('Wet Season Copy', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::WYSIWYG,
      'rows'    => 10,
      'default' => __("November – the heat continues to rise. Daytime temperatures are above 30°C, but can be higher in the deserts. Humidity is still low, keeping it quite pleasant. Clouds begin building in the afternoons.\n\nDecember – the first rains usually arrive and with them temperatures drop. The landscape changes after the first rains and everything comes to life.\n\nJanuary & February – midsummer. Hot and humid with maximum temperatures around 30–35°C, with peaks of over 40°C in the desert. Torrential downpours are possible in the afternoon but not every day. Mornings are usually clear.\n\nMarch & April – rainfall decreases and stops around April. It cools down after the rains and the nights start to get cold again. Average daytime temperatures around 25–30°C.", 'jacana-luxe'),
    ));

    $this->add_control('weather_link_label', array(
      'label'   => __('Live Forecast Link Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('See Live Forecast', 'jacana-luxe'),
    ));

    $this->add_control('weather_link', array(
      'label'       => __('Live Forecast URL', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::URL,
      'placeholder' => 'https://',
    ));

    $this->end_controls_section();

    /* ── FAQ Items ── */
    $this->start_controls_section('faq_section', array('label' => __('FAQ Items', 'jacana-luxe')));

    $faq_repeater = new \Elementor\Repeater();

    $faq_repeater->add_control('tag', array(
      'label'   => __('Category Tag', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Travel Basics', 'jacana-luxe'),
    ));

    $faq_repeater->add_control('question', array(
      'label' => __('Question', 'jacana-luxe'),
      'type'  => \Elementor\Controls_Manager::TEXT,
    ));

    $faq_repeater->add_control('answer', array(
      'label' => __('Answer', 'jacana-luxe'),
      'type'  => \Elementor\Controls_Manager::WYSIWYG,
    ));

    $this->add_control('faq_items', array(
      'label'       => __('FAQ Items', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::REPEATER,
      'fields'      => $faq_repeater->get_controls(),
      'default'     => array(
        array(
          'tag'      => __('Payments', 'jacana-luxe'),
          'question' => __('Can I pay with a credit card?', 'jacana-luxe'),
          'answer'   => __('In most areas of Namibia you can pay with a credit card. However it is advisable to always carry some cash with you, in case the ATM or swiping machine does not function. It is your own decision how much cash you carry with you.', 'jacana-luxe'),
        ),
        array(
          'tag'      => __('Health', 'jacana-luxe'),
          'question' => __('Do I need vaccinations?', 'jacana-luxe'),
          'answer'   => __('Depending on your travel destination, vaccinations and antimalarial medications may be necessary. We recommend consulting your local doctor or travel clinic prior to your travel to Namibia. They have the most current information and can offer guidance tailored to your medical history.', 'jacana-luxe'),
        ),
        array(
          'tag'      => __('Connectivity', 'jacana-luxe'),
          'question' => __('How is the network coverage?', 'jacana-luxe'),
          'answer'   => __('While most lodges, camps, and hotels have free WiFi, Namibia also has very good mobile network coverage in most areas. However, especially remote areas might not have any reception at all. We encourage you to disconnect to connect. If you get a local SIM card you can easily use online navigation on your phone (e.g. Google Maps) — it is not necessary to rent or bring a GPS navigation system.', 'jacana-luxe'),
        ),
        array(
          'tag'      => __('Driving', 'jacana-luxe'),
          'question' => __('What do I need to know about driving in Namibia?', 'jacana-luxe'),
          'answer'   => __('It is mandatory to have a valid driver\'s license at all times while driving. If you do not have a Namibian license, you need your foreign license plus an international driver\'s license. In Namibia, driving is on the left-hand side of the road. Many rural roads are gravel. Distances between cities can be considerable, and petrol is only available at a few service stations along some routes — fill up even when the tank is still half full if the next destination is far. Carry five litres of water per person when travelling on dirt roads.', 'jacana-luxe'),
        ),
        array(
          'tag'      => __('National Parks', 'jacana-luxe'),
          'question' => __('Is there an entrance fee for the National Parks?', 'jacana-luxe'),
          'answer'   => __('There are entrance fees for each person and vehicle entering one of Namibia\'s National Parks. The park gates open at sunrise and close at sunset. Please ensure you do not drive in the parks after dark.', 'jacana-luxe'),
        ),
        array(
          'tag'      => __('Practical', 'jacana-luxe'),
          'question' => __('Do I need a travel plug adapter?', 'jacana-luxe'),
          'answer'   => __('In Namibia, power plugs and sockets of type D and type M are used. The standard voltage is 220 V at a frequency of 50 Hz. You need a power plug travel adapter for sockets type D and M. You can get them at most shops in Namibia or before travelling in an online shop.', 'jacana-luxe'),
        ),
        array(
          'tag'      => __('Payments', 'jacana-luxe'),
          'question' => __('What payment methods does Jacana Safaris and Tours accept?', 'jacana-luxe'),
          'answer'   => __('You can either do an online payment via credit card or pay via international bank transfer. We will guide you through the payment method that best fits your booking.', 'jacana-luxe'),
        ),
      ),
      'title_field' => '{{{ question }}}',
    ));

    $this->end_controls_section();

    /* ── CTA ── */
    $this->start_controls_section('cta_section', array('label' => __('Sidebar CTA', 'jacana-luxe')));

    $this->add_control('cta_mode', array(
      'label'   => __('Primary CTA Action', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::SELECT,
      'default' => 'chat',
      'options' => array(
        'chat' => __('Open chatbot', 'jacana-luxe'),
        'link' => __('Open link', 'jacana-luxe'),
      ),
    ));

    $this->add_control('cta_label', array(
      'label'   => __('Primary CTA Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Ask us anything', 'jacana-luxe'),
    ));

    $this->add_control('cta_link', array(
      'label'     => __('Primary CTA Link', 'jacana-luxe'),
      'type'      => \Elementor\Controls_Manager::URL,
      'placeholder'=> '/contact',
      'condition' => array('cta_mode' => 'link'),
    ));

    $this->end_controls_section();

    /* ── Downloads ── */
    $this->start_controls_section('downloads_section', array('label' => __('Downloads', 'jacana-luxe')));

    $this->add_control('downloads_heading', array(
      'label'   => __('Downloads Heading', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Free Downloads', 'jacana-luxe'),
    ));

    $this->add_control('downloads_intro', array(
      'label'   => __('Downloads Intro', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Pack these before you leave. Our travel documents are designed to help you get the most out of your Namibian adventure.', 'jacana-luxe'),
    ));

    $dl_repeater = new \Elementor\Repeater();

    $dl_repeater->add_control('dl_name', array(
      'label'   => __('Document Name', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Travel Checklist', 'jacana-luxe'),
    ));

    $dl_repeater->add_control('dl_desc', array(
      'label'   => __('Description', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Everything you need to pack and prepare before your Namibia trip.', 'jacana-luxe'),
    ));

    $dl_repeater->add_control('dl_file', array(
      'label'       => __('Upload File (PDF)', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::MEDIA,
      'description' => __('Upload the PDF via the WordPress media library.', 'jacana-luxe'),
      'media_type'  => 'application/pdf',
    ));

    $dl_repeater->add_control('dl_url', array(
      'label'       => __('Or: Direct File URL', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::TEXT,
      'placeholder' => 'https://…/file.pdf',
      'description' => __('Used if no file is uploaded above. Paste the direct URL to the PDF.', 'jacana-luxe'),
    ));

    $this->add_control('downloads', array(
      'label'       => __('Download Items', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::REPEATER,
      'fields'      => $dl_repeater->get_controls(),
      'default'     => array(
        array(
          'dl_name' => __('Travel Checklist', 'jacana-luxe'),
          'dl_desc' => __('Everything you need to pack and prepare before your Namibia trip.', 'jacana-luxe'),
        ),
        array(
          'dl_name' => __('Namibia Bingo', 'jacana-luxe'),
          'dl_desc' => __('A fun wildlife and landscape bingo card to play on your safari.', 'jacana-luxe'),
        ),
        array(
          'dl_name' => __('Bucket List — Digital', 'jacana-luxe'),
          'dl_desc' => __('Our curated digital bucket list of must-see places and experiences across Namibia.', 'jacana-luxe'),
        ),
        array(
          'dl_name' => __('Bucket List — Print', 'jacana-luxe'),
          'dl_desc' => __('A print-ready version of the Namibia bucket list to take with you on the road.', 'jacana-luxe'),
        ),
        array(
          'dl_name' => __('General Information', 'jacana-luxe'),
          'dl_desc' => __('A comprehensive overview of Namibia: culture, currency, climate, and safety.', 'jacana-luxe'),
        ),
      ),
      'title_field' => '{{{ dl_name }}}',
    ));

    $this->end_controls_section();
  }

  protected function render_primary_cta($settings) {
    $label = !empty($settings['cta_label']) ? $settings['cta_label'] : __('Ask us anything', 'jacana-luxe');

    if (($settings['cta_mode'] ?? 'chat') === 'link') {
      $link  = !empty($settings['cta_link']) ? $settings['cta_link'] : array();
      $url   = !empty($link['url']) ? $link['url'] : '#';
      $ext   = !empty($link['is_external']);
      echo '<a class="jacana-faq-sidebar-btn" href="' . esc_url($url) . '"' . ($ext ? ' target="_blank" rel="noopener noreferrer"' : '') . '>'
        . esc_html($label)
        . ' <span aria-hidden="true">&rarr;</span></a>';
      return;
    }

    echo '<button type="button" class="jacana-faq-sidebar-btn"'
      . ' data-jacana-ai-flow="faq"'
      . ' data-jacana-widget="jacana_faq_downloads"'
      . ' data-jacana-service="faq">'
      . esc_html($label)
      . ' <span aria-hidden="true">&rarr;</span></button>';
  }

  private function get_topic_tags($items) {
    $topics = array();
    foreach ((array) $items as $item) {
      $tag = sanitize_text_field((string) ($item['tag'] ?? ''));
      if ($tag !== '') {
        $topics[$tag] = $tag;
      }
    }
    return array_values($topics);
  }

  protected function render() {
    $settings     = $this->get_settings_for_display();
    $weather_link = !empty($settings['weather_link']) ? $settings['weather_link'] : array();
    $faq_items    = !empty($settings['faq_items']) && is_array($settings['faq_items']) ? $settings['faq_items'] : array();
    $downloads    = !empty($settings['downloads']) && is_array($settings['downloads']) ? $settings['downloads'] : array();
    $topics       = $this->get_topic_tags($faq_items);
    $faq_count    = count($faq_items);
    ?>
    <section class="jacana-faq-section" id="faq">
      <div class="jacana-faq-inner">

        <!-- ── Header ── -->
        <header class="jacana-faq-header jacana-reveal">
          <h2 class="jacana-faq-heading"><?php echo esc_html($settings['heading']); ?></h2>
          <?php if (!empty($settings['intro'])) : ?>
            <p class="jacana-faq-intro"><?php echo esc_html($settings['intro']); ?></p>
          <?php endif; ?>
        </header>

        <!-- ── Weather Feature Card ── -->
        <?php
          $dry_label = !empty($settings['dry_season_label']) ? $settings['dry_season_label'] : '';
          $dry_copy  = !empty($settings['dry_season_copy'])  ? $settings['dry_season_copy']  : '';
          $wet_label = !empty($settings['wet_season_label']) ? $settings['wet_season_label'] : '';
          $wet_copy  = !empty($settings['wet_season_copy'])  ? $settings['wet_season_copy']  : '';
        ?>
        

        <!-- ── FAQ Layout ── -->
        <div class="jacana-faq-layout">

          <!-- Sidebar -->
          <aside class="jacana-faq-sidebar jacana-reveal" style="--jacana-delay: 120ms;">
            <div class="jacana-faq-sidebar-count">
              <?php echo esc_html(sprintf(
                _n('%d answer ready', '%d answers ready', $faq_count, 'jacana-luxe'),
                $faq_count
              )); ?>
            </div>

            <?php if (!empty($topics)) : ?>
              <div class="jacana-faq-sidebar-topics">
                <div class="jacana-faq-sidebar-topics-label">
                  <?php echo esc_html__('Browse by topic', 'jacana-luxe'); ?>
                </div>
                <div class="jacana-faq-topic-pills" aria-label="<?php echo esc_attr__('FAQ topics', 'jacana-luxe'); ?>">
                  <?php foreach ($topics as $topic) : ?>
                    <button type="button"
                            class="jacana-faq-topic-pill"
                            data-faq-topic="<?php echo esc_attr(strtolower($topic)); ?>">
                      <?php echo esc_html($topic); ?>
                    </button>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <div class="jacana-faq-sidebar-cta">
              <?php $this->render_primary_cta($settings); ?>
            </div>
          </aside>

          <!-- Main accordion -->
          <div class="jacana-faq-main jacana-reveal" style="--jacana-delay: 160ms;" data-jacana-faq-block>

            <div class="jacana-faq-toolbar">
              <label class="jacana-faq-search">
                <span class="jacana-faq-search-label"><?php echo esc_html__('Search FAQ', 'jacana-luxe'); ?></span>
                <input class="jacana-faq-search-input"
                       type="search"
                       placeholder="<?php echo esc_attr($settings['search_placeholder']); ?>">
              </label>
              <div class="jacana-faq-results-meta">
                <span data-faq-results-count>
                  <?php echo esc_html(sprintf(
                    _n('%d answer found', '%d answers found', $faq_count, 'jacana-luxe'),
                    $faq_count
                  )); ?>
                </span>
                <button type="button" class="jacana-faq-clear" data-faq-clear hidden>
                  <?php echo esc_html__('Clear', 'jacana-luxe'); ?>
                </button>
              </div>
            </div>

            <div class="jacana-faq-list">
              <?php foreach ($faq_items as $index => $item) :
                $question    = $item['question'] ?? '';
                $answer      = $item['answer']   ?? '';
                $tag         = $item['tag']       ?? '';
                if (empty($question) && empty($answer)) { continue; }
                $search_blob = strtolower(trim($tag . ' ' . $question . ' ' . $answer));
                $delay       = 120 + (50 * ((int) $index + 1));
              ?>
                <details class="jacana-faq-item jacana-reveal"
                         style="--jacana-delay: <?php echo esc_attr($delay); ?>ms;"
                         data-faq-item
                         data-faq-text="<?php echo esc_attr($search_blob); ?>"
                         <?php echo 0 === $index ? 'open' : ''; ?>>
                  <summary>
                    <?php if (!empty($tag)) : ?>
                      <span class="jacana-faq-tag"><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $tag, array('area' => 'faq_tag'))); ?></span>
                    <?php endif; ?>
                    <span class="jacana-faq-question"><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $question, array('area' => 'faq_question'))); ?></span>
                  </summary>
                  <div class="jacana-faq-answer">
                    <div class="jacana-faq-answer-content">
                      <?php echo wp_kses_post(apply_filters('jacana_i18n_translate_string', $answer, array('area' => 'faq_answer'))); ?>
                    </div>
                  </div>
                </details>
              <?php endforeach; ?>
            </div>

            <div class="jacana-faq-empty-state" data-faq-empty hidden>
              <strong><?php echo esc_html__('No answers matched that search.', 'jacana-luxe'); ?></strong>
              <span><?php echo esc_html__('Try a broader word like roads, weather, visa, payment or safari.', 'jacana-luxe'); ?></span>
            </div>

          </div>
        </div><!-- /.jacana-faq-layout -->

      </div><!-- /.jacana-faq-inner -->

      <!-- ── Downloads Strip ── -->
      <?php if (!empty($downloads)) : ?>
        <div class="jacana-faq-downloads" id="downloads">
          <div class="jacana-faq-downloads-inner">

            <div class="jacana-faq-downloads-head jacana-reveal">
              <div class="jacana-faq-downloads-kicker">
                <?php echo esc_html($settings['downloads_heading']); ?>
              </div>
              <?php if (!empty($settings['downloads_intro'])) : ?>
                <p class="jacana-faq-downloads-intro"><?php echo esc_html($settings['downloads_intro']); ?></p>
              <?php endif; ?>
            </div>

            <div class="jacana-faq-downloads-grid">
              <?php foreach ($downloads as $i => $dl) :
                $dl_name  = !empty($dl['dl_name'])       ? $dl['dl_name']             : '';
                $dl_desc  = !empty($dl['dl_desc'])       ? $dl['dl_desc']             : '';
                $dl_file  = !empty($dl['dl_file']['url'])? $dl['dl_file']['url']      : '';
                $dl_url   = !empty($dl['dl_url'])        ? trim($dl['dl_url'])        : '';
                $href     = $dl_file ?: $dl_url;
                $delay    = 80 + ($i * 90);
              ?>
                <div class="jacana-faq-download-card jacana-reveal" style="--jacana-delay: <?php echo esc_attr($delay); ?>ms;">
                  <span class="jacana-faq-download-type" aria-hidden="true">PDF</span>
                  <?php if ($dl_name) : ?>
                    <h3 class="jacana-faq-download-name"><?php echo esc_html($dl_name); ?></h3>
                  <?php endif; ?>
                  <?php if ($dl_desc) : ?>
                    <p class="jacana-faq-download-desc"><?php echo esc_html($dl_desc); ?></p>
                  <?php endif; ?>
                  <?php if ($href) : ?>
                    <a class="jacana-faq-download-btn"
                       href="<?php echo esc_url($href); ?>"
                       download
                       target="_blank"
                       rel="noopener noreferrer">
                      <?php echo esc_html__('Download', 'jacana-luxe'); ?>
                      <span aria-hidden="true">&darr;</span>
                    </a>
                  <?php else : ?>
                    <span class="jacana-faq-download-btn jacana-faq-download-btn--soon"
                          role="link"
                          aria-disabled="true"
                          tabindex="0"
                          aria-label="<?php echo esc_attr($dl_name . ' — ' . __('coming soon', 'jacana-luxe')); ?>">
                      <?php echo esc_html__('Coming soon', 'jacana-luxe'); ?>
                    </span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>

          </div>
        </div><!-- /.jacana-faq-downloads -->
      <?php endif; ?>

    </section>
    <?php
  }
}
