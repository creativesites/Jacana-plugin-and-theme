<?php
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Handles custom fields for the Destination post type.
 */
class Jacana_Luxe_Destination_Fields {
  
  public static function register_meta_boxes($post) {
    add_meta_box(
      'jacana_destination_core',
      __('Core Identity', 'jacana-luxe'),
      array(__CLASS__, 'render_core_fields'),
      'jacana_destination',
      'normal',
      'high'
    );

    add_meta_box(
      'jacana_destination_location',
      __('Location & Map Settings', 'jacana-luxe'),
      array(__CLASS__, 'render_location_fields'),
      'jacana_destination',
      'normal',
      'high'
    );

    add_meta_box(
      'jacana_destination_conditions',
      __('Best Time & Conditions', 'jacana-luxe'),
      array(__CLASS__, 'render_conditions_fields'),
      'jacana_destination',
      'normal',
      'high'
    );

    add_meta_box(
      'jacana_destination_activities',
      __('Activities (Repeater)', 'jacana-luxe'),
      array(__CLASS__, 'render_activities_fields'),
      'jacana_destination',
      'normal',
      'high'
    );

    add_meta_box(
      'jacana_destination_logistics',
      __('Accommodation & Logistics', 'jacana-luxe'),
      array(__CLASS__, 'render_logistics_fields'),
      'jacana_destination',
      'normal',
      'high'
    );
    
    add_meta_box(
      'jacana_destination_trust',
      __('Trust & Authority Content', 'jacana-luxe'),
      array(__CLASS__, 'render_trust_fields'),
      'jacana_destination',
      'normal',
      'high'
    );

    add_meta_box(
      'jacana_destination_gallery',
      __('Photo Gallery', 'jacana-luxe'),
      array(__CLASS__, 'render_gallery_field'),
      'jacana_destination',
      'normal',
      'high'
    );

    add_meta_box(
      'jacana_destination_conversion',
      __('Conversion Settings', 'jacana-luxe'),
      array(__CLASS__, 'render_conversion_fields'),
      'jacana_destination',
      'side',
      'low'
    );
  }

  public static function render_core_fields($post) {
    wp_nonce_field('jacana_destination_meta', 'jacana_destination_meta_nonce');
    $subtitle = get_post_meta($post->ID, '_jacana_dest_subtitle', true);
    $summary = get_post_meta($post->ID, '_jacana_dest_summary', true);
    $why_visit = get_post_meta($post->ID, '_jacana_dest_why_visit', true);
    ?>
    <table class="form-table">
      <tr>
        <th scope="row"><label for="jacana_dest_subtitle"><?php _e('Destination Subtitle', 'jacana-luxe'); ?></label></th>
        <td><input type="text" id="jacana_dest_subtitle" name="jacana_dest_subtitle" value="<?php echo esc_attr($subtitle); ?>" class="large-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="jacana_dest_summary"><?php _e('Short Summary', 'jacana-luxe'); ?></label></th>
        <td><textarea id="jacana_dest_summary" name="jacana_dest_summary" rows="3" class="large-text"><?php echo esc_textarea($summary); ?></textarea></td>
      </tr>
      <tr>
        <th scope="row"><label for="jacana_dest_why_visit"><?php _e('Why Visit (One per line)', 'jacana-luxe'); ?></label></th>
        <td><textarea id="jacana_dest_why_visit" name="jacana_dest_why_visit" rows="5" class="large-text"><?php echo esc_textarea($why_visit); ?></textarea></td>
      </tr>
    </table>
    <?php
  }

