#!/bin/zsh

# Syncing Trellis & Bedrock-based WordPress environments with WP-CLI aliases (Kinsta version)
# Version 1.1.1
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

# Get script directory for config file path
SCRIPT_DIR="$(cd "$(dirname "${(%):-%x}")" && pwd)"
CONFIG_FILE="${SCRIPT_DIR}/sync.conf"

# Pre-declare environment associative arrays (zsh requires this before dynamic assignment)
typeset -gA DEV STAGING PROD

# Parse INI config file
# - Lines before first section become global variables (uppercase)
# - Space-separated values become arrays
# - Section names become associative arrays (uppercase)
parse_ini_file() {
  local file=$1
  local current_section=""
  local key value

  while IFS= read -r line || [[ -n "$line" ]]; do
    # Trim leading/trailing whitespace
    line="${line#"${line%%[![:space:]]*}"}"
    line="${line%"${line##*[![:space:]]}"}"

    # Skip empty lines and comments
    [[ -z "$line" || "$line" == \#* || "$line" == \;* ]] && continue

    # Section header [name]
    if [[ "$line" == \[*\] ]]; then
      current_section="${line#\[}"       # Remove leading [
      current_section="${current_section%\]}"  # Remove trailing ]
      current_section="${(U)current_section}"  # Uppercase: dev -> DEV
      continue
    fi

    # Key=value pair
    if [[ "$line" == *=* ]]; then
      key="${line%%=*}"
      value="${line#*=}"
      # Trim whitespace from key and value
      key="${key#"${key%%[![:space:]]*}"}"; key="${key%"${key##*[![:space:]]}"}"
      value="${value#"${value%%[![:space:]]*}"}"; value="${value%"${value##*[![:space:]]}"}"

      if [[ -z "$current_section" ]]; then
        # Global variable (before any section)
        # Handle space-separated lists as arrays
        if [[ "$value" == *" "* ]]; then
          eval "${(U)key}=(\${(s: :)value})"  # Split into array
        else
          eval "${(U)key}=\"\${value}\""
        fi
      else
        # Section variable
        eval "${current_section}[${key}]=\"\${value}\""
      fi
    fi
  done < "$file"
}

# Calculate derived values (domain, url) from rootdomain
calculate_derived_values() {
  local env_name=$1
  eval "${env_name}[domain]=\"wp.\${${env_name}[rootdomain]}\""
  eval "${env_name}[url]=\"https://\${${env_name}[domain]}\""
}

# Load configuration
if [[ ! -f "$CONFIG_FILE" ]]; then
  echo "❌  Configuration file not found: $CONFIG_FILE"
  exit 1
fi

parse_ini_file "$CONFIG_FILE"

# Calculate derived values for each environment
calculate_derived_values DEV
calculate_derived_values STAGING
calculate_derived_values PROD

# Validate required environment configs exist
if [[ -z "${(k)DEV}" ]] || [[ -z "${(k)STAGING}" ]] || [[ -z "${(k)PROD}" ]]; then
  echo "❌  Invalid configuration: DEV, STAGING, or PROD arrays not found"
  exit 1
fi

# Declare arrays to store environment configuration values
declare -A SOURCE
declare -A DEST

case "$FROM-$TO" in
	prod-dev) DIR="down ⬇️ "; ;;
	staging-dev)    DIR="down ⬇️ "; ;;
	dev-prod) echo "syncing dev to prod not supported, sync to staging first. usage: $0 prod dev | staging dev | dev staging | staging prod | prod staging" && exit 1 ;;
	dev-staging)    DIR="up ⬆️ "; ;;
	prod-staging)     DIR="horizontally ↔️ "; ;;
	staging-prod)     DIR="horizontally ↔️ "; ;;
	*) echo "usage: $0 [[--skip-db] [--skip-assets] [--local]] prod dev | staging dev | dev staging | staging prod | prod staging" && exit 1 ;;
esac

case "$FROM" in
	prod)  SOURCE=("${(@fkv)PROD}"); ;;
	dev) SOURCE=("${(@fkv)DEV}"); ;;
	staging)     SOURCE=("${(@fkv)STAGING}"); ;;
esac

case "$TO" in
	dev) DEST=("${(@fkv)DEV}"); ;;
	prod)  DEST=("${(@fkv)PROD}"); ;;
	staging)     DEST=("${(@fkv)STAGING}"); ;;
esac

