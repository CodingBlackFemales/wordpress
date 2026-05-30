"""Session outline post-process and heading bold stripping."""

from __future__ import annotations

import unittest

from slides_to_learndash import blocks
from slides_to_learndash.content_postprocess import strip_session_outline_columns_keep_learning_objectives
from slides_to_learndash.rich_text import strip_bold_tags_from_inner_html


class TestStripSessionOutlineColumns(unittest.TestCase):
    def test_replaces_session_outline_columns_with_lo_heading_and_list(self) -> None:
        left = blocks.join_blocks(
            [
                blocks.heading_block_html("Session Outline", 3),
                blocks.list_block_html(["Version Control", "Git Commands"]),
            ]
        )
        right = blocks.join_blocks(
            [
                blocks.paragraph_block_html("<strong>Learning Objectives</strong>"),
                blocks.list_block_html(["Understand Git", "Use branches"]),
            ]
        )
        columns = blocks.columns_block_html([left, right], ["50%", "50%"])
        tail = blocks.heading_block_html("Next Section", 3)
        html = blocks.join_blocks([columns, tail])

        out = strip_session_outline_columns_keep_learning_objectives(html)

        self.assertNotIn("<!-- wp:columns -->", out)
        self.assertNotIn("Session Outline</h3>", out)
        self.assertNotIn("Session Outline", out)
        self.assertNotIn("Version Control", out)
        self.assertIn("Understand Git", out)
        self.assertIn("Use branches", out)
        self.assertRegex(out, r'<!-- wp:heading \{"level":2\} --><h2[^>]*>Learning Objectives</h2>')
        self.assertNotRegex(out, r"<h2[^>]*>.*<strong>Learning Objectives</strong>")
        self.assertIn("Next Section", out)

    def test_unrelated_columns_unchanged(self) -> None:
        left = blocks.paragraph_block_html("Left")
        right = blocks.paragraph_block_html("Right")
        columns = blocks.columns_block_html([left, right], ["50%", "50%"])
        out = strip_session_outline_columns_keep_learning_objectives(columns)
        self.assertEqual(out.strip(), columns.strip())

    def test_replaces_columns_when_lo_label_is_heading(self) -> None:
        left = blocks.join_blocks(
            [
                blocks.heading_block_html("Data Types", 3),
                blocks.list_block_html(["Primitive Types", "Wrappers"]),
            ]
        )
        right = blocks.join_blocks(
            [
                blocks.heading_block_html("Learning Objectives", 3),
                blocks.list_block_html(["Practice Java syntax"]),
            ]
        )
        columns = blocks.columns_block_html([left, right], ["50%", "50%"])
        html = blocks.join_blocks([blocks.heading_block_html("Data Types", 3), columns])

        out = strip_session_outline_columns_keep_learning_objectives(html)

        self.assertNotIn("<!-- wp:columns -->", out)
        self.assertNotIn("Java Data Types</h3>", out)
        self.assertNotIn("Primitive Types", out)
        self.assertIn("Practice Java syntax", out)
        self.assertRegex(out, r'<!-- wp:heading \{"level":2\} --><h2[^>]*>Learning Objectives</h2>')


class TestStripBoldFromHeadingInnerHtml(unittest.TestCase):
    def test_unwraps_strong(self) -> None:
        self.assertEqual(strip_bold_tags_from_inner_html("<strong>Foo</strong>"), "Foo")

    def test_unwraps_nested_strong(self) -> None:
        self.assertEqual(
            strip_bold_tags_from_inner_html("<strong><strong>x</strong></strong>"),
            "x",
        )

    def test_preserves_em(self) -> None:
        self.assertEqual(
            strip_bold_tags_from_inner_html("<em>Hi</em> and <strong>Bold</strong>"),
            "<em>Hi</em> and Bold",
        )
