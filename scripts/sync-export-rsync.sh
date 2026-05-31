#!/usr/bin/env bash
# Upload CSV files and media/ to a remote user's export dir via rsync over SSH.
#
# Connection settings are taken from flags first, then environment variables,
# then built-in fallback defaults. This lets import-slides.sh feed the target
# derived from wp-cli.yml (single source of truth) instead of editing this file.
#
# Usage (from the directory that contains your CSVs and media/):
#   ./scripts/sync-export-rsync.sh
#   ./scripts/sync-export-rsync.sh --dry-run
#   ./scripts/sync-export-rsync.sh --host 1.2.3.4 --user bob --port 65002 ./MyLesson.csv ./media
#
# Flags (all optional):
#   --user <user>          SSH user            (env SSH_USER)
#   --host <host>          SSH host            (env SSH_HOST)
#   --port <port>          SSH port            (env SSH_PORT, default 22)
#   --identity <path>      SSH private key     (env SSH_IDENTITY)
#   --remote-dir <name>    Dir under remote ~  (env REMOTE_EXPORT, default "export")
#   --dry-run              Preview without transferring
#   [paths...]             Explicit sources; default: *.csv in cwd + ./media if present
#
# Remote layout: ~/<remote-dir>/<same basenames and media/ subtree>. Uses rsync --update (-u).

set -euo pipefail

# --- fallback defaults (overridden by env vars or flags) ---
SSH_USER="${SSH_USER:-u544495502}"
SSH_HOST="${SSH_HOST:-82.29.186.233}"
SSH_PORT="${SSH_PORT:-65002}"
# Leave empty to use ssh default identity; otherwise path to a private key.
SSH_IDENTITY="${SSH_IDENTITY:-}"
# Directory name under the remote user's home (destination is ~/${REMOTE_EXPORT}/).
REMOTE_EXPORT="${REMOTE_EXPORT:-export}"
# --- end defaults ---

dry_run=()
paths=()

while [[ "$#" -gt 0 ]]; do
  case "$1" in
    --dry-run) dry_run=(--dry-run); shift ;;
    --user) SSH_USER="$2"; shift 2 ;;
    --host) SSH_HOST="$2"; shift 2 ;;
    --port) SSH_PORT="$2"; shift 2 ;;
    --identity) SSH_IDENTITY="$2"; shift 2 ;;
    --remote-dir) REMOTE_EXPORT="$2"; shift 2 ;;
    --user=*) SSH_USER="${1#*=}"; shift ;;
    --host=*) SSH_HOST="${1#*=}"; shift ;;
    --port=*) SSH_PORT="${1#*=}"; shift ;;
    --identity=*) SSH_IDENTITY="${1#*=}"; shift ;;
    --remote-dir=*) REMOTE_EXPORT="${1#*=}"; shift ;;
    --) shift; while [[ "$#" -gt 0 ]]; do paths+=("$1"); shift; done ;;
    -*) echo "Error: unknown option: $1" >&2; exit 1 ;;
    *) paths+=("$1"); shift ;;
  esac
done

if [[ -z "${SSH_USER}" || -z "${SSH_HOST}" ]]; then
  echo "Error: SSH user and host are required (set --user/--host or SSH_USER/SSH_HOST)." >&2
  exit 1
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
if [[ "${#paths[@]}" -gt 0 ]]; then
  for p in "${paths[@]}"; do
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
