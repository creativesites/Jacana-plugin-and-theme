<?php get_header(); ?>
<main>
  <section class="section">
    <div class="section-inner">
      <div class="section-header">
        <h2><?php the_archive_title(); ?></h2>
        <?php the_archive_description('<p>', '</p>'); ?>
      </div>
      <?php if (have_posts()) : ?>
        <div class="card-grid">
          <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class('card'); ?>>
              <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
              <?php the_excerpt(); ?>
            </article>
          <?php endwhile; ?>
        </div>
      <?php else : ?>
        <p>No content found.</p>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php get_footer(); ?>
