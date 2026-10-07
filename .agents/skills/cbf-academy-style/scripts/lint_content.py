#!/usr/bin/env python3
"""Lint CBF Academy content against the mechanical rules of the style guide.

Reads WordPress block markup (the post_content exported by `wp post get`) or
Markdown, and reports rule breaches with line numbers. Prose checks ignore code,
HTML tags and comments, so code and identifiers keep their own spelling.

Usage:
    lint_content.py FILE [FILE ...] [--type lesson|guide] [--errors-only]
                    [--ignore RULE[,RULE...]]

    --type lesson   Also check the lesson skeleton (objectives, summary, no "What's next").
    --type guide    Also check shared-guide rules (no lesson references, no objectives).
    --errors-only   Hide warnings.
    --ignore        Comma-separated rule IDs to skip, e.g. --ignore long-sentence,just.

Exit status: 0 if no errors, 1 if any errors, 2 on usage or file problems.

Standard library only. Python 3.8+.
"""

import argparse
import html as htmllib
import json
import re
import sys
from dataclasses import dataclass

ERROR = "error"
WARNING = "warning"


@dataclass
class Finding:
    line: int
    severity: str
    rule: str
    message: str
    excerpt: str = ""


# --------------------------------------------------------------------------
# Word lists
# --------------------------------------------------------------------------

