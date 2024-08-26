# WARNING: When updating adhere to the guidelines for valid updates as provided in the README
# TLDR: No skipping major releases. Only upgrade major release if on the last minor release.
# See: https://hub.docker.com/_/nextcloud/
# Source: https://github.com/nextcloud/docker
# The default version specified here will be used by the CI pipeline.
ARG VERSION=27.1.8

FROM git.iserv.eu:443/iserv/dependency_proxy/containers/alpine:3.20 AS patchstage
ARG VERSION

RUN apk update
RUN apk upgrade
RUN apk add bash git make npm patch

COPY ./source /source

RUN /source/clone_and_apply_patches.sh "${VERSION}" /nextcloud-server
RUN /source/create_combined_patches.sh /nextcloud-server /

# TODO: Here we could execute the tests. #73704

FROM git.iserv.eu:443/iserv/dependency_proxy/containers/nextcloud:${VERSION}-apache

ENV NEXTCLOUD_CONFIG_DIR=/var/www/html/config/

# Limit Apache MaxRequestWorkers to 25 to prevent out of memory
COPY ./apache/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

# Install git for binary patches
RUN apt-get update \
 && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    git vim nano \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

# Include required third-party apps in the image to avoid dependency on external network during runtime
# https://apps.nextcloud.com/apps/user_saml
RUN mkdir /iserv-apps \
    && curl -sLo user_saml.tar.gz "https://github.com/nextcloud-releases/user_saml/releases/download/v5.2.0/user_saml-v5.2.0.tar.gz" \
    && tar -xf user_saml.tar.gz \
    && mv user_saml /iserv-apps \
    && rm user_saml.tar.gz

# Theme to fix some css errors created by our patches
COPY ./theme /usr/src/nextcloud/themes/iserv
# Contains config scripts
COPY ./adds /

# Copy added files
COPY ./source/added /usr/src/nextcloud

# Apply combined patches
COPY --from=patchstage /*.patch /
RUN cd /usr/src/nextcloud && git apply --verbose --allow-empty /main.patch
RUN cd /usr/src/nextcloud/apps/activity && git apply --verbose --allow-empty /apps_activity.patch
RUN rm /*.patch

# Wrap default entrypoint to better suit our needs and reconfigure on every restart
# This makes config updates easier
ENTRYPOINT ["/entrypoint-iserv.sh"]
