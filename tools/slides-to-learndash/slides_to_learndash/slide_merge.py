"""Merge consecutive slides with the same title (column continuation + heading dedupe support)."""

from __future__ import annotations

from dataclasses import replace

from slides_to_learndash.extract import ColumnsBlock, LinearBlock, SlideContentBlock, SlideExtract
from slides_to_learndash.rich_text import plain_text_from_inner_html


def _parse_width_percent(s: str) -> float:
    t = s.strip()
    if t.endswith("%"):
        try:
            return float(t[:-1])
        except ValueError:
            return -1.0
    return -1.0


def widths_layout_compatible(a: list[str], b: list[str]) -> bool:
    """True if column counts match and width presets are close (EMU noise → different % strings)."""
    if len(a) != len(b):
        return False
    if a == b:
        return True
    for wa, wb in zip(a, b, strict=True):
        pa, pb = _parse_width_percent(wa), _parse_width_percent(wb)
        if pa < 0 or pb < 0:
            return False
        if abs(pa - pb) > 4.0:
            return False
    return True


def titles_equivalent(a: str, b: str) -> bool:
    """True if both titles are non-empty and equal after trim + casefold."""
    x = (a or "").strip()
    y = (b or "").strip()
    if not x or not y:
        return False
    return x.casefold() == y.casefold()


def titles_allow_column_merge(prev: SlideExtract, curr: SlideExtract) -> bool:
    """Same-title slides merge; continuation slides often have an empty title placeholder."""
    pt, ct = (prev.title or "").strip(), (curr.title or "").strip()
    if not pt:
        return False
    if not ct:
        return True
    return pt.casefold() == ct.casefold()


def _segment_fingerprint(kind: str, payload: str) -> tuple[str, str]:
    if kind == "image":
        return ("image", payload.strip())
    if kind == "code":
        return ("code", " ".join(payload.strip().split()).casefold())
    return (kind, plain_text_from_inner_html(payload).strip().casefold())


def segments_equal(seg_a: tuple[str, str], seg_b: tuple[str, str]) -> bool:
    return _segment_fingerprint(seg_a[0], seg_a[1]) == _segment_fingerprint(
        seg_b[0], seg_b[1]
    )


def _column_plain_join(segments: list[tuple[str, str]]) -> str:
    """Single signature for duplicate detection when HTML differs but text is the same."""
    parts: list[str] = []
    for k, pl in segments:
        fp = _segment_fingerprint(k, pl)
        parts.append(f"{fp[0]}\0{fp[1]}")
    return "\n".join(parts)


def merge_column_segments(
    a: list[tuple[str, str]], b: list[tuple[str, str]]
) -> list[tuple[str, str]]:
    """Append column b onto a after dropping the longest common prefix of segment tuples."""
    if not b:
        return list(a)
    if not a:
        return list(b)
    # Identical column body (e.g. same bullets with different markup) — keep one copy.
    if _column_plain_join(a) == _column_plain_join(b):
        return list(a)
    lp = 0
    while lp < len(a) and lp < len(b) and segments_equal(a[lp], b[lp]):
        lp += 1
    raw = a + b[lp:]
    return _dedupe_consecutive_identical_images(raw)


def _dedupe_consecutive_identical_images(
    segments: list[tuple[str, str]],
) -> list[tuple[str, str]]:
    """Drop back-to-back image segments with the same src (stacked continuation slides)."""
    out: list[tuple[str, str]] = []
    for seg in segments:
        if (
            out
            and seg[0] == "image"
            and out[-1][0] == "image"
            and seg[1] == out[-1][1]
        ):
            continue
        out.append(seg)
    return out


def _flatten_columns_only_slide(
    slide: SlideExtract,
) -> tuple[list[list[tuple[str, str]]], list[str]] | None:
    """Stack every ColumnsBlock row into one segment list per column (same slide).

    Empty LinearBlocks (e.g. trailing placeholder rows with no content) are ignored so
    that slides whose only non-empty blocks are ColumnsBlocks can still be merged.
    """
    if not slide.content_blocks:
        return None
    for b in slide.content_blocks:
        if isinstance(b, LinearBlock) and not b.segments:
            continue
        if not isinstance(b, ColumnsBlock):
            return None
    active_blocks = [
        b for b in slide.content_blocks
        if not (isinstance(b, LinearBlock) and not b.segments)
    ]
    first = active_blocks[0]
    n = len(first.columns)
    w = first.widths
    cols: list[list[tuple[str, str]]] = [[] for _ in range(n)]
    for block in active_blocks:
        if len(block.columns) != n:
            return None
        if not widths_layout_compatible(w, block.widths):
            return None
        for j in range(n):
            cols[j].extend(block.columns[j])
    cols = [_dedupe_consecutive_identical_images(c) for c in cols]
    return cols, w


