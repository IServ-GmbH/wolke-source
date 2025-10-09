#!/bin/sh
#Fail fast
set -e
# Note for entry- and exitpoint: The container starts with an empty /var/www/html/. The actual source of nextcloud is
# located at /usr/src/nextcloud/. The container copies that source to /var/www/html/ on first install and
# consequent version updates but expects it to be persisted between restarts.
# We circumvent that by copying the source code ourselves if the update script doesn't in the exitpoint-iserv.sh.
# This behavior also leads to us copying version.php

# --- Memory-based MaxRequestWorkers ---
MEM_LIMIT_BYTES=$(cat /sys/fs/cgroup/memory.max)

if [ "$MEM_LIMIT_BYTES" = "max" ] || [ "$MEM_LIMIT_BYTES" -gt 1000000000000 ]; then
    # container is unlimited, fallback to host memory
    MEM_TOTAL_MB=$(awk '/MemTotal/ {print int($2/1024)}' /proc/meminfo)
else
    MEM_TOTAL_MB=$((MEM_LIMIT_BYTES / 1024 / 1024))
fi

if [ "$MEM_TOTAL_MB" -le 4096 ]; then
    WORKERS=8
elif [ "$MEM_TOTAL_MB" -le 8192 ]; then
    WORKERS=12
else
    WORKERS=25
fi

echo "Configuring Apache: Detected ${MEM_TOTAL_MB}MB RAM → MaxRequestWorkers=${WORKERS}"

# bc www-data user does not have write access to /etc/apache2/mods-enabled/ we utilize the symlink alredy created in the Dockerfile
sed "s/{{MAX_REQUEST_WORKERS}}/${WORKERS}/g" \
    /etc/apache2/mods-enabled/mpm_prefork.conf.template \
    > /tmp/mpm_prefork.conf
# --------------------------------------

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
  # We have to use sed instead of occ because occ does not support editing config file if it's read-only
  # Same as: php /var/www/html/occ config:system:set config_is_read_only --value="false" --type=boolean
  sed -i'' "s/'config_is_read_only'\s*=>\s*true/'config_is_read_only' => false/" /var/www/html/config/config.php
fi
echo "Running upstream nextcloud upgrade procedure"
# run the normal nextcloud update
# the param is the script that will be called when the update finishes
/entrypoint.sh /exitpoint-iserv.sh