# Validate required config keys exist (approot can be empty for local environments)
required_keys=("rootdomain" "domain" "url" "dir" "port" "multisite_mode")
for key in "${required_keys[@]}"; do
	if [[ -z "${SOURCE[$key]}" ]]; then
		echo "❌  Missing required config key in source environment: $key"
		exit 1
	fi
	if [[ -z "${DEST[$key]}" ]]; then
		echo "❌  Missing required config key in destination environment: $key"
		exit 1
	fi
done

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

		if [[ "$LOCAL" = true && $FROM == "dev" ]]; then
			AVAILFROM=$("$WP" option get home 2>&1)
		else
			AVAILFROM=$("$WP" "@$FROM" option get home 2>&1)
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
		if [[ "$LOCAL" = true && $TO == "dev" ]]; then
			AVAILTO=$("$WP" option get home 2>&1)
		else
			AVAILTO=$("$WP" "@$TO" option get home 2>&1)
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
		EXPORTFILE="${DEST[approot]}data/export-$(date +'%Y-%m-%d-%H%M%S').sql"

		# Export/import database
		if [[ "$LOCAL" = true && $TO == "dev" ]]; then
			"$WP" db export $EXPORTFILE --default-character-set=utf8mb4 &&
			"$WP" db reset --yes &&
			"$WP" "@$FROM" db export --default-character-set=utf8mb4 - | "$WP" db import -
		elif [[ "$LOCAL" = true && $FROM == "dev" ]]; then
			"$WP" "@$TO" db export $EXPORTFILE --default-character-set=utf8mb4 &&
			"$WP" "@$TO" db reset --yes &&
			"$WP" db export --default-character-set=utf8mb4 - | "$WP" "@$TO" db import -
		else
			"$WP" "@$TO" db export $EXPORTFILE --default-character-set=utf8mb4 &&
			"$WP" "@$TO" db reset --yes &&
			"$WP" "@$FROM" db export --default-character-set=utf8mb4 - | "$WP" "@$TO" db import -
		fi

		if [ $? -ne 0 ]; then
			echo "❌  Database import failed" >&2
			exit 1
		fi

		# Run search & replace for sub-sites
		for subsite in "${SUBSITES[@]}"; do
			# Build source subsite URL based on multisite mode
			if [[ "${SOURCE[multisite_mode]}" == "subdomain" ]]; then
				SOURCESUBSITE="$subsite.${SOURCE[rootdomain]}"
				SOURCEDOMAIN=$SOURCESUBSITE
				SOURCEPATH="/"
			else
				# subdirectory mode
				SOURCESUBSITE="${SOURCE[rootdomain]}/$subsite"
				SOURCEDOMAIN="${SOURCE[rootdomain]}"
				SOURCEPATH="/$subsite/"
			fi

			# Build destination subsite URL based on multisite mode
			if [[ "${DEST[multisite_mode]}" == "subdomain" ]]; then
				DESTSUBSITE="$subsite.${DEST[rootdomain]}"
				DESTDOMAIN="$DESTSUBSITE"
				DESTPATH="/"
			else
				# subdirectory mode
				DESTSUBSITE="${DEST[rootdomain]}/$subsite"
				DESTDOMAIN="${DEST[rootdomain]}"
				DESTPATH="/$subsite/"
			fi

			echo
			echo "Replacing $SOURCESUBSITE (sub-site) with $DESTSUBSITE"
			"$WP" @"$TO" db query "UPDATE wp_blogs SET domain='$DESTDOMAIN', path='$DESTPATH' WHERE domain='$SOURCEDOMAIN' AND path='$SOURCEPATH';" &&
			"$WP" @"$TO" search-replace "$SOURCESUBSITE" "$DESTSUBSITE" --all-tables-with-prefix
		done

		# Run search & replace for primary domain
		echo
		echo "Replacing ${SOURCE[domain]} (primary domain) with ${DEST[domain]}"
		"$WP" @"$TO" search-replace "${SOURCE[domain]}" "${DEST[domain]}" --all-tables-with-prefix
		echo "Replacing ${SOURCE[rootdomain]} (root domain) with ${DEST[rootdomain]}"
		"$WP" @"$TO" search-replace "${SOURCE[rootdomain]}" "${DEST[rootdomain]}" --all-tables-with-prefix
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
			rsync -chavzP -e "ssh -p ${DEST[port]}" --progress "${SOURCE[dir]}" "${DEST[dir]}"
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
	# Use project vendor/bin/wp if available, avoiding the PHAR vs vendor class split.
	if [[ -x "./vendor/bin/wp" ]]; then
		WP="./vendor/bin/wp"
	else
		WP="wp"
	fi
	availfrom
	availto
	sync_db
	sync_uploads
	# notify

	popd
fi
