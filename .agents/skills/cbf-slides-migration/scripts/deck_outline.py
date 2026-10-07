#!/usr/bin/env python3
"""Print a slide-by-slide outline of a .pptx deck, including speaker notes.

The slides importer drops speaker notes, but they are usually the best source of
the prose a migrated lesson needs. This script recovers them, and suggests a
classification for each slide (cover, section header, content, code, exercise,
activity, recap, questions, references) for you to check.

Usage (from the repository root, using the importer's virtualenv):
    .venv-pptx/bin/python .agents/skills/cbf-slides-migration/scripts/deck_outline.py DECK.pptx [--no-body]

    --no-body   Omit slide body text; print titles, classes and notes only.

Output is Markdown on stdout. Redirect it to a file to keep it beside the draft.
Requires python-pptx (installed in .venv-pptx by the import pipeline setup).
"""

import argparse
import re
import sys

try:
    from pptx import Presentation
except ImportError:
    sys.exit(
        "python-pptx is not installed. Run this with .venv-pptx/bin/python, or set it up with:\n"
        "  python3 -m venv .venv-pptx && ./.venv-pptx/bin/pip install -e tools/slides-to-learndash"
    )

CLASS_RULES = [
    ("questions", r"\b(any )?questions\??$|^q ?& ?a$|^thank(s| you)"),
    ("references", r"\b(references|resources|further reading|useful links|reading list)\b"),
    ("recap", r"\b(recap|summary|key takeaways|takeaways|wrap[- ]?up|what we covered)\b"),
    ("exercise", r"\b(exercise|lab|practice|challenge|homework|assignment)\b"),
    ("activity", r"\b(activity|your turn|try it|discuss|breakout|reflect|poll)\b"),
    ("objectives", r"\b(learning objectives|objectives|outline|agenda|what you('ll| will) learn)\b"),
]
MONO_FONTS = re.compile(r"(?i)mono|courier|consolas|menlo|source code|fira code|roboto mono")


def slide_title(slide):
    if slide.shapes.title is not None and slide.shapes.title.has_text_frame:
        return slide.shapes.title.text_frame.text.strip()
    return ""


def body_lines(slide):
    lines, mono = [], 0
    for shape in slide.shapes:
        if shape == slide.shapes.title or not shape.has_text_frame:
            continue
        for para in shape.text_frame.paragraphs:
            text = "".join(r.text for r in para.runs).strip()
            if not text:
                continue
            fonts = {r.font.name or "" for r in para.runs}
            if fonts and all(MONO_FONTS.search(f) for f in fonts):
                mono += 1
            lines.append(("  " * para.level) + "- " + text)
    return lines, mono


def count_images(slide):
    return sum(1 for s in slide.shapes if s.shape_type == 13)  # MSO_SHAPE_TYPE.PICTURE


def notes_text(slide):
    if not slide.has_notes_slide:
        return ""
    tf = slide.notes_slide.notes_text_frame
    return tf.text.strip() if tf is not None else ""


def classify(index, layout, title, lines, mono, images):
    if index == 1:
        return "cover"
    t = title.lower()
    for name, pattern in CLASS_RULES:
        if re.search(pattern, t):
            return name
    if re.search(r"(?i)section|title only|divider", layout) and len(lines) <= 1:
        return "section header"
    if title and not lines and not images:
        return "section header"
    if mono and mono >= max(1, len(lines) // 2):
        return "code"
    if not lines and not title:
        return "empty"
    return "content"


def main():
    parser = argparse.ArgumentParser(description=__doc__.split("\n\n")[0])
    parser.add_argument("deck")
    parser.add_argument("--no-body", action="store_true")
    args = parser.parse_args()

    try:
        prs = Presentation(args.deck)
    except Exception as exc:  # python-pptx raises several types for bad files
        sys.exit(f"Cannot open {args.deck}: {exc}")

    print(f"# Deck outline: {args.deck}\n")
    print("Classes are suggestions from titles, layouts and fonts. Check each one.\n")
    with_notes = 0
    for i, slide in enumerate(prs.slides, start=1):
        layout = slide.slide_layout.name or ""
        title = slide_title(slide)
        lines, mono = body_lines(slide)
        images = count_images(slide)
        notes = notes_text(slide)
        with_notes += bool(notes)
        cls = classify(i, layout, title, lines, mono, images)
        print(f"## Slide {i}: {title or '(no title)'}\n")
        meta = f"class: {cls} | layout: {layout} | images: {images} | bullets: {len(lines)}"
        print(f"`{meta}`\n")
        if lines and not args.no_body:
            print("\n".join(lines) + "\n")
        if notes:
            print("**Speaker notes:**\n")
            print("\n".join("> " + l if l.strip() else ">" for l in notes.splitlines()) + "\n")
    print(f"---\n{len(prs.slides)} slides, {with_notes} with speaker notes.")


if __name__ == "__main__":
    main()
