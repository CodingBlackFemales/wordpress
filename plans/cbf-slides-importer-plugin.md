# CBF Slides-to-LearnDash Importer Plugin

---

## Document Control

| Field | Value |
|---|---|
| **Plan version** | 1.3.0 |
| **Status** | Awaiting approval |
| **Depth tier** | **Standard** — content migration tool; elevated security treatment for OAuth token storage; no money flows, no shared counters, no irreversible structural DB changes |
| **Evidence baseline** | Local inspection · branch `claude/wizardly-yonath-c64ab1` (slides-to-learndash) · branch `main` (wordpress) · inspection date 2026-08-24 |
| **Changelog** | 1.3.0 — P0.4 complete: PhpPresentation probe passed 7/8 capabilities on 2 real CBF decks; A6/R1 resolved; image extraction method documented (Drawing\Gd::getContents()); pixel unit difference from python-pptx EMU noted; P2.4 implementation notes updated · 1.2.0 — AQ1 resolved; folder-restricted Picker added; SettingsPage, R12/R13, P1.9/P1.10, P2.3 updated · 1.1.0 — A1–A5/A7–A9 verified; R2/R5 closed; S3 removed; WP-Cron confirmed · 1.0.0 — initial plan |
| **Attribution** | Robust Feature Planner by Simeon Williams — Veedence.co.uk |
| **Planner** | Robust Feature Planner v3.0.0 (raw prompt) — plannerskill.veedence.com |

---

## Feature Summary

Replace a multi-step, CLI-dependent migration pipeline (Google Slides → PPTX export → local Python conversion → rsync → WP-CLI import) with a self-contained WordPress admin plugin that allows editors—including non-technical colleagues—to:

1. Pick a Google Slides deck directly from Drive via a browser-based file picker.
2. Configure how slides map to LearnDash structures (lesson, topics, slide headings, hidden slides) without touching a command line.
3. Preview the generated Gutenberg block HTML before any WordPress content is created.
4. Trigger the import with one click and track progress in the browser.

---

## Current-State Findings

### Pipeline (observed)

| Step | Tool | Who runs it | Pain point |
|---|---|---|---|
| 1 | Manual: update deck in Google Slides | Editor | Formatting must match implicit rules |
| 2 | Manual: File → Download → PPTX | Editor | Local file required |
| 3 | Manual: move file to project dir | Technical user | Requires repo checkout |
| 4 | `slides-to-learndash` Python CLI | Technical user | Python venv, CLI flags |
| 5 | `scripts/sync-export-rsync.sh` | Technical user | SSH credentials, rsync |
| 6 | `wp learndash-bulk import` via WP-CLI | Technical user | SSH + WP-CLI alias familiarity |

The `scripts/import-slides.sh` script in the wordpress repo orchestrates steps 2–6 into a single command, but still requires a local PPTX file and full CLI access.

### slides-to-learndash (Python package — observed)

- **Language/runtime**: Python ≥ 3.10, `python-pptx ≥ 0.6.21`, `typer ≥ 0.9.0`
- **Modes**: `lesson-only` (one merged lesson row) / `lesson-with-topics` (lesson row + N topic rows split by slide layout name regex)
- **Extraction modules**: `extract.py` (SlideExtract dataclass, geometry-based column detection via `slide_geometry.py`), `rich_text.py` (paragraph classification), `blocks.py` (WP block serialiser), `pipeline.py` (orchestration), `export_csv.py`
- **Geometry analysis**: `slide_geometry.py` uses EMU bounding-box overlap ratios to detect multi-column layouts — this logic is non-trivial and is the key intellectual asset to port or wrap
- **Hidden slides**: detected via OOXML `show` attribute (`slide_visibility.py`)
- **Cover slide**: slide 1 is always excluded from `post_content`
- **Images**: extracted as PNG/JPEG files into `media/<deck-stem>/`, referenced by relative path in CSV HTML
- **Copyright stripping**: SECTION_HEADER layout slides have body text and footer images removed

### learndash-bulk-lessons-or-topics plugin (observed, v1.2.2)

- Provides `wp learndash-bulk import <csv> --content-type=<slug> --media-dir=<path>` WP-CLI command
- Provides admin UI under LearnDash > Bulk Import (CSV upload form)
- `ELDBC_Media::rewrite_paths()` swaps `media/…` relative paths in HTML for real WP attachment URLs with SHA-256 deduplication
- `run_import_cli()` method handles actual WP post creation — **reusable programmatically**
- Supports post types: `sfwd-courses`, `sfwd-lessons`, `sfwd-topic`, `sfwd-quiz`, `sfwd-question`
- Already installed as a Composer dependency (`serenichron/learndash-bulk-lessons-or-topics: ^1.2.1`)

### WordPress environment (observed)

- **Stack**: Bedrock/Roots multisite, PHP 8.5, MariaDB 8.4, Apache, Lando (local dev), staging/prod on shared VPS (82.29.186.233:65002)
- **Multisite**: three subsites — `wp.`, `academy.`, `jobs.` — imports target `academy.codingblackfemales.com`
- **Media**: `humanmade/s3-uploads` is present in `composer.json` but **disabled in all environments** (confirmed A5); uploads are stored on the local filesystem via standard WP `wp_handle_upload`. `ELDBC_Media` returns local attachment URLs — no S3 path-rewriting concern.
- **Existing custom plugin**: `cbf-multisite` — site-specific plugin; has its own `src/`, `includes/`, build pipeline (`webpack.config.js`), coding standards (`phpcs.xml`)
- **Auth plugins**: `wp-rest-api-authentication`, `members` — REST API auth infrastructure exists
- **Background jobs**: WP-Cron used for background job dispatch; server-level cron job (configured via hosting control panel) requests `wp-cron.php` on a schedule, making it reliable (A3 confirmed). No Action Scheduler dependency required.
- **No Google API integration** currently observed anywhere in the codebase

### Existing tests (observed)

| Test file | What it guards |
|---|---|
| `tests/test_columns_layout.py` | Geometry-based multi-column detection (requires `demo.pptx` fixture) |
| `tests/test_hidden_slides.py` | OOXML hidden-slide detection |
| `tests/test_slide_merge.py` | Same-title slide merge / heading suppression logic |
| `tests/test_content_postprocess.py` | Session-outline column stripping |

**No PHP tests exist** for the bulk import plugin or cbf-multisite. No integration tests for the end-to-end pipeline exist.

---

## Assumptions and Open Questions

| ID | Assumption | Status | Risk if wrong | Verification needed |
|---|---|---|---|---|
| A1 | Python 3.10+ is **not** reliably available on the production VPS | ✅ **Verified** — Python is not installed | None — PHP-only approach confirmed | — |
| A2 | `run_import_cli()` is the correct public entry point to reuse from learndash-bulk | ✅ **Verified** — confirmed correct entrypoint | None | — |
| A3 | WP-Cron is reliable because server-level cron jobs (via control panel) request `wp-cron.php` on a schedule | ✅ **Verified** — proper cron triggers configured in server control panel | None — no Action Scheduler needed | — |
| A4 | The production server can make outbound HTTPS requests to Google APIs | ✅ **Verified** — `https://www.googleapis.com/drive/v3/about` confirmed accessible from prod | None | — |
| A5 | S3 Uploads plugin is **disabled** in all environments; media is stored locally via standard WP uploads | ✅ **Verified** — S3 Uploads disabled everywhere; local file system used | None — `ELDBC_Media` works unchanged with local paths | — |
| A6 | PhpPresentation (Apache POI-compatible PHP library) can parse the PPTX files in use with sufficient fidelity for this use case | ✅ **Verified** — P0.4 probe: 7/8 capabilities PASS on both CBF decks. Image extraction uses `Drawing\Gd::getContents()` (not `getPath()`). Shape dims in pixels (÷9525 to convert from python-pptx EMU). Multi-column detection fully functional. | None — all critical capabilities confirmed | P0.4 complete |
| A7 | Per-user Google OAuth is the right credential model; each user authenticates with their own Google account; the Drive Picker is constrained to a configurable CBF shared folder ID so partner-org users cannot browse unrelated internal Drive content | ✅ **Verified** — AQ1 resolved: partner users import from a CBF shared folder (AQ1-b). Per-user OAuth + folder-restricted picker confirmed. | None | — |
| A8 | The new plugin lives in `web/app/plugins/cbf-slides-importer/` managed by the wordpress repo | ✅ **Verified** | None | — |
| A9 | WP-Cron is sufficient; Action Scheduler is not required | ✅ **Verified** — server-level cron confirmed (see A3) | None | — |

