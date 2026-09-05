# CBF Slides-to-LearnDash Importer Plugin

---

## Document Control

| Field | Value |
|---|---|
| **Plan version** | 2.8.0 |
| **Status** | Phase 5 complete · Phase 6 partial (P6.5) · Phase 7 partial (P7.0, P7.1, P7.3) · **Phase 8 complete (P8.0–P8.17) except integration tests (P8.15), which wait on the P7.2 suite**; all Phase 8 assumptions verified |
| **Depth tier** | **Standard** — content migration tool; elevated security treatment for OAuth token storage; no money flows, no shared counters, no irreversible structural DB changes |
| **Evidence baseline** | Local inspection · branch `claude/wizardly-yonath-c64ab1` (slides-to-learndash) · branch `main` (wordpress) · inspection date 2026-08-24 |
| **Changelog** | 2.8.0 — **A12 closed** (P8.17): pre-flight over the real 132-row curriculum sheet resolved 109 of 109 importable files with no permission failures; the 23 rejections are all source-type, as predicted. The run exposed **R25**, a live defect — google/apiclient 2.19 accepts only Guzzle 6–7, Bedrock's root vendor supplies Guzzle 8, so every Drive API-client call threw before reaching the network; metadata now goes over `wp_remote_get`, matching the fallback the export path already had. Also disproved **A15**: 42 of 74 `/presentation/` URLs are uploaded `.pptx` binaries, not native Slides, so the URL never implies the format · 2.7.0 — Phase 8 implemented and verified end to end on Lando (a three-row CSV built the intended course structure). P8.6a settled: a section heading's `ID` is a millisecond Unix timestamp. The probe also found that lesson order is read from the `ld_course_steps` step tree rather than `menu_order` (A14) — the reorder pass now writes both, reloads the cached steps model, and refuses to write a tree missing lessons the batch created (R22). Two further implementation defects recorded as R23 (batch jobs losing their course config) and R24 (a requeued row never rescheduled). Unit suite at 226 tests / 644 assertions; README documents the CSV contract and the two-pass workflow. **A12 stays open** — confirming Drive read access across workspaces needs a live OAuth token · 2.6.0 — AQ2–AQ6 resolved (AQ2-a, AQ3-c, AQ4-a, AQ5 synonyms, AQ6 two-stage), removing the update-by-ID scope risk; P8.1 completed by measuring the draft curriculum spreadsheet — ~17% of rows point at sources that cannot become lesson content (12 Google Forms, GitHub repos, external courses, empty cells), driving R20, R21 and a path-segment-based URL parser; two-pass session-then-topic workflow documented as a consequence of AQ3-c · 2.5.0 — Phase 8 (bulk CSV migration) designed: batch data model, CSV contract, endpoints, section-heading resolution, concurrency strategy, reporting · 2.4.0 — Codeception adopted as the PHP test framework; unit suite implemented (P7.1 complete, 158 tests / 401 assertions, no WordPress required); committed synthetic fixtures for all three formats plus an env-gated corpus test over real decks; plugin PHP floor raised to 8.5 to match the root project · 2.3.0 — PDF and DOCX import added (see Multi-format support); parsing refactored onto a format-neutral IR; plugin README written (P6.5) · 2.2.0 — P5.8 closed: animated progress bar removed (no reliable sub-phase metric); status badges in job list provide sufficient feedback; _pollJob keeps badge current after import trigger · 2.1.0 — P5.7 complete (import confirmation modal: lesson/topic count + course name summary, amber warnings for no-course/overwrite, conflict 409 handled inline — warning injected, button swapped to "Re-Import anyway" — no window.confirm() anywhere in the import flow) · 2.0.0 — P5.4 complete (revert-on-error: posts created published, reverted to draft if batch has errors; replaces draft-filter approach to avoid concurrent-process interference) · 1.9.0 — P5.2 complete (config_hash idempotency check, 409 conflict response, inline warning + confirm-dialog fallback, force re-import); import-from-configure-view fix (cached preview config used when form elements gone); PHPCS pre-existing complexity fixes in JobController (extract_config, decode_summary, apply_request_config, apply_slide_overrides helpers) · 1.8.0 — P5.1/P5.3/P5.5/P5.6 marked complete (already implemented) · 1.7.0 — Phase 3 complete (P3.1–P3.5, slide map UI, overwrite flag, config validation); Phase 4 complete (P4.1–P4.4, PreviewRenderer.php, on-demand preview with transient cache + bust, preview panel UI with topic accordion, back-to-configure, warning when no content); skipped_post_ids fix (false-outcome bug on title-match dedup) · 1.6.0 — Phase 1 and Phase 2 (P2.1–P2.10) complete; all bugs fixed (Font::getUnderline, parsed ENUM, DOMContentLoaded timing, ESLint); ConfigController and uninstall.php confirmed present · 1.5.0 — P0.8/P0.9 complete: plugin scaffold committed (31 files, 6 541 insertions); namespace CodingBlackFemales\SlidesImporter; composer deps installed; all PHP files parse clean · 1.4.0 — P0.6 complete: academy blog_id=2 confirmed; wp_usermeta per-site scoped confirmed; all Phase 0 gates cleared; Phase 1 unblocked · 1.3.0 — P0.4 complete: PhpPresentation probe passed 7/8 capabilities on 2 real CBF decks; A6/R1 resolved; image extraction method documented (Drawing\Gd::getContents()); pixel unit difference from python-pptx EMU noted; P2.4 implementation notes updated · 1.2.0 — AQ1 resolved; folder-restricted Picker added; SettingsPage, R12/R13, P1.9/P1.10, P2.3 updated · 1.1.0 — A1–A5/A7–A9 verified; R2/R5 closed; S3 removed; WP-Cron confirmed · 1.0.0 — initial plan |
| **Attribution** | Robust Feature Planner by Simeon Williams — Veedence.co.uk |
| **Planner** | Robust Feature Planner v3.0.0 (raw prompt) — plannerskill.veedence.com |

---

## Feature Summary

Replace a multi-step, CLI-dependent migration pipeline (Google Slides → PPTX export → local Python conversion → rsync → WP-CLI import) with a self-contained WordPress admin plugin that allows editors—including non-technical colleagues—to:

1. Pick a source document directly from Drive via a browser-based file picker — Google Slides, Google Docs, or a PPTX, DOCX or PDF already stored there — or upload one from their machine.
2. Configure how each unit of content — a slide, a PDF page or a Word section — maps to LearnDash structures (lesson, topics, headings, hidden units) without touching a command line.
3. Preview the generated Gutenberg block HTML before any WordPress content is created.
4. Trigger the import with one click and track progress in the browser.
5. Migrate a whole course in one pass by uploading a CSV that lists many source files, their target titles and where each belongs in the course structure (Phase 8).

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

