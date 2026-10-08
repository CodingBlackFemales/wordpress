---
name: cbf-slides-migration
description: Use this skill when converting a CBF Academy slide deck (Google Slides or .pptx) into a LearnDash lesson, lab or extended exercise topic, or when reworking a lesson imported from slides that still reads like slides (bullet fragments, slide-title headings, missing reasoning, agenda timings, copyright footers, decorative images). It runs the migration playbook one pass at a time (structure, prose, code, media, sequencing, consistency, review), works from the imported post content and any reference material the user provides, retrieves course structure with WP-CLI, renames items to the naming convention and decides what stays in the lesson versus attached topics. Use it even when the user only says "migrate this deck", "do the structure pass", "clean up this imported lesson" or "rename these sessions". Use it together with cbf-academy-style, which sets the standard the finished lesson must meet. Not for running the import pipeline itself unless asked.
metadata:
  author: cbf-academy
  version: "0.1"
  source: docs/migration-playbook.md
---

# Slides to lesson migration

A deck was written to be presented: the presenter carried the reasoning, the connections and the examples. The migrated lesson has to carry them itself. Reproducing the slides in order as paragraphs is not a migration.

The canonical process is [references/migration-playbook.md](references/migration-playbook.md), a symlink to `docs/migration-playbook.md` (about 120 lines; read it in full on first use). The standard the result must meet is the CBF Academy style guide. Activate the `cbf-academy-style` skill alongside this one. If your client cannot, read `.agents/skills/cbf-academy-style/SKILL.md` before the prose pass.

## Inputs

Work from these sources:

