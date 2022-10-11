#!/bin/bash
# Fail Fast
set -e

php /var/www/html/occ theming:config logo /logo.svg
php /var/www/html/occ theming:config logoheader /logo-header.svg
php /var/www/html/occ theming:config favicon /logo.png
php /var/www/html/occ theming:config name Wolke
php /var/www/html/occ theming:config url https://$ISERV_DOMAIN
php /var/www/html/occ theming:config slogan "IServ Wolke"
