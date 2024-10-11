#!/bin/sh
# Fail fast
set -e
# status.php is a file from core nextcloud. If it exists, there was a version mismatch => update and the copy already happened
# otherwise perform a copy ourselves
if [ ! -f /var/www/html/status.php ]; then
  echo "No nextcloud update. Copying installation manually"
  # Taken from https://github.com/nextcloud/docker/blob/111add0e1c8ccf67a35a33d4c0ca85a4c3908270/23/apache/entrypoint.sh#L104
  rsync -rlD --exclude=/config --exclude=version.php /usr/src/nextcloud/ /var/www/html/
fi

echo "enable provided apps (to allow upgrading)"
cp -r /iserv-apps/groupfolders /var/www/html/apps/
cp -r /iserv-apps/user_saml /var/www/html/apps/
# cp -r /iserv-apps/iservlogin /var/www/html/apps/

echo "Trying to upgrade apps"
# execute upgrade to trigger app updates (if required)
# According to the docs, this is required if apps were upgraded manually by replacing them with newer archives (https://docs.nextcloud.com/server/latest/admin_manual/configuration_server/occ_command.html?highlight=occ#command-line-upgrade)
php /var/www/html/occ upgrade

echo "Back-upping version.php"
# backup edited version.php again for it to be persisted
cp /var/www/html/version.php /version/version.php

php /var/www/html/occ app:enable groupfolders
# php /var/www/html/occ app:enable iservlogin

echo "configure options not available in config file"
# nextcloud should expect cron via normal cronjob
php /var/www/html/occ config:app:set core backgroundjobs_mode --value cron

php /var/www/html/occ config:system:set overwrite.cli.url --value="https://cloudfiles.$SUBDOMAIN_ROOT" --type=string

echo "Disallow public uploads in shares"
php /var/www/html/occ config:app:set core shareapi_allow_public_upload --value "no"
echo "Set trusted domains"
php /var/www/html/occ config:system:set trusted_domains 1 --value=cloudfiles.$SUBDOMAIN_ROOT
php /var/www/html/occ config:system:set trusted_domains 2 --value=cloudfiles.docker.$SUBDOMAIN_ROOT

echo "Set trusted proxies"
php /var/www/html/occ config:system:set trusted_proxies 0 --value=$TRUSTED_PROXIES

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

echo "Done! Starting apache..."
# start apache2 and begin servicing requests (taken from original Dockerfile)
apache2-foreground
