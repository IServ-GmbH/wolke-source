#!/bin/sh
#Fail fast
set -e
# Note for entry- and exitpoint: The container starts with an empty /var/www/html/. The actual source of nextcloud is
# located at /usr/src/nextcloud/. The container copies that source to /var/www/html/ on first install and
# consequent version updates but expects it to be persisted between restarts.
# We circumvent that by copying the source code ourselves if the update script doesn't in the exitpoint-iserv.sh.
# This behavior also leads to us copying version.php

# on first start our version.php does not exist, so skip
if [ -f /version/version.php ]; then
  echo "Copy version.php to facilitate nextcloud upgrades"
  # nextcloud checks the version.php in /var/www/html/ for currently installed version
  # since this dir is not persisted we copy our version.php there and backup it once the update completes
  cp /version/version.php /var/www/html/
fi
if [ -f /var/www/html/config/config.php ]; then
  echo "Enable config editing (required for nextcloud upgrades)"
  # Set config to be writable to allow being updated
  # https://docs.nextcloud.com/server/latest/admin_manual/configuration_server/config_sample_php_parameters.html#nextcloud-verifications
  # We set NEXTCLOUD_CONFIG_DIR in Dockerfile so occ uses the correct config folder even though it's in a different path
  php /usr/src/nextcloud/occ config:system:set config_is_read_only --value="false" --type=boolean || true # do not fail on failing config system
fi
echo "Running upstream nextcloud upgrade procedure"
# run the normal nextcloud update
# the param is the script that will be called when the update finishes
/entrypoint.sh /exitpoint-iserv.sh
