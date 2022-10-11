#!/bin/bash
# Fail fast
set -e

php /var/www/html/occ app:disable firstrunwizard
php /var/www/html/occ app:disable weather_status
php /var/www/html/occ app:disable circles
php /var/www/html/occ app:disable dashboard
php /var/www/html/occ app:disable contactsinteraction
php /var/www/html/occ app:disable user_status
php /var/www/html/occ app:disable survey_client
php /var/www/html/occ app:disable privacy
php /var/www/html/occ app:disable sharebymail
php /var/www/html/occ app:disable support
php /var/www/html/occ app:disable nextcloud_announcements
php /var/www/html/occ app:disable updatenotification
php /var/www/html/occ app:disable serverinfo
php /var/www/html/occ app:disable federation
php /var/www/html/occ app:disable notifications
php /var/www/html/occ config:app:set workflowengine user_scope_disabled --value="yes"
