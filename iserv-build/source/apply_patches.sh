#!/bin/sh

# Apply all .patch files found under a given directory to the Nextcloud source tree.
# Usage: apply_patches.sh <patches-dir> <nextcloud-dir>

set -eo pipefail

PATCHES_DIR="$1"
NEXTCLOUD_DIR="$2"

if [ -z "$PATCHES_DIR" ] || [ -z "$NEXTCLOUD_DIR" ]; then
  echo "Usage: $0 <patches-dir> <nextcloud-dir>"
  exit 1
fi

find "$PATCHES_DIR" -name '*.patch' -print0 | sort -z \
  | while IFS= read -r -d '' P; do
      echo "Applying $P"
      patch -d "$NEXTCLOUD_DIR" -p0 --forward --no-backup-if-mismatch -r - -F 0 < "$P"
    done