# (rule, severity, pattern, message). Patterns are matched against prose only.
# Case-insensitive unless the pattern starts with (?-i).
PROSE_RULES = [
    # Dashes and quotes (section 5, Punctuation)
    ("dash", ERROR, r"[\u2014\u2013]|&mdash;|&ndash;|&#821[12];|(?<=\w) - (?=\w)",
     "Dash used as punctuation. Use a comma, full stop, colon or brackets (or \"to\" for a range)"),
    ("curly-quote", ERROR, r"[\u2018\u2019\u201c\u201d]|&[lr][sd]quo;|&#82(?:1[6-9]|2[01]);",
     "Curly quote. Use straight quotes"),
    # Words to avoid (sections 4 and 5)
    ("belittling", ERROR, r"\b(?:simply|easily|easy|obvious(?:ly)?|of course|trivial(?:ly)?)\b",
     "Belittling word. Delete it, or say what makes the task manageable"),
    ("just", WARNING, r"\bjust\b",
     "\"just\" often belittles. Delete it unless it means \"only\" or \"exactly\""),
    ("filler", ERROR, r"\b(?:in order to|basically|essentially|it is important to note(?: that)?|it's important to note(?: that)?)\b",
     "Filler. Cut it (\"in order to\" becomes \"to\")"),
    ("fancy-word", ERROR, r"\b(?:utilis(?:e|es|ed|ing|ation)|utiliz(?:e|es|ed|ing|ation)|leverag(?:e|es|ed|ing))\b",
     "Use \"use\""),
    ("via", WARNING, r"\bvia\b", "Vague. Use \"using\", \"through\" or \"by\""),
    ("etc", ERROR, r"\betc\b\.?", "Finish the list or say \"and others\""),
    ("see-below", ERROR, r"\b(?:see |shown |listed |as )?(?:below|above)(?= *[.,:;)]|\s*$)|\bsee (?:below|above)\b|\bthe (?:above|below)\b",
     "Positional reference. Use \"the following\", \"earlier in this lesson\" or a link"),
    ("please", WARNING, r"\bplease\b", "Avoid \"please\" in instructions. State the action"),
    ("latin-abbrev", WARNING, r"\b(?:e\.g\.|i\.e\.)",
     "Write \"for example\" or \"that is\" in prose (\"e.g.\" is acceptable in tables)"),
    # Word list (section 16). Case-sensitive.
    ("word-list", ERROR, r"(?-i:\bLookerML\b)", "Write \"LookML\""),
    ("word-list", ERROR, r"(?-i:\bJavascript\b|\bjavascript\b)", "Write \"JavaScript\""),
    ("word-list", ERROR, r"(?-i:\bTypescript\b|\btypescript\b)", "Write \"TypeScript\""),
    ("word-list", ERROR, r"(?-i:\bVSCode\b|\bVScode\b|\bVsCode\b)", "Write \"VS Code\""),
    ("word-list", ERROR, r"(?-i:\bGithub\b|\bgithub\b(?!\.))", "Write \"GitHub\""),
    ("word-list", ERROR, r"(?-i:\bBigquery\b|\bBig Query\b|\bbigquery\b)", "Write \"BigQuery\""),
    ("word-list", ERROR, r"(?-i:\bDBT\b|\bDbt\b)", "Write \"dbt\" (always lowercase; rewrite the sentence if it starts one)"),
    ("word-list", ERROR, r"(?-i:\bNodeJS\b|\bNodejs\b|\bNode JS\b|\bnodejs\b)", "Write \"Node.js\""),
    ("word-list", ERROR, r"(?-i:\bNPM\b)", "Write \"npm\""),
    ("word-list", ERROR, r"(?-i:\bMacOS\b|\bMac OS\b|\bOSX\b|\bOS X\b)", "Write \"macOS\""),
    ("word-list", ERROR, r"(?-i:\bMySql\b|\bMYSQL\b|\bmysql\b)", "Write \"MySQL\""),
    ("word-list", ERROR, r"(?-i:\bPostgreSql\b|\bPostgresql\b|\bPostgresSQL\b)", "Write \"PostgreSQL\""),
    ("word-list", ERROR, r"(?-i:\bjinja\b)", "Write \"Jinja\""),
    ("word-list", ERROR, r"(?-i:(?<![./\w-])git\b)(?![-./_])", "Write \"Git\" for the tool (use inline code for the `git` command)"),
    ("word-list", WARNING, r"(?-i:\bGCP\b)", "Write \"Google Cloud\" on beginner courses, or expand \"Google Cloud Platform (GCP)\" first"),
    ("word-list", ERROR, r"\be-mail\b", "Write \"email\""),
    ("word-list", ERROR, r"\bfilenames?\b", "Write \"file name\""),
    ("word-list", ERROR, r"\bdata sets?\b", "Write \"dataset\""),
    ("word-list", ERROR, r"\bweb sites?\b|\bon-line\b", "Write \"website\" / \"online\""),
    ("word-list", WARNING, r"(?-i:(?<![.!?] )(?<!^)\bInternet\b)", "Write \"internet\" (lowercase)"),
    ("word-list", ERROR, r"\bopen-source\b", "Write \"open source\" (no hyphen)"),
    ("word-list", WARNING, r"\b(?:backend|frontend)\b",
     "Write \"back end\" / \"front end\" (noun) or \"back-end\" / \"front-end\" (adjective)"),
    ("word-list", WARNING, r"\b(?:the|a|our|your) Academy\b", "Write \"CBF Academy\" on first use, then \"the academy\""),
    ("word-list", WARNING, r"\bfinal project\b", "Write \"capstone project\""),
    # Inclusive language (section 6)
    ("inclusive", ERROR, r"\bmaster branch\b", "Write \"main branch\""),
    ("inclusive", WARNING, r"\bmaster\b",
     "Use \"main\" or \"primary\". If a tool still uses the old term, say so once"),
    ("inclusive", ERROR, r"\bslaves?\b", "Write \"replica\""),
    ("inclusive", ERROR, r"\b(?:white|black)[- ]?lists?(?:ed|ing)?\b", "Write \"allowlist\" / \"denylist\""),
    ("inclusive", ERROR, r"\bdumm(?:y|ies)\b", "Write \"placeholder value\" or \"sample data\""),
    ("inclusive", ERROR, r"\bsanity[- ]checks?\b", "Write \"quick check\" or \"confidence check\""),
    ("inclusive", ERROR, r"\bout[- ]of[- ]the[- ]box\b", "Write \"built-in\" or \"native\""),
    ("inclusive", ERROR, r"\bguys\b", "Do not address a group as \"guys\""),
    ("inclusive", ERROR, r"\bman[- ]?(?:hours?|days?|power)\b|\bmanpower\b",
     "Use a gender-neutral term (\"person-hours\", \"staff\", \"workforce\")"),
    # Point of view (section 4)
    ("point-of-view", WARNING, r"\bthe (?:student|trainee|user)s?\b|\b(?:students|trainees)\b",
     "Address the learner as \"you\". Use \"learners\" only when talking about the group"),
    # Loose coupling (section 7)
    ("coupling", WARNING,
     r"\b(?:session|week|day|module|lesson|part|unit)\s+(?:\d+|one|two|three|four|five|six)\b"
     r"|\b(?:module|lesson|part|session)\s+(?-i:[IVX]{1,4})\b"
     r"|\b(?:next|previous|last|following|earlier|later|upcoming) (?:lesson|module|session|week|topic)s?\b"
     r"|\bin the [A-Z][\w.]* module\b|\blater in (?:the|this) course\b",
     "Names another lesson, module or session. Describe prior knowledge generically, never by position"),
    ("backreference", WARNING,
     r"\bas (?:we )?(?:introduced|explained|defined|discussed|mentioned|seen|shown) (?:earlier|before|previously|above)\b"
     r"|\bfrom the previous (?:topic|section)\b",
     "Once a term is explained, use it plainly without pointing back to where it was introduced"),
]

