<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Destination Explorer — interactive map with rich destination panel.
 * Sources exclusively from jacana_destination posts.
 * Preserves Jacana_Luxe_Highlights_Map which remains untouched.
 */
class Jacana_Luxe_Destination_Explorer extends \Elementor\Widget_Base {

	public function get_name()       { return 'jacana_destination_explorer'; }
	public function get_title()      { return __( 'Destination Explorer', 'jacana-luxe' ); }
	public function get_icon()       { return 'eicon-map-pin'; }
	public function get_categories() { return [ 'jacana-luxe' ]; }

	// ── Helpers ────────────────────────────────────────────────

	private function clamp( $v, $fallback = 50 ) {
		if ( $v === '' || $v === null ) return (float) $fallback;
		return max( 0, min( 100, (float) $v ) );
	}

	private function get_destinations( $limit = -1 ) {
		$query = new \WP_Query( [
			'post_type'      => 'jacana_destination',
			'post_status'    => 'publish',
			'posts_per_page' => $limit > 0 ? $limit : -1,
			'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
			'order'          => 'ASC',
			'no_found_rows'  => true,
		] );

		$items = [];

		foreach ( $query->posts as $post ) {
			$id = $post->ID;

			// Gallery images (featured first, then gallery)
			$media = [];
			$feat  = get_the_post_thumbnail_url( $id, 'large' );
			if ( $feat ) $media[] = $feat;

			$gallery_raw = get_post_meta( $id, '_jacana_dest_gallery', true );
			if ( $gallery_raw ) {
				foreach ( array_filter( array_map( 'intval', explode( ',', $gallery_raw ) ) ) as $gid ) {
					$url = wp_get_attachment_image_url( $gid, 'large' );
					if ( $url ) $media[] = $url;
				}
			}

			// Activities
			$activities = [];
			$acts_raw   = get_post_meta( $id, '_jacana_dest_activities_json', true );
			if ( $acts_raw ) {
				foreach ( array_filter( array_map( 'trim', explode( "\n", $acts_raw ) ) ) as $line ) {
					$p = array_map( 'trim', explode( '|', $line ) );
					if ( ! empty( $p[0] ) ) {
						$activities[] = [
							'name'       => $p[0],
							'desc'       => $p[1] ?? '',
							'duration'   => $p[2] ?? '',
							'difficulty' => $p[3] ?? '',
						];
					}
				}
			}

			// Why visit
			$why_raw = get_post_meta( $id, '_jacana_dest_why_visit', true );
			$why     = $why_raw
				? array_values( array_filter( array_map( 'trim', explode( "\n", $why_raw ) ) ) )
				: [];

			// Taxonomy terms
			$region_terms = get_the_terms( $id, 'destination_region' );
			$type_terms   = get_the_terms( $id, 'destination_type' );

			$items[] = [
				'id'           => $id,
				'title'        => get_the_title( $id ),
				'subtitle'     => (string) get_post_meta( $id, '_jacana_dest_subtitle',   true ),
				'summary'      => (string) get_post_meta( $id, '_jacana_dest_summary',    true ),
				'region'       => ( $region_terms && ! is_wp_error( $region_terms ) ) ? $region_terms[0]->name : '',
				'type'         => ( $type_terms   && ! is_wp_error( $type_terms   ) ) ? $type_terms[0]->name   : '',
				'x'            => $this->clamp( get_post_meta( $id, '_jacana_map_x', true ), 50 ),
				'y'            => $this->clamp( get_post_meta( $id, '_jacana_map_y', true ), 50 ),
				'media'        => $media,
				'why'          => array_slice( $why, 0, 5 ),
				'activities'   => array_slice( $activities, 0, 6 ),
				'best_months'  => (string) get_post_meta( $id, '_jacana_dest_best_months',  true ),
				'typical_stay' => (string) get_post_meta( $id, '_jacana_dest_typical_stay', true ),
				'roads'        => (string) get_post_meta( $id, '_jacana_dest_roads',         true ),
				'wildlife'     => (string) get_post_meta( $id, '_jacana_dest_wildlife',      true ),
				'landscape'    => (string) get_post_meta( $id, '_jacana_dest_landscape',     true ),
				'culture'      => (string) get_post_meta( $id, '_jacana_dest_culture',       true ),
				'permalink'    => get_permalink( $id ),
				'excerpt'      => get_the_excerpt( $post ),
				'weather'      => ( class_exists( 'Jacana_Weather_Service' ) && ! \Elementor\Plugin::$instance->editor->is_edit_mode() )
				                  ? Jacana_Weather_Service::get_for_post( $id )
				                  : null,
			];
		}

		wp_reset_postdata();
		return $items;
	}

