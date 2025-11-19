#!/bin/bash

# Downloads the Docker image for IServ Cloudfiles from the GitLab Container Registry and saves it to a tar.xz file.

set -eu

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DATA_DIR="$SCRIPT_DIR/../../data"
PROJECT_SCRIPTS_DIR="$SCRIPT_DIR/../../lib/docker-cloudfiles"

if [[ -n ${1:-} ]]; then
  TAG=$1
else
  # If no tag is provided, compose the tag from the current commit short SHA.
  COMMIT_SHORT_SHA=$(git rev-parse --short=8 HEAD)
  TAG="${COMMIT_SHORT_SHA}"
fi

IMAGE="registry.git.iserv.eu/iserv/docker-cloudfiles:${TAG}"

mkdir -p "$DATA_DIR"

echo "Pulling image ${IMAGE}"
docker pull --platform linux/amd64 "${IMAGE}"

"$PROJECT_SCRIPTS_DIR"/cleanup-image-container

docker image tag "${IMAGE}" "iserv/cloudfiles:latest"

echo "Saving image tag to data/image.tag"
echo "$IMAGE" > "$DATA_DIR"/image.tag

if [ -n "${CI:-}" ]; then
    echo "Saving image to data/image.tar.xz"
    docker save "${IMAGE}" | xz -v - > "$DATA_DIR"/image.tar.xz
fi
