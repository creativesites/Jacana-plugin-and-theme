<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Team_Profiles extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_team_profiles';
  }

  public function get_title() {
    return __('Team Profiles', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-person';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('heading', array(
      'label' => __('Heading', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Meet the Team', 'jacana-luxe'),
    ));

    $this->add_control('intro', array(
      'label' => __('Intro', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('Led by Simon and Lara Hamalwa, Jacana blends local heritage with global hospitality expertise.', 'jacana-luxe'),
    ));

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('name', array(
      'label' => __('Name', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
    ));
    $repeater->add_control('role', array(
      'label' => __('Role', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
    ));
    $repeater->add_control('bio', array(
      'label' => __('Bio', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
    ));
    $repeater->add_control('image', array(
      'label' => __('Photo', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
    ));
    $repeater->add_control('highlight', array(
      'label' => __('Highlight (optional)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
    ));
    $repeater->add_control('education', array(
      'label' => __('Education', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
    ));
    $repeater->add_control('experience', array(
      'label' => __('Work Experience', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
    ));
    $repeater->add_control('testimonials', array(
      'label' => __('Friends & Clients Say', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
    ));
    $repeater->add_control('quote', array(
      'label' => __('Favorite Quote', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
    ));
    $repeater->add_control('cta_label', array(
      'label'   => __('Booking CTA Label', 'jacana-luxe'),
      'type'    => \Elementor\Controls_Manager::TEXT,
      'default' => __('Book with this guide', 'jacana-luxe'),
    ));
    $repeater->add_control('cta_link', array(
      'label'       => __('Booking CTA Link', 'jacana-luxe'),
      'type'        => \Elementor\Controls_Manager::URL,
      'placeholder' => '/contact/',
    ));

    $this->add_control('profiles', array(
      'type' => \Elementor\Controls_Manager::REPEATER,
      'fields' => $repeater->get_controls(),
      'default' => array(
        array(
          'name' => __('Simon Hamalwa', 'jacana-luxe'),
          'role' => __('Founder & Managing Director', 'jacana-luxe'),
          'bio' => __('With 8+ years in tourism and hospitality, Simon brings heartfelt Namibian storytelling to every journey.', 'jacana-luxe'),
          'education' => __('I have a Bachelor Degree in Tourism Management and a Diploma in International Tourism Principals.', 'jacana-luxe'),
          'experience' => __('I worked in different sectors of the Tourism Industry for more than 8 years. To name some experience: I was a front office agent, a retail supervisor, a game drive trainee and more until I founded Jacana Safaris and Tours in 2018 and live my own dream.', 'jacana-luxe'),
          'testimonials' => __('My friends would tell you that I am a very friendly and passionate person. They would say that I am a Namibian through and through and that I love showing that through my tours for foreign and Namibian national tourists. My clients would tell you that I am a trustworthy person who cares a lot about their wishes and that I would do anything in my power to make their dream tour through Namibia possible.', 'jacana-luxe'),
          'quote' => __('"Inoenda, inotala." - Oshiwambo saying meaning "If you didn\'t travel, you didn\'t see."', 'jacana-luxe'),
        ),
        array(
          'name' => __('Lara Hamalwa', 'jacana-luxe'),
          'role' => __('Chief of Operations', 'jacana-luxe'),
          'bio' => __('Lara\'s planning expertise and calm leadership ensure every tour feels effortless and unforgettable.', 'jacana-luxe'),
          'education' => __('Originally I am a rehabilitation scientist with a Master Degree. But I found my passion for Tourism through my love for Namibia and of course my husband, who teaches me what I need to know, to give you the best experience with us.', 'jacana-luxe'),
          'experience' => __('I am an active part of Jacana Safaris and Tours since 2019. My profession requires me to be able to interact well with different people and to put their wishes and needs in my work focus. This ability and my organizational talent helps me to assure a good service for you.', 'jacana-luxe'),
          'testimonials' => __('If you would ask my friends about me, they would tell you that I always have a plan and a solution to any problem. They would tell you that I have a positive outlook on life and that it is easy for me to focus professionally on my clients wishes, to assure them the best experience of their life. Our clients would tell you that I make them feel safe and well informed about their possibilities in Namibia.', 'jacana-luxe'),
          'quote' => __('"If I have ever seen magic, it has been in Africa." - John Hemmingway', 'jacana-luxe'),
        ),
      ),
      'title_field' => '{{{ name }}}',
    ));

    $this->end_controls_section();
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    ?>
    <section class="jacana-team-profiles">
      <div class="jacana-tp-inner">

        <header class="jacana-tp-header jacana-reveal">
          <div class="jacana-tp-kicker"><?php echo esc_html__('The People Behind the Journey', 'jacana-luxe'); ?></div>
          <h2><?php echo esc_html($settings['heading']); ?></h2>
          <p class="jacana-tp-intro"><?php echo esc_html($settings['intro']); ?></p>
        </header>

        <div class="jacana-tp-list">
          <?php 
          $profiles = is_array($settings['profiles']) ? $settings['profiles'] : [];
          foreach ($profiles as $index => $profile) :
            $photo   = !empty($profile['image']['url']) ? $profile['image']['url'] : '';
            $details = array(
              __('Education', 'jacana-luxe')                  => $profile['education']    ?? '',
              __('Work experience', 'jacana-luxe')             => $profile['experience']   ?? '',
              __('What friends & clients say', 'jacana-luxe')  => $profile['testimonials'] ?? '',
              __('Favourite quote', 'jacana-luxe')              => $profile['quote']       ?? '',
            );
            $cta_label = !empty($profile['cta_label']) ? $profile['cta_label'] : __('Book with this guide', 'jacana-luxe');
            $cta_url   = !empty($profile['cta_link']['url']) ? $profile['cta_link']['url'] : '/booking/';
            $cta_ext   = !empty($profile['cta_link']['is_external']);
            $delay     = 80 + (int) $index * 140;
            $is_even   = $index % 2 === 1;
            ?>
            <article class="jacana-tp-card jacana-reveal<?php echo $is_even ? ' jacana-tp-card--flip' : ''; ?>"
                     style="--team-delay: <?php echo esc_attr($delay); ?>ms;">

              <!-- Photo panel -->
              <div class="jacana-tp-photo-wrap">
                <?php if ($photo) : ?>
                  <figure class="jacana-tp-figure">
                    <img src="<?php echo esc_url($photo); ?>"
                         alt="<?php echo esc_attr($profile['name']); ?>"
                         loading="lazy">
                    <div class="jacana-tp-photo-stripe" aria-hidden="true"></div>
                  </figure>
                <?php else : ?>
                  <div class="jacana-tp-photo-placeholder" aria-hidden="true">
                    <span><?php echo esc_html($profile['name'][0] ?? ''); ?></span>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Content panel -->
              <div class="jacana-tp-content">
                <div class="jacana-tp-meta">
                  <h3 class="jacana-tp-name"><?php echo esc_html($profile['name']); ?></h3>
                  <div class="jacana-tp-role"><?php echo esc_html($profile['role']); ?></div>
                </div>

                <?php if (!empty($profile['bio'])) : ?>
                  <p class="jacana-tp-bio"><?php echo esc_html(apply_filters('jacana_i18n_translate_string', $profile['bio'], array('area' => 'team_bio'))); ?></p>
                <?php endif; ?>

                <div class="jacana-tp-details">
                  <?php foreach ($details as $label => $value) : ?>
                    <?php if (!empty(trim((string) $value))) : 
                      $translated_value = apply_filters('jacana_i18n_translate_string', $value, array('area' => 'team_detail'));
                    ?>
                      <details class="jacana-tp-detail">
                        <summary><?php echo esc_html($label); ?></summary>
                        <div class="jacana-tp-detail-body">
                          <?php echo wp_kses_post(wpautop(wp_kses_post($translated_value))); ?>
                        </div>
                      </details>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>

                <a class="jacana-tp-cta"
                   href="<?php echo esc_url($cta_url); ?>"
                   <?php echo $cta_ext ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                  <?php echo esc_html($cta_label); ?>
                  <span class="jacana-tp-arrow" aria-hidden="true">&rarr;</span>
                </a>
              </div>

            </article>
          <?php endforeach; ?>
        </div>

      </div>
    </section>
    <?php
  }
}
