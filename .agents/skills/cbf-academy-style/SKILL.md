---
name: cbf-academy-style
description: Use this skill whenever you write, edit, rewrite or review learning content for the CBF Academy LearnDash platform. That covers lessons, topics, shared guides, extended exercises, labs, quizzes and skills checks, including WordPress block markup pulled from a lesson post with `wp post get`. It applies the CBF Academy content style guide (British English, voice and tone, lesson skeleton, headings, code blocks, callouts, CBF Academy patterns, glossary terms, alt text, titles and the word list) and ends with a lint script and the review checklist. Use it even when the user only says "tidy up this lesson", "check this topic", "rename these lessons" or "write an exercise for the dbt module" without naming the style guide. For converting a slide deck into a lesson, use cbf-slides-migration as well. Not for slide design, marketing copy, or PHP, theme or plugin code.
metadata:
  author: cbf-academy
  version: "0.2"
  source: docs/academy-style-guide.md
---

# CBF Academy content style

The canonical standard is [references/academy-style-guide.md](references/academy-style-guide.md), a symlink to `docs/academy-style-guide.md`. This file distils what you need on every run. When the guide and this file disagree, the guide wins; tell the user so this skill can be updated.

The guide is long (about 1,100 lines). Do not read it whole. List its sections, then read only the one you need:

```bash
grep -n '^## \|^### ' references/academy-style-guide.md
```

| Read this section | When |
| --- | --- |
| 3. Who you are writing for | You don't know what the course level allows you to assume |
| 5. Language and spelling | Numbers, dates, units, abbreviations, capitalisation questions |
| 7. Course structure | Naming a title, splitting lesson vs topic, session length, skeletons |
| 9. Paragraphs, lists, procedures and tables | Writing a procedure or deciding list vs table |
| 10. Code | Writing or annotating code blocks |
| 11. Callouts | Choosing a callout type, or deciding whether something belongs in a callout |
| 12. Patterns | Using an activity pattern, a synced pattern or OS tabs, or spotting content repeated across lessons |
| 13. Images, screenshots and diagrams | Writing alt text or handling screenshots |
| 15. Exercises, labs and quizzes | Writing practice material or quiz questions |
| 16. Terminology and word list | Any product name or term you are unsure of, and "Glossary" for marking terms and writing entries |
| 18. Review checklist | Before handing work over (always) |
| Appendix: block markup reference | Producing any block you haven't produced this session |

## Before you start

You need four things. If the user hasn't given them, ask, or state your assumption at the top of your report:

1. The course level: beginner, intermediate or advanced.
2. The content type: lesson, shared guide, extended exercises, lab, or skills check.
3. The course formats it serves (short course, part-time or full-time bootcamp). This decides what belongs in the lesson and what goes in attached topics.
4. Reference material such as reviewer comments or a course outline. If the user hasn't mentioned any, ask whether there is any.

Content usually arrives as a `.txt` file of WordPress block markup exported from a post. Edit it in place and keep it as block markup. Output Markdown only when asked.

## Rules that agents get wrong

These are the rules most often broken by default writing habits. Apply all of them.

**Language**

- British spelling in prose: "optimise", "modelling", "labelled", "analyse", "colour", "behaviour", "catalogue". Code, identifiers, product names, quoted errors and program output keep their original spelling.
- No dashes as sentence punctuation. No em dashes, no en dashes, no spaced hyphens. Use a comma, full stop, colon (for a list or example) or brackets.
- Straight quotes only, never curly. Double quotes for quotations.
- Oxford comma in lists of three or more.
- No "simply", "just", "easy", "obvious", "of course", "trivial", "please", "in order to", "basically", "essentially", "utilise", "leverage", "via", "etc.", "see below", "see above".
- "For example" and "that is" in prose, not "e.g." and "i.e." (tables may use "e.g.").
- Numbers: zero to nine in words, 10 and up as numerals; always numerals with units, versions and percentages. Dates as "22 September 2026". Times as "14:00".
- Expand abbreviations on first use in each lesson, unless the course level already assumes them. A glossary entry never replaces the expansion.

