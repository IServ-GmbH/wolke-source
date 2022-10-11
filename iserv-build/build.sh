#!/bin/bash
set -e

# See Dockerfile
nextcloud_version=23.0.7

file_sharing_tab_combined="./docker/cloudfiles/adds/patches/generated/file_sharing_tab_combined.patch"
file_sharing_tab_part="./docker/cloudfiles/build-steps/patches/limit_link_share_edit.patch"
if [ ! -f "$file_sharing_tab_combined" ] || [ "$file_sharing_tab_combined" -ot "$file_sharing_tab_part" ]
then
  echo "Missing or outdated file_sharing_tab patch. Rebuilding..."
  ./docker/cloudfiles/build-steps/create-file_sharing_tab-patch $nextcloud_version
fi

DOCKER_DIR="$(dirname "$0")"

docker build -t iserv/cloudfiles:latest "$DOCKER_DIR"

if [ -d "$PWD/data" ]; then
  # Extract the image as tarball to make moving it easier
  docker save 'iserv/cloudfiles:latest' | xz -T0 > data/image.tar.xz
  docker image inspect iserv/cloudfiles --format '{{ .Id }}' > data/image.id
fi
