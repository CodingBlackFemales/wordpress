#!/bin/bash
changedFiles="$(git diff-tree -r --name-only --no-commit-id ORIG_HEAD HEAD)"
echo "compile.sh"
runOnChange() {
	echo "$changedFiles" | grep -q "$1" && eval "$2"
}

runOnChange composer.lock "composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader"