**Voice**

- Address the learner as "you". CBF Academy is "we". Never "the student", "the trainee" or "the user" in body text.
- Give the reason before the instruction. Be honest about difficulty ("this trips most people up the first time").
- Contractions are fine. No emoji, no humour, at most one exclamation mark per lesson.

**Structure**

- The post title is the H1. Never put an H1 in the body. Sections are H2, subsections H3. H4 only when a subsection genuinely has parts, and then at least two of them.
- Sentence case for every heading, title, label, table header and button name.
- Never place two headings back to back; put at least one sentence under each.
- Headings describe the content ("Configure the connection", not "Configuration"). Avoid question headings, except one motivating "Why does X matter?" per lesson.
- Write in sentences and paragraphs. A bullet without a verb is a fragment: give it a verb or fold it into prose. Introduce every list and table with a sentence.
- One concept per H2, delivered as Describe, Demonstrate, Do.
- In a Columns block, a column holding text is never narrower than half the content width: 50/50 at most, with the text in the wider column of an uneven split. Never three text columns; use a table.

**Terms and emphasis**

- Define every new term in the sentence where it first appears. The prose definition always stays; the glossary backs it up.
- Mark the first mention of each key term the lesson or topic teaches as a glossary term (see Glossary terms below). Later mentions, and terms taught in other lessons, get no markup.
- A term that matters only in one place and doesn't merit a glossary entry gets `<em>` where it is defined.
- Reserve `<strong>` for genuine emphasis and lead-in labels ("Done when", callout labels, UI element names in procedures).
- Once a term has been explained, use it plainly. Do not add "as introduced earlier" or "from the previous topic" pointers to later mentions.

**Loose coupling**

- Never name, number or link another lesson, week or module. Module order and presence differ between courses. Describe prior knowledge generically ("basic SQL joins"). Within a module, "as we covered previously" is the most you may say.
- No "What's next" section. Pointing ahead couples the lesson to whatever follows it in one course. LearnDash navigation takes the learner to attached topics and the next step. Refer to an attached topic from the body, where the learner needs it.
- A shared guide never refers to a lesson, course or other topic. It says "this guide".

## Lesson skeleton

Every lesson follows this shape. Copy [assets/lesson-skeleton.html](assets/lesson-skeleton.html) for new lessons, or [assets/shared-guide-skeleton.html](assets/shared-guide-skeleton.html) for shared guides.

1. Introduction: one to three paragraphs, no heading. What, why, and what it builds on (generically).
2. **Learning objectives**: the Learning objectives pattern, whose locked H2 is followed by "By the end of this lesson, you will be able to:" and three to six bullets, each starting with an observable verb (explain, describe, write, run, compare, identify, diagnose, choose, design, build). Never "understand", "learn about" or "be familiar with". Advanced lessons need at least one analyse, evaluate or create verb.
3. **Prerequisites** (H2, optional): tools, accounts, generic knowledge.
4. Body: H2 sections.
5. **Summary** (H2): three to six bullets mirroring the objectives.
6. **Further reading** (H2, optional): three to five links, one sentence each, official docs first.
7. **Glossary**: added automatically to any lesson that marks glossary terms. Never write one by hand.

## Titles

Every title is `Anchor: Descriptor` in sentence case, under about 60 characters, with "and" not "&". The patterns you will meet most:

| Item | Pattern | Example |
| --- | --- | --- |
| Lesson in a series | Series N: Title (Roman numerals) | Spring Boot II: Building REST APIs |
| Standalone lesson | Title | Introduction to the command line |
| Extended exercises | Lesson anchor: Extended exercises n (Name) | Git and GitHub II: Extended exercises 1 (Remote repositories) |
| Lab | Lesson anchor: Lab n (Name) | SQL for BigQuery II: Lab 1 (New York City transit) |
| Skills check | Module: Skills check | Foundations: Skills check |
| Shared guide | Verb phrase, no anchor | Install Git |