  public static function render_location_fields($post) {
    $gps = get_post_meta($post->ID, '_jacana_dest_gps', true);
    $drive_times = get_post_meta($post->ID, '_jacana_dest_drive_times', true);
    $x = get_post_meta($post->ID, '_jacana_map_x', true);
    $y = get_post_meta($post->ID, '_jacana_map_y', true);
    $teaser = get_post_meta($post->ID, '_jacana_map_teaser', true);
    $description = get_post_meta($post->ID, '_jacana_map_description', true);
    ?>
    <table class="form-table">
      <tr>
        <th scope="row"><label for="jacana_dest_gps"><?php _e('GPS Coordinates', 'jacana-luxe'); ?></label></th>
        <td><input type="text" id="jacana_dest_gps" name="jacana_dest_gps" value="<?php echo esc_attr($gps); ?>" class="regular-text" placeholder="-22.5609, 17.0658"></td>
      </tr>
      <tr>
        <th scope="row"><label for="jacana_dest_drive_times"><?php _e('Drive Times (JSON or list)', 'jacana-luxe'); ?></label></th>
        <td><textarea id="jacana_dest_drive_times" name="jacana_dest_drive_times" rows="4" class="large-text"><?php echo esc_textarea($drive_times); ?></textarea></td>
      </tr>
      <tr>
        <th scope="row"><label for="jacana_map_x"><?php _e('Map Hotspot X (%)', 'jacana-luxe'); ?></label></th>
        <td><input type="number" step="0.1" id="jacana_map_x" name="jacana_map_x" value="<?php echo esc_attr($x); ?>" class="small-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="jacana_map_y"><?php _e('Map Hotspot Y (%)', 'jacana-luxe'); ?></label></th>
        <td><input type="number" step="0.1" id="jacana_map_y" name="jacana_map_y" value="<?php echo esc_attr($y); ?>" class="small-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="jacana_map_teaser"><?php _e('Map Teaser', 'jacana-luxe'); ?></label></th>
        <td><input type="text" id="jacana_map_teaser" name="jacana_map_teaser" value="<?php echo esc_attr($teaser); ?>" class="large-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="jacana_map_description"><?php _e('Map Panel Description', 'jacana-luxe'); ?></label></th>
        <td><textarea id="jacana_map_description" name="jacana_map_description" rows="3" class="large-text"><?php echo esc_textarea($description); ?></textarea></td>
      </tr>
    </table>
    <?php
  }

  public static function render_conditions_fields($post) {
    $best_months = get_post_meta($post->ID, '_jacana_dest_best_months', true);
    $climate = get_post_meta($post->ID, '_jacana_dest_climate', true);
    $roads = get_post_meta($post->ID, '_jacana_dest_roads', true);
    $family = get_post_meta($post->ID, '_jacana_dest_family', true);
    $access = get_post_meta($post->ID, '_jacana_dest_access', true);
    ?>
    <table class="form-table">
      <tr>
        <th scope="row"><label for="jacana_dest_best_months"><?php _e('Best Months', 'jacana-luxe'); ?></label></th>
        <td><input type="text" id="jacana_dest_best_months" name="jacana_dest_best_months" value="<?php echo esc_attr($best_months); ?>" class="large-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="jacana_dest_climate"><?php _e('Climate Notes', 'jacana-luxe'); ?></label></th>
        <td><textarea id="jacana_dest_climate" name="jacana_dest_climate" rows="3" class="large-text"><?php echo esc_textarea($climate); ?></textarea></td>
      </tr>
      <tr>
        <th scope="row"><label for="jacana_dest_roads"><?php _e('Road Conditions', 'jacana-luxe'); ?></label></th>
        <td><input type="text" id="jacana_dest_roads" name="jacana_dest_roads" value="<?php echo esc_attr($roads); ?>" class="large-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="jacana_dest_family"><?php _e('Family Suitability', 'jacana-luxe'); ?></label></th>
        <td><input type="text" id="jacana_dest_family" name="jacana_dest_family" value="<?php echo esc_attr($family); ?>" class="large-text"></td>
      </tr>
      <tr>
        <th scope="row"><label for="jacana_dest_access"><?php _e('Accessibility Notes', 'jacana-luxe'); ?></label></th>
        <td><textarea id="jacana_dest_access" name="jacana_dest_access" rows="2" class="large-text"><?php echo esc_textarea($access); ?></textarea></td>
      </tr>
    </table>
    <?php
  }

