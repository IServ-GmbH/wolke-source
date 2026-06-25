#!/bin/sh
# Fail fast
set -e
# Refresh /var/www/html (a persistent bind-mount) from the image's pristine /usr/src/nextcloud when either:
#   * status.php is absent: a fresh install or an in-progress update
#   * the baked image identity differs: a newer image was loaded under the
#     existing install, so patched core/app files must reach /var/www/html.
SRC_REF="$(cat /usr/src/nextcloud/.iserv-image-ref 2>/dev/null || true)"
DST_REF="$(cat /var/www/html/.iserv-image-ref 2>/dev/null || true)"
if [ ! -f /var/www/html/status.php ] || [ "$SRC_REF" != "$DST_REF" ]; then
  echo "Refreshing nextcloud installation (fresh install or image changed)"
  # Based on https://github.com/nextcloud/docker/blob/111add0e1c8ccf67a35a33d4c0ca85a4c3908270/23/apache/entrypoint.sh#L104
  # --delete prunes files removed between image versions
  # /config and version.php are separate mounts, so they are preserved
  rsync -rlD --delete --exclude=/config --exclude=version.php --exclude=/.iserv-image-ref /usr/src/nextcloud/ /var/www/html/
fi

echo "enable provided apps (to allow upgrading)"
cp -r /iserv-apps/files_retention /var/www/html/apps/
cp -r /iserv-apps/files_linkeditor /var/www/html/apps/
cp -r /iserv-apps/groupfolders /var/www/html/apps/
cp -r /iserv-apps/user_saml /var/www/html/apps/
cp -r /iserv-apps/iservlogin /var/www/html/apps/
cp -r /iserv-apps/richdocuments /var/www/html/apps/

# Record the image identity now that the install (incl. iserv apps) is in place,
# so the next start skips the refresh unless a new image is loaded.
cp /usr/src/nextcloud/.iserv-image-ref /var/www/html/.iserv-image-ref

echo "Trying to upgrade apps"
# execute upgrade to trigger app updates (if required)
# According to the docs, this is required if apps were upgraded manually by replacing them with newer archives (https://docs.nextcloud.com/server/latest/admin_manual/configuration_server/occ_command.html?highlight=occ#command-line-upgrade)
php /var/www/html/occ upgrade

echo "Back-upping version.php"
# backup edited version.php again for it to be persisted
cp /var/www/html/version.php /version/version.php

php /var/www/html/occ app:enable files_retention
php /var/www/html/occ app:enable groupfolders
php /var/www/html/occ app:enable files_linkeditor
php /var/www/html/occ app:enable iservlogin

echo "configure options not available in config file"
# nextcloud should expect cron via normal cronjob
php /var/www/html/occ config:app:set core backgroundjobs_mode --value cron

php /var/www/html/occ config:system:set overwrite.cli.url --value="https://$INSTANCE_NAME.$SUBDOMAIN_ROOT" --type=string

# set public_upload
echo "Allow public uploads in shares"
if [ "$ENABLE_PUBLIC_UPLOAD" = "1" ] && [ "$INSTANCE_NAME" = "cloudfiles" ]; then
  php /var/www/html/occ config:app:set core shareapi_allow_public_upload --value "yes"
  php /var/www/html/occ config:app:set core shareapi_allow_links_exclude_groups --value '["00_STUDENT"]'
else
  echo "Disallow public uploads in shares"
  php /var/www/html/occ config:app:set core shareapi_allow_public_upload --value "no"
fi

echo "Set trusted domains"
php /var/www/html/occ config:system:set trusted_domains 1 --value="$INSTANCE_NAME.$SUBDOMAIN_ROOT"
php /var/www/html/occ config:system:set trusted_domains 2 --value="$INSTANCE_NAME.docker.$SUBDOMAIN_ROOT"

echo "Set trusted proxies"
php /var/www/html/occ config:system:set trusted_proxies 0 --value=$TRUSTED_PROXIES

# enforce passwords on public share links for cloudsafe
if [ -n "$SHAREAPI_ENFORCE_LINKS_PASSWORD" -a "$SHAREAPI_ENFORCE_LINKS_PASSWORD" = "1" ]; then
  php /var/www/html/occ config:app:set core shareapi_enforce_links_password --value="yes"
fi

echo "Configuring theme..."
/theming-config.sh
echo "Configured theme."

echo "Configuring ldap..."
/ldap-config.sh
echo "Configured ldap."

echo "Configuring saml..."
/saml-config.sh
echo "Configured saml."

echo "Disabling unwanted apps"
/disable-unwanted-apps.sh

echo "Disabling config editing"
# disable config editing again since the update is done
php /var/www/html/occ config:system:set config_is_read_only --value="true" --type=boolean

echo "Done! Starting PHP-FPM..."
exec php-fpm -F