# American spellings (section 5). Warnings, because product names and quotes may use them.
AMERICAN_IZE = re.compile(
    r"\b([A-Za-z]{3,})(iz(?:e|es|ed|ing|ation|ations|er|ers)|yz(?:e|es|ed|ing|er|ers))\b"
)
IZE_EXCEPTIONS = {
    "resize", "resized", "resizes", "resizing", "capsize", "capsized", "citizen",
    "citizens", "baptize", "seized", "seizes", "seizing", "prized", "sized",
    "oversized", "undersized", "downsize", "downsized", "downsizing", "upsize",
}
AMERICAN_LIST = re.compile(
    r"\b(colors?|colored|coloring|behaviors?|behavioral|favorites?|honors?|labors?|neighbors?"
    r"|centers?|centered|centering|modeling|modeled|modeler|labeled|labeling|traveled|traveling"
    r"|canceled|canceling|catalogs?|cataloged|gray|enroll(?:s|ment)?|fulfill(?:s|ment)?|artifacts?"
    r"|defense|offense|practicing|practiced"
    r"|judgment|aging|skeptical|analog)\b",
    re.IGNORECASE,
)
BRITISH_HINT = {
    "color": "colour", "behavior": "behaviour", "favorite": "favourite", "honor": "honour",
    "labor": "labour", "neighbor": "neighbour", "center": "centre", "modeling": "modelling",
    "modeled": "modelled", "modeler": "modeller", "labeled": "labelled", "labeling": "labelling",
    "traveled": "travelled", "traveling": "travelling", "canceled": "cancelled",
    "canceling": "cancelling", "catalog": "catalogue", "gray": "grey", "enroll": "enrol",
    "fulfill": "fulfil", "artifact": "artefact", "defense": "defence", "offense": "offence",
    "practicing": "practising", "practiced": "practised", "judgment": "judgement",
    "aging": "ageing", "skeptical": "sceptical", "analog": "analogue",
}

EMOJI = re.compile(
    "[\U0001F300-\U0001FAFF\U0001F600-\U0001F64F\U0001F680-\U0001F6FF\u2600-\u27BF\u2B50\u2B55]"
)

ALLOWED_LANGUAGES = {
    "java", "python", "javascript", "typescript", "bash", "powershell", "sql",
    "html", "css", "json", "yaml", "text",
}
LANGUAGE_HINTS = {
    "sh": "bash", "shell": "bash", "zsh": "bash", "console": "bash", "shellsession": "bash",
    "plaintext": "text", "plain": "text", "txt": "text", "output": "text",
    "jinja": "sql", "jinja2": "sql", "js": "javascript", "ts": "typescript",
    "yml": "yaml", "ps1": "powershell", "py": "python",
}
CALLOUT_LABELS = {"Note", "Tip", "Warning", "Try it", "Reflect"}
WEAK_OBJECTIVE_VERBS = {"understand", "learn", "know", "appreciate", "be", "grasp", "gain", "become"}

# Words that may be capitalised mid-heading without making it title case.
PROPER_NOUNS = {
    "BigQuery", "GoogleSQL", "Git", "GitHub", "Google", "Cloud", "Jinja", "JavaScript",
    "TypeScript", "LookML", "Looker", "Studio", "macOS", "Windows", "Linux", "MySQL",
    "PostgreSQL", "Node.js", "Python", "SQL", "VS", "Code", "Slack", "Zoom", "ClearFeed",
    "Java", "Spring", "Boot", "React", "Docker", "Netlify", "HTML", "CSS", "JSON", "YAML",
    "API", "APIs", "REST", "CLI", "CTE", "CTEs", "ETL", "ELT", "SCD", "ID", "IDs", "CI/CD",
    "CBF", "Academy", "LearnDash", "WordPress", "PowerShell", "Bash", "Kubernetes", "AWS",
    "Azure", "I", "II", "III", "IV", "V", "VI", "UK", "Excel", "Sheets", "Tableau", "Power",
    "BI", "Jest", "Vite", "Maven", "Gradle", "IntelliJ", "IDEA", "JDK", "JVM", "npm", "dbt",
    "Core", "Hub", "Analytics", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday",
}


# --------------------------------------------------------------------------
# Helpers
# --------------------------------------------------------------------------

def blank(match):
    """Replace a match with spaces, keeping newlines so line numbers survive."""
    return re.sub(r"[^\n]", " ", match.group(0))


def line_of(text, pos):
    return text.count("\n", 0, pos) + 1


def strip_tags(s):
    return htmllib.unescape(re.sub(r"<[^>]+>", "", s)).strip()


def excerpt_at(prose, start, end, width=40):
    line_start = prose.rfind("\n", 0, start) + 1
    line_end = prose.find("\n", end)
    if line_end == -1:
        line_end = len(prose)
    a = max(line_start, start - width)
    b = min(line_end, end + width)
    snippet = re.sub(r"\s+", " ", prose[a:b]).strip()
    return ("..." if a > line_start else "") + snippet + ("..." if b < line_end else "")