  public static function render_activities_fields($post) {
    $activities = get_post_meta($post->ID, '_jacana_dest_activities_json', true);
    ?>
    <p><?php _e('Enter activities as a list or simple JSON structure. For now, we will use a text area.', 'jacana-luxe'); ?></p>
    <textarea id="jacana_dest_activities_json" name="jacana_dest_activities_json" rows="10" class="large-text" placeholder="Activity Name | Description | Duration | Difficulty | Min Age | Shared/Private"><?php echo esc_textarea($activities); ?></textarea>
    <?php
  }

  public static function render_logistics_fields($post) {
    $accom_styles = get_post_meta($post->ID, '_jacana_dest_accom_styles', true);
    $typical_stay = get_post_meta($post->ID, '_jacana_dest_typical_stay', true);
    $nearby_bases = get_post_meta($post->ID, '_jacana_dest_nearby_bases', true);
    $booking_notes = get_post_meta($post->ID, '_jacana_dest_booking_notes', true);
    $trip_length = get_post_meta($post->ID, '_jacana_dest_trip_length', true);
    $combine_with = get_post_meta($post->ID, '_jacana_dest_combine_with', true);
    $route_pos = get_post_meta($post->ID, '_jacana_dest_route_pos', true);
    $transfers = get_post_meta($post->ID, '_jacana_dest_transfers', true);
    $permits = get_post_meta($post->ID, '_jacana_dest_permits', true);
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="jacana_dest_accom_styles"><?php _e('Suggested Styles', 'jacana-luxe'); ?></label></th>
            <td><input type="text" id="jacana_dest_accom_styles" name="jacana_dest_accom_styles" value="<?php echo esc_attr($accom_styles); ?>" class="large-text"></td>
        </tr>
        <tr>
            <th scope="row"><label for="jacana_dest_typical_stay"><?php _e('Typical Stay', 'jacana-luxe'); ?></label></th>
            <td><input type="text" id="jacana_dest_typical_stay" name="jacana_dest_typical_stay" value="<?php echo esc_attr($typical_stay); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th scope="row"><label for="jacana_dest_nearby_bases"><?php _e('Nearby Bases', 'jacana-luxe'); ?></label></th>
            <td><textarea id="jacana_dest_nearby_bases" name="jacana_dest_nearby_bases" rows="3" class="large-text"><?php echo esc_textarea($nearby_bases); ?></textarea></td>
        </tr>
        <tr>
            <th scope="row"><label for="jacana_dest_booking_notes"><?php _e('Booking Notes', 'jacana-luxe'); ?></label></th>
            <td><textarea id="jacana_dest_booking_notes" name="jacana_dest_booking_notes" rows="2" class="large-text"><?php echo esc_textarea($booking_notes); ?></textarea></td>
        </tr>
        <tr>
            <th scope="row"><label for="jacana_dest_trip_length"><?php _e('Recommended Trip Length', 'jacana-luxe'); ?></label></th>
            <td><input type="text" id="jacana_dest_trip_length" name="jacana_dest_trip_length" value="<?php echo esc_attr($trip_length); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th scope="row"><label for="jacana_dest_combine_with"><?php _e('Combine-with Destinations', 'jacana-luxe'); ?></label></th>
            <td><input type="text" id="jacana_dest_combine_with" name="jacana_dest_combine_with" value="<?php echo esc_attr($combine_with); ?>" class="large-text" placeholder="e.g. Sossusvlei, Etosha"></td>
        </tr>
        <tr>
            <th scope="row"><label for="jacana_dest_route_pos"><?php _e('Sample Route Position', 'jacana-luxe'); ?></label></th>
            <td><input type="text" id="jacana_dest_route_pos" name="jacana_dest_route_pos" value="<?php echo esc_attr($route_pos); ?>" class="regular-text" placeholder="Start / Mid / End"></td>
        </tr>
        <tr>
            <th scope="row"><label for="jacana_dest_transfers"><?php _e('Transfer Options', 'jacana-luxe'); ?></label></th>
            <td><input type="text" id="jacana_dest_transfers" name="jacana_dest_transfers" value="<?php echo esc_attr($transfers); ?>" class="large-text"></td>
        </tr>
        <tr>
            <th scope="row"><label for="jacana_dest_permits"><?php _e('Permit / Park Fee Notes', 'jacana-luxe'); ?></label></th>
            <td><textarea id="jacana_dest_permits" name="jacana_dest_permits" rows="2" class="large-text"><?php echo esc_textarea($permits); ?></textarea></td>
        </tr>
    </table>
    <?php
  }

