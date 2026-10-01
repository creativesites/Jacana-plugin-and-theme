<?php
if (!defined('ABSPATH')) {
  exit;
}

class Jacana_Luxe_Gallery_Hero extends \Elementor\Widget_Base {
  public function get_name() {
    return 'jacana_gallery_hero';
  }

  public function get_title() {
    return __('Gallery Hero', 'jacana-luxe');
  }

  public function get_icon() {
    return 'eicon-banner';
  }

  public function get_categories() {
    return array('jacana-luxe');
  }

  protected function register_controls() {
    $this->start_controls_section('content_section', array('label' => __('Content', 'jacana-luxe')));

    $this->add_control('kicker', array(
      'label' => __('Kicker', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Gallery', 'jacana-luxe'),
    ));

    $this->add_control('title', array(
      'label' => __('Title', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Moments across Namibia', 'jacana-luxe'),
    ));

    $this->add_control('copy', array(
      'label' => __('Copy', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => __('A visual journey through our favorite landscapes and adventures.', 'jacana-luxe'),
    ));

    $this->add_control('background', array(
      'label' => __('Background Image', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
    ));

    $this->add_control('enable_parallax', array(
      'label' => __('Enable Parallax Effect', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::SWITCHER,
      'label_on' => __('Yes', 'jacana-luxe'),
      'label_off' => __('No', 'jacana-luxe'),
      'return_value' => 'yes',
      'default' => 'yes',
    ));

    $this->add_control('primary_cta_label', array(
      'label' => __('Primary CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Explore Gallery', 'jacana-luxe'),
    ));

    $this->add_control('primary_cta_link', array(
      'label' => __('Primary CTA Link', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'placeholder' => '#',
    ));

    $this->add_control('secondary_cta_label', array(
      'label' => __('Secondary CTA Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Plan Your Safari', 'jacana-luxe'),
    ));

    $this->add_control('secondary_cta_link', array(
      'label' => __('Secondary CTA Link', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::URL,
      'placeholder' => '#',
    ));

    $this->add_control('highlights', array(
      'label' => __('Hero Highlights (one per line)', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXTAREA,
      'default' => "Curated destination galleries\nAuthentic safari moments\nTailor-made journey planning",
    ));

    $this->add_control('stat_one_value', array(
      'label' => __('Stat 1 Value', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('16+', 'jacana-luxe'),
    ));
    $this->add_control('stat_one_label', array(
      'label' => __('Stat 1 Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Destination Galleries', 'jacana-luxe'),
    ));
    $this->add_control('stat_two_value', array(
      'label' => __('Stat 2 Value', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('500+', 'jacana-luxe'),
    ));
    $this->add_control('stat_two_label', array(
      'label' => __('Stat 2 Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Safari Moments', 'jacana-luxe'),
    ));
    $this->add_control('stat_three_value', array(
      'label' => __('Stat 3 Value', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('100%', 'jacana-luxe'),
    ));
    $this->add_control('stat_three_label', array(
      'label' => __('Stat 3 Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Tailor-Made', 'jacana-luxe'),
    ));

    $repeater = new \Elementor\Repeater();
    $repeater->add_control('image', array(
      'label' => __('Accent Image', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::MEDIA,
    ));
    $repeater->add_control('label', array(
      'label' => __('Label', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::TEXT,
      'default' => __('Namibia Highlight', 'jacana-luxe'),
    ));

    $this->add_control('accent_images', array(
      'label' => __('Accent Images', 'jacana-luxe'),
      'type' => \Elementor\Controls_Manager::REPEATER,
      'fields' => $repeater->get_controls(),
      'default' => array(
        array('label' => __('Desert Light', 'jacana-luxe')),
        array('label' => __('Wildlife Moment', 'jacana-luxe')),
        array('label' => __('Sunset Vista', 'jacana-luxe')),
      ),
      'title_field' => '{{{ label }}}',
    ));

    $this->end_controls_section();
  }

  private function render_highlights($input) {
    $items = array_filter(array_map('trim', explode("\n", (string) $input)));
    if (empty($items)) {
      return;
    }

    echo '<ul class="jacana-highlights-list">';
    foreach ($items as $index => $item) {
      $delay = 200 + ($index * 50);
      echo '<li class="jacana-reveal" style="--reveal-delay: ' . $delay . 'ms;" data-highlight-index="' . $index . '">';
      echo '<span class="highlight-icon">';
      echo '<svg width="12" height="12" viewBox="0 0 12 12" fill="none"><circle cx="6" cy="6" r="5" stroke="currentColor" stroke-width="1.5"/><circle cx="6" cy="6" r="2" fill="currentColor"/></svg>';
      echo '</span>';
      echo '<span class="highlight-text">' . esc_html($item) . '</span>';
      echo '</li>';
    }
    echo '</ul>';
  }

  protected function render() {
    $settings = $this->get_settings_for_display();
    $bg = !empty($settings['background']['url']) ? $settings['background']['url'] : '';
    $primary = !empty($settings['primary_cta_link']) ? $settings['primary_cta_link'] : array();
    $secondary = !empty($settings['secondary_cta_link']) ? $settings['secondary_cta_link'] : array();
    $primary_url = !empty($primary['url']) ? $primary['url'] : '#';
    $secondary_url = !empty($secondary['url']) ? $secondary['url'] : '#';
    $primary_external = !empty($primary['is_external']) && false === strpos(untrailingslashit($primary_url), untrailingslashit(home_url('/booking')));
    $secondary_external = !empty($secondary['is_external']) && false === strpos(untrailingslashit($secondary_url), untrailingslashit(home_url('/booking')));
    $accents = !empty($settings['accent_images']) && is_array($settings['accent_images']) ? $settings['accent_images'] : array();
    $accent_count = count($accents);
    $parallax = $settings['enable_parallax'] === 'yes' ? 'has-parallax' : '';
    ?>
    <section class="jacana-gallery-hero <?php echo esc_attr($parallax); ?>" data-jacana-hero<?php echo !empty($bg) ? ' style="--hero-bg:url(' . esc_url($bg) . ');"' : ''; ?>>
      
      <!-- Background Layers -->
      <div class="hero-bg-primary" data-parallax-speed="0.4"></div>
      <div class="hero-bg-overlay"></div>
      
      <!-- African Pattern Decorations -->
      <div class="hero-pattern-decoration hero-pattern-top-left" aria-hidden="true"></div>
      <div class="hero-pattern-decoration hero-pattern-bottom-right" aria-hidden="true"></div>
      
      <!-- Floating Light Particles -->
      <div class="hero-light-particles" aria-hidden="true">
        <?php for ($i = 1; $i <= 8; $i++): ?>
          <span class="light-particle light-particle-<?php echo $i; ?>"></span>
        <?php endfor; ?>
      </div>

      <div class="hero-container">
        <div class="hero-grid">
          
          <!-- Content Column -->
          <div class="hero-content">
            <div class="hero-content-inner">
              
              <!-- Kicker with African motif -->
              <!-- <div class="hero-kicker jacana-reveal" style="--reveal-delay: 0ms;">
                <span class="kicker-pattern" aria-hidden="true"></span>
                <svg class="kicker-icon" width="14" height="14" viewBox="0 0 14 14" fill="none">
                  <path d="M7 1L8.5 5L12.5 6L9.5 9L10.5 13L7 11L3.5 13L4.5 9L1.5 6L5.5 5L7 1Z" fill="currentColor"/>
                </svg>
                <span class="kicker-text"><?php echo esc_html($settings['kicker']); ?></span>
              </div> -->

              <!-- Title with decorative elements -->
              <h1 class="hero-title jacana-reveal jacana-reveal-scale" style="--reveal-delay: 80ms;">
                <span class="title-word"><?php echo esc_html($settings['title']); ?></span>
                <span class="title-accent-line" aria-hidden="true"></span>
              </h1>

              <!-- Copy -->
              <div class="hero-copy jacana-reveal" style="--reveal-delay: 160ms;">
                <p><?php echo esc_html($settings['copy']); ?></p>
              </div>

              <!-- Highlights -->
              <div class="hero-highlights jacana-reveal" style="--reveal-delay: 240ms;">
                <?php $this->render_highlights($settings['highlights'] ?? ''); ?>
              </div>

              <!-- CTA Buttons -->
              <div class="hero-actions jacana-reveal" style="--reveal-delay: 320ms;">
                <a class="hero-btn hero-btn-primary" data-jacana-ai-flow="destination" data-jacana-widget="jacana_gallery_hero" data-jacana-service="tailor_made" href="<?php echo esc_url($primary_external ? $primary_url : '#'); ?>"<?php echo $primary_external ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
                  <span class="btn-bg-layer"></span>
                  <span class="btn-text"><?php echo esc_html($settings['primary_cta_label']); ?></span>
                  <svg class="btn-arrow" width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M1 8H15M15 8L8 1M15 8L8 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </a>
                <a class="hero-btn hero-btn-secondary" data-jacana-ai-flow="destination" data-jacana-widget="jacana_gallery_hero" data-jacana-service="tailor_made" href="<?php echo esc_url($secondary_external ? $secondary_url : '#'); ?>"<?php echo $secondary_external ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
                  <span class="btn-border-layer"></span>
                  <span class="btn-text"><?php echo esc_html($settings['secondary_cta_label']); ?></span>
                </a>
              </div>

              <!-- Stats with African design elements -->
              <div class="hero-stats jacana-reveal" style="--reveal-delay: 400ms;">
                <div class="hero-stat" data-stat-index="0">
                  <div class="stat-pattern" aria-hidden="true"></div>
                  <div class="stat-value"><?php echo esc_html($settings['stat_one_value']); ?></div>
                  <div class="stat-label"><?php echo esc_html($settings['stat_one_label']); ?></div>
                </div>
                <div class="hero-stat" data-stat-index="1">
                  <div class="stat-pattern" aria-hidden="true"></div>
                  <div class="stat-value"><?php echo esc_html($settings['stat_two_value']); ?></div>
                  <div class="stat-label"><?php echo esc_html($settings['stat_two_label']); ?></div>
                </div>
                <div class="hero-stat" data-stat-index="2">
                  <div class="stat-pattern" aria-hidden="true"></div>
                  <div class="stat-value"><?php echo esc_html($settings['stat_three_value']); ?></div>
                  <div class="stat-label"><?php echo esc_html($settings['stat_three_label']); ?></div>
                </div>
              </div>

            </div>
          </div>

          <!-- Gallery Column -->
          <div class="hero-gallery">
            <div class="hero-gallery-inner jacana-reveal jacana-reveal-right" style="--reveal-delay: 180ms;">
              
              <!-- Gallery Header -->
              <div class="gallery-header">
                <div class="gallery-header-pattern" aria-hidden="true"></div>
                <div class="gallery-header-content">
                  <svg class="gallery-icon" width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <rect x="2" y="2" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M2 12L6 8L9 11L14 6L16 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <div class="gallery-header-text">
                    <div class="gallery-title"><?php echo esc_html__('Visual Journey', 'jacana-luxe'); ?></div>
                    <div class="gallery-subtitle"><?php echo esc_html__('Discover Namibia through curated moments', 'jacana-luxe'); ?></div>
                  </div>
                </div>
              </div>

              <!-- Gallery Grid -->
              <?php if (!empty($accents)) : ?>
                <div class="gallery-grid" data-accent-count="<?php echo esc_attr($accent_count); ?>">
                  <?php foreach ($accents as $index => $item) :
                    $image = !empty($item['image']['url']) ? $item['image']['url'] : '';
                    $delay = 480 + ($index * 100);
                    ?>
                    <figure class="gallery-item jacana-reveal jacana-reveal-zoom" style="--reveal-delay: <?php echo $delay; ?>ms;" data-accent-index="<?php echo $index; ?>">
                      <div class="gallery-item-frame">
                        <div class="gallery-item-border" aria-hidden="true"></div>
                        <div class="gallery-item-content">
                          <?php if (!empty($image)) : ?>
                            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($item['label'] ?? ''); ?>" loading="lazy">
                          <?php else : ?>
                            <div class="gallery-item-placeholder"></div>
                          <?php endif; ?>
                          <div class="gallery-item-overlay"></div>
                        </div>
                        <?php if (!empty($item['label'])) : ?>
                          <figcaption class="gallery-item-caption">
                            <span class="caption-icon"></span>
                            <span class="caption-text"><?php echo esc_html($item['label']); ?></span>
                          </figcaption>
                        <?php endif; ?>
                      </div>
                    </figure>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

            </div>
          </div>

        </div>
      </div>
    </section>
    
    <script>
/**
 * Jacana Gallery Hero - Enhanced Premium Interactions
 * Luxury safari experience with African-inspired animations
 */

(function() {
  'use strict';

  class JacanaGalleryHero {
    constructor(element) {
      this.element = element;
      this.bgLayer = element.querySelector('.hero-bg-primary');
      this.hasParallax = element.classList.contains('has-parallax');
      this.isInView = false;
      this.rafId = null;
      
      this.init();
    }

    init() {
      this.setupIntersectionObserver();
      if (this.hasParallax) {
        this.setupParallax();
      }
      this.setupStats();
      this.setupGalleryInteractions();
      this.setupButtonInteractions();
      this.setupLightParticles();
    }

    setupIntersectionObserver() {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting && !this.isInView) {
            this.isInView = true;
            this.element.classList.add('is-in-view');
            this.triggerRevealAnimations();
          }
        });
      }, { threshold: 0.15, rootMargin: '50px' });

      observer.observe(this.element);
    }

    triggerRevealAnimations() {
      const reveals = this.element.querySelectorAll('.jacana-reveal');
      reveals.forEach(el => {
        const delay = parseInt(el.style.getPropertyValue('--reveal-delay')) || 0;
        setTimeout(() => {
          el.classList.add('is-revealed');
        }, delay);
      });
    }

    setupParallax() {
      if (!this.bgLayer) return;

      let ticking = false;

      const update = () => {
        const rect = this.element.getBoundingClientRect();
        const scrolled = window.pageYOffset;
        
        if (rect.top < window.innerHeight && rect.bottom > 0) {
          const progress = (window.innerHeight - rect.top) / (window.innerHeight + rect.height);
          const translateY = (progress - 0.5) * 80;
          this.bgLayer.style.transform = `translate3d(0, ${translateY}px, 0) scale(1.15)`;
        }
        
        ticking = false;
      };

      const requestTick = () => {
        if (!ticking) {
          this.rafId = requestAnimationFrame(update);
          ticking = true;
        }
      };

      window.addEventListener('scroll', requestTick, { passive: true });
      update();
    }

    setupStats() {
      const stats = this.element.querySelectorAll('.hero-stat');
      
      stats.forEach((stat, index) => {
        const valueEl = stat.querySelector('.stat-value');
        const text = valueEl.textContent;
        const match = text.match(/(\d+)([+%]?)/);
        
        if (match) {
          const target = parseInt(match[1]);
          const suffix = match[2] || '';
          
          const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                this.animateNumber(valueEl, 0, target, 2000, suffix);
                observer.unobserve(stat);
              }
            });
          }, { threshold: 0.6 });
          
          observer.observe(stat);
        }

        // Interactive glow effect
        stat.addEventListener('mouseenter', () => {
          stat.classList.add('is-hovered');
        });

        stat.addEventListener('mouseleave', () => {
          stat.classList.remove('is-hovered');
        });

        stat.addEventListener('mousemove', (e) => {
          const rect = stat.getBoundingClientRect();
          const x = ((e.clientX - rect.left) / rect.width) * 100;
          const y = ((e.clientY - rect.top) / rect.height) * 100;
          stat.style.setProperty('--mouse-x', `${x}%`);
          stat.style.setProperty('--mouse-y', `${y}%`);
        });
      });
    }

    animateNumber(el, start, end, duration, suffix = '') {
      const range = end - start;
      const startTime = performance.now();
      
      const update = (currentTime) => {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Easing function for smooth animation
        const easeOut = 1 - Math.pow(1 - progress, 3);
        const current = Math.floor(start + (range * easeOut));
        
        el.textContent = current + suffix;
        
        if (progress < 1) {
          requestAnimationFrame(update);
        }
      };
      
      requestAnimationFrame(update);
    }

    setupGalleryInteractions() {
      const items = this.element.querySelectorAll('.gallery-item');
      
      items.forEach((item, index) => {
        const frame = item.querySelector('.gallery-item-frame');
        
        item.addEventListener('mouseenter', () => {
          item.classList.add('is-hovered');
          
          // Subtle scale down for siblings
          items.forEach(other => {
            if (other !== item) {
              other.classList.add('is-dimmed');
            }
          });
        });
        
        item.addEventListener('mouseleave', () => {
          item.classList.remove('is-hovered');
          items.forEach(other => other.classList.remove('is-dimmed'));
        });

        // 3D tilt effect
        item.addEventListener('mousemove', (e) => {
          if (!frame) return;
          
          const rect = item.getBoundingClientRect();
          const x = (e.clientX - rect.left) / rect.width;
          const y = (e.clientY - rect.top) / rect.height;
          
          const tiltX = (y - 0.5) * 8;
          const tiltY = (0.5 - x) * 8;
          
          frame.style.transform = `perspective(1200px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(1.02)`;
        });
        
        item.addEventListener('mouseleave', () => {
          if (frame) {
            frame.style.transform = '';
          }
        });
      });
    }

    setupButtonInteractions() {
      const buttons = this.element.querySelectorAll('.hero-btn');
      
      buttons.forEach(btn => {
        // Ripple effect
        btn.addEventListener('click', (e) => {
          const ripple = document.createElement('span');
          ripple.className = 'btn-ripple';
          
          const rect = btn.getBoundingClientRect();
          const size = Math.max(rect.width, rect.height) * 2;
          const x = e.clientX - rect.left - size / 2;
          const y = e.clientY - rect.top - size / 2;
          
          ripple.style.cssText = `
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
          `;
          
          btn.appendChild(ripple);
          setTimeout(() => ripple.remove(), 800);
        });

        // Magnetic hover effect
        const strength = 0.15;
        
        btn.addEventListener('mousemove', (e) => {
          const rect = btn.getBoundingClientRect();
          const centerX = rect.left + rect.width / 2;
          const centerY = rect.top + rect.height / 2;
          
          const deltaX = (e.clientX - centerX) * strength;
          const deltaY = (e.clientY - centerY) * strength;
          
          btn.style.transform = `translate(${deltaX}px, ${deltaY}px)`;
        });
        
        btn.addEventListener('mouseleave', () => {
          btn.style.transform = '';
        });
      });
    }

    setupLightParticles() {
      const particles = this.element.querySelectorAll('.light-particle');
      
      particles.forEach((particle, i) => {
        // Randomize animation timing
        const delay = Math.random() * 3;
        const duration = 8 + Math.random() * 6;
        
        particle.style.animationDelay = `${delay}s`;
        particle.style.animationDuration = `${duration}s`;
      });
    }

    destroy() {
      if (this.rafId) {
        cancelAnimationFrame(this.rafId);
      }
    }
  }

  // Initialize
  function init() {
    const heroes = document.querySelectorAll('[data-jacana-hero]');
    heroes.forEach(hero => new JacanaGalleryHero(hero));
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Elementor preview support
  if (window.elementorFrontend) {
    window.elementorFrontend.hooks.addAction(
      'frontend/element_ready/jacana_gallery_hero.default',
      function($scope) {
        const hero = $scope[0].querySelector('[data-jacana-hero]');
        if (hero) new JacanaGalleryHero(hero);
      }
    );
  }

})();
    </script>
    <?php
  }

  protected function content_template() {
    // Elementor editor preview template
  }
}