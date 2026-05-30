#!/usr/bin/env bash
# Upload CSV files and media/ to the remote user's ~/export via rsync over SSH.
# Edit the configuration block below. Do not commit real hostnames or keys if this file is public.
#
# Usage (from the directory that contains your CSVs and media/):
#   ./scripts/sync-export-rsync.sh
#   ./scripts/sync-export-rsync.sh --dry-run
#   ./scripts/sync-export-rsync.sh ./MyLesson.csv ./media
#
# Default with no paths: all *.csv in the current directory, plus ./media if it exists.
# Remote layout: ~/export/<same basenames and media/ subtree>. Uses rsync --update (-u).

set -euo pipefail

# --- configuration (edit for your environment) ---
SSH_USER="u544495502"
SSH_HOST="82.29.186.233"
SSH_PORT="65002"
# Leave empty to use ssh default identity; otherwise path to private key, e.g. ~/.ssh/id_ed25519
SSH_IDENTITY=""
# Directory name under the remote user's home (destination is ~/${REMOTE_EXPORT}/)
REMOTE_EXPORT="export"
# --- end configuration ---

dry_run=()
if [[ "${1:-}" == "--dry-run" ]]; then
  dry_run=(--dry-run)
  shift
fi

if [[ -n "${SSH_IDENTITY}" && ! -f "${SSH_IDENTITY}" ]]; then
  echo "Error: SSH_IDENTITY is set but file not found: ${SSH_IDENTITY}" >&2
  exit 1
fi

# rsync -e string (avoid spaces in SSH_IDENTITY paths or use ~/.ssh/config Host entries)
RSYNC_RSH="ssh"
if [[ -n "${SSH_PORT}" && "${SSH_PORT}" != "22" ]]; then
  RSYNC_RSH+=" -p ${SSH_PORT}"
fi
if [[ -n "${SSH_IDENTITY}" ]]; then
  RSYNC_RSH+=" -i ${SSH_IDENTITY}"
fi

sources=()
if [[ "$#" -gt 0 ]]; then
  for p in "$@"; do
    if [[ ! -e "$p" ]]; then
      echo "Error: source does not exist: $p" >&2
      exit 1
    fi
    # Preserve directory names on the remote side: "media" keeps ~/export/media,
    # while "media/" would flatten contents into ~/export.
    if [[ -d "$p" ]]; then
      p="${p%/}"
    fi
    sources+=("$p")
  done
else
  shopt -s nullglob
  for f in *.csv; do
    sources+=("$f")
  done
  shopt -u nullglob
  if [[ -d media ]]; then
    sources+=(media)
  fi
  if [[ "${#sources[@]}" -eq 0 ]]; then
    echo "Error: no arguments and no *.csv or ./media/ in the current directory." >&2
    echo "  cd to your export folder or pass explicit paths." >&2
    exit 1
  fi
fi

dest="${SSH_USER}@${SSH_HOST}:~/${REMOTE_EXPORT}/"

if [[ "${#dry_run[@]}" -gt 0 ]]; then
  echo "rsync (dry-run) → ${dest}"
else
  echo "rsync → ${dest}"
fi
rsync -auvz -h \
  --exclude=".DS_Store" \
  --exclude="*/.DS_Store" \
  "${dry_run[@]}" \
  -e "${RSYNC_RSH}" \
  "${sources[@]}" \
  "$dest"
