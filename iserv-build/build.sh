#!/bin/bash

set -e

DOCKER_DIR="$(readlink -f "$(dirname "$0")")"

VERSION="$1"
if [ -z "$VERSION" ] ; then
  echo "No version specified, using 'latest'."
  docker build -t iserv/cloudfiles:latest "$DOCKER_DIR"
else
  echo "Building image for version: $VERSION"
  docker build -t iserv/cloudfiles:latest --build-arg "VERSION=$VERSION" "$DOCKER_DIR"
fi

if [ -d "$PWD/data" ] ; then
  echo "Extracting image to data directory, this may take a while."
  # Extract the image as tarball to make moving it easier
  docker save 'iserv/cloudfiles:latest' | xz -T0 > data/image.tar.xz
  docker image inspect iserv/cloudfiles --format '{{ .Id }}' > data/image.id
else
  echo "No data directory found, skipping image extraction."
fi
