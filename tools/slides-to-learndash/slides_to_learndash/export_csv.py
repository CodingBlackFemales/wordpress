"""Write LearnDash bulk-plugin compatible CSV (single-line rows)."""

from __future__ import annotations

import csv
import io
from pathlib import Path
from typing import Any

# Matches [bulk_template.csv](wordpress/.../bulk_template.csv) core columns used by create_content.
DEFAULT_HEADERS = [
    "ID",
    "post_type",
    "post_title",
    "post_content",
    "course_id",
    "lesson_id",
    "quiz_id",
]


def _row_dict_to_list(headers: list[str], row: dict[str, Any]) -> list[str]:
    return [str(row.get(h, "") or "") for h in headers]


def write_csv(
    path: Path,
    rows: list[dict[str, Any]],
    *,
    headers: list[str] | None = None,
) -> None:
    headers = headers or DEFAULT_HEADERS
    path.parent.mkdir(parents=True, exist_ok=True)
    buf = io.StringIO()
    writer = csv.writer(buf, quoting=csv.QUOTE_MINIMAL, lineterminator="\n")
    writer.writerow(headers)
    for row in rows:
        writer.writerow(_row_dict_to_list(headers, row))
    path.write_text(buf.getvalue(), encoding="utf-8")
