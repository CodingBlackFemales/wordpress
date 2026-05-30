"""Layout/columns extraction tests (demo.pptx fixtures)."""

from __future__ import annotations

import unittest
from pathlib import Path

from slides_to_learndash.extract import LinearBlock, load_presentation, slide_to_post_html
from slides_to_learndash.pipeline import build_slides
from slides_to_learndash.slide_geometry import ShapeBox, cluster_row_indices, _vertical_overlap_ratio

_ROOT = Path(__file__).resolve().parent.parent
_DEMO = _ROOT / "demo.pptx"


class TestColumnsLayout(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        if not _DEMO.is_file():
            raise unittest.SkipTest(f"Missing {_DEMO}")
        prs = load_presentation(_DEMO)
        cls.slides = build_slides(prs, media_dir=None, skip_images=True)

    def test_slide_16_full_width_row_then_two_column_lists(self) -> None:
        s16 = self.slides[15]
        html = slide_to_post_html(s16, include_slide_heading=False)
        self.assertIn("Tuesday", html)
        self.assertIn("<!-- wp:columns -->", html)
        self.assertLess(html.find("Tuesday"), html.find("<!-- wp:columns -->"))
        self.assertIn('"width":"50%"', html)
        self.assertEqual(html.count("<!-- wp:columns -->"), 1)

    def test_slide_3_single_column_no_columns_block(self) -> None:
        s3 = self.slides[2]
        self.assertEqual(len(s3.content_blocks), 1)
        self.assertIsInstance(s3.content_blocks[0], LinearBlock)
        html = slide_to_post_html(s3, include_slide_heading=False)
        self.assertNotIn("wp:columns", html)

    def test_slide_8_two_column_row(self) -> None:
        s8 = self.slides[7]
        html = slide_to_post_html(s8, include_slide_heading=False)
        self.assertIn("<!-- wp:columns -->", html)
        self.assertIn('"width":"33.33%"', html)
        self.assertIn('"width":"66.66%"', html)

    def test_row_cluster_merges_side_by_side_when_overlap_ratio_low(self) -> None:
        """Short label + tall body column: min-height ratio can fall below 0.4; still one row."""
        a = ShapeBox(None, 0, 0, 50_000, 100_000, "text")
        b = ShapeBox(None, 200_000, 70_000, 300_000, 1_000_000, "text")
        self.assertLess(_vertical_overlap_ratio(a, b), 0.4)
        rows = cluster_row_indices([a, b])
        self.assertEqual(len(rows), 1)
        self.assertEqual(set(rows[0]), {0, 1})


if __name__ == "__main__":
    unittest.main()
