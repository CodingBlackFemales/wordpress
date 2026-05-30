"""Map PowerPoint paragraphs/runs to HTML for WordPress blocks."""

from __future__ import annotations

import html
import re
from typing import Any

from pptx.oxml.ns import qn
from pptx.util import Length

_TAG_STRIP = re.compile(r"<[^>]+>")


def strip_bold_tags_from_inner_html(inner: str) -> str:
    """Remove <strong>/<b> wrappers for heading inner HTML; keep other markup."""
    s = inner
    while True:
        prev = s
        s = re.sub(r"<strong\b[^>]*>(.*?)</strong\s*>", r"\1", s, flags=re.IGNORECASE | re.DOTALL)
        s = re.sub(r"<b\b[^>]*>(.*?)</b\s*>", r"\1", s, flags=re.IGNORECASE | re.DOTALL)
        if s == prev:
            break
    return s


def plain_text_from_inner_html(inner: str) -> str:
    """Strip tags for title dedupe / comparison."""
    t = _TAG_STRIP.sub("", inner)
    t = html.unescape(t)
    return " ".join(t.split())


def _paragraph_is_bullet(paragraph: Any) -> bool:
    """True when OOXML marks the paragraph as a list item.

    Google Slides (and some exports) use ``a:buChar`` / ``a:buAutoNum`` / ``a:buBlip``
    instead of ``a:numPr``. ``buFont`` alone is *not* sufficient — it appears on
    non-bullets together with ``a:buNone``.
    """
    p = paragraph._element
    ppr = p.pPr
    if ppr is None:
        return False
    if getattr(ppr, "numPr", None) is not None:
        return True
    for tag in ("a:buChar", "a:buAutoNum", "a:buBlip"):
        if ppr.find(qn(tag)) is not None:
            return True
    lvl = getattr(paragraph, "level", None)
    return bool(lvl and lvl > 0)


def _run_is_monospace(run: Any) -> bool:
    n = (run.font.name or "").lower()
    if not n:
        return False
    keys = (
        "mono", "consolas", "courier", "monaco", "menlo",
        "source code", "lucida console", "inconsolata", "fira code",
        "jetbrains", "cascadia", "hack", "iosevka", "droid sans mono",
    )
    return any(k in n for k in keys)


def _run_size_pt(run: Any) -> float | None:
    sz = run.font.size
    if sz is None:
        return None
    if isinstance(sz, Length):
        return float(sz.pt)
    return None


def _paragraph_is_code(paragraph: Any) -> bool:
    runs = [r for r in paragraph.runs if r.text and r.text.strip()]
    if not runs:
        return False
    return all(_run_is_monospace(r) for r in runs)


def _paragraph_max_pt(paragraph: Any) -> float | None:
    pts = [_run_size_pt(r) for r in paragraph.runs if r.text]
    pts = [p for p in pts if p is not None]
    return max(pts) if pts else None


def paragraph_heading_level(paragraph: Any) -> int | None:
    """Treat larger / emphasized body text as sub-headings (h3/h4)."""
    if _paragraph_is_bullet(paragraph) or _paragraph_is_code(paragraph):
        return None
    runs = [r for r in paragraph.runs if r.text and r.text.strip()]
    if not runs:
        return None
    r0 = runs[0]
    pt = _paragraph_max_pt(paragraph) or _run_size_pt(r0)
    bold = bool(r0.font.bold)
    if pt is None:
        return None
    # Typical body ~14–18pt; deck titles/subtitles often 20pt+.
    if pt >= 28 or (pt >= 24 and bold):
        return 3
    if pt >= 22 or (pt >= 20 and bold):
        return 4
    return None


def _run_has_visible_text(run: Any) -> bool:
    """True if the run has non-whitespace characters (link labels users can read)."""
    return bool((run.text or "").strip())


def _run_hyperlink_url(run: Any) -> str | None:
    """Return URL from a text run hyperlink, if any."""
    try:
        h = run.hyperlink
        addr = h.address
        if addr:
            return str(addr).strip()
    except (AttributeError, TypeError, ValueError):
        pass
    return None


def _sanitize_href(url: str) -> str | None:
    """Return HTML-attribute-safe href or None if the URL must be dropped."""
    u = (url or "").strip()
    if not u:
        return None
    low = u.lower()
    scheme = low.split(":", 1)[0] if ":" in low else ""
    if scheme in ("javascript", "data", "vbscript"):
        return None
    if low.startswith("file:"):
        return None
    return html.escape(u, quote=True)


def _wrap_anchor(inner_html: str, url: str | None) -> str:
    """Wrap inline HTML in <a> when the URL is safe for WordPress."""
    if not url:
        return inner_html
    safe = _sanitize_href(url)
    if not safe:
        return inner_html
    low = url.lower().strip()
    if low.startswith(("http://", "https://")):
        return (
            f'<a href="{safe}" target="_blank" rel="noopener noreferrer">{inner_html}</a>'
        )
    return f'<a href="{safe}">{inner_html}</a>'


def format_run_html(run: Any) -> str:
    """Single run → inline HTML (escaped text + semantic tags + optional hyperlink).

    Hyperlinks on whitespace-only runs (layout/logo hit areas) are omitted — only
    human-visible link text is converted to ``<a>`` tags.
    """
    raw = run.text or ""
    if not _run_has_visible_text(run):
        return ""
    url = _run_hyperlink_url(run)
    t = html.escape(raw)
    if run.font.bold:
        t = f"<strong>{t}</strong>"
    if run.font.italic:
        t = f"<em>{t}</em>"
    # PowerPoint usually sets underline on linked runs; <a> styling is enough for WordPress.
    if getattr(run.font, "underline", None) and not url:
        t = f"<u>{t}</u>"
    if getattr(run.font, "strike", None):
        t = f"<s>{t}</s>"
    if _run_is_monospace(run):
        t = f"<code>{t}</code>"
    return _wrap_anchor(t, url)


def paragraph_runs_to_html(paragraph: Any) -> str:
    return "".join(format_run_html(r) for r in paragraph.runs if _run_has_visible_text(r))


def paragraph_to_code_plain(paragraph: Any) -> str:
    """Preserve line breaks inside code paragraph."""
    return (paragraph.text or "").replace("\r\n", "\n").replace("\r", "\n")


def classify_paragraph(paragraph: Any) -> str | None:
    """Return segment kind: bullet, code, h3, h4, p — or None if empty."""
    text = (paragraph.text or "").strip()
    if not text:
        return None
    # Code detection wins over bullet: monospace font is the authoritative signal,
    # even when indentation level > 0 triggers the generic bullet heuristic.
    if _paragraph_is_code(paragraph):
        return "code"
    if _paragraph_is_bullet(paragraph):
        return "bullet"
    hl = paragraph_heading_level(paragraph)
    if hl == 3:
        return "h3"
    if hl == 4:
        return "h4"
    return "p"