  public static function render_trust_fields($post) {
    $wildlife = get_post_meta($post->ID, '_jacana_dest_wildlife', true);
    $landscape = get_post_meta($post->ID, '_jacana_dest_landscape', true);
    $culture = get_post_meta($post->ID, '_jacana_dest_culture', true);
    $safety = get_post_meta($post->ID, '_jacana_dest_safety', true);
    $faq = get_post_meta($post->ID, '_jacana_dest_faq_json', true);
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="jacana_dest_wildlife"><?php _e('Wildlife Highlights', 'jacana-luxe'); ?></label></th>
            <td><textarea id="jacana_dest_wildlife" name="jacana_dest_wildlife" rows="3" class="large-text"><?php echo esc_textarea($wildlife); ?></textarea></td>
        </tr>
        <tr>
            <th scope="row"><label for="jacana_dest_landscape"><?php _e('Landscape / Geology Facts', 'jacana-luxe'); ?></label></th>
            <td><textarea id="jacana_dest_landscape" name="jacana_dest_landscape" rows="3" class="large-text"><?php echo esc_textarea($landscape); ?></textarea></td>
        </tr>
        <tr>
            <th scope="row"><label for="jacana_dest_culture"><?php _e('Cultural Etiquette Notes', 'jacana-luxe'); ?></label></th>
            <td><textarea id="jacana_dest_culture" name="jacana_dest_culture" rows="3" class="large-text"><?php echo esc_textarea($culture); ?></textarea></td>
        </tr>
        <tr>
            <th scope="row"><label for="jacana_dest_safety"><?php _e('Safety Tips', 'jacana-luxe'); ?></label></th>
            <td><textarea id="jacana_dest_safety" name="jacana_dest_safety" rows="3" class="large-text"><?php echo esc_textarea($safety); ?></textarea></td>
        </tr>
        <tr>
            <th scope="row"><label for="jacana_dest_faq_json"><?php _e('FAQ (Repeater)', 'jacana-luxe'); ?></label></th>
            <td><textarea id="jacana_dest_faq_json" name="jacana_dest_faq_json" rows="6" class="large-text" placeholder="Question | Answer"><?php echo esc_textarea($faq); ?></textarea></td>
        </tr>
    </table>
    <?php
  }

