"""Build lesson/topic rows from PPTX extraction."""

from __future__ import annotations

import re
from pathlib import Path
from typing import Any, Literal

from pptx import Presentation

from slides_to_learndash import blocks
from slides_to_learndash.content_postprocess import strip_session_outline_columns_keep_learning_objectives
from slides_to_learndash.extract import SlideExtract, extract_slide, slide_to_post_html
from slides_to_learndash.slide_merge import (
    include_slide_heading_vs_previous,
    merge_and_coalesce_slides,
)
from slides_to_learndash.slide_visibility import slide_is_hidden

Mode = Literal["lesson-only", "lesson-with-topics"]


def _compile_layout_pattern(pattern: str) -> re.Pattern[str]:
    try:
        return re.compile(pattern)
    except re.error as e:
        raise ValueError(f"Invalid --heading-layout regex: {e}") from e


def _is_heading(sl: SlideExtract, rx: re.Pattern[str]) -> bool:
    return rx.fullmatch(sl.layout_name.strip()) is not None


def _looks_like_section_layout(layout_name: str) -> bool:
    """Heuristic fallback for section/divider slides in lesson-only mode."""
    n = layout_name.strip().upper()
    return n.startswith("SECTION_")


def _lesson_title_from_deck(slides: list[SlideExtract], explicit: str | None) -> str:
    if explicit and explicit.strip():
        return explicit.strip()
    if slides:
        if slides[0].title:
            return slides[0].title
        # first non-empty text segment
        for kind, text in slides[0].segments:
            if kind != "image" and text.strip():
                return text.strip()[:200]
    return "Imported lesson"


def _topic_title(sl: SlideExtract, fallback: str) -> str:
    if sl.title.strip():
        return sl.title.strip()
    for kind, text in sl.segments:
        if kind != "image" and text.strip():
            return text.strip()[:200]
    return fallback


def build_slides(
    prs: Presentation,
    *,
    media_dir: Path | None,
    skip_images: bool,
    media_url_prefix: str = "media",
) -> list[SlideExtract]:
    counter = [0]
    blob_cache: dict[bytes, str] = {}
    mdir = None if skip_images else media_dir
    prefix = media_url_prefix if mdir is not None else "media"
    out: list[SlideExtract] = []
    for i, slide in enumerate(prs.slides, start=1):
        if slide_is_hidden(slide):
            continue
        out.append(
            extract_slide(
                slide,
                i,
                media_dir=mdir,
                image_counter=counter,
                image_blob_cache=blob_cache,
                media_url_prefix=prefix,
            )
        )
    return out


def _slides_after_cover(slides: list[SlideExtract]) -> list[SlideExtract]:
    """Skip slide 1 (cover); it is not included in post_content."""
    return slides[1:] if len(slides) > 1 else []


def run_lesson_only(
    slides: list[SlideExtract],
    *,
    course_id: str,
    lesson_title: str | None,
    include_slide_headings: bool,
    section_heading_layout_regex: str | None,
) -> list[dict[str, Any]]:
    content_slides = merge_and_coalesce_slides(_slides_after_cover(slides))
    rx = (
        _compile_layout_pattern(section_heading_layout_regex.strip())
        if section_heading_layout_regex and section_heading_layout_regex.strip()
        else None
    )
    sections: list[str] = []
    prev: SlideExtract | None = None
    for s in content_slides:
        # Section/divider slides → H2; other content slides → H3 (cover is already skipped).
        is_section = (
            rx.fullmatch(s.layout_name.strip()) is not None
            if rx is not None
            else _looks_like_section_layout(s.layout_name)
        )
        lvl = 2 if is_section else 3
        sections.append(
            slide_to_post_html(
                s,
                include_slide_heading=include_slide_heading_vs_previous(
                    s, prev, include_slide_headings=include_slide_headings
                ),
                slide_heading_level=lvl,
            )
        )
        prev = s
    body = blocks.merge_slide_sections(sections, slide_separator=False)
    body = strip_session_outline_columns_keep_learning_objectives(body)
    title = _lesson_title_from_deck(slides, lesson_title)
    return [
        {
            "ID": "",
            "post_type": "sfwd-lessons",
            "post_title": title,
            "post_content": body,
            "course_id": course_id,
            "lesson_id": "",
            "quiz_id": "",
        }
    ]


def run_lesson_with_topics(
    slides: list[SlideExtract],
    *,
    course_id: str,
    lesson_title: str | None,
    heading_layout_regex: str,
    include_slide_headings: bool,
) -> tuple[list[dict[str, Any]], list[dict[str, Any]]]:
    rx = _compile_layout_pattern(heading_layout_regex)
    heading_indices = [i for i, s in enumerate(slides) if _is_heading(s, rx)]

    if not heading_indices:
        # All slides → single lesson; no topic rows
        lesson_rows = run_lesson_only(
            slides,
            course_id=course_id,
            lesson_title=lesson_title,
            include_slide_headings=include_slide_headings,
            section_heading_layout_regex=heading_layout_regex,
        )
        return lesson_rows, []

    first_h = heading_indices[0]
    pre = slides[:first_h]
    # Cover (index 0) is never part of lesson body; remaining pre-heading slides get optional slide titles.
    lesson_body_slides = merge_and_coalesce_slides(
        pre[1:] if len(pre) > 1 else []
    )
    lesson_sections: list[str] = []
    prev_lb: SlideExtract | None = None
    for s in lesson_body_slides:
        lesson_sections.append(
            slide_to_post_html(
                s,
                include_slide_heading=include_slide_heading_vs_previous(
                    s, prev_lb, include_slide_headings=include_slide_headings
                ),
                slide_heading_level=2
                if rx.fullmatch(s.layout_name.strip()) is not None
                else 3,
            )
        )
        prev_lb = s
    lesson_body = blocks.merge_slide_sections(lesson_sections, slide_separator=False) if lesson_sections else ""
    lesson_body = strip_session_outline_columns_keep_learning_objectives(lesson_body)

    lesson_row = {
        "ID": "",
        "post_type": "sfwd-lessons",
        "post_title": _lesson_title_from_deck(pre if pre else slides, lesson_title),
        "post_content": lesson_body,
        "course_id": course_id,
        "lesson_id": "",
        "quiz_id": "",
    }

    topic_rows: list[dict[str, Any]] = []
    for ti, h_idx in enumerate(heading_indices):
        end = heading_indices[ti + 1] if ti + 1 < len(heading_indices) else len(slides)
        chunk = merge_and_coalesce_slides(slides[h_idx:end])
        if not chunk:
            continue
        heading_sl = chunk[0]
        title = _topic_title(heading_sl, f"Topic {ti + 1}")
        parts: list[str] = []
        prev_ch: SlideExtract | None = None
        for idx, s in enumerate(chunk):
            lvl = 2 if idx == 0 else 3
            parts.append(
                slide_to_post_html(
                    s,
                    include_slide_heading=include_slide_heading_vs_previous(
                        s, prev_ch, include_slide_headings=include_slide_headings
                    ),
                    slide_heading_level=lvl,
                )
            )
            prev_ch = s
        body = blocks.merge_slide_sections([p for p in parts if p], slide_separator=False)
        body = strip_session_outline_columns_keep_learning_objectives(body)
        topic_rows.append(
            {
                "ID": "",
                "post_type": "sfwd-topic",
                "post_title": title,
                "post_content": body,
                "course_id": course_id,
                "lesson_id": "",
                "quiz_id": "",
            }
        )

    return [lesson_row], topic_rows
