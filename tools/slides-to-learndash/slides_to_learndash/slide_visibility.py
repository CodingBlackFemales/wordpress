"""OOXML slide visibility (hidden slides) without importing python-pptx here."""

from __future__ import annotations


def slide_is_hidden(slide: object) -> bool:
    """Return True if the slide is marked hidden in PowerPoint (excluded from slideshow).

    The ``show`` attribute on the ``p:sld`` root defaults to true; ``0`` or ``false`` means hidden.
    """
    el = getattr(slide, "_element", None)
    if el is None:
        return False
    show = el.get("show")
    if show is None:
        return False
    s = str(show).strip().lower()
    return s in ("0", "false")
