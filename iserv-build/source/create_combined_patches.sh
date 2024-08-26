#!/bin/sh

set -e

# Create patch files based on the changes in git working directories

REPO_PATH="$1"
if [ -z "$REPO_PATH" ]
then
  echo "Missing repository path argument."
  exit 1
fi

PATCH_DESTINATION="$2"
if [ -z "$PATCH_DESTINATION" ]
then
  echo "Missing patch destination argument."
  exit 1
fi

echo "Downloading JS dependencies..."
make -C "$REPO_PATH" npm-init

echo "Build JS artefacts..."
# Note from past self: You cannot use the MODULE env var to speed this build up.
# The resulting file will be loaded in the browser but not executed (No idea why)
make -C "$REPO_PATH" build-js-production

echo "Creating main.patch..."
# --binary is required for the "compiled" JavaScript files
# Vue files are not part of the release tarball, thus we skip them when creating the patch.
# The non-core-repo-apps in apps/ are ignored automatically because they're part of .gitignore.
git -C "$REPO_PATH" diff --binary ':!*.vue' > "$PATCH_DESTINATION/main.patch"

echo "Creating apps_activity.patch..."
git -C "$REPO_PATH/apps/activity" diff --binary ':!*.vue' > "$PATCH_DESTINATION/apps_activity.patch"
