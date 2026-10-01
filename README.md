# Jacana Safaris & Tours — Plugin & Theme Repository

Source-controlled backup of all custom-built WordPress plugins, the theme, translations, and a database snapshot for the [Jacana Safaris & Tours](http://jacana-safaris-and-tours.local) site.

---

## Repository Structure

```
/
├── plugins/
│   ├── jacana-ai-concierge/        AI concierge chat backend (Google Gemini API)
│   ├── jacana-compliance/          Cookie consent banner + POPIA/GDPR compliance
│   ├── jacana-crm/                 Booking CRM, leads, sessions, SEO jobs
│   ├── jacana-force-http/          Force HTTP on local dev environments
│   ├── jacana-host-diagnostics/    Server diagnostics page for Local
│   ├── jacana-i18n/                Multi-language support (EN/DE/FR/IT/ES)
│   ├── jacana-luxe-elementor/      Custom Elementor widget library (43+ widgets)
│   ├── jacana-media-month-uploader Media uploader with month-folder organisation
│   ├── jacana-speed-doctor/        Page-by-page performance audit & optimisation
│   └── luxe-admin/                 Admin branding & dashboard customisations
├── theme/
│   └── jacana-luxe/                Custom WordPress theme (Elementor-based)
├── translations/
│   ├── master-strings.json         Canonical EN source strings for all UI text
│   ├── french.json                 French (FR) translation
│   ├── italian1.json               Italian (IT) translation (in-progress)
│   └── (de.json / es.json / it.json live inside the jacana-i18n plugin)
├── database/
│   └── jacana-db-backup-YYYY-MM-DD.sql.gz  Full MySQL dump (gzip compressed)
└── docs/
    └── TRANSLATION-PLAN.md         Translation roadmap and string coverage notes
```

---

## Local Development Setup (Local by Flywheel)

1. Create a new site in [Local](https://localwp.com/) called **jacana-safaris-and-tours**.
2. Import the database:
   ```bash
   gunzip database/jacana-db-backup-YYYY-MM-DD.sql.gz
   mysql -u root -proot local < database/jacana-db-backup-YYYY-MM-DD.sql
   ```
3. Copy plugins to `wp-content/plugins/`:
   ```bash
   cp -r plugins/* /path/to/site/wp-content/plugins/
   ```
4. Copy theme to `wp-content/themes/`:
   ```bash
   cp -r theme/jacana-luxe /path/to/site/wp-content/themes/
   ```
5. Copy root translation JSONs to site root (used by the translation import tool):
   ```bash
   cp translations/french.json /path/to/site/frnch.json
   cp translations/italian1.json /path/to/site/italian1.json
   ```
6. Activate plugins in WP Admin → Plugins.

---

## Key Custom Plugins

| Plugin | Purpose |
|---|---|
| `jacana-luxe-elementor` | Core widget library — 43+ custom Elementor widgets |
| `jacana-crm` | Lead capture, bookings, CRM sessions, weekly SEO reports |
| `jacana-ai-concierge` | AI-powered chat concierge (Google Gemini backend) |
| `jacana-i18n` | Language switcher, string catalog, language JSON files |
| `jacana-speed-doctor` | Performance audit tool, CSS bundling, smart video loading |
| `jacana-compliance` | Cookie consent, Privacy Policy, Terms of Service pages |
| `luxe-admin` | Branded WP Admin panel, dashboard customisation |

---

## Theme

**`jacana-luxe`** — A custom-built Elementor-compatible theme.

Brand tokens (all exposed as CSS custom properties):
- `--jacana-earth` `#2d2416` — Primary text
- `--jacana-dunes` `#db9751` — Accent / CTA
- `--jacana-savannah` `#fffae4` — Light backgrounds
- `--jacana-sky` `#9dcbdf` — Secondary accent

Fonts: **Cormorant Garamond** (headings) / **Sora** (body/UI)

---

## Database Backup

The `database/` folder contains a gzipped full `mysqldump` of the `local` database.
Restore with:
```bash
gunzip -c database/jacana-db-backup-YYYY-MM-DD.sql.gz | mysql -u root -p local
```

> **Note:** Update `siteurl` and `home` options after restoring to a different domain:
> ```sql
> UPDATE wp_options SET option_value = 'https://your-domain.com' WHERE option_name IN ('siteurl','home');
> ```

---

## Translations

The `jacana-i18n` plugin manages runtime language switching. String source files:

| File | Contents |
|---|---|
| `translations/master-strings.json` | All EN source strings (canonical reference) |
| `plugins/jacana-i18n/languages/de.json` | German |
| `plugins/jacana-i18n/languages/fr.json` | French |
| `plugins/jacana-i18n/languages/it.json` | Italian |
| `plugins/jacana-i18n/languages/es.json` | Spanish |
| `translations/french.json` | French draft (pre-import) |
| `translations/italian1.json` | Italian draft (pre-import) |

See `docs/TRANSLATION-PLAN.md` for string coverage status.