Never put week or day numbers, dates, cohort or sponsor names, "Part 1", the course name, underscores or file-name artefacts in a title. Number exercises and labs only when the lesson has more than one. The slug is the title in kebab-case. For course titles and the full rules, read section 7 under "Naming".

## Block markup

Agents output WordPress block markup. The forms you need most:

- Code: `<!-- wp:code {"language":"bash"} -->`. Every block declares `language`, using `bash`, `powershell`, `sql` (also for dbt and Jinja), `python`, `java`, `javascript`, `typescript`, `html`, `css`, `json`, `yaml`, or `text` for output and logs. Add `"lineNumbers":true` for blocks over about five lines and `"title":"path/to/file"` when the code lives in a file.
- Commands and output go in separate blocks: a `bash` block with no `$` prompt, introduced with "Run:", then a `text` block introduced with "You should see:". Show a PowerShell variant where Windows differs.
- Annotated code: `"highlightLines":"2,5"` on the code block, inside a figure Group, then a Custom HTML block holding a `<figcaption>` with an ordered list with class `code-annotations` whose items carry `value="<line>"` (rendered "Line 2:"). Copy the appendix example. At most five annotations, each saying what the line does and why.
- Callouts: copy the callout pattern for the type from `web/app/themes/cbf-academy/patterns/callout-*.php`. Each is a `wp:group` with `cbf-callout` and a modifier (`cbf-callout--note`, `--tip`, `--warning`, `--try`, `--reflect`), holding one paragraph that opens with its bold label: `<strong>Tip.</strong> Run …`. Never add colour, icons or emoji; the theme draws them. To change type, use the other pattern rather than editing the label. No headings, code blocks or nested callouts inside. At most one per screen. Core teaching never goes in a callout. Warnings go before the risky step.
- Glossary terms: `<span class="glossary-ref" data-glossary-id="<id>">term</span>` around the first mention only. The full markup and the abbreviation override are in the appendix.
- Images: every content image has alt text describing what it tells the learner, under 125 characters, never starting "Image of" or "Screenshot of". Decorative images are deleted. Turn on Enlarge on click (`"lightbox":{"enabled":true}` in the `wp:image` attributes) unless the image is linked or is legible at its displayed size, such as an icon.
- Placeholders: `<kebab-case>` in angle brackets, explained on first use.

Copy exact markup from the appendix of the style guide when producing a block type for the first time.

## Patterns

Patterns are block groups for content that recurs across lessons. They carry class names only: the theme styles them, so never add colours, fonts, spacing or emoji. The sources are in the academy theme, and are the reference markup:

- **Activity patterns** (unsynced, inserted as a copy): Learning objectives, Demonstration, Exercise, Homework, Pop quiz and Recap, in `web/app/themes/cbf-academy/patterns/`. Keep the locked label paragraph (you may number it, as in "Exercise 2") and replace every placeholder in square brackets. Exercises end with a "Done when" line. Pop quiz answers go in its Show answers block. The Recap is for part-way through a long lesson; the closing Summary stays a section.
- **Callouts**: also theme patterns, covered under Block markup.
- **Synced patterns** (inserted as a reference): Learning checkpoint, Q&A and Break, sourced from `web/app/themes/cbf-academy/synced-patterns/`. Insert `<!-- wp:block {"ref":<id>} /-->`, looking up the ID on the environment you are editing because IDs can differ:

  ```bash
  ./vendor/bin/wp @dev-academy post list --post_type=wp_block --name=learning-checkpoint --field=ID
  ./vendor/bin/wp @dev-academy post list --post_type=wp_block --name=question-and-answer-break --field=ID
  ./vendor/bin/wp @dev-academy post list --post_type=wp_block --name=break --field=ID
  ```

  Never paste, detach or edit a copy. If you find a pasted copy (it contains "How are you feeling?" or a `cbf-activity--checkpoint`, `--questions` or `--break` group), replace it with the reference. Place checkpoints at the end of an H2 section, two or three per lesson at natural breaks. A break never states a duration or a time.
