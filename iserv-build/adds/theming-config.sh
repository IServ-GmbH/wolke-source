#!/bin/bash
# Fail Fast
set -e

php /var/www/html/occ theming:config url https://$ISERV_DOMAIN
#use a color instead of a background image
php /var/www/html/occ theming:config background backgroundColor

if [ -n "${BG_COLOR}" ]; then
  #default iserv-turquoise color for cloudsafe
  php /var/www/html/occ theming:config color "${BG_COLOR}"
else
  #default color iserv-steelblue for cloudfiles
  php /var/www/html/occ theming:config color "#7296C8"
fi

#set default logo, header, favicon for cloudsafe or cloudfiles
if [ -n "$LOGO_FILENAME_PREFIX" ]; then
  php /var/www/html/occ theming:config logo "/${LOGO_FILENAME_PREFIX}-logo.svg"
  php /var/www/html/occ theming:config logoheader "/${LOGO_FILENAME_PREFIX}-logo-header.svg"
  php /var/www/html/occ theming:config favicon "/${LOGO_FILENAME_PREFIX}-logo.png"
else
  php /var/www/html/occ theming:config logo /cloudfiles-logo.svg
  php /var/www/html/occ theming:config logoheader /cloudfiles-logo-header.svg
  php /var/www/html/occ theming:config favicon /cloudfiles-logo.png
fi

#set default logo, header, favicon for cloudsafe or cloudfiles
if [ -n "$APP_NAME" ]; then
  php /var/www/html/occ theming:config name "${APP_NAME}"
  php /var/www/html/occ theming:config slogan "IServ ${APP_NAME}"
else
  php /var/www/html/occ theming:config name Wolke
  php /var/www/html/occ theming:config slogan "IServ Wolke"
fi
