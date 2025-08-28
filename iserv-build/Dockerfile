# WARNING: When updating adhere to the guidelines for valid updates as provided in the README
# TLDR: No skipping major releases. Only upgrade major release if on the last minor release.
# See: https://hub.docker.com/_/nextcloud/
# Source: https://github.com/nextcloud/docker
# The default version specified here will be used by the CI pipeline.
ARG VERSION=28.0.9

FROM git.iserv.eu:443/iserv/dependency_proxy/containers/node:22-alpine AS patchstage
ARG VERSION

RUN apk update
RUN apk upgrade
RUN apk add bash git make patch

RUN npm config set registry https://npm.iserv.eu/ && npm config set audit false

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
# TODO: Have a central place to keep the app versions #76427
RUN mkdir /iserv-apps \
    && curl -sLo files_retention.tar.gz "https://github.com/nextcloud-releases/files_retention/releases/download/v1.17.2/files_retention-v1.17.2.tar.gz" \
    && tar -xf files_retention.tar.gz \
    && mv files_retention /iserv-apps \
    && rm files_retention.tar.gz \
    # keep version for files_linkeditor in sync with version number in clone_and_apply_patches.sh
    && curl -sLo files_linkeditor.tar.gz "https://github.com/te-online/nextcloud-app-releases/raw/main/files_linkeditor/v1.1.22/files_linkeditor.tar.gz" \
    && tar -xf files_linkeditor.tar.gz \
    && mv files_linkeditor /iserv-apps \
    && rm files_linkeditor.tar.gz \
    && curl -sLo groupfolders.tar.gz "https://github.com/nextcloud-releases/groupfolders/releases/download/v16.0.9/groupfolders-v16.0.9.tar.gz" \
    && tar -xf groupfolders.tar.gz \
    && mv groupfolders /iserv-apps \
    && rm groupfolders.tar.gz \
    && curl -sLo user_saml.tar.gz "https://github.com/nextcloud-releases/user_saml/releases/download/v6.6.0/user_saml-v6.6.0.tar.gz" \
    && tar -xf user_saml.tar.gz \
    && mv user_saml /iserv-apps \
    && rm user_saml.tar.gz \
    && curl -sLo richdocuments.tar.gz "https://github.com/nextcloud-releases/richdocuments/releases/download/v8.3.13/richdocuments-v8.3.13.tar.gz" \
    && tar -xf richdocuments.tar.gz \
    && mv richdocuments /iserv-apps \
    && rm richdocuments.tar.gz

COPY ./iservlogin /iserv-apps/iservlogin

# Theme to fix some css errors created by our patches
COPY ./theme /usr/src/nextcloud/themes/iserv
# Contains config scripts
COPY ./adds /

# Apply combined patches (will include added files)
COPY --from=patchstage /*.patch /
RUN cd /usr/src/nextcloud && git apply --verbose --allow-empty /main.patch
RUN cd /usr/src/nextcloud/apps/activity && git apply --verbose --allow-empty /apps_activity.patch
RUN cd /iserv-apps/files_retention && git apply --verbose --allow-empty /apps_files_retention.patch
RUN cd /iserv-apps/richdocuments && git apply --verbose --allow-empty /apps_richdocuments.patch
RUN cd /iserv-apps/user_saml && git apply --verbose --allow-empty /apps_user_saml.patch
RUN cd /usr/src/nextcloud/apps/viewer && git apply --verbose --allow-empty /apps_viewer.patch
RUN cd /iserv-apps/files_linkeditor && git apply --verbose --allow-empty /apps_files_linkeditor.patch
RUN rm /*.patch

# Wrap default entrypoint to better suit our needs and reconfigure on every restart
# This makes config updates easier
ENTRYPOINT ["/entrypoint-iserv.sh"]
