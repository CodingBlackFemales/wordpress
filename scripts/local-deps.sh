#!/usr/bin/env bash
#
# Install or update the Composer and npm dependencies of each in-repo plugin
# (web/app/plugins/cbf-*). Root Composer runs this after its own install or
# update; run it directly to refresh the plugins alone.
#
# Usage: scripts/local-deps.sh install|update
#
# Under `composer install --no-dev`, as on the servers, plugins get --no-dev
# too and npm is skipped. npm is also skipped in CI, which builds plugin assets
# in its own step.

set -euo pipefail

action=${1:-}
if [ "$action" != install ] && [ "$action" != update ]; then
	echo "Usage: $0 install|update" >&2
	exit 1
fi

cd "$(dirname "$0")/.."

# Composer sets this for its hook scripts: 0 under --no-dev, 1 otherwise
dev=${COMPOSER_DEV_MODE:-1}

composer_args=(--no-interaction)
if [ "$dev" = 0 ]; then
	composer_args+=(--no-dev)
fi

run_npm=0
if [ "$dev" = 1 ] && [ -z "${CI:-}" ]; then
	if command -v npm >/dev/null 2>&1; then
		run_npm=1
	else
		echo "npm not found: skipping plugin npm dependencies" >&2
	fi
fi

for dir in web/app/plugins/cbf-*/; do
	name=$(basename "$dir")

	if [ -f "$dir/composer.json" ]; then
		echo "> $name: composer $action"
		composer "$action" --working-dir="$dir" "${composer_args[@]}"
	fi

	if [ "$run_npm" = 1 ] && [ -f "$dir/package.json" ]; then
		echo "> $name: npm $action"
		npm "$action" --prefix "$dir" --no-audit --no-fund
	fi
done