def mergeable_column_pair(prev: SlideExtract, curr: SlideExtract) -> bool:
    """Merge when both slides are columns-only and column counts / widths match."""
    if not titles_allow_column_merge(prev, curr):
        return False
    fa = _flatten_columns_only_slide(prev)
    fb = _flatten_columns_only_slide(curr)
    if fa is None or fb is None:
        return False
    cols_a, wa = fa
    cols_b, wb = fb
    if len(cols_a) != len(cols_b):
        return False
    return widths_layout_compatible(wa, wb)


def _merge_two_slides(prev: SlideExtract, curr: SlideExtract) -> SlideExtract:
    fa = _flatten_columns_only_slide(prev)
    fb = _flatten_columns_only_slide(curr)
    assert fa is not None and fb is not None
    cols_a, wa = fa
    cols_b, wb = fb
    merged_cols = [
        merge_column_segments(a, b) for a, b in zip(cols_a, cols_b, strict=True)
    ]
    merged_paths = list(dict.fromkeys(prev.image_paths + curr.image_paths))
    return SlideExtract(
        index=prev.index,
        layout_name=prev.layout_name,
        title=prev.title,
        content_blocks=[ColumnsBlock(widths=list(wa), columns=merged_cols)],
        image_paths=merged_paths,
    )


def merge_consecutive_same_title_slides(slides: list[SlideExtract]) -> list[SlideExtract]:
    """Merge consecutive column-layout slides with the same title; linear slides unchanged."""
    if len(slides) < 2:
        return list(slides)
    out: list[SlideExtract] = []
    i = 0
    while i < len(slides):
        cur = slides[i]
        while i + 1 < len(slides) and mergeable_column_pair(cur, slides[i + 1]):
            cur = _merge_two_slides(cur, slides[i + 1])
            i += 1
        out.append(cur)
        i += 1
    return out


def coalesce_consecutive_columns_blocks(
    blocks: list[SlideContentBlock],
) -> list[SlideContentBlock]:
    """Stack consecutive ColumnsBlocks with the same widths into one row (one wp:columns)."""
    if len(blocks) <= 1:
        return blocks
    out: list[SlideContentBlock] = []
    i = 0
    while i < len(blocks):
        b = blocks[i]
        if not isinstance(b, ColumnsBlock):
            out.append(b)
            i += 1
            continue
        group: list[ColumnsBlock] = [b]
        j = i + 1
        while j < len(blocks) and isinstance(blocks[j], ColumnsBlock):
            c = blocks[j]
            if len(c.columns) != len(b.columns):
                break
            if not widths_layout_compatible(b.widths, c.widths):
                break
            group.append(c)
            j += 1
        if len(group) == 1:
            out.append(group[0])
        else:
            merged_cols: list[list[tuple[str, str]]] = []
            for k in range(len(group[0].columns)):
                merged: list[tuple[str, str]] = []
                for g in group:
                    merged.extend(g.columns[k])
                merged_cols.append(_dedupe_consecutive_identical_images(merged))
            out.append(
                ColumnsBlock(widths=list(group[0].widths), columns=merged_cols)
            )
        i = j
    return out


def coalesce_slide_column_blocks(slide: SlideExtract) -> SlideExtract:
    """Combine stacked column rows on one slide into a single ColumnsBlock when shapes match."""
    flat = _flatten_columns_only_slide(slide)
    if flat is None:
        new_blocks = coalesce_consecutive_columns_blocks(slide.content_blocks)
        if new_blocks is slide.content_blocks:
            return slide
        return replace(slide, content_blocks=new_blocks)
    cols, w = flat
    single = (
        len(slide.content_blocks) == 1
        and isinstance(slide.content_blocks[0], ColumnsBlock)
        and slide.content_blocks[0].widths == w
        and slide.content_blocks[0].columns == cols
    )
    if single:
        return slide
    return replace(
        slide,
        content_blocks=[ColumnsBlock(widths=list(w), columns=cols)],
    )


def merge_and_coalesce_slides(slides: list[SlideExtract]) -> list[SlideExtract]:
    """Same-title column merge, then coalesce multiple column rows into one block per slide."""
    merged = merge_consecutive_same_title_slides(slides)
    return [coalesce_slide_column_blocks(s) for s in merged]


def include_slide_heading_vs_previous(
    slide: SlideExtract,
    previous: SlideExtract | None,
    *,
    include_slide_headings: bool,
) -> bool:
    """Whether to emit a slide title heading: off when consecutive same title."""
    if not include_slide_headings:
        return False
    if previous is None:
        return True
    st = (slide.title or "").strip()
    pt = (previous.title or "").strip()
    # Blank title on a continuation slide: do not emit a second heading.
    if not st and pt:
        return False
    return not titles_equivalent(slide.title, previous.title)
