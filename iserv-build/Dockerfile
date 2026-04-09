# syntax=docker/dockerfile:1
# WARNING: When updating adhere to the guidelines for valid updates as provided in the README
# TLDR: No skipping major releases. Only upgrade major release if on the last minor release.
# See: https://hub.docker.com/_/nextcloud/
# Source: https://github.com/nextcloud/docker
ARG VERSION=dummy

# ============================================================
# Stage: clone
# Clones upstream repos. Cached by VERSION alone — patch
# changes do NOT invalidate this layer.
# ============================================================
FROM git.iserv.eu:443/iserv/dependency_proxy/containers/node:22-alpine AS clone
ARG VERSION

RUN <<EOF
  apk update
  apk upgrade
  apk add bash git make patch
EOF
RUN npm config set registry https://npm.iserv.eu/ && npm config set audit false

COPY ./source/clone_and_apply_patches.sh /source/clone_and_apply_patches.sh
COPY ./source/apply_patches.sh /source/apply_patches.sh
RUN /source/clone_and_apply_patches.sh --clone-only "${VERSION}" /nextcloud-server

# ============================================================
# Stage: js-main
# Builds main Nextcloud JS. Only invalidated when Vue/JS
# source patches for core apps change.
# npm ci runs BEFORE COPY of patches → cached independently.
# ============================================================
FROM clone AS js-main

# PUPPETEER_DOWNLOAD_BASE_URL: Nextcloud's npm dependencies include puppeteer,
# which downloads Chrome during `npm ci`. The default URL is often unreliable,
# so we point it to Google's official mirror.
RUN --mount=type=cache,target=/root/.npm,id=npm-main \
    cd /nextcloud-server \
    && PUPPETEER_DOWNLOAD_BASE_URL="https://storage.googleapis.com/chrome-for-testing-public" npm ci

# src/ patches for apps built by make build-js-production.
# Exclude apps that have dedicated JS stages (js-richdocuments, js-viewer,
# js-linkeditor) — including them here would defeat their cache isolation.
COPY ./source/patches/core/src/ /source/patches/core/src/
COPY ./source/patches/apps/theming/src/ /source/patches/apps/theming/src/
COPY ./source/patches/apps/files/src/ /source/patches/apps/files/src/
COPY ./source/patches/apps/files_versions/src/ /source/patches/apps/files_versions/src/
COPY ./source/patches/apps/files_sharing/src/ /source/patches/apps/files_sharing/src/

RUN <<EOF
  set -eo pipefail
  /source/apply_patches.sh /source/patches /nextcloud-server
  # Note from past self: You cannot use the MODULE env var to speed this build up.
  # The resulting file will be loaded in the browser but not executed (No idea why)
  make -C /nextcloud-server build-js-production
  git -C /nextcloud-server add -N .
  git -C /nextcloud-server diff --binary -- ':!apps/*/src/*' ':!core/src/*' > /nextcloud_js.patch
EOF

# ============================================================
# Stage: js-richdocuments
# ============================================================
FROM clone AS js-richdocuments

RUN --mount=type=cache,target=/root/.npm,id=npm-richdocuments \
    cd /nextcloud-server/apps/richdocuments && npm ci

COPY ./source/patches/apps/richdocuments/src/ /source/patches/apps/richdocuments/src/

RUN <<EOF
  set -eo pipefail
  /source/apply_patches.sh /source/patches /nextcloud-server
  cd /nextcloud-server/apps/richdocuments
  npm run build
  git add -N .
  git diff --binary -- ':!src/' > /app_richdocuments_js.patch
EOF

# ============================================================
# Stage: js-viewer
# ============================================================
FROM clone AS js-viewer

RUN --mount=type=cache,target=/root/.npm,id=npm-viewer \
    cd /nextcloud-server/apps/viewer && npm ci

COPY ./source/patches/apps/viewer/src/ /source/patches/apps/viewer/src/

RUN <<EOF
  set -eo pipefail
  /source/apply_patches.sh /source/patches /nextcloud-server
  cd /nextcloud-server/apps/viewer
  npm run build
  git add -N .
  git diff --binary -- ':!src/' > /app_viewer_js.patch
EOF

# ============================================================
# Stage: js-linkeditor
# ============================================================
FROM clone AS js-linkeditor

RUN --mount=type=cache,target=/root/.npm,id=npm-linkeditor \
    cd /nextcloud-server/apps/files_linkeditor && npm ci

COPY ./source/patches/apps/files_linkeditor/src/ /source/patches/apps/files_linkeditor/src/

RUN <<EOF
  set -eo pipefail
  /source/apply_patches.sh /source/patches /nextcloud-server
  cd /nextcloud-server/apps/files_linkeditor
  npm run build
  git add -N .
  git diff --binary -- ':!src/' > /app_files_linkeditor_js.patch
EOF

# ============================================================
# Stage: php-patches
# Applies ALL patches, creates combined diffs excluding
# Vue/Svelte source. No JS compilation
# Iterates over all sub-repos automatically.
# ============================================================
FROM clone AS php-patches
ARG VERSION

COPY ./source /source