def make_prose(text, keep_urls=False):
    """Mask everything that is not learner-facing prose."""
    masked = text
    masked = re.sub(r"<!--.*?-->", blank, masked, flags=re.S)
    masked = re.sub(r"<pre\b.*?</pre>", blank, masked, flags=re.S | re.I)
    masked = re.sub(r"^(```|~~~).*?^\1[ \t]*$", blank, masked, flags=re.S | re.M)
    masked = re.sub(r"<(code|kbd|samp|var)\b[^>]*>.*?</\1>", blank, masked, flags=re.S | re.I)
    masked = re.sub(r"`[^`\n]+`", blank, masked)
    masked = re.sub(r"<[^>]+>", blank, masked)
    # Markdown link targets and bare URLs are not prose.
    masked = re.sub(r"\]\([^)]*\)", blank, masked)
    if not keep_urls:
        masked = re.sub(r"https?://\S+", blank, masked)
    # Placeholders like <your-username> written as entities.
    masked = re.sub(r"&lt;[\w-]+&gt;", blank, masked)
    return masked


def iter_headings(text):
    """Yield (pos, level, inner_text) for HTML and Markdown headings."""
    for m in re.finditer(r"<h([1-6])\b[^>]*>(.*?)</h\1>", text, flags=re.S | re.I):
        yield m.start(), int(m.group(1)), strip_tags(m.group(2))
    for m in re.finditer(r"^(#{1,6})[ \t]+(.+?)[ \t#]*$", text, flags=re.M):
        if not in_code(text, m.start()):
            yield m.start(), len(m.group(1)), m.group(2).strip()


_code_spans_cache = {}


def in_code(text, pos):
    key = id(text)
    if key not in _code_spans_cache:
        spans = [(m.start(), m.end()) for m in re.finditer(r"^(```|~~~).*?^\1[ \t]*$", text, flags=re.S | re.M)]
        spans += [(m.start(), m.end()) for m in re.finditer(r"<pre\b.*?</pre>", text, flags=re.S | re.I)]
        _code_spans_cache[key] = spans
    return any(a <= pos < b for a, b in _code_spans_cache[key])


def looks_title_case(heading):
    words = re.findall(r"[A-Za-z][\w.'/-]*", heading)
    if len(words) < 3:
        return False
    rest = [w for w in words[1:] if len(w) > 3]
    capped = [w for w in rest if w[0].isupper() and w not in PROPER_NOUNS and not w.isupper()]
    return len(capped) >= 2 and len(capped) * 2 >= len(rest)


# --------------------------------------------------------------------------
# Checks
# --------------------------------------------------------------------------

def check_prose(text, prose, out):
    for rule, severity, pattern, message in PROSE_RULES:
        flags = re.IGNORECASE | re.MULTILINE
        for m in re.finditer(pattern, prose, flags):
            out.append(Finding(line_of(prose, m.start()), severity, rule, message,
                               excerpt_at(prose, m.start(), m.end())))

    for m in AMERICAN_IZE.finditer(prose):
        word = m.group(0)
        if word.lower() in IZE_EXCEPTIONS:
            continue
        british = m.group(1) + m.group(2).replace("iz", "is", 1).replace("yz", "ys", 1)
        out.append(Finding(line_of(prose, m.start()), WARNING, "spelling",
                           f"American spelling? Write \"{british}\" unless it is a product name or quote",
                           excerpt_at(prose, m.start(), m.end())))
    for m in AMERICAN_LIST.finditer(prose):
        word = m.group(0)
        stem = next((k for k in BRITISH_HINT if word.lower().startswith(k)), None)
        hint = f" (\"{BRITISH_HINT[stem]}...\")" if stem else ""
        out.append(Finding(line_of(prose, m.start()), WARNING, "spelling",
                           f"American spelling{hint}, unless it is a product name or quote",
                           excerpt_at(prose, m.start(), m.end())))

    for m in EMOJI.finditer(prose):
        out.append(Finding(line_of(prose, m.start()), ERROR, "emoji",
                           "No emoji in lesson body text or headings", excerpt_at(prose, m.start(), m.end())))

    bangs = [m for m in re.finditer(r"(?<=\w)!(?!=)", prose)]
    if len(bangs) > 1:
        for m in bangs[1:]:
            out.append(Finding(line_of(prose, m.start()), WARNING, "exclamation",
                               "At most one exclamation mark per lesson", excerpt_at(prose, m.start(), m.end())))

    for m in re.finditer(r"https?://\S+", make_prose(text, keep_urls=True)):
        out.append(Finding(line_of(text, m.start()), WARNING, "bare-url",
                           "Bare URL in body text. Use descriptive link text", m.group(0)[:60]))

    # Long sentences: guide target is under 25 words; flag the clear outliers.
    for para in re.finditer(r"[^\n]+", prose):
        for sent in re.finditer(r"[^.?!:;]+[.?!]", para.group(0)):
            words = re.findall(r"\b\w[\w'-]*\b", sent.group(0))
            if len(words) > 30:
                pos = para.start() + sent.start()
                out.append(Finding(line_of(prose, pos), WARNING, "long-sentence",
                                   f"{len(words)}-word sentence. Aim for under 25; split it",
                                   sent.group(0).strip()[:80] + "..."))