	// ── Controls ───────────────────────────────────────────────

	protected function register_controls() {
		$this->start_controls_section( 'content', [ 'label' => __( 'Content', 'jacana-luxe' ) ] );

		$this->add_control( 'heading', [
			'label'   => __( 'Heading', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Explore Namibia', 'jacana-luxe' ),
		] );

		$this->add_control( 'intro', [
			'label'   => __( 'Intro text', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'default' => __( 'Select any destination on the map to explore details, activities and gallery.', 'jacana-luxe' ),
		] );

		$this->add_control( 'map_image', [
			'label' => __( 'Map Background Image', 'jacana-luxe' ),
			'type'  => \Elementor\Controls_Manager::MEDIA,
		] );

		$this->add_control( 'destinations_limit', [
			'label'       => __( 'Limit', 'jacana-luxe' ),
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'default'     => -1,
			'min'         => -1,
			'description' => __( 'Use -1 for all published destinations.', 'jacana-luxe' ),
		] );

		$this->add_control( 'cta_label', [
			'label'   => __( 'Primary CTA Label', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Plan my journey', 'jacana-luxe' ),
		] );

		$this->add_control( 'cta_link', [
			'label'   => __( 'Primary CTA Link', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::URL,
			'default' => [ 'url' => '/contact/' ],
		] );

		$this->add_control( 'view_label', [
			'label'   => __( 'View Page Label', 'jacana-luxe' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Full destination guide', 'jacana-luxe' ),
		] );

		$this->end_controls_section();
	}

	// ── Render ─────────────────────────────────────────────────

	protected function render() {
		$s         = $this->get_settings_for_display();
		$map       = ! empty( $s['map_image']['url'] ) ? $s['map_image']['url'] : '';
		$limit     = isset( $s['destinations_limit'] ) ? (int) $s['destinations_limit'] : -1;
		$wid       = esc_attr( $this->get_id() );
		$cta_label = ! empty( $s['cta_label'] ) ? $s['cta_label'] : __( 'Plan my journey', 'jacana-luxe' );
		$cta_url   = ! empty( $s['cta_link']['url'] ) ? $s['cta_link']['url'] : '/contact/';
		$view_lbl  = ! empty( $s['view_label'] ) ? $s['view_label'] : __( 'Full destination guide', 'jacana-luxe' );

		$destinations = $this->get_destinations( $limit );
		?>

		<section class="jacana-dex-section" data-dex-widget="<?php echo $wid; ?>">

			<!-- Header -->
			<header class="jacana-dex-header">
				<!-- <div class="jacana-dex-kicker jacana-reveal">
					<?php esc_html_e( 'EXPLORE NAMIBIA', 'jacana-luxe' ); ?>
				</div> -->
				<h2 class="jacana-dex-heading jacana-reveal" style="--jacana-delay:80ms;">
					<?php echo esc_html( $s['heading'] ); ?>
				</h2>
				<p class="jacana-dex-intro jacana-reveal" style="--jacana-delay:150ms;">
					<?php echo esc_html( $s['intro'] ); ?>
				</p>
			</header>

			<!-- Map + Panel -->
			<div class="jacana-dex-grid">

				<!-- Map column: canvas + pill list -->
				<div class="jacana-dex-map-col">

					<!-- Map canvas -->
					<div class="jacana-dex-canvas<?php echo empty( $map ) ? ' is-empty' : ''; ?>">
						<?php if ( $map ) : ?>
							<img class="jacana-dex-map-img"
							     src="<?php echo esc_url( $map ); ?>"
							     alt="<?php esc_attr_e( 'Destinations map of Namibia', 'jacana-luxe' ); ?>"
							     loading="lazy">
						<?php endif; ?>

						<?php foreach ( $destinations as $i => $dest ) : ?>
							<button
								class="jacana-dex-hotspot"
								type="button"
								aria-label="<?php echo esc_attr( $dest['title'] ); ?>"
								style="left:<?php echo esc_attr( $dest['x'] ); ?>%;top:<?php echo esc_attr( $dest['y'] ); ?>%;"
								data-dex-index="<?php echo esc_attr( $i ); ?>"
							>
								<span class="jacana-dex-hotspot-ring" aria-hidden="true"></span>
								<span class="jacana-dex-hotspot-dot"  aria-hidden="true"></span>
								<span class="jacana-dex-hotspot-label"><?php echo esc_html( $dest['title'] ); ?></span>
							</button>
						<?php endforeach; ?>
					</div>

					<!-- Pill list — below the map, left-aligned -->
					<?php if ( ! empty( $destinations ) ) : ?>
					<div class="jacana-dex-pills jacana-reveal" style="--jacana-delay:200ms;"
					     role="list" aria-label="<?php esc_attr_e( 'Browse all destinations', 'jacana-luxe' ); ?>">
						<?php foreach ( $destinations as $i => $dest ) : ?>
							<button class="jacana-dex-pill" type="button"
							        role="listitem"
							        data-dex-pill="<?php echo esc_attr( $i ); ?>">
								<?php echo esc_html( $dest['title'] ); ?>
							</button>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>

				</div><!-- /.jacana-dex-map-col -->

				<!-- Panel -->
				<aside class="jacana-dex-panel" aria-live="polite" aria-label="<?php esc_attr_e( 'Destination details', 'jacana-luxe' ); ?>">

					<!-- Empty state -->
					<div class="jacana-dex-empty" data-dex-empty>
						<svg class="jacana-dex-empty-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
							<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
							<circle cx="12" cy="9" r="2.5"/>
						</svg>
						<p class="jacana-dex-empty-text"><?php esc_html_e( 'Select a destination on the map to explore it in detail', 'jacana-luxe' ); ?></p>
					</div>

					<!-- Detail view -->
					<div class="jacana-dex-detail" data-dex-detail hidden>

						<!-- Slider -->
						<div class="jacana-dex-slider" data-dex-slider>
							<div class="jacana-dex-slides" data-dex-slides></div>

							<!-- Slider nav -->
							<button class="jacana-dex-sl-prev" type="button" data-dex-sl-prev aria-label="<?php esc_attr_e( 'Previous photo', 'jacana-luxe' ); ?>" hidden>&#8249;</button>
							<button class="jacana-dex-sl-next" type="button" data-dex-sl-next aria-label="<?php esc_attr_e( 'Next photo', 'jacana-luxe' ); ?>" hidden>&#8250;</button>

							<!-- Counter -->
							<div class="jacana-dex-sl-counter" data-dex-sl-counter aria-hidden="true"></div>

							<!-- Overlay: tags + title -->
							<div class="jacana-dex-sl-overlay" aria-hidden="true">
								<div class="jacana-dex-sl-tags" data-dex-sl-tags></div>
								<h3 class="jacana-dex-sl-title" data-dex-sl-title></h3>
							</div>
						</div>

						<!-- Thumbnail strip -->
						<div class="jacana-dex-thumbs" data-dex-thumbs></div>

						<!-- Scrollable body -->
						<div class="jacana-dex-body" data-dex-body>

							<p class="jacana-dex-subtitle" data-dex-subtitle hidden></p>

							<p class="jacana-dex-excerpt" data-dex-excerpt hidden></p>

							<div class="jacana-dex-facts-row" data-dex-facts hidden></div>

							<div class="jacana-dex-wx-row" data-dex-weather hidden></div>

							<p class="jacana-dex-summary" data-dex-summary hidden></p>

							<ul class="jacana-dex-why" data-dex-why hidden></ul>

							<div class="jacana-dex-acts-wrap" data-dex-acts hidden>
								<p class="jacana-dex-body-heading"><?php esc_html_e( 'Activities', 'jacana-luxe' ); ?></p>
								<div class="jacana-dex-acts-grid" data-dex-acts-grid></div>
							</div>

							<div class="jacana-dex-nature-wrap" data-dex-nature hidden>
								<p class="jacana-dex-body-heading"><?php esc_html_e( 'Natural highlights', 'jacana-luxe' ); ?></p>
								<div class="jacana-dex-nature-grid" data-dex-nature-grid></div>
							</div>

						</div>

						<!-- Footer CTAs -->
						<div class="jacana-dex-footer">
							<button type="button"
							        class="jacana-dex-ai-btn"
							        data-map-chat-cta
							        data-jacana-widget="jacana_destination_explorer"
							        data-dex-ai-btn>
								<?php esc_html_e( 'I want to know more about this place', 'jacana-luxe' ); ?>
								<span aria-hidden="true">→</span>
							</button>
							<!-- <a class="jacana-dex-page-link" href="#" data-dex-page target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Explore destination', 'jacana-luxe' ); ?>
								<span aria-hidden="true">↗</span>
							</a> -->
						</div>

					</div><!-- /.jacana-dex-detail -->
				</aside>

			</div><!-- /.jacana-dex-grid -->

		</section>

		<!-- Destination data -->
		<script type="application/json" id="jacana-dex-data-<?php echo $wid; ?>">
			<?php echo wp_json_encode( $destinations, JSON_HEX_TAG | JSON_HEX_AMP ); ?>
		</script>

		<script>
		(function () {
			var wid     = <?php echo wp_json_encode( $this->get_id() ); ?>;
			var section = document.querySelector('[data-dex-widget="' + wid + '"]');
			var dataEl  = document.getElementById('jacana-dex-data-' + wid);
			if (!section || !dataEl) return;

			var dests;
			try { dests = JSON.parse(dataEl.textContent); } catch(e) { return; }
			if (!dests || !dests.length) return;

			var dexWxIcons = <?php echo class_exists( 'Jacana_Weather_Service' ) ? wp_json_encode( Jacana_Weather_Service::get_icon_map(), JSON_HEX_TAG | JSON_HEX_AMP ) : '{}'; ?>;

			// Element refs
			var q = function(sel) { return section.querySelector(sel); };
			var emptyEl   = q('[data-dex-empty]');
			var detailEl  = q('[data-dex-detail]');
			var slidesEl  = q('[data-dex-slides]');
			var thumbsEl  = q('[data-dex-thumbs]');
			var counterEl = q('[data-dex-sl-counter]');
			var tagsEl    = q('[data-dex-sl-tags]');
			var titleEl   = q('[data-dex-sl-title]');
			var subtitleEl= q('[data-dex-subtitle]');
			var excerptEl = q('[data-dex-excerpt]');
			var factsEl   = q('[data-dex-facts]');
			var wxEl      = q('[data-dex-weather]');
			var summaryEl = q('[data-dex-summary]');
			var whyEl     = q('[data-dex-why]');
			var actsWrap  = q('[data-dex-acts]');
			var actsGrid  = q('[data-dex-acts-grid]');
			var natWrap   = q('[data-dex-nature]');
			var natGrid   = q('[data-dex-nature-grid]');
			var pageEl    = q('[data-dex-page]');
			var prevBtn   = q('[data-dex-sl-prev]');
			var nextBtn   = q('[data-dex-sl-next]');

			var currentSlide = 0;
			var totalSlides  = 0;

			// ── HTML escaping ──────────────────────────────────
				function esc(str) {
					var d = document.createElement('div');
					d.appendChild(document.createTextNode(str || ''));
					return d.innerHTML;
				}

				// Destination URL param aliases (mirrors Highlights Map behavior).
				var DEST_ALIASES = {
					'etosha waterhole': 'etosha national park',
					'etosha waterholes': 'etosha national park',
					'etosha': 'etosha national park',
					'rhino in etosha': 'etosha national park',
					'rhino in etosha national park': 'etosha national park',
					'rhino tracking': 'etosha national park',
					'okaukuejo waterhole': 'etosha national park',
					'elephants at okaukuejo waterhole': 'etosha national park',
					'sossusvlei dunes': 'sossusvlei',
					'dunes': 'sossusvlei',
					'namib dunes': 'sossusvlei',
					'sandwich harbour': 'walvisbay',
					'walvis bay': 'walvisbay',
					'walvis': 'walvisbay',
					'flamingos': 'swakopmund',
					'flamingo coast': 'swakopmund',
					'flamingos, swakopmund coast': 'swakopmund',
					'swakopmund coast': 'swakopmund',
					'spitzkoppe rock arch': 'damaraland',
					'spitzkoppe': 'damaraland',
					'spitzkoppe rock': 'damaraland'
				};

				function normalizeDestValue(raw) {
					if (!raw) return '';
					return String(raw)
						.toLowerCase()
						.trim()
						.replace(/,\s*$/, '')
						.replace(/\s+/g, ' ');
				}

				function slugifyDestValue(raw) {
					return normalizeDestValue(raw)
						.replace(/[^a-z0-9]+/g, '-')
						.replace(/^-+|-+$/g, '');
				}

				function compactDestValue(raw) {
					return slugifyDestValue(raw).replace(/-/g, '');
				}

				function permalinkSlug(url) {
					if (!url) return '';
					try {
						var parsed = new URL(url, window.location.origin);
						var parts = (parsed.pathname || '').split('/').filter(Boolean);
						return parts.length ? String(parts[parts.length - 1]).toLowerCase() : '';
					} catch (e) {
						return '';
					}
				}

				function resolveDestParam(raw) {
					if (!raw) return '';
					var normalized = normalizeDestValue(raw);
					return DEST_ALIASES[normalized] || normalized;
				}

				function findDestinationIndex(items, resolved) {
					var resolvedNorm = normalizeDestValue(resolved);
					if (!resolvedNorm) return -1;
					var resolvedSlug = slugifyDestValue(resolvedNorm);
					var resolvedCompact = compactDestValue(resolvedNorm);

					function getCandidates(dest) {
						var title = normalizeDestValue(dest && dest.title ? dest.title : '');
						var titleSlug = slugifyDestValue(title);
						var postSlug = permalinkSlug(dest && dest.permalink ? dest.permalink : '');
						var postCompact = postSlug ? postSlug.replace(/-/g, '') : '';
						var values = [];
						if (title) values.push(title);
						if (titleSlug) values.push(titleSlug);
						if (titleSlug) values.push(titleSlug.replace(/-/g, ''));
						if (postSlug) values.push(postSlug);
						if (postCompact) values.push(postCompact);
						return values;
					}

					for (var i = 0; i < items.length; i++) {
						var exactCandidates = getCandidates(items[i]);
						if (
							exactCandidates.indexOf(resolvedNorm) !== -1 ||
							(resolvedSlug && exactCandidates.indexOf(resolvedSlug) !== -1) ||
							(resolvedCompact && exactCandidates.indexOf(resolvedCompact) !== -1)
						) {
							return i;
						}
					}

					for (var j = 0; j < items.length; j++) {
						var title = normalizeDestValue(items[j] && items[j].title ? items[j].title : '');
						if (title && title.indexOf(resolvedNorm) === 0) {
							return j;
						}
					}

					var words = resolvedNorm.split(/[\s,]+/).filter(function(w) { return w.length > 3; });
					if (!words.length) return -1;

					for (var k = 0; k < items.length; k++) {
						var titleWords = normalizeDestValue(items[k] && items[k].title ? items[k].title : '').split(/\s+/);
						if (words.some(function(w) { return titleWords.indexOf(w) !== -1; })) {
							return k;
						}
					}

					return -1;
				}

			// ── Slider ─────────────────────────────────────────
			function buildSlider(media) {
				slidesEl.innerHTML = '';
				thumbsEl.innerHTML = '';
				currentSlide = 0;
				totalSlides  = media ? media.length : 0;

				if (!totalSlides) {
					slidesEl.innerHTML = '<div class="jacana-dex-slide jacana-dex-slide--empty"></div>';
					prevBtn.hidden = true;
					nextBtn.hidden = true;
					thumbsEl.hidden = true;
					counterEl.textContent = '';
					return;
				}

				media.forEach(function(url, i) {
					var slide = document.createElement('div');
					slide.className = 'jacana-dex-slide';
					slide.style.backgroundImage = 'url(' + url + ')';
					slidesEl.appendChild(slide);

					var thumb = document.createElement('button');
					thumb.type = 'button';
					thumb.className = 'jacana-dex-thumb' + (i === 0 ? ' is-active' : '');
					thumb.style.backgroundImage = 'url(' + url + ')';
					thumb.setAttribute('aria-label', 'Photo ' + (i + 1));
					thumb.dataset.dexThumb = i;
					thumbsEl.appendChild(thumb);
				});

				var showNav = totalSlides > 1;
				prevBtn.hidden = !showNav;
				nextBtn.hidden = !showNav;
				thumbsEl.hidden = !showNav;
				goSlide(0);
			}

			function goSlide(idx) {
				currentSlide = (idx + totalSlides) % totalSlides;
				slidesEl.style.transform = 'translateX(-' + (currentSlide * 100) + '%)';
				counterEl.textContent = totalSlides > 1 ? (currentSlide + 1) + ' / ' + totalSlides : '';

				thumbsEl.querySelectorAll('[data-dex-thumb]').forEach(function(t) {
					t.classList.toggle('is-active', parseInt(t.dataset.dexThumb, 10) === currentSlide);
				});
			}

			prevBtn && prevBtn.addEventListener('click', function() { goSlide(currentSlide - 1); });
			nextBtn && nextBtn.addEventListener('click', function() { goSlide(currentSlide + 1); });
			thumbsEl && thumbsEl.addEventListener('click', function(e) {
				var t = e.target.closest('[data-dex-thumb]');
				if (t) goSlide(parseInt(t.dataset.dexThumb, 10));
			});

			// Swipe support
			(function() {
				var startX = 0;
				var slider = q('[data-dex-slider]');
				if (!slider) return;
				slider.addEventListener('touchstart', function(e) { startX = e.touches[0].clientX; }, {passive:true});
				slider.addEventListener('touchend', function(e) {
					var diff = startX - e.changedTouches[0].clientX;
					if (Math.abs(diff) > 40) goSlide(diff > 0 ? currentSlide + 1 : currentSlide - 1);
				}, {passive:true});
			}());

			// ── Populate panel ─────────────────────────────────
			function populate(dest) {
				// Slider
				buildSlider(dest.media);

				// Overlay tags + title
				tagsEl.innerHTML =
					(dest.region ? '<span class="jacana-dex-tag jacana-dex-tag--region">' + esc(dest.region) + '</span>' : '') +
					(dest.type   ? '<span class="jacana-dex-tag jacana-dex-tag--type">'   + esc(dest.type)   + '</span>' : '');
				titleEl.textContent = dest.title || '';

				// Subtitle
				if (dest.subtitle) { subtitleEl.textContent = dest.subtitle; subtitleEl.hidden = false; }
				else { subtitleEl.hidden = true; }

				// Excerpt
				if (dest.excerpt) { excerptEl.textContent = dest.excerpt; excerptEl.hidden = true; }
				else { excerptEl.hidden = true; }

				// Facts
				var facts = [];
				if (dest.best_months)  facts.push(['Best time',  dest.best_months]);
				if (dest.typical_stay) facts.push(['Typical stay', dest.typical_stay]);
				if (dest.roads)        facts.push(['Roads', dest.roads]);
				if (facts.length) {
					factsEl.innerHTML = facts.map(function(f) {
						return '<div class="jacana-dex-fact">' +
							'<span class="jacana-dex-fact-lbl">' + esc(f[0]) + '</span>' +
							'<span class="jacana-dex-fact-val">' + esc(f[1]) + '</span>' +
							'</div>';
					}).join('');
					factsEl.hidden = false;
				} else { factsEl.hidden = true; }

				// Weather
				if (wxEl) {
					var wx = dest.weather;
					if (wx && wx.temp !== null && wx.temp !== undefined) {
						var iconKey = wx.icon || 'unknown';
						var iconSvg = dexWxIcons[iconKey] || dexWxIcons['unknown'] || '';
						var wxStats = '';
						if (wx.wind !== null && wx.wind !== undefined) {
							wxStats += '<span class="jacana-dex-wx-stat">' +
								'<span class="jacana-dex-wx-stat-lbl">Wind</span>' +
								'<span class="jacana-dex-wx-stat-val">' + esc(String(wx.wind)) + ' km/h</span>' +
								'</span>';
						}
						if (wx.humidity !== null && wx.humidity !== undefined) {
							wxStats += '<span class="jacana-dex-wx-stat">' +
								'<span class="jacana-dex-wx-stat-lbl">Humidity</span>' +
								'<span class="jacana-dex-wx-stat-val">' + esc(String(wx.humidity)) + '%</span>' +
								'</span>';
						}
						wxEl.innerHTML =
							'<div class="jacana-dex-wx">' +
								'<div class="jacana-dex-wx-icon" aria-hidden="true">' + iconSvg + '</div>' +
								'<div class="jacana-dex-wx-main">' +
									'<span class="jacana-dex-wx-temp">' + esc(String(wx.temp)) + '<sup class="jacana-dex-wx-unit">°C</sup></span>' +
									'<span class="jacana-dex-wx-label">' + esc(wx.label || '') + '</span>' +
								'</div>' +
								(wxStats ? '<div class="jacana-dex-wx-stats">' + wxStats + '</div>' : '') +
							'</div>';
						wxEl.hidden = false;
					} else {
						wxEl.hidden = true;
					}
				}

				// Summary
				if (dest.summary) { summaryEl.textContent = dest.summary; summaryEl.hidden = false; }
				else { summaryEl.hidden = true; }

				// Why visit
				if (dest.why && dest.why.length) {
					whyEl.innerHTML = dest.why.map(function(r) {
						return '<li>' + esc(r) + '</li>';
					}).join('');
					whyEl.hidden = false;
				} else { whyEl.hidden = true; }

				// Activities
				if (dest.activities && dest.activities.length) {
					actsGrid.innerHTML = dest.activities.map(function(a) {
						return '<div class="jacana-dex-act">' +
							'<span class="jacana-dex-act-name">' + esc(a.name) + '</span>' +
							(a.duration   ? '<span class="jacana-dex-act-meta">' + esc(a.duration)   + '</span>' : '') +
							(a.difficulty ? '<span class="jacana-dex-act-meta">' + esc(a.difficulty) + '</span>' : '') +
							'</div>';
					}).join('');
					actsWrap.hidden = false;
				} else { actsWrap.hidden = true; }

				// Natural highlights
				var nature = [];
				if (dest.wildlife)  nature.push(['Wildlife',  dest.wildlife]);
				if (dest.landscape) nature.push(['Landscape', dest.landscape]);
				if (dest.culture)   nature.push(['Culture',   dest.culture]);
				if (nature.length) {
					natGrid.innerHTML = nature.map(function(n) {
						return '<div class="jacana-dex-nat-card">' +
							'<span class="jacana-dex-nat-lbl">' + esc(n[0]) + '</span>' +
							'<p>' + esc(n[1]) + '</p></div>';
					}).join('');
					natWrap.hidden = false;
				} else { natWrap.hidden = true; }

				// CTAs
				if (pageEl) pageEl.href = dest.permalink || '#';

				// Scroll body back to top
				var body = q('[data-dex-body]');
				if (body) body.scrollTop = 0;
			}

			// ── Activate ───────────────────────────────────────
			function activate(idx) {
				var dest = dests[idx];
				if (!dest) return;

				// Toggle active states
				section.querySelectorAll('.jacana-dex-hotspot').forEach(function(h) { h.classList.remove('is-active'); });
				section.querySelectorAll('.jacana-dex-pill').forEach(function(p)    { p.classList.remove('is-active'); });
				var hs   = section.querySelector('.jacana-dex-hotspot[data-dex-index="' + idx + '"]');
				var pill = section.querySelector('.jacana-dex-pill[data-dex-pill="'     + idx + '"]');
				if (hs)   hs.classList.add('is-active');
				if (pill) pill.classList.add('is-active');

				// Show panel
				emptyEl.hidden  = true;
				detailEl.hidden = false;

				populate(dest);
			}

			// ── Event listeners ────────────────────────────────
			section.querySelectorAll('.jacana-dex-hotspot').forEach(function(h) {
				h.addEventListener('click', function() { activate(parseInt(h.dataset.dexIndex, 10)); });
			});
			section.querySelectorAll('.jacana-dex-pill').forEach(function(p) {
				p.addEventListener('click', function() { activate(parseInt(p.dataset.dexPill, 10)); });
			});

				// Default: first destination. Override with ?destination= URL param.
				var rawDestParam = new URLSearchParams(window.location.search).get('destination');
				if (rawDestParam) {
					var resolvedDest = resolveDestParam(rawDestParam);
					var matchIndex = findDestinationIndex(dests, resolvedDest);
					if (matchIndex > -1) {
						activate(matchIndex);
						setTimeout(function() {
							section.scrollIntoView({ behavior: 'smooth', block: 'start' });
						}, 350);
					} else {
						activate(0);
					}
				} else {
					activate(0);
				}

		}());
		</script>

		<?php
	}
}