RUN <<EOF
  set -eo pipefail
  /source/clone_and_apply_patches.sh "${VERSION}" /nextcloud-server
  mkdir /patches

  # Main repo patch (excludes JS source — the upstream production image doesn't
  # ship src/ directories, so we can't patch them into the final image)
  git -C /nextcloud-server add -N .
  git -C /nextcloud-server diff --binary -- ':!apps/*/src/*' ':!core/src/*' > /patches/nextcloud_php.patch

  # Per-app patches: iterate over all separately-cloned app repos
  # (these have their own .git, so the main repo diff above doesn't capture them)
  for app_dir in /nextcloud-server/apps/*/; do
    [ -d "$app_dir/.git" ] || continue
    app_name=$(basename "$app_dir")
    git -C "$app_dir" add -N .
    git -C "$app_dir" diff --binary -- ':!src/' ':!*.js' > "/patches/app_${app_name}_php.patch"
  done
EOF

# TODO: Here we could execute the tests. #73704

# ============================================================
# Stage: production
# ============================================================
FROM git.iserv.eu:443/iserv/dependency_proxy/containers/nextcloud:${VERSION}-fpm
ARG VERSION

RUN test "$VERSION" != "dummy"

ENV NEXTCLOUD_CONFIG_DIR=/var/www/html/config/

# Install git for binary patches
RUN <<EOF
  set -e
  apt-get update
  DEBIAN_FRONTEND=noninteractive apt-get install -y git vim nano
  apt-get clean
  rm -rf /var/lib/apt/lists/*
EOF

# Include required third-party apps in the image to avoid dependency on external network during runtime
# https://apps.nextcloud.com/apps/user_saml
# TODO: Have a central place to keep the app versions #76427
# remember to keep version for files_linkeditor in sync with version number in clone_and_apply_patches.sh
RUN <<EOF
  set -e
  mkdir /iserv-apps

  curl -sLo files_retention.tar.gz "https://github.com/nextcloud-releases/files_retention/releases/download/v2.0.1/files_retention-v2.0.1.tar.gz"
  tar -xf files_retention.tar.gz
  mv files_retention /iserv-apps
  rm files_retention.tar.gz

  curl -sLo files_linkeditor.tar.gz "https://github.com/te-online/nextcloud-app-releases/raw/main/files_linkeditor/v1.1.23/files_linkeditor.tar.gz"
  tar -xf files_linkeditor.tar.gz
  mv files_linkeditor /iserv-apps
  rm files_linkeditor.tar.gz

  curl -sLo groupfolders.tar.gz "https://github.com/nextcloud-releases/groupfolders/releases/download/v19.1.17/groupfolders-v19.1.17.tar.gz"
  tar -xf groupfolders.tar.gz
  mv groupfolders /iserv-apps
  rm groupfolders.tar.gz

  curl -sLo user_saml.tar.gz "https://github.com/nextcloud-releases/user_saml/releases/download/v7.1.3/user_saml-v7.1.3.tar.gz"
  tar -xf user_saml.tar.gz
  mv user_saml /iserv-apps
  rm user_saml.tar.gz

  curl -sLo richdocuments.tar.gz "https://github.com/nextcloud-releases/richdocuments/releases/download/v8.7.8/richdocuments-v8.7.8.tar.gz"
  tar -xf richdocuments.tar.gz
  mv richdocuments /iserv-apps
  rm richdocuments.tar.gz
EOF

COPY ./iservlogin /iserv-apps/iservlogin

# Theme to fix some css errors created by our patches
COPY ./theme /usr/src/nextcloud/themes/iserv
# Contains config scripts
COPY ./adds /

# Collect patches from all stages
COPY --from=php-patches /patches/ /patches/
COPY --from=js-main /nextcloud_js.patch /patches/
COPY --from=js-richdocuments /app_richdocuments_js.patch /patches/
COPY --from=js-viewer /app_viewer_js.patch /patches/
COPY --from=js-linkeditor /app_files_linkeditor_js.patch /patches/

# Apply all patches. Main repo patches (nextcloud_*) are applied from /usr/src/nextcloud.
# Per-app patches (app_*) auto-detect their target directory.
RUN <<EOF
  set -e

  cd /usr/src/nextcloud
  for patch in /patches/nextcloud_*.patch; do
    [ -f "$patch" ] || continue
    echo "Applying $patch"
    git apply --verbose --allow-empty "$patch"
  done

  for patch in /patches/app_*.patch; do
    [ -f "$patch" ] || continue
    app_name=$(basename "$patch" | sed 's/^app_//; s/_\(php\|js\)\.patch$//')
    if [ -d "/usr/src/nextcloud/apps/$app_name" ]; then
      cd "/usr/src/nextcloud/apps/$app_name"
    elif [ -d "/iserv-apps/$app_name" ]; then
      cd "/iserv-apps/$app_name"
    else
      echo "ERROR: No target directory for app '$app_name'"
      exit 1
    fi
    echo "Applying $patch in $(pwd)"
    git apply --verbose --allow-empty "$patch"
  done

  rm -rf /patches
EOF

# Pipe Access Logging from FPM to /dev/null, bc its the same as the nginx logs
RUN echo '[www] \n\
access.log = /dev/null' > /usr/local/etc/php-fpm.d/zz-iserv.conf

# Wrap default entrypoint to better suit our needs and reconfigure on every restart
# This makes config updates easier
ENTRYPOINT ["/entrypoint-iserv.sh"]

EXPOSE 9000
CMD ["php-fpm"]
