#!/usr/bin/env bash

# Builds the Docker image for IServ Cloudfiles and saves it to a tar.xz file.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="$SCRIPT_DIR/.env"
DATA_DIR="$SCRIPT_DIR/../../data"
PROJECT_SCRIPTS_DIR="$SCRIPT_DIR/../../lib/docker-cloudfiles"

# Load version
# shellcheck disable=SC1090
source "$ENV_FILE"
: "${VERSION:?VERSION not set in .env}"
COMMIT_SHORT_SHA=$(git rev-parse --short=8 HEAD)

IMAGE_NAME="registry.git.iserv.eu/iserv/docker-cloudfiles:${COMMIT_SHORT_SHA}"

mkdir -p "$DATA_DIR"

echo ">>> Building Docker image: $IMAGE_NAME"
docker build \
  --platform=linux/amd64 \
  -t "$IMAGE_NAME" \
  --build-arg VERSION="$VERSION" \
  "$SCRIPT_DIR"

if [ -n "${CI:-}" ]; then
    echo ">>> Exporting image to tar.xz"
    docker image save "$IMAGE_NAME" | xz -T0 > "$DATA_DIR/image.tar.xz"
fi

echo ">>> Saving image tag"
echo "$IMAGE_NAME" > "$DATA_DIR/image.tag"

"$PROJECT_SCRIPTS_DIR"/cleanup-image-container
docker image tag "${IMAGE_NAME}" "iserv/cloudfiles:latest"

echo ">>> Done. Image and metadata available in $DATA_DIR"
echo ">>> Version: $VERSION"
