#!/bin/sh

# Extract changed files within a git repository into individual patch files / added files

set -e

REPO_DIR="$1"
if [ -z "$REPO_DIR" ] ; then
  echo "Missing repository directory argument."
  echo "Usage: $0 REPOSITORY"
  echo
  echo "Example:"
  echo "$0 ~/nextcloud-server"
  exit 1
fi

SCRIPT_BASE="$(readlink -f "$(dirname "$0")")"
ADDED_DIR="$SCRIPT_BASE/added"
PATCHES_DIR="$SCRIPT_BASE/patches"
COLOR_WARNING='\033[0;33m'
COLOR_NEUTRAL='\033[0m'

# Remove everything to make sure no old patches are left
rm -rf "$ADDED_DIR"
rm -rf "$PATCHES_DIR"

create_patches_for_repo()
{
  REPO_DIR_LOCAL="$1"
  ADDED_DIR_LOCAL="$2"
  PATCHES_DIR_LOCAL="$3"
  PREFIX="$4"
  for F in $(git -C "$REPO_DIR_LOCAL" ls-files --modified --other | grep -v '^apps/.*/tests');
  do
    if [ -d "$REPO_DIR_LOCAL/$F" ] ; then
      # skip directories
      continue
    fi
    DIFF=$(git -C "$REPO_DIR_LOCAL" diff "$F")
    DIR=$(dirname "$F")
    if [ -z "$DIFF" ] ; then
      # Completely new file
      echo "New: $F"
      mkdir -p "$ADDED_DIR_LOCAL/$DIR"
      cp "$REPO_DIR_LOCAL/$F" "$ADDED_DIR_LOCAL/$F"
    else
      # Modified file
      echo "Modified: $F"
      if grep -q "^<<<<<<<$" "$REPO_DIR_LOCAL/$F" ; then
        echo "${COLOR_WARNING}WARNING: It looks like $F contains an unresolved merge conflict!${COLOR_NEUTRAL}"
      fi
      mkdir -p "$PATCHES_DIR_LOCAL/$DIR"
      git -C "$REPO_DIR_LOCAL" diff --src-prefix="$PREFIX" --dst-prefix="$PREFIX" "$F" > "$PATCHES_DIR_LOCAL/$F.patch"
    fi
  done
}

create_patches_for_repo "$REPO_DIR" "$ADDED_DIR" "$PATCHES_DIR" ""
create_patches_for_repo "$REPO_DIR/apps/activity" "$ADDED_DIR/apps/activity" "$PATCHES_DIR/apps/activity" "apps/activity/"
create_patches_for_repo "$REPO_DIR/apps/files_retention" "$ADDED_DIR/apps/files_retention" "$PATCHES_DIR/apps/files_retention" "apps/files_retention/"
create_patches_for_repo "$REPO_DIR/apps/richdocuments" "$ADDED_DIR/apps/richdocuments" "$PATCHES_DIR/apps/richdocuments" "apps/richdocuments/"
create_patches_for_repo "$REPO_DIR/apps/viewer" "$ADDED_DIR/apps/viewer" "$PATCHES_DIR/apps/viewer" "apps/viewer/"
create_patches_for_repo "$REPO_DIR/apps/files_linkeditor" "$ADDED_DIR/apps/files_linkeditor" "$PATCHES_DIR/apps/files_linkeditor" "apps/files_linkeditor/"
create_patches_for_repo "$REPO_DIR/apps/user_saml" "$ADDED_DIR/apps/user_saml" "$PATCHES_DIR/apps/user_saml" "apps/user_saml/"
