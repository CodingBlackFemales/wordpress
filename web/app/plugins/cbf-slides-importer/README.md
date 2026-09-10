# CBF Slides Importer

Imports slide decks, PDFs and Word documents into LearnDash lessons and topics from the WordPress admin. Editors pick a file from a shared Google Drive folder or upload one, review a preview of the generated content, then import.

This is the browser-based counterpart to [`tools/slides-to-learndash`](../../../../tools/slides-to-learndash), the Python CLI that produces CSVs for the same LearnDash importer. The two share a content model, not code.

## Requirements

- PHP 8.5+, WordPress 6.0+
- [LearnDash Bulk Lessons or Topics](https://github.com/serenichron/learndash-bulk-lessons-or-topics) active — this plugin builds the rows and delegates post creation to it
- A Google Cloud project with the Drive API enabled, for the Drive picker
- `CBF_SI_ENCRYPTION_KEY` set in the environment (see [Setup](#setup))

Run `composer install` in this directory after checkout; `vendor/` is not committed.

## Supported formats

| Format  | Source                                         | One unit of content is | Structure comes from                 |
| ------- | ---------------------------------------------- | ---------------------- | ------------------------------------ |
| `.pptx` | Google Slides (exported) or an uploaded deck   | a slide                | shape geometry and placeholder types |
| `.pdf`  | any PDF, typically a deck exported to PDF      | a page                 | glyph positions and font metrics     |
| `.docx` | Google Docs (exported) or an uploaded document | a section              | heading levels                       |

Google Slides and Google Docs files are exported by Drive to PPTX and DOCX on the way in. Files already stored in Drive in one of the three formats are downloaded unchanged.

## How it works

Each source parser emits the same format-neutral intermediate representation, so classification, layout analysis and block rendering are shared:

```text
Pptx\Parser ─┐
Pdf\Parser  ─┼─→ ParsedDeck ──→ SlideClassifier ──→ BlockLayout ──→ BlockRenderer ──→ LearnDashImporter
Docx\Parser ─┘   (Document\Ir)   type per unit      rows/columns     Gutenberg HTML    posts
```

A job moves through these statuses:

`pending` → `downloading` → `parsing` → `parsed` → `importing` → `done`, or `failed` at any point.

Parsing stops at `parsed` and waits. Nothing is written to LearnDash until an editor reviews the preview and triggers the import, which re-reads the source file so any configuration changed in the meantime is applied.

### Vocabulary

The pipeline calls one unit of content a "slide" throughout — in the database, the REST payloads and the per-unit override map. A PDF page and a Word section are slides as far as the code is concerned. Only the admin UI relabels them, using the noun the source format's parser reports.

### What the parsers produce

All three emit paragraphs classified as body text, headings, bullets or code, with bold, italic, underline, strikethrough, inline code and links preserved. Images are extracted to the job's temp directory and referenced as `media/<filename>`, which `ELDBC_Media::rewrite_paths()` swaps for real attachment URLs after the posts exist.

**PPTX.** Shape offsets drive the layout: shapes sharing a row become a `wp:columns` block, and shapes in the footer band (below 87% of the slide height) are dropped, which is what removes the CBF logo and copyright line from every slide. Hidden slides are read from the OOXML `show` attribute and excluded. Slide 1 is treated as a cover and excluded from content.

**PDF.** A PDF stores glyphs at coordinates and nothing else, so structure is reconstructed: glyphs are grouped into lines by baseline, lines into blocks by proximity and alignment, and wrapped lines are rejoined where the previous line ran to the block's right edge. The largest text in the top of a page becomes its title. Bold and monospace are inferred from embedded font names — but only where the page's dominant font is _not_ monospaced, since decks that set all their body copy in Consolas would otherwise import as one long code block. Images are located by walking the content stream's transformation matrix stack, because position is what separates real content from the logo repeated on every page.

**DOCX.** Sections split at the shallowest heading depth that occurs more than once, so a document whose only Heading 1 is its title splits on Heading 2 instead of collapsing into a single section. A leading heading with no body of its own is treated as a title page and excluded. Tables become `wp:table`; list types are resolved by reading `word/numbering.xml` directly, since PhpWord's reader records which numbering definition an item belongs to but not whether it renders as a bullet or a counter.

## Setup

### 1. Encryption key

OAuth client secrets and per-user access tokens are stored encrypted with AES-256-GCM. The key comes from `CBF_SI_ENCRYPTION_KEY`, read as either a PHP constant or an environment variable. Without it, nothing is stored and the plugin reports an error rather than falling back to plaintext.

```ini
# .env (Bedrock)
CBF_SI_ENCRYPTION_KEY='a long random passphrase'
```

Any length works — the value is run through SHA-256 to derive the 32-byte key. Changing it invalidates every stored token, and users will need to reconnect.

### 2. Google Cloud project

Enable the Google Drive API and the Google Picker API, then create an OAuth 2.0 **Web application** client. Add this redirect URI, using the site's real host:

```text
https://example.com/wp-json/cbf-si/v1/auth/callback
```

The plugin requests the `drive.readonly` scope only.

### 3. Plugin settings

Under **LearnDash → Slides Importer**, on the **Settings** tab (administrators only — editors see the Import screen with no tab bar):

| Setting                  | Value                                                                                                 |
| ------------------------ | ----------------------------------------------------------------------------------------------------- |
| OAuth Client Secret JSON | the JSON downloaded from Google Cloud, pasted whole — validated to contain a `web` or `installed` key |
| Shared Drive Folder ID   | the folder editors browse, from its Drive URL                                                         |

### 4. Capability

Activation grants `cbf_slides_import` to administrators. Grant it to other roles to let them import:

```php
get_role( 'editor' )->add_cap( 'cbf_slides_import' );
```

Activation also creates the `cbf_slide_import_configs` and `cbf_slide_import_jobs` tables (under the site's `$wpdb->prefix`) and schedules the hourly cleanup event.

## Importing

Under **LearnDash → Import Documents**, connect Google Drive once, then either choose a file from the configured Drive folder or upload one. Uploads are capped at `wp_max_upload_size()` and checked against the format's magic bytes, so a renamed file is rejected at upload rather than failing later inside a parser.

Once a job reaches `parsed`, its configuration panel offers:

| Option    | Effect                                                                        |
| --------- | ----------------------------------------------------------------------------- |
| Title     | the post title; defaults to the file name                                     |
| Mode      | `lesson-only` creates an `sfwd-lessons` post, `topic` creates an `sfwd-topic` |
| Course    | the course to attach to                                                       |
| Lesson    | in `topic` mode, the lesson to nest under                                     |
| Overwrite | update an existing post matched by title instead of skipping it               |
| Unit map  | override each unit's detected type: cover, heading, content or hidden         |

Units marked cover or hidden are excluded from the generated content. **Preview content** re-renders from the current settings without importing.

Triggering an import for a Drive file that was already imported with the same content-affecting settings returns a 409 and asks for confirmation. The check hashes the Drive file ID, mode, course and unit map — not the title or overwrite flag, which are post metadata rather than content. Local uploads have no stable identity and are not deduplicated.

If any error occurs mid-batch, every post created by that job is reverted to draft so students never see partial content.

## Bulk migration from a CSV

Migrating a whole course one file at a time does not scale. Instead, upload a CSV describing every piece of content and where it belongs.

| Column       | Required on  | Meaning                                                                                                                                               |
| ------------ | ------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| `heading`    | session rows | Section heading the session goes under; created if the course does not have it. Ignored on topic rows — LearnDash sections group sessions, not topics |
| `session_id` | topic rows   | Post ID of the existing session the topic nests under. Ignored on session rows                                                                        |
| `type`       | all rows     | `session` (or `lesson`) or `topic`                                                                                                                    |
| `title`      | all rows     | Title of the created post                                                                                                                             |
| `url`        | all rows     | Google Drive link to the source document                                                                                                              |

Course and Overwrite are chosen once in the panel and apply to every row, so they are not columns.

```csv
heading,session_id,type,title,url
Foundations,,session,Introduction to Git,https://docs.google.com/presentation/d/FILE_ID/edit
Foundations,,session,Command Line Basics,https://drive.google.com/file/d/FILE_ID/view
,412,topic,Git Exercises,https://docs.google.com/document/d/FILE_ID/edit
```

### Sessions before topics

A topic's `session_id` must name a session that **already exists**. A session row does not report its new ID back into the CSV, so one file cannot create a session and a topic under it in the same run. Migration goes in two passes:

1. Upload the session rows. The report gives each created session's post ID.
2. Put those IDs into the `session_id` column of a topic CSV, and upload that.

Sessions already live in LearnDash need only the second pass.

### What happens

Uploading **validates only** — nothing is created until you confirm. Every row is checked and every Drive link resolved, so the report tells you what will happen before it happens. You can upload, fix the spreadsheet, and upload again at no cost.

On confirmation the rows import one at a time. That is slower than it could be, deliberately: a session's section is decided by its position in the course's lesson list, and running rows in parallel would interleave those positions and land content under the wrong headings. Expect roughly 10–30 seconds per row — a 120-row migration runs for the better part of an hour, in the background.

### Sources that cannot be imported

Expect a meaningful minority of rows to be rejected. In the curriculum this feature was built for, about a sixth of rows pointed at things that are not documents:

| Source                             | Why                                   | What to do                          |
| ---------------------------------- | ------------------------------------- | ----------------------------------- |
| Google Form                        | Quizzes have their own question model | Build the quiz in LearnDash         |
| Google Sheet                       | Not lesson content                    | Export or rewrite it as a document  |
| GitHub repository, external course | Not in Drive                          | Link to it from a session's content |
| Empty `url` cell                   | No source                             | Fill it in, or drop the row         |

These are reported individually, with the reason, before anything runs. A row that fails during import — a permissions problem, a corrupt file — does not stop the rest: the batch continues and the report records what happened to each row. Nothing is half-created; a post whose import errored is reverted to draft.

Re-uploading a corrected CSV is safe. Rows already imported with the same file and settings are reported as skipped rather than duplicated.

### Content that already exists

This site has LearnDash **shared course steps** enabled, so one session can belong to several courses. When a row's title matches a session that already exists — commonly a shared one like "Introduction to Git", which several bootcamps teach — the importer does not create a second copy. It adds the existing session to this course as a shared step and reports the row as **Reused**, naming the other courses it belongs to. Nothing is overwritten and nothing is duplicated, and the session keeps whatever content it already had.

To replace that content rather than reuse it, enable Overwrite — but note that with shared steps the change is visible in every course holding the session, not only this one.

## Background jobs

Work happens on WP-Cron, not in the request that queues it.

| Hook                 | Schedule          | Does                                             |
| -------------------- | ----------------- | ------------------------------------------------ |
| `cbf_si_process_job` | one-off per phase | download, parse, and — once triggered — import   |
| `cbf_si_cleanup`     | hourly            | resets stale jobs and purges orphaned temp files |

Source files and extracted images live in `wp-content/uploads/cbf-slides-tmp/job_<id>/` and are deleted when the import finishes. A job stuck in an in-flight status for more than 30 minutes is reset to `pending` for retry; temp directories older than 2 hours are removed. The 30-minute reset deliberately comes first, so a retried job can still find its files.

Sites that set `DISABLE_WP_CRON` need a system cron hitting `wp-cron.php`, or jobs never leave `pending`.

## REST API

Namespace `cbf-si/v1`. Every route requires the `cbf_slides_import` capability except `/auth/callback`, which Google redirects to and which validates the OAuth state parameter instead.

| Method                 | Route                                           | Purpose                                                             |
| ---------------------- | ----------------------------------------------- | ------------------------------------------------------------------- |
| `GET`                  | `/auth/begin`, `/auth/callback`, `/auth/status` | Google OAuth; `POST /auth/revoke` drops the stored token            |
| `GET`                  | `/drive/picker-config`                          | access token, folder ID and the MIME types the picker should offer  |
| `GET`, `POST`          | `/jobs`                                         | list jobs, or queue one from a Drive file                           |
| `POST`                 | `/jobs/upload`                                  | queue one from a `multipart/form-data` upload                       |
| `GET`                  | `/jobs/{id}`                                    | poll status                                                         |
| `GET`                  | `/jobs/{id}/slides`                             | per-unit metadata and stored overrides, plus the format's unit noun |
| `GET`, `POST`          | `/jobs/{id}/preview`                            | rendered preview; `POST` saves settings first and re-renders        |
| `POST`                 | `/jobs/{id}/import`                             | run the import phase                                                |
| `POST`                 | `/jobs/{id}/cancel`                             | cancel a job still `pending`                                        |
| `GET`, `POST`          | `/configs`                                      | list or create saved per-deck configurations                        |
| `GET`, `PUT`, `DELETE` | `/configs/{id}`                                 | read, update or remove one                                          |
| `GET`, `POST`          | `/batches`                                      | list bulk migrations, or upload and validate a CSV                  |
| `GET`                  | `/batches/{id}`                                 | one batch, with its plan and live report                            |
| `POST`                 | `/batches/{id}/run`                             | confirm the plan and start importing                                |
| `GET`                  | `/batches/{id}/report`                          | per-row outcomes; `?format=csv` downloads them                      |
| `POST`                 | `/batches/{id}/cancel`                          | stop a running batch, keeping what it created                       |

Previews are cached in a per-user transient for an hour and busted whenever settings change.

## Code layout

```text
includes/
  Document/      format-neutral core
    Ir.php               the intermediate representation, and its constructors
    ParserFactory.php    the format table; dispatches on file extension
    BlockLayout.php      geometry → linear/column blocks
    SlideClassifier.php  cover/heading/hidden/body per unit
    BlockRenderer.php    IR → Gutenberg block HTML
  Pptx/          Parser, ShapeReader          (PhpPresentation)
  Pdf/           Parser, TextExtractor, ImageExtractor  (smalot/pdfparser)
  Docx/          Parser, Numbering            (PhpWord)
  Bulk/          CSV parsing, Drive URL resolution, batch planning and running
  Api/           REST controllers and the router
  Google/        OAuth and Drive clients
  Import/        JobRunner, PreviewRenderer, LearnDashImporter, Janitor
  Admin/         settings and importer screens
```

Only the three `Parser` classes and their helpers know about a document library. Everything under `Document/` works on arrays.

### Adding a format

1. Write a parser exposing `parse( string $path, string $img_dir ): array|WP_Error` that returns a `ParsedDeck`. The shape is documented in `Document\Ir`.
2. Add an entry to `ParserFactory::FORMATS` giving its extension, MIME type, label, unit noun and — if a Google editor exports to it — the export MIME type and direct export URL.
3. Add the extension to `SUPPORTED_FORMATS` and its MIME type to `SUPPORTED_MIME_TYPES` in `assets/js/admin/cbf-slides-importer.js`. These exist only so the file input's `accept` attribute can be rendered before any API call; the server remains the authority.

Nothing else needs to change. The REST layer, job runner, preview and importer all dispatch through `ParserFactory`.

## Development

```bash
composer install
composer test      # Codeception suite
composer test:unit # unit suite only
composer phpcs     # WordPress coding standards
composer phpcbf    # fix what can be fixed automatically
```

`lando codecept run` works from anywhere in the project.

### Tests

The `Unit` suite runs without WordPress: the parsing code touches only a handful of WordPress helpers, and those are stubbed in `tests/Support/wordpress-stubs.php`. Anything needing real WordPress behaviour — the REST controllers, `$wpdb` access, the LearnDash handoff — belongs in the integration suite rather than a larger stub.

Three small synthetic fixtures under `tests/_data/bin/` exercise the parsers; regenerate them with `php tests/_data/build-fixtures.php`.

Real decks are too large to commit, so the corpus test is opt-in. Drop documents into `tests/assets/` — gitignored, and the default location — or point `CBF_SI_FIXTURE_DIR` somewhere else:

| Where           | Example                                  | Notes                                            |
| --------------- | ---------------------------------------- | ------------------------------------------------ |
| `tests/assets/` | —                                        | the default; nothing to configure                |
| `.env`          | `CBF_SI_FIXTURE_DIR=tests/assets`        | copy `.env.example`; relative to the plugin root |
| Environment     | `CBF_SI_FIXTURE_DIR=/path composer test` | overrides `.env`, for one-off runs               |

That test asserts only what must hold for any input — no errors, no PHP diagnostics, escaped output — and skips when no corpus is found.

Two things to know if you add a suite. Codeception needs `register_argc_argv=On`, which the shared Lando `php.ini` turns off, so every entry point overrides it per-invocation. And Codeception 5 does not load `tests/_bootstrap.php` implicitly: without a bootstrap defining `ABSPATH`, the first plugin class autoloaded hits its `exit` guard and the run dies with no error and exit code 125.

The repository's [`phpcs.xml`](../../../../phpcs.xml) caps cyclomatic complexity at 6 and nesting at 3, which is why the parsers are built from many small methods.

Coding standards, commit conventions and local environment setup are covered in the repository's [CONTRIBUTING.md](../../../../CONTRIBUTING.md).

### Version metadata

Two sets of versions are duplicated across files and have to be changed together.

| What                      | Where                                                                                                                                             |
| ------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| Plugin version            | the `Version:` header and the `VERSION` constant, both in `cbf-slides-importer.php`                                                               |
| Minimum PHP and WordPress | the `Requires` headers in `cbf-slides-importer.php`, `Main::PLUGIN_REQUIREMENTS`, and `require.php` plus `config.platform.php` in `composer.json` |

## Troubleshooting

| Symptom                                           | Cause                                                                                                                                                                                                  |
| ------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Jobs stay at `pending`                            | WP-Cron is not running                                                                                                                                                                                 |
| "Google Drive is not connected" after connecting  | `CBF_SI_ENCRYPTION_KEY` changed or is unset, so the stored token cannot be decrypted                                                                                                                   |
| "No shared Drive folder is configured"            | the folder ID setting is empty                                                                                                                                                                         |
| Preview unavailable after a completed import      | expected — temp files are deleted once posts are created                                                                                                                                               |
| A PDF imports with words run together             | the PDF positions each glyph individually and omits space characters; there is no reliable signal to recover the spaces                                                                                |
| Everything in a PDF becomes one code block        | the page is set entirely in a monospaced font, and code detection has nothing to contrast against                                                                                                      |
| A bulk row says the session does not exist        | `session_id` must name a session already in the selected course — see Sessions before topics                                                                                                           |
| Bulk rows are reported as skipped                 | the same file was already imported with these settings; enable Overwrite to update instead                                                                                                             |
| A bulk row is reported as reused                  | a session with that title already existed, so it was added to this course as a shared step instead of being duplicated — see Content that already exists                                               |
| A bulk batch seems stuck                          | rows run one at a time; check WP-Cron is firing, and note that a stalled row is reset and retried after 30 minutes                                                                                     |
| A row fails saying the document needs more memory | the document is too large or too image-heavy for this server to parse; import it on its own, or split it up. Raise the ceiling with the `cbf_si_job_memory_limit` filter where the server has headroom |

Errors stored against a job have filesystem paths redacted. For full detail, enable `WP_DEBUG_LOG` and look for `[CBF-SI]` entries.
