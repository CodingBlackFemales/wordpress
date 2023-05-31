#!/bin/zsh

# Syncing Trellis & Bedrock-based WordPress environments with WP-CLI aliases (Kinsta version)
# Version 1.1.0
# Copyright (c) Ben Word

LOCAL=false
SKIP_DB=false
SKIP_ASSETS=false
POSITIONAL_ARGS=()

while [[ $# -gt 0 ]]; do
  case $1 in
    --skip-db)
      SKIP_DB=true
      shift
      ;;
    --skip-assets)
      SKIP_ASSETS=true
      shift
      ;;
    --local)
      LOCAL=true
      shift
      ;;
    --*)
      echo "Unknown option $1"
      exit 1
      ;;
    *)
      POSITIONAL_ARGS+=("$1")
      shift
      ;;
  esac
done

set -- "${POSITIONAL_ARGS[@]}"

if [ $# != 2 ]
then
  echo "Usage: $0 [[--skip-db] [--skip-assets] [--local]] [ENV_FROM] [ENV_TO]"
exit;
fi

FROM=$1
TO=$2

bold=$(tput bold)
normal=$(tput sgr0)

# Declare arrays to store environment configuration values
SUBSITES=("academy" "cms" "jobs")
declare -A SOURCE
declare -A DEST
declare -A DEV=(
	["rootdomain"]="codingblackfemales.lndo.site"
	["domain"]="wp.codingblackfemales.lndo.site"
	["url"]="https://wp.codingblackfemales.lndo.site"
	["dir"]="web/app/uploads/"
)
declare -A STAGING=(
	["rootdomain"]="staging.codingblackfemales.com"
	["domain"]="staging.codingblackfemales.com"
	["url"]="https://staging.codingblackfemales.com"
	["dir"]="codingblackfemales.com@ssh.gb.stackcp.com:/home/virtual/vps-da309d/e/e7eeed1b7b/staging_html/web/app/uploads/"
	["port"]="22"
)
declare -A PRODUCTION=(
	["rootdomain"]="codingblackfemales.com"
	["domain"]="wp.codingblackfemales.com"
	["url"]="https://wp.codingblackfemales.com"
	["dir"]="codingblackfemales.com@ssh.gb.stackcp.com:/home/virtual/vps-da309d/e/e7eeed1b7b/public_html/web/app/uploads/"
	["port"]="22"
)

case "$FROM-$TO" in
	production-development) DIR="down ⬇️ "; ;;
	staging-development)    DIR="down ⬇️ "; ;;
	development-production) echo "syncing development to production not supported, sync to staging first. usage: $0 production development | staging development | development staging | staging production | production staging" && exit 1 ;;
	development-staging)    DIR="up ⬆️ "; ;;
	production-staging)     DIR="horizontally ↔️ "; ;;
	staging-production)     DIR="horizontally ↔️ "; ;;
	*) echo "usage: $0 [[--skip-db] [--skip-assets] [--local]] production development | staging development | development staging | staging production | production staging" && exit 1 ;;
esac

case "$FROM" in
	production)  SOURCE=("${(@fkv)PRODUCTION}"); ;;
	development) SOURCE=("${(@fkv)DEV}"); ;;
	staging)     SOURCE=("${(@fkv)STAGING}"); ;;
esac

case "$TO" in
	development) DEST=("${(@fkv)DEV}"); ;;
	production)  DEST=("${(@fkv)PRODUCTION}"); ;;
	staging)     DEST=("${(@fkv)STAGING}"); ;;
esac

if [ "$SKIP_DB" = false ]
then
  DB_MESSAGE=" - ${bold}reset the $TO database${normal} (${DEST[url]})"
fi

if [ "$SKIP_ASSETS" = false ]
then
  ASSETS_MESSAGE=" - sync ${bold}$DIR${normal} from $FROM (${SOURCE[url]})?"
fi

if [ "$SKIP_DB" = true ] && [ "$SKIP_ASSETS" = true ]
then
  echo "Nothing to synchronize."
  exit;
fi

read "response?
🔄  Would you really like to
${DB_MESSAGE}
${ASSETS_MESSAGE}
[y/N] "

