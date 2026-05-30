"""Hidden slide detection (OOXML ``show`` on ``p:sld``)."""

from __future__ import annotations

import unittest
from unittest.mock import MagicMock

from slides_to_learndash.slide_visibility import slide_is_hidden


class TestSlideIsHidden(unittest.TestCase):
    def test_visible_when_show_absent(self) -> None:
        slide = MagicMock()
        slide._element.get.return_value = None
        self.assertFalse(slide_is_hidden(slide))
        slide._element.get.assert_called_once_with("show")

    def test_visible_when_show_one(self) -> None:
        slide = MagicMock()
        slide._element.get.return_value = "1"
        self.assertFalse(slide_is_hidden(slide))

    def test_hidden_when_show_zero(self) -> None:
        slide = MagicMock()
        slide._element.get.return_value = "0"
        self.assertTrue(slide_is_hidden(slide))

    def test_hidden_when_show_false(self) -> None:
        slide = MagicMock()
        slide._element.get.return_value = "false"
        self.assertTrue(slide_is_hidden(slide))

    def test_visible_when_no_element(self) -> None:
        class Bare:
            pass

        self.assertFalse(slide_is_hidden(Bare()))  # type: ignore[arg-type]