def check_headings(text, out):
    seen = {}
    h4_count = 0
    question_count = 0
    for pos, level, inner in iter_headings(text):
        line = line_of(text, pos)
        if level == 1:
            out.append(Finding(line, ERROR, "h1", "No H1 in the body. The post title is the only H1", inner))
        if level == 4:
            h4_count += 1
        if level >= 5:
            out.append(Finding(line, ERROR, "heading-depth", "H5 and H6 are not used. Restructure the section", inner))
        if inner.endswith("."):
            out.append(Finding(line, ERROR, "heading-punctuation", "No full stop at the end of a heading", inner))
        if inner.endswith("?"):
            question_count += 1
            if question_count > 1:
                out.append(Finding(line, WARNING, "heading-question",
                                   "Only one question heading per lesson. Name the topic instead", inner))
        if "&" in inner or "&amp;" in inner:
            out.append(Finding(line, ERROR, "heading-ampersand", "Write \"and\", not \"&\"", inner))
        if re.match(r"^\d+[.)]\s", inner):
            out.append(Finding(line, ERROR, "heading-numbered", "Do not number headings (except procedure steps)", inner))
        if re.fullmatch(r"(?i)(what'?s|what is) (next|coming up)\??|next steps?|coming up next|looking ahead", inner.replace("’", "'")):
            out.append(Finding(line, ERROR, "coupling",
                               "No \"What's next\" section. It couples the lesson to what follows it in one course; "
                               "refer to attached topics from the body instead", inner))
        if looks_title_case(inner):
            out.append(Finding(line, WARNING, "sentence-case", "Heading looks like title case. Use sentence case", inner))
        key = inner.lower()
        if key in seen:
            out.append(Finding(line, ERROR, "heading-duplicate",
                               f"Duplicate heading (also line {seen[key]}). Keep headings unique", inner))
        else:
            seen[key] = line
    if h4_count == 1:
        out.append(Finding(1, WARNING, "h4-single",
                           "Only one H4 in the document. An H4 section needs at least two, or should be an H3", ""))

    # Back-to-back headings, block markup.
    pattern = r"</h[1-6]>\s*(?:<!--\s*/wp:heading\s*-->\s*)(?:<!--\s*wp:heading\b[^>]*-->\s*)<h[1-6]\b[^>]*>(.*?)</h"
    for m in re.finditer(pattern, text, flags=re.S | re.I):
        out.append(Finding(line_of(text, m.start(1)), ERROR, "stacked-headings",
                           "Two headings back to back. Put at least one sentence between them", strip_tags(m.group(1))))
    # Back-to-back headings, Markdown.
    for m in re.finditer(r"^#{1,6} .+\n(?:[ \t]*\n)*(#{1,6} .+)$", text, flags=re.M):
        if not in_code(text, m.start()):
            out.append(Finding(line_of(text, m.start(1)), ERROR, "stacked-headings",
                               "Two headings back to back. Put at least one sentence between them", m.group(1)))


