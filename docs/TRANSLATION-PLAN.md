# Jacana i18n — Widget Translation & Auto-Translate Plan

**Status:** Draft — April 2026  
**Scope:** All `jacana-luxe-elementor` custom widgets + auto-translate-on-save feature  
**Target languages:** German (`de`), French (`fr`), Italian (`it`), Spanish (`es`)

---

## 1. Current State

### 1.1 How the translation pipeline works (end-to-end)

```
Elementor saves widget data
        ↓
_elementor_data postmeta (JSON)
        ↓
Jacana_I18n_Master_String_Exporter::export()
  → walks every post → every widget node
  → Jacana_I18n_Elementor_String_Extractor::extract_widget_strings()
     filters out: URLs, colours, layout keywords, CSS keys, icons, IDs
  → writes data/master-strings.json
        ↓
Jacana_I18n_Language_Pack_Manager::sync_with_master($lang)
  → adds new keys to languages/{lang}.json with status: 'missing'
  → marks removed keys as status: 'orphaned'
        ↓
Jacana_I18n_AI_Translator::translate_batch($items, $lang)
  → sends up to 20 items per Gemini request
  → respects glossary brand-locks (Jacana, Namibia, etc.)
  → writes translations back to language pack manager
        ↓
languages/{lang}.json  (key → { source, translation, status, sourceHash, updatedAt })
        ↓
Runtime: Jacana_I18n_Elementor_Bridge::translate_widget_render_content()
  → hooks: elementor/widget/render_content (priority 20)
  → for non-default language: str_replace source → translation in rendered HTML
```

### 1.2 What is translated today

| Language | Total keys | Translated |
|---|---|---|
| de | 3,170 | 93 (65 post_304 text + 28 catalog) |
| fr | ~3,170 | 0 |
| it | ~3,170 | 0 |
| es | ~3,170 | 0 |

### 1.3 Key structural finding

The live **Homepage (post_304)** is currently built entirely with **standard Elementor widgets**
(`heading`, `icon-box`, `button`, `image`, `icon-list`). Our custom `jacana_*` widgets are not yet
placed on the homepage — they live on other pages (see §1.4).

This is important because:
- The string extractor already captures standard Elementor widget strings — they appear in
  master-strings.json but have no translations yet.
- Custom widget strings ARE in master-strings for the pages they're placed on.
- Once the homepage is rebuilt with custom widgets (planned), those strings will auto-appear
  in master-strings on the next sync.

### 1.4 Pages using custom jacana_ widgets (current)

| Page | post_id | Custom widgets present |
|---|---|---|
| Car Rental | 150 | `jacana_car_rental_hero`, `jacana_car_rental_fleet_explorer`, `jacana_car_rental_cta` |
| Gallery | 176 | `jacana_gallery_grid`, `jacana_highlights_map` |
| About | 360 | `jacana_team_profiles` |
| Tours | 418 | `jacana_accommodation_styles`, `jacana_highlights_map`, `jacana_tailor_made_story`, `jacana_tours_inclusions` |
| Safari | 609 | `jacana_safaris_hero` |
| Services | 621 | `jacana_services_hero`, `jacana_main_services_grid` |
| Booking | 760 | `jacana_booking_premium_hero`, `jacana_booking_process_timeline`, `jacana_booking_request_studio` |
| Tailor Made Tours | 816 | `jacana_accommodation_styles`, `jacana_highlights_map`, `jacana_tailor_made_story`, `jacana_tours_inclusions` |
| FAQs | 870 | `jacana_faq_downloads`, `jacana_downloads_library` |
| About Us | 925 | `jacana_about_hero`, `jacana_about_split`, `jacana_affiliations`, `jacana_cta_banner`, `jacana_expos`, `jacana_mission_vision`, `jacana_team_profiles` |

---

## 2. Problem Analysis

### 2.1 Why some strings did not translate on the /de/ test

Three root causes, in order of frequency:

**A. Key mismatch — widget IDs change when re-saved**  
Each key is `post_{id}.widget_{widget_id}.settings.{path}`. Elementor assigns `widget_id` on
creation. If a widget is deleted and re-added, or a page is duplicated, the widget ID changes.
Old keys in de.json become orphaned and new ones are `missing`. The runtime bridge then finds
no translation for the new key, falls back to English.

*Fix:* Re-run "Sync All Languages" from the i18n admin after any page rebuild. This retires
orphaned keys and adds the new ones. Then re-run "Translate".

**B. String extractor skips some custom widget fields**  
The extractor uses a path-segment blocklist (`url`, `link`, `image`, `color`, `id`, `class`,
`icon`, etc.). Some custom widget field names inadvertently match these. For example, a field
named `map_key` is not filtered, but a field named `image_alt` would be skipped even if it
contains alt text.

*Fix:* Audit each custom widget's `register_controls()` field names against the blocklist.
Rename blocked field names where necessary (e.g. `hero_image` → `hero_photo`). (See §4.)

**C. Catalog (static UI) strings not bridged to frontend widgets**  
`catalog.*` keys cover header, AI concierge, and booking modal strings. These are rendered
by PHP/JS outside Elementor widget render, so the Elementor bridge doesn't touch them.
They require the gettext bridge (`class-gettext-bridge.php`) to function.

*Fix:* Confirm `Jacana_I18n_Gettext_Bridge` is hooked. If not, hook it on `init` in the
main plugin bootstrap.

---

## 3. Translation Work Plan — Phase by Phase

### Phase 1: Housekeeping (do this first, once)

**1a. Delete the conflicting old plugin file**

```bash
rm wp-content/plugins/jacana-ai-translator.php
```

This old v0.3.0 standalone file uses the same option keys as `jacana-i18n` and creates a
duplicate admin menu. It will corrupt glossary and settings if left active.

**1b. Re-export master-strings**

From WP Admin → Tools → Jacana i18n → click **"Export / Refresh Strings"**.  
This re-crawls every published Elementor page and rebuilds `data/master-strings.json`.

After exporting, verify the count has increased (it should now include all custom widget fields
added since the last export, including `home-hero`, `home-journey`, `destinations-strip`).

**1c. Sync all language packs**

Click **"Sync All Languages"**. This compares master-strings.json against each language JSON
and transitions:
- new keys → `missing`
- removed keys → `orphaned`
- unchanged keys with existing translations → no change

**1d. Remove orphaned keys**

Click **"Maintenance → Remove Orphaned"** for each language. This keeps the language files lean
and prevents stale translations from leaking into output.

---

### Phase 2: German (de) — complete first, use as quality reference

German is already partially translated (93 keys). Complete it before touching other languages.

**2a. Categorise untranslated German strings**

Run the admin translate view filtered by `status: missing`. Group by `postTitle` to see which
pages need the most work.

Priority order based on visitor-facing importance:
1. Homepage (post_304) — 251 strings, 65 translated, ~186 remaining
2. About Us (post_925) — 63 strings, 0 translated
3. Tours (post_418) — 223 strings, 0 translated
4. Services (post_621 + related) — ~170 strings combined
5. Car Rental (post_150) — 132 strings
6. Booking (post_760) — 75 strings
7. Tailor Made Tours (post_816) — 129 strings
8. Gallery (post_176) — 146 strings
9. FAQs (post_870) — 64 strings
10. Contact (post_526) — 51 strings

**2b. AI-translate all missing German strings**

Once the Gemini API key is available:
1. Go to i18n admin → German → Translate → mode: "missing" → limit: 250
2. Repeat until all keys have status `ai_draft`
3. Review `ai_draft` strings for brand accuracy (destination names, service names, tone)
4. Approve correct ones: status → `translated`
5. Fix wrong ones inline, save

**2c. Verify on /de/ pages**

Visit each page at `http://jacana-safaris-and-tours.local/de/{page-slug}/` and spot-check
visible text. Pay special attention to:
- Destination names (should NOT be translated — add to glossary if they are)
- CTA button labels
- Hero headings and kickers
- Repeater content (tour inclusions, fleet items, team bios)

