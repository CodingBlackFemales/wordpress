"""Post-process merged block HTML before CSV export."""

from __future__ import annotations

import re

from slides_to_learndash import blocks

_COL_START = re.compile(
    r'<!-- wp:column \{"width":"[^"]+"\} -->\s*<div class="wp-block-column"[^>]*>',
)
_COL_END = "</div><!-- /wp:column -->"


def _find_next_balanced_columns_block(html: str, start_at: int = 0) -> tuple[int, int] | None:
    """Return [start, end) span of next wp:columns block at/after start_at."""
    start = html.find("<!-- wp:columns -->", start_at)
    if start == -1:
        return None
    depth = 0
    i = start + len("<!-- wp:columns -->")
    while True:
        next_open = html.find("<!-- wp:columns -->", i)
        next_close = html.find("<!-- /wp:columns -->", i)
        if next_close == -1:
            return None
        if next_open != -1 and next_open < next_close:
            depth += 1
            i = next_open + len("<!-- wp:columns -->")
            continue
        if depth == 0:
            return (start, next_close + len("<!-- /wp:columns -->"))
        depth -= 1
        i = next_close + len("<!-- /wp:columns -->")


def _split_column_inners(columns_inner: str) -> list[str]:
    """Split merged column markup into inner HTML per column (matches blocks.columns_block_html)."""
    inners: list[str] = []
    pos = 0
    while True:
        m = _COL_START.search(columns_inner, pos)
        if not m:
            break
        inner_start = m.end()
        end = columns_inner.find(_COL_END, inner_start)
        if end == -1:
            break
        inners.append(columns_inner[inner_start:end].strip())
        pos = end + len(_COL_END)
    return inners


def _columns_inner(block: str) -> str | None:
    """Return inner HTML between columns wrapper open and final /wp:columns in one block."""
    open_div = '<div class="wp-block-columns is-layout-flex wp-block-columns-is-layout-flex">'
    i = block.find(open_div)
    if i == -1:
        return None
    inner_start = i + len(open_div)
    close = block.rfind("</div><!-- /wp:columns -->")
    if close == -1 or close <= inner_start:
        return None
    return block[inner_start:close]


_LO_LABEL_PARA = re.compile(
    r'^<!-- wp:paragraph --><p>\s*(?:<(?:strong|b)\b[^>]*>)?\s*Learning\s+Objectives\s*(?:</(?:strong|b)\s*>)?\s*</p><!-- /wp:paragraph -->',
    re.IGNORECASE | re.DOTALL,
)
_LO_LABEL_HEADING = re.compile(
    r'^<!-- wp:heading \{"level":[1-6]\} --><h[1-6][^>]*>\s*(?:<(?:strong|b)\b[^>]*>)?\s*Learning\s+Objectives\s*(?:</(?:strong|b)\s*>)?\s*</h[1-6]><!-- /wp:heading -->',
    re.IGNORECASE | re.DOTALL,
)
_LEAD_OUTLINE_HEADING_BLOCK_AT_END = re.compile(
    r'<!-- wp:heading \{"level":[23]\} --><h[23][^>]*>.*?</h[23]><!-- /wp:heading -->\s*$',
    re.IGNORECASE | re.DOTALL,
)


def _extract_lo_heading_and_list(objectives_column: str) -> tuple[str, str] | None:
    """Return (heading block, list block) if column starts with LO label + list; else None."""
    s = objectives_column.strip()
    m = _LO_LABEL_PARA.match(s) or _LO_LABEL_HEADING.match(s)
    if not m:
        return None
    rest = s[m.end() :].lstrip()
    if not rest.startswith("<!-- wp:list -->"):
        return None
    end_list = rest.find("<!-- /wp:list -->")
    if end_list == -1:
        return None
    end_list += len("<!-- /wp:list -->")
    list_block = rest[:end_list].strip()
    heading = blocks.heading_block("Learning Objectives", 2)
    return (heading, list_block)


def strip_session_outline_columns_keep_learning_objectives(html: str) -> str:
    """Replace first matching outline-style columns row with full-width H2 + objectives list."""
    cursor = 0
    while True:
        span = _find_next_balanced_columns_block(html, start_at=cursor)
        if span is None:
            return html
        start, end = span
        block = html[start:end]
        if "Learning Objectives" not in block:
            cursor = end
            continue
        inner = _columns_inner(block)
        if inner is None:
            cursor = end
            continue
        cols = _split_column_inners(inner)
        if len(cols) < 2:
            cursor = end
            continue

        objectives_col: str | None = None
        for c in cols:
            if _extract_lo_heading_and_list(c) is not None:
                objectives_col = c
                break
        if objectives_col is None:
            cursor = end
            continue

        extracted = _extract_lo_heading_and_list(objectives_col)
        if extracted is None:
            cursor = end
            continue
        heading_html, list_html = extracted
        replacement = blocks.join_blocks([heading_html, list_html])
        prefix = html[:start]
        # Remove an immediate leading H3 heading before the outline columns; it is
        # redundant once LO is promoted to the top-level heading.
        prefix = _LEAD_OUTLINE_HEADING_BLOCK_AT_END.sub("", prefix)
        return prefix + replacement + html[end:]
