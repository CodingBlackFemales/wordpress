---
title: "Migration playbook: slides to documents"
version: 0.1
status: draft for team review
updated: 2026-09-22
related:
  - "[CBF Academy content style guide](academy-style-guide.md)"
---

# Migration playbook: slides to documents

This playbook is for anyone converting a slide deck into a lesson on the CBF Academy platform. It assumes you have read the [content style guide](academy-style-guide.md), which sets the standard the finished lesson must meet. The playbook covers the process of getting there.

The goal of migration is a document that teaches on its own. A slide deck was written to be presented; the presenter carried the reasoning, the connections and the examples. The document has to carry them instead. Reproducing the slides in order, as paragraphs, does not complete the migration.

## Before you start

1. Read the whole deck. If you have the speaker notes, read them too, since they often hold the reasoning the slides leave out.
2. Read any reference material supplied with the deck, such as reviewer comments, author notes or a course outline. If none has been supplied, ask the content editor whether there is any. Reviewers most often ask about: too much text per slide, missing visual aids, a concept used before it is taught, and inconsistent product names.
3. Classify each slide: cover, section header, content, code, exercise, activity, recap, questions, references. The importer does part of this; check its choices.

## Passes

Work through the draft in this order. Doing everything at once produces inconsistent results. If an AI agent is doing the work, ask for one pass at a time: "Do the structure pass" gets a better result than "migrate this deck".

**1. Structure pass.**