  public static function render_gallery_field($post) {
    $raw    = get_post_meta($post->ID, '_jacana_dest_gallery', true);
    $ids    = $raw ? array_filter(array_map('intval', explode(',', $raw))) : array();
    ?>
    <style>
      .jacana-gf-wrap { display: flex; flex-direction: column; gap: 14px; }
      .jacana-gf-preview { display: flex; flex-wrap: wrap; gap: 10px; min-height: 40px; }
      .jacana-gf-thumb { position: relative; width: 80px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid #ddd; }
      .jacana-gf-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
      .jacana-gf-remove { position: absolute; top: 2px; right: 2px; background: rgba(0,0,0,0.65); color: #fff; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 0; }
    </style>
    <div class="jacana-gf-wrap" data-gallery-field>
      <input type="hidden" name="jacana_dest_gallery" id="jacana_dest_gallery"
             value="<?php echo esc_attr($raw); ?>">
      <div class="jacana-gf-preview" data-gallery-preview>
        <?php foreach ($ids as $img_id) :
          $thumb = wp_get_attachment_image_url($img_id, 'thumbnail');
          if (!$thumb) continue; ?>
          <div class="jacana-gf-thumb" data-id="<?php echo esc_attr($img_id); ?>">
            <img src="<?php echo esc_url($thumb); ?>" alt="">
            <button type="button" class="jacana-gf-remove" data-remove="<?php echo esc_attr($img_id); ?>"
                    aria-label="<?php esc_attr_e('Remove', 'jacana-luxe'); ?>">&times;</button>
          </div>
        <?php endforeach; ?>
      </div>
      <div>
        <button type="button" class="button jacana-gf-add" data-gallery-add>
          <?php esc_html_e('Add / Edit Gallery Images', 'jacana-luxe'); ?>
        </button>
      </div>
    </div>
    <?php
  }

  public static function render_conversion_fields($post) {
    $cta = get_post_meta($post->ID, '_jacana_dest_cta', true);
    $services = get_post_meta($post->ID, '_jacana_dest_services', true); // array
    $lead_tags = get_post_meta($post->ID, '_jacana_dest_lead_tags', true);
    ?>
    <p><strong><?php _e('Primary CTA Label', 'jacana-luxe'); ?></strong></p>
    <input type="text" name="jacana_dest_cta" value="<?php echo esc_attr($cta); ?>" class="widefat">
    
    <p><strong><?php _e('Service Package Context', 'jacana-luxe'); ?></strong></p>
    <input type="text" name="jacana_dest_lead_tags" value="<?php echo esc_attr($lead_tags); ?>" class="widefat" placeholder="e.g. adventure-safari">
    <?php
  }

  public static function save_meta($post_id) {
    if (!isset($_POST['jacana_destination_meta_nonce']) || !wp_verify_nonce($_POST['jacana_destination_meta_nonce'], 'jacana_destination_meta')) {
      return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }

    $fields = array(
      'jacana_dest_subtitle' => '_jacana_dest_subtitle',
      'jacana_dest_summary' => '_jacana_dest_summary',
      'jacana_dest_why_visit' => '_jacana_dest_why_visit',
      'jacana_dest_gps' => '_jacana_dest_gps',
      'jacana_dest_drive_times' => '_jacana_dest_drive_times',
      'jacana_map_x' => '_jacana_map_x',
      'jacana_map_y' => '_jacana_map_y',
      'jacana_map_teaser' => '_jacana_map_teaser',
      'jacana_map_description' => '_jacana_map_description',
      'jacana_dest_best_months' => '_jacana_dest_best_months',
      'jacana_dest_climate' => '_jacana_dest_climate',
      'jacana_dest_roads' => '_jacana_dest_roads',
      'jacana_dest_family' => '_jacana_dest_family',
      'jacana_dest_access' => '_jacana_dest_access',
      'jacana_dest_activities_json' => '_jacana_dest_activities_json',
      'jacana_dest_accom_styles' => '_jacana_dest_accom_styles',
      'jacana_dest_typical_stay' => '_jacana_dest_typical_stay',
      'jacana_dest_nearby_bases' => '_jacana_dest_nearby_bases',
      'jacana_dest_booking_notes' => '_jacana_dest_booking_notes',
      'jacana_dest_trip_length' => '_jacana_dest_trip_length',
      'jacana_dest_combine_with' => '_jacana_dest_combine_with',
      'jacana_dest_route_pos' => '_jacana_dest_route_pos',
      'jacana_dest_transfers' => '_jacana_dest_transfers',
      'jacana_dest_permits' => '_jacana_dest_permits',
      'jacana_dest_wildlife' => '_jacana_dest_wildlife',
      'jacana_dest_landscape' => '_jacana_dest_landscape',
      'jacana_dest_culture' => '_jacana_dest_culture',
      'jacana_dest_safety' => '_jacana_dest_safety',
      'jacana_dest_faq_json' => '_jacana_dest_faq_json',
      'jacana_dest_cta' => '_jacana_dest_cta',
      'jacana_dest_lead_tags' => '_jacana_dest_lead_tags',
    );

    foreach ($fields as $post_key => $meta_key) {
      if (isset($_POST[$post_key])) {
        update_post_meta($post_id, $meta_key, wp_unslash($_POST[$post_key]));
      }
    }

    // Gallery — store as sanitized comma-separated attachment IDs
    if (isset($_POST['jacana_dest_gallery'])) {
      $ids = implode(',', array_filter(array_map('intval', explode(',', $_POST['jacana_dest_gallery']))));
      update_post_meta($post_id, '_jacana_dest_gallery', $ids);
    }
  }
}