---

### Phase 3: French, Italian, Spanish

Once German is clean and verified, run the same flow for `fr`, `it`, `es`:

1. Sync (already done in Phase 1c)
2. Translate → mode: "missing" → limit: 250 — repeat
3. Review ai_draft → approve or fix
4. Spot-check on `/fr/`, `/it/`, `/es/` frontends

These three languages will benefit from a German pass first because the glossary and brand
locks will already be tuned correctly from Phase 2.

---

### Phase 4: Widget field audit — ensure all text fields are captured

Go through each custom widget class and verify its text-bearing fields are not accidentally
blocked by the string extractor's path filter.

The extractor blocks any path segment matching:
`url | href | link | image | img | icon | id | class | classes | css | style | color |
background | padding | margin | border | width | height | alignment | align | position |
size | unit | typography | font | animation | delay | duration | speed | selected_icon | __globals__`

**Field names to audit in each widget:**

| Widget | Fields to check | Risk |
|---|---|---|
| `jacana_home_hero` | `kicker`, `title_line1`, `title_em`, `copy`, `cta_label`, `cta_secondary_label`, stat repeater `label`/`value` | Low — good names |
| `jacana_home_journey` | `kicker`, `heading`, `intro`, step repeater `step_title`/`step_body`, `cta_label` | Low |
| `jacana_destinations_strip` | `kicker`, `heading`, `intro`, card repeater `title`/`location`/`category`, `explore_label` | **Check `description` field on cards** — not blocked |
| `jacana_highlights_map` | hotspot repeater `title`/`body`, place pill `label` | Check if `title` path ends up as `hotspot.N.title` — not blocked |
| `jacana_car_rental_fleet_explorer` | 123 strings — largest custom widget. Verify `ac`, `transmission`, `seats`, capacity fields are translated | These single-word values may be filtered as "layout keywords" by `is_translatable_leaf()` |
| `jacana_booking_request_studio` | Form field labels, error messages, step titles | Medium — form field names may collide |
| `jacana_team_profiles` | `name`, `role`, `bio` per team member | Low — check `role` isn't treated as a path keyword |
| `jacana_mission_vision` | `mission_heading`, `mission_body`, `vision_heading`, `vision_body` | Low |
| `jacana_accommodation_styles` | Style repeater `label`/`description` | Low |
| `jacana_faq_downloads` | Question/answer repeater, `heading`, `intro` | Low |

