<?php get_header(); ?>
<main>
  <section class="section">
    <div class="section-inner">
      <div class="section-header">
        <h2><?php bloginfo('name'); ?></h2>
        <p><?php bloginfo('description'); ?></p>
      </div>
      <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
          <article <?php post_class('card'); ?>>
            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <?php the_excerpt(); ?>
          </article>
        <?php endwhile; ?>
      <?php else : ?>
        <p>No content found.</p>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php get_footer(); ?>
