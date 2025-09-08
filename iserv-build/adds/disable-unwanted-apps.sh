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
php /var/www/html/occ app:disable notifications

# Disables photos app for cloudsafe
if [ "$INSTANCE_NAME" = "cloudsafe" ]; then
  php /var/www/html/occ app:disable photos
fi

# password_policy seems to be incompatible with LDAP or SAML
# (failing with "OCA\\Password_Policy\\ComplianceService::entryControl(): Argument #2 ($password) must be of type string, null given")
# As of 2023-06-01
php /var/www/html/occ app:disable password_policy
if [ "$ENABLE_RETENTION" -eq 0 ]; then
  php /var/www/html/occ app:disable files_retention
  php /var/www/html/occ config:app:set workflowengine user_scope_disabled --value="yes"
fi
