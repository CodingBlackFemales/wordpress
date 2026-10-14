---
title: CBF Academy content style guide
version: 0.1
status: draft for team review
updated: 2026-10-13
open_decisions:
  - section: "[4. Voice and tone](#4-voice-and-tone)"
    question: Confirm the "professional and warm" register.
    recommendation: Confirm as written and revisit after the first two lessons written to this guide are reviewed.
  - section: "[7. Course structure](#7-course-structure)"
    question: Course naming as "Subject: Format" with a closed format vocabulary, cohorts as LearnDash groups, and a bracketed qualifier only for sponsored instances with different content. Also whether website marketing names must match platform titles.
    recommendation: Adopt as written. The platform can carry the systematic title while the website uses its own copy, if the website owner agrees.
  - section: "[7. Course structure](#7-course-structure)"
    question: One skills check per module, or one per lesson series where a module covers several subjects.
    recommendation: One per module, as defined. Confirm with the team.
  - section: "[10. Code](#10-code)"
    question: Use `sql` for all dbt code blocks, or `jinja2` where the highlighter supports it.
    recommendation: Check highlighter support, then decide.
  - section: "[15. Exercises, labs and quizzes](#15-exercises-labs-and-quizzes)"
    question: Where exercise solutions live. Separate document, a lesson step released after the session, or collapsible block.
    recommendation: Separate document, linked after the session.
  - section: "[16. Terminology and word list](#16-terminology-and-word-list)"
    question: '"Learner" or "trainee" platform-wide. Some existing decks and planning documents say "trainees".'
    recommendation: '"Learner", because it covers bootcamp and short-course participants.'
  - section: "[16. Terminology and word list](#glossary)"
    question: Where each course's Course Glossary block lives. On the course page, or on a dedicated glossary step.
    recommendation: On the course page, so learners can reach it from anywhere in the course.
  - section: "[1. Scope](#1-scope)"
    question: Where this guide lives long term. This repo, a Google Doc, or a page on the platform.
    recommendation: This repo as the source of truth, with a read-only copy on the platform for authors without repo access.
editorial_notes:
  - section: "[15. Exercises, labs and quizzes](#15-exercises-labs-and-quizzes)"
    note: Intentionally short. Expand once the lesson standard has settled.
  - section: whole guide
    note: The original Google Slides style deck (1LELPAkbRnIiAjeiYf-AgKzyFtRKy9MZjakvkcSdA1zQ) was not accessible when this draft was written. Reconcile it with this guide once shared.
---

# CBF Academy content style guide

This guide sets the standard for learning material on the CBF Academy platform. It is for anyone who writes, edits or reviews that material, including AI agents working under a human editor. Following it means a learner moving between courses, or between lessons by different authors, meets the same voice, structure and conventions throughout.

Read sections 2 to 6 before writing anything. Use sections 7 to 16 as a reference while you work, and run the checklist in section 18 before handing work over. Section 17 is for anyone directing an AI agent. Anyone converting slide decks into lessons should also read the [migration playbook](migration-playbook.md).

## Contents

