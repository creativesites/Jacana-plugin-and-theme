<!doctype html>
<html <?php 
language_attributes(); 
$uploads = wp_get_upload_dir();
$base = trailingslashit($uploads['baseurl']) . '2026/02/';
$logo_image = $base . 'logo.png';
$home_url = home_url('/');
if (function_exists('jacana_i18n_localize_url')) {
  $home_url = jacana_i18n_localize_url($home_url);
}
?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="site">
  <header class="site-header">
    <div class="header-inner">
      <a class="brand" href="<?php echo esc_url($home_url); ?>">
        <span class="brand-mark" aria-hidden="true">
          <img src="<?php echo esc_url($logo_image); ?>" alt="Jacana Safaris & Tours Logo">
        </span>
        <span class="brand-text">
          <strong>Jacana</strong>
          Safaris & Tours
        </span>
      </a>

      <nav class="primary-nav" id="primary-nav" aria-label="<?php echo esc_attr__('Primary', 'jacana-luxe'); ?>">
        <?php
        wp_nav_menu(array(
          'theme_location' => 'primary',
          'container' => false,
          'fallback_cb' => false,
          'items_wrap' => '<ul>%3$s</ul>',
        ));
        ?>
      </nav>

      <div class="header-actions">
        <?php if (function_exists('jacana_i18n_get_switcher_html')) : ?>
          <?php echo jacana_i18n_get_switcher_html(array('class' => 'jacana-language-switcher--header', 'variant' => 'dropdown')); ?>
        <?php endif; ?>
        <!-- <button class="button button-outline header-cta header-cta-secondary" type="button" data-jacana-ai-flow="planner" data-jacana-widget="jacana_site_header" data-jacana-service="tailor_made"><?php echo esc_html__('Plan with AI', 'jacana-luxe'); ?></button> -->
        <a class="button button-primary header-cta" data-jacana-booking-modal="true" data-jacana-widget="jacana_site_header" data-jacana-service="tailor_made" href="#"><?php echo esc_html__('Start booking', 'jacana-luxe'); ?></a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav"><?php echo esc_html__('Menu', 'jacana-luxe'); ?></button>
      </div>
    </div>
  </header>
