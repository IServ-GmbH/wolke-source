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
  REPO_DIR="$1"
  ADDED_DIR="$2"
  PATCHES_DIR="$3"
  PREFIX="$4"
  for F in $(git -C "$REPO_DIR" ls-files --modified --other)
  do
    if [ -d "$REPO_DIR/$F" ] ; then
      # skip directories
      continue
    fi
    DIFF=$(git -C "$REPO_DIR" diff "$F")
    DIR=$(dirname "$F")
    if [ -z "$DIFF" ] ; then
      # Completely new file
      echo "New: $F"
      mkdir -p "$ADDED_DIR/$DIR"
      cp "$REPO_DIR/$F" "$ADDED_DIR/$F"
    else
      # Modified file
      echo "Modified: $F"
      if grep -q "^<<<<<<<$" "$REPO_DIR/$F" ; then
        echo "${COLOR_WARNING}WARNING: It looks like $F contains an unresolved merge conflict!${COLOR_NEUTRAL}"
      fi
      mkdir -p "$PATCHES_DIR/$DIR"
      git -C "$REPO_DIR" diff --src-prefix="$PREFIX" --dst-prefix="$PREFIX" "$F" > "$PATCHES_DIR/$F.patch"
    fi
  done
}

create_patches_for_repo "$REPO_DIR" "$ADDED_DIR" "$PATCHES_DIR" ""
create_patches_for_repo "$REPO_DIR/apps/activity" "$ADDED_DIR/apps/activity" "$PATCHES_DIR/apps/activity" "apps/activity/"