if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
	# Change to site directory
	pwd=$(pwd)
	echo

	# Check we're running under a Bedrock site: https://unix.stackexchange.com/a/22215
	findenv () {
		root=$(pwd)
		while [[ "$root" != "" && ! -e "$root/.env" ]]; do
			root=${root%/*}
		done
		if [[ $root != "" ]]; then
			pushd "$root"
		else
			echo "❌  Unable to find a Bedrock site root"
			exit 1
		fi
	};

	# Make sure both environments are available before we continue
	availfrom() {
		local AVAILFROM

		if [[ "$LOCAL" = true && $FROM == "development" ]]; then
			AVAILFROM=$(wp option get home 2>&1)
		else
			AVAILFROM=$(wp "@$FROM" option get home 2>&1)
		fi
		if [[ $AVAILFROM == *"Error"* ]]; then
			echo "❌  Unable to connect to $FROM"
			exit 1
		else
			echo "✅  Able to connect to $FROM"
		fi
	};

	availto() {
		local AVAILTO
		if [[ "$LOCAL" = true && $TO == "development" ]]; then
			AVAILTO=$(wp option get home 2>&1)
		else
			AVAILTO=$(wp "@$TO" option get home 2>&1)
		fi

		if [[ $AVAILTO == *"Error"* ]]; then
			echo "❌  Unable to connect to $TO $AVAILTO"
			exit 1
		else
			echo "✅  Able to connect to $TO"
		fi
	};

	sync_db() {
		if [ "$SKIP_DB" = true ]
		then
			return
		fi

		local DESTDOMAIN
		local DESTPATH
		local DESTSUBSITE
		local SOURCEDOMAIN
		local SOURCEPATH
		local SOURCESUBSITE
		local EXPORTFILE

		echo "Syncing database..."
		EXPORTFILE="data/export-$(date +'%Y-%m-%d-%H%M%S').sql"

		# Export/import database
		if [[ "$LOCAL" = true && $TO == "development" ]]; then
			wp db export $EXPORTFILE --default-character-set=utf8mb4 &&
			wp db reset --yes &&
			wp "@$FROM" db export --default-character-set=utf8mb4 - | wp db import -
		elif [[ "$LOCAL" = true && $FROM == "development" ]]; then
			wp "@$TO" db export $EXPORTFILE --default-character-set=utf8mb4 &&
			wp "@$TO" db reset --yes &&
			wp db export --default-character-set=utf8mb4 - | wp "@$TO" db import -
		else
			wp "@$TO" db export $EXPORTFILE --default-character-set=utf8mb4 &&
			wp "@$TO" db reset --yes &&
			wp "@$FROM" db export --default-character-set=utf8mb4 - | wp "@$TO" db import -
		fi

		if [ $? -ne 0 ]; then
			echo "❌  Database import failed" >&2
			exit 1
		fi

		# Run search & replace for sub-sites
		for subsite in "${SUBSITES[@]}"; do
			if [ "$FROM" = "staging" ]; then
				SOURCESUBSITE="${SOURCE[rootdomain]}/$subsite"
				SOURCEDOMAIN=${SOURCE[rootdomain]}
				SOURCEPATH="/$subsite/"
			else
				SOURCESUBSITE="$subsite.${SOURCE[rootdomain]}"
				SOURCEDOMAIN=$SOURCESUBSITE
				SOURCEPATH="/"
			fi

			if [ "$TO" = "staging" ]; then
				DESTSUBSITE="${DEST[rootdomain]}/$subsite"
				DESTDOMAIN="${DEST[rootdomain]}"
				DESTPATH="/$subsite/"
			else
				DESTSUBSITE="$subsite.${DEST[rootdomain]}"
				DESTDOMAIN="$DESTSUBSITE"
				DESTPATH="/"
			fi

			echo
			echo "Replacing $SOURCESUBSITE (sub-site) with $DESTSUBSITE"
			wp @$TO db query "UPDATE wp_blogs SET domain='$DESTDOMAIN', path='$DESTPATH' WHERE domain='$SOURCEDOMAIN' AND path='$SOURCEPATH';" &&
			wp @$TO search-replace "$SOURCESUBSITE" "$DESTSUBSITE" --all-tables-with-prefix
		done

		# Run search & replace for primary domain
		echo
		echo "Replacing ${SOURCE[domain]} (primary domain) with ${DEST[domain]}"
		wp @$TO search-replace "${SOURCE[domain]}" "${DEST[domain]}" --url="${SOURCE[url]}" &&
		wp @$TO search-replace --network "${SOURCE[domain]}" "${DEST[domain]}"
	};

	sync_uploads() {
		if [ "$SKIP_ASSETS" = true ]
		then
			return
		fi

		echo "Syncing assets $DIR from ${SOURCE[dir]} to ${DEST[dir]}..."
		# Sync uploads directory
		chmod -R 755 web/app/uploads/ &&
		if [[ $DIR == "horizontally"* ]]; then
			[[ ${SOURCE[dir]} =~ ^(.*): ]] && FROMHOST=${match[1]}
			[[ ${SOURCE[dir]} =~ ^(.*):(.*)$ ]] && FROMDIR=${match[2]}
			[[ ${DEST[dir]} =~ ^(.*): ]] && TOHOST=${match[1]}
			[[ ${DEST[dir]} =~ ^(.*):(.*)$ ]] && TODIR=${match[2]}

			if [[ "$FROMHOST" == "$TOHOST" && "${SOURCE[port]}" == "${DEST[port]}" ]]; then
				ssh -p ${SOURCE[port]} $FROMHOST "rsync -az --progress '$FROMDIR' '$TODIR'"
			else
				ssh -p ${SOURCE[port]} -o ForwardAgent=yes $FROMHOST "rsync -aze 'ssh -o StrictHostKeyChecking=no -p ${DEST[port]}' --progress $FROMDIR $TOHOST:$TODIR"
			fi
		elif [[ $DIR == "down"* ]]; then
			rsync -chavzP -e "ssh -p ${SOURCE[port]}" --progress "${SOURCE[dir]}" "${DEST[dir]}"
		else
			rsync -az --progress "${SOURCE[dir]}" "${DEST[dir]}"
		fi
	};

	# Slack notification when sync direction is up or horizontal
	notify() {
		# if [[ $DIR != "down"* ]]; then
		#   USER="$(git config user.name)"
		#   curl -X POST -H "Content-type: application/json" --data "{\"attachments\":[{\"fallback\": \"\",\"color\":\"#36a64f\",\"text\":\"🔄 Sync from ${SOURCE[url]} to ${DEST[url]} by ${USER} complete \"}],\"channel\":\"#site\"}" https://hooks.slack.com/services/xx/xx/xx
		# fi

		echo -e "\n\n🔄  Sync from $FROM to $TO complete.\n\n    ${bold}${DEST[url]}${normal}\n"
	};

	findenv
	availfrom
	availto
	sync_db
	sync_uploads
	# notify

	popd
fi