- **OS tabs** (unsynced): steps that differ by operating system, mainly in shared setup guides. Tabs are macOS, Windows and Ubuntu, in that order. Put shared steps outside the tabs, delete tabs that don't apply, and replace all placeholder text. Don't use tabs when only one operating system applies. Markup is in the style guide's appendix.

Don't add a pattern or a link pointing to extended exercises, labs or skills checks: they are attached to the lesson and the course navigation lists them.

When other content repeats across lessons, it may need a new pattern. Do not create patterns yourself. Propose them in your report and mark each occurrence with `<!-- REVIEW: candidate for a "<name>" pattern -->`. A complete reusable procedure with its own outcome is a shared guide, not a pattern.

## Glossary terms

The academy has one shared glossary (style guide section 16, "Glossary"). Marking a term links its first mention to the definition, lists it in the lesson's glossary, and adds it to the course glossary.

- Mark subject terms the learner must remember and will meet again, and abbreviations. Not everyday words, terms the course level assumes, or product names (unless the lesson explains the product).
- Mark a term only in a lesson that teaches it, at its first mention. A term the lesson only uses, because another lesson teaches it, stays plain: "commit" is marked in the lesson that introduces Git, not in every later lesson that mentions commits. If the lesson depends on such terms, state them generically under Prerequisites. Plurals and other forms can reference the base entry. For an abbreviation, mark the expanded form: "common table expression (CTE)".
- Use existing entries only. Find the ID on the environment you are editing:

  ```bash
  ./vendor/bin/wp @dev-academy glossary term list --search=<term> --fields=id,term,abbr,status
  ```

- If a key term has no entry, do not create one and do not guess an ID. Leave the term unmarked and add `<!-- REVIEW: glossary entry needed for "<term>": <proposed definition> -->` after the paragraph. Proposed definitions are one or two standalone sentences, about 40 words at most, that never mention a lesson, course or cohort.
- A reference to a draft entry shows as plain text until an editor publishes it. Report any you used.

## Agent rules

1. Do not change technical meaning. Restructure and rewrite prose freely, but keep code, commands and claims verbatim unless asked. If you believe something is wrong, keep it and flag it.
2. Do not invent facts, versions, links, datasets or examples the source lacks. If asked to add them, list which ones you added.
3. Flag anything uncertain with `<!-- REVIEW: what you were unsure about and what you did -->` in the markup.
4. Never mark content as reviewed. Review is a human step.
5. Never push content to a WordPress environment (`wp post update`) without the user's explicit go-ahead for that environment.
6. Never create, edit or publish glossary entries (`wp glossary term create`/`update`) unless the user asks. Propose them instead.

## Validate

Run the linter on every file you change, from the repository root:

```bash
python3 .agents/skills/cbf-academy-style/scripts/lint_content.py <file> [<file> ...]
```

It checks spelling, dashes, quotes, banned words, word-list offenders, headings, code block languages, prompts, alt text, callout labels and types, narrow text columns, pasted copies of synced patterns, leftover `[placeholder]` text, and glossary references (missing IDs and duplicates). It exits 1 when it finds errors. Fix every error. Treat warnings as prompts for judgement: some are false positives (for example "just" meaning "only", or American spelling inside a product name). Re-run until no errors remain.

The linter cannot judge teaching quality. After it passes, read section 18 of the style guide and work through the checklist yourself, especially the Teaching block: nothing used before it is taught, Describe, Demonstrate, Do for each concept, and the level matches the course.

## Report

End every task with this report, so the editor does not have to diff the file:

```markdown
**Assumptions:** level, content type, formats (only those you had to assume)

**Changed:**
- ...

**Removed:**
- ...

**Flagged for review:**
- <!-- REVIEW --> comments added, with a one-line summary each

**Patterns proposed:** repeated content that should become a pattern, and where it recurs (omit if none)

**Glossary entries proposed:** key terms with no entry, each with a proposed definition, plus any referenced entries still in draft (omit if none)

**Checklist items not met:** any section 18 items you could not satisfy, and why
```
