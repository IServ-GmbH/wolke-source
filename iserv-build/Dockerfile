# WARNING: When updating adhere to the guidelines for valid updates as provided in the README
# TLDR: No skipping major releases. Only upgrade major release if on the last minor release.
# IMPORTANT: Update version in build.sh as well!
# See: https://hub.docker.com/_/nextcloud/
# Source: https://github.com/nextcloud/docker
FROM nextcloud:26.0.4-apache

ENV NEXTCLOUD_CONFIG_DIR=/var/www/html/config/

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
# Contains patches and config scripts
COPY ./adds /

# Apply patches - Refer to README.md for description of individual patches.

# Backend patches
# This is not the most efficient way in term of image layers but it's easier to find faulty patches during updates
RUN cd /usr/src/nextcloud && patch -p1 < /patches/remove_contactsmenu.patch
RUN cd /usr/src/nextcloud && patch -p1 < /patches/cleanup_settings.patch
RUN cd /usr/src/nextcloud && patch -p1 < /patches/cleanup_activity.patch
RUN cd /usr/src/nextcloud && patch -p1 < /patches/deactivate_send_mails.patch
RUN cd /usr/src/nextcloud && patch -p1 < /patches/deactivate_activity_jobs.patch
RUN cd /usr/src/nextcloud && patch -p1 < /patches/show_share_owner.patch

# Frontend patches
RUN cd /usr/src/nextcloud && git apply /patches/generated/generated_combined.patch

# Wrap default entrypoint to better suit our needs and reconfigure on every restart
# This makes config updates easier
ENTRYPOINT ["/entrypoint-iserv.sh"]
