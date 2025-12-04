#!/bin/bash

# Downloads the Docker image for IServ Cloudfiles from the GitLab Container Registry and saves it to a tar.xz file.

set -eu

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DATA_DIR="$SCRIPT_DIR/../../data"
PROJECT_SCRIPTS_DIR="$SCRIPT_DIR/../../lib/docker-cloudfiles"

if [ -f ".skip_download_image.pipeline" ]; then
    echo "build-package does not inherit env and creates a temporary commit causing our image tag to be wrong"
    echo "Skip pulling image and trust that we have artifacts from the previous job"
    exit 0
fi

if [[ -n ${1:-} ]]; then
  TAG=$1
else
  # If no tag is provided, compose the tag from the current commit short SHA.
  COMMIT_SHORT_SHA=$(git rev-parse --short=8 HEAD)
  TAG="${CI_COMMIT_SHORT_SHA:-$COMMIT_SHORT_SHA}"
fi

IMAGE="registry.git.iserv.eu/iserv/docker-cloudfiles:${TAG}"

if [ "$(cat "$DATA_DIR/image.tag")" = "$IMAGE" ]; then
  echo "Newest image already present"
  exit 0
fi

cat "$DATA_DIR/image.tag" 2>/dev/null || echo "No previous image tag found"

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
