<?php get_header(); ?>
<main>
  <section class="section">
    <div class="section-inner">
      <div class="card">
        <h2>Page not found</h2>
        <p>The page you’re looking for doesn’t exist. Let us help you find your way back.</p>
        <?php $home_url = function_exists('jacana_i18n_localize_url') ? jacana_i18n_localize_url(home_url('/')) : home_url('/'); ?>
        <a class="button button-primary" href="<?php echo esc_url($home_url); ?>">Return home</a>
      </div>
    </div>
  </section>
</main>
<?php get_footer(); ?>
