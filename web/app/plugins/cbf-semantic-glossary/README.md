# CBF Semantic Glossary

Lets editors mark terms in course material and renders them as accessible, semantic HTML (`<dfn>`, `<abbr>`, `<dl>`), with a glossary for each post built from the terms it references. Specified in TECH-899.

The plugin knows nothing about LearnDash. Course-wide glossaries and learner visibility belong in the LearnDash companion (TECH-900), which builds on the [public API](#public-api).

## Requirements

- PHP 8.5+, WordPress 6.7+
- Node (current LTS) to build the editor assets

After checkout, run `composer install` and `npm ci && npm run build:assets` in this directory. Neither `vendor/` nor `assets/` is committed; the deploy workflow builds `assets/` on the server.

## Data model

**Glossary entry**: the `glossary_term` post type. Entries are global, shared by every post that references them.

| Field      | Stored as                         | Notes                                                                                   |
| ---------- | --------------------------------- | --------------------------------------------------------------------------------------- |
| Forms      | `_glossary_forms` meta            | Ordered `{ term, abbr }` pairs. The first is canonical; `abbr` is optional              |
| Definition | post content                      | Rich text: inline code, bold, italic, links, paragraphs and lists                       |
| Slug       | post slug                         | Fixes the anchor `#dfn-{slug}`. Renaming the term never changes it                      |
| Title      | post title                        | Copied from the canonical term, so list tables, search and revisions keep working       |

**Capabilities**: entries have their own capability type, so each role's rights over them are set independently of its rights over posts (for example with PublishPress Capabilities). Administrators and Editors get every capability on activation.

| Capability | Lets a role… |
| --- | --- |
| `edit_glossary_terms` | create entries and edit their own; also needed to open the Glossary admin menu |
| `edit_others_glossary_terms` | edit other people's entries |
| `edit_published_glossary_terms` | edit published entries |
| `publish_glossary_terms` | publish entries; without it, new entries start as drafts |
| `delete_glossary_terms`, `delete_others_glossary_terms`, `delete_published_glossary_terms` | delete entries |
| `edit_private_glossary_terms`, `read_private_glossary_terms`, `delete_private_glossary_terms` | work with private entries |

Searching for entries and referencing them only needs the right to edit some kind of content. That includes roles that edit only LearnDash content (`edit_courses`) and never had `edit_posts`.

Entries created from the editor are published only for users with `publish_glossary_terms`; anyone else's start as drafts, the first stage of the editorial workflow. A reference to a draft (or otherwise unpublished) entry renders as plain text until it is published. The editor labels it "not published yet", and `wp glossary audit --fix` leaves it alone.

**Inline reference**: a rich-text format on the selected text, stored as `<span class="glossary-ref" data-glossary-id="42">text</span>`. Only the entry ID is stored, so editing an entry updates every post. "Render as abbreviation" adds `data-glossary-abbr="true"`.

**Per post**: two lists of entry IDs in post meta, exposed to the block editor as one REST field, `glossary`. `extra` holds entries listed in the glossary without an inline reference; `ignored` holds entries the sidebar should stop suggesting. It is a REST field rather than registered meta because meta only reaches REST on post types that support custom fields, and not all LearnDash post types do.

**Reference index**: the `{prefix}glossary_refs` table, rebuilt from a post's content whenever it is saved. Rendering never reads it. It answers the questions that would otherwise mean scanning every post: usage counts, "Used in N posts", "First used in", `glossary_get_terms_for_posts()` and the WP-CLI reports.

## In the editor

- **Glossary term** in the rich-text toolbar (<kbd>⌘</kbd><kbd>⇧</kbd><kbd>G</kbd>) opens a popover with three states:
  - **Search** covers every term and abbreviation, prefilled with the selected text, and shows usage counts. "Create new term" is pinned at the bottom for Editors.
  - **Create** has Term and Abbreviation fields, alternative terms and a definition. It also says how the selected text will render.
  - **Existing reference**: shown when you click a marked term. It shows the entry, whether this is the first mention, and a "Render as abbreviation" override, with Edit entry, Change term and Unmark actions.
- Marked text has a dotted underline and a tint, so it is distinct from links.
- The **Glossary** sidebar has three panels:
  - **Needs attention** lists duplicate references (fixed with "Unmark later mentions", in document order), references to deleted entries, and known terms that appear but aren't marked ("Mark first mention", "Ignore in this post").
  - **In this post** lists terms in document order with jump-to links, plus terms listed without an inline reference.
  - The **footer** shows whether the post has a Glossary block.
- The **Glossary block** previews the post's glossary. Only its heading is editable in place. Its settings are heading text, heading level (H2–H4), alternative terms, back-links, the A–Z index and the minimum number of entries before the index appears. Leave the minimum unset to use the site default.

The popover's definition field takes a small Markdown subset (`code`, `**bold**`, `*italic*`, `[links](url)`); the server converts it. The full definition editor is on the entry's own screen (Glossary › All Terms).

## Rendering

References and the glossary are rendered on `the_content` at priority 20, after blocks and shortcodes, so "first mention" is measured on the final HTML in document order.

- Only the first reference to an entry links; later ones render as plain text. The sidebar and `wp glossary audit` report them; nothing strips them silently.
- Text matching one of the entry's abbreviations (case-insensitively), or with the override set, renders as `<a href="#dfn-{slug}"><abbr title="{term}">t</abbr></a>`. The `title` is the term on that abbreviation's own row. Anything else renders as `<a href="#dfn-{slug}">t</a>`. The first reference carries `id="ref-{slug}"`.
- A reference to a deleted or unpublished entry renders as plain text, never as a dead link.
- The glossary is a `<section aria-labelledby>` holding the heading, the A–Z index and a `<dl class="glossary">`:
  - It has one `<div>` per entry, and several `<dt>` elements share one `<dd>`.
  - The `<dfn>`, the anchor and the back-link go on the primary `<dt>` only, and the abbreviation leads when there is one.
  - Entries are sorted by displayed term, so "VCS" sorts under V.
  - "Also:" and the separators come from CSS.
- Back-links (↩) follow the primary term and are rendered only when the glossary sits in the same post as the references.
- The A–Z index lists only letters that have entries, and groups non-letter initials under `#`. It is omitted below a minimum number of entries: **Glossary › Settings › A–Z index** sets the site default (8), used by automatic glossaries, the shortcode and any Glossary block that doesn't set its own.
- With **Glossary › Settings › Automatic glossary** on (the default), a post that references terms but has no Glossary block gets one appended. Classic-editor content can use `[glossary heading="Glossary" level="3"]`, which also accepts `alternatives`, `backlinks` and `index` (yes/no) and `index_min` (a number).

### Styling

The stylesheet is enqueued as `cbf-semantic-glossary`, so themes can dequeue it. It uses single-class selectors (anything more specific is wrapped in `:where()`), no IDs and no `!important`. Themes can set these custom properties:

| Property                         | Default                                |
| -------------------------------- | -------------------------------------- |
| `--glossary-ref-color`           | `inherit`                              |
| `--glossary-ref-background`      | `rgba(56, 88, 233, 0.08)` (light tint) |
| `--glossary-ref-underline-color` | `#3858e9` (dotted)                     |
| `--glossary-alt-color`           | `#6b7280`                              |
| `--glossary-scroll-margin`       | `2rem`                                 |
| `--glossary-index-target`        | `32px` (`38px` on phones)              |
| `--glossary-index-background`    | `#eef2ff` (tile behind each index letter) |
| `--glossary-index-hover-background` | `#dbe3ff`                           |

References look as they do in the editor (body-coloured text, a tint and a dotted underline, solid on hover), so they read as glossary terms rather than ordinary links. Their link states are styled explicitly, so a theme's ordinary hover and visited colours don't apply. A theme or LMS plugin with more specific link resets (LearnDash removes `text-decoration` from links with a five-class selector) needs a matching rule in the theme. Set `--glossary-scroll-margin` too if the theme has a sticky header. The cbf-academy theme does both in `assets/css/custom.css`.

References, back-links and index letters get a visible focus outline even where a theme removes link outlines.

On touch screens (`@media (hover: none)`) the first mention of an abbreviation is spelled out after it, since `title` tooltips need hover.

## Public API

`glossary_get_terms_for_posts( array $post_ids ): array` returns the entries referenced across some posts. Each entry appears once, with the post where it first appears (posts are walked in the order given) and the form used there. Each item has the entry's fields (`id`, `slug`, `anchor`, `term`, `abbr`, `display`, `forms`, `definition`, `status`) plus `post_id`, `text`, `form` and `inline`.

| Hook                         | Type   | Use                                                                          |
| ---------------------------- | ------ | ---------------------------------------------------------------------------- |
| `glossary_reference_href`    | filter | Where a reference links to (default: `#dfn-{slug}` on the same page)         |
| `glossary_reference_html`    | filter | A rendered inline reference                                                  |
| `glossary_entries`           | filter | The entries a post's glossary lists, before ordering                         |
| `glossary_entry_html`        | filter | One entry's `<div>`, e.g. to add an "Introduced in…" link                    |
| `glossary_back_link_href`    | filter | Where an entry's ↩ back-link points (default: `#ref-{slug}` on the same page) |
| `glossary_html_before_index` | filter | Markup between the heading and the A–Z index (empty by default), e.g. an introduction or controls |
| `glossary_list_html`         | filter | The `<dl>`                                                                   |
| `glossary_html_after_list`   | filter | Markup after the `<dl>`, inside the section (empty by default)               |
| `glossary_html`              | filter | The whole glossary                                                           |
| `glossary_index_min_entries` | filter | The site default for entries needed before the A–Z index appears; a block's own minimum overrides it |
| `glossary_first_used_order`  | filter | Order of the posts using an entry; the first is "First used in"              |

The `context` array passed to the glossary filters includes `options`, `post_id` and `context_post_id`, the post being viewed.

## WP-CLI

Registered only when `WP_CLI` is defined. List and get commands take `--format=table|json|csv|yaml|ids` and `--fields`. Every mutating command takes `--dry-run`, and validation failures exit non-zero.

```bash
wp glossary term list [--search=<text>] [--unused] [--orphaned]
wp glossary term get <id|slug>
wp glossary term create --term=<text> [--abbr=<text>] [--definition=<markdown|html>] [--form=<term[:abbr]>]... [--slug=<slug>]
wp glossary term update <id|slug> [--term] [--abbr] [--definition] [--form=...]... [--add-form=...]... [--remove-form=<term>]...
wp glossary term delete <id|slug>... [--force]
wp glossary term import <file> [--update] [--format=json|csv]
wp glossary term export [--file=<path>] [--format=json|csv]

wp glossary refs list [--post=<id>] [--term=<id|slug>]
wp glossary refs reindex [--post=<id>|--post-type=<type>]

wp glossary audit [--post=<id>|--post-type=<type>] [--fix] [--strict]
wp glossary block ensure [--post-type=<type>]
wp glossary render <post-id> [--glossary-only]
```

Notes on behaviour:

- `audit` reports references to entries that aren't published yet as `unpublished`; they never fail the run and `--fix` leaves them in place.
- `term delete` refuses an entry that posts still reference, unless `--force`. Deletion is permanent, and references then render as plain text.
- `term import` matches on slug. CSV has the columns `slug,term,abbr,alternatives,definition`, with alternatives packed as `term:abbr|term`.
- `audit --fix` unmarks later duplicates and strips dead references. It never marks terms. It exits 1 while duplicate or dead references remain, and with `--strict` when unmarked terms are found too.
- `refs reindex` rebuilds the index, for content changed while the plugin was inactive. `term list --orphaned` reports index rows that point at posts which no longer exist.

## Development

```bash
composer install
npm ci
npm run build:assets   # or `npm run watch`
composer test          # Codeception unit suite
npm run lint:js
npm run lint:css
```

`composer test` runs from this directory with the root's Codeception. `composer test` in the project root runs every plugin listed in the root `codeception.yml`. Run `composer install` in the root first.

### Tests

The unit suite covers everything that doesn't need a database:

- the scanner and both renderers
- glossary ordering and the A–Z index
- the audit and the term matcher
- index rows, search, the import/export formats, the Markdown subset and the WP-CLI form options

That code works on `Entry` objects behind the `EntrySource` interface, and the suite feeds it in-memory entries (`tests/Support/Entries.php`). The few WordPress helpers it touches (escaping, translation, filters) are stubbed in `tests/Support/wordpress-stubs.php`. Filters are real enough to test the plugin's hooks.

Root-level runs load every plugin's stubs into one PHP process, and each stub is only defined if no other plugin defined it first. Keep stubs close to the real WordPress behaviour so that any plugin's version will do.

The parts that need WordPress (the repository, the index table, REST, the edit screen, WP-CLI) have no automated tests yet. `wp glossary render <post-id>` prints a post's final HTML, so these can be checked against a real site, and output can be diffed in CI.

The repository's [`phpcs.xml`](../../../../phpcs.xml) caps cyclomatic complexity at 6 and nesting at 3, which is why some methods are split further than they otherwise would be.

### Still to verify by hand

These are listed in TECH-899 as things to check during the build, and can't be settled by unit tests:

- **Screen readers.** Check how they announce `<dfn>` inside `<dt>`, several `<dt>` sharing one `<dd>`, and the CSS-generated "Also:". The touch expansion is already hidden from assistive technology with CSS alternative text (`content: … / ""`), because the `<abbr title>` already carries it.
- **Contrast.** By default reference text inherits the body colour, with a blue dotted underline on a light tint. Check the underline and tint against the course theme, and any colour a theme sets through the custom properties.
