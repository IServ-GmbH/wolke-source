# WARNING: When updating adhere to the guidelines for valid updates as provided in the README
# TLDR: No skipping major releases. Only upgrade major release if on the last minor release.
# IMPORTANT: Update version in build.sh as well!
# See: https://hub.docker.com/_/nextcloud/
# Source: https://github.com/nextcloud/docker
FROM nextcloud:23.0.7-apache

ENV NEXTCLOUD_CONFIG_DIR=/var/www/html/config/

# Install git for binary patches
RUN apt-get update \
 && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    git \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

# Include required third-party apps in the image to avoid dependency on external network during runtime
# https://apps.nextcloud.com/apps/user_saml
# https://apps.nextcloud.com/apps/groupfolders
RUN mkdir /iserv-apps \
    && curl -sLo groupfolders.tar.gz "https://github.com/nextcloud-releases/groupfolders/releases/download/v11.1.5/groupfolders-v11.1.5.tar.gz" \
    && tar -xf groupfolders.tar.gz \
    && mv groupfolders /iserv-apps \
    && rm groupfolders.tar.gz \
    \
    && curl -sLo user_saml.tar.gz "https://github.com/nextcloud-releases/user_saml/releases/download/v5.0.2/user_saml-v5.0.2.tar.gz" \
    && tar -xf user_saml.tar.gz \
    && mv user_saml /iserv-apps \
    && rm user_saml.tar.gz

# Theme to fix some css errors created by our patches
COPY ./theme /usr/src/nextcloud/themes/iserv
# Contains patches and config scripts
COPY ./adds /

# Apply backend patches
RUN cd /usr/src/nextcloud \
    && patch -p1 < /patches/remove_contactsmenu.patch \
    && patch -p1 < /patches/cleanup_settings.patch \
    && patch -p1 < /patches/cleanup_activity.patch \
    && patch -p1 < /patches/deactivate_send_mails.patch \
    && patch -p1 < /patches/deactivate_activity_jobs.patch \
    && patch -p1 < /patches/show_share_owner.patch \
    && patch -p1 < /patches/allow_public_upload_for_editing.patch

# Apply frontend patches
RUN cd /usr/src/nextcloud \
    && git apply /patches/generated/file_sharing_tab_combined.patch

# Wrap default entrypoint to better suit our needs and reconfigure on every restart
# This makes config updates easier
ENTRYPOINT ["/entrypoint-iserv.sh"]
