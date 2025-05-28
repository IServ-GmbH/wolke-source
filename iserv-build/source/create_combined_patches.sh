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
# make -C "$REPO_PATH" npm-init
# Workaround for #76306 - see https://github.com/puppeteer/puppeteer/issues/12094
cd "$REPO_PATH" && PUPPETEER_DOWNLOAD_BASE_URL="https://storage.googleapis.com/chrome-for-testing-public" npm ci && cd -

echo "Build JS artefacts..."
# Note from past self: You cannot use the MODULE env var to speed this build up.
# The resulting file will be loaded in the browser but not executed (No idea why)
make -C "$REPO_PATH" build-js-production

echo "Creating main.patch..."
git -C "$REPO_PATH" add -N . # required to include newly added files
# --binary is required for the "compiled" JavaScript files
# Vue files are not part of the release tarball, thus we skip them when creating the patch.
# The non-core-repo-apps in apps/ are ignored automatically because they're part of .gitignore.
git -C "$REPO_PATH" diff --binary ':!*.vue' > "$PATCH_DESTINATION/main.patch"

echo "Creating apps_activity.patch..."
git -C "$REPO_PATH/apps/activity" add -N .
git -C "$REPO_PATH/apps/activity" diff --binary ':!*.vue' > "$PATCH_DESTINATION/apps_activity.patch"

echo "Creating apps_files_retention.patch..."
git -C "$REPO_PATH/apps/files_retention" add -N .
git -C "$REPO_PATH/apps/files_retention" diff --binary ':!*.vue' > "$PATCH_DESTINATION/apps_files_retention.patch"

echo "Creating apps_viewer.patch..."
# For this app we need to build the JS, because we are changing frontend code.
cd "$REPO_PATH/apps/viewer"
npm ci
npm run build
cd ../../..
git -C "$REPO_PATH/apps/viewer" add -N .
git -C "$REPO_PATH/apps/viewer" diff --binary ':!*.vue' > "$PATCH_DESTINATION/apps_viewer.patch"

echo "Creating apps_files_linkeditor.patch..."
# For this app we need to build the JS, because we are changing frontend code.
cd "$REPO_PATH/apps/files_linkeditor"
npm ci
npm run build
cd ../../..
git -C "$REPO_PATH/apps/files_linkeditor" add -N .
git -C "$REPO_PATH/apps/files_linkeditor" diff --binary ':!*.svelte' > "$PATCH_DESTINATION/apps_files_linkeditor.patch"