**Action for blocked fields:** If a field is confirmed blocked (i.e. appears in master-strings
with no source value or doesn't appear at all), rename the Elementor control key in the
widget PHP class. Example: change `add_control('image_caption', …)` → `add_control('photo_caption', …)`.
The widget render `$settings['image_caption']` must be updated to match.

---

### Phase 5: Homepage rebuild with custom widgets

The homepage (post_304) is the highest-traffic page but is currently built with generic
Elementor widgets. The plan is to rebuild it with the custom `jacana_*` widgets that are
already created. Once rebuilt:

1. Export master-strings → new widget IDs appear
2. Sync languages → new keys appear as `missing`
3. Auto-translate (Phase 6) picks them up immediately on save

Planned homepage widget layout:
```
Section 1 — jacana_home_hero          (full-viewport video hero)
Section 2 — jacana_destinations_strip (6-card destination grid)
Section 3 — jacana_tailor_made_story  (who we are)
Section 4 — jacana_main_services_grid (what we offer)
Section 5 — jacana_home_journey       (3-step how it works)
Section 6 — jacana_accommodation_styles (places to stay)
Section 7 — jacana_social_proof       (testimonials)
Section 8 — jacana_affiliations       (trust logos)
Section 9 — jacana_cta_banner         (final CTA)
```

---

## 4. Auto-Translate on Elementor Save — Implementation Spec

This is the most impactful feature: when a content editor saves or updates any Elementor page,
the i18n plugin automatically:
1. Detects which widget strings are new or changed
2. Runs Gemini translation for those strings only
3. Writes translations to language JSON files

No manual admin steps required. Every content edit self-propagates to all target languages.

### 4.1 Hook entry point

Elementor fires `elementor/document/after_save` whenever a document is saved (both full saves
and autosaves). This is the right hook — it fires after `_elementor_data` postmeta is written,
so the extracted strings are already the new content.

```php
// In Jacana_I18n plugin bootstrap (jacana-i18n.php → bootstrap())
add_action('elementor/document/after_save', array($this, 'on_elementor_document_save'), 10, 2);
```

### 4.2 Handler logic (`on_elementor_document_save`)

```php
public function on_elementor_document_save($document, $data) {
    // Skip autosaves and revisions
    if (!$document instanceof \Elementor\Core\Documents_Manager) {
        // Check it's a saveable document type
    }
    if ($document->is_autosave()) {
        return;
    }

    $post_id = (int) $document->get_main_id();

    // 1. Extract strings from this post only
    $post_items = $this->extract_strings_for_post($post_id);
    if (empty($post_items)) {
        return;
    }

    // 2. Load current master-strings, merge in new items
    $master = $this->master_string_exporter->read_export();
    $existing_keys = array_column($master['items'] ?? array(), null, 'key');

    $new_or_changed = array();
    foreach ($post_items as $item) {
        $key = $item['key'];
        $existing = $existing_keys[$key] ?? null;

        if ($existing === null || $existing['source'] !== $item['source']) {
            // New string or source text changed
            $new_or_changed[] = $item;
            $existing_keys[$key] = $item;
        }
    }

    // 3. Re-export master-strings (full crawl, keeps other posts intact)
    $this->master_string_exporter->export();

    if (empty($new_or_changed)) {
        return; // Nothing changed, no translation needed
    }

    // 4. For each target language, translate new/changed strings
    $default_lang = $this->lang_detector->get_default_language();

    foreach ($this->lang_detector->get_supported_languages() as $lang => $lang_data) {
        if ($lang === $default_lang) {
            continue;
        }

        // Sync language pack first (adds new keys, marks old ones orphaned)
        $updated_master = $this->master_string_exporter->read_export();
        $this->language_pack_manager->sync_with_master($lang, $updated_master);

        // Translate only the new/changed items for this post
        $items_to_translate = array();
        foreach ($new_or_changed as $item) {
            $items_to_translate[] = array(
                'key'    => $item['key'],
                'source' => $item['source'],
            );
        }

        if (empty($items_to_translate)) {
            continue;
        }

        // Chunk into 20-item Gemini requests
        foreach (array_chunk($items_to_translate, 20) as $chunk) {
            $result = $this->ai_translator->translate_batch($chunk, $lang);
            if (!is_wp_error($result) && !empty($result)) {
                $this->language_pack_manager->apply_translations($lang, $result, 'ai_draft');
            }
        }
    }
}
```

### 4.3 `extract_strings_for_post($post_id)` helper

```php
private function extract_strings_for_post($post_id) {
    global $wpdb;

    $meta_value = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta}
         WHERE post_id = %d AND meta_key = '_elementor_data'
         LIMIT 1",
        $post_id
    ));

    if (!$meta_value) {
        return array();
    }

    $document = json_decode($meta_value, true);
    if (!is_array($document)) {
        return array();
    }

    $post = get_post($post_id);
    $items = array();

    $this->string_extractor->walk_document($document, function ($node, $strings) use (&$items, $post_id, $post) {
        $widget_id   = isset($node['id']) ? (string) $node['id'] : '';
        $widget_type = isset($node['widgetType']) ? (string) $node['widgetType'] : '';

        if ($widget_id === '' || $widget_type === '') {
            return;
        }

        foreach ($strings as $path => $source) {
            $key = sprintf('post_%d.widget_%s.settings.%s', $post_id, $widget_id, $path);
            $items[] = array(
                'key'        => $key,
                'source'     => $source,
                'postId'     => $post_id,
                'postTitle'  => $post ? $post->post_title : '',
                'postType'   => $post ? $post->post_type : '',
                'postStatus' => $post ? $post->post_status : '',
                'widgetId'   => $widget_id,
                'widgetType' => $widget_type,
                'path'       => (string) $path,
            );
        }
    });

    return $items;
}
```

### 4.4 Rate limiting and API guard

The Gemini API has per-minute request limits. A single Elementor save can produce dozens of
new strings. Guards needed:

```php
// Check API key exists before attempting
$api_key = get_option('jacana_i18n_gemini_api_key', '');
if (empty($api_key)) {
    return; // Silently skip — no key configured
}

// Transient-based cooldown: skip if another auto-translate ran in the last 30 seconds
if (get_transient('jacana_i18n_auto_translate_lock')) {
    // Queue for background processing instead (see §4.5)
    return;
}
set_transient('jacana_i18n_auto_translate_lock', 1, 30);
```

### 4.5 Background processing via WP-Cron (recommended for production)

Running Gemini calls synchronously during Elementor save adds latency to the editor experience.
Better approach: queue the translation job and process it asynchronously.

**Step A** — On save, write a pending job to a transient queue:
```php
$queue = get_option('jacana_i18n_pending_translations', array());
$queue[] = array(
    'post_id'  => $post_id,
    'items'    => $new_or_changed,
    'queued_at'=> time(),
);
update_option('jacana_i18n_pending_translations', $queue, false);
```

**Step B** — Register a WP-Cron job that fires every 2 minutes:
```php
if (!wp_next_scheduled('jacana_i18n_process_translation_queue')) {
    wp_schedule_event(time(), 'every_two_minutes', 'jacana_i18n_process_translation_queue');
}
add_action('jacana_i18n_process_translation_queue', array($this, 'process_translation_queue'));
```

**Step C** — `process_translation_queue()` dequeues jobs, translates, applies:
```php
public function process_translation_queue() {
    $queue = get_option('jacana_i18n_pending_translations', array());
    if (empty($queue)) {
        return;
    }

    // Process one job at a time to stay within rate limits
    $job = array_shift($queue);
    update_option('jacana_i18n_pending_translations', $queue, false);

    foreach ($this->lang_detector->get_supported_languages() as $lang => $lang_data) {
        if ($lang === $this->lang_detector->get_default_language()) {
            continue;
        }

        foreach (array_chunk($job['items'], 20) as $chunk) {
            $result = $this->ai_translator->translate_batch($chunk, $lang);
            if (!is_wp_error($result) && !empty($result)) {
                $this->language_pack_manager->apply_translations($lang, $result, 'ai_draft');
            }
        }
    }
}
```

### 4.6 Admin visibility — translation queue status

Add a small status indicator in the i18n admin panel showing:
- "Translation queue: N jobs pending"
- "Last auto-translated: {timestamp} — {N} strings across {M} languages"
- A "Process now" button to manually drain the queue without waiting for cron

---

## 5. Glossary — brand-locked terms

These strings must NEVER be machine-translated. Add them to the i18n Glossary in admin:

```
Jacana Safaris & Tours
Jacana
Namibia
Etosha National Park
Etosha
Sossusvlei
Namib Desert
Damaraland
Swakopmund
Walvis Bay
Sandwich Harbour
Spitzkoppe
Brandberg
Fish River Canyon
Skeleton Coast
Twyfelfontein
Himba
San Bushmen
Ovambo
```

Format in the glossary text area (one line per term, Jacana_I18n_AI_Translator reads this):
```
Jacana Safaris & Tours = Jacana Safaris & Tours
Namibia = Namibia
Etosha National Park = Etosha National Park
...
```

---

## 6. Content authoring guidelines for editors

Once auto-translate is live, these rules keep translations accurate:

1. **Write complete sentences.** The AI translator relies on context. Sentence fragments produce
   lower quality output. "Wildlife rich" → poor. "Wildlife-rich plains teeming with predators" → good.

2. **Don't embed HTML in Elementor text fields.** The string extractor and runtime bridge do
   string replacement on raw values. HTML mixed into a text field will corrupt the translation.
   Use Elementor's built-in rich-text (HTML widget) sparingly, and never for translatable content.

3. **Don't put URLs, phone numbers, or email addresses inside text fields alongside prose.**
   The extractor filters any string that looks like a URL/email. If "Contact us at hello@jacana.com"
   is one field value, the whole string is skipped. Keep the prose and the contact detail in
   separate fields.

4. **Use the Glossary for proper nouns.** If you add a new destination name, lodge name, or
   brand name, add it to the glossary before saving. Otherwise the AI may translate it.

5. **Review ai_draft translations before publishing a multilingual page.** Auto-translated
   strings land as `ai_draft` status. Review them via Tools → Jacana i18n → Review before
   considering a language "live".

---

## 7. Implementation Order & Checklist

### Immediate (before Gemini API key arrives)
- [ ] Delete `wp-content/plugins/jacana-ai-translator.php`
- [ ] Audit all custom widget field names against extractor blocklist (§4, widget table)
- [ ] Re-export master-strings from i18n admin
- [ ] Sync all language packs
- [ ] Remove orphaned keys for all languages
- [ ] Populate glossary with brand-locked terms (§5)
- [ ] Manually complete German translations for homepage and About Us (highest priority pages)

### When Gemini API key arrives
- [ ] Enter API key in i18n admin settings
- [ ] Run "Translate All Languages" → mode: missing → limit: 250 → repeat until 0 remaining
- [ ] Review German ai_draft strings (use as quality gate for other languages)
- [ ] Approve correct German translations → promote to `translated`
- [ ] Run same review for fr, it, es

### Auto-translate feature build
- [ ] Add `extract_strings_for_post()` helper to main i18n plugin class
- [ ] Hook `elementor/document/after_save` → `on_elementor_document_save()`
- [ ] Add transient-based API lock (30-second cooldown)
- [ ] Add WP-Cron queue (`jacana_i18n_pending_translations` option, every-2-min schedule)
- [ ] Add `process_translation_queue()` cron handler
- [ ] Add queue status display to i18n admin panel
- [ ] Test: edit a widget text field in Elementor → save → confirm translation job queued → confirm translations appear after cron fires
- [ ] Test: edit with no API key configured → confirm silent skip, no errors

### Homepage rebuild (parallel workstream)
- [ ] Build homepage in Elementor using the 9 custom widgets (see §3, Phase 5)
- [ ] After saving, run Export + Sync from i18n admin
- [ ] Confirm new widget string keys appear in master-strings.json
- [ ] Translate new homepage keys

---

## 8. File locations reference

| File | Purpose |
|---|---|
| `plugins/jacana-i18n/jacana-i18n.php` | Main plugin bootstrap — add save hook here |
| `plugins/jacana-i18n/includes/class-master-string-exporter.php` | String crawl + master-strings.json write |
| `plugins/jacana-i18n/includes/class-elementor-string-extractor.php` | Per-widget string extraction + path filtering |
| `plugins/jacana-i18n/includes/class-elementor-bridge.php` | Runtime str_replace of widget HTML |
| `plugins/jacana-i18n/includes/class-ai-translator.php` | Gemini API batch translate |
| `plugins/jacana-i18n/includes/class-language-pack-manager.php` | sync, apply_translations, get_items_for_translation |
| `plugins/jacana-i18n/includes/class-dictionary.php` | translate_string() — runtime lookup in language JSON |
| `plugins/jacana-i18n/data/master-strings.json` | Source of truth for all extracted strings |
| `plugins/jacana-i18n/languages/de.json` | German language pack |
| `plugins/jacana-i18n/languages/fr.json` | French language pack |
| `plugins/jacana-i18n/languages/it.json` | Italian language pack |
| `plugins/jacana-i18n/languages/es.json` | Spanish language pack |

---

*Last updated: 2026-04-11*
