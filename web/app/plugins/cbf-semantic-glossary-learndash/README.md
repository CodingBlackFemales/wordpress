# CBF Semantic Glossary for LearnDash

A course-wide glossary for LearnDash courses. It lists the terms from the lessons and topics the current learner can open, says which lesson introduced each one, and lets the learner filter by lesson. Specified in TECH-900.

It builds on [CBF Semantic Glossary](../cbf-semantic-glossary/README.md), which keeps no LearnDash code. This plugin uses core's public API (`glossary_get_terms_for_posts()`) and render filters, so the course glossary gets core's markup, A–Z index and styling.

## Requirements

- PHP 8.5+, WordPress 6.7+
- CBF Semantic Glossary and LearnDash (verified against 5.2) active. If either is missing, the plugin shows an admin notice and does nothing else.
- Node (current LTS) to build the assets

After checkout, run `composer install` and `npm ci && npm run build:assets` in this directory. Neither `vendor/` nor `assets/` is committed; the deploy workflow builds `assets/` on the server.

## Course Glossary block and shortcode

The block is `cbf/course-glossary` (named like core's `cbf/glossary`). In the Classic editor, use `[course_glossary course="current" filter="true" index="true"]`.

| Setting | Block attribute | Shortcode | Default |
| --- | --- | --- | --- |
| Course | `course` (0 = current) | `course` (`current` or an ID) | The course the page belongs to |
| Heading text | `heading` | `heading` | Glossary |
| Heading level | `level` | `level` | 2 |
| Lesson filter | `showFilter` | `filter` | On |
| "Introduced in" line | `showSource` | `source` | On |
| Alternative terms | `showAlternatives` | `alternatives` | On |
| Back-links | `backLinks` | `backlinks` | On |
| A–Z index | `showIndex`, `indexMinEntries` | `index`, `index_min` | On, with core's minimum |
| Preview locked lessons in editor | `previewLocked` | n/a | On |

The layout is: the heading, an introduction, the lesson filter, the A–Z index, one flat `<dl>` sorted alphabetically, then a notice about locked content. Each entry has core's markup, plus:

- `data-lesson` and `data-letter` attributes, which the filter uses;
- a provenance `<dd>`: `<dd class="glossary-source">Introduced in <a href="…">How Does Git Work?</a></dd>`;
- core's ↩ back-link, pointing at the first reference in the lesson that introduces the entry (`…/how-does-git-work/#ref-branch`).

### Which lessons count

Steps (in `includes/Course/Steps.php`) is the only class that asks LearnDash anything:

- **Order** comes from the course's linear step list: each lesson, then its topics, then the next lesson. Drafts, and topics under unpublished lessons, are left out. Quizzes are not included.
- **Access** comes from `Step::is_content_visible()`, the check LearnDash's own lesson and topic templates use. It covers enrolment, sample lessons, drip schedules (a release date, or a number of days after enrolment, inherited from the lesson by its topics), linear progression, and the admin and group-leader bypass settings. It does not cover course prerequisites or points, which LearnDash enforces on the course page.

An entry is listed if any open step references it. Its "Introduced in" lesson is the first open step, in course order, that references it.

What this means in practice:

- **Learners without access** (not enrolled, logged out, or the course has ended) see only sample lessons' terms. If there are none, they see the heading and a notice saying how many terms the course introduces.
- **Admins and group leaders** see every lesson when LearnDash's "bypass course limits" setting is on for their role. Otherwise they see what an unenrolled user sees.

### Locked content

Locked lessons are left out of both the list and the filter, and their terms never appear in front-end markup, not even hidden. A closing notice gives the number of terms still to come and links to the first locked lesson that introduces one ("Continue to Branching").

In the editor, the preview has no learner to check. It lists every lesson, except those still held back by a drip schedule (a release date in the future, or a delay after enrolment). With **Preview locked lessons** on, those appear after the list as dashed placeholders, each with a lock badge, a term count and the terms it introduces. Linear progression unlocks lessons differently for each learner, so the preview does not try to reproduce it.

### Lesson filter

This is the [multi-select input pattern](https://uxpatterns.dev/patterns/forms/multi-select-input). The server renders the label, the combobox, the full option list and an `aria-live` status line. The script (`src/js/frontend/`) adds the chips and the keyboard handling: arrows to move, Enter to toggle, Escape to close, Backspace to remove the last chip. Filtering hides and shows the entries already on the page, and moves each A–Z index target to the first visible entry for that letter. It makes no requests.

The options are the open lessons and topics that introduce at least one entry, in course order. The filter is left out when there is only one to choose. Without JavaScript it stays hidden and the full list shows.

## Caching

Output is per learner. A page holding the block or shortcode is exempted from full-page caching, which is option (a) in the spec: it defines `DONOTCACHEPAGE` (honoured by WP Rocket and most page caches), fires LiteSpeed Cache's `litespeed_control_set_nocache`, and sends no-cache headers. This happens on `template_redirect` and again at render time, which catches glossaries placed by templates.

Course step order and the first-use map are kept in the object cache. They are cleared when a course, lesson, topic or entry is saved or deleted, when a course's structure changes, and whenever core re-indexes a post. After a bulk import, run `wp glossary course cache-flush`.

## "First used in"

The plugin filters `glossary_first_used_order`, so core's entry screen orders a course's lessons by course order rather than post date. "First used in" then names the lesson that introduces the entry, matching "Introduced in".

## WP-CLI

`wp glossary course …`, registered alongside core's `wp glossary` commands. Every listing takes `--format` and `--fields`, as core's do.

| Command | Does |
| --- | --- |
| `list` | Courses, with their step and entry counts |
| `terms <course> [--as-user=<id\|login\|email>] [--lesson=<id>...]` | The resolved glossary. With `--as-user`, that learner's enrolment, drip and progression apply; without it, the full set |
| `first-use <course> [--term=<id\|slug>]` | The step that introduces each entry, which "Introduced in" and "First used in" both use |
| `audit <course> [--strict]` | Entries used unmarked in an earlier lesson than the one that marks them (`introduced-late`); steps with references but no Glossary block (`no-glossary`); lessons and topics with references that belong to no course (`no-course`) |
| `render <course> [--as-user=…]` | The front-end HTML, for snapshot tests of the visibility logic |
| `cache-flush [<course>]` | Clear the cached step order and first-use maps |

`--as-user` also makes that learner the current user, because some LearnDash checks read the current user rather than the one they're given.

## Development

The same layout and tooling as core: `includes/` (PSR-4, `CodingBlackFemales\SemanticGlossaryLearnDash`), `src/` compiled by `@wordpress/scripts` into `assets/`, and the root PHPCS ruleset.

```bash
composer phpcs           # or, from the repo root: composer lint:all
npm run lint:js && npm run lint:css
```

### Tests

The unit suite runs without WordPress or LearnDash. It covers what is listed or locked, provenance, the course-order sort, the course audit, and the markup. It loads core's unit stubs and autoloader, so core must have had `composer install` run first.

```bash
composer test                                                            # this plugin
php -d register_argc_argv=1 vendor/bin/codecept run                      # from the repo root: every plugin
```

Reading courses from LearnDash is checked against a real site, using `wp glossary course terms` and `render` with `--as-user`.