- [CBF Academy content style guide](#cbf-academy-content-style-guide)
	- [Contents](#contents)
	- [1. Scope](#1-scope)
	- [2. Quick rules](#2-quick-rules)
	- [3. Who you are writing for](#3-who-you-are-writing-for)
	- [4. Voice and tone](#4-voice-and-tone)
	- [5. Language and spelling](#5-language-and-spelling)
		- [British English](#british-english)
		- [Punctuation](#punctuation)
		- [Capitalisation](#capitalisation)
		- [Numbers, dates and units](#numbers-dates-and-units)
		- [Abbreviations and acronyms](#abbreviations-and-acronyms)
		- [Words to avoid](#words-to-avoid)
	- [6. Inclusive and accessible writing](#6-inclusive-and-accessible-writing)
	- [7. Course structure](#7-course-structure)
		- [Terms](#terms)
		- [Approach](#approach)
		- [Naming](#naming)
		- [Session lengths](#session-lengths)
		- [Lesson skeleton](#lesson-skeleton)
		- [Shared guide skeleton](#shared-guide-skeleton)
		- [Pacing within a lesson](#pacing-within-a-lesson)
	- [8. Headings](#8-headings)
	- [9. Paragraphs, lists, procedures and tables](#9-paragraphs-lists-procedures-and-tables)
		- [Paragraphs](#paragraphs)
		- [Lists](#lists)
		- [Procedures](#procedures)
		- [Tables](#tables)
		- [Columns](#columns)
	- [10. Code](#10-code)
		- [Code blocks](#code-blocks)
		- [Commands and output](#commands-and-output)
		- [Inline code](#inline-code)
		- [Placeholders](#placeholders)
		- [Annotated code blocks](#annotated-code-blocks)
		- [Comments in code](#comments-in-code)
	- [11. Callouts](#11-callouts)
	- [12. Patterns](#12-patterns)
		- [Current patterns](#current-patterns)
		- [Creating a new pattern](#creating-a-new-pattern)
	- [13. Images, screenshots and diagrams](#13-images-screenshots-and-diagrams)
		- [When to use an image](#when-to-use-an-image)
		- [Alt text](#alt-text)
		- [Enlarge on click](#enlarge-on-click)
		- [Alignment](#alignment)
		- [Screenshots](#screenshots)
		- [Diagrams](#diagrams)
		- [Captions and file names](#captions-and-file-names)
	- [14. Links](#14-links)
	- [15. Exercises, labs and quizzes](#15-exercises-labs-and-quizzes)
	- [16. Terminology and word list](#16-terminology-and-word-list)
		- [Products and technologies](#products-and-technologies)
		- [General terms](#general-terms)
		- [Academy terms](#academy-terms)
		- [Glossary](#glossary)
	- [17. Working with AI agents](#17-working-with-ai-agents)
	- [18. Review checklist](#18-review-checklist)
	- [Appendix: block markup reference](#appendix-block-markup-reference)
- [Appendix: block markup reference](#appendix-block-markup-reference)

## 1. Scope

This guide covers lessons and topics on the academy platform, which runs on LearnDash in WordPress. It also covers exercises, labs and quizzes in less detail, since those attach to lessons and share their conventions. Slide design, brand identity, marketing copy and internal documents are out of scope.

The guide is deliberately short for a small team. It sets out the decisions that matter for consistency and leaves the rest to your judgement. When it does not cover a case:

1. Match what the surrounding lesson already does, so the learner sees one approach within a course.
2. Choose the option that is clearest for the course's declared level.
3. Tell the content editor, so the guide can be updated and the next author does not face the same question.
4. If unsure, consult with the CBF Academy team.

The guide changes when the team agrees a change, and the version in this repository is the current standard.

## 2. Quick rules

If you read nothing else, follow these.

1. Write to the learner as "you". CBF Academy is "we".
2. British English in prose. Code, commands, product names and quoted error messages keep their original spelling.
3. Professional, but warm. Do not use condescension, hype or jokes. Do use relatable examples and grounded encouragement.
4. Every lesson opens with an introduction and learning objectives, and closes with a summary.
5. The lesson title is the only H1. Sections are H2, subsections H3. Use H4 only when the content genuinely needs it.
6. Sentence case for every heading, label and title.
7. Write in sentences and paragraphs. A bullet without a verb is usually a fragment.
8. One idea per paragraph, one action per numbered step.
9. Every code block declares a language. Commands and their output go in separate blocks.
10. Every content image has alt text. Decorative images are removed, not given empty alt text.
11. Use the word list in section 16. For example, "LookML", "dbt", "GitHub" and "JavaScript" are the spellings to be used, consistently.
12. Define each new term in the sentence where it first appears, and mark that first mention as a glossary term (section 16).
13. Nothing is used before it is taught. If a lesson needs a concept it has not covered, explain it in place or move the material. Never point the learner to another module, and other lessons within a module only in generic terms.
14. Use the OS tabs pattern for steps that differ by operating system and the synced Learning checkpoint pattern for learner check-ins. Propose a new pattern for anything else repeated across lessons (section 12).
15. Run the review checklist in section 18 before handing work over.

## 3. Who you are writing for

Learners on the platform range from complete beginners on a bootcamp to senior staff on a short course. Every course targets a specific experience level, and you should write for that level, not for a general reader.

| Level | Assume | Do not assume |
| --- | --- | --- |
| Beginner | Competent use of a desktop computer and web browser. Ability to install software with clear instructions. | Any programming, command-line or data experience. Familiarity with technical jargon such as "repository" or "schema". |
| Intermediate | Completion of a beginner course or equivalent personal experience in a related discipline. Can write and run simple code or data queries. | Knowledge of the specific tools or languages the course introduces. Professional experience. |
| Advanced | Meaningful professional experience. Ability to read technical documentation unaided. | Familiarity with CBF's conventions, datasets or previous courses. |

Whatever the level:

- All academy learners are adults, often changing career and studying alongside work or caring responsibilities. Respect their time. Explain why something matters before describing how it works.
- Many cohorts are sponsored by an employer, and exercises may use that employer's data. Do not assume the learner is familiar with the sponsor's knowledge domain, unless the audience has been specified as such.
- Do not assume use of a specific operating system. Where commands and actions differ across Windows, macOS, and Linux, show both.
- For Linux commands and actions, treat the Debian lineage as default, unless otherwise specified in the course requirements.
- Do not assume a UK reader for anything other than spelling. Avoid idioms that depend on local knowledge.

## 4. Voice and tone

**Point of view.** Address the learner using the second-person POV, as "you" and "your". Reference CBF Academy staff and authors in the first-person plural POV, using "we", "our" and "us", as in "our recommendation" or "in this course, we use BigQuery". Do not refer to the reader in the third-person as "the student", "the trainee" or "the user" in lesson body text. "Learners" is fine when talking about the group, for example in a mentor briefing.

**Register.** Professional, but warm. In practice that means:

- Explain, do not lecture. Provide reasoning before instruction.
- Be honest about difficulty. "This trips most people up the first time" is more useful than pretending it is simple.
- Never call something easy, simple, obvious or trivial. If a learner is struggling, those words imply that they are the problem.
- Avoid opinionated language. Instead, provide the rationale for recommended best practises.
- Encourage without gushing. One "well done" at the end of a hard section is warm. One in every paragraph is noise.

**Tone mechanics.**

- Contractions are fine ("you'll", "it's", "don't"). They read as spoken and keep the tone more relatable.
- Humour should be avoided in order to prevent cultural or personal confusion.
- No emoji in lesson body text or headings.
- Exclamation marks in prose should generally be avoided, at most one per lesson.
- Avoid "please" in instructions. For example, "Run the command" not "Please run the command".
- Avoid filler such as "in order to", "it is important to note that", "basically", "essentially".

| Instead of | Write |
| --- | --- |
| Simply run the following command to easily install Git. | Run this command to install Git. |
| The student should now open their terminal. | Open your terminal. |
| Of course, you need to commit before you push. | Git can't push work you haven't committed, so commit first. |
| It is important to note that BigQuery charges per query. | BigQuery charges by the amount of data a query reads, so the habits in this section save money as well as time. |
| Data modelling has to be done right the first time, otherwise you're likely to run into problems when writing your queries. | Data modelling is a process that defines how your tables fit together. When your database is well-structured, every query becomes more straightforward. |

## 5. Language and spelling

### British English

Use British spelling in all prose: "modelling", "labelled", "optimise", "normalisation", "visualisation", "analyse", "colour", "centre", "catalogue", "licence" (noun), "practise" (verb), "programme" (a course of study) but "program" (software).

Do not change spelling inside:

- Code, identifiers, commands, file names and configuration keys (`color: red;`, `normalize()`, `--analyze`).
- Product, feature and organisation names, written as their owner writes them ("Google Cloud Storage", "Looker Studio", "Analytics Hub").
- Quoted error messages and program output. Paste them verbatim.
- Titles of external resources you link to.

### Punctuation

- Use the Oxford comma ("Git, GitHub, and Netlify"). It removes ambiguity in technical lists.
- Do not use dashes as sentence punctuation. Use a comma, a full stop or brackets. This also keeps agents from repeating the em dash habit.
- Use double quotation marks for quoted text and single quotation marks only for a quote within a quote. Always use straight quotes, never curly, so that anything pasted into a terminal or editor works.
- Full stops go outside quotation marks unless the quoted material is a full sentence.
- Colons introduce a list or an example. They do not join two clauses.
- No full stops in abbreviations: "UK", "SQL", "PhD".

### Capitalisation

- Sentence case for headings, table headers, captions, button labels and list items.
- Capitalise product names as the owner does. Some are lowercase by design ("dbt", "npm", "macOS"). If one would start a sentence, rewrite the sentence.
- Do not capitalise concepts for emphasis. "data warehouse", "common table expression", "pull request".

### Numbers, dates and units

- Spell out zero to nine, use numerals from 10. Always use numerals with units, versions, percentages, and in anything the learner types ("3 GB", "Python 3.12", "5%", "port 8080").
- Use a comma as the thousands separator in prose ("10,000 rows") and never inside code.
- Dates: 22 September 2026. Never a numeric-only date, since 03/04 means different things to different readers.
- Times: 24-hour clock, "14:00". Say the time zone when it matters ("14:00 UK time").
- Currency: "£1,200". Use GBP unless the example is specifically about another currency.

### Abbreviations and acronyms

Expand on first use in each lesson, then use the abbreviation: "common table expression (CTE)". A glossary entry shows an abbreviation's expansion on hover, but that never replaces expanding it in the text (section 16). Do not expand ones the level already assumes ("SQL" on an intermediate SQL course, "HTML" anywhere past week one). Write "for example" and "that is" in prose rather than "e.g." and "i.e.". In tables, "e.g." is acceptable to save space.

### Words to avoid

| Avoid | Because | Use |
| --- | --- | --- |
| simply, just, easy, obvious, of course | Belittles a learner who finds it hard | Delete, or say what makes it manageable |
| utilise, leverage | Fancy for "use" | use |
| in order to | Filler | to |
| via | Vague | using, through, by |
| etc. | Signals an unfinished thought | Finish the list or say "and others" |
| above, below (as in "see below") | Meaningless on a phone, and to screen readers | "the following", "earlier in this lesson", a link |
| master (branch, database) | See section 6 | main, primary |

## 6. Inclusive and accessible writing

CBF exists to widen who gets into tech. The material has to live up to that.

**People.**

- Use "they" for a hypothetical person. Never infer gender from a role or a name.
- Choose example names from a range of backgrounds, and vary who plays the expert in scenarios.
- Do not address a group as "guys".
- Use gender-neutral terms, e.g. "person-hours" vs "man-hours", "labour", "staff" or "workforce" vs "manpower", etc
- Do not assume prior education, employment history or income. "When you were at university" excludes people.
- Do not assume a fast machine, a large screen or a quiet room.

**Technical terms.** Use the current inclusive term, and where an older tool still uses the old one, say so once rather than hiding it.

| Use | Instead of |
| --- | --- |
| main branch | master branch |
| primary and replica | master and slave |
| allowlist and denylist | whitelist and blacklist |
| placeholder value, sample data | dummy value, dummy data |
| quick check, confidence check | sanity check |
| built-in, native | out of the box |

For example: "GitHub names the default branch `main`. Older projects and some tutorials still use `master`, which means the same thing."

**Accessibility.**

- Do not rely on colour alone to convey meaning. "The red line" means nothing to a colour-blind learner; say "the dashed line" or label it.
- Give every content image alt text (section 13).
- Do not describe position ("the button on the right"). Name the control.
- Use real lists and headings, not bold text pretending to be a heading, so screen readers and the table of contents both work.
- "Click", "select" and "open" are all fine. Use "select" for menu items and options, "click" for buttons and links.

**Plain English.**

- Keep sentences short, ideally one clause and under 25 words. If a sentence needs a second reading, split it.
- Define a term the first time it appears, in the sentence where it appears. If it is a key term, mark that mention as a glossary term (section 16).
- Prefer the concrete to the abstract. "A query that reads the whole table" beats "an inefficient access pattern".
- Analogies help beginners, but say where the analogy stops working.
- Avoid idioms and cultural references that need explaining ("hit the ground running", "a game of two halves").

## 7. Course structure

CBF Academy content is organised according to this hierarchy:
- A **course** is a learning programme offered by the academy
- A **module** is a collection of one or more lessons covering a single subject within a course, e.g. Spring Boot, React, containerisation, etc.
- A **lesson** is one teaching session, which can be anything from 2 hours for a short course up to half a day for a full-time bootcamp
- A **topic** is a content segment attached to a lesson. Topics hold either *shared guides*, which are reusable segments such as setup instructions, or the *extended exercises and labs* for the lesson. Topics cannot be added directly to a course; every topic has a host lesson
- An **exercise** is a practical task that states a goal and leaves the learner to complete it, often providing hints where needed and a reference solution for instructors to check against. Lessons may contain **inline exercises**, as the Do step for a concept, or **extended exercises** in attached topics, which hold further practice tasks for the session or afterwards. An exercise can be practical or reflective, and a set may share a working repository or dataset
- A **lab** is a guided practical that walks the learner step by step through a repository or dataset to build or run something real. It states its learning objectives, prerequisites and expected output, and ends with a checkpoint that confirms it worked. Where a lesson has several, they are numbered, each builds on the last, and starter and solution files are provided. The difference between a lab and an exercise is the level of guidance and verification
- A **skills check** is a quiz presented to learners at the end of a module to assess their learning. There is one per module

### Terms

LearnDash, the learning platform used by the Academy, provides a range of models to structure a corpus, including courses, lessons, topics, assignments and quizzes. They map to academy concepts as follows:

| Academy concept | LearnDash model |
| --- | --- |
| Course | Course |
| Module | Section heading |
| Lesson | Lesson |
| Shared guide | Topic |
| Extended exercises | Topic |
| Lab | Topic |
| Skills check | Quiz |

### Approach

Topics are used for two specific use cases:

1. Shared guides: independent segments that can be reused across lessons, such as the instructions for installing Git, VS Code or MySQL. These guides are required across multiple courses, but may be combined uniquely for each course.
2. Lesson practice: extended exercise sets and labs, which sit under the lesson as topics so that a teaching session and its practice travel together. These are typically used in full-time bootcamps, where afternoon sessions add extra practical tasks to lessons that part-time bootcamps and short courses deliver on their own. Lessons are generally written for the shorter formats, and the practise topics extend it for the longer one. LearnDash only allows a topic to sit under a lesson, never directly under a course, so a shared guide always needs a host lesson to be attached to. Teaching content itself never goes in a topic.

We use the [Shared Course Steps](https://docs.nexcess.com/software/learndash/shared-course-steps/) feature to enable lessons, topics and quizzes to be reused across multiple courses. This minimises content duplication and lowers maintenance effort.

To further enable effective maintenance, authors should implement **loosely-coupled content**. In practical terms, this means structuring modules to be independent of each other and avoiding references to other content, particularly across module boundaries. This is because the order or presence of any other module isn't guaranteed across separate courses. While references to other lessons within a module may sometimes be necessary, they should be implemented sparingly and in generic terms, e.g.

| Bad | Good |
| --- | --- |
| As we covered in Session 1, inheritance defines "is a" relationships, while composition defines "has a" relationships | As we covered previously, inheritance defines "is a" relationships, while composition defines "has a" relationships |
| We'll examine how to connect your API to a frontend interface later, in the React module | Connecting your API to a frontend is a subject for future investigation |

### Naming

Titles appear in course navigation, breadcrumbs, search results and the admin list of every item on the platform, and a shared item keeps its title in every course that uses it. Every title follows the same grammar, `Anchor: Descriptor`, and the anchor is dropped when the item stands alone.

| Item | Pattern | Example |
| --- | --- | --- |
| Course | Subject: Format | Data analytics: Full-time bootcamp |
| Supporting course | Subject: Purpose | Data analytics: System setup |
| Module | Subject | Data modelling |
| Standalone lesson | Title | Introduction to the command line |
| Lesson in a series | Series N: Title | Spring Boot II: Building REST APIs |
| Extended exercises | Lesson anchor: Extended exercises n (Name) | Introduction to the command line: Extended exercises 1 (Navigating files) |
| Lab | Lesson anchor: Lab n (Name) | Spring Boot II: Lab 1 (Order service) |
| Skills check | Module: Skills check | Foundations: Skills check |
| Project | Project name: Descriptor | Capstone project: Brief |
| Shared guide | Verb phrase | Install Git |

The lesson anchor is the series name and numeral for a lesson in a series, or the full title for a standalone lesson. Extended exercises and labs sit directly under their lesson in the course, so the short anchor is enough for learners, and it still identifies the item in the admin list. Number them only when the lesson has more than one of that kind, and always give the name in brackets.

Course titles put the subject first so that every format of the same subject sits together in the catalogue, the admin course list and the enrolment menu, and the format is what tells them apart. The format comes from a closed vocabulary: Short course, Part-time bootcamp, Full-time bootcamp. "Bootcamp" on its own is not allowed, since it does not say which one. A supporting course that is not a teaching format, such as a setup course or an induction, takes a purpose word in the same slot, agreed with the content editor. Cohorts are LearnDash groups enrolled on the course, not separate courses, so a cohort or sponsor never appears in the title. The one exception is a sponsored cohort that gets different content throughout, such as its own dataset. That needs its own course, and it takes a bracketed qualifier at the end: "Data analytics: Full-time bootcamp (Monzo 2026)".

Rules:

- Sentence case throughout, with product names capitalised as their owners write them. A title may start with a lowercase product name such as dbt.
- Roman numerals for lesson series and Arabic numerals for extended exercise sets and labs, so the two kinds of numbering never look alike. Number a series only when it has two or more lessons, keep a series to six parts at most, and renumber the whole series if a lesson is added or removed rather than adding "IIb".
- The series name is the subject as the learner knows it, not the module name. Part titles do not repeat it: "Spring Boot I: Getting started", not "Spring Boot I: Introduction to Spring Boot".
- Descriptors come from a fixed vocabulary: Extended exercises n, Lab n, Skills check, Brief, Kick-off. Agree any addition with the content editor.
- Never put in a title: week or day numbers, dates, cohort or sponsor names, "Part 1", the course name, or file-name artefacts such as underscores and version words. These belong in the timetable or the course settings, and any of them breaks when the item is reused. The only exception is the bracketed qualifier on a sponsored course instance described above.
- Write "and", not "&".
- Keep titles under about 60 characters so they survive breadcrumbs, menus and phone screens.
- Shared guides never have an anchor, since a guide must not know where it sits. A verb-first title such as "Install Git" identifies it without a label. Extended exercise and lab topics take the anchor of the lesson they belong to.
- The slug follows the title in kebab-case, numeral included: `spring-boot-ii-building-rest-apis`, `data-analytics-full-time-bootcamp`.

### Session lengths

Use these to judge how much material a lesson should hold.

| Format | Session length | Typical lesson |
| --- | --- | --- |
| Part-time bootcamp | 3 hours | 1 lesson, including 1-3 inline exercises |
| Full-time bootcamp | 6 hours, usually as two lessons | 2 lessons, each including 1-3 inline exercises, plus extended exercises and labs attached to them |
| Short course | 2 hours | 1 lesson, including 1-2 inline exercises |

2,500 to 3,000 words with code examples and an exercise should represent roughly 60 minutes of content. These are rough guidelines, not rules; the check is whether an instructor can deliver the lesson in the session with sufficient coverage of all the content and exercises, without rushing.

### Lesson skeleton

Every lesson follows this shape. The section names are the H2 headings unless noted.

1. **Title.** The LearnDash post title. It is the H1 and is not repeated in the body. Name what the learner will be able to do or what they will meet: "Introduction to Git", "Handling slowly changing dimensions".
2. **Introduction** (no heading; the first one to three paragraphs). What this lesson covers, why it matters, and how it builds on what the learner already knows. Describe that prior knowledge in generic terms, as set out under Approach, rather than naming or linking another lesson.
3. **Learning objectives.** The line "By the end of this lesson, you will be able to:" followed by three to six bullets. Each starts with an observable verb. "Understand", "learn about" and "be familiar with" are not observable; use "explain", "describe", "write", "run", "compare", "identify", "diagnose", "choose".
4. **Prerequisites** (optional). Tools to have installed, accounts to have, and knowledge the lesson assumes. State knowledge generically ("basic SQL joins"), not as a link to another lesson. Where a setup topic is attached to the lesson, link to it.
5. **Body.** A series of H2 sections holding the teaching. Topics are not part of the body: they are attached to the lesson as separate steps and referred to from the body where the learner needs them ("Before continuing, complete the Git setup guide attached to this lesson").
6. **Summary.** Three to six bullets restating what the learner can now do, mirroring the objectives. This is where a learner checks their own understanding, so keep it concrete.

7. **Further reading** (optional). Three to five links with a sentence each saying what the link is for. Prefer official documentation.
8. **Glossary** (automatic). Added at the end of any lesson that marks glossary terms (section 16). Do not write one by hand.

Do not end a lesson with a "What's next" section. Pointing ahead couples the lesson to whatever follows it, which differs between courses. LearnDash navigation already takes the learner to the attached topics and the next step in their course.

Objective verbs by level, adapted from Bloom's taxonomy:

| Level of thinking | Verbs |
| --- | --- |
| Remember, understand | define, describe, explain, identify, list, summarise |
| Apply | run, write, configure, use, demonstrate, calculate |
| Analyse, evaluate | compare, diagnose, distinguish, justify, choose, critique |
| Create | design, build, plan, refactor |

Beginner lessons lean on the first two rows. Advanced lessons should have at least one objective from the last two.

### Shared guide skeleton

A shared guide is written to be dropped into any lesson on any course, so it must be self-contained with no positional references.

1. **Title.** Names the outcome and stands alone: "Install Git", "Set up a BigQuery sandbox". Do not number it or prefix it with a course or lesson name.
2. **Purpose.** (no heading, one paragraph). What the learner will have or be able to do at the end, and roughly how long it takes.
3. **Requirements.** (optional). Operating systems covered, accounts needed, anything that must already be installed.
4. **Body.** H2 sections. Most guides are procedures, so this is usually one H2 per stage. Where the steps differ by operating system, put them in the OS tabs pattern (section 12) rather than writing an H2 per operating system.
5. **Validation.** How the learner confirms success, for example a command to run and the output to expect. Every guide ends here, so the learner knows they are done before returning to the lesson.

Rules for shared guides:

- Do not refer to the lesson, module or course the guide is in, or to any other topic. Write "this guide", not "this lesson".
- Do not assume what the learner has just done or is about to do. Requirements go in the requirements list, not in the prose.
- Do not include learning objectives, a summary or further reading. Those belong to the lesson.
- Keep a guide to one outcome. "Install Git and VS Code" is two guides.
- Make sure it works for every course that uses it before changing it. A change to a shared guide is a change to every lesson it is attached to.

### Pacing within a lesson

Deliver each concept in three steps, in this order. The mnemonic is **Describe, Demonstrate, Do**.

1. **Describe.** Explain the concept and its rational, before any syntax or mechanics. A learner who knows why a branch exists will remember how to create one.
2. **Demonstrate.** Show one worked example, with the code, the command or the steps and the result the learner should expect. Explain what the example does, not just what it says. Use the Demonstration pattern (section 12) where the example is a distinct step the instructor walks through.
3. **Do.** Give the learner something to do with the idea straight away, even if it is only "run this and look at the output". This can be a step in the body, a "Try it" callout or an inline exercise, depending on how much practice the concept needs.

Keep the cycle short. One concept per H2 section, and all three steps within it. If you find yourself describing three things before demonstrating any of them, split the section.

## 8. Headings

- The lesson or topic title is the only H1. Never write an H1 in the body.
- H2 for sections, H3 for subsections. Use H4 only when a subsection genuinely has parts of its own and splitting it would break the flow. Before adding one, check whether the section is too long and would be better split, or whether an H3 with a list does the job. If an H4 is needed, the section should have at least two of them.
- Sentence case. No full stop at the end. No numbering, except in a procedure where steps are headings ("Step 1: install the CLI").
- Make headings descriptive. "Configure the connection" tells the learner what is coming; "Configuration" does not.
- Keep headings unique within a lesson so the table of contents and links work.
- Never place two headings back to back. Put at least one sentence under each.
- Avoid questions as headings. "Why does normalisation matter?" is acceptable once per lesson as a motivating section; "What is a CTE?" should be "Common table expressions".

## 9. Paragraphs, lists, procedures and tables

### Paragraphs

- One idea per paragraph, two to five sentences.
- Lead with the concept. Put the reason or caveat after it.
- Connect paragraphs. If two paragraphs sit together with no logical link, add the sentence that joins them. The learner has no instructor in the room to do it for them.

### Lists

- Use a bulleted list for items of the same kind where order does not matter. Use a numbered list for sequences and ranked items.
- Introduce every list with a sentence ending in a colon, so the reader knows what the items are.
- Keep items parallel: all sentences, or all fragments, all starting with the same part of speech.
- Capitalise the first word. End with a full stop only if the item is a full sentence, and then do it for every item.
- Five to seven items is the comfortable maximum. Beyond that, group into sub-lists or a table.
- Nest at most one level.
- Do not use lists of fragments. A bullet without a verb is a fragment. Either give it a verb or fold it into a paragraph.

Fragments:

> - Version control
> - Track changes
> - Collaboration

Prose:

> Git is a version control system. It records every change you make to a project, so you can see who changed what and when, and return to an earlier state if something breaks. Because every collaborator has the full history, several people can work on the same project without overwriting each other.

### Procedures

- Number the steps. One action per step.
- Start each step with a verb: "Open", "Run", "Select", "Enter".
- Name interface elements in bold, exactly as they appear on screen: select **File > Save as**.
- Put anything the learner types in inline code or a code block.
- After a step with a visible result, say what the learner should see. This is how they know they are on track.
- Give the Windows and macOS variants where they differ, in that order. Where a whole procedure differs by operating system, use the OS tabs pattern instead (section 12).
- Keep procedures under about ten steps. Longer ones get an H3 per stage.

### Tables

- Use a table when comparing two or more things across two or more attributes. A list of items with one attribute each is a list, not a table.
- Always include a header row.
- Keep cells short. If a cell needs more than two sentences, the content belongs in prose.
- Introduce the table with a sentence saying what it compares.
- Use the **Stripes** style, set in the table block's **Styles** panel. Alternating row shading helps the reader follow a row across the table, especially on a phone. Keep the table's fixed width cells setting on.
- Do not use tables for layout.

### Columns

Columns put content side by side, such as an image beside the paragraph that explains it, or two short code blocks to compare. They stack on phones, so use them only where the side-by-side view helps on a wider screen.

- A column that holds text, such as paragraphs, lists or headings, is never narrower than half the content width. Narrow text columns break prose into short, ragged lines that are slow to read.
- Two columns at 50/50 are the most a text column can share. An uneven split, such as 60/40, puts the text in the wider column and the image or code in the narrower one.
- Never place three or more text columns side by side. Compare several things in a table instead.
- Leave **Stack on mobile** on.

## 10. Code

### Code blocks

- Every code block declares a language. Use the Code Highlighting panel in the editor. In block markup, the language is the `language` attribute in the block comment, as in `<!-- wp:code {"language":"sql"} -->` (see the appendix).
- Language names, as the platform's highlighter expects them:

| Language | Value |
| --- | --- |
| Java | `java` |
| Python | `python` |
| JavaScript | `javascript` |
| TypeScript | `typescript` |
| Shell commands | `bash` |
| PowerShell | `powershell` |
| SQL (including GoogleSQL for BigQuery) | `sql` |
| dbt models with Jinja | `sql` |
| HTML | `html` |
| CSS | `css` |
| JSON | `json` |
| YAML | `yaml` |
| Program output, logs, file listings | `text` |

- Turn line numbers on for any block longer than about five lines, and always for annotated blocks.
- Use the block's **Title** field for the file name when the code belongs in a file: `models/staging/stg_orders.sql`.
- Keep examples under about 30 lines. If the real code is longer, show the part that matters and link to the full file in the course repository.
- Code must run. Test it before publishing, including the imports and setup the learner needs. If a version matters, say which one.
- Follow the language's mainstream style: PEP 8 for Python, Google Java Style, Prettier defaults for JavaScript and TypeScript, uppercase keywords and lowercase identifiers for SQL.
- Do not use screenshots of code. Learners cannot copy from them or read them on a phone.

### Commands and output

- Commands go in a `bash` block with no prompt character. A leading `$` breaks copy and paste.
- One command per block where the learner is expected to copy it. Several commands in one block are fine when they are meant to be run together, one per line.
- Output goes in a separate `text` block. Introduce the pair with "Run:" and "You should see:" or similar.
- Paste output verbatim, including its spelling and any American English.
- Show a Windows variant where the command differs. PowerShell is the assumed Windows shell.

Run:

```bash
git status
```

You should see:

```text
On branch main
nothing to commit, working tree clean
```

### Inline code

Use inline code for anything that appears literally in code or on screen: identifiers, file names, commands, values, keys, table and column names, and anything the learner types. Do not use it for product names, general concepts or emphasis.

- Correct: "Open `settings.json` and set `theme` to `dark`."
- Correct: "Run `dbt run` from the project directory."
- Wrong: "Use `Git` to track your changes." (Git the product is a name, not code.)

### Placeholders

- Write placeholders in angle brackets and kebab-case: `<your-username>`, `<project-id>`, `<path-to-file>`.
- Say what to replace it with the first time it appears: "Replace `<project-id>` with the ID shown on your Google Cloud dashboard."
- Do not use `xxx`, `foo` or `bar` unless the point of the example is that the name does not matter, and say so.

### Annotated code blocks

Use this pattern when a learner needs to understand particular lines rather than the block as a whole. It pairs the highlighter's **Highlight lines** setting with an ordered list of annotations, styled by the theme's `code-annotations` class.

1. Insert the **Annotated code** pattern. It holds a code block and, below it, a Custom HTML block with the annotations, wrapped in a figure.
2. Paste the code, set its language and keep line numbers on.
3. In **Highlight lines**, list the lines to explain: `2,5-6`.
4. In the annotations, give each item a `value` matching a highlighted line, and the theme labels it "Line 2:". The editor's List block can't set an item's value, which is why the annotations are HTML.

Rules:

- Aim for five annotations or fewer per block. Beyond that, highlights stop standing out and learners lose their place moving between the code and the notes. Split the block into smaller examples, or move some of the explanation to prose. A longer list is fine when each note covers the same kind of line, such as one per data type.
- Keep each annotation to one or two sentences.
- Annotate what the line does and why it matters, not what it says. "Line 5: `ref()` tells dbt this model depends on `stg_orders`, so dbt builds them in the right order" is useful. "Line 5: calls ref" is not.

Markup is in the appendix.

### Comments in code

Comments are prose and follow the same rules as prose, including British spelling. Use them only where the reader cannot be looking at the annotation or the surrounding text, for example in a file the learner will download. Prefer annotations and prose in lessons.

## 11. Callouts

Insert callouts from the **CBF Academy** category in the **Patterns** tab of the block inserter. Each one is a group block with the class `cbf-callout` and a modifier for its label, holding one paragraph that opens with the bold label: "**Tip.** Run `git help add` to open the manual page for any command." The theme draws each callout's icon and colour from its modifier class, so the label keeps its purpose clear without relying on colour.

Use only these labels:

| Label | Pattern | Class | Use for |
| --- | --- | --- | --- |
| **Note** | Note callout | `cbf-callout--note` | Background or context the learner does not need to act on. |
| **Tip** | Tip callout | `cbf-callout--tip` | A shortcut, a habit or a tool that makes the task easier. |
| **Warning** | Warning callout | `cbf-callout--warning` | Something that will lose work, cost money or break an environment. |
| **Try it** | Try it callout | `cbf-callout--try` | A task under five minutes, done in place, with no marking. |
| **Reflect** | Reflect callout | `cbf-callout--reflect` | A question for the learner to think about or discuss with their cohort. |

Rules:

- At most one callout per screen of content. If every section has one, none stands out.
- Core teaching never goes in a callout. A learner who skims callouts should still be able to pass the lesson.
- Do not nest callouts or put headings or code blocks inside them. Short inline code is fine.
- Do not open a lesson or topic with a callout. Say what it is first.
- Warnings go before the step that can cause harm, not after.
- Never add colour, icons or emoji to a callout. To change its type, insert the pattern for the type you need rather than editing the label.

Markup is in the appendix.

## 12. Patterns

Patterns are saved groups of blocks in the editor's pattern library. Use them for content that appears in the same form across many lessons, so that learners see it consistently and editors maintain it in one place. Insert them from the **Patterns** tab of the block inserter.

There are two kinds:

- A **synced** pattern is a single shared copy. A lesson holds a reference to it, so editing the pattern updates every lesson that uses it. Use synced patterns for content that must be identical everywhere.
- An **unsynced** pattern inserts a copy that you then edit. Use unsynced patterns for a repeated structure whose content changes each time.

### Current patterns

The **CBF Academy** category holds the activity patterns below and the callouts in section 11. In courses, lessons, topics and quizzes, the inserter offers only these patterns; the page-building patterns from WordPress and plugins appear on other pages only.

| Pattern | Kind | Use for |
| --- | --- | --- |
| Learning objectives | Unsynced | The objectives that open every lesson (section 7). |
| Demonstration | Unsynced | A worked example: the Demonstrate step for a concept, with the code or steps and the result to expect. |
| Exercise | Unsynced | An inline exercise: the Do step for a concept. |
| Homework | Unsynced | Work for learners to do on their own after the session. |
| Pop quiz | Unsynced | Quick questions for the cohort, with the answers hidden until revealed. |
| Learning checkpoint | Synced | Letting instructors check how learners are feeling about the material so far. |
| Q&A | Synced | A pause for the cohort's questions in a live session. |
| Break | Synced | A break in a live session. The instructor decides how long, so the content never gives a duration or a time. |
| Recap | Unsynced | A short recap part-way through a long lesson. The closing summary stays a section (section 7). |
| Annotated code | Unsynced | A code block whose highlighted lines are explained in numbered notes (section 10). |
| OS tabs | Unsynced | Steps that differ by operating system, mainly in shared setup guides. |

**Activity patterns.** All the CBF Academy patterns carry class names only. The theme draws their icons, colours and type, so the design can change without editing a lesson.

- Never style a pattern by hand: no colours, fonts, spacing or emoji, and no Additional CSS from plugins.
- Keep the first line, the activity's label. It is locked so it can't be moved or deleted, but you can edit it, for example to number an exercise ("Exercise 2") when a lesson has more than one.
- Replace every placeholder in square brackets.
- Demonstrations, exercises, homework and pop quizzes have an H3 title, because they sit inside an H2 section and belong in the lesson's contents list. Checkpoints, breaks and Q&As use a styled paragraph instead, so they don't fill the contents list with repeated entries.
- Add blocks inside an activity as you need them, such as extra steps, code blocks or a **Tip** callout for hints.
- Don't add a pattern pointing to extended exercises, labs or skills checks. They're attached to the lesson, and the course navigation lists them automatically.
- Put pop quiz answers in its **Show answers** block, never in the questions.

**OS tabs.** The pattern holds one tab per operating system: macOS, Windows and Ubuntu, in that order.

- Use it where a procedure differs by operating system. Steps that are the same everywhere go before or after the tabs, not repeated in each one.
- Replace the placeholder text in every tab you keep.
- Delete any tab that does not apply. If only one operating system applies, do not use tabs; say so in the title or the requirements instead, as in "Install Homebrew (macOS only)".
- Each tab must make sense on its own, since the learner only reads their own. Follow the procedure rules in section 9 inside every tab.

**Learning checkpoint.** The pattern asks learners to rate how they feel about the section they have finished, on a red, yellow and green scale. The instructor uses the answers to decide whether to recap or move on.

- Place it at the end of an H2 section, after the learner has practised the concept. Use it at natural breaks in the lesson, typically two or three times, not after every section.
- Always insert the synced pattern. Never paste or detach a copy, since a copy no longer updates with the pattern and repeats markup in every lesson.
- Never edit the checkpoint's content from inside a lesson. Changes to it are changes to every lesson that uses it, so agree them with the content editor.

**Synced patterns.** The learning checkpoint, Q&A and break are synced. Their source lives in the academy theme's `synced-patterns/` folder, and every deployment runs `wp cbf patterns sync` on the academy site to create or update them. Change the source file, not the pattern in the editor, so every environment stays the same.

### Creating a new pattern

When the same content recurs across lessons, propose a new pattern rather than repeating it. A pattern is worth creating when the content or its structure appears in two or more lessons, or is expected to.

1. Check the pattern library first. Extend or reuse an existing pattern where you can.
2. Agree the new pattern with the content editor, as you would a new callout label.
3. Choose synced if the content is identical everywhere, as with a standard question and answer prompt. Choose unsynced if only the structure repeats, as with a pop quiz whose questions change.
4. Name it in sentence case with a noun phrase that says what it holds: "Pop quiz", "Learning checkpoint".
5. Write the content to this guide. A pattern's mistakes appear in every lesson that uses it.
6. Add it to the table of current patterns in this section, with when to use it.

A pattern is not a shared guide. A shared guide is a whole topic reused across courses (section 7); a pattern is a block group reused inside lessons and topics. If the repeated content is a complete procedure with its own outcome, such as installing a tool, it belongs in a shared guide.

Markup is in the appendix.

## 13. Images, screenshots and diagrams

### When to use an image

- Use a diagram for relationships, flows and architecture. A diagram of how dbt models depend on each other teaches more than a paragraph.
- Use a screenshot when the learner has to find something in an interface.
- Do not use an image for code, commands, tables or text. Use the right block.
- Do not use decorative imagery such as logos, stock photos and clip art. Every image should carry content.

### Alt text

- Every content image has alt text. Write what the image tells the learner, in one sentence, ideally under 125 characters.
- Do not start with "image of" or "screenshot of". Screen readers already announce that it is an image.
- For a screenshot, name the screen and what is highlighted: "BigQuery console with the Run button highlighted".
- For a diagram, summarise the relationship: "Three staging models feeding one orders mart model". Explain the detail in the body text, where every reader can get it.
- Decorative images are removed, so empty alt text should not be needed. If an image is genuinely decorative and must stay, give it empty alt text.

### Enlarge on click

Turn on **Enlarge on click** for content images that are not already linked. Screenshots and diagrams are often too small to read at the width of a lesson, especially on a phone. Enlarging lets the learner see the detail without leaving the page.

- Set it in the image block's toolbar under **Link**, or with `"lightbox":{"enabled":true}` in block markup.
- An image that links somewhere, such as to the tool it shows, keeps its link instead. The two options cannot be combined.
- You can leave it off for small images that are fully legible at their displayed size, such as an icon.

### Alignment

Centre every image that sits on its own in the lesson's single column: choose **Align centre** in the image block's toolbar, or set `"align":"center"` in block markup. A centred image reads as part of the text around it, whatever its width. An image inside a Columns block takes its place in the column, so leave its alignment unset.

### Screenshots

- Crop to the window or element that matters. A full-screen capture of a 27-inch monitor is unreadable on a phone.
- Capture at native resolution and never stretch. Keep the aspect ratio.
- Use the tool's light theme unless the course teaches a dark-themed tool.
- Highlight with one contrasting colour only, using a simple box or arrow. Do not annotate with text inside the image; put the text in the body where it can be read aloud and translated.
- Remove or blur personal data, account IDs, project IDs and anything that looks like a credential.
- Interfaces change. Prefer a screenshot of a stable area, and describe the goal in the text so the learner can still find it after a redesign.

### Diagrams

- Keep one diagram to one idea. Two small diagrams beat one crowded one.
- Label everything in the diagram in sentence case, and use the same names as the surrounding text and code.
- Label every group and every arrow, so no meaning depends on colour.

Draw diagrams in the house style, which uses the same design tokens as the lesson patterns, so a diagram looks like the activities and callouts around it:

- **Type:** Atkinson Hyperlegible Next for labels, and Atkinson Hyperlegible Mono for commands and code. At 1x, headings are 16px bold, labels 15px and code 14px.
- **Text and connectors:** ink (`#11181c`), with 2px connectors and solid arrowheads.
- **Boxes:** white, with a 1.5px outline in ink at 25% opacity and 8px corners, like an activity panel.
- **Groups:** borderless panels tinted with ink at 5%, like a callout, each with a heading.
- **Emphasis:** at most one yellow (`#ffda65`) box, for the item the diagram is about.
- **Background:** white, so the diagram reads in dark mode as a screenshot does.
- **Size:** no wider than 420px at 1x, so labels stay legible at phone width.

Make every diagram the same way:

1. Copy the diagram template (`.agents/skills/cbf-academy-style/assets/diagram-template.svg`), which holds the house style, and draw the diagram as SVG.
2. Save the source as `docs/diagrams/<lesson-slug>/<name>.svg`, with a descriptive kebab-case name. The source is what you edit when the diagram changes.
3. Export it as a lossless WebP at twice its size: run `.agents/skills/cbf-academy-style/scripts/export_diagram.sh docs/diagrams/<lesson-slug>/<name>.svg`. The script uses the bundled Atkinson Hyperlegible fonts and prints the 1x size.
4. Upload the WebP and insert it as an image block, centred, at its 1x width, with alt text and Enlarge on click.

Diagrams are images rather than inline SVG because learners can enlarge an image, and an inline SVG can't be zoomed. The 2x export keeps the image sharp on high-resolution screens.

Some older lessons draw entity-relationship diagrams as inline SVG (class `cbf-er`), in a palette that predates the visual identity. Redraw them in the house style when their lesson is migrated.

### Captions and file names

- Add a caption only if the image needs one to make sense. Sentence case, no "Figure 1" numbering unless the text refers to the figure by number.
- Name image files descriptively in kebab-case: `bigquery-console-run-button.png`, not `Screenshot 2026-09-22 at 10.14.03.png`.

## 14. Links

- Link text says where the link goes: "the dbt documentation on sources", never "here" or "this link".
- Link the first mention of an external tool or resource in a lesson, not every mention.
- Prefer official documentation over blog posts. If a blog post is the best source, say who wrote it and why it is worth reading.
- Link to a topic attached to the lesson when you refer to it. Do not link to other lessons or modules; section 7 explains why.
- Check every link before publishing. Prefer links that are unlikely to move, such as a documentation section rather than a search result.
- Do not bare-paste URLs into body text. In further reading lists, the link text is the resource title.

## 15. Exercises, labs and quizzes

**Exercises and labs.**

- Title extended exercise and lab topics as set out in the naming convention in section 7.
- Open with the context (what data, what tools, what they have just learned), then the task, then the expected outcome so the learner knows when they are done. Inline exercises use the Exercise pattern, whose "**Done when**" line holds the expected outcome.
- Name the dataset. If it is the sponsor's dataset, say how to get access.
- Hints go in a **Tip** callout. Solutions go in a separate document linked at the end of the session, never inline where they are visible before the attempt.
- Number sub-tasks and keep them independent where possible, so a learner who is stuck on one can continue.

**Quizzes and skills checks.**

- Write the question stem as a complete sentence or question.
- One clearly correct answer per multiple-choice question. Distractors should be plausible mistakes, not jokes.
- Do not use "all of the above" or "none of the above".
- Give feedback on every answer explaining why it is right or wrong. The wrong-answer feedback is where the learning happens.

## 16. Terminology and word list

Use these forms consistently. Add to the list as you go; a new entry needs an editor's agreement.

### Products and technologies

| Use | Notes |
| --- | --- |
| BigQuery | One word, capital Q. |
| GoogleSQL | The SQL dialect used by BigQuery. |
| dbt, dbt Core, dbt Cloud | Always lowercase "dbt", even at the start of a sentence. Rewrite the sentence if needed. |
| Git | Capital G for the tool; `git` in code. |
| GitHub | Capital H. |
| Google Cloud | Not "GCP" on beginner courses. Expand "Google Cloud Platform (GCP)" if the abbreviation is needed. |
| Jinja | Capital J. |
| JavaScript, TypeScript | Capital S and T. |
| LookML | Not "LookerML". Looker's modelling language. |
| Looker, Looker Studio | Two different products; say which one you mean. |
| macOS, Windows, Linux | As their owners write them. |
| MySQL, PostgreSQL | Not "Postgres" in headings; fine in body text after first use. |
| Node.js | With the dot. |
| npm | Lowercase. |
| Python | Capital P. |
| SQL | Pronounced either way; write "an SQL query". |
| VS Code | Two words, space, not "VSCode". |
| Slack, Zoom, ClearFeed | As written. |

### General terms

| Use | Instead of | Notes |
| --- | --- | --- |
| back end, front end (noun); back-end, front-end (adjective) | backend, frontend | "the front end"; "a front-end developer". |
| command line | command-line interface, CLI (on beginner courses) | The way of working. |
| terminal | console, shell (when you mean the app) | The application you type into. Use "shell" only when discussing bash or zsh specifically. |
| directory | folder | In command-line contexts. Use "folder" when describing a graphical file manager. |
| file name | filename | Two words. |
| dataset | data set | One word. |
| data warehouse, data pipeline | | Two words. |
| email | e-mail | |
| website, online, internet | web site, on-line, Internet | |
| set up (verb), setup (noun) | | "Set up the project" but "the setup takes five minutes". |
| log in (verb), login (noun) | sign in | Match the platform's own button label if it differs. |
| open source | open-source | No hyphen. |
| real time (noun), real-time (adjective) | | |
| pull request | PR (on beginner courses) | Expand on first use, then "PR" is fine. |
| ID, IDs | id, Id | |
| CI/CD | CI-CD | Expand on first use: "continuous integration and continuous delivery (CI/CD)". |
| ETL, ELT, CTE, SCD | | Expand on first use in each lesson. |

### Academy terms

| Use | Instead of | Notes |
| --- | --- | --- |
| learner | student, trainee | Covers bootcamp and short-course participants. |
| cohort | class, group | |
| mentor, supporter | | As used in the mentor briefing. |
| course, module, lesson, topic, shared guide, exercise, extended exercises, lab, skills check | unit, session (in platform content) | The academy's structural terms, defined in section 7. "Session" is fine when talking about the timetable. |
| bootcamp, short course | | Lowercase. |
| capstone project | final project | |
| skills check | quiz, test | "Quiz" is acceptable when naming the LearnDash block. |
| CBF Academy | the Academy, CBF | Full name on first use per lesson; "the academy" afterwards. |

### Glossary

The word list above settles how terms are spelled. The glossary settles what they mean. The academy keeps one shared glossary: each entry has a term, an optional abbreviation, any alternative names and a definition, and every lesson that teaches the term references the same entry. Marking a term in a lesson does three things: its first mention links to the definition, the lesson's glossary lists it, and the course glossary shows it once the learner reaches that lesson, labelled with the lesson that introduced it.

**What gets an entry.** A term the learner needs to remember and will meet again: concepts ("commit", "common table expression"), named techniques and abbreviations. Everyday words don't get one, and neither do terms the course level already assumes (section 5). Product names belong in the word list instead, unless the lesson explains what the product is.

**Marking terms.**

- Define every new term in the prose, in the sentence where it first appears (section 6). The glossary backs that definition up; it never replaces it, and a learner should never have to open it to follow the lesson.
- In the editor, select the term and choose **Glossary term** in the toolbar (<kbd>⌘</kbd><kbd>⇧</kbd><kbd>G</kbd>). The popover searches every entry's terms and abbreviations for the selected text. Pick the matching entry, or choose **Create new term** only when there is none.
- Mark only the first mention in each lesson or topic. Later mentions stay plain. The **Glossary** sidebar lists any duplicates under **Needs attention**, where **Unmark later mentions** fixes them.
- Mark a term only in the lessons that teach it, where it belongs to the lesson's subject. A lesson that only uses a term taught elsewhere leaves it plain: "commit" and "repository" are marked in the lesson that introduces Git, not in every later lesson that mentions them. Marking them again would repeat definitions the learner already has and pad every lesson's glossary.
- Treat the terms a lesson uses but doesn't teach as assumed knowledge. If the lesson depends on them, say so generically under **Prerequisites** (section 7), such as "basic Git: commits and branches". In the **Glossary** sidebar, choose **Ignore in this post** for each one so it stops being flagged as unmarked.
- Mark the term as it appears in the sentence. Plurals and other forms are fine: "branches" can reference the "Branch" entry. Add a genuine synonym ("repo" for "repository") to the existing entry as an alternative term rather than creating a second entry.
- For an abbreviation, follow section 5: write "common table expression (CTE)" and mark the full term. Mark the abbreviation on its own only where the course level lets you use it unexpanded.
- When the word appears in a different sense ("a branch of the business"), leave it unmarked and choose **Ignore in this post** in the sidebar, so it stops being suggested.
- If a term matters only in one place and doesn't merit an entry, introduce it in italics where it is defined. Use bold for emphasis and labels, never for terms.

**Writing an entry.** Create entries from the **Glossary term** popover, which has fields for the term, its abbreviation, alternative terms and the definition. For longer definitions, use the entry's own screen under **Glossary › All Terms**.

- **Term:** sentence case, singular and spelled as in the word list: "Common table expression", "Branch". Proper nouns, abbreviations and code keep their own case: `HEAD`, `.gitignore`. Get it right first time: the entry's link anchor comes from the term when it is created and never changes, even if the term is renamed.
- **Abbreviation:** only if the abbreviation is in common use ("CTE"). Text matching it renders as an abbreviation that expands on hover, and on touch screens its first mention is spelled out.
- **Alternative terms:** genuine synonyms and other spellings ("repo"), each with its own abbreviation if it has one. The glossary lists them under the main term.
- **Definition:** one or two plain sentences, about 40 words at most, that make sense on their own. Start with what it is, without repeating the term: "A snapshot of a repository at a point in time.", not "A commit is a snapshot…". The popover accepts `code`, `**bold**`, `*italic*` and `[links](url)`.
- **Shared by every course:** entries are reused like shared guides (section 7). Never mention a lesson, course, dataset or cohort in a definition.
- **Review:** if your role can't publish glossary entries, the entries you create start as drafts for an editor to review and publish. The editor labels a reference to one "not published yet", and learners see plain text until it is published, so check a lesson's entries are all published before it goes live.

**The glossaries learners see.**

- **Lesson glossary:** every lesson and topic that marks a term gets a glossary at the end, after the summary and further reading. It is added automatically; add a **Glossary** block only to place it somewhere else.
- **Course glossary:** the **Course Glossary** block lists the terms from every lesson the learner has unlocked, alphabetically, each linked to the lesson that introduced it. That is the first lesson, in course order, that marks the term. If an earlier lesson uses the term without marking it, the course audit (`wp glossary course audit`) reports it as `introduced-late`: the course uses the term before it teaches it. Fix it as you would any concept used before it is taught (section 7): reorder the lessons, explain the term where it is first used and mark it there, or rewrite that passage without it.

## 17. Working with AI agents

Agents draft and edit material under a human editor. They follow this guide like any other author, with these additional rules.

**Rules for agents**

1. This guide takes precedence over general writing habits, including the habit of American spelling and of using dashes as punctuation.
2. Do not change the technical meaning of source material. Restructure and rewrite freely; do not "correct" a claim, a command or a code sample without flagging it.
3. Do not invent facts, version numbers, links, dataset names or examples that the source does not contain, unless asked to and then say which ones you added.
4. Keep code verbatim unless asked to change it. If you believe code is wrong, keep it and flag it.
5. Flag anything uncertain as an editorial comment on the post, never in the content. Start the comment with "Agent review", name the section it refers to, and say what you were unsure about and what you did. Editorial comments keep review notes out of the lesson and show them to the whole editorial team (see "Review notes" in the appendix). Don't use HTML comments for this: the block editor wraps a comment between blocks in an empty paragraph when the post is saved.
6. Output WordPress block markup matching the appendix, not Markdown, unless asked otherwise.
7. Finish with a short list of what you changed, what you removed, what you flagged and any glossary entries you propose. The editor should not have to diff the whole document to find out.
8. Never mark a draft as reviewed. Review is a human step.
9. Mark the glossary terms a lesson teaches with the markup in the appendix, using the IDs of existing entries. Leave terms it only uses plain. Don't create entries: where a key term has none, leave it unmarked and propose an entry, with a definition, in an editorial comment and in your report.

**For humans directing agents**

- Give the agent the source, the course level, the lesson's position in the course and any reference material, such as reviewer comments. It cannot infer these.
- Ask for one kind of change at a time on large documents. "Fix the headings and structure" gets a better result than "edit this lesson".
- Read the agent's editorial comments on each post, which list everything it flagged, and resolve each one before the lesson goes live.
- An agent's draft goes through the same review checklist as a human's.

**Prompt preamble**

Paste this at the start of any content task:

> You are editing learning material for CBF Academy. Follow the CBF Academy content style guide at `docs/academy-style-guide.md`, in particular sections 4, 5, 7, 10, 12 and 17. Do not change technical meaning or invent facts. Flag uncertainties as editorial comments on the post, never in the content. This course is at [level]. This lesson comes after [previous lesson] and before [next lesson]. Reference material for the source, such as reviewer comments: [paste, or "none"]. Output WordPress block markup. End with a list of changes, removals and flags.

## 18. Review checklist

Copy this into the review and tick each line. A reviewer should be able to complete it in ten minutes for a topic and thirty for a lesson.

**Structure**

- [ ] Title is descriptive and in sentence case. No H1 in the body.
- [ ] Introduction says what, why and how it connects to the previous lesson.
- [ ] Learning objectives are present, three to six, each with an observable verb.
- [ ] Headings are H2 and H3, with H4 only where justified. Descriptive, unique, none back to back.
- [ ] Summary mirrors the objectives. No "What's next" section.
- [ ] Length fits the session, using the guideline in section 7.

**Language**

- [ ] Second person throughout. No "the student" or "the trainee" in body text.
- [ ] British spelling in prose. Code and product names untouched.
- [ ] No "simply", "just", "easy", "obviously". No filler.
- [ ] Sentences mostly under 25 words. One idea per paragraph.
- [ ] Terms defined on first use. Abbreviations expanded on first use.
- [ ] Key terms this lesson teaches are marked as glossary terms at their first mention. Terms it only uses are left plain and ignored in the Glossary sidebar, which shows nothing under Needs attention. No referenced entry is still a draft.
- [ ] Word list applied. Product names spelled as their owners spell them.
- [ ] Inclusive language. No gendered assumptions, no legacy technical terms without explanation.

**Code**

- [ ] Every block has a language. Line numbers on for blocks over five lines.
- [ ] Commands and output in separate blocks. No prompt characters.
- [ ] Every example runs. Versions stated where they matter.
- [ ] Placeholders in `<angle-brackets>` and explained.
- [ ] No screenshots of code.
- [ ] Each annotation matches a highlighted line. Blocks with more than five annotations have been considered for splitting.

**Media**

- [ ] Every content image has alt text describing what matters.
- [ ] No text column narrower than half the content width.
- [ ] Tables have a header row and use the Stripes style.
- [ ] Content images that are not linked have Enlarge on click turned on.
- [ ] Images outside Columns blocks are centred.
- [ ] Decorative images, logos and template furniture removed.
- [ ] Screenshots cropped, sharp, free of personal data.
- [ ] Diagrams use the same names as the text and code, follow the house style, and are 2x WebP images with Enlarge on click.

**Teaching**

- [ ] Nothing is used before it is taught, and nothing depends on another module.
- [ ] Each new concept follows Describe, Demonstrate, Do: explained, then shown in a worked example, then tried by the learner.
- [ ] Level matches the course's declared level.
- [ ] Callouts have a label from the approved list, at most one per screen, and hold no core content.

**Platform**

- [ ] Links work and have descriptive text.
- [ ] Renders correctly on a phone-width screen.
- [ ] OS-specific steps use the OS tabs pattern, with no placeholder text or unused tabs left.
- [ ] Activities and callouts use the CBF Academy patterns, with no placeholder text left and no styling added by hand.
- [ ] Learning checkpoints, Q&As and breaks are the synced patterns, not pasted copies. Content repeated across lessons is a pattern or has been proposed as one.

## Appendix: block markup reference

These are the block forms the platform uses and the theme styles. Agents should output these; authors using the editor get them automatically.

**Heading**

```html
<!-- wp:heading -->
<h2 class="wp-block-heading">Create a branch</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Switch between branches</h3>
<!-- /wp:heading -->
```

**Paragraph**

```html
<!-- wp:paragraph -->
<p>A branch is a separate line of work. Run <code>git branch</code> to list the ones you have.</p>
<!-- /wp:paragraph -->
```

**Lists**

```html
<!-- wp:list -->
<ul class="wp-block-list">
<!-- wp:list-item -->
<li>Every collaborator has the full history.</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li>Changes are recorded as commits.</li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->

<!-- wp:list {"ordered":true} -->
<ol class="wp-block-list">
<!-- wp:list-item -->
<li>Open your terminal.</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li>Run <code>git init</code>.</li>
<!-- /wp:list-item -->
</ol>
<!-- /wp:list -->
```

**Code block with language, title and line numbers**

The attributes come from the WebberZone Code Block Highlighting plugin (version 1.3 or later), which extends the core code block. The plugin keeps every option in the block comment, and the HTML is the core block's plain `<pre class="wp-block-code"><code>`, so deactivating the plugin leaves the block valid.

- Write the options in this order, leaving out any you don't use: `language`, `lineNumbers`, `lineNumbersStart`, `wordWrap`, `highlightLines`, `maxHeight`, `downloadButton`, `colorScheme`, `title`. That is the order the editor saves them in.
- Never add a language class, a `lang` attribute, `line-numbers`, `data-title` or `data-line` to the HTML. That was the markup of version 1.2 and earlier, which the editor converts when the post is next saved. To convert older posts in bulk, run `wp wzcbh migrate-code-blocks --dry-run` to see what would change, then without `--dry-run`.
- The editor writes `[` inside a code block as `&#91;`, so WordPress doesn't read it as a shortcode. Either form is valid in markup you write; the editor saves the escaped one.

```html
<!-- wp:code {"language":"python","lineNumbers":true,"title":"load_orders.py"} -->
<pre class="wp-block-code"><code>import csv

with open("orders.csv") as f:
    rows = list(csv.DictReader(f))

print(len(rows))</code></pre>
<!-- /wp:code -->
```

**Annotated code block**

```html
<!-- wp:group {"tagName":"figure","metadata":{"patternName":"cbf-academy/annotated-code"},"layout":{"type":"constrained"},"allowedBlocks":["core/code","core/html"]} -->
<figure class="wp-block-group"><!-- wp:code {"language":"sql","lineNumbers":true,"highlightLines":"2,5","title":"models/marts/orders.sql"} -->
<pre class="wp-block-code"><code>SELECT
    o.order_id,
    o.customer_id,
    o.order_date
FROM {{ ref('stg_orders') }} AS o
WHERE o.status = 'complete'</code></pre>
<!-- /wp:code -->

<!-- wp:html -->
<figcaption>
<ol class="code-annotations" role="list">
<li value="2">Every column is listed explicitly. <code>SELECT *</code> would make the model change shape whenever the source did.</li>
<li value="5"><code>ref()</code> tells dbt this model depends on <code>stg_orders</code>, so dbt builds them in the right order.</li>
</ol>
</figcaption>
<!-- /wp:html --></figure>
<!-- /wp:group -->
```

**Callout**

The modifier is one of `note`, `tip`, `warning`, `try` and `reflect`. Write `--` in the block comment's JSON as `\u002d\u002d`, as the editor does.

```html
<!-- wp:group {"className":"cbf-callout cbf-callout\u002d\u002dwarning"} -->
<div class="wp-block-group cbf-callout cbf-callout--warning"><!-- wp:paragraph -->
<p><strong>Warning.</strong> <code>git reset --hard</code> discards uncommitted changes and cannot be undone. Commit or stash first.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
```

**Activity (Exercise)**

Every activity has the same shape: a group with `cbf-activity` and a type modifier, then a locked label paragraph. The pattern files in the academy theme's `patterns/` folder are the reference for each type.

```html
<!-- wp:group {"tagName":"section","className":"cbf-activity cbf-activity\u002d\u002dexercise"} -->
<section class="wp-block-group cbf-activity cbf-activity--exercise"><!-- wp:paragraph {"className":"cbf-activity__label","lock":{"move":true,"remove":true}} -->
<p class="cbf-activity__label"><strong>Exercise</strong></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Add and delete files in a repository</h3>
<!-- /wp:heading -->

<!-- wp:list {"ordered":true} -->
<ol class="wp-block-list"><!-- wp:list-item -->
<li>Initialise a Git repository.</li>
<!-- /wp:list-item --><!-- wp:list-item -->
<li>Create a file, stage it and commit it.</li>
<!-- /wp:list-item --></ol>
<!-- /wp:list -->

<!-- wp:paragraph {"className":"cbf-activity__outcome"} -->
<p class="cbf-activity__outcome"><strong>Done when</strong> <code>git log --oneline</code> shows both commits.</p>
<!-- /wp:paragraph --></section>
<!-- /wp:group -->
```

**Image with alt text and Enlarge on click**

```html
<!-- wp:image {"lightbox":{"enabled":true},"linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter"><img src="media/bigquery-console-run-button.png" alt="BigQuery console with the Run button highlighted"/></figure>
<!-- /wp:image -->
```

**Table (Stripes style)**

```html
<!-- wp:table {"className":"is-style-stripes"} -->
<figure class="wp-block-table is-style-stripes"><table class="has-fixed-layout"><thead><tr><th>Command</th><th>What it does</th></tr></thead><tbody><tr><td><code>git status</code></td><td>Shows changed files.</td></tr><tr><td><code>git log</code></td><td>Shows commit history.</td></tr></tbody></table></figure>
<!-- /wp:table -->
```

**Glossary term**

A reference stores only the entry's ID, so the entry can be edited without touching the lesson. Look the ID up on the environment you are editing:

```bash
wp @<env> glossary term list --search=commit --fields=id,term,abbr,status
```

```html
<!-- wp:paragraph -->
<p>Each snapshot, called a <span class="glossary-ref" data-glossary-id="<entry-id>">commit</span>, records who changed what, when and why.</p>
<!-- /wp:paragraph -->
```

Text that matches one of the entry's abbreviations renders as an abbreviation by itself. To make other text do the same, add `data-glossary-abbr="true"` to the span.

**Synced pattern (Learning checkpoint, Q&A, Break)**

A synced pattern is a reference to the pattern's post ID. IDs can differ between environments, so look the ID up on the environment you are editing rather than copying it from another lesson:

```bash
wp @<env> post list --post_type=wp_block --name=learning-checkpoint --field=ID
wp @<env> post list --post_type=wp_block --name=question-and-answer-break --field=ID
wp @<env> post list --post_type=wp_block --name=break --field=ID
```

```html
<!-- wp:block {"ref":<pattern-id>} /-->
```

**OS tabs (unsynced pattern)**

Each tab is a `tabs-item` with a `title`. Keep the `title` attribute, the `data-title` attribute and the header text the same. Omit tabs that do not apply. Replace `<8-hex-chars>` with eight random hexadecimal characters, the same in both places and unique within the post.

```html
<!-- wp:themeisle-blocks/tabs {"id":"wp-block-themeisle-blocks-tabs-<8-hex-chars>","className":""} -->
<div id="wp-block-themeisle-blocks-tabs-<8-hex-chars>" class="wp-block-themeisle-blocks-tabs"><div class="wp-block-themeisle-blocks-tabs__content"><!-- wp:themeisle-blocks/tabs-item {"title":"macOS"} -->
<div data-title="macOS" class="wp-block-themeisle-blocks-tabs-item"><div class="wp-block-themeisle-blocks-tabs-item__header" tabindex="0">macOS</div><div class="wp-block-themeisle-blocks-tabs-item__content"><!-- wp:paragraph -->
<p>Steps for macOS.</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:themeisle-blocks/tabs-item -->

<!-- wp:themeisle-blocks/tabs-item {"title":"Windows"} -->
<div data-title="Windows" class="wp-block-themeisle-blocks-tabs-item"><div class="wp-block-themeisle-blocks-tabs-item__header" tabindex="0">Windows</div><div class="wp-block-themeisle-blocks-tabs-item__content"><!-- wp:paragraph -->
<p>Steps for Windows.</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:themeisle-blocks/tabs-item -->

<!-- wp:themeisle-blocks/tabs-item {"title":"Ubuntu"} -->
<div data-title="Ubuntu" class="wp-block-themeisle-blocks-tabs-item"><div class="wp-block-themeisle-blocks-tabs-item__header" tabindex="0">Ubuntu</div><div class="wp-block-themeisle-blocks-tabs-item__content"><!-- wp:paragraph -->
<p>Steps for Ubuntu.</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:themeisle-blocks/tabs-item --></div></div>
<!-- /wp:themeisle-blocks/tabs -->
```

**Review notes**

Review notes are PublishPress editorial comments on the post, not markup. Add them in the **Editorial Comments** box below the editor. The team sees every post's comments on the **Planner › Editorial Comments** screen. Write one note per issue, starting with the section it refers to:

```text
Agent review, "Work with branches > Create a branch": The source says "LookerML"; changed to "LookML" per the word list. Confirm this was not deliberate.
```

Agents add them from a list of notes with `.agents/skills/cbf-academy-style/scripts/add_editorial_comments.php`, attributed to the person they're working for.
