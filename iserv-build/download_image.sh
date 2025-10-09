#!/bin/bash

# Downloads the Docker image for IServ Cloudfiles from the GitLab Container Registry and saves it to a tar.xz file.

set -eu

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DATA_DIR="$SCRIPT_DIR/../../data"

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

echo "Saving image ID to data/image.id"
docker inspect "${IMAGE}" --format="{{.Id}}" > "$DATA_DIR"/image.id

if [ -n "${CI:-}" ]; then
    echo "Saving image to data/image.tar.xz"
    docker save "${IMAGE}" | xz -v - > "$DATA_DIR"/image.tar.xz
fi
