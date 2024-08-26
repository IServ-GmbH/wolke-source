#!/bin/bash

set -e

DOCKER_DIR="$(readlink -f "$(dirname "$0")")"

VERSION="$1"
if [ -z "$VERSION" ] ; then
  docker build -t iserv/cloudfiles:latest "$DOCKER_DIR"
else
  docker build -t iserv/cloudfiles:latest --build-arg "VERSION=$VERSION" "$DOCKER_DIR"
fi

if [ -d "$PWD/data" ] ; then
  # Extract the image as tarball to make moving it easier
  docker save 'iserv/cloudfiles:latest' | xz -T0 > data/image.tar.xz
  docker image inspect iserv/cloudfiles --format '{{ .Id }}' > data/image.id
fi