def check_code(text, out):
    for m in re.finditer(r"<!--\s*wp:code(\s+\{.*?\})?\s*-->(.*?)<!--\s*/wp:code\s*-->", text, flags=re.S):
        line = line_of(text, m.start())
        try:
            attrs = json.loads(m.group(1)) if m.group(1) else {}
        except json.JSONDecodeError:
            out.append(Finding(line, ERROR, "code-attrs", "Code block attributes are not valid JSON", m.group(1).strip()))
            attrs = {}
        lang = attrs.get("language")
        body_m = re.search(r"<code[^>]*>(.*?)</code>", m.group(2), flags=re.S)
        body = body_m.group(1) if body_m else ""
        first = body.strip().splitlines()[0][:60] if body.strip() else ""
        if not lang:
            out.append(Finding(line, ERROR, "code-language", "Code block has no language. Set \"language\"", first))
        elif lang not in ALLOWED_LANGUAGES:
            hint = LANGUAGE_HINTS.get(lang.lower())
            msg = f"Language \"{lang}\" is not in the guide's list" + (f". Use \"{hint}\"" if hint else "")
            out.append(Finding(line, WARNING, "code-language", msg, first))
        n_lines = len(body.strip("\n").splitlines())
        if n_lines > 5 and not attrs.get("lineNumbers"):
            out.append(Finding(line, WARNING, "code-line-numbers",
                               f"{n_lines}-line block without line numbers. Set \"lineNumbers\":true", first))
        if attrs.get("highlightLines") and not attrs.get("lineNumbers"):
            out.append(Finding(line, ERROR, "code-line-numbers", "Annotated block must have line numbers on", first))
        if n_lines > 30:
            out.append(Finding(line, WARNING, "code-length",
                               f"{n_lines}-line block. Keep examples under about 30 lines", first))
        if (lang or "bash") in {"bash", "powershell", "sh", "shell", "zsh", "console"}:
            for i, code_line in enumerate(body.splitlines()):
                if re.match(r"^\s*(?:\$|%|&gt;|>|PS[ >]|C:\\.*&gt;)\s", code_line):
                    out.append(Finding(line + i, ERROR, "code-prompt",
                                       "Prompt character in a command block. Remove it so copy and paste works",
                                       code_line.strip()[:60]))
    # Markdown fences.
    for m in re.finditer(r"^(```|~~~)[ \t]*$\n.*?^\1[ \t]*$", text, flags=re.S | re.M):
        out.append(Finding(line_of(text, m.start()), ERROR, "code-language", "Code fence has no language", ""))

    # Annotations.
    for m in re.finditer(r"<ol\b[^>]*\bcode-annotations\b[^>]*>(.*?)</ol>", text, flags=re.S | re.I):
        items = re.findall(r"<li\b([^>]*)>", m.group(1), flags=re.I)
        line = line_of(text, m.start())
        if len(items) > 5:
            out.append(Finding(line, ERROR, "annotations",
                               f"{len(items)} annotations. Five at most; split the block or move the explanation to prose"))
        for attrs in items:
            if "data-line" not in attrs:
                out.append(Finding(line, ERROR, "annotations", "Annotation item missing data-line attribute"))


def check_images(text, out):
    # Enlarge on click (lightbox) for unlinked content images.
    for m in re.finditer(r"<!--\s*wp:image(\s+\{.*?\})?\s*-->(.*?)<!--\s*/wp:image\s*-->", text, flags=re.S):
        try:
            attrs = json.loads(m.group(1)) if m.group(1) else {}
        except json.JSONDecodeError:
            attrs = {}
        linked = attrs.get("linkDestination", "none") != "none" or re.search(r"<a\b", m.group(2), flags=re.I)
        if not linked and not (attrs.get("lightbox") or {}).get("enabled"):
            src = re.search(r'src="([^"]*)"', m.group(2))
            out.append(Finding(line_of(text, m.start()), WARNING, "enlarge-on-click",
                               "Turn on Enlarge on click (\"lightbox\":{\"enabled\":true}) unless the image is "
                               "legible at its displayed size", src.group(1).rsplit("/", 1)[-1] if src else ""))
    for m in re.finditer(r"<img\b[^>]*>", text, flags=re.I):
        line = line_of(text, m.start())
        src = re.search(r'src="([^"]*)"', m.group(0))
        name = src.group(1).rsplit("/", 1)[-1] if src else ""
        alt = re.search(r'alt="([^"]*)"', m.group(0))
        if not alt:
            out.append(Finding(line, ERROR, "alt-text", "Image has no alt text", name))
            continue
        a = alt.group(1).strip()
        if not a:
            out.append(Finding(line, WARNING, "alt-text",
                               "Empty alt text. Remove decorative images instead of keeping them", name))
        elif re.match(r"(?i)(an? )?(image|screenshot|picture|photo|graphic) (of|showing)", a):
            out.append(Finding(line, ERROR, "alt-text", "Alt text should not start with \"image of\" or \"screenshot of\"", a))
        elif len(a) > 125:
            out.append(Finding(line, WARNING, "alt-text", f"Alt text is {len(a)} characters. Aim for under 125", a[:60] + "..."))
        if re.search(r"(?i)screenshot[ _-]?\d{4}|image\d*\.(png|jpe?g)$|slide_\d+_img", name):
            out.append(Finding(line, WARNING, "image-file-name",
                               "Non-descriptive image file name. Use kebab-case that says what it shows", name))


OS_TAB_ORDER = ["macOS", "Windows", "Ubuntu"]
CHECKPOINT_EMOJI = set("\U0001F6A6\U0001F534\U0001F7E1\U0001F7E2")