- Delete the cover, closing, "any questions?" and contact slides. The importer removes the cover; check the rest.
- The importer turns slide titles into H2 headings and attempts to strip duplicate headings from sequential slides. That is a starting point, not a structure. Choose the H2s from section headers and content groupings, and merge consecutive slides on the same point.
- Decide what belongs in the lesson and what belongs in attached topics by course type, not by size. The lesson holds what a part-time bootcamp or short course session delivers, including its inline exercises. Material that only a full-time bootcamp uses, such as afternoon exercise sets and labs, becomes extended exercise or lab topics attached to the lesson.
- Replace confidence-poll slides ("How are you feeling?", red, yellow and green ratings) with the synced [Learning checkpoint pattern](academy-style-guide.md#12-patterns) at the end of the H2 section they follow. Note any other content repeated across decks, such as pop quizzes or question and answer slides, as candidates for a new pattern.
- Add the [lesson skeleton](academy-style-guide.md#7-course-structure): introduction, learning objectives, summary. Remove any "next steps" or "next session" slide rather than turning it into a section. Objectives often already exist on an early slide; rewrite them with observable verbs.

**2. Prose pass.**

- Turn bullet fragments into sentences and paragraphs. Use the speaker notes, where you have them, and the reference material for the reasoning.
- Add the connective sentences between ideas.
- Define each term on first use and check the definition matches the course's level.
- Check that each H2 introduces one concept.
- Apply [voice and tone](academy-style-guide.md#4-voice-and-tone) and [spelling](academy-style-guide.md#5-language-and-spelling).

**3. Code pass.**

- Set a language on every block. Split commands from output. Remove prompt characters.
- Replace screenshots of code with code blocks. Retype if you must; then test.
- Add [annotations](academy-style-guide.md#10-code) for the lines the slide was pointing at with arrows or highlights.
- Run every example.

**4. Media pass.**

- Remove decorative images, logos, "any questions?" graphics and the slide template's furniture. Keep every image that carries content.
- Crop screenshots. Replace low-resolution captures.
- Write [alt text](academy-style-guide.md#13-images-screenshots-and-diagrams), and turn on [Enlarge on click](academy-style-guide.md#enlarge-on-click) for content images that are not linked.
- Redraw diagrams that were built from slide shapes as SVG where practical.

**5. Sequencing pass.**

- Check nothing is used before it is taught within the lesson, and within the module where the course structure or the reference material gives the order. Check the lesson does not depend on anything from another module, since module order and presence are not guaranteed across courses.
- Where a lesson uses a concept it has not taught, do one of three things: move the material to the lesson that teaches the concept, explain the concept in place with the minimum the learner needs ("for now, read this as a named subquery"), or rewrite the example without it. Do not point the learner to another lesson, week or module. The [loose-coupling rule](academy-style-guide.md#7-course-structure) in the style guide explains why.

**6. Consistency pass.**

- Apply the [word list](academy-style-guide.md#16-terminology-and-word-list). Search for the known offenders: "LookerML", "Javascript", "DBT", "VSCode", "master".
- Check callout labels, heading case and list punctuation.
- Check [patterns](academy-style-guide.md#12-patterns): OS-specific steps in OS tabs, learning checkpoints inserted as the synced pattern rather than pasted copies, and no pattern placeholder text left behind.

**7. Review.**

- Run the [review checklist](academy-style-guide.md#18-review-checklist).
- Update the course's record of migration progress: migrated status, link to migrated content, review status.
- Hand to a reviewer who has not worked on the draft.

## Slide element conversions

| Slide element | Becomes |
| --- | --- |
| Cover slide | Lesson title. Body text removed. |
| Section header slide | Topic boundary or H2. |
| Slide title | Usually merged into the H2 for the section, sometimes an H3. Rarely kept as its own heading. |
| Bullet list of fragments | Paragraph, or a list with full sentences. |
| Two-column comparison | Table. |
| Image with caption | Figure with alt text. Caption only if needed. |
| Screenshot of code | Code block, tested. |
| Diagram built from shapes | SVG diagram, or a redrawn image with alt text. |
| Highlighted line or arrow on code | Annotated code block. |
| "Activity" or "Your turn" slide | [Try it callout](academy-style-guide.md#11-callouts) if under five minutes, otherwise an exercise. |
| Speaker notes, where available | Body prose. |
| Recap or key takeaways slide | Summary section. |
| Confidence poll or "how are you feeling?" slide | Learning checkpoint pattern (synced). |
| Separate instructions per operating system | OS tabs pattern. |
| Content repeated across decks, such as a pop quiz or Q&A slide | An existing pattern, or a proposal for a new one. |
| "Any questions?" slide | Deleted. |
| References slide | Further reading, with a sentence per link. |
| Copyright and logo footers | Removed by the importer. Check none survived. |

## Renaming existing material

Existing titles were written before the [naming convention](academy-style-guide.md#7-course-structure) and mix several styles: "Part 1" suffixes, numbered prefixes from file names, session times, ampersands and title case. Rename each item as you migrate it. The examples below show the common cases.

| Current | Proposed |
| --- | --- |
| Introduction to Git & GitHub, Part 1 | Git and GitHub I: Introduction to Git |
| Introduction to Git & GitHub, Part 2 | Git and GitHub II: Introduction to GitHub |
| Introduction to Git: Supplementary Exercises | Git and GitHub I: Extended exercises (Local repositories) |
| Git_GitHub_Part2_Practice_Exercises_Part_2_CBF | Git and GitHub II: Extended exercises 1 (Remote repositories) |
| Afternoon_Git_GitHub_Netlify_Exercises | Git and GitHub II: Extended exercises 2 (Netlify) |
| Command Line, Git, GitHub & Cyber Security Skills Check | Foundations: Skills check |
| Engineering Practices 1: Programming Practices | Engineering practices I: Programming practices |
| 03 Lab 1: The New York City Transit Investigation | SQL for BigQuery II: Lab 1 (New York City transit) |
| 05b - Installation and setup | dbt fundamentals II: Setting up dbt Core |
| 07 Advanced Lab 3.pdf | Advanced dbt: Lab 3 (Macros) |
| Analytics Engineering (dbt) | Analytics engineering |
| Introduction to Spring Boot | Spring Boot I: Getting started |
| Building REST APIs with Spring Boot | Spring Boot II: Building REST APIs |
| Data Persistence in Spring Boot | Spring Boot III: Data persistence |
| From Java to TypeScript | TypeScript I: From Java to TypeScript |
| Core TypeScript Types | TypeScript II: Core types |
| Interfaces, Type Aliases, and Generics | TypeScript III: Interfaces, type aliases and generics |
| Functional Patterns and Narrowing | TypeScript IV: Functional patterns and narrowing |

Two situations need a decision rather than a rename:

- Subjects previously organised as topics under one lesson, such as Spring Boot and TypeScript, become lesson series. Each former topic becomes a lesson in the series, and the parent lesson is removed.
- Where a module has more than one skills check, merge them into one per module, or split the module. The convention allows one skills check per module.

## Text-heavy decks

Many of the decks flagged for rework have too much text per slide. Do not reproduce that density. A slide with eight bullets and a paragraph usually holds two or three distinct ideas. Give each its own paragraph or H3, cut what the presenter would have skipped, and move reference material to Further reading.
