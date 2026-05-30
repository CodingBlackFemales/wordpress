"""Tests for same-title slide merge and heading suppression (no pptx)."""

from __future__ import annotations

import unittest

from slides_to_learndash.extract import (
    ColumnsBlock,
    LinearBlock,
    SlideExtract,
    slide_to_post_html,
)
from slides_to_learndash.slide_merge import (
    _flatten_columns_only_slide,
    coalesce_consecutive_columns_blocks,
    include_slide_heading_vs_previous,
    merge_and_coalesce_slides,
    merge_column_segments,
    merge_consecutive_same_title_slides,
    mergeable_column_pair,
    segments_equal,
    titles_equivalent,
    widths_layout_compatible,
)


def _col_slide(title: str, cols: list[list[tuple[str, str]]], idx: int = 1) -> SlideExtract:
    w = ["50%", "50%"] if len(cols) == 2 else ["100%"]
    return SlideExtract(
        index=idx,
        layout_name="TWO_COL",
        title=title,
        content_blocks=[ColumnsBlock(widths=w, columns=cols)],
        image_paths=[],
    )


def _linear_slide(title: str, segs: list[tuple[str, str]], idx: int = 1) -> SlideExtract:
    return SlideExtract(
        index=idx,
        layout_name="TITLE_ONLY",
        title=title,
        content_blocks=[LinearBlock(segs)],
        image_paths=[],
    )


class TestTitlesEquivalent(unittest.TestCase):
    def test_case_insensitive(self) -> None:
        self.assertTrue(titles_equivalent("Hello", "hello"))
        self.assertFalse(titles_equivalent("", "a"))
        self.assertFalse(titles_equivalent("a", ""))


class TestSegmentsEqual(unittest.TestCase):
    def test_paragraph_normalized(self) -> None:
        self.assertTrue(
            segments_equal(("p", " Hi "), ("p", "<strong>hi</strong>"))
        )


class TestMergeColumnSegments(unittest.TestCase):
    def test_duplicate_column(self) -> None:
        a = [("p", "x")]
        b = [("p", "x")]
        self.assertEqual(merge_column_segments(a, b), a)

    def test_continuation(self) -> None:
        a = [("p", "x")]
        b = [("p", "x"), ("p", "y")]
        self.assertEqual(merge_column_segments(a, b), [("p", "x"), ("p", "y")])

    def test_disjoint(self) -> None:
        a = [("p", "a")]
        b = [("p", "b")]
        self.assertEqual(merge_column_segments(a, b), [("p", "a"), ("p", "b")])

    def test_semantic_duplicate_different_markup(self) -> None:
        a = [("bullet", "<strong>git remote</strong>")]
        b = [("bullet", "<b>git remote</b>")]
        out = merge_column_segments(a, b)
        self.assertEqual(out, a)


class TestWidthsLayoutCompatible(unittest.TestCase):
    def test_minor_percent_drift(self) -> None:
        self.assertTrue(
            widths_layout_compatible(["66.66%", "33.33%"], ["66.67%", "33.33%"])
        )

    def test_different_layouts(self) -> None:
        self.assertFalse(widths_layout_compatible(["50%", "50%"], ["40%", "60%"]))


class TestCoalesceColumnsBlocks(unittest.TestCase):
    def test_stacks_two_rows(self) -> None:
        w = ["50%", "50%"]
        b1 = ColumnsBlock(
            widths=w,
            columns=[[("p", "left1")], [("p", "right1")]],
        )
        b2 = ColumnsBlock(
            widths=w,
            columns=[[("p", "left2")], [("p", "right2")]],
        )
        out = coalesce_consecutive_columns_blocks([b1, b2])
        self.assertEqual(len(out), 1)
        self.assertEqual(
            out[0].columns,
            [
                [("p", "left1"), ("p", "left2")],
                [("p", "right1"), ("p", "right2")],
            ],
        )

    def test_coalesces_when_width_strings_differ_slightly(self) -> None:
        b1 = ColumnsBlock(
            widths=["66.66%", "33.33%"],
            columns=[[("p", "a")], [("p", "b")]],
        )
        b2 = ColumnsBlock(
            widths=["66.67%", "33.33%"],
            columns=[[("p", "c")], [("p", "d")]],
        )
        out = coalesce_consecutive_columns_blocks([b1, b2])
        self.assertEqual(len(out), 1)
        assert isinstance(out[0], ColumnsBlock)
        self.assertEqual(
            out[0].columns,
            [[("p", "a"), ("p", "c")], [("p", "b"), ("p", "d")]],
        )


