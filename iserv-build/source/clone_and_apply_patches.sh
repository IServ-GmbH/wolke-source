#!/bin/bash

# Clone upstream repositories, copy added files and apply patches
set -e

print_help()
{
  echo "Usage: $0 [OPTION]... VERSION DESTINATION"
  echo
  echo "Options:"
  echo
  echo "-m  write 3-way-merge representation (<<<<<<<...>>>>>>>) for failed files"
  echo "    (Don't try to do this repeatedly!)"
  echo "-h  display help"
  echo
  echo "Example:"
  echo "$0 27.1.8 ~/nextcloud-server"
  echo
  echo "Available versions: https://nextcloud.com/changelog/"
  exit 1
}

while getopts "mh" FLAG ; do
  case "$FLAG" in
      m) MERGE=1 ;;
      *) print_help ;;
  esac
done

VERSION="${*:$OPTIND:1}"
if [ -z "$VERSION" ] ; then
  echo "Missing version argument."
  print_help
fi

DESTINATION="${*:$OPTIND+1:1}"
if [ -z "$DESTINATION" ] ; then
  echo "Missing destination argument."
  print_help
fi

UPSTREAM_VERSION_TAG="v$VERSION" # example: v27.1.8
SCRIPT_BASE="$(readlink -f "$(dirname "$0")")"
ADDED_DIR="$SCRIPT_BASE/added"
PATCHES_DIR="$SCRIPT_BASE/patches"
COLOR_ERROR='\033[0;31m'
COLOR_WARNING='\033[0;33m'
COLOR_NEUTRAL='\033[0m'

if [ -e "$DESTINATION" ] ; then
  echo
  echo -e "${COLOR_WARNING}WARNING: Destination path already exists. Cloning of upstream repositories is being skipped. Expecting that all upstream repositories have successfully been cloned.${COLOR_NEUTRAL}"
  echo
else
  echo "Cloning version $VERSION of upstream main repo..."
  git clone --branch "$UPSTREAM_VERSION_TAG" --depth 1 -c advice.detachedHead=false "https://github.com/nextcloud/server.git" "$DESTINATION"

  echo "Cloning version $VERSION of upstream app repos..."
  git clone --branch "$UPSTREAM_VERSION_TAG" --depth 1 -c advice.detachedHead=false "https://github.com/nextcloud/activity.git" "$DESTINATION/apps/activity"
fi

echo "Copying added files into repo directories..."
cp -rv "$ADDED_DIR"/* "$DESTINATION"

echo
echo "Applying source patches..."
FAILED=0
SKIPPED=0
shopt -s globstar
for P in "$PATCHES_DIR"/**/*.patch
do
  echo
  echo -n "Applying $P"
  # We have to parse patch'es output because it returns 1 for skipped patches as well as actual errors.
  if OUT="$(patch -d "$DESTINATION" -p0 --forward --no-backup-if-mismatch -r - -F 0 < "$P")" ; then
    # Success
    echo
    echo "$OUT"
  else
    if echo "${OUT}" | grep "Reversed (or previously applied) patch detected!" -q ; then
      # Patch has already been applied
      (( SKIPPED++ )) || true
      echo -e " $COLOR_WARNING...skipped!$COLOR_NEUTRAL"
    else
      # Actual error
      (( FAILED++ )) || true
      echo -ne "$COLOR_ERROR"
      echo " ...PROBLEM:"
      echo "$OUT"
      echo -e "$COLOR_NEUTRAL"
      echo "Content of patchfile is:"
      echo
      cat "$P"
      echo
      if [ "$MERGE" = "1" ] ; then
        patch -d "$DESTINATION" -p0 --forward --merge=diff3 --no-backup-if-mismatch -r - -F 0 < "$P" || true
      fi
    fi
  fi
done

if [ "$SKIPPED" -gt "0" ] ; then
  echo
  echo -e "${COLOR_WARNING}$SKIPPED patch(es) skipped!${COLOR_NEUTRAL}"
fi

if [ "$FAILED" -gt "0" ] ; then
  echo
  echo -e "${COLOR_ERROR}$FAILED patch(es) failed!${COLOR_NEUTRAL}"
  if [ "$MERGE" != "1" ] ; then
    echo "Hint: Run this command again with -m option to write 3-way-merge representations for failed files."
  else
    echo "3-way-merge representations been written for failed files."
  fi
  false
fi
