#!/usr/bin/env bash
# Export CBF Academy diagram sources (SVG) as 2x lossless WebP images.
#
# Renders each SVG at twice its size with the bundled Atkinson Hyperlegible
# fonts, so text matches lesson content without installing fonts system-wide,
# then prints the 1x width and height to set on the image block.
#
# Usage:
#   export_diagram.sh <diagram.svg> [<diagram.svg> ...] [--out <dir>]
#
#   --out <dir>  Write the WebP files here (default: next to each SVG).
#
# Requires rsvg-convert (librsvg) and ImageMagick 7 (magick) with WebP support.
# On macOS: brew install librsvg imagemagick

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FONT_DIR="$(cd "$SCRIPT_DIR/../assets/fonts" && pwd)"

for tool in rsvg-convert magick; do
	if ! command -v "$tool" >/dev/null 2>&1; then
		echo "error: $tool not found. On macOS: brew install librsvg imagemagick" >&2
		exit 2
	fi
done

OUT_DIR=""
SOURCES=()
while [[ $# -gt 0 ]]; do
	case "$1" in
		--out) OUT_DIR="${2:?--out needs a directory}"; shift 2 ;;
		-h|--help) sed -n '2,15p' "$0"; exit 0 ;;
		*) SOURCES+=("$1"); shift ;;
	esac
done
if [[ ${#SOURCES[@]} -eq 0 ]]; then
	echo "usage: $(basename "$0") <diagram.svg> [...] [--out <dir>]" >&2
	exit 2
fi

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

# A fontconfig file that adds the bundled fonts to the system ones.
cat > "$WORK/fonts.conf" <<EOF
<?xml version="1.0"?>
<!DOCTYPE fontconfig SYSTEM "urn:fontconfig:fonts.dtd">
<fontconfig>
  <include ignore_missing="yes">/etc/fonts/fonts.conf</include>
  <include ignore_missing="yes">/opt/homebrew/etc/fonts/fonts.conf</include>
  <include ignore_missing="yes">/usr/local/etc/fonts/fonts.conf</include>
  <dir>$FONT_DIR</dir>
  <cachedir>$WORK/cache</cachedir>
</fontconfig>
EOF
export FONTCONFIG_FILE="$WORK/fonts.conf"
# On macOS, Pango defaults to CoreText, which ignores fontconfig and the bundled fonts.
export PANGOCAIRO_BACKEND=fc

for svg in "${SOURCES[@]}"; do
	if [[ ! -f "$svg" ]]; then
		echo "error: $svg not found" >&2
		exit 1
	fi
	name="$(basename "${svg%.svg}")"
	dest_dir="${OUT_DIR:-$(dirname "$svg")}"
	mkdir -p "$dest_dir"
	webp="$dest_dir/$name.webp"

	rsvg-convert --zoom 2 --format png "$svg" --output "$WORK/$name.png"
	magick "$WORK/$name.png" -define webp:lossless=true "$webp"

	read -r w h < <(magick identify -format '%w %h\n' "$webp")
	echo "$webp: ${w}x${h} pixels; set the image block to $((w / 2))x$((h / 2))"
done