class TestMergeConsecutiveSameTitleSlides(unittest.TestCase):
    def test_merges_two_column_slides(self) -> None:
        s1 = _col_slide("T", [[("p", "a")], [("p", "b")]])
        s2 = _col_slide("T", [[("p", "a")], [("p", "c")]], idx=2)
        out = merge_consecutive_same_title_slides([s1, s2])
        self.assertEqual(len(out), 1)
        blk = out[0].content_blocks[0]
        self.assertIsInstance(blk, ColumnsBlock)
        assert isinstance(blk, ColumnsBlock)
        self.assertEqual(blk.columns[0], [("p", "a")])
        self.assertEqual(blk.columns[1], [("p", "b"), ("p", "c")])

    def test_linear_not_merged(self) -> None:
        s1 = _linear_slide("T", [("p", "one")])
        s2 = _linear_slide("T", [("p", "two")], idx=2)
        out = merge_consecutive_same_title_slides([s1, s2])
        self.assertEqual(len(out), 2)

    def test_different_title_not_merged(self) -> None:
        s1 = _col_slide("A", [[("p", "1")], [("p", "2")]])
        s2 = _col_slide("B", [[("p", "1")], [("p", "2")]], idx=2)
        out = merge_consecutive_same_title_slides([s1, s2])
        self.assertEqual(len(out), 2)

    def test_merges_two_row_slides(self) -> None:
        w = ["50%", "50%"]
        s1 = SlideExtract(
            index=1,
            layout_name="X",
            title="T",
            content_blocks=[
                ColumnsBlock(
                    widths=w,
                    columns=[[("p", "L1")], [("p", "R1")]],
                ),
                ColumnsBlock(
                    widths=w,
                    columns=[[("p", "L2a")], [("p", "R2a")]],
                ),
            ],
            image_paths=[],
        )
        s2 = SlideExtract(
            index=2,
            layout_name="X",
            title="T",
            content_blocks=[
                ColumnsBlock(
                    widths=w,
                    columns=[[("p", "L2b")], [("p", "R2b")]],
                ),
                ColumnsBlock(
                    widths=w,
                    columns=[[("p", "L3")], [("p", "R3")]],
                ),
            ],
            image_paths=[],
        )
        out = merge_consecutive_same_title_slides([s1, s2])
        self.assertEqual(len(out), 1)
        self.assertEqual(len(out[0].content_blocks), 1)
        fa = _flatten_columns_only_slide(s1)
        fb = _flatten_columns_only_slide(s2)
        assert fa is not None and fb is not None
        ca, _wa = fa
        cb, _wb = fb
        blk = out[0].content_blocks[0]
        self.assertIsInstance(blk, ColumnsBlock)
        assert isinstance(blk, ColumnsBlock)
        self.assertEqual(blk.columns[0], merge_column_segments(ca[0], cb[0]))
        self.assertEqual(blk.columns[1], merge_column_segments(ca[1], cb[1]))


class TestMergeAndCoalesce(unittest.TestCase):
    def test_single_slide_two_rows_becomes_one_block(self) -> None:
        w = ["50%", "50%"]
        s = SlideExtract(
            index=1,
            layout_name="X",
            title="T",
            content_blocks=[
                ColumnsBlock(widths=w, columns=[[("p", "a")], [("p", "b")]]),
                ColumnsBlock(widths=w, columns=[[("p", "c")], [("p", "d")]]),
            ],
            image_paths=[],
        )
        out = merge_and_coalesce_slides([s])
        self.assertEqual(len(out), 1)
        self.assertEqual(len(out[0].content_blocks), 1)
        blk = out[0].content_blocks[0]
        self.assertIsInstance(blk, ColumnsBlock)
        assert isinstance(blk, ColumnsBlock)
        self.assertEqual(
            blk.columns,
            [
                [("p", "a"), ("p", "c")],
                [("p", "b"), ("p", "d")],
            ],
        )


class TestMergeableColumnPair(unittest.TestCase):
    def test_widths_mismatch(self) -> None:
        s1 = SlideExtract(
            index=1,
            layout_name="X",
            title="T",
            content_blocks=[
                ColumnsBlock(widths=["50%", "50%"], columns=[[("p", "a")], [("p", "b")]])
            ],
        )
        s2 = SlideExtract(
            index=2,
            layout_name="X",
            title="T",
            content_blocks=[
                ColumnsBlock(widths=["40%", "60%"], columns=[[("p", "a")], [("p", "b")]])
            ],
        )
        self.assertFalse(mergeable_column_pair(s1, s2))


class TestIncludeSlideHeadingVsPrevious(unittest.TestCase):
    def test_first_and_second_same_title(self) -> None:
        a = _linear_slide("Same", [("p", "x")])
        b = _linear_slide("same", [("p", "y")], idx=2)
        self.assertTrue(
            include_slide_heading_vs_previous(
                a, None, include_slide_headings=True
            )
        )
        self.assertFalse(
            include_slide_heading_vs_previous(
                b, a, include_slide_headings=True
            )
        )

    def test_headings_off(self) -> None:
        a = _linear_slide("Same", [("p", "x")])
        self.assertFalse(
            include_slide_heading_vs_previous(
                a, None, include_slide_headings=False
            )
        )


class TestSlideToPostHtmlHeadings(unittest.TestCase):
    def test_second_slide_no_duplicate_heading(self) -> None:
        s1 = _linear_slide("Topic", [("p", "body1")])
        s2 = _linear_slide("topic", [("p", "body2")], idx=2)
        h1 = slide_to_post_html(
            s1,
            include_slide_heading=include_slide_heading_vs_previous(
                s1, None, include_slide_headings=True
            ),
            slide_heading_level=3,
        )
        h2 = slide_to_post_html(
            s2,
            include_slide_heading=include_slide_heading_vs_previous(
                s2, s1, include_slide_headings=True
            ),
            slide_heading_level=3,
        )
        self.assertIn("wp:heading", h1)
        self.assertNotIn("wp:heading", h2)
        self.assertIn("body2", h2)


if __name__ == "__main__":
    unittest.main()
