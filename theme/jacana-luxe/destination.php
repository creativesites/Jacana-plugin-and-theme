<?php
/**
 * The template for displaying Destination Archives
 * Premium, Editorial & Cinematic Design
 */

get_header();

// Get the current taxonomy context if applicable
$current_term = is_tax() ? get_queried_object() : null;
$title = $current_term ? $current_term->name : __('Explore Namibia', 'jacana-luxe');
$description = $current_term ? $current_term->description : __('Extraordinary destinations designed around your sense of adventure.', 'jacana-luxe');

// Hero Image Logic
$hero_image = get_template_directory_uri() . '/assets/img/hero-fallback.jpg'; // Default
$uploads = wp_get_upload_dir();
$base = trailingslashit($uploads['baseurl']) . '2026/02/';

// Check if we have a specific hero image for the term or CPT
if ($current_term) {
    // Optional: add logic here for term-specific images if available
    $hero_image = $base . 'Sossusvlei-Dunes.jpg'; 
} else {
    $hero_image = $base . 'Skeleton_Coast.jpg';
}

// Get all Regions for the filter bar
$regions = get_terms(array(
    'taxonomy' => 'destination_region',
    'hide_empty' => true,
));
?>

<main class="destinations-archive">
    <section class="destinations-hero" style="background-image: url('<?php echo esc_url($hero_image); ?>');">
        <div class="container">
            <span class="subtitle scroll-reveal"><?php echo esc_html__('Extraordinary Journeys', 'jacana-luxe'); ?></span>
            <h1 class="jacana-reveal"><?php echo esc_html($title); ?></h1>
            <p class="jacana-reveal" style="--jacana-delay: 200ms;"><?php echo esc_html($description); ?></p>
        </div>
    </section>

    <?php
    // Fetch destinations for the Map
    $map_query = new WP_Query(array(
        'post_type' => 'jacana_destination',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ));

    $hotspots = array();
    if ($map_query->have_posts()) {
        while ($map_query->have_posts()) {
            $map_query->the_post();
            $id = get_the_ID();
            $x = get_post_meta($id, '_jacana_map_x', true);
            $y = get_post_meta($id, '_jacana_map_y', true);
            
            // Only add if we have coordinates (or default some for discovery)
            if ($x !== '' && $y !== '') {
                $hotspots[] = array(
                    'id' => $id,
                    'title' => get_the_title(),
                    'x' => (float)$x,
                    'y' => (float)$y,
                    'teaser' => get_post_meta($id, '_jacana_dest_subtitle', true) ?: get_the_excerpt(),
                    'description' => get_post_meta($id, '_jacana_map_description', true) ?: wp_trim_words(get_the_content(), 25),
                    'image' => get_the_post_thumbnail_url($id, 'large'),
                    'permalink' => get_permalink(),
                );
            }
        }
        wp_reset_postdata();
    }

    $map_image_url = get_template_directory_uri() . '/assets/img/namibia_discovery_map.png';
    ?>

    <?php if (!empty($hotspots)) : ?>
    <!-- <section class="section jacana-highlights-map destinations-discovery-map">
        <div class="section-inner">
            <header class="jacana-map-header">
                <div class="jacana-tailor-story-kicker jacana-reveal">
                    <?php echo esc_html__('Interactive Explorer', 'jacana-luxe'); ?>
                </div>
                <h2 class="jacana-map-heading jacana-reveal" style="--jacana-delay: 80ms;">
                    <?php echo esc_html__('Discover the Wild', 'jacana-luxe'); ?>
                </h2>
                <p class="jacana-map-intro jacana-reveal" style="--jacana-delay: 150ms;">
                    <?php echo esc_html__('Click any hotspot on our custom map to explore Namibia’s most iconic regions.', 'jacana-luxe'); ?>
                </p>
            </header>

            <div class="jacana-highlights-map-grid">
                <div class="jacana-map-canvas jacana-reveal">
                    <img class="jacana-map-base-image" src="<?php echo esc_url($map_image_url); ?>" alt="Namibia Discovery Map" loading="lazy">
                    
                    <?php foreach ($hotspots as $index => $spot) : ?>
                        <button
                            class="jacana-map-hotspot"
                            type="button"
                            aria-label="<?php echo esc_attr($spot['title']); ?>"
                            style="left: <?php echo esc_attr($spot['x']); ?>%; top: <?php echo esc_attr($spot['y']); ?>%;"
                            data-title="<?php echo esc_attr($spot['title']); ?>"
                            data-teaser="<?php echo esc_attr($spot['teaser']); ?>"
                            data-description="<?php echo esc_attr($spot['description']); ?>"
                            data-image="<?php echo esc_url($spot['image']); ?>"
                            data-cta-label="<?php echo esc_attr__('Explore Destination', 'jacana-luxe'); ?>"
                            data-cta-link="<?php echo esc_url($spot['permalink']); ?>"
                            data-index="<?php echo esc_attr($index); ?>"
                        >
                            <span class="jacana-hotspot-label"><?php echo esc_html($spot['title']); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <aside class="card jacana-map-panel jacana-reveal jacana-reveal-right" style="--jacana-delay: 160ms;">
                    <div class="jacana-map-panel-media">
                        <img class="jacana-map-panel-image is-visible" data-map-image src="<?php echo esc_url($hotspots[0]['image']); ?>" alt="Destination preview" loading="lazy">
                        <div class="jacana-map-panel-media-overlay" aria-hidden="true">
                            <h6 class="jacana-map-panel-media-title" data-map-title-overlay><?php echo esc_html($hotspots[0]['title']); ?></h6>
                        </div>
                    </div>
                    <div class="jacana-map-panel-body">
                        <p class="jacana-map-panel-teaser" data-map-teaser><?php echo esc_html($hotspots[0]['teaser']); ?></p>
                        <p class="jacana-map-panel-description" data-map-description><?php echo esc_html($hotspots[0]['description']); ?></p>
                    </div>
                    <div class="jacana-map-actions">
                        <a href="<?php echo esc_url($hotspots[0]['permalink']); ?>" class="button button-primary jacana-tailor-cta-primary" data-map-chat-cta>
                            <?php echo esc_html__('Explore Destination', 'jacana-luxe'); ?>
                            <span class="jacana-cta-arrow" aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </aside>
            </div>

            <div class="jacana-map-places jacana-reveal" style="--jacana-delay: 200ms;">
                <?php foreach ($hotspots as $index => $spot) : ?>
                    <button class="jacana-map-place-pill" type="button" data-place-index="<?php echo esc_attr($index); ?>">
                        <?php echo esc_html($spot['title']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </section> -->
    <?php endif; ?>

    <nav class="destinations-filters">
        <div class="filter-inner">
            <a href="<?php echo esc_url(get_post_type_archive_link('jacana_destination')); ?>" 
               class="filter-pill <?php echo !is_tax('destination_region') ? 'is-active' : ''; ?>">
                <?php echo esc_html__('All Regions', 'jacana-luxe'); ?>
            </a>
            <?php foreach ($regions as $region) : ?>
                <a href="<?php echo esc_url(get_term_link($region)); ?>" 
                   class="filter-pill <?php echo ($current_term && $current_term->term_id === $region->term_id) ? 'is-active' : ''; ?>">
                    <?php echo esc_html($region->name); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>

    <section class="discovery-section">
        <div class="discovery-grid">
            <?php if (have_posts()) : ?>
                <?php while (have_posts()) : the_post(); 
                    $subtitle = get_post_meta(get_the_ID(), '_jacana_dest_subtitle', true);
                    $region_terms = get_the_terms(get_the_ID(), 'destination_region');
                    $region_name = ($region_terms && !is_wp_error($region_terms)) ? $region_terms[0]->name : '';
                    $image_url = get_the_post_thumbnail_url(get_the_ID(), 'large');

                    // Fallback for demo if no featured image
                    if (!$image_url) {
                        $image_url = $base . get_post_field('post_name', get_the_ID()) . '.jpg';
                    }

                    $gallery_raw   = get_post_meta(get_the_ID(), '_jacana_dest_gallery', true);
                    $gallery_count = $gallery_raw ? count(array_filter(explode(',', $gallery_raw))) : 0;
                ?>
                    <article <?php post_class('destination-card jacana-reveal'); ?>>
                        <a href="<?php the_permalink(); ?>" class="destination-card-link" aria-label="<?php the_title(); ?>"></a>
                        
                        <div class="destination-card-media">
                            <?php if ($image_url) : ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php the_title(); ?>" loading="lazy">
                            <?php endif; ?>
                        </div>

                        <div class="destination-card-overlay"></div>

                        <div class="destination-card-content">
                            <?php if ($region_name) : 
                                $translated_region = apply_filters('jacana_i18n_translate_string', $region_name, array('area' => 'destination_region'));
                            ?>
                                <span class="destination-card-region"><?php echo esc_html($translated_region); ?></span>
                            <?php endif; ?>
                            
                            <h3><?php the_title(); ?></h3>
                            
                            <?php if ($subtitle) : 
                                $translated_subtitle = apply_filters('jacana_i18n_translate_string', $subtitle, array('area' => 'destination_subtitle'));
                            ?>
                                <span class="destination-card-subtitle"><?php echo esc_html($translated_subtitle); ?></span>
                            <?php endif; ?>

                            <?php if ($gallery_count > 0) : ?>
                                <span class="destination-card-photo-count" aria-label="<?php echo esc_attr(sprintf(_n('%d photo', '%d photos', $gallery_count, 'jacana-luxe'), $gallery_count)); ?>">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                    <?php echo esc_html($gallery_count); ?>
                                </span>
                            <?php endif; ?>
                            <div class="destination-card-cta">
                                <?php echo esc_html__('Explore Destination', 'jacana-luxe'); ?>
                                <span class="icon" aria-hidden="true">→</span>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <div class="no-results container">
                    <p><?php echo esc_html__('No destinations found for this selection.', 'jacana-luxe'); ?></p>
                    <a href="<?php echo esc_url(get_post_type_archive_link('jacana_destination')); ?>" class="button button-outline">
                        <?php echo esc_html__('View All Destinations', 'jacana-luxe'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple reveal observer if theme logic isn't already handling it
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.jacana-reveal').forEach(el => observer.observe(el));
});
</script>

<?php get_footer(); ?>
