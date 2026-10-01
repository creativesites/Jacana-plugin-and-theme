<?php get_header(); ?>
<main>
  <section class="section">
    <div class="section-inner">
      <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article <?php post_class('card'); ?>>
          <h2><?php the_title(); ?></h2>
          <?php the_content(); ?>
        </article>
      <?php endwhile; endif; ?>
    </div>
  </section>
</main>
<?php get_footer(); ?>