def check_patterns(text, out):
    """Section 12: synced Learning checkpoint and the OS tabs pattern."""
    for m in re.finditer(r"How Are You Feeling\??", text, flags=re.I):
        out.append(Finding(line_of(text, m.start()), ERROR, "pattern-copy",
                           "Pasted copy of the Learning checkpoint pattern. Replace the whole group with the "
                           "synced reference <!-- wp:block {\"ref\":<id>} /-->", m.group(0)))
    for m in re.finditer(r"<!--\s*wp:themeisle-blocks/tabs\b(?!-item).*?<!--\s*/wp:themeisle-blocks/tabs\s*-->",
                         text, flags=re.S):
        line = line_of(text, m.start())
        block = m.group(0)
        titles = re.findall(r'wp:themeisle-blocks/tabs-item\s+\{[^}]*"title":"([^"]*)"', block)
        if len(titles) == 1:
            out.append(Finding(line, WARNING, "os-tabs",
                               "Tabs with a single tab. If only one operating system applies, drop the tabs "
                               "and say so in the title or requirements", titles[0]))
        unknown = [t for t in titles if t not in OS_TAB_ORDER]
        if unknown:
            out.append(Finding(line, WARNING, "os-tabs",
                               "OS tabs hold macOS, Windows and Ubuntu. Check that this tab belongs here",
                               ", ".join(unknown)))
        known = [t for t in titles if t in OS_TAB_ORDER]
        if known != sorted(known, key=OS_TAB_ORDER.index):
            out.append(Finding(line, WARNING, "os-tabs", "Keep OS tabs in the order macOS, Windows, Ubuntu",
                               " | ".join(titles)))
        for p in re.finditer(r"This is just a placeholder", block, flags=re.I):
            out.append(Finding(line_of(text, m.start() + p.start()), ERROR, "pattern-placeholder",
                               "Pattern placeholder text left in a tab. Replace it, or delete the tab",
                               "This is just a placeholder..."))


def check_callouts(text, out):
    for m in re.finditer(r'<div\b[^>]*class="[^"]*\bcbf-callout\b[^"]*"[^>]*>', text, flags=re.I):
        line = line_of(text, m.start())
        end = text.find("<!-- /wp:group -->", m.end())
        region = text[m.end(): end if end != -1 else len(text)]
        first_p = re.search(r"<p\b[^>]*>(.*?)</p>", region, flags=re.S | re.I)
        label_m = re.match(r"\s*<strong>(.*?)</strong>\s*$", first_p.group(1), flags=re.S) if first_p else None
        if not label_m or label_m.group(1).strip() not in CALLOUT_LABELS:
            found = strip_tags(first_p.group(1))[:40] if first_p else ""
            out.append(Finding(line, ERROR, "callout-label",
                               "Callout must open with a bold label on its own line: Note, Tip, Warning, Try it or Reflect",
                               found))
        if re.search(r"<h[1-6]\b|wp:code|cbf-callout", region, flags=re.I):
            out.append(Finding(line, ERROR, "callout-content",
                               "No headings, code blocks or nested callouts inside a callout"))
    first_block = re.search(r"<!--\s*wp:(\S+)(.*?)-->", text, flags=re.S)
    if first_block and first_block.group(1) == "group" and "cbf-callout" in first_block.group(2):
        out.append(Finding(line_of(text, first_block.start()), ERROR, "callout-position",
                           "Do not open a lesson or topic with a callout. Say what it is first"))


def check_links(text, out):
    for m in re.finditer(r"<a\b[^>]*>(.*?)</a>", text, flags=re.S | re.I):
        label = strip_tags(m.group(1)).lower().strip(" .")
        if label in {"here", "click here", "this link", "link", "this", "this page", "read more", "more"}:
            out.append(Finding(line_of(text, m.start()), ERROR, "link-text",
                               "Link text must say where the link goes", strip_tags(m.group(1))))
    for m in re.finditer(r"\[(here|click here|this link|link)\]\(", text, flags=re.I):
        out.append(Finding(line_of(text, m.start()), ERROR, "link-text", "Link text must say where the link goes", m.group(1)))


def section_after(text, heading_re):
    """Return (pos, html) of the content between a matching heading and the next heading."""
    hs = list(iter_headings(text))
    for i, (pos, level, inner) in enumerate(hs):
        if re.fullmatch(heading_re, inner, flags=re.I):
            end = hs[i + 1][0] if i + 1 < len(hs) else len(text)
            return pos, text[pos:end]
    return None, None


def list_items(html):
    items = re.findall(r"<li\b[^>]*>(.*?)</li>", html, flags=re.S | re.I)
    if not items:
        items = re.findall(r"^\s*[-*]\s+(.+)$", html, flags=re.M)
    return [strip_tags(i) for i in items]


