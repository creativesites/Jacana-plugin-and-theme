<?php
get_header();
$uploads = wp_get_upload_dir();
$base = trailingslashit($uploads['baseurl']) . '2026/02/';
$hero_slides = array(
  array(
    'kicker' => 'Namibia • Tailor-made Journeys',
    'title' => 'Jacana Safaris & Tours',
    'copy' => 'Mastering the art of perfect adventure for 10+ years in the wild. Private tours, seamless logistics, and curated experiences designed around you.',
    'image' => $base . 'img-01.jpg',
  ),
  array(
    'kicker' => 'Signature Landscapes',
    'title' => 'Dunes, wildlife, and vast horizons',
    'copy' => 'From the ochre dunes of Sossusvlei to the coastal drama of the Skeleton Coast, we craft journeys that feel cinematic and personal.',
    'image' => $base . 'Sossusvlei-Dunes.jpg',
  ),
  array(
    'kicker' => 'Curated Luxury',
    'title' => 'Guided or self-drive elegance',
    'copy' => 'Choose a fully guided safari or take the wheel with our expert planning, premium accommodations, and 24/7 support.',
    'image' => $base . 'Skeleton_Coast.jpg',
  ),
);
$gallery_images = array(
  array('Etosha National Park', $base . 'Etosha_National_Park.jpg'),
  array('Sossusvlei', $base . 'Sossusvlei-Dunes.jpg'),
  array('Skeleton Coast', $base . 'Skeleton_Coast.jpg'),
  array('Sandwich Harbour', $base . 'Sandwich_Harbour.jpg'),
  array('Spitzkoppe', $base . 'Spitzkoppe-Rock-Arch.jpg'),
  array('Namib-Naukluft Park', $base . 'Namib_Naukluft_Park.jpg'),
  array('Rhino in Etosha', $base . 'Rhino-in-Etosha-National-Park.jpg'),
  array('Stars at Spitzkoppe', $base . 'Stars-at-Spitzkoppe.jpg'),
);
?>
<main>
  <?php
  if (have_posts()) :
    the_post();
    if (function_exists('elementor_theme_do_location') && elementor_theme_do_location('single')) {
      get_footer();
      return;
    }
    if (did_action('elementor/loaded') && class_exists('\\Elementor\\Plugin')) {
      $elementor_instance = \Elementor\Plugin::$instance;
      if ($elementor_instance->db->is_built_with_elementor(get_the_ID())) {
        the_content();
        get_footer();
        return;
      }
    }
    rewind_posts();
  endif;
  ?>
  <section class="hero hero-slider">
    <div class="hero-slides">
      <?php foreach ($hero_slides as $index => $slide) : ?>
        <div class="hero-slide<?php echo $index === 0 ? ' is-active' : ''; ?>" style="background-image: url('<?php echo esc_url($slide['image']); ?>');">
          <div class="hero-slide-overlay"></div>
          <div class="hero-inner">
            <div>
              <div class="hero-kicker"><?php echo esc_html($slide['kicker']); ?></div>
              <h1><?php echo esc_html($slide['title']); ?></h1>
              <p><?php echo esc_html($slide['copy']); ?></p>
              <div class="hero-actions">
                <a class="button button-primary" href="<?php echo esc_url(home_url('/contact')); ?>">Plan your journey</a>
                <a class="button button-ghost" href="#about">Discover Jacana</a>
              </div>
            </div>
            <div class="hero-card">
              <h3>Signature Experiences</h3>
              <ul>
                <li>Guided and self-drive tours across Namibia.</li>
                <li>Luxury camps, lodges, and exclusive retreats.</li>
                <li>Multilingual guides: English, German, French, Afrikaans.</li>
                <li>Private safari game drives and transfers.</li>
              </ul>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="hero-dots">
      <?php foreach ($hero_slides as $index => $slide) : ?>
        <button class="hero-dot<?php echo $index === 0 ? ' is-active' : ''; ?>" type="button" aria-label="<?php echo esc_attr('Slide ' . ($index + 1)); ?>" data-slide="<?php echo esc_attr($index); ?>"></button>
      <?php endforeach; ?>
    </div>
  </section>

  <section id="about" class="section">
    <div class="section-inner">
      <div class="section-header">
        <h2>The Adventure</h2>
        <p>Jacana Safaris & Tours is a Namibian-owned company founded in 2018, crafted by experts who live and breathe the rhythm of the wild.</p>
      </div>
      <div class="card-grid">
        <div class="card">
          <h3>Mission</h3>
          <p>To be competitive with high quality services, exceeding customer expectations by providing customized travelling and accommodation within local and global destinations.</p>
        </div>
        <div class="card">
          <h3>Vision</h3>
          <p>To be a recognized and most preferred travel, accommodation, tour operator and car rentals service provider in Namibia and beyond.</p>
        </div>
        <div class="card">
          <h3>Values & Objectives</h3>
          <p>Integrity, Passion, Respect. Our objective is to determine and meet the travel needs and service delivery expectations of all our clients, all the time, every time.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="section dark">
    <div class="section-inner split">
      <div>
        <h2>About Jacana Safaris & Tours</h2>
        <p>We strive fully to give you the best Namibian experience by planning your tours, executing your adventures, and delivering professional, friendly, reliable service.</p>
        <p>We assist you with English-, German-, French- and Afrikaans-speaking guides who bring Namibia to life in every landscape.</p>
        <div class="highlight">
          <p><strong>Founded in 2018</strong> by Namibians, Jacana is growing under the leadership of experienced personnel eager to contribute to Namibia’s tourism industry.</p>
        </div>
      </div>
      <div class="card dark">
        <h3>Why Jacana?</h3>
        <p>Precision itinerary design, premium accommodations, and a local team that knows every corner of Namibia.</p>
        <ul>
          <li>Personalized consultations</li>
          <li>Dedicated travel designers</li>
          <li>Seamless logistics</li>
        </ul>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="section-inner">
      <div class="section-header">
        <h2>Experience the Jacana Difference</h2>
        <p>Luxury travel design paired with deep local knowledge and seamless execution.</p>
      </div>
      <div class="card-grid">
        <div class="card">
          <h3>Tailor-made itineraries</h3>
          <p>Every route is crafted around your pace, budget, and sense of adventure.</p>
        </div>
        <div class="card">
          <h3>Premium stays</h3>
          <p>From elegant lodges to luxury tented camps, we select the finest stays.</p>
        </div>
        <div class="card">
          <h3>Local expertise</h3>
          <p>Namibian hosts who anticipate your needs and elevate every moment.</p>
        </div>
      </div>
    </div>
  </section>

  <section id="tours" class="section">
    <div class="section-inner">
      <div class="section-header">
        <h2>Tailor-made Tours</h2>
        <p>We plan your personalized Namibian adventure according to your wishes.</p>
      </div>
      <div class="split">
        <div>
          <p>Get in touch with us and we will schedule a complimentary consultation call. We’ll craft your itinerary, refine it with your input, then handle reservations and logistics with easy online payment.</p>
          <div class="tabs">
            <div class="tab">
              <span>How it works</span>
              <strong>Consult • Design • Book</strong>
            </div>
            <div class="tab">
              <span>Dates & Pricing</span>
              <strong>Custom based on your preferences</strong>
            </div>
            <div class="tab">
              <span>Accommodation</span>
              <strong>Luxury camps to rooftop tents</strong>
            </div>
          </div>
        </div>
        <div class="card">
          <h3>Guided Tour</h3>
          <p>Fully planned itinerary with a dedicated guide driving you from the first day to the last.</p>
          <p><strong>Included:</strong> Accommodation, meals as per itinerary, transport and fuel, park fees, guide, transfers, activities, car insurance.</p>
          <p><strong>Not included:</strong> Travel insurance, visas, tips, beverages, photography accessories.</p>
        </div>
      </div>
      <div class="card" style="margin-top: 26px;">
        <h3>Self-drive Tour</h3>
        <p>We deliver the full itinerary, you take the wheel. Our team stays available throughout your journey.</p>
        <p><strong>Included:</strong> Accommodation, meals as indicated, transport, car insurance.</p>
        <p><strong>Not included:</strong> Travel insurance, visas, tips, beverages, photography accessories, park fees, transfers, guide, fuel.</p>
      </div>
    </div>
  </section>

  <section class="section dark">
    <div class="section-inner">
      <div class="section-header">
        <h2>Destinations</h2>
        <p>From Etosha to the Skeleton Coast, we take you everywhere you want to explore in Namibia and beyond.</p>
      </div>
      <div class="gallery-grid">
        <?php foreach ($gallery_images as $item) : ?>
          <div class="gallery-item has-image" style="background-image: url('<?php echo esc_url($item[1]); ?>');">
            <span class="gallery-label"><?php echo esc_html($item[0]); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <p style="margin-top: 30px;">The listed destinations are the main Namibian attractions and we are not limited to them. Get in touch and we will discover your perfect route.</p>
    </div>
  </section>

  <section class="section">
    <div class="section-inner split">
      <div>
        <h2>Accommodation Styles</h2>
        <p>We design your stay to match your taste and budget.</p>
        <ul>
          <li>Luxury Tented Camps</li>
          <li>Lodges / Hotels</li>
          <li>Guest Houses</li>
          <li>Backpackers</li>
          <li>Rooftop Tent (on top of a car)</li>
          <li>Ground tent</li>
        </ul>
      </div>
      <div class="card">
        <h3>Tour Dates & Pricing</h3>
        <p>Tour dates are arranged according to you and prices are calculated based on tour type, vehicles, accommodation and travel duration.</p>
        <a class="button button-primary" data-jacana-booking-modal="true" data-jacana-widget="jacana_home_quote" data-jacana-service="tailor_made" href="#">Request a quote</a>
      </div>
    </div>
  </section>

  <section id="safari" class="section dark">
    <div class="section-inner split">
      <div>
        <h2>Safari Experience</h2>
        <p><strong>Game Drive in Etosha National Park.</strong> Travel in an open vehicle with an experienced guide on the lookout for lions, elephants, zebras and more.</p>
        <p>The Etosha National Park spans nearly 22,912 km² and is home to over 100 mammal species and 340 bird species.</p>
      </div>
      <div class="card dark">
        <h3>Etosha Highlights</h3>
        <p>The name Etosha means “big, white place.” Water holes sustain wildlife year-round, creating unforgettable viewing moments.</p>
      </div>
    </div>
  </section>

  <section class="section dark">
    <div class="section-inner">
      <div class="section-header">
        <h2>Begin your Namibia journey</h2>
        <p>Tell us your dream itinerary and we’ll craft a bespoke proposal within 48 hours.</p>
      </div>
      <div class="footer-cta">
        <a class="button button-primary" href="<?php echo esc_url(home_url('/contact')); ?>">Request a private consultation</a>
        <a class="button button-ghost" href="<?php echo esc_url(home_url('/tours')); ?>">Explore tours</a>
      </div>
    </div>
  </section>
</main>
<?php get_footer(); ?>
 