### AQ1 — Partner-org users and Drive access model ✅ Resolved

**Confirmed**: Course materials live in a **CBF shared Drive folder** (AQ1-b). Partner-org users need access to that folder, not their own Drive.

**Chosen approach**: Per-user OAuth with a **folder-restricted Drive Picker** — each user authenticates with their own Google account; the Picker is initialised with `setParent(rootFolderId)` so users can only browse the configured shared folder. This:

- Prevents partner users from accidentally seeing unrelated CBF internal Drive content.
- Prevents CBF users from accidentally importing from personal Drive folders.
- Requires no shared service account.

**Operational pre-requisite (outside the plugin's control)**: A CBF administrator must grant each WP user (internal and partner-org) at minimum **Viewer** access to the shared folder in Google Drive before they can authenticate and use the picker. The plugin surfaces a clear setup notice when the folder ID is unconfigured, and a graceful error when a user's Google account does not have access to the folder.

**Design additions from this resolution**:
1. A **Plugin Settings screen** (`Settings.php`) stores the `cbf_si_drive_folder_id` option (writable only by `manage_options`).
2. `FilePicker.php` passes `rootFolderId` from the stored option to the Picker's `setParent()` and `setSelectableMimeTypes()` config.
3. If `cbf_si_drive_folder_id` is empty, the entire plugin UI shows an admin notice ("Configure the shared Drive folder ID to enable the importer") and disables the Picker.
4. If the user's Google account cannot access the folder (Google returns a `403`), the UI shows: "Your Google account does not have access to the shared course materials folder. Ask a CBF administrator to share it with [user's Google email]."

---

## Goals and Non-Goals

### Goals
- **G1** Editors can authenticate with Google, browse their Drive, and pick a Slides deck from the WP admin — no local file required.
- **G2** Editors can configure per-deck: which slides are headings (topic boundaries), which slides to exclude, and the export mode (`lesson-only` / `lesson-with-topics`).
- **G3** Editors can preview the generated Gutenberg block HTML for each lesson/topic before committing any import.
- **G4** Import creates LearnDash `sfwd-lessons` and `sfwd-topic` posts with media attached to the WordPress media library — no CSV or rsync required.
- **G5** Non-technical users can complete a full import without SSH, WP-CLI, or Python knowledge.
- **G6** The plugin is restricted to users with `manage_options` (or a new custom capability) so it is not exposed to general editors.
- **G7** The existing `import-slides.sh` / WP-CLI pipeline continues to work unchanged (no regressions).

### Non-Goals
- **NG1** Real-time Google Slides API sync (two-way or webhook-triggered); the feature is import-only on demand.
- **NG2** Processing non-Google-Slides source files (PDFs, PowerPoint from other sources) in v1 — PPTX upload from local machine is a stretch goal for v2.
- **NG3** Automated course structure management (creating new courses, reordering lessons) — only lesson/topic creation.
- **NG4** Translation or multilingual support.
- **NG5** Public-facing UI; this is admin-only.
- **NG6** Replacing or modifying the existing `learndash-bulk-lessons-or-topics` plugin.

---

## Non-Negotiable Design Rules

