#!/usr/bin/env bash

# Builds the Docker image for IServ Cloudfiles and saves it to a tar.xz file.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="$SCRIPT_DIR/.env"
DATA_DIR="$SCRIPT_DIR/../../data"

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

echo ">>> Exporting image to tar.xz"
docker image save "$IMAGE_NAME" | xz -T0 > "$DATA_DIR/image.tar.xz"

echo ">>> Saving image ID"
docker image inspect "$IMAGE_NAME" --format '{{ .Id }}' > "$DATA_DIR/image.id"

echo ">>> Done. Image and metadata available in $DATA_DIR"