1. The imported post content: block markup from `wp post get` (see [Retrieve content with WP-CLI](#retrieve-content-with-wp-cli)).
2. Reference material the user provides, such as a curriculum tracker export, reviewer comments, author notes or a course outline. Use whatever it holds about the lesson's position in the course, its review status and the changes reviewers asked for. If the user hasn't mentioned any, ask whether there is any before you start. If there is none, carry on without it.
3. The course level and the course formats the lesson serves (short course, part-time bootcamp, full-time bootcamp). Ask if neither the references nor the user say.

The imported content and the user's references are enough. Do not try to fetch the original decks from Google Drive, recover the importer's temporary files, or chase speaker notes. Where the slides leave the reasoning out and you can't supply it from the references or the surrounding content, write the best explanation you can and flag it with a REVIEW comment.

If the user does give you a local `.pptx`, `scripts/deck_outline.py` prints its slides with their speaker notes (run it with `.venv-pptx/bin/python`). It is optional.

## Retrieve content with WP-CLI

Run WP-CLI from the repository root. The local site's alias is `@dev-academy`; the import tooling is described in `scripts/README.md`.

**Choosing a runner.** Start with `./vendor/bin/wp @dev-academy`. Fall back to `lando wp @dev-academy` for the rest of the session if the host command fails because of the local MySQL setup. For example, `ERROR 2059 ... mysql_native_password cannot be loaded` comes from a Homebrew MySQL 9 client. It affects `wp db` commands, which call the host's `mysql` binary. Commands that run through PHP (`eval`, `eval-file`, `post get`, `post list`) usually work on the host. Every command prints warnings about the `--user` argument and Otter Blocks; ignore them.

**Course structure.** To see a course's modules, lessons, topics and quizzes in order, with post IDs, run:

```bash
./vendor/bin/wp @dev-academy eval-file .agents/skills/cbf-slides-migration/scripts/course_structure.php <course-id>
```

Use this, not `post list --meta_key=course_id`. The course shares lessons with other courses (Shared Course Steps), and the meta query misses the shared ones. You don't need the structure for every task; fetch it when you need to find a lesson's ID, its attached topics, or what comes before it for the sequencing pass.

**Lesson content.** Export a post to a local file, edit the file, and push it back only when the user says so:

```bash
./vendor/bin/wp @dev-academy post get <id> --field=post_content > "<Lesson title>.txt"
```

```bash
./vendor/bin/wp @dev-academy post update <id> - < "<Lesson title>.txt"
```

Never run `post update`, or an import, against any environment without the user's explicit go-ahead for that environment. For remote aliases, use the STDIN form shown: a positional file argument reads from the remote server.

## Passes

Work in passes, in this order. Doing everything at once produces inconsistent results.

- If the user names a pass, do only that pass.
- If the user asks for a whole migration, finish the structure pass and present the proposed outline (title, H2 list, what moves to topics, renames) for confirmation before writing prose. Structure decisions cause the most rework.
- Complete each pass over the whole document before starting the next.

Progress:

- [ ] 1. Structure
- [ ] 2. Prose
- [ ] 3. Code
- [ ] 4. Media
- [ ] 5. Sequencing
- [ ] 6. Consistency
- [ ] 7. Review

### 1. Structure

- Delete cover, closing, "any questions?", contact and agenda-timing slides.
- Replace confidence-poll slides with the synced Learning checkpoint pattern at the end of the H2 section they follow (see Patterns).
- Choose H2s from section headers and content groupings, not from slide titles. Merge consecutive slides on the same point. Turn importer H3/H4s that were only larger-font subtitles into prose.
- Decide lesson versus topics by course format, not by size. The lesson holds what a part-time bootcamp or short course session delivers, including its inline exercises. Material only a full-time bootcamp uses (afternoon exercise sets, labs) becomes extended exercise or lab topics attached to the lesson.
- Add the lesson skeleton: introduction, learning objectives, summary. Delete "next steps" or "next session" slides; do not turn them into a "What's next" section. Rewrite any objectives slide with observable verbs.
- Propose the new title using the naming convention (see Renaming).

### 2. Prose

- Turn bullet fragments into sentences and paragraphs. Supply the reasoning the presenter would have given, using the reviewer comments and notes in the user's references where they help. Presenter cues left in the slides, such as "ask for examples", become a **Reflect** or **Try it** callout, or are dropped.
- Add the connecting sentences between ideas. The learner has no presenter to bridge them.
- Define each term on first use, at the course's level, with `<em>` on the term.
- Check each H2 teaches one concept as Describe, Demonstrate, Do.
- Apply the style guide's voice, tone and spelling rules.

### 3. Code

- Set a language on every block. The importer turns all-monospace paragraphs into code blocks with no language.
- Split commands from output. Remove prompt characters.
- Replace screenshots of code with code blocks. Retype if you must, and flag retyped code for testing.
- Add annotations for the lines the slide pointed at with arrows or highlights.
- Do not fix code silently. If it looks wrong, keep it and add `<!-- REVIEW: ... -->`. You cannot run every example; list the ones that need testing in your report.

### 4. Media

- Remove decorative images, logos, "any questions?" graphics and template furniture. Keep every image that carries content.
- Write alt text for each kept image, and turn on Enlarge on click for content images that are not linked.
- Flag low-resolution or uncropped screenshots, and diagrams built from slide shapes that should be redrawn as SVG, with REVIEW comments. Do not attempt the redraw unless asked.

### 5. Sequencing

- Check nothing is used before it is taught, within the lesson and within the module. Get the module's lesson order from the course structure script, or from the user's references.
- Check nothing depends on another module. Module order and presence vary between courses.
- When the lesson uses a concept it has not taught, do one of three things: move the material to the lesson that teaches it, explain the minimum in place ("for now, read this as a named subquery"), or rewrite the example without it. Never point the learner to another lesson, week or module.

### 6. Consistency

- Apply the word list. Search for the known offenders: "LookerML", "Javascript", "DBT", "VSCode", "master", "trainee".
- Check callout labels, heading case and list punctuation.
- Check patterns: OS-specific steps in OS tabs, checkpoints as the synced reference, no placeholder text left.
- Run the linter from `cbf-academy-style` and fix every error:

```bash
python3 .agents/skills/cbf-academy-style/scripts/lint_content.py --type lesson "<Lesson title>.txt"
```

### 7. Review

- Work through the review checklist (style guide section 18) and list any item not met.
- Remind the user to update wherever the course's migration progress is tracked (migrated status, link, review status) and to hand the draft to a reviewer who has not worked on it. Never mark it as reviewed yourself.

## Slide element conversions

| Slide element | Becomes |
| --- | --- |
| Cover slide | Lesson title. Body text removed. |
| Section header slide | Topic boundary or H2. |
| Slide title | Usually merged into the section's H2, sometimes an H3. Rarely kept as its own heading. |
| Bullet list of fragments | Paragraph, or a list of full sentences. |
| Two-column comparison | Table. |
| Image with caption | Figure with alt text. Caption only if needed. |
| Screenshot of code | Code block, flagged for testing. |
| Diagram built from shapes | SVG diagram, or a redrawn image with alt text. |
| Highlighted line or arrow on code | Annotated code block. |
| "Activity" or "Your turn" slide | **Try it** callout if under five minutes, otherwise an exercise. |
| Speaker notes (if you have them) | Body prose. |
| Recap or key takeaways slide | Summary section. |
| Agenda or session outline slide | Introduction and learning objectives. Timings deleted. |
| Confidence poll or "how are you feeling?" slide | Learning checkpoint pattern (synced). |
| Separate instructions per operating system | OS tabs pattern. |
| Content repeated across decks (pop quiz, Q&A) | An existing pattern, or a proposal for a new one. |
| "Any questions?" slide | Deleted. |
| References slide | Further reading, with a sentence per link. |
| Copyright and logo footers | Deleted. |

## Renaming

Rename each item as you migrate it, following the naming rules in the style guide (section 7, "Naming"). The common cases:

| Current | Proposed |
| --- | --- |
| Introduction to Git & GitHub, Part 1 | Git and GitHub I: Introduction to Git |
| Introduction to Git: Supplementary Exercises | Git and GitHub I: Extended exercises (Local repositories) |
| Afternoon_Git_GitHub_Netlify_Exercises | Git and GitHub II: Extended exercises 2 (Netlify) |
| 03 Lab 1: The New York City Transit Investigation | SQL for BigQuery II: Lab 1 (New York City transit) |
| Core TypeScript Types | TypeScript II: Core types |

The playbook has the full table. Two cases need the user's decision rather than a rename, so raise them instead of acting:

- Subjects previously organised as topics under one lesson (Spring Boot, TypeScript) become a lesson series, and the parent lesson is removed.
- A module with more than one skills check must merge them or split the module.

## Patterns

The style guide's section 12 covers patterns in full. During migration:

- **Learning checkpoint** (synced): replaces confidence-poll slides. Insert it as a reference, never a copy. Look up its ID on the environment you are editing, since IDs can differ:

  ```bash
  ./vendor/bin/wp @dev-academy post list --post_type=wp_block --name=learning-checkpoint --field=ID
  ```

  Then insert `<!-- wp:block {"ref":<id>} /-->` at the end of the H2 section. Use two or three per lesson at natural breaks, not one per section.
- **OS tabs** (unsynced): use for OS-specific instructions, mostly in setup topics. Copy the markup from the style guide's appendix.
- **New patterns:** when the same content repeats across the decks you migrate (pop quizzes, question and answer breaks, break reminders), do not create a pattern yourself. List it under "Patterns proposed" in your report with where it recurs, and mark each place with `<!-- REVIEW: candidate for a "<name>" pattern -->`.

## Gotchas

These come from real imported decks:

- Copyright lines ("Copyright © 2022 Coding Black Females. All Rights Reserved. Do Not Redistribute.") and bare slide numbers on their own line survive some imports and most older plain-text exports. Delete them.
- Agenda timings ("18:40 - end of part 1 - 5 minute break") and session labels ("Unit 00 - Session 01") are facilitation details. Delete them; they also break loose coupling.
- Confidence polls ("I have no idea what you're talking about" / "I feel comfortable with everything you've said") are learning checkpoints. Replace each with the synced pattern. Some imported lessons hold pasted copies of the checkpoint (look for "How Are You Feeling?" in the markup); replace those with the synced reference too.
- Emoji used as file or folder icons (📂 `sales_fact.data`) should become a file listing in a `text` code block.
- The importer drops a slide's title from its body only when it would repeat the H2, and it skips slide 1 only. Duplicate headings across non-adjacent slides remain.
- Imported image files are named like `slide_003_img_01.png`. Flag them for renaming to descriptive kebab-case names.
- Text-heavy decks (many of those flagged for rework) hold two or three ideas per slide. Give each its own paragraph or H3, cut what a presenter would have skipped, and move reference material to Further reading. Do not reproduce the density.
- Decks say "trainees". Write "you" in body text, "learners" for the group.

## Report

After each pass, report in this shape:

```markdown
**Pass:** <name> (<n> of 7)

**Assumptions:** level, formats, position (only those you had to assume)

**Changed:**
- ...

**Removed:**
- ...

**Flagged for review:**
- one line per <!-- REVIEW --> comment

**Decisions needed:** renames, lesson/topic splits, series or skills-check changes

**Patterns proposed:** repeated content that should become a pattern, and where it recurs

**Next pass:** <name>, and anything it depends on
```