- **NR1** The plugin **must not** modify any existing table used by LearnDash, cbf-multisite, or learndash-bulk — it owns only its own tables.
- **NR2** Google OAuth tokens **must** be stored encrypted (WP's `wp_options` with a separate encryption key stored in an environment variable, not the DB).
- **NR3** Every import operation **must** be idempotent: re-running with the same deck and configuration must not create duplicate posts (use title-based lookup + `--overwrite` semantics from existing plugin).
- **NR4** Any failure in Google API calls, PPTX parsing, or media upload **must not** leave WordPress in a partial state visible to students — use draft status during processing; promote to publish only on full success.
- **NR5** The plugin **must** fail gracefully if the `learndash-bulk-lessons-or-topics` plugin is deactivated — show a clear dependency error, not a PHP fatal.
- **NR6** No secrets (tokens, keys) are ever logged, stored in post_meta, or emitted to the browser.
- **NR7** All user-supplied input (slide indices, layout names) is validated and sanitised before storage or use in queries.
- **NR8** The plugin is coded to CBF's existing PHP coding standards (`phpcs.xml`) and is Composer-managed.

---

## Risk and Issue Register

| ID | Risk | Likelihood | Impact | Owner | Mitigation |
|---|---|---|---|---|---|
| R1 | ~~PhpPresentation cannot faithfully reproduce the geometry-based column detection from python-pptx~~ | — | — | — | ✅ **Closed** — P0.4 probe confirmed multi-column detection fully functional on both CBF decks. Key difference from python-pptx: PhpPresentation exposes shape dims in **pixels** (not EMU); constants must be divided by 9525 (96 DPI). `Drawing\Gd::getContents()` replaces `getPath()` for image bytes. All 7 critical capabilities pass. |
| R2 | ~~Production server cannot make outbound requests to Google APIs~~ | ~~Low~~ | ~~Critical~~ | — | ✅ **Closed** — outbound HTTPS to `googleapis.com` confirmed accessible (A4 verified) |
| R3 | OAuth token refresh fails silently; imports break without clear feedback | Medium | Medium | Developer | Implement token expiry check before every Drive call; show "Re-authenticate" prompt in UI |
| R4 | Large PPTX files (many slides, high-res images) exhaust PHP memory or execution time | Medium | Medium | Developer | Chunk processing per-slide; set `ini_set('memory_limit')` within the plugin; use background job |
| R5 | ~~WP-Cron unreliability on low-traffic sites causes jobs to stall~~ | ~~Medium~~ | ~~Medium~~ | — | ✅ **Closed** — server-level cron triggers confirmed via control panel (A3 verified); WP-Cron is reliable |
| R6 | Importing to wrong subsite (wrong `blog_id`) creates content editors can't find | Medium | High | Developer | Enforce subsite context via `switch_to_blog()` / `restore_current_blog()`; log `blog_id` in every import record |
| R7 | Duplicate lesson/topic posts if import is triggered twice | Low | Medium | Developer | SHA-256 hash of (deck file ID + config) stored in import_jobs table; idempotency guard before `run_import_cli()` |
| R8 | Sensitive slide content (internal CBF docs) exposed in preview HTML stored in DB | Low | Medium | Developer | Store preview content in a transient or temp table with short TTL; never cache to persistent logs |
| R9 | Google Drive file permissions change after picker selection; download fails at import time | Low | Medium | Developer | Download PPTX immediately after picker selection; store temporarily in WP local uploads dir |
| R12 | Partner-org user's Google account does not have Viewer access to the CBF shared Drive folder; Drive Picker returns 403 | Low | Low | Ops/Admin | Plugin surfaces a clear actionable message ("Ask a CBF administrator to share the folder with [email]"); plugin cannot grant access itself — this is a Drive permissions step outside the plugin |
| R13 | CBF shared Drive folder ID changes (e.g. folder reorganisation); all users get 403 until admin updates the plugin setting | Low | Medium | Admin | Admin-only settings screen displays the current folder ID; plugin health check (`GET /cbf-si/v1/health`) verifies the folder is accessible using the first available user token |
| R10 | Existing tests (`test_columns_layout.py`) depend on `demo.pptx` which is not committed — CI gap | Medium | Low | Developer | Flag as existing risk; add `demo.pptx` to test fixtures or skip in CI with clear explanation (see Validation Plan) |
| R11 | The `cbf-multisite` build pipeline (webpack) may conflict with the new plugin's assets if co-located | Low | Low | Developer | New plugin has its own build pipeline; assets are completely independent |

---

## Branch Review and Chosen Architecture

### Option A — WordPress Plugin with PHP PPTX parsing (PhpPresentation) ✅ Chosen

A new WordPress plugin (`cbf-slides-importer`) registered via Composer in the wordpress repo. Google Drive integration via Google API PHP client library. PPTX parsing by PhpPresentation (maintained PHP port of Apache POI's presentation model). Import orchestration delegates to existing `learndash-bulk` plugin's `run_import_cli()`. Background processing via WP-Cron (server-level cron trigger confirmed reliable — A3). Full admin UI in WP admin under LearnDash menu.

**Pros**: zero external infrastructure, no Python on server, single deployment unit, reuses existing WP media/import logic, works for non-technical users.
**Cons**: PhpPresentation geometry detection less sophisticated than python-pptx (R1); requires prototyping to validate fidelity.

### Option B — WordPress Plugin + Separate Python Microservice

Plugin calls a FastAPI/Flask service running on the VPS (or a cloud function) that does PPTX processing and returns block HTML. Plugin handles Drive integration and UI.

**Pros**: retains the full sophistication of the Python extraction logic.
**Cons**: introduces a new service to deploy, monitor, and keep running; SSH and port access complexity on VPS; doubles failure modes; harder for a team of mixed skills to maintain.
**Rejected**: operational overhead outweighs the parsing fidelity benefit, especially since a PHP-side config UI for slide-type overrides compensates for parser imperfections.

### Option C — Enhanced CLI with a Simple Web Form

Add a simple HTML form in the existing `learndash-bulk` admin page that wraps `import-slides.sh` via a PHP `exec()` call.

**Pros**: minimal code change, very fast to ship.
**Cons**: requires Python on the server (A1), exposes shell execution from PHP, no Drive integration, no preview, does not meet the non-technical-user requirement, security risk.
**Rejected**: does not meet the core goals.

---

## Module Map

```
cbf-slides-importer/
│
├── cbf-slides-importer.php          # Plugin bootstrap; dependency check; init hooks
│
├── src/
│   ├── Admin/
│   │   ├── AdminPage.php            # Registers WP admin page (LearnDash submenu)
│   │   ├── SettingsPage.php         # Admin settings: Drive Shared Folder ID, client ID/secret entry
│   │   └── Assets.php               # Enqueues JS/CSS; passes nonces + config to JS
│   │
│   ├── Auth/
│   │   ├── GoogleOAuth.php          # OAuth2 flow: auth URL, token exchange, token refresh
│   │   └── TokenStore.php           # Encrypt/decrypt tokens; storage in wp_options
│   │
│   ├── Drive/
│   │   ├── DriveClient.php          # Wraps google-api-php-client; list files, export PPTX; verifies folder access
│   │   └── FilePicker.php           # Returns picker config (API key, app ID, scope, rootFolderId) for JS
│   │
│   ├── Parser/
│   │   ├── PptxParser.php           # Orchestrates PhpPresentation; returns ParsedDeck DTO
│   │   ├── SlideClassifier.php      # Applies user config overrides to classify each slide
│   │   ├── BlockRenderer.php        # Converts parsed slide content to WP block HTML
│   │   └── ParsedDeck.php           # DTO: slides[], layout metadata
│   │
│   ├── Config/
│   │   ├── DeckConfig.php           # Per-deck configuration DTO
│   │   └── ConfigRepository.php     # CRUD on cbf_slide_import_configs DB table
│   │
│   ├── Preview/
│   │   └── PreviewRenderer.php      # Assembles lesson/topic block HTML for preview panel
│   │
│   ├── Import/
│   │   ├── ImportJob.php            # Job record DTO
│   │   ├── ImportRepository.php     # CRUD on cbf_slide_import_jobs DB table
│   │   └── ImportOrchestrator.php   # Calls learndash-bulk run_import_cli(); handles media
│   │
│   ├── Queue/
│   │   └── JobDispatcher.php        # Schedules/dispatches background jobs via WP-Cron (server-level cron confirmed)
│   │
│   ├── REST/
│   │   └── ApiController.php        # WP REST API endpoints for the admin SPA
│   │
│   └── Installer.php                # register_activation_hook: create DB tables, store schema version
│
├── assets/
│   ├── src/                         # ES modules + SCSS source
│   └── dist/                        # Compiled JS/CSS (webpack or @wordpress/scripts)
│
├── templates/
│   └── admin-page.php               # Shell page; React app mounts here
│
├── composer.json                    # google/apiclient, phpoffice/phppresentation, etc.
└── package.json                     # @wordpress/scripts build toolchain
```

**Module ownership and interfaces:**

| Module | Owns | Consumes | Publishes |
|---|---|---|---|
| Auth | Google OAuth tokens (encrypted wp_options) | Google OAuth2 endpoints | Access token to Drive |
| Drive | PPTX byte stream download | Auth access token | Temp file path to Parser |
| Parser | ParsedDeck DTO | PPTX file (PhpPresentation) | ParsedDeck to Config, Preview, Import |
| Config | `cbf_slide_import_configs` | ParsedDeck metadata | DeckConfig to all consumers |
| Preview | Transient block HTML | ParsedDeck + DeckConfig | Rendered HTML to REST response |
| Import | `cbf_slide_import_jobs` | DeckConfig + ParsedDeck | WP posts via learndash-bulk |
| Queue | WP-Cron job scheduling (server cron confirmed reliable) | ImportJob record ID | Scheduled hook to ImportOrchestrator |
| REST | HTTP endpoints | All modules | JSON to admin JS SPA |
| Admin | WP admin page | REST endpoints + Assets | Google Picker config to browser |

---

## Data and Persistence Plan

### New DB tables

#### `cbf_slide_import_configs`

Stores per-deck user configuration. One row per deck per user (upsert on re-configure).

```sql
CREATE TABLE {$wpdb->prefix}cbf_slide_import_configs (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  blog_id       BIGINT UNSIGNED NOT NULL,           -- multisite context
  drive_file_id VARCHAR(200)    NOT NULL,           -- Google Drive file ID
  user_id       BIGINT UNSIGNED NOT NULL,
  mode          ENUM('lesson-only','lesson-with-topics') NOT NULL DEFAULT 'lesson-only',
  course_id     BIGINT UNSIGNED DEFAULT NULL,
  lesson_title  VARCHAR(500)    DEFAULT NULL,
  slide_overrides LONGTEXT      DEFAULT NULL,       -- JSON: [{slide_index, type}]
  config_hash   CHAR(64)        DEFAULT NULL,       -- SHA-256 of normalised config (idempotency)
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_blog_file_user (blog_id, drive_file_id, user_id),
  KEY idx_drive_file (drive_file_id),
  KEY idx_blog_id (blog_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

`slide_overrides` JSON schema (per element):
```json
{ "slide_index": 3, "type": "heading|content|hidden|cover" }
```

#### `cbf_slide_import_jobs`

Tracks each import attempt for auditability and replay.

```sql
CREATE TABLE {$wpdb->prefix}cbf_slide_import_jobs (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  blog_id         BIGINT UNSIGNED NOT NULL,
  config_id       BIGINT UNSIGNED DEFAULT NULL,     -- FK to cbf_slide_import_configs.id
  drive_file_id   VARCHAR(200)    NOT NULL,
  drive_file_name VARCHAR(500)    DEFAULT NULL,
  user_id         BIGINT UNSIGNED NOT NULL,
  status          ENUM('pending','processing','preview_ready','importing','complete','failed') NOT NULL DEFAULT 'pending',
  mode            ENUM('lesson-only','lesson-with-topics') NOT NULL DEFAULT 'lesson-only',
  config_hash     CHAR(64)        DEFAULT NULL,
  result_summary  TEXT            DEFAULT NULL,     -- JSON: {created, updated, errors[]}
  error_message   TEXT            DEFAULT NULL,
  created_post_ids TEXT           DEFAULT NULL,     -- JSON: [lesson_id, topic_id, ...]
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_blog_status (blog_id, status),
  KEY idx_config (config_id),
  KEY idx_file_hash (drive_file_id, config_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### wp_options (this plugin's keys)

| Key | Value | Scope |
|---|---|---|
| `cbf_si_google_client_id` | Plaintext OAuth client ID (not secret) | Site option |
| `cbf_si_google_client_secret_enc` | AES-256-GCM encrypted client secret | Site option |
| `cbf_si_drive_folder_id` | Google Drive shared folder ID (set by admin; restricts Picker to this folder) | Site option |
| `cbf_si_db_version` | Schema version string | Site option |

**`wp_usermeta`** (per user, per site):

| Meta key | Value | Notes |
|---|---|---|
| `cbf_si_google_token_enc` | AES-256-GCM encrypted OAuth token JSON | Per-user; stored in usermeta not wp_options to prevent cross-user access |

**Note**: Per-user OAuth tokens go in `wp_usermeta` (`meta_key = 'cbf_si_google_token_enc'`), not wp_options, to prevent cross-user token access.

### Transients

| Key | TTL | Purpose |
|---|---|---|
| `cbf_si_preview_{job_id}_{user_id}` | 1 hour | Block HTML preview cache; never persisted to logs |
| `cbf_si_parsed_{drive_file_id}_{config_hash}` | 30 min | Parsed deck cache (avoids re-parsing on config change) |

### Temporary files

Downloaded PPTX files go to `wp_upload_dir()['basedir'] . '/cbf-slides-tmp/{job_id}.pptx'` — stored on the local filesystem (S3 Uploads is disabled in all environments, confirmed A5). Deleted immediately after parsing (or on job failure). Never publicly accessible (`.htaccess` or server config denies access to `cbf-slides-tmp/`).

### Existing tables (read-only from this plugin)

- `wp_posts` — read lesson/topic titles for deduplication check
- `wp_options`, `wp_sitemeta` — multisite option reads only

### Migration order

1. `register_activation_hook` runs `Installer::activate()` which creates both tables via `dbDelta()`.
2. `cbf_si_db_version` stored in `wp_options`; on plugin update, `plugins_loaded` hook compares version and runs any new `dbDelta()` migrations.
3. No changes to existing tables; no data migration required on first install.

### Rollback / cleanup

- Deactivation hook: removes scheduled WP-Cron jobs. Does **not** drop tables (data preserved).
- Uninstall hook (`uninstall.php`): drops both tables, removes all `wp_options` keys, removes all `wp_usermeta` tokens. Guarded by `WP_UNINSTALL_PLUGIN` constant check.

---

## API, Event, and Contract Plan

### Inbound REST API (WordPress REST API, authenticated)

Base: `/wp-json/cbf-si/v1/`

All endpoints require: `is_user_logged_in() && current_user_can('cbf_slides_import')`. Nonce verification (`X-WP-Nonce` header). All responses are JSON.

#### `GET /auth/url`
Returns Google OAuth2 authorisation URL. No body params.
```json
{ "url": "https://accounts.google.com/o/oauth2/auth?..." }
```

#### `GET /auth/status`
Returns whether the current user has a valid (non-expired) Google token.
```json
{ "authenticated": true, "email": "user@codingblackfemales.com", "expires_in": 3200 }
```

#### `POST /auth/callback`
Exchanges authorisation code for tokens (called from admin redirect handler, not JS).
Body: `{ "code": "...", "state": "..." }`

#### `DELETE /auth/token`
Revokes and deletes the current user's Google token.

#### `GET /drive/picker-config`
Returns config for the Google Picker JS API (API key, app ID, OAuth token, and the shared folder ID). Token is passed as a short-lived, single-use value — never stored in browser localStorage. Returns `400` if `cbf_si_drive_folder_id` is not configured (admin must set it first).
```json
{ "api_key": "...", "app_id": "...", "oauth_token": "...", "scope": "...", "root_folder_id": "1BxiM..." }
```
The JS Picker is initialised with `setParent(root_folder_id)` so users can only browse the CBF shared folder. A Drive `403` when loading the folder is surfaced to the user as: "Your Google account does not have access to the shared course materials folder. Ask a CBF administrator to share it with [your Google email]."

#### `POST /jobs`
Creates a new import job (status: `pending`). Downloads PPTX immediately. Returns job ID.
Body: `{ "drive_file_id": "...", "drive_file_name": "..." }`
```json
{ "job_id": 42, "status": "pending" }
```

#### `GET /jobs/{id}`
Polls job status. Returns `status`, `progress_message`, `result_summary`, `error_message`.

#### `GET /jobs`
Lists jobs for the current user on the current subsite. Paginated. `?page=1&per_page=20`

#### `GET /jobs/{id}/preview`
Returns rendered block HTML for each lesson/topic (from transient cache). Returns 404 if preview not yet ready.
```json
{
  "lesson": { "title": "Intro to Java", "html": "<!-- wp:heading -->..." },
  "topics": [{ "title": "What is Java?", "html": "..." }]
}
```

#### `PUT /jobs/{id}/config`
Saves or updates the deck configuration for this job. Triggers re-parse and invalidates preview transient.
Body: `DeckConfig` object (see schema in Config module).

#### `POST /jobs/{id}/import`
Triggers the actual LearnDash import. Sets status to `importing`, dispatches background job.

#### `GET /jobs/{id}/slides`
Returns slide thumbnail metadata (index, title, layout, detected type, current override) for the configuration UI. Does not include full block HTML.
```json
{
  "slides": [
    { "index": 1, "title": "Introduction to Java", "layout": "Title Slide", "detected_type": "cover", "override": null },
    { "index": 2, "title": "Learning Objectives", "layout": "SECTION_HEADER", "detected_type": "heading", "override": null }
  ]
}
```

### Outbound API calls

#### Google OAuth2

- Auth URL: `https://accounts.google.com/o/oauth2/auth`
- Token exchange: `https://oauth2.googleapis.com/token`
- Token revoke: `https://oauth2.googleapis.com/revoke`
- Scopes: `https://www.googleapis.com/auth/drive.readonly` (read-only; least privilege)

#### Google Drive API v3

- Export PPTX: `GET https://www.googleapis.com/drive/v3/files/{fileId}/export?mimeType=application/vnd.openxmlformats-officedocument.presentationml.presentation`
- Rate limits: 1,000 requests/100 seconds per user; export is one request per import — not a concern.
- Retry: exponential backoff (2 retries) on 429 and 5xx; surface error to UI on third failure.

### Background event contract (WP-Cron — server-level cron confirmed reliable)

Hook: `cbf_si_process_job`
Payload: `[ 'job_id' => int, 'blog_id' => int ]`
Idempotency: job record checked for `status !== 'pending'` before any work starts; duplicate fires are no-ops.
Scheduling: `wp_schedule_single_event()` used for per-job dispatch; server cron hits `wp-cron.php` on a configured schedule so jobs are not dependent on visitor traffic.

### Versioning and compatibility

- REST API namespace includes version: `/cbf-si/v1/`; future breaking changes increment to `/cbf-si/v2/`.
- All REST responses include `X-CBF-SI-Version` header with plugin version.
- `DeckConfig` JSON stored in DB includes a `schema_version` field; parsed with defaults for missing fields.
- `slide_overrides` array is append-compatible: adding new `type` enum values is non-breaking for existing stored configs.

---

## UI/UX Plan

### Navigation

New submenu under **LearnDash > Slides Importer** (requires `cbf_slides_import` capability, which is granted to `manage_options` on activation).

### Screen flow

```
1. Connect Google Account (first use only)
        ↓
2. Pick Slides Deck (Google Drive Picker overlay)
        ↓
3. Configure Import
   ├── Slide map (list of slides with detected type + override dropdown)
   ├── Mode toggle: Lesson Only | Lesson + Topics
   ├── Course selector
   └── Lesson title override
        ↓
4. Preview (block HTML rendered in scrollable panel per lesson/topic)
   └── [Back to Configure] or [Start Import]
        ↓
5. Importing (progress bar, live status polling)
        ↓
6. Done (links to created lesson/topics in WP admin; link to front-end)
```

### Key UI states

| State | What the user sees |
|---|---|
| Not authenticated | "Connect Google Account" button; explains what access is requested |
| Authenticated | User's Google email displayed; "Disconnect" link |
| Picker open | Google Drive Picker overlay (Google's own UI) |
| Parsing (background) | Spinner: "Analysing slides…" with estimated time |
| Parse error | Inline error with specific message (e.g. "Could not read this presentation — try re-exporting from Google Slides") |
| Config screen (empty slide_overrides) | All slides shown with auto-detected type; yellow badge on slides with low-confidence detection |
| Config screen (overrides applied) | Blue badge on manually overridden slides |
| Preview loading | Skeleton loaders per lesson/topic panel |
| Preview ready | Scrollable block HTML panels; warning if any slides produced no content |
| Import in progress | Progress bar; current slide being processed; cannot be cancelled once started (warn before) |
| Import complete | Green banner: "Lesson created: [link]. X topics created." |
| Import failed | Red banner with specific error; "View error log" link; "Retry import" button |
| Drive folder not configured | Admin notice: "Configure the shared Drive folder ID in Settings before using the importer." Picker button disabled. |
| User's Google account lacks folder access | After auth, when Picker tries to open: "Your Google account [email] does not have access to the shared folder. Ask a CBF administrator to share it with you." Re-authenticate link shown. |
| Dependency missing (learndash-bulk inactive) | Admin notice at top: "CBF Slides Importer requires LearnDash Bulk Import to be active." |

### Validation and inline guidance

- **Slide index overrides**: validated as positive integers within deck length; duplicate assignments warned.
- **Course ID**: dropdown populated from existing LearnDash courses on the target subsite.
- **Lesson title**: max 500 chars; strips HTML.
- **Mode toggle**: switching from `lesson-only` to `lesson-with-topics` shows a helper: "Slides marked as Heading will become topic boundaries."
- **Import confirmation modal**: "This will create [N] lesson(s) and [M] topic(s) in the '[Course Name]' course. Existing content with the same title will be updated."

### Accessibility

- All form controls have associated `<label>` elements.
- Status messages use `role="status"` / `aria-live="polite"` for screen readers.
- Focus management: after picker closes, focus returns to the "Pick a deck" button.
- Progress bar uses `role="progressbar"` with `aria-valuenow`.

---

## Security and Privacy Plan

### Authentication and authorisation

- Plugin capability: `cbf_slides_import` — assigned to `administrator` role on activation via `members` plugin's role system (or `add_cap`).
- All REST endpoints verify `current_user_can('cbf_slides_import')` + nonce.
- OAuth callback URL is registered as a specific WP admin redirect target; state parameter (CSRF token stored in transient, 10-minute TTL) validated on callback.

### Token storage

- Client secret: AES-256-GCM encrypted using a key from `CBF_SI_ENCRYPTION_KEY` environment variable (required; plugin fails gracefully if absent with an admin notice).
- Per-user OAuth tokens: AES-256-GCM encrypted, stored in `wp_usermeta`. Encryption key is the same env var.
- Token refresh: performed transparently before every Drive API call; refreshed token written back encrypted.
- Revocation: `DELETE /auth/token` calls Google's revoke endpoint before deleting the local record.

### Secrets never appear in:
- WordPress debug logs (`WP_DEBUG_LOG`)
- Error messages returned to the browser (only sanitised versions)
- `result_summary` or `error_message` columns in DB
- Any transient key or value

### Least privilege

- Google OAuth scope: `drive.readonly` only — no write access to Drive.
- DB queries use `$wpdb->prepare()` exclusively.
- File paths sanitised with `sanitize_file_name()` and constrained to `wp_upload_dir()['basedir'] . '/cbf-slides-tmp/'`.

### Tenant/subsite isolation

- All DB queries filter on `blog_id = get_current_blog_id()`.
- `switch_to_blog()` / `restore_current_blog()` used consistently in background jobs (where blog context may not be set).
- Job IDs are not guessable (auto-increment + nonce check, not shared between users).

### Data retention

- Temp PPTX files: deleted immediately after parsing, and by a scheduled cleanup job hourly.
- Preview transients: 1-hour TTL.
- Import job records: retained indefinitely (audit trail); no sensitive slide content stored in job records.
- Config records: retained until user deletes them.

### Audit log

Every `POST /jobs/{id}/import` call writes a log entry: `{user_id, blog_id, drive_file_id, deck_name, timestamp, result}`. Stored in `result_summary` column. Accessible to `manage_options` users only.

---

## Failure Isolation and Recovery Plan

| Scenario | Behaviour | Recovery |
|---|---|---|
| Google OAuth token expired | Transparent refresh before API call; if refresh fails, job status → `failed` with "Re-authenticate" prompt in UI | User re-authenticates; job can be retried |
| Drive export returns non-PPTX or empty file | Job → `failed`; temp file deleted; error message: "Could not export deck — ensure you have Viewer access" | User checks permissions, retries |
| PhpPresentation parse error | Job → `failed`; specific exception message (redacted of paths) logged to error_message | Admin reviews error; user tries re-exporting deck |
| PHP memory limit exceeded mid-parse | OOM fatal caught by WP's shutdown handler; job left in `processing` state; cleanup job resets stale jobs >30 min in `processing` | Retry job (stale cleanup resets status to `pending`) |
| WP-Cron not firing (low traffic) | Job stays `pending` for more than N minutes; admin notice in plugin dashboard shows count of stalled jobs | Admin can manually trigger via `wp cron event run cbf_si_process_job` or REST endpoint |
| `learndash-bulk run_import_cli()` returns WP_Error | Job → `failed`; WP_Error message stored in `error_message`; no partial posts created (draft posts rolled back — see NR4) | Admin reviews; retries import |
| Media upload fails (local disk write error or `wp_handle_upload` failure) | Non-fatal: post created with broken image src; `result_summary.errors[]` includes list of failed media files | Admin re-runs import with `--overwrite` semantics; media retried |
| Import runs twice (duplicate dispatch) | Idempotency guard: second invocation finds status ≠ `pending`, logs and exits | No user action needed |
| Partial import (lesson created, topics not) | Both lesson and topics created inside a single transaction-like sequence; on topic failure, lesson set back to draft | Admin retries; lesson re-used via `--overwrite` |
| Plugin deactivated with stalled jobs | Scheduled cron hooks removed on deactivation; jobs remain in DB as `pending`; no dangling cron events | Reactivate plugin; jobs can be retried |

**Fails open vs closed:**
- Preview failures → fail open (show partial preview with warning banner)
- Import failures → fail closed (no posts published; draft posts deleted or remain as draft)
- Google API failures → fail closed on import; fail open on preview (allow proceeding with warning)

---

## Operations, Observability, and Support Plan

### Logging

- All errors logged via `error_log()` with prefix `[CBF-SI]`; include `job_id`, `blog_id`, `user_id` but **never** token values.
- Use `WP_DEBUG_LOG` convention; compatible with Query Monitor.
- Critical errors (parse failure, import failure) additionally stored in `cbf_slide_import_jobs.error_message` (sanitised).

### Metrics / health

- Admin dashboard widget (within plugin page): total jobs by status in last 30 days, stalled jobs count, last successful import timestamp.
- REST endpoint `GET /cbf-si/v1/health` (admin only): returns plugin version, dependency status (learndash-bulk active/inactive), token status, DB schema version, queue depth.

### Background job observability

- Each job status transition is timestamped in `updated_at`.
- Stale job detection: cleanup cron (`cbf_si_cleanup`, hourly) resets jobs stuck in `processing` for >30 minutes to `pending` with a note in `error_message`.
- Temp file cleanup: same cron deletes PPTX files in `cbf-slides-tmp/` older than 2 hours.

### Rate limits / quotas

- Google Drive API: 1,000 requests/100 seconds per user — far below import frequency; no throttling needed in v1.
- `google/apiclient` handles retry on 429 automatically with exponential backoff.

### Admin tooling

- WP-CLI command: `wp cbf-si jobs list [--status=pending] [--blog=2]` — for debugging stalled jobs.
- WP-CLI command: `wp cbf-si jobs retry <job_id>` — manually triggers a stalled job.
- WP-CLI command: `wp cbf-si cleanup` — runs temp file and stale job cleanup immediately.

### Support checklist (for non-technical users hitting errors)

Documented in plugin's admin Help tab:
1. "Re-authenticate Google" — most common fix for auth errors
2. "Re-export from Google Slides" — fixes parse errors from corrupted PPTX
3. "Contact your administrator" — for permission / server errors

---

## Rollout, Migration, and Rollback Plan

### Feature flag / gating

- Plugin is installed but **not** activated until ready. Activation is the gate.
- `CBF_SI_ENABLED` PHP constant (defined in `web/app/config/application.php`) can disable the plugin's REST API and admin page without deactivating (for staged rollout).

### Deployment order

1. Add `google/apiclient`, `phpoffice/phppresentation`, new plugin to `composer.json`; `composer install`.
2. Deploy to staging. Run `wp plugin activate cbf-slides-importer --url=academy.staging.codingblackfemales.com`.
3. Set `CBF_SI_ENCRYPTION_KEY` env var on staging server (`.env`).
4. Register Google OAuth2 credentials in GCP Console; add staging callback URL.
5. QA on staging: full end-to-end import of 2–3 real decks.
6. Deploy to production. Set env var. Add prod callback URL to GCP Console.
7. Activate on production. Announce to editors.

### Backwards compatibility

- `learndash-bulk-lessons-or-topics` is unchanged; existing WP-CLI workflow continues to work.
- `slides-to-learndash` Python package is unchanged; existing `import-slides.sh` continues to work.
- No DB schema changes to existing tables.

### Rollback path

- `wp plugin deactivate cbf-slides-importer --url=...` — instant; removes cron hooks; leaves data in DB.
- No content created by the plugin is affected (LearnDash posts remain; editor must manually review/delete if needed).
- Remove from `composer.json` and redeploy to remove code entirely.

### Staged release

1. **Phase 1 staging**: developer + 1 editor; validate parse fidelity on real CBF decks.
2. **Phase 2 staging**: 3–4 editors; validate configuration UI usability.
3. **Phase 3 production**: soft launch with `CBF_SI_ENABLED` constant; invite team leads only.
4. **Full rollout**: remove constant gate.

---

## Implementation Phases with Checklist Tasks

### Phase 0: Foundation and Dependency Validation

- [x] **P0.1** ~~Verify Python availability on production VPS~~ — **Verified**: Python is not installed (A1 confirmed). PHP-only approach locked in.
- [x] **P0.2** ~~Verify outbound HTTPS from production VPS~~ — **Verified**: `https://www.googleapis.com/drive/v3/about` accessible from prod (A4 confirmed, R2 closed).
- [x] **P0.3** ~~Check if Action Scheduler is bundled with sfwd-lms~~ — **Verified**: not needed; server-level cron triggers WP-Cron reliably (A3, A9 confirmed, R5 closed).
- [x] **P0.4** ✅ PhpPresentation probe run against both CBF decks (Introduction to Java, Object-Oriented Programming). Results: **7/8 PASS, 1 WARN, 0 FAIL** — Load, layout names, hidden slides (ZipArchive OOXML), EMU boxes, multi-column geometry, text+rich-text, title placeholders all PASS. Image extraction: WARN — shapes are `Drawing\Gd`; use `getContents()` not `getPath()`. **Implementation note:** PhpPresentation returns shape offsets/dimensions in pixels (not EMU); divide python-pptx EMU thresholds by 9525 for PHP. Resolves A6 and R1.
- [x] **P0.5** ~~Confirm `run_import_cli()` entry point~~ — **Verified**: confirmed correct entrypoint (A2).
- [ ] **P0.6** Confirm `academy` subsite `blog_id` in multisite; confirm `wp_usermeta` is per-site
- [x] **P0.7** ~~Resolve AQ1~~ — **Verified**: partner users import from a CBF shared Drive folder (AQ1-b confirmed). Folder-restricted Picker with configurable `cbf_si_drive_folder_id` setting is the chosen approach. (resolves A7, R12 → R12/R13 updated)
- [ ] **P0.8** Create `web/app/plugins/cbf-slides-importer/` directory; initialise `composer.json` and `package.json` for the new plugin (resolves A8)
- [ ] **P0.9** Add `google/apiclient` and `phpoffice/phppresentation` to plugin's `composer.json`; verify no version conflicts with root `composer.json` dependencies; resolve any conflicts

### Phase 1: Plugin Scaffold and Auth

- [ ] **P1.1** Create `cbf-slides-importer.php` bootstrap: plugin header, ABSPATH guard, dependency check for `learndash-bulk-lessons-or-topics` active (NR5), init hook (resolves NR5)
- [ ] **P1.2** Create `Installer.php`: `register_activation_hook` creates both DB tables via `dbDelta()`; stores `cbf_si_db_version`; assigns `cbf_slides_import` capability to `administrator` role
- [ ] **P1.3** Create `uninstall.php`: drops tables, removes wp_options keys, removes wp_usermeta tokens (guarded by `WP_UNINSTALL_PLUGIN`)
- [ ] **P1.4** Implement `TokenStore.php`: AES-256-GCM encrypt/decrypt using `CBF_SI_ENCRYPTION_KEY` env var; graceful error if env var absent (resolves NR2, NR6)
- [ ] **P1.5** Implement `GoogleOAuth.php`: build authorisation URL with `drive.readonly` scope and CSRF state token; token exchange; token refresh; revoke; state stored in transient with 10-min TTL (resolves R3, Security plan)
- [ ] **P1.6** Register OAuth callback as WP admin redirect target (`admin_action_cbf_si_oauth_callback`); validate state; exchange code; store encrypted token
- [ ] **P1.7** Implement REST `GET /auth/url`, `GET /auth/status`, `DELETE /auth/token` endpoints in `ApiController.php`; nonce + capability checks on all (resolves NR6)
- [ ] **P1.8** Create admin page scaffold (`AdminPage.php`, `templates/admin-page.php`): registers submenu under LearnDash; mounts React app placeholder; enqueues assets
- [ ] **P1.9** Implement `SettingsPage.php`: registers a settings screen under the plugin admin menu (accessible only to `manage_options`); fields: Google Client ID, encrypted Client Secret, **Drive Shared Folder ID** (`cbf_si_drive_folder_id`); validates that Folder ID is a non-empty string before saving; displays folder ID prominently so admins can share the right folder with partner-org users (resolves AQ1-b, R13)
- [ ] **P1.10** Add admin notice on the importer page when `cbf_si_drive_folder_id` is empty: "The shared Drive folder has not been configured. Go to [Settings] to set it up." Disables the Picker button until set (resolves AQ1-b)
- [ ] **P1.11** Build auth UI component: "Connect Google Account" button + status display; calls `/auth/url` and `/auth/status`

### Phase 2: Drive Integration and PPTX Parsing

- [ ] **P2.1** Implement `DriveClient.php`: wraps `google/apiclient`; `exportPptx(fileId)` downloads to temp path; includes retry on 429/5xx; validates mime type of response (resolves R9)
- [ ] **P2.2** Implement `FilePicker.php`: returns picker config for Google Picker JS API; OAuth token passed as short-lived value only
- [ ] **P2.3** Implement REST `GET /drive/picker-config`; pass `root_folder_id` from `cbf_si_drive_folder_id` setting; add Google Picker JS to admin assets; initialise Picker with `setParent(root_folder_id)` and `setSelectableMimeTypes(['application/vnd.google-apps.presentation'])`; wire picker close event to `POST /jobs`; handle Drive 403 on folder load with user-friendly message (resolves AQ1-b, R12)
- [ ] **P2.4** Implement `PptxParser.php` using PhpPresentation: slide iteration, title extraction, layout name, visible-slide detection via `ZipArchive` (checking `show` attr in `ppt/slides/slideN.xml`), image extraction via `Drawing\Gd::getContents()` + `getExtension()` to temp dir. **Unit note:** PhpPresentation returns shape offsets/dims in pixels; divide python-pptx EMU thresholds by 9525 (= 96 DPI). (Resolves A6, R1, R4)
- [ ] **P2.5** Implement `SlideClassifier.php`: auto-classify each slide based on layout name heuristics (SECTION_HEADER → heading, blank → hidden, etc.); apply user overrides from DeckConfig
- [ ] **P2.6** Implement `BlockRenderer.php`: convert parsed slide segments to WP Gutenberg block HTML (paragraphs, headings, lists, code, columns, images) — port `blocks.py` serialisation logic to PHP (resolves R1)
- [ ] **P2.7** Implement multi-column detection in PHP: port geometry-based overlap ratio logic from `slide_geometry.py` with pixel thresholds (ROW_OVERLAP_MIN=0.40, MIN_COL_GAP=4px, MAX_X_OVERLAP=5px). P0.4 confirmed this detects 21/33 multi-col slides correctly across both CBF decks. (Resolves R1)
- [ ] **P2.8** Implement REST `POST /jobs` endpoint: validate Drive file ID; dispatch background download + parse job; return `job_id`
- [ ] **P2.9** Implement background job handler (`cbf_si_process_job` hook): download PPTX → parse → store ParsedDeck metadata → update job status; catch all exceptions → set status `failed` (resolves R4, R5)
- [ ] **P2.10** Implement `GET /jobs/{id}` and `GET /jobs/{id}/slides` REST endpoints (resolves R6)
- [ ] **P2.11** Set up temp file cleanup cron (`cbf_si_cleanup`) and stale job reset (resolves R4, Memory OOM recovery in Failure Isolation)

### Phase 3: Configuration UI

- [ ] **P3.1** Implement `ConfigRepository.php`: upsert to `cbf_slide_import_configs`; validate `slide_overrides` JSON against schema; compute and store `config_hash` (resolves NR7, R7)
- [ ] **P3.2** Implement REST `PUT /jobs/{id}/config` endpoint; invalidate preview transient on save
- [ ] **P3.3** Build slide map UI component: list of slides with index, title, layout name, auto-detected type, override dropdown (`cover|heading|content|hidden`); low-confidence badge (resolves UX plan)
- [ ] **P3.4** Build mode toggle, course selector (populated from LearnDash API), lesson title field, slide headings toggle
- [ ] **P3.5** Validate all config inputs client-side before `PUT`; server-side validation in `ConfigRepository.php` (resolves NR7)

### Phase 4: Preview

- [ ] **P4.1** Implement `PreviewRenderer.php`: assemble block HTML for lesson and each topic using BlockRenderer + DeckConfig; store in transient `cbf_si_preview_{job_id}_{user_id}` (1h TTL) (resolves NR4, R8)
- [ ] **P4.2** Trigger preview generation on job completion (after parse) and on config update
- [ ] **P4.3** Implement REST `GET /jobs/{id}/preview` endpoint; return 202 if not yet ready (resolves G3)
- [ ] **P4.4** Build preview panel UI: scrollable block HTML panels per lesson/topic; warning if slides produced no content; "Back to Configure" and "Start Import" actions

### Phase 5: Import Orchestration

- [ ] **P5.1** Implement `ImportOrchestrator.php`: call `Extended_LearnDash_Bulk_Create::run_import_cli()` programmatically with block HTML rows; handle `WP_Error` returns; track `created_post_ids` (resolves A2, NR3, NR4)
- [ ] **P5.2** Implement idempotency: before calling `run_import_cli()`, check `cbf_slide_import_jobs` for a prior `complete` job with same `drive_file_id + config_hash`; offer update-or-skip choice in UI (resolves NR3, R7)
- [ ] **P5.3** Implement media rewrite: call `ELDBC_Media::rewrite_paths()` with temp image dir path; local file system confirmed (A5) — no S3 routing to account for
- [ ] **P5.4** Draft-first import: create posts with `post_status = 'draft'`; promote to configured status only after all posts + media in the batch are confirmed created (resolves NR4)
- [ ] **P5.5** Implement REST `POST /jobs/{id}/import` endpoint: validate status is `preview_ready`; dispatch background import job; return 202 (resolves R7)
- [ ] **P5.6** Implement background import job handler: `switch_to_blog()`; run orchestrator; `restore_current_blog()`; update job status + `created_post_ids` + `result_summary` (resolves R6)
- [ ] **P5.7** Build import confirmation modal UI: summary of content to be created, "Confirm" / "Cancel" (resolves UX plan)
- [ ] **P5.8** Build import progress UI: status polling with `GET /jobs/{id}` every 3s; progress bar; completion/failure states with links to created content

### Phase 6: Operations and Admin Tooling

- [ ] **P6.1** Implement `GET /cbf-si/v1/health` endpoint: dependency checks, schema version, queue depth, last successful import (resolves Operations plan)
- [ ] **P6.2** Implement WP-CLI commands: `wp cbf-si jobs list`, `wp cbf-si jobs retry <id>`, `wp cbf-si cleanup` (resolves Operations plan)
- [ ] **P6.3** Implement admin dashboard widget on plugin page: jobs summary by status, stalled jobs count, last success (resolves Operations plan)
- [ ] **P6.4** Document `CBF_SI_ENCRYPTION_KEY` env var requirement in README and `.env.dist`; add validation on plugin activation (resolves NR2)
- [ ] **P6.5** Write plugin README: setup instructions (GCP project, OAuth consent screen, client ID/secret, env var); user guide for each screen step (resolves G5)

### Phase 7: Quality and Hardening

- [ ] **P7.1** Write PHP unit tests for `TokenStore`, `GoogleOAuth` (mocked HTTP), `SlideClassifier`, `BlockRenderer`, `ConfigRepository` (resolves test gap for PHP)
- [ ] **P7.2** Write PHP integration test (against Lando dev environment): end-to-end import of a known PPTX fixture; assert post titles and content structure
- [ ] **P7.3** Run PHPCS against new plugin code using existing `phpcs.xml` rules; fix all violations
- [ ] **P7.4** Security review: verify no token values in logs, no IDOR on job endpoints (user can only access their own jobs), no path traversal in temp file handling
- [ ] **P7.5** Add `.htaccess` / Apache `<Directory>` block denying direct HTTP access to `cbf-slides-tmp/` uploads subdirectory
- [x] **P7.6** ~~Test S3-uploads transparency~~ — **Not needed**: S3 Uploads disabled in all environments (A5 confirmed). ELDBC_Media returns local attachment URLs.
- [ ] **P7.7** Load test: import a deck with 60 slides and 40 images; verify no memory limit error and completion <10 minutes (resolves R4)
- [ ] **P7.8** Test multisite subsite isolation: import as user on `academy` subsite; confirm content not visible on `wp` or `jobs` subsites (resolves R6, A7)

---

## Validation Plan

### Static checks
- [ ] PHPCS passes with zero errors against `phpcs.xml` rules
- [ ] PHPStan level 6 (or equivalent) passes on all new PHP files
- [ ] ESLint passes on all new JS
- [ ] No `error_log()` calls that could print tokens (grep for `cbf_si.*token` patterns in log calls)

### Unit tests (new, PHP)
- [ ] `TokenStore`: encrypt → decrypt round-trip; missing env var returns WP_Error
- [ ] `GoogleOAuth`: CSRF state validation; expired token triggers refresh; revoke calls correct endpoint
- [ ] `SlideClassifier`: SECTION_HEADER layout → heading; hidden slide → hidden; user override beats auto-detect
- [ ] `BlockRenderer`: paragraph → `wp:paragraph`; bullet list → `wp:list`; two-column → `wp:columns`; image → `wp:image`
- [ ] `ConfigRepository`: upsert; config_hash computed correctly; duplicate insert updates rather than errors
- [ ] `ImportOrchestrator`: WP_Error from learndash-bulk sets job to failed; duplicate dispatch exits early

### Existing Python tests (regression — must not break)
- [ ] `test_columns_layout.py` — skip if `demo.pptx` absent; document skip in CI config (resolves R10)
- [ ] `test_hidden_slides.py` — passes unchanged
- [ ] `test_slide_merge.py` — passes unchanged
- [ ] `test_content_postprocess.py` — passes unchanged

### Integration tests (Lando dev environment)
- [ ] Full pipeline: Google auth → pick real CBF deck → configure → preview → import; verify lesson and topics created with correct post_type, post_title, course_id
- [ ] Idempotency: run same import twice; verify no duplicate posts
- [ ] Media: confirm images appear in WP media library with correct local attachment URLs (S3 not in use — A5 confirmed)
- [ ] Subsite: confirm posts created on `academy` subsite, not `wp`

### Security tests
- [ ] IDOR: user A cannot access user B's job IDs via REST
- [ ] CSRF: REST endpoints reject requests without valid nonce
- [ ] Path traversal: job ID with `../` characters returns 400
- [ ] Token redaction: trigger a parse error; confirm exception message in DB does not contain OAuth token string

### Failure-mode tests
- [ ] Drive folder ID not set: Picker button disabled; admin notice shown; no JS errors
- [ ] User's Google account lacks folder access: clear actionable message shown; no stack trace; re-authenticate link present
- [ ] Drive folder ID changed (R13): health check endpoint reports folder inaccessible; admin settings page surfaced
- [ ] Expired/revoked Google token: UI shows "Re-authenticate" prompt; no stack trace exposed
- [ ] Malformed PPTX: job → failed with sanitised error message
- [ ] Stale job: manually set job to `processing` with `updated_at` >30 min ago; run cleanup cron; verify status reset to `pending`
- [ ] Missing `CBF_SI_ENCRYPTION_KEY`: admin notice shown; no plugin fatal

### UI checks (manual, Lando)
- [ ] Screen flow: complete happy path in < 5 minutes as a non-technical user following only on-screen instructions
- [ ] Empty state: first-time view shows "Connect Google Account" and nothing else
- [ ] Error state: Google auth fails → clear error message, no raw PHP error
- [ ] Accessibility: keyboard navigation through all screens; screen reader announces progress bar updates

### Rollback check
- [ ] Deactivate plugin; verify `import-slides.sh` WP-CLI workflow still imports successfully
- [ ] Reactivate; verify jobs table still intact; verify prior import job visible in history

### Smoke test (production deploy)
- [ ] `GET /wp-json/cbf-si/v1/health` returns 200 with all dependencies `active`
- [ ] Auth flow completes; picker opens; one-slide test deck imports successfully

---

## Done Criteria

The feature is done when **all** of the following are true:

1. A non-technical editor can, without any CLI access, pick a Google Slides deck, configure it, preview the output, and trigger an import that creates correctly structured LearnDash content in the `academy` subsite.
2. The import is idempotent: running the same deck twice with the same configuration does not create duplicate posts.
3. All created posts are initially in draft status and promoted to the target status only after the full batch is confirmed.
4. Encrypted token storage is in place; no token value appears in any log or REST response.
5. The existing `import-slides.sh` / WP-CLI pipeline produces the same output as before (regression tests pass).
6. PHPCS passes; no IDOR vulnerabilities; path traversal rejected.
7. Staging end-to-end test passes with at least two real CBF decks.
8. `GET /cbf-si/v1/health` returns healthy on production.
9. Plugin README documents setup steps; non-technical user guide is present.
10. All Phase 0 verifications (P0.1–P0.6) are documented with actual results.

---

## Final Review Note

### Evidence inspected

- `slides-to-learndash/` full source: `cli.py`, `pipeline.py`, `extract.py`, `export_csv.py`, `blocks.py`, `slide_geometry.py`, `slide_visibility.py`, `rich_text.py`, `pyproject.toml`, `README.md`, all 4 test files
- `wordpress/` repo: `.lando.yml`, `wp-cli.yml`, `composer.json`, `package.json`
- `web/app/plugins/learndash-bulk-lessons-or-topics/`: `learndash-bulk-create.php`, `includes/class-eldbc-cli.php`, `includes/class-eldbc-media.php`, `classes/Exporter.php`, `README.md`
- `web/app/plugins/cbf-multisite/`: plugin header, directory structure
- `web/app/plugins/s3-uploads/`: directory confirmed present
- `web/app/plugins/sfwd-lms/`: directory structure (vendor not inspected — A3 resolved via user confirmation of server-level cron; Action Scheduler not required)
- `scripts/import-slides.sh`, `scripts/sync-export-rsync.sh`
- Introduction to Java CSV and TXT files (seen in conversation context)

### Review checks completed

- ✅ Every module has one clear owner and one clear interface
- ✅ Optional/external dependencies (Google API, PhpPresentation) cannot break core WP behaviour (NR5)
- ✅ Every write path has idempotency story (config_hash + status guard in R7, P5.2)
- ✅ Every read path has pagination (`GET /jobs?page=&per_page=`), permission check (capability), blog_id filter
- ✅ Every secret has storage (encrypted wp_usermeta), rotation (re-auth flow), redaction (NR6) plan
- ✅ Every user workflow has error, empty, loading, and recovery states (UX plan)
- ✅ Every migration step has rollback (deactivate plugin; no destructive DB change)
- ✅ Every contract has versioning (`/cbf-si/v1/`, `schema_version` in JSON)
- ✅ Every risky assumption has a verification task (P0.1–P0.8)
- ✅ All assumption and risk IDs referenced by at least one task or explicit non-goal
- ✅ Existing tests named; untested invariants flagged (R10, no PHP tests)

### Validation still required before implementation starts

The following Phase 0 verifications are complete and confirmed:
- ✅ **A1** Python not on server — PHP-only approach confirmed
- ✅ **A2** `run_import_cli()` correct entrypoint
- ✅ **A3 / A9** WP-Cron reliable (server-level cron configured) — no Action Scheduler needed
- ✅ **A4** Outbound HTTPS to googleapis.com confirmed — R2 closed
- ✅ **A5** S3 Uploads disabled — local filesystem confirmed, R9 and P7.6 simplified
- ✅ **A8** Plugin location confirmed

**Still required before Phase 1 code is written:**

1. ~~**P0.4** — see above~~ ✅ Complete.
2. **P0.6** — confirm `academy` subsite `blog_id` and `wp_usermeta` per-site behaviour before Phase 1 token storage implementation.

All assumption open questions (A1–A9, AQ1) are now resolved. Phase 0 verifications P0.1–P0.5, P0.7 are complete and P0.4 is now complete. **P0.6** is the last gate before Phase 1 begins.

> **P0.4 results (2026-08-24):** PhpPresentation probe ran clean (exit 0) against both CBF decks. Critical implementation notes for `PptxParser.php`:
> - Shape offsets/dimensions are in **pixels** (96 DPI), not EMU. Divide all python-pptx EMU thresholds by 9525. Use `$prs->getLayout()->getCX('px')` for slide width.
> - Hidden slides: use `ZipArchive` to read `ppt/slides/slide{N}.xml` and check `show` attribute — no public PhpPresentation API for this.
> - Image shapes load as `Drawing\Gd`; extract bytes with `$shape->getContents()`, extension with `$shape->getExtension()`.
> - Title placeholders: `$shape->getPlaceholder()->getType()` returns `'title'` or `'ctrTitle'` — reliable on both decks.
> - Layout names, multi-column detection, and rich-text classification all function with high fidelity.
