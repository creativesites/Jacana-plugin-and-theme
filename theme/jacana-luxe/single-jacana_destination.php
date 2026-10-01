<?php
/**
 * Single template for jacana_destination post type.
 */
get_header();

the_post();
$id = get_the_ID();

// Core fields
$subtitle    = get_post_meta($id, '_jacana_dest_subtitle',  true);
$summary     = get_post_meta($id, '_jacana_dest_summary',   true);
$why_visit   = get_post_meta($id, '_jacana_dest_why_visit', true);

// Conditions
$best_months = get_post_meta($id, '_jacana_dest_best_months', true);
$typical_stay = get_post_meta($id, '_jacana_dest_typical_stay', true);
$roads       = get_post_meta($id, '_jacana_dest_roads',     true);
$family      = get_post_meta($id, '_jacana_dest_family',    true);
$climate     = get_post_meta($id, '_jacana_dest_climate',   true);
$access      = get_post_meta($id, '_jacana_dest_access',    true);

// Logistics
$accom_styles  = get_post_meta($id, '_jacana_dest_accom_styles',  true);
$nearby_bases  = get_post_meta($id, '_jacana_dest_nearby_bases',  true);
$combine_with  = get_post_meta($id, '_jacana_dest_combine_with',  true);
$trip_length   = get_post_meta($id, '_jacana_dest_trip_length',   true);
$transfers     = get_post_meta($id, '_jacana_dest_transfers',     true);
$permits       = get_post_meta($id, '_jacana_dest_permits',       true);
$booking_notes = get_post_meta($id, '_jacana_dest_booking_notes', true);
$route_pos     = get_post_meta($id, '_jacana_dest_route_pos',     true);

// Trust content
$wildlife  = get_post_meta($id, '_jacana_dest_wildlife',  true);
$landscape = get_post_meta($id, '_jacana_dest_landscape', true);
$culture   = get_post_meta($id, '_jacana_dest_culture',   true);
$safety    = get_post_meta($id, '_jacana_dest_safety',    true);

// Activities & FAQ (pipe-delimited, one per line)
$activities_raw = get_post_meta($id, '_jacana_dest_activities_json', true);
$faq_raw        = get_post_meta($id, '_jacana_dest_faq_json',        true);

// Gallery
$gallery_raw = get_post_meta($id, '_jacana_dest_gallery', true);
$gallery_ids = $gallery_raw ? array_filter(array_map('intval', explode(',', $gallery_raw))) : [];

// Conversion
$cta_label = get_post_meta($id, '_jacana_dest_cta', true) ?: __('Plan my journey here', 'jacana-luxe');

// Taxonomies
$region_terms = get_the_terms($id, 'destination_region');
$type_terms   = get_the_terms($id, 'destination_type');
$region_name  = ($region_terms && !is_wp_error($region_terms)) ? $region_terms[0]->name : '';
$type_name    = ($type_terms   && !is_wp_error($type_terms))   ? $type_terms[0]->name   : '';

// Hero image
$hero_img = get_the_post_thumbnail_url($id, 'full');

// GPS coords for map
$gps_raw    = get_post_meta($id, '_jacana_dest_gps', true);
$gps_coords = null;
if ($gps_raw && preg_match('/^([-\d.]+)\s*,\s*([-\d.]+)/', trim($gps_raw), $_m)) {
    $gps_coords = ['lat' => (float)$_m[1], 'lon' => (float)$_m[2]];
}

// Weather
$weather_data = null;
if (class_exists('Jacana_Weather_Service')) {
    $weather_data = Jacana_Weather_Service::get_for_post($id);
}

// Parse helpers
$why_lines   = $why_visit   ? array_filter(array_map('trim', explode("\n", $why_visit)))   : [];
$activity_lines = $activities_raw ? array_filter(array_map('trim', explode("\n", $activities_raw))) : [];
$faq_lines   = $faq_raw     ? array_filter(array_map('trim', explode("\n", $faq_raw)))     : [];
?>

