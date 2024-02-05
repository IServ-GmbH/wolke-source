#!/bin/bash
set -e

# See Dockerfile
nextcloud_version=27.1.6

./docker/cloudfiles/build-steps/create-combined-patch $nextcloud_version

DOCKER_DIR="$(dirname "$0")"

docker build -t iserv/cloudfiles:latest "$DOCKER_DIR"

if [ -d "$PWD/data" ]; then
  # Extract the image as tarball to make moving it easier
  docker save 'iserv/cloudfiles:latest' | xz -T0 > data/image.tar.xz
  docker image inspect iserv/cloudfiles --format '{{ .Id }}' > data/image.id
fi

