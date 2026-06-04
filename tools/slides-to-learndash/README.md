# slides-to-learndash

Convert a `.pptx` file (e.g. exported from Google Slides) into CSV for the [LearnDash Bulk Lessons Or Topics](https://github.com/serenichron/learndash-bulk-lessons-or-topics) WordPress plugin. Content is emitted as **WordPress block markup** on a single CSV line per row (compatible with the plugin’s CSV reader).

## Setup

```bash
python3 -m venv .venv
source .venv/bin/activate
pip install -e .
```

## List slide layout names

Provide exactly one `.pptx` as a positional argument or with `--input` / `-i`:

```bash
slides-to-learndash --list-layouts ./deck.pptx
slides-to-learndash --list-layouts --input ./deck.pptx
slides-to-learndash --list-layouts -i ./deck.pptx
```

Use this to choose a `--heading-layout` regex for topic mode.

## Export: one merged lesson (`lesson-only`)

**Default mode** is `lesson-only`. **Default output** is the same path as each input file with a `.csv` extension (e.g. `./deck.pptx` → `./deck.csv`).

Minimal example:

```bash
slides-to-learndash ./deck.pptx
```

Several decks in one run (each gets its own CSV next to the `.pptx`):

```bash
slides-to-learndash ./a.pptx ./b.pptx
```

Optional **`--out` / `-o`** (single input only): override the output CSV path. With multiple inputs, omit `--out` (it is not supported).

Legacy style (positional or `--input`, not both):

```bash
slides-to-learndash --input ./deck.pptx --course-id 123 --out ./build.csv
slides-to-learndash ./deck.pptx --course-id 123 -o ./build.csv
```

Optional: `--lesson-title`, `--slide-headings` / `--no-slide-headings` (default: add each slide’s title as an **H2** before that slide’s body), `--skip-images`, `--media-dir`.

Slide **1** is treated as the cover slide: its content is **not** included in `post_content` (the lesson/topic title can still come from it via `--lesson-title` or the first slide’s title). Slides are **not** separated with `wp:separator` blocks.

When a slide title is emitted as an **H2**, the same title is **dropped** from the start of that slide’s body so it is not repeated as a paragraph.

### Rich text → blocks

Paragraph runs are mapped to HTML inside `wp:paragraph`, list items, and headings: **bold**, *italic*, underline, strikethrough, monospace/`code`, and combined emphasis. Bullet paragraphs become `wp:list` / `wp:list-item`. Paragraphs with larger font sizes (typical subtitle lines) become `wp:heading` level **3** or **4**. All-monospace paragraphs become `wp:code` (pre/code). Images are still `wp:image` with files under `media/`.

## Export: lesson + topics (`lesson-with-topics`)

Slides whose **`slide_layout.name`** matches the regex (Python `re.fullmatch` on the trimmed name) start a new **topic**. All slides **before** the first match are merged into the **lesson** row only.

Writes two files next to the output base path (default: same directory as the input, stem from the input basename):

- `{stem}_lesson.csv`
- `{stem}_topics.csv`

Example:

```bash
slides-to-learndash ./deck.pptx \
  --mode lesson-with-topics \
  --course-id 123 \
  --heading-layout 'SECTION_HEADER|Title - Top_1'
```

With an explicit base path (single file only):

```bash
slides-to-learndash ./deck.pptx \
  --mode lesson-with-topics \
  --course-id 123 \
  -o ./build.csv \
  --heading-layout 'SECTION_HEADER|Title - Top_1'
```

(`./build.csv` supplies the stem `build`, so outputs are `build_lesson.csv` and `build_topics.csv` next to `./build.csv`.)

### Import order

1. Import **`_lesson.csv`** with the bulk plugin, content type **Lessons**, action **Create**.
2. Note the new lesson **post ID** in WordPress.
3. Edit **`_topics.csv`**: set the `lesson_id` column on each topic row to that ID.
4. Import **`_topics.csv`** with content type **Topics**, action **Create**.

**Media folders:** by default, images go under `media/<sanitized_csv_stem>/` beside the output CSV (e.g. `media/Introduction_to_Git/slide_001_img_01.png` in HTML), so different decks do not share one flat `media/` directory. With **multiple** inputs and a shared **`--media-dir`**, each export uses a subfolder named after that CSV stem under that directory so files do not overwrite each other. Override layout with **`--media-dir`** (paths in the CSV are relative to the CSV’s directory when possible). Or use **`--skip-images`**.

## Upload export to server (rsync)

The script [scripts/sync-export-rsync.sh](scripts/sync-export-rsync.sh) uploads CSV files and the `media/` tree to **`~/export`** on a remote host over SSH using **rsync** (`-a` archive, **`-u` / `--update`** so the receiver is not overwritten when its copy is newer, **`-z`** compression). It does **not** use `--delete`.

1. Edit the configuration block at the top of the script: `SSH_USER`, `SSH_HOST`, optional `SSH_PORT` (default `22`), optional `SSH_IDENTITY`, and `REMOTE_EXPORT` (default `export`, i.e. `~/export` on the server).
2. Ensure the remote directory exists once, e.g. `ssh youruser@yourhost 'mkdir -p ~/export'`.
3. From the folder that contains your generated `*.csv` and `media/`:

```bash
chmod +x scripts/sync-export-rsync.sh
cd /path/to/that/folder
/path/to/slides-to-learndash/scripts/sync-export-rsync.sh --dry-run
/path/to/slides-to-learndash/scripts/sync-export-rsync.sh
```

With **no path arguments**, the script syncs all **`*.csv` in the current directory** and **`./media`** if present. You can pass explicit paths instead, e.g. `./MyLesson.csv ./media`. Basenames and the `media/…` subtree are mirrored under `~/export/` on the server.

Avoid committing real credentials in the script if the repository is shared; use placeholders or a private fork.

## Requirements for export

| Input | Required when |
|------|----------------|
| One or more `.pptx` paths (positional) **or** `--input` / `-i` (single file) | Always (except help). Do not pass both. |
| `--mode` | Optional; defaults to **`lesson-only`**. |
| `--course-id` | Optional (empty in CSV if omitted). |
| `--out` / `-o` | Optional; default is `<input>.csv`. **Not allowed** with multiple positional inputs. |
| `--heading-layout` | **`lesson-with-topics` only** (regex). |