<main class="dest-single">

    <!-- ── Hero ── -->
    <section class="dest-hero<?php echo $hero_img ? '' : ' dest-hero--no-image'; ?>"
             <?php if ($hero_img) : ?>style="background-image: url('<?php echo esc_url($hero_img); ?>');"<?php endif; ?>>
        <div class="dest-hero-overlay" aria-hidden="true"></div>
        <div class="dest-hero-inner">
            <div class="dest-hero-meta">
                <?php if ($region_name) : ?>
                    <span class="dest-hero-region"><?php echo esc_html($region_name); ?></span>
                <?php endif; ?>
                <?php if ($type_name) : ?>
                    <span class="dest-hero-type"><?php echo esc_html($type_name); ?></span>
                <?php endif; ?>
            </div>
            <h1 class="dest-hero-title jacana-reveal"><?php the_title(); ?></h1>
            <?php if ($subtitle) : ?>
                <p class="dest-hero-subtitle jacana-reveal" style="--jacana-delay: 100ms;"><?php echo esc_html($subtitle); ?></p>
            <?php endif; ?>
            <?php if ($why_lines) : ?>
                <ul class="dest-hero-reasons jacana-reveal" style="--jacana-delay: 180ms;" aria-label="<?php esc_attr_e('Reasons to visit', 'jacana-luxe'); ?>">
                    <?php foreach (array_slice($why_lines, 0, 4) as $reason) : ?>
                        <li><?php echo esc_html($reason); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="dest-hero-stripe" aria-hidden="true"></div>
    </section>

    <!-- ── Quick Facts Bar ── -->
    <?php if ($best_months || $typical_stay || $roads || $family || $weather_data) : ?>
    <div class="dest-facts-bar">
        <div class="dest-facts-inner">
            <?php if ($best_months) : ?>
                <div class="dest-fact">
                    <span class="dest-fact-label"><?php esc_html_e('Best time', 'jacana-luxe'); ?></span>
                    <span class="dest-fact-value"><?php echo esc_html($best_months); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($typical_stay) : ?>
                <div class="dest-fact">
                    <span class="dest-fact-label"><?php esc_html_e('Typical stay', 'jacana-luxe'); ?></span>
                    <span class="dest-fact-value"><?php echo esc_html($typical_stay); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($roads) : ?>
                <div class="dest-fact">
                    <span class="dest-fact-label"><?php esc_html_e('Roads', 'jacana-luxe'); ?></span>
                    <span class="dest-fact-value"><?php echo esc_html($roads); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($family) : ?>
                <div class="dest-fact">
                    <span class="dest-fact-label"><?php esc_html_e('Family', 'jacana-luxe'); ?></span>
                    <span class="dest-fact-value"><?php echo esc_html($family); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($route_pos) : ?>
                <div class="dest-fact">
                    <span class="dest-fact-label"><?php esc_html_e('Route position', 'jacana-luxe'); ?></span>
                    <span class="dest-fact-value"><?php echo esc_html($route_pos); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($weather_data && class_exists('Jacana_Luxe_Weather_Badge')) : ?>
                <div class="dest-fact dest-fact--weather">
                    <?php Jacana_Luxe_Weather_Badge::render_badge($weather_data, 'inline', 'dark', false, ''); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Body: summary + content ── -->
    <?php if ($summary || get_the_content()) : ?>
    <section class="dest-body">
        <div class="dest-body-inner">
            <?php if ($summary) : ?>
                <p class="dest-summary jacana-reveal"><?php echo esc_html($summary); ?></p>
            <?php endif; ?>
            <?php if (get_the_content()) : ?>
                <div class="dest-content jacana-reveal" style="--jacana-delay: 80ms;">
                    <?php the_content(); ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── Activities ── -->
    <?php if ($activity_lines) : ?>
    <section class="dest-activities">
        <div class="dest-section-inner">
            <h2 class="dest-section-heading jacana-reveal"><?php esc_html_e('Activities', 'jacana-luxe'); ?></h2>
            <div class="dest-activities-grid">
                <?php foreach ($activity_lines as $i => $line) :
                    $parts = array_map('trim', explode('|', $line));
                    if (empty($parts[0])) continue;
                ?>
                    <div class="dest-activity-card jacana-reveal" style="--jacana-delay: <?php echo esc_attr($i * 60); ?>ms;">
                        <h3 class="dest-activity-name"><?php echo esc_html($parts[0]); ?></h3>
                        <?php if (!empty($parts[1])) : ?>
                            <p class="dest-activity-desc"><?php echo esc_html($parts[1]); ?></p>
                        <?php endif; ?>
                        <div class="dest-activity-meta">
                            <?php if (!empty($parts[2])) : ?>
                                <span><?php echo esc_html($parts[2]); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($parts[3])) : ?>
                                <span><?php echo esc_html($parts[3]); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── Trust: wildlife / landscape / culture / climate ── -->
    <?php if ($wildlife || $landscape || $culture || $climate) : ?>
    <section class="dest-trust">
        <div class="dest-section-inner">
            <h2 class="dest-section-heading jacana-reveal"><?php esc_html_e('Good to know', 'jacana-luxe'); ?></h2>
            <div class="dest-trust-grid">
                <?php
                $trust_items = [
                    [__('Wildlife', 'jacana-luxe'), $wildlife],
                    [__('Landscape', 'jacana-luxe'), $landscape],
                    [__('Culture & etiquette', 'jacana-luxe'), $culture],
                    [__('Climate', 'jacana-luxe'), $climate],
                    [__('Safety', 'jacana-luxe'), $safety],
                    [__('Accessibility', 'jacana-luxe'), $access],
                ];
                foreach ($trust_items as [$label, $value]) :
                    if (!$value) continue;
                ?>
                    <div class="dest-trust-card jacana-reveal">
                        <h3 class="dest-trust-label"><?php echo esc_html($label); ?></h3>
                        <p><?php echo esc_html($value); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── Logistics ── -->
    <?php if ($accom_styles || $nearby_bases || $transfers || $permits || $combine_with || $booking_notes || $trip_length) : ?>
    <section class="dest-logistics">
        <div class="dest-section-inner">
            <h2 class="dest-section-heading jacana-reveal"><?php esc_html_e('Planning & logistics', 'jacana-luxe'); ?></h2>
            <dl class="dest-logistics-list jacana-reveal" style="--jacana-delay: 80ms;">
                <?php
                $logistics = [
                    [__('Accommodation styles', 'jacana-luxe'), $accom_styles],
                    [__('Recommended trip length', 'jacana-luxe'), $trip_length],
                    [__('Nearby bases', 'jacana-luxe'), $nearby_bases],
                    [__('Transfer options', 'jacana-luxe'), $transfers],
                    [__('Permits & park fees', 'jacana-luxe'), $permits],
                    [__('Combine with', 'jacana-luxe'), $combine_with],
                    [__('Booking notes', 'jacana-luxe'), $booking_notes],
                ];
                foreach ($logistics as [$label, $value]) :
                    if (!$value) continue;
                ?>
                    <div class="dest-logistics-row">
                        <dt><?php echo esc_html($label); ?></dt>
                        <dd><?php echo esc_html($value); ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── FAQ ── -->
    <?php if ($faq_lines) : ?>
    <section class="dest-faq">
        <div class="dest-section-inner">
            <h2 class="dest-section-heading jacana-reveal"><?php esc_html_e('Frequently asked', 'jacana-luxe'); ?></h2>
            <dl class="dest-faq-list">
                <?php foreach ($faq_lines as $i => $line) :
                    $parts = array_map('trim', explode('|', $line));
                    if (empty($parts[0])) continue;
                ?>
                    <div class="dest-faq-item jacana-reveal" style="--jacana-delay: <?php echo esc_attr($i * 60); ?>ms;">
                        <dt><?php echo esc_html($parts[0]); ?></dt>
                        <?php if (!empty($parts[1])) : ?>
                            <dd><?php echo esc_html($parts[1]); ?></dd>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── Gallery ── -->
    <?php if ($gallery_ids) : ?>
    <section class="dest-gallery">
        <div class="dest-section-inner">
            <h2 class="dest-section-heading jacana-reveal"><?php esc_html_e('Gallery', 'jacana-luxe'); ?></h2>
            <div class="dest-gallery-grid">
                <?php foreach ($gallery_ids as $i => $img_id) :
                    $full  = wp_get_attachment_image_url($img_id, 'full');
                    $large = wp_get_attachment_image_url($img_id, 'large');
                    $alt   = get_post_meta($img_id, '_wp_attachment_image_alt', true) ?: get_the_title();
                    if (!$large) continue;
                ?>
                    <a class="dest-gallery-item jacana-reveal"
                       href="<?php echo esc_url($full ?: $large); ?>"
                       style="--jacana-delay: <?php echo esc_attr($i * 50); ?>ms;"
                       data-gallery-lightbox
                       aria-label="<?php echo esc_attr($alt); ?>">
                        <img src="<?php echo esc_url($large); ?>"
                             alt="<?php echo esc_attr($alt); ?>"
                             loading="lazy">
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Lightbox -->
        <dialog class="dest-lightbox" data-lightbox aria-modal="true" aria-label="<?php esc_attr_e('Image lightbox', 'jacana-luxe'); ?>">
            <button class="dest-lightbox-close" data-lightbox-close aria-label="<?php esc_attr_e('Close', 'jacana-luxe'); ?>">&times;</button>
            <button class="dest-lightbox-prev" data-lightbox-prev aria-label="<?php esc_attr_e('Previous', 'jacana-luxe'); ?>">&#8249;</button>
            <button class="dest-lightbox-next" data-lightbox-next aria-label="<?php esc_attr_e('Next', 'jacana-luxe'); ?>">&#8250;</button>
            <figure class="dest-lightbox-figure">
                <img class="dest-lightbox-img" data-lightbox-img src="" alt="">
            </figure>
        </dialog>
        <script>
        (function () {
            var dialog  = document.querySelector('[data-lightbox]');
            var lbImg   = dialog && dialog.querySelector('[data-lightbox-img]');
            if (!dialog || !lbImg) return;
            var items   = Array.from(document.querySelectorAll('[data-gallery-lightbox]'));
            var current = 0;

            function show(index) {
                current = (index + items.length) % items.length;
                lbImg.src = items[current].href;
                lbImg.alt = items[current].getAttribute('aria-label') || '';
            }

            items.forEach(function (a, i) {
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    show(i);
                    dialog.showModal();
                });
            });

            dialog.querySelector('[data-lightbox-close]').addEventListener('click', function () { dialog.close(); });
            dialog.querySelector('[data-lightbox-prev]').addEventListener('click', function () { show(current - 1); });
            dialog.querySelector('[data-lightbox-next]').addEventListener('click', function () { show(current + 1); });

            dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });

            document.addEventListener('keydown', function (e) {
                if (!dialog.open) return;
                if (e.key === 'ArrowLeft')  show(current - 1);
                if (e.key === 'ArrowRight') show(current + 1);
                if (e.key === 'Escape')     dialog.close();
            });
        }());
        </script>
    </section>
    <?php endif; ?>

    <!-- ── Location Map ── -->
    <?php if ($gps_coords) :
        $map_lat = $gps_coords['lat'];
        $map_lon = $gps_coords['lon'];
        $map_embed_url = 'https://maps.google.com/maps?q=' . $map_lat . ',' . $map_lon . '&z=6&output=embed';
        $map_open_url  = 'https://maps.google.com/maps?q=' . $map_lat . ',' . $map_lon;
    ?>
    <section class="dest-map-section">
        <div class="dest-map-inner">
            <h2 class="dest-map-heading jacana-reveal"><?php esc_html_e('Location', 'jacana-luxe'); ?></h2>
            <div class="dest-map-frame jacana-reveal" style="--jacana-delay:60ms;">
                <iframe
                    src="<?php echo esc_url($map_embed_url); ?>"
                    width="100%"
                    height="100%"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="<?php echo esc_attr(sprintf(__('%s — location on map', 'jacana-luxe'), get_the_title())); ?>"
                ></iframe>
            </div>
            <a class="dest-map-link"
               href="<?php echo esc_url($map_open_url); ?>"
               target="_blank"
               rel="noopener noreferrer">
                <?php esc_html_e('Open in Google Maps', 'jacana-luxe'); ?>
                <span aria-hidden="true">↗</span>
            </a>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── CTA ── -->
    <section class="dest-cta-section">
        <div class="dest-section-inner dest-cta-inner">
            <div class="jacana-reveal">
                <p class="dest-cta-kicker"><?php esc_html_e('Ready to go?', 'jacana-luxe'); ?></p>
                <h2 class="dest-cta-heading"><?php printf(esc_html__('Plan your trip to %s', 'jacana-luxe'), get_the_title()); ?></h2>
            </div>
            <div class="dest-cta-actions jacana-reveal" style="--jacana-delay: 100ms;">
                <a href="/contact/" class="dest-cta-primary">
                    <?php echo esc_html($cta_label); ?>
                    <span aria-hidden="true">→</span>
                </a>
                <a href="<?php echo esc_url(get_post_type_archive_link('jacana_destination')); ?>" class="dest-cta-back">
                    <?php esc_html_e('All destinations', 'jacana-luxe'); ?>
                </a>
            </div>
        </div>
    </section>

</main>

<?php get_footer(); ?>
