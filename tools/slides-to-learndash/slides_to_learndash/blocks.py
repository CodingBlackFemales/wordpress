"""WordPress block serialization (single-line HTML for CSV)."""

from __future__ import annotations

import html
import json
import re
from typing import Iterable

# Block delimiter comments use JSON with double quotes — escape for embedding in attributes if needed.
_WS = re.compile(r"\s+")


def _esc(s: str) -> str:
    return html.escape(s, quote=False)


def paragraph_block(text: str) -> str:
    t = _esc(text.strip())
    if not t:
        return ""
    return f'<!-- wp:paragraph --><p>{t}</p><!-- /wp:paragraph -->'


def paragraph_block_html(inner_html: str) -> str:
    """Paragraph block with trusted inner HTML (bold, italic, code, etc.)."""
    inner = inner_html.strip()
    if not inner:
        return ""
    return f'<!-- wp:paragraph --><p>{inner}</p><!-- /wp:paragraph -->'


def heading_block(text: str, level: int = 2) -> str:
    t = _esc(text.strip())
    if not t:
        return ""
    if level < 1:
        level = 1
    if level > 6:
        level = 6
    cls = "wp-block-heading"
    return (
        f'<!-- wp:heading {{"level":{level}}} -->'
        f'<h{level} class="{cls}">{t}</h{level}>'
        f"<!-- /wp:heading -->"
    )


def heading_block_html(inner_html: str, level: int) -> str:
    """Heading with trusted inner HTML."""
    inner = inner_html.strip()
    if not inner:
        return ""
    if level < 1:
        level = 1
    if level > 6:
        level = 6
    cls = "wp-block-heading"
    return (
        f'<!-- wp:heading {{"level":{level}}} -->'
        f'<h{level} class="{cls}">{inner}</h{level}>'
        f"<!-- /wp:heading -->"
    )


def list_block(items: list[str]) -> str:
    if not items:
        return ""
    parts = ["<!-- wp:list --><ul class=\"wp-block-list\">"]
    for item in items:
        ti = _esc(item.strip())
        if not ti:
            continue
        parts.append("<!-- wp:list-item -->")
        parts.append(f"<li>{ti}</li>")
        parts.append("<!-- /wp:list-item -->")
    parts.append("</ul><!-- /wp:list -->")
    return "".join(parts)


def list_block_html(items: list[str]) -> str:
    """List items with trusted inner HTML per <li>."""
    if not items:
        return ""
    parts = ['<!-- wp:list --><ul class="wp-block-list">']
    for item in items:
        li = item.strip()
        if not li:
            continue
        parts.append("<!-- wp:list-item -->")
        parts.append(f"<li>{li}</li>")
        parts.append("<!-- /wp:list-item -->")
    parts.append("</ul><!-- /wp:list -->")
    return "".join(parts)


def code_block_plain(code_text: str) -> str:
    """wp:code block from plain source (escaped).

    Newlines are encoded as &#10; so they survive the single-line whitespace
    normalisation applied to the full post_content before CSV export.
    WordPress faithfully preserves &#10; inside <pre><code> on import.
    """
    raw = code_text.replace("\r\n", "\n").replace("\r", "\n").strip()
    if not raw:
        return ""
    esc = html.escape(raw).replace("\n", "&#10;")
    return (
        "<!-- wp:code -->"
        '<pre class="wp-block-code"><code>'
        f"{esc}"
        "</code></pre>"
        "<!-- /wp:code -->"
    )


def image_block(relative_src: str, alt: str = "") -> str:
    """relative_src is URL path fragment for wp-content or relative file path as stored in CSV."""
    alt_a = _esc(alt)
    # wp:image with minimal attrs (classic block format)
    return (
        f'<!-- wp:image {{"sizeSlug":"large","linkDestination":"none"}} -->'
        f'<figure class="wp-block-image size-large">'
        f'<img src="{_esc(relative_src)}" alt="{alt_a}"/>'
        f"</figure>"
        f"<!-- /wp:image -->"
    )


def separator_block() -> str:
    return (
        '<!-- wp:separator -->'
        '<hr class="wp-block-separator has-alpha-channel-opacity"/>'
        "<!-- /wp:separator -->"
    )


def _flex_basis_for_column_width(width: str) -> str | None:
    """Match core Column block save() (column/save.js): inline flex-basis from width attr."""
    w = width.strip()
    if not w or not re.search(r"\d", w):
        return None
    flex_basis = w
    if w.endswith("%"):
        mult = 10**12
        n = round(float(w[:-1]) * mult) / mult
        # Match JS number + "%" stringification (no spurious .0 on whole numbers).
        flex_basis = f"{n:g}%"
    return flex_basis


def _column_wrapper_open(width: str) -> str:
    """Opening <div> for core/column: class + flex-basis style when width is set."""
    fb = _flex_basis_for_column_width(width)
    if fb is None:
        return '<div class="wp-block-column">'
    # Core serializes React style {{ flexBasis }} as flex-basis in the saved markup.
    return f'<div class="wp-block-column" style="flex-basis:{fb}">'


def _equal_column_widths(n: int) -> list[str]:
    """Width attributes for n equal columns (sum ~100%; matches core presets)."""
    if n <= 0:
        return []
    if n == 1:
        return ["100%"]
    if n == 2:
        return ["50%", "50%"]
    if n == 3:
        return ["33.33%", "33.33%", "33.34%"]
    p = 100.0 / n
    return [f"{p:.2f}%" for _ in range(n)]


def columns_block_html(column_inner_html: list[str], widths_percent: list[str]) -> str:
    """Core Columns block: one row, N column inner HTML fragments (already block markup)."""
    cols = [c.strip() for c in column_inner_html if c and c.strip()]
    if len(cols) < 2:
        return join_blocks(column_inner_html)
    w = list(widths_percent)
    # Empty columns are dropped above; do not slice preset widths — that can yield e.g. two
    # "33.33%" from a 3-column preset (invalid, does not sum to 100%) and Gutenberg flags the block.
    if len(w) != len(cols):
        w = _equal_column_widths(len(cols))
    parts: list[str] = [
        "<!-- wp:columns -->",
        '<div class="wp-block-columns is-layout-flex wp-block-columns-is-layout-flex">',
    ]
    for inner, width in zip(cols, w, strict=True):
        attr = json.dumps({"width": width}, separators=(",", ":"))
        parts.append(f"<!-- wp:column {attr} -->")
        parts.append(_column_wrapper_open(width))
        parts.append(inner)
        parts.append("</div><!-- /wp:column -->")
    parts.append("</div><!-- /wp:columns -->")
    return "".join(parts)


def join_blocks(parts: Iterable[str]) -> str:
    """Concatenate block strings; drop empties; normalize whitespace to single line."""
    out: list[str] = []
    for p in parts:
        p = p.strip()
        if p:
            out.append(p)
    joined = "".join(out)
    return _WS.sub(" ", joined).strip()


def merge_slide_sections(sections: list[str], *, slide_separator: bool = False) -> str:
    """Join per-slide HTML sections; optionally insert wp:separator between slides (off by default)."""
    merged: list[str] = []
    for i, sec in enumerate(sections):
        sec = sec.strip()
        if not sec:
            continue
        if slide_separator and i > 0:
            merged.append(separator_block())
        merged.append(sec)
    return join_blocks(merged)