**No PHP tests existed** for the bulk import plugin or cbf-multisite when this plan was written, and there was no PHP test framework anywhere in the repository. Codeception has since been adopted for this plugin — see [Test Framework](#test-framework). No integration tests for the end-to-end pipeline exist yet (P7.2).

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
| A10 | LearnDash section headings are **virtual markers**, not posts: a JSON array in the course's `course_sections` post meta, each entry `{ID, order, post_title, type: 'section-heading'}`, where `order` is an index into the course's ordered lesson list | ✅ **Verified** — read from `LDLMS_Course_Steps::steps_grouped_sections()` and `set_section_headings()`; corroborated by the [LearnDash sections documentation](https://docs.nexcess.com/software/learndash/course-sections/). **P8.6a: `ID` is a millisecond Unix timestamp** assigned client-side at creation, confirmed against live data (`{"order":0,"ID":1631808942506,"post_title":"Test Driven Development",...}` = 16 Sep 2021) | Section creation would need a different mechanism | — |
| A11 | Section headings group **lessons only** and exist **only at course top level** — a topic cannot sit directly under a heading | ✅ **Verified** — vendor docs: sections "cannot be placed inside lessons or topics" | The CSV's `heading` column would mean something different for `type=topic` rows — see **AQ2** | — |
| A12 | A CSV row's Drive file is readable by the importing user's own Google account, whatever folder or workspace it lives in | ✅ **Verified** — pre-flight run over all 132 rows of the draft curriculum sheet with the migrating account's live token: **109 of 109 importable files resolved, zero permission or not-found errors**. The 23 remaining rows fail on source type, not access (12 Forms, 6 blank, 5 non-Drive) | Rows fail individually with a permissions error; batch completes partially | — |
| A13 | Bulk rows may be imported without a per-row human preview, because the CSV itself is the reviewed artefact and a pre-flight validation report is shown before anything is written | ⚠️ **Assumed** — differs from the single-file flow, where NR4 requires preview-before-import | Editors get no chance to catch a bad parse before content is created | Confirm with CBF during review — see **AQ6** |
| A14 | Lesson order is read from the course's step tree (`ld_course_steps`), not from `menu_order` alone | ✅ **Verified** during P8.6 — shared course steps are enabled on this site, so `ld_lesson_list()` queries `orderby: post__in` against the tree. Writing `menu_order` alone leaves the builder order unchanged | A reorder pass appears to succeed while the course builder shows the old order | — |
| A15 | A `docs.google.com/presentation/` URL identifies a native Google Slides file | ❌ **Disproved** — of 74 such URLs, only **32 are native Slides**; the other **42 are uploaded `.pptx` binaries** that Drive also serves under a `/presentation/` URL. Same pattern for Docs: 16 URLs, 2 native, 14 `.docx`. The design already reads the MIME type from Drive rather than the URL, so nothing changed — but trusting the URL would have sent 56 rows down the wrong export path | Files would be exported when they should be downloaded, and fail | — |

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

### AQ2 — What does `heading` mean on a `type=topic` row? ✅ Resolved — AQ2-a

LearnDash section headings group **lessons at course top level** (A11). A topic sits under a lesson, so a heading cannot apply to it directly.

| Option | Behaviour | Consequence |
|---|---|---|
| **AQ2-a** | Ignore `heading` on topic rows | Simplest. The column is only read for `type=session` rows |
| **AQ2-b** | Treat it as the heading of the topic's *parent session*, and use it to place that session if the parent is also being created in the same CSV | Lets one CSV build a whole course; ordering becomes dependent on row order |
| **AQ2-c** | Reject topic rows that carry a heading as invalid | Strictest; surfaces authoring mistakes but rejects harmless data |

**Resolved: AQ2-a.** `heading` is read only on `type=session` rows. A populated `heading` on a topic row is ignored, and the pre-flight report shows it as an ignored-value notice rather than an error.

### AQ3 — What is `session_id` for on a `type=session` row? ✅ Resolved — AQ3-c

On a `type=topic` row its meaning is clear: the existing session the topic is nested under. On a `type=session` row it is ambiguous.

| Option | Behaviour |
|---|---|
| **AQ3-a** | Expected to be empty; a value is a validation error |
| **AQ3-b** | Identifies an **existing** session to update in place — effectively a per-row overwrite target, independent of the batch-level Overwrite toggle |
| **AQ3-c** | Ignored entirely |

**Resolved: AQ3-c.** `session_id` is ignored entirely on `type=session` rows — not an error, simply unread. `LearnDashImporter` needs no update-by-ID path, and session overwrite continues to work by title match under the batch-level Overwrite toggle.

**Consequence — topics can only reference sessions that already exist.** Because a session row never reports an ID back into the CSV, a topic cannot reference a session created by the same batch. Migration therefore runs in two passes:

1. Upload a CSV of `type=session` rows. The report gives each created session's post ID.
2. Fill those IDs into the `session_id` column of a second CSV of `type=topic` rows, and upload that.

Sessions already live in LearnDash need only the second pass. This is a workflow constraint of AQ3-c rather than a defect, but it must be documented in the README (P8.16) or editors will hit it on their first attempt.

### AQ4 — Where in a section should a newly created session be placed? ✅ Resolved — AQ4-a

Section membership is positional (A10): a lesson belongs to the nearest preceding heading in the course's lesson order. So creating a session "under" a heading means placing it at a specific index.

| Option | Behaviour |
|---|---|
| **AQ4-a** | Append to the end of the named section, preserving CSV row order within it |
| **AQ4-b** | Append to the end of the course, then move the heading — simplest but reorders existing content |
| **AQ4-c** | Honour an explicit `order` column added to the CSV |

**Resolved: AQ4-a.** New sessions are appended to the end of the named section in CSV row order. No CSV change needed.

### AQ5 — Should `type` accept LearnDash's custom labels? ✅ Resolved — accept synonyms

CBF renames LearnDash's "Lesson" to "Session" via custom labels, which the admin UI already honours. The CSV uses `session`/`topic`.

**Resolved: accept synonyms.** `session` and `lesson` both mean the lesson post type; `topic` means topics. Matching is case-insensitive and whitespace-trimmed. Anything else is a row-level validation error naming the accepted values.

### AQ6 — Is a per-row preview required before content is created? ✅ Resolved — two-stage batch flow

The single-file flow requires the editor to review rendered output before anything is written (NR4). A CSV of 40 rows makes that impractical.

**Resolved: two-stage batch flow.** The production CSV is expected to be roughly 120 rows, which settles it — per-row preview is not workable at that size. A pre-flight validation report (rows parsed, URLs resolved, sessions and headings located, unsupported sources and duplicates flagged) must be confirmed before any job runs. Preview remains available on any individual job afterwards, since each row is still an ordinary job.

---

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
- **NG2** ~~Processing non-Google-Slides source files~~ — **superseded**: PPTX, PDF and DOCX are supported, from Drive or local upload.
- **NG3** Creating courses. Course structure is otherwise **partly in scope as of Phase 8**: creating section headings and ordering created lessons beneath them is required by bulk migration. Reordering content the importer did not create remains out of scope.
- **NG4** Translation or multilingual support.
- **NG5** Public-facing UI; this is admin-only.
- **NG6** Replacing or modifying the existing `learndash-bulk-lessons-or-topics` plugin.
- **NG7** Editing or re-syncing content after import. A corrected CSV can be re-run — already-imported rows are skipped — but the plugin never reconciles changes made in Drive against posts it created earlier.

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
| R14 | **Concurrent writes to `course_sections` lose headings.** Section headings live in one JSON post-meta value on the course. If several bulk jobs each read-modify-write it, later writes silently discard earlier ones | High (if headings are created per-job) | High | Developer | Resolve and create **all** headings once, synchronously, during batch creation — before any job is scheduled. Jobs then only read heading positions, never write them. This is the single most important design constraint in Phase 8 |
| R15 | **Concurrent lesson ordering corrupts section membership.** A lesson's section is decided by its index in the course's lesson order; parallel job completion interleaves `menu_order` unpredictably | High | High | Developer | Run batch jobs **strictly sequentially** (one in flight per batch), and apply a single reorder pass at batch end that places every created lesson under its intended heading |
| R16 | Drive files referenced by CSV live in other folders or workspaces and are not readable by the importing user | Medium | Medium | Developer/Ops | Validate every URL during pre-flight with a metadata call and report unreachable rows before any import runs; per-row failure never aborts the batch. Add `supportsAllDrives=true` to the API export and metadata calls, which currently only the media-download path sets |
| R17 | A large batch exhausts Drive API quota (429) or the PHP time limit | Medium | Medium | Developer | Sequential execution with a short delay between jobs; existing 429 retry with backoff; batch resumes from the first unfinished row after a stale-job reset |
| R18 | A malformed or hostile CSV (huge row count, injected formulae, path-like titles) is uploaded | Low | Medium | Developer | Cap row count; validate every column against an allow-list; `sanitize_text_field` all values; never interpolate CSV values into SQL or shell; treat the file as untrusted input exactly as document content is |
| R20 | A large minority of CSV rows point at sources that cannot become lesson content — Google Forms, GitHub repositories, external courses, empty cells. Measured at ~17% of the draft curriculum sheet | High (confirmed present) | Medium | Developer | Detect at pre-flight from the URL's path segment and the Drive MIME type; report each with a specific reason ("Google Forms cannot be imported — this row is a quiz") rather than a generic failure. Never queue a job for one |
| R21 | Editors expect one CSV to build sessions and their topics in a single pass, but AQ3-c means a topic can only reference a session that already exists | Medium | Medium | Developer | Document the two-pass workflow prominently; have the pre-flight report name the offending rows when a `session_id` cannot be resolved, rather than failing them at import time |
| R19 | Partial batch outcome is misread as total success or total failure | Medium | Medium | Developer | Report presents per-row outcomes with explicit counts (created / updated / skipped / failed) and remains available after completion; batch status is `completed_with_errors` rather than `done` when any row failed |
| R22 | **A stale course step tree written back detaches lessons the batch just created.** Found during P8.6: the steps model is cached per request, so a tree read before the batch ran silently drops everything created since | High (hit in testing) | High — silent content loss | Developer | Reload the model with `course_steps( $id, true )` before writing; `tree_is_complete()` refuses any write whose tree is missing lessons the batch created, logging the skip instead. Never delete `ld_course_steps` to force a rebuild — it drops headings and topic nesting |
| R23 | **A batch job loses its course association.** Found during P8.9: `JobRunner` reloaded config from the configs table, which is empty for batch jobs, overwriting the config carried on the job summary | High (hit in testing) | High — lessons created outside any course | Developer | `JobRunner::resolve_config()` falls back to the summary config when `config_id` is `NULL`. Covered by the end-to-end batch check |
| R25 | **The Drive API client cannot build a transport in this environment.** google/apiclient 2.19 accepts Guzzle 6 or 7 only; Bedrock's root vendor ships **Guzzle 8.0.2** (since 2026-07-30) and wins the autoloader race against the copy bundled with the plugin, so every call through `DriveService` throws `LogicException: Could not find supported version of Guzzle` before reaching the network | High — was live, undetected | High — bulk pre-flight could resolve no file at all | Developer | Read metadata over `wp_remote_get` with the Bearer token instead of the API client, mirroring the fallback the export path already had. This was invisible until bulk because the single-file flow gets its MIME type from the Drive Picker and the export path silently falls back — so only the metadata call, which bulk depends on entirely, had no escape route |
| R24 | **A row reset to `pending` never runs again.** Found during P8.8: the Janitor cleared the in-flight state without scheduling a cron event, so a stalled batch stayed stalled | Medium | Medium | Developer | `Janitor::requeue()` reschedules the cron event alongside the status reset |

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

## Bulk Migration (CSV) — Phase 8 Design

<a name="bulk-migration"></a>

**Status: implemented** (P8.2–P8.16). AQ2–AQ6 resolved; the design below is as built, with the P8.6 findings folded in. Integration coverage of the LearnDash-dependent modules still waits on the P7.2 suite.

Migrating an existing course one file at a time does not scale: each deck needs a picker selection, a configuration pass and a preview confirmation. Bulk migration replaces that with a single CSV describing every piece of content and where it belongs.

### CSV contract

| Column | Required | Meaning | Validation |
|---|---|---|---|
| `heading` | Session rows only | LearnDash section heading the created session belongs under; created if absent from the course | Trimmed; matched case-insensitively against existing headings. **Ignored on topic rows** (AQ2-a) — reported as a notice, not an error |
| `session_id` | Topic rows only | The existing session the topic nests under | Must be a lesson-type post belonging to the selected course. **Ignored on session rows** (AQ3-c) |
| `type` | Required | `session` or `topic` | Case-insensitive, trimmed; `lesson` accepted as a synonym for `session` (AQ5) |
| `title` | Required | Title of the created session or topic | Non-empty after sanitisation; length capped to the `post_title` column |
| `url` | Required | Google Drive URL of the source document | Must yield a file ID and resolve to a supported MIME type |

Course and Overwrite are **not** CSV columns — they are chosen once in the configuration panel and apply to the whole batch, exactly as the brief specifies.

Header row is required. Column order is not significant; unknown columns are ignored with a notice. Values are treated as untrusted input (R18).

**Drive URL forms to accept:**

```text
Accepted — all observed in the real material:
  https://docs.google.com/presentation/d/FILE_ID/edit#slide=id.p     -> export to PPTX
  https://docs.google.com/document/d/FILE_ID/edit?usp=sharing        -> export to DOCX
  https://drive.google.com/file/d/FILE_ID/view?usp=sharing           -> download as-is
  https://drive.google.com/open?id=FILE_ID                           -> download as-is
  http://docs.google.com/...                                         -> scheme tolerated
  FILE_ID                                                            -> bare ID, for hand-written rows

Rejected at pre-flight, with a reason naming the type:
  https://docs.google.com/forms/d/FILE_ID/edit         -> Google Form (12 rows; quizzes, out of scope)
  https://docs.google.com/spreadsheets/d/FILE_ID/edit  -> Google Sheet
  https://github.com/org/repo                          -> not a Drive URL
  (empty)                                              -> no source given
```

Unsupported Drive types share the same `/d/ID` URL shape as supported ones — Google Forms are the largest group in the real material (R20) — so the parser keys on the path segment and rejects `forms` and `spreadsheets` explicitly rather than extracting an ID and failing later.

The file's MIME type is then read from Drive metadata, not guessed from the URL — the existing `ParserFactory::format_for_mime()` then decides whether it is exported or downloaded as-is, so bulk inherits PPTX, PDF and DOCX support unchanged.

### Source material — measured, not assumed (P8.1)

The curriculum spreadsheet that the CSV will be derived from was inspected directly: `Data sponsorship 2026 | Proposed curriculum.xlsx`, 132 content rows across 9 Learning Journeys, with 126 hyperlinks in the "Link to Session Slides" column. Every figure below comes from that file.

**What the sources actually are:**

| Source | Rows | Importable |
|---|---|---|
| Google Slides (`docs.google.com/presentation/d/…`) | 74 | Yes — exported to PPTX |
| Drive binary (`drive.google.com/file/d/…`) | 19 | Only if the MIME type is PPTX, PDF or DOCX — unknown until the metadata call |
| Google Docs (`docs.google.com/document/d/…`) | 16 | Yes — exported to DOCX |
| **Google Forms** (`docs.google.com/forms/d/…`) | **12** | **No** — all "Quiz / Skills Check" rows |
| No link at all | 6 | No |
| GitHub repositories | 3 | No |
| Already live on the LMS | 1 | No |
| External course (netacad.com) | 1 | No |

**So roughly 109 of 132 rows are importable and 23 — about 17% — are not.** The exact figures will shift as the sheet is edited, but the proportion is unlikely to: a meaningful minority of rows point at things that are not documents. That is not an error condition to be fixed; it is the normal shape of the input. The pre-flight report is therefore the feature's main surface, not a formality: its job is to tell an editor which 17% need handling another way, before anything runs.

The 12 Google Forms are the largest single group and are all quizzes. LearnDash quizzes are a different post type with their own question model, and importing them is **out of scope** (NG3) — they must be reported as an unsupported source with a clear reason, never silently skipped.

**What this adds to URL parsing (P8.2):**

- Query strings are present on most links and must be discarded when extracting the ID: `?usp=sharing` (22), `?slide=…` deep links (72), plus `tab`, `ouid`, `rtpof`, `sd`.
- At least one link uses `http://`, not `https://`, so the scheme must be tolerated.
- `docs.google.com/forms/d/ID` matches the same `/d/ID` shape as Slides and Docs, so the parser must key on the **path segment** (`presentation`, `document`, `forms`, `spreadsheets`) and not merely on finding an ID.
- All 121 file IDs are distinct — no row reuses another's file, so nothing depends on de-duplicating identical sources.

**Treat the row counts as indicative, not final.** The spreadsheet is still being edited and will be revised before the CSV is produced; the session and topic columns in particular are incomplete at the time of writing. What is stable enough to design against is the *distribution of source types* above — that reflects the material itself rather than the state of the sheet.

**Still unverified:** whether the importing user's Google account can actually read all 121 files (A12, R16). URL shape says nothing about permissions, and testing it needs a real OAuth token against the live account. The pre-flight metadata call is what will answer it, per row, before any content is created — which is precisely why validation resolves every URL rather than trusting the CSV.

### Data model

A new table, plus two columns on the existing jobs table so every row is a first-class job with the status tracking, retry and cleanup behaviour already built:

```sql
CREATE TABLE {prefix}cbf_slide_import_batches (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  blog_id       BIGINT UNSIGNED NOT NULL,
  user_id       BIGINT UNSIGNED NOT NULL,
  course_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
  overwrite     TINYINT(1)      NOT NULL DEFAULT 0,
  csv_name      VARCHAR(500)    NOT NULL DEFAULT '',
  row_count     INT UNSIGNED    NOT NULL DEFAULT 0,
  status        ENUM('validating','awaiting_confirmation','running','done','completed_with_errors','failed','cancelled')
                                NOT NULL DEFAULT 'validating',
  plan          LONGTEXT            NULL DEFAULT NULL,  -- validated rows + resolved targets
  report        LONGTEXT            NULL DEFAULT NULL,  -- per-row outcomes, written as jobs finish
  error_message TEXT                NULL DEFAULT NULL,
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_blog_user (blog_id, user_id),
  KEY idx_status (status)
);

ALTER TABLE {prefix}cbf_slide_import_jobs
  ADD COLUMN batch_id  BIGINT UNSIGNED NULL DEFAULT NULL,
  ADD COLUMN batch_row INT UNSIGNED    NULL DEFAULT NULL,
  ADD KEY idx_batch (batch_id, batch_row);
```

`batch_id` being NULL is what distinguishes a single-file job from a bulk row, so every existing query keeps working untouched. `Install::DB_VERSION` moves to `1.1.0` and `create_tables()` gains the new table; `dbDelta()` adds the columns in place. Uninstall drops the batch table alongside the others.

### Flow

```text
1. Upload      POST /batches            CSV + course_id + overwrite
2. Validate    (synchronous)            parse CSV, resolve every URL, session and heading
3. Report      GET  /batches/{id}       pre-flight table: row -> action, with errors and warnings
4. Confirm     POST /batches/{id}/run   creates headings, then queues one job per valid row
5. Execute     WP-Cron, sequential      each job: download -> parse -> import, no preview gate
6. Report      GET  /batches/{id}/report  per-row outcomes; CSV download
```

Steps 1–3 write nothing to LearnDash. That pre-flight gate is what replaces the per-row preview (AQ6): the editor sees exactly what will be created, and which rows cannot be, before committing.

### Section heading resolution

Headings are created **once, synchronously, at step 4** — never inside a job (R14). For each distinct `heading` value in the batch:

1. Read `course_sections` post meta for the selected course.
2. Match case-insensitively on `post_title`; reuse the existing heading if found.
3. Otherwise append a new entry `{ID, order, post_title, type: 'section-heading'}` and write the meta back once, for all new headings together.

The write goes through `LDLMS_Course_Steps` where possible rather than touching post meta directly, so LearnDash's own caches are invalidated.

✅ **Resolved (P8.6a): a section's `ID` is a millisecond Unix timestamp**, assigned client-side when the heading is created in the builder. Confirmed against a live course on Lando, whose oldest heading carries `"ID":1631808942506` — 16 September 2021, matching when that course was built. `SectionHeadings::next_id()` therefore uses `max(round(microtime(true) * 1000), highest_existing_id + 1)`, so several headings created inside the same millisecond still get distinct IDs and can never collide with an existing one.

Because `order` is an index into the lesson list (A10), the final placement of created lessons is applied as **one reorder pass at the end of the batch** (R15), not per job: collect the created lesson IDs per heading, then rewrite the whole course order in a single sweep.

**What that sweep has to write turned out to be the hardest part of Phase 8 (A14).** Shared course steps are enabled on this site, so LearnDash builds a course's lesson list from the step tree stored in `ld_course_steps` post meta, queried with `orderby: post__in`. Writing `menu_order` alone changes nothing an editor can see. `SectionHeadings::reorder_steps()` therefore writes both: `menu_order` on each lesson, and the tree itself via `LDLMS_Factory_Post::course_steps( $course_id )->set_steps_keeping_sections()`, which preserves the heading markers the same pass depends on.

Two hazards found while building it, both now guarded:

- **The steps model is cached per request.** A tree read before the batch created its lessons is stale, and writing it back silently detaches everything created since. The reorder pass reloads with `LDLMS_Factory_Post::course_steps( $course_id, true )`, and `tree_is_complete()` refuses to write a tree that is missing lessons this batch created — logging `Skipped reordering: the course step tree is missing lessons this batch created` rather than destroying them. A wrong order is recoverable by hand; a detached lesson is not.
- **Deleting `ld_course_steps` to force a rebuild is destructive**, not a refresh: it drops section headings and any topic nesting LearnDash has not re-derived. All writes go through the `set_steps` API. Note that `set_steps()` takes the type map directly — wrapping it in another `'h'` key empties the course.

`place_lessons()` detaches each lesson from its current position before reattaching it, because appending without detaching duplicates the entry in the tree.

### Concurrency

Jobs in a batch run **strictly sequentially** — one in flight at a time. Each job's completion schedules the next. This is slower than parallel execution and deliberately so: it is what makes lesson ordering deterministic (R15), keeps Drive well inside rate limits (R17), and bounds peak memory to a single parse. The production CSV is expected to be roughly 120 rows, of which ~109 are importable; at roughly 10–30 s per row that is a **20–55 minute run**. This is background work either way, but the duration has consequences worth designing for: the batch must survive WP-Cron ticks across that window, resume from the first unfinished row after a stale-job reset, and show live progress so an editor can tell a slow batch from a stalled one.

The existing Janitor already resets jobs stuck in flight for 30 minutes, so a wedged batch resumes on the next tick rather than stalling permanently.

### Failure handling

Per-row failure never aborts the batch. NR4's revert-on-error is per job, so a failed row leaves the rows before it intact and published — an important difference from the single-file flow, and the reason the report matters. A row can fail at three points, each recorded distinctly:

| Stage | Example | Outcome |
|---|---|---|
| Validation | Unparseable URL, unknown `type`, `session_id` not in this course | Row never queued; reported before confirmation |
| Fetch | Drive 403/404 — file in another workspace (R16) | Job `failed`, row reported with the Drive error |
| Import | Malformed document, LearnDash rejection | Job `failed`, any post it created reverted to draft |

Rows already imported with the same file and configuration are reported as **skipped**, reusing the existing `config_hash` idempotency check — which currently surfaces as a 409 to be confirmed by hand and must become a non-blocking outcome in batch context.

### Reporting

The report is built incrementally as jobs finish, so it is useful while the batch is still running:

| Column | Content |
|---|---|
| Row | CSV line number, for cross-reference against the source file |
| Title | From the CSV |
| Type | Session or topic, using LearnDash's custom labels |
| Target | Heading and parent session the row resolved to |
| Outcome | Created / Updated / Skipped / Failed |
| Post | Link to the created post, where there is one |
| Detail | Error message for failures, reason for skips |

Downloadable as CSV so a partially failed batch can be corrected and re-uploaded — the skipped-duplicate check makes re-running a corrected file safe.

### New modules

```text
includes/
  Bulk/
    CsvParser.php        # header mapping, row validation, untrusted-input handling
    DriveUrl.php         # URL -> file ID for every accepted Drive URL form
    BatchPlanner.php     # resolve rows against the course: headings, sessions, duplicates
    BatchRunner.php      # sequential job scheduling; batch status transitions
    SectionHeadings.php  # read/create course_sections; end-of-batch reorder pass
    BatchReport.php      # per-row outcomes; CSV export
  Api/
    BatchController.php  # POST /batches, GET /batches/{id}, POST /batches/{id}/run,
                         # GET /batches/{id}/report, POST /batches/{id}/cancel
```

`Bulk\DriveUrl` and `Bulk\CsvParser` are pure functions over strings, so they are unit-testable in the existing suite with no WordPress. `BatchPlanner` and `SectionHeadings` need `$wpdb` and LearnDash, so they belong to the P7.2 integration suite.

---

## Test Framework

<a name="test-framework"></a>

**Framework:** [Codeception](https://codeception.com) 5.3, installed as a dev dependency of the plugin (`web/app/plugins/cbf-slides-importer/composer.json`) rather than of the root project, so the plugin stays self-contained and the root `composer install` is unaffected.

Codeception was chosen over bare PHPUnit because the phases still outstanding need more than unit tests: P7.2 wants an integration test against a real WordPress install, and the REST surface warrants functional tests. Codeception covers all three tiers under one runner and one configuration, and `lucatume/wp-browser` plugs a WordPress-aware module set into it. Adopting it now, while only the unit tier exists, avoids migrating later.

### Suites

| Suite | Needs WordPress? | Covers | Status |
|---|---|---|---|
| `Unit` | No | `Document`, `Pptx`, `Pdf`, `Docx` — parsing, layout, classification, rendering | **Implemented** — 158 tests, 401 assertions, ~0.4 s |
| `Integration` | Yes (WPLoader) | `JobRunner`, `LearnDashImporter`, `$wpdb` access, the LearnDash handoff | P7.2 |
| `Functional` | Yes (WPLoader) | REST controllers: auth, ownership, validation, status codes | P7.9 |

The parsing code is plain PHP that touches only a handful of WordPress helpers (escaping, slashes, translation, `WP_Error`). Those are stubbed in `tests/Support/wordpress-stubs.php`, which the unit suite's bootstrap loads. That keeps the unit tier fast and runnable anywhere — no database, no WordPress, no network — and draws a clear line: **anything that needs real WordPress behaviour belongs in the integration suite, not a bigger stub.**

### Layout

```text
codeception.yml                     runner config; declares the unit bootstrap
tests/
  Unit.suite.yml                    suite modules
  Unit/
    _bootstrap.php                  autoloader + stubs; defines ABSPATH
    IrTest.php                      IR constructors, bullet/mono/font heuristics
    ParserFactoryTest.php           format table, MIME mapping, Drive routing
    SlideClassifierTest.php         cover/hidden/override/regex precedence
    BlockRendererTest.php           IR to Gutenberg blocks, escaping, grouping
    PptxParserTest.php              against deck.pptx
    PdfParserTest.php               against slides.pdf
    DocxParserTest.php              against document.docx
    NumberingTest.php               Word list-type resolution
    PreviewRendererTest.php         parse-to-HTML pipeline, legacy key fallback
    CorpusTest.php                  real decks; skipped unless opted in
  Support/
    wordpress-stubs.php             the WordPress surface the parsers touch
    Helper/Fixtures.php             fixture paths, per-test image directories
  _data/
    build-fixtures.php              regenerates the binaries below
    bin/{deck.pptx,document.docx,slides.pdf}
```

### Fixtures

Real CBF decks are megabytes each and cannot be committed, so the suite ships three small synthetic files (~21 KB total) built by `tests/_data/build-fixtures.php`. Each is constructed to exercise specific behaviour rather than to look realistic:

| Fixture | Exercises |
|---|---|
| `deck.pptx` | title placeholders, cover flag, `show="0"` hidden slide, two-column geometry, bullets, a code paragraph, an inline code run inside prose, footer-band image exclusion |
| `document.docx` | heading-depth sectioning, title-page detection, relative heading levels, XML entity decoding, bullet vs numbered lists via real numbering definitions, a table with a bold header row, an inline image |
| `slides.pdf` | glyph-position text reconstruction, title detection, wrapped-line rejoining, bullet splitting, monospace code detection, footer text and footer image exclusion |

The DOCX and PDF are written as raw bytes rather than through a library, because both need structure the writers will not produce on demand — exact numbering definitions, and precise glyph positions with a footer-band image.

**Corpus test.** `CorpusTest` parses every supported document in a nominated directory and asserts only what must hold for any input: no `WP_Error`, no PHP diagnostics, non-empty output, escaped text. It is skipped when no corpus is found, mirroring how the Python suite skips when `demo.pptx` is absent (R10).

The directory is resolved in this order, so it can be configured per-run or left standing on a machine:

1. `CBF_SI_FIXTURE_DIR` in the environment
2. `CBF_SI_FIXTURE_DIR` in the plugin's `.env` (see `.env.example`)
3. `tests/assets/` — the default

All three are gitignored: real course material is large and not ours to redistribute, and `tests/assets/` in particular sat at 13 MB during development. Relative paths resolve against the plugin root, which is where the test commands run from.

Note that PHP does not read `.env` files on its own, and Codeception's `params: - env` only feeds its own `%PLACEHOLDER%` config interpolation — it neither reads `.env` nor populates `getenv()`. The helper therefore parses `.env` itself via `vlucas/phpdotenv` (the library Bedrock already uses at the repository root), and deliberately parses rather than populates, so running the suite cannot leak settings into the wider process.

Against the 30+ real CBF decks in `tools/slides-to-learndash/assets` it runs 156 assertions in ~53 s, which is why it is not part of the default run.

### Running

```bash
cd web/app/plugins/cbf-slides-importer
composer test           # all suites
composer test:unit      # unit suite only
composer test:build     # regenerate actor classes after changing suite modules
```

or `lando codecept run` from anywhere in the project.

**`register_argc_argv`.** Codeception refuses to start unless this is `On`. The shared `.lando/config/php/php.ini` sets it `Off` deliberately for the web SAPI, and many local CLI builds also default it off, so every entry point above overrides it per-invocation (`php -d register_argc_argv=1`) rather than weakening a web-facing setting.

**PHP floor.** The plugin's minimum PHP was raised from 8.1 to **8.5** to match the root project's `composer.json` (`php >=8.5`) and the Lando appserver. Composer's `config.platform.php` applies to dev dependencies as well as runtime ones, so the old 8.1 pin held the tooling back several major versions. The floor is declared in four places that must stay in step — see the README's Version metadata table.

### Known trap

Every plugin file ends its `ABSPATH` guard with `exit`. Codeception 5 does **not** load `tests/_bootstrap.php` implicitly, so if the bootstrap that defines `ABSPATH` is not wired up, the first autoloaded plugin class silently terminates the run: no failure, no PHP error, just `COMMAND DID NOT FINISH PROPERLY` and exit code 125. The unit suite declares its bootstrap explicitly (`settings.bootstrap` in `codeception.yml`, resolved relative to the suite directory). Any new suite must do the same.

---

## Implementation Phases with Checklist Tasks

### Phase 0: Foundation and Dependency Validation

- [x] **P0.1** ~~Verify Python availability on production VPS~~ — **Verified**: Python is not installed (A1 confirmed). PHP-only approach locked in.
- [x] **P0.2** ~~Verify outbound HTTPS from production VPS~~ — **Verified**: `https://www.googleapis.com/drive/v3/about` accessible from prod (A4 confirmed, R2 closed).
- [x] **P0.3** ~~Check if Action Scheduler is bundled with sfwd-lms~~ — **Verified**: not needed; server-level cron triggers WP-Cron reliably (A3, A9 confirmed, R5 closed).
- [x] **P0.4** ✅ PhpPresentation probe run against both CBF decks (Introduction to Java, Object-Oriented Programming). Results: **7/8 PASS, 1 WARN, 0 FAIL** — Load, layout names, hidden slides (ZipArchive OOXML), EMU boxes, multi-column geometry, text+rich-text, title placeholders all PASS. Image extraction: WARN — shapes are `Drawing\Gd`; use `getContents()` not `getPath()`. **Implementation note:** PhpPresentation returns shape offsets/dimensions in pixels (not EMU); divide python-pptx EMU thresholds by 9525 for PHP. Resolves A6 and R1.
- [x] **P0.5** ~~Confirm `run_import_cli()` entry point~~ — **Verified**: confirmed correct entrypoint (A2).
- [x] **P0.6** ✅ `academy` subsite confirmed: **`blog_id = 2`**. `wp_usermeta` is per-site scoped — tokens stored under a user's `user_id` with `meta_key = 'cbf_si_google_token_enc'` are naturally isolated per user; no additional per-blog keying needed. Phase 1 token storage implementation can proceed.
- [x] **P0.7** ~~Resolve AQ1~~ — **Verified**: partner users import from a CBF shared Drive folder (AQ1-b confirmed). Folder-restricted Picker with configurable `cbf_si_drive_folder_id` setting is the chosen approach. (resolves A7, R12 → R12/R13 updated)
- [x] **P0.8** Create `web/app/plugins/cbf-slides-importer/` directory; initialise `composer.json` and `package.json` for the new plugin (resolves A8) — committed 2026-08-24, 31 files, namespace `CodingBlackFemales\SlidesImporter`
- [x] **P0.9** Add `google/apiclient` and `phpoffice/phppresentation` to plugin's `composer.json`; verify no version conflicts with root `composer.json` dependencies; resolve any conflicts — `google/apiclient ^2.15`, `phpoffice/phppresentation ^1.1` (1.2.0 installed); no conflicts with root `composer.json`

### Phase 1: Plugin Scaffold and Auth

- [x] **P1.1** Create `cbf-slides-importer.php` bootstrap: plugin header, ABSPATH guard, dependency check for `learndash-bulk-lessons-or-topics` active (NR5), init hook (resolves NR5)
- [x] **P1.2** Create `Install.php` (`Installer.php` in design): `register_activation_hook` creates both DB tables via `dbDelta()`; stores `cbf_si_db_version`; assigns `cbf_slides_import` capability to `administrator` role
- [x] **P1.3** Create `uninstall.php`: drops tables, removes wp_options keys, removes wp_usermeta tokens (guarded by `WP_UNINSTALL_PLUGIN`) — file confirmed present
- [x] **P1.4** Implement `Crypto.php` (`TokenStore.php` in design): AES-256-GCM encrypt/decrypt using `CBF_SI_ENCRYPTION_KEY` env var; graceful error if env var absent (resolves NR2, NR6)
- [x] **P1.5** Implement `Google/OAuthClient.php` (`GoogleOAuth.php` in design): build authorisation URL with `drive.readonly` scope and CSRF state token; token exchange; token refresh; revoke; state stored in transient with 10-min TTL (resolves R3, Security plan)
- [x] **P1.6** Register OAuth callback via `Admin/OAuthBridge.php`; validate state; exchange code; store encrypted token
- [x] **P1.7** Implement REST `GET /auth/url`, `GET /auth/status`, `DELETE /auth/token` endpoints in `Api/AuthController.php`; nonce + capability checks on all (resolves NR6)
- [x] **P1.8** Create admin page scaffold (`Admin/ImporterPage.php`, admin-page template): registers submenu under LearnDash; enqueues assets; Google Picker JS wired
- [x] **P1.9** Implement `Admin/SettingsPage.php`: registers settings screen (accessible only to `manage_options`); fields: Google Client ID, encrypted Client Secret, Drive Shared Folder ID (`cbf_si_drive_folder_id`); validates Folder ID before saving (resolves AQ1-b, R13)
- [x] **P1.10** Admin notice when `cbf_si_drive_folder_id` is empty: "The shared Drive folder has not been configured." Disables Picker button until set (resolves AQ1-b)
- [x] **P1.11** Auth UI component: "Connect Google Account" button + status display in admin JS; calls `/auth/url` and `/auth/status`

### Phase 2: Drive Integration and PPTX Parsing

- [x] **P2.1** Implement `Google/DriveClient.php`: wraps `google/apiclient`; `exportPptx(fileId)` downloads to temp path; includes retry on 429/5xx; validates mime type of response (resolves R9)
- [x] **P2.2** Implement `Api/DriveController.php` (`FilePicker.php` in design): returns picker config for Google Picker JS API; OAuth token passed as short-lived value only
- [x] **P2.3** Implement REST `GET /drive/picker-config`; passes `folder_id` from `cbf_si_drive_folder_id` setting; Google Picker JS initialised with `setParent(folder_id)`; picker close event wired to `POST /jobs`; Drive 403 surfaces user-friendly message (resolves AQ1-b, R12)
- [x] **P2.4** Implement `Pptx/Parser.php`: slide iteration, title extraction, layout name, visible-slide detection via `ZipArchive`, image extraction via `Drawing\Gd::getContents()` + `getExtension()`. PhpPresentation pixel dims handled correctly. (Resolves A6, R1, R4)
- [x] **P2.5** Implement `Pptx/SlideClassifier.php`: auto-classify slides by layout name heuristics (SECTION_HEADER → heading, blank → hidden, etc.); apply user overrides from config
- [x] **P2.6** Implement `Pptx/BlockRenderer.php`: WP Gutenberg block HTML from parsed slide segments (paragraphs, headings, lists, code, columns, images). Fixed `Font::isUnderline()` → `getUnderline()` bug. (Resolves R1)
- [x] **P2.7** Multi-column detection in `Pptx/GeometryDetector.php`: geometry-based overlap ratio logic ported from `slide_geometry.py` with pixel thresholds. (Resolves R1)
- [x] **P2.8** Implement REST `POST /jobs` endpoint in `Api/JobController.php`: validate Drive file ID; dispatch background download + parse job; return `job_id`
- [x] **P2.9** Background job handler in `Import/JobRunner.php` (`cbf_si_process_job` hook): download PPTX → parse → classify → render → store result_summary → set status `parsed`; catch all exceptions → status `failed`. Fixed `parsed` missing from ENUM. (Resolves R4, R5)
- [x] **P2.10** `GET /jobs/{id}` and `GET /jobs` REST endpoints implemented in `Api/JobController.php` (resolves R6)
- [x] **P2.11** Set up temp file cleanup cron (`cbf_si_cleanup`) and stale job reset (resolves R4, Memory OOM recovery in Failure Isolation)

### Bug fixes (post-P2)

- [x] **BF1** `learndash-bulk-lessons-or-topics` plugin not found — fix: use `global $extended_learndash_bulk_create` + call `run_import()` not `run_import_cli()` (commit d583184)
- [x] **BF2** Google Drive export HTTP 403 exportSizeLimitExceeded on large decks — fix: fall back to direct Docs export URL with Bearer token streamed via `wp_remote_get(stream:true)` (commit e094d86)
- [x] **BF3** Import produces only headings, empty columns, no body text — root cause: PHP shape objects (`PhpPresentation\Shape\RichText`) cannot survive JSON serialisation; stored `classified` in DB became empty arrays. Fix: import phase re-parses the PPTX from `pptx_path` and re-classifies from stored config; `classified` key no longer written to `result_summary` (commit b0468be)

### Phase 3: Configuration UI

- [x] **P3.1** `Api/ConfigController.php` implements full CRUD on `cbf_slide_import_configs` (inline DB ops — no separate ConfigRepository needed); validates `slide_overrides` JSON; sanitises all inputs (resolves NR7, R7)
- [x] **P3.2** REST `GET/POST /configs` and `GET/PUT/DELETE /configs/{id}` endpoints registered via `Api/Router.php`; server-side validation present
- [x] **P3.3** Build slide map UI component: list of slides with index, title, layout name, auto-detected type, override dropdown (`cover|heading|content|hidden`) — currently absent from admin JS (resolves UX plan)
- [x] **P3.4** Mode toggle, course selector (LearnDash API), lesson title field implemented in config panel; post_title passed through REST API to LearnDashImporter (slide headings toggle deferred to P3.5)
- [x] **P3.5** Validate config inputs client-side before saving; wire config UI to `POST/PUT /configs` and associate config with job before import (resolves NR7)

### Phase 4: Preview

- [x] **P4.1** Implement `PreviewRenderer.php`: assemble block HTML for lesson and each topic using BlockRenderer + DeckConfig; store in transient `cbf_si_preview_{job_id}_{user_id}` (1h TTL) (resolves NR4, R8)
- [x] **P4.2** Trigger preview generation on job completion (after parse) and on config update
- [x] **P4.3** Implement REST `GET /jobs/{id}/preview` endpoint; return 202 if not yet ready (resolves G3)
- [x] **P4.4** Build preview panel UI: scrollable block HTML panels per lesson/topic; warning if slides produced no content; "Back to Configure" and "Start Import" actions

### Phase 5: Import Orchestration

- [x] **P5.1** Implement `ImportOrchestrator.php`: call `Extended_LearnDash_Bulk_Create::run_import_cli()` programmatically with block HTML rows; handle `WP_Error` returns; track `created_post_ids` (resolves A2, NR3, NR4) — implemented as `LearnDashImporter.php`
- [x] **P5.2** Implement idempotency: before calling `run_import_cli()`, check `cbf_slide_import_jobs` for a prior `complete` job with same `drive_file_id + config_hash`; offer update-or-skip choice in UI (resolves NR3, R7) — config_hash (SHA-256 of file_id+mode+course+overrides) stored in result_summary at trigger time; 409 returned when prior done job matches; inline warning in preview panel, confirm dialog in configure view; force=true bypasses check
- [x] **P5.3** Implement media rewrite: call `ELDBC_Media::rewrite_paths()` with temp image dir path; local file system confirmed (A5) — no S3 routing to account for
- [x] **P5.4** Draft-first import: posts are created in their natural published state; if any errors occur, all created posts are reverted to `draft` so students never see partial content (resolves NR4) — revert-on-error approach chosen over temporary `wp_insert_post_data` filter to avoid interfering with concurrent post-creation processes
- [x] **P5.5** Implement REST `POST /jobs/{id}/import` endpoint: validate status is `preview_ready`; dispatch background import job; return 202 (resolves R7)
- [x] **P5.6** Implement background import job handler: `switch_to_blog()`; run orchestrator; `restore_current_blog()`; update job status + `created_post_ids` + `result_summary` (resolves R6)
- [x] **P5.7** Build import confirmation modal UI: summary of content to be created, "Confirm" / "Cancel" (resolves UX plan) — fixed overlay modal with lesson/topic count and course name; conflict (409) handled inline within same modal (warning strip injected, button swapped to "Re-Import anyway"); identical UX from configure and preview views; no window.confirm()
- [x] **P5.8** Import status feedback: animated progress bar scrapped (no reliable sub-phase metric available without backend instrumentation); existing job list status badges (pending/downloading/parsing/importing/done/failed) provide sufficient feedback; config panel closes on import trigger and `_pollJob` keeps badge current

### Phase 6: Operations and Admin Tooling

- [ ] **P6.1** Implement `GET /cbf-si/v1/health` endpoint: dependency checks, schema version, queue depth, last successful import (resolves Operations plan)
- [ ] **P6.2** Implement WP-CLI commands: `wp cbf-si jobs list`, `wp cbf-si jobs retry <id>`, `wp cbf-si cleanup` (resolves Operations plan)
- [ ] **P6.3** Implement admin dashboard widget on plugin page: jobs summary by status, stalled jobs count, last success (resolves Operations plan)
- [ ] **P6.4** Document `CBF_SI_ENCRYPTION_KEY` env var requirement in README and `.env.dist`; add validation on plugin activation (resolves NR2)
- [x] **P6.5** ✅ Plugin README written (`web/app/plugins/cbf-slides-importer/README.md`): requirements, supported formats, pipeline overview, setup (encryption key, GCP project, settings, capability), import flow, background jobs, REST surface, code layout, how to add a format, development commands, troubleshooting (resolves G5)

### Phase 7: Quality and Hardening

- [x] **P7.0** ✅ Codeception 5.3 adopted as the PHP test framework and wired into the plugin: `codeception.yml`, `Unit` suite, `composer test` / `test:unit` / `test:build` scripts, `lando codecept` tooling, generated artefacts gitignored and excluded from PHPCS. Plugin PHP floor raised 8.1 → 8.5 to match the root project, which had been pinning the tooling to unsupported versions. See [Test Framework](#test-framework).
- [x] **P7.1** ✅ Unit suite implemented — **158 tests, 401 assertions, ~0.4 s**, no WordPress required. Covers `Ir` (bullet/mono/font heuristics, IR constructors), `ParserFactory` (format table, MIME mapping, Drive routing), `SlideClassifier` (cover/hidden/override/regex precedence), `BlockRenderer` (every block type, escaping, run nesting, list and code grouping), all three parsers against committed fixtures, `Docx\Numbering`, and `PreviewRenderer` (including the legacy `pptx_path` fallback). Regression guards included for the BF3 serialisation bug and the monospace-paragraph misclassification.
  - `TokenStore` (`Crypto`) and `GoogleOAuth` are **not** covered: both need WordPress options/usermeta and HTTP mocking, so they move to P7.2's WordPress-backed suite rather than being stubbed into the unit tier.
- [ ] **P7.2** Add the `Integration` suite (`lucatume/wp-browser` + WPLoader against the Lando database): end-to-end import of `tests/_data/bin/deck.pptx`; assert post type, title, course association and block structure; cover `Crypto` round-trip, `OAuthClient` state validation with mocked HTTP, `JobRunner` phase transitions, and `ConfigController` persistence
- [x] **P7.3** ✅ PHPCS passes on all new plugin code including the test suite. Plugin-wide count is **12 errors, down from a pre-existing baseline of 18** — every remaining one is in a function that was already over the complexity limit before this work
- [ ] **P7.4** Security review: verify no token values in logs, no IDOR on job endpoints (user can only access their own jobs), no path traversal in temp file handling
- [ ] **P7.5** Add `.htaccess` / Apache `<Directory>` block denying direct HTTP access to `cbf-slides-tmp/` uploads subdirectory
- [x] **P7.6** ~~Test S3-uploads transparency~~ — **Not needed**: S3 Uploads disabled in all environments (A5 confirmed). ELDBC_Media returns local attachment URLs.
- [ ] **P7.7** Load test: import a deck with 60 slides and 40 images; verify no memory limit error and completion <10 minutes (resolves R4)
- [ ] **P7.8** Test multisite subsite isolation: import as user on `academy` subsite; confirm content not visible on `wp` or `jobs` subsites (resolves R6, A7)
- [ ] **P7.9** Add the `Functional` suite for the REST surface: capability enforcement on every route, IDOR (user A cannot read user B's jobs), upload validation (extension, size, magic bytes), and the 409 idempotency conflict — currently only reachable by hand
- [ ] **P7.10** Wire `composer test` into CI alongside the existing lint steps; publish the corpus test as an opt-in job with `CBF_SI_FIXTURE_DIR` pointed at a fixture store

### Phase 8: Bulk CSV Migration

**Complete**, apart from integration tests. Every task is implemented and verified end to end on Lando: a three-row CSV produced the correct course structure — a topic nested under an existing session, and two new sessions under a heading the batch created. P8.6a was settled empirically and changed the ordering design (see A14, R22). **A12 is now closed** — pre-flight over the real 132-row sheet resolved all 109 importable files with no permission failures, and in doing so exposed R25, a live Guzzle-version defect that had silently disabled the Drive metadata path.

- [x] **P8.0** ✅ AQ2–AQ6 resolved with CBF: AQ2-a (ignore `heading` on topic rows), AQ3-c (ignore `session_id` on session rows), AQ4-a (append to end of section in row order), AQ5 (accept `session`/`lesson` synonyms), AQ6 (two-stage batch flow, confirmed by the ~120-row CSV size)
- [x] **P8.1** ✅ Source material measured from the draft curriculum spreadsheet — see [Source material](#bulk-migration). 132 rows, 126 links: 74 Google Slides, 19 Drive binaries, 16 Google Docs, **12 Google Forms**, 6 with no link, 3 GitHub repos, 2 other external. **~17% of rows cannot become lesson content** (R20). All 121 file IDs distinct. Query strings and an `http://` link present, both of which the URL parser must handle. **A12 since closed** by the pre-flight run — see P8.17
- [x] **P8.2** ✅ Implemented `Bulk/DriveUrl.php`: parse on the **path segment** (`presentation`, `document`, `forms`, `spreadsheets`, `file`, `open`) rather than on finding an ID, so Forms and Sheets are recognised and rejected rather than mistaken for importable files. Tolerate `http://`, discard query strings and fragments. Unit-tested against a table drawn from the real spreadsheet, including every unsupported form
- [x] **P8.3** ✅ Implemented `Bulk/CsvParser.php`: header mapping, per-row validation against the column contract, row cap, sanitisation; `type` accepts `session`/`lesson`/`topic` case-insensitively (AQ5); a populated `heading` on a topic row is a notice, not an error (AQ2-a); `session_id` on a session row is ignored (AQ3-c). Returns typed rows plus per-row errors. Unit-tested including malformed, oversized and hostile input (R18)
- [x] **P8.4** ✅ Schema: add `cbf_slide_import_batches`; add `batch_id` and `batch_row` to the jobs table via `dbDelta()`; bump `Install::DB_VERSION` to `1.1.0`; extend `uninstall.php`. Verify existing single-file jobs (`batch_id IS NULL`) are unaffected
- [x] **P8.5** ✅ Implemented `Bulk/BatchPlanner.php`: resolve each row against the selected course — locate or plan headings, validate that a topic's `session_id` names a lesson in this course (R21), call Drive metadata for every URL, classify unsupported sources with a specific reason (R20), flag duplicates via the existing `config_hash`. Produces the plan stored on the batch. Writes nothing
- [x] **P8.6a** ✅ **The `ID` is a millisecond Unix timestamp**, assigned client-side. Read from a live course whose oldest heading carries `"ID":1631808942506` (16 Sep 2021, when that course was built). `next_id()` uses `max(now_ms, highest + 1)` so same-millisecond creates stay distinct. The same probe surfaced the finding that mattered more — ordering is read from the `ld_course_steps` tree, not `menu_order` (A14, R22)
- [x] **P8.6** ✅ Implemented `Bulk/SectionHeadings.php`: read `course_sections`, match case-insensitively, create missing headings in **one** write at batch start (R14); expose the end-of-batch reorder pass that places created lessons under their heading (R15, AQ4)
- [x] **P8.7** ✅ Implemented `Api/BatchController.php`: `POST /batches` (multipart CSV + `course_id` + `overwrite`), `GET /batches/{id}`, `POST /batches/{id}/run`, `GET /batches/{id}/report`, `POST /batches/{id}/cancel`. Capability and ownership checks identical to `JobController`
- [x] **P8.8** ✅ Implemented `Bulk/BatchRunner.php`: create one job per valid row carrying its own config (mode from `type`, `course_id`, `lesson_id` from `session_id`, `post_title` from `title`, file ID from `url`); schedule **sequentially**, each completion queueing the next; drive batch status transitions
- [x] **P8.9** ✅ Added an auto-import path to `JobRunner`: a job with a `batch_id` proceeds from `parsed` straight to import without waiting for the manual trigger, and reports its outcome to the batch. The single-file preview gate is untouched
- [x] **P8.10** ✅ Made the `config_hash` duplicate check non-blocking in batch context: a match becomes a **skipped** row outcome instead of a 409 the user must confirm
- [x] **P8.11** ✅ Added `supportsAllDrives=true` to the Drive API metadata and export calls — only the media-download path sets it today, so shared-drive files fail on the export path (R16)
- [x] **P8.12** ✅ Implemented `Bulk/BatchReport.php`: per-row outcomes written incrementally, counts by outcome, CSV download
- [x] **P8.13** ✅ Built the bulk UI: CSV upload control alongside the existing single-file actions; configuration panel showing **only** course selector and overwrite toggle; pre-flight validation table with per-row errors and a confirm action; live progress; report view with CSV download. Uses LearnDash custom labels throughout
- [x] **P8.14** ✅ Batch cancellation: stop scheduling further rows, leave completed rows in place, mark the batch `cancelled`, and make clear in the report what was and was not created
- [~] **P8.15** Unit tests **done** — `DriveUrl` (25), `CsvParser` (33) and `BatchReport` (10), URL shapes taken from the real spreadsheet with synthetic IDs; suite now `226 tests, 644 assertions`. Integration tests **outstanding**, blocked on the P7.2 suite: heading creation and reuse, sequential ordering, duplicate skip, partial-failure reporting, and a full small-batch run against fixtures. All of these were exercised by hand on Lando in the meantime
- [x] **P8.17** ✅ **Pre-flight run against the real material, closing A12.** All 132 rows of the draft curriculum sheet, with the migrating account's live token: **109 ready, 0 unreachable**; the 23 failures are all source-type rejections (12 Forms, 6 blank, 5 non-Drive), exactly as P8.1 predicted. 8 section headings planned. Resolved MIME types: 42 `.pptx`, 32 native Slides, 19 PDF, 14 `.docx`, 2 native Docs — disproving A15 and confirming the PDF/DOCX support added in v2.3.0 covers **75 of the 109 rows**. The run surfaced R25 and was only completable after fixing it
- [x] **P8.16** ✅ Documentation: README section on the CSV format with a worked example and a downloadable template; **the two-pass session-then-topic workflow required by AQ3-c (R21)**; the list of source types that cannot be imported and what to do with them instead (R20); troubleshooting entries for the common row failures

---

## Validation Plan

### Static checks
- [x] PHPCS passes on all new plugin code and the test suite; plugin-wide errors reduced from a pre-existing baseline of 18 to 12, all in functions that were already over the complexity limit
- [ ] PHPStan level 6 (or equivalent) passes on all new PHP files
- [ ] ESLint passes on all new JS
- [ ] No `error_log()` calls that could print tokens (grep for `cbf_si.*token` patterns in log calls)

### Unit tests (Codeception `Unit` suite — no WordPress required)

Run with `composer test:unit` from the plugin directory, or `lando codecept run Unit`.

- [x] `Ir`: bullet glyphs split without a following space; a hyphen needs one; monospaced families detected by name including PDF subset prefixes; weight and slant read off embedded font names
- [x] `ParserFactory`: extension dispatch; Google editor and binary MIME types both resolve; export MIME only for editor files; picker MIME list; unit nouns per format; unsupported input rejected before a parser is reached
- [x] `SlideClassifier`: layout regex is anchored and case-insensitive; a malformed regex is survivable; user override beats auto-detect; cover and hidden cannot be overridden; overrides keyed by the 1-based UI number
- [x] `BlockRenderer`: every block type (`wp:paragraph`, `wp:list` ordered and unordered, `wp:code`, `wp:heading`, `wp:columns`, `wp:table`, `wp:image`); consecutive bullets and code lines group into one block; run formatting nests with `<code>` innermost; document text is escaped; unsafe link schemes dropped; repeated unit titles emit one heading
- [x] `Pptx\Parser`: titles from placeholders; `show="0"` hidden slides; two-column geometry; footer-band images excluded; a partly monospaced paragraph stays prose (regression guard); the parse result holds no PHP objects (BF3 regression guard)
- [x] `Pdf\Parser`: word spacing reconstructed from glyph positions; title is the largest text at the top; wrapped lines rejoined; bullet markers stripped; monospace becomes code; footer text and footer images excluded
- [x] `Docx\Parser`: sectioning descends past a lone top-level heading; title page detected; heading levels relative to the split depth; XML entities decoded exactly once; tables survive the empty-paragraph filter; inline images extracted
- [x] `Docx\Numbering`: bullet vs counting formats; unknown level falls back to level zero; missing, list-free and malformed documents all yield an empty map rather than an error
- [x] `PreviewRenderer`: all three formats render; cover and hidden content excluded; stored overrides applied and malformed ones ignored; preview images resolve to absolute URLs while import uses `media/`; legacy `pptx_path` summaries still resolve
- [x] Every parser returns `WP_Error` — never an exception — for missing, malformed and empty input
- [ ] `Crypto` (`TokenStore`): encrypt → decrypt round-trip; missing env var returns WP_Error — **deferred to P7.2**, needs WordPress
- [ ] `OAuthClient`: CSRF state validation; expired token triggers refresh; revoke calls the correct endpoint — **deferred to P7.2**, needs WordPress and HTTP mocking
- [ ] `ConfigController`: upsert; config_hash computed correctly; duplicate insert updates rather than errors — **deferred to P7.2**, needs `$wpdb`
- [ ] `LearnDashImporter`: WP_Error from learndash-bulk sets job to failed; duplicate dispatch exits early — **deferred to P7.2**, needs the LearnDash plugin

### Bulk migration tests (Phase 8)

- [x] **Live pre-flight** over the real curriculum sheet (132 rows, migrating account's own token): 109 ready, 0 unreachable, 8 headings planned, MIME resolved for every ready row — writes nothing to LearnDash
- [x] `DriveUrl`: every accepted URL form yields the right file ID; malformed and non-Drive URLs are rejected rather than guessed at; Forms and Sheets rejected by path segment despite sharing the `/d/ID/` shape
- [x] `CsvParser`: missing header, unknown `type`, empty `title`, absent `url`, unknown columns, row cap exceeded, values containing quotes, commas, newlines and leading `=` — each reported per-row without aborting the parse
The remaining bulk checks need WordPress and LearnDash, so they belong to the P7.2 integration suite. Each was verified by hand on Lando during P8.6–P8.14; none is covered by an automated test yet.

- [ ] `BatchPlanner`: `session_id` belonging to a different course is rejected; an unreachable Drive URL is reported at pre-flight, not at import; a row matching a prior import is marked skipped
- [ ] `SectionHeadings`: an existing heading is reused, not duplicated; matching ignores case; several new headings are created in one write; **a concurrent write does not lose headings** (R14 — the regression this design exists to prevent)
- [ ] Ordering: sessions created under a heading land under that heading in the course builder, in CSV row order (R15, AQ4)
- [ ] Sequencing: only one job per batch is ever in flight
- [ ] Partial failure: a batch where row 3 fails still creates rows 1, 2 and 4; the failed row's post is reverted to draft and the others are not; batch status is `completed_with_errors`
- [ ] Cancellation: cancelling mid-batch stops further rows and leaves completed rows intact
- [x] Report: counts match the per-row outcomes; CSV download parses cleanly and defuses leading `=+-@`; re-uploading the corrected CSV skips the rows already done
- [ ] Isolation: a batch job carries `batch_id`; single-file jobs still work unchanged and are excluded from batch queries

### Corpus regression (opt-in)
- [x] `CorpusTest` parses every supported document in `CBF_SI_FIXTURE_DIR` and asserts no errors, no PHP diagnostics, non-empty output and escaped text. Verified against the 30+ real CBF decks in `tools/slides-to-learndash/assets` — 156 assertions, ~53 s. Skipped when the variable is unset, mirroring the Python suite's `demo.pptx` convention (R10)

### Existing Python tests (regression — must not break)
- [ ] `test_columns_layout.py` — skip if `demo.pptx` absent; document skip in CI config (resolves R10)
- [ ] `test_hidden_slides.py` — passes unchanged
- [ ] `test_slide_merge.py` — passes unchanged
- [ ] `test_content_postprocess.py` — passes unchanged

### Integration tests (Codeception `Integration` suite — P7.2, not yet built)
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

1. A non-technical editor can, without any CLI access, pick a Google Slides deck, Google Doc, PDF or Word document, configure it, preview the output, and trigger an import that creates correctly structured LearnDash content in the `academy` subsite.
2. The import is idempotent: running the same deck twice with the same configuration does not create duplicate posts.
3. All created posts are initially in draft status and promoted to the target status only after the full batch is confirmed.
4. Encrypted token storage is in place; no token value appears in any log or REST response.
5. The existing `import-slides.sh` / WP-CLI pipeline produces the same output as before (regression tests pass).
6. PHPCS passes; no IDOR vulnerabilities; path traversal rejected.
7. Staging end-to-end test passes with at least two real CBF decks.
8. `GET /cbf-si/v1/health` returns healthy on production.
9. Plugin README documents setup steps; non-technical user guide is present.
10. All Phase 0 verifications (P0.1–P0.6) are documented with actual results.
11. `composer test` passes, covering all three source formats, and runs in CI.
12. An editor can migrate a course from a single CSV: upload, review the pre-flight report, confirm, and receive a per-row outcome report — with sessions placed under the right section headings and topics under the right sessions.
13. A batch with failing rows completes the rows it can, reports each failure with an actionable reason, and leaves no partially created content behind.

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

**All Phase 0 verifications complete. Ready to begin Phase 1.**

- ~~P0.4~~ ✅ PhpPresentation fidelity confirmed.
- ~~P0.6~~ ✅ `academy` blog_id = 2; `wp_usermeta` per-site scoped confirmed.

All assumption open questions (A1–A9, AQ1) are resolved. All Phase 0 gates (P0.1–P0.9 where applicable) are cleared. **Phase 1 (Plugin Scaffold and Auth) can begin.**

> **P0.4 results (2026-08-24):** PhpPresentation probe ran clean (exit 0) against both CBF decks. Critical implementation notes for `PptxParser.php`:
> - Shape offsets/dimensions are in **pixels** (96 DPI), not EMU. Divide all python-pptx EMU thresholds by 9525. Use `$prs->getLayout()->getCX('px')` for slide width.
> - Hidden slides: use `ZipArchive` to read `ppt/slides/slide{N}.xml` and check `show` attribute — no public PhpPresentation API for this.
> - Image shapes load as `Drawing\Gd`; extract bytes with `$shape->getContents()`, extension with `$shape->getExtension()`.
> - Title placeholders: `$shape->getPlaceholder()->getType()` returns `'title'` or `'ctrTitle'` — reliable on both decks.
> - Layout names, multi-column detection, and rich-text classification all function with high fidelity.