def check_objectives(text, out):
    pos, html = section_after(text, r"learning objectives")
    if pos is None:
        return
    items = list_items(html)
    line = line_of(text, pos)
    if not 3 <= len(items) <= 6:
        out.append(Finding(line, WARNING, "objectives-count", f"{len(items)} learning objectives. Use three to six"))
    for item in items:
        first = re.findall(r"[A-Za-z]+", item)[:1]
        if first and first[0].lower() in WEAK_OBJECTIVE_VERBS:
            out.append(Finding(line, ERROR, "objective-verb",
                               "Objective must start with an observable verb (explain, write, run, compare...)", item[:60]))
    if "by the end of this lesson, you will be able to" not in strip_tags(html).lower():
        out.append(Finding(line, WARNING, "objectives-lead",
                           "Introduce objectives with \"By the end of this lesson, you will be able to:\""))


def check_lesson(text, out):
    titles = [inner.lower() for _, level, inner in iter_headings(text) if level == 2]
    for required in ("learning objectives", "summary"):
        if required not in titles:
            out.append(Finding(1, ERROR, "skeleton", f"Lesson has no \"{required.capitalize()}\" H2 section"))
    first_block = re.search(r"<!--\s*wp:(\S+)", text)
    if first_block and first_block.group(1) == "heading":
        out.append(Finding(line_of(text, first_block.start()), WARNING, "skeleton",
                           "Lesson should open with introduction paragraphs, not a heading"))
    pos, html = section_after(text, r"summary")
    if pos is not None:
        n = len(list_items(html))
        if not 3 <= n <= 6:
            out.append(Finding(line_of(text, pos), WARNING, "summary-count", f"Summary has {n} bullets. Use three to six"))


def check_guide(text, prose, out):
    for m in re.finditer(r"\bthis (?:lesson|course|module|topic)\b", prose, flags=re.I):
        out.append(Finding(line_of(prose, m.start()), ERROR, "guide-coupling",
                           "A shared guide says \"this guide\" and never refers to its lesson, course or topic",
                           excerpt_at(prose, m.start(), m.end())))
    for _, level, inner in iter_headings(text):
        if inner.lower() in {"learning objectives", "summary", "further reading"}:
            out.append(Finding(1, ERROR, "guide-skeleton",
                               f"Shared guides have no \"{inner}\" section. Those belong to the lesson"))


# --------------------------------------------------------------------------
# Main
# --------------------------------------------------------------------------

def lint(text, content_type=None):
    prose = make_prose(text)
    out = []
    check_prose(text, prose, out)
    check_headings(text, out)
    check_code(text, out)
    check_images(text, out)
    check_callouts(text, out)
    check_links(text, out)
    check_patterns(text, out)
    check_objectives(text, out)
    if content_type == "lesson":
        check_lesson(text, out)
    elif content_type == "guide":
        check_guide(text, prose, out)
    if any(f.rule == "pattern-copy" for f in out):
        out = [f for f in out if not (f.rule == "emoji" and set(f.excerpt) & CHECKPOINT_EMOJI)]
    out = [f for f in out if not (f.rule == "just" and "just a placeholder" in f.excerpt)]
    # De-duplicate (overlapping patterns can hit the same span).
    unique = {(f.line, f.rule, f.message, f.excerpt): f for f in out}
    return sorted(unique.values(), key=lambda f: (f.line, f.severity != ERROR, f.rule))


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__.split("\n\n")[0])
    parser.add_argument("files", nargs="+")
    parser.add_argument("--type", choices=["lesson", "guide"], dest="content_type")
    parser.add_argument("--errors-only", action="store_true")
    parser.add_argument("--ignore", default="", help="comma-separated rule IDs to skip")
    args = parser.parse_args(argv)
    ignored = {r.strip() for r in args.ignore.split(",") if r.strip()}

    total_errors = total_warnings = 0
    for path in args.files:
        try:
            with open(path, encoding="utf-8") as fh:
                text = fh.read()
        except OSError as exc:
            print(f"{path}: cannot read file: {exc}", file=sys.stderr)
            return 2
        _code_spans_cache.clear()
        findings = [f for f in lint(text, args.content_type) if f.rule not in ignored]
        if args.errors_only:
            findings = [f for f in findings if f.severity == ERROR]
        for f in findings:
            ex = f' "{f.excerpt}"' if f.excerpt else ""
            print(f"{path}:{f.line}: {f.severity} [{f.rule}] {f.message}{ex}")
        errors = sum(f.severity == ERROR for f in findings)
        warnings = len(findings) - errors
        total_errors += errors
        total_warnings += warnings

    print(f"\n{total_errors} error(s), {total_warnings} warning(s) in {len(args.files)} file(s).")
    if total_errors:
        print("Fix every error. Review each warning: some are false positives (see SKILL.md).")
    return 1 if total_errors else 0


if __name__ == "__main__":
    sys.exit(main())
