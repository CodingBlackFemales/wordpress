#!/bin/bash

# Syncing Trellis & Bedrock-based WordPress environments with WP-CLI aliases (Kinsta version)
# Version 1.1.0
# Copyright (c) Ben Word

FROM=$1
TO=$2

bold=$(tput bold)
normal=$(tput sgr0)

# Declare arrays to store environment configuration values
SUBDOMAINS=("academy" "jobs")
declare -A SOURCE
declare -A DEST
declare -A DEV=(
	["rootdomain"]="codingblackfemales.lndo.site"
	["domain"]="wp.codingblackfemales.lndo.site"
	["url"]="https://wp.codingblackfemales.lndo.site"
)
declare -A STAGING=(
	["rootdomain"]="staging.codingblackfemales.com"
	["domain"]="wp.staging.codingblackfemales.com"
	["url"]="https://wp.staging.codingblackfemales.com"
)
declare -A PRODUCTION=(
	["rootdomain"]="codingblackfemales.com"
	["domain"]="wp.codingblackfemales.com"
	["url"]="https://wp.codingblackfemales.com"
)

case "$1-$2" in
  production-development) DIR="down ⬇️ "; ;;
  staging-development)    DIR="down ⬇️ "; ;;
  development-production) echo "syncing development to production not supported, sync to staging first. usage: $0 production development | staging development | development staging | staging production | production staging" && exit 1 ;;
  development-staging)    DIR="up ⬆️ "; ;;
  production-staging)     DIR="horizontally ↔️ "; ;;
  staging-production)     DIR="horizontally ↔️ "; ;;
  *) echo "usage: $0 production development | staging development | development staging | staging production | production staging" && exit 1 ;;
esac

case "$1" in
  production)  SOURCE=("${(@fkv)PRODUCTION}"); ;;
  development) SOURCE=("${(@fkv)DEV}"); ;;
  staging)     SOURCE=("${(@fkv)STAGING}"); ;;
esac

case "$2" in
  development) DEST=("${(@fkv)DEV}"); ;;
  production)  DEST=("${(@fkv)PRODUCTION}"); ;;
  staging)     DEST=("${(@fkv)STAGING}"); ;;
esac

read "response?
🔄  Would you really like to ⚠️  ${bold}reset the $TO database${normal} (${DEST[url]})
    and sync ${bold}$DIR${normal} from $FROM (${SOURCE[url]})? [y/N] "

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
    AVAILFROM=$(wp "@$FROM" option get home 2>&1)
    if [[ $AVAILFROM == *"Error"* ]]; then
      echo "❌  Unable to connect to $FROM"
      exit 1
    else
      echo "✅  Able to connect to $FROM"
    fi
  };

  availto() {
    local AVAILTO
    AVAILTO=$(wp "@$TO" option get home 2>&1)
    if [[ $AVAILTO == *"Error"* ]]; then
      echo "❌  Unable to connect to $TO"
      exit 1
    else
      echo "✅  Able to connect to $TO"
    fi
  };

  sync_db() {
	local DESTSUBDOMAIN
	local SOURCESUBDOMAIN

  	echo

    # Export/import database
    wp "@$TO" db export &&
    wp "@$TO" db reset --yes &&
    wp "@$FROM" db export - | wp "@$TO" db import -

	if [ $? -ne 0 ]; then
		echo "❌  Database import failed" >&2
		exit 1
	fi

    # Run search & replace for sub-domains
    for subdomain in "${SUBDOMAINS[@]}"; do
      DESTSUBDOMAIN="$subdomain.${DEST[rootdomain]}"
      SOURCESUBDOMAIN="$subdomain.${SOURCE[rootdomain]}"
	  echo
	  echo "Replacing $SOURCESUBDOMAIN (sub-domain) with $DESTSUBDOMAIN"
      wp @$TO search-replace "$SOURCESUBDOMAIN" "$DESTSUBDOMAIN" --url="$SOURCESUBDOMAIN" &&
      wp @$TO search-replace "$SOURCESUBDOMAIN" "$DESTSUBDOMAIN" --url="${SOURCE[url]}"
    done

    # Run search & replace for primary domain
	echo
	echo "Replacing ${SOURCE[domain]} (primary domain) with ${DEST[domain]}"
    wp @$TO search-replace "${SOURCE[domain]}" "${DEST[domain]}" --url="${SOURCE[url]}" &&
    wp @$TO search-replace --network "${SOURCE[domain]}" "${DEST[domain]}"
  };

  sync_uploads() {
	# Sync uploads directory
	chmod -R 755 web/app/uploads/ &&
	if [[ $DIR == "horizontally"* ]]; then
		[[ $FROMDIR =~ ^(.*): ]] && FROMHOST=${BASH_REMATCH[1]}
		[[ $FROMDIR =~ ^(.*):(.*)$ ]] && FROMDIR=${BASH_REMATCH[2]}
		[[ $TODIR =~ ^(.*): ]] && TOHOST=${BASH_REMATCH[1]}
		[[ $TODIR =~ ^(.*):(.*)$ ]] && TODIR=${BASH_REMATCH[2]}

		ssh -o ForwardAgent=yes $FROMHOST "rsync -aze 'ssh -o StrictHostKeyChecking=no' --progress $FROMDIR $TOHOST:$TODIR"
	else
		rsync -az --progress "$FROMDIR" "$TODIR"
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
#   sync_uploads
  notify

  popd
fi
