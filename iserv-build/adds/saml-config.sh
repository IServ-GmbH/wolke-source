#!/bin/bash


php /var/www/html/occ app:enable user_saml
php /var/www/html/occ config:app:set user_saml general-allow_multiple_user_back_ends --value="0"
php /var/www/html/occ config:app:set user_saml general-use_saml_auth_for_desktop --value="1"
php /var/www/html/occ config:app:set user_saml general-require_provisioned_account --value="1"
php /var/www/html/occ config:app:set user_saml type --value="saml"


php /var/www/html/occ saml:config:set 1 \
  --sp-x509cert="$(cat /sp_certificate.pem)" \
  --sp-privateKey="$(cat /sp_key.pem)" \
  --idp-x509cert="$(cat /idp_certificate.pem)" \
  --idp-entityId="urn:uri:$ISERV_DOMAIN" \
  --general-idp0_display_name="IServ-SAML" \
  --general-uid_mapping="IDPUUID" \
  --idp-singleSignOnService.url="https://$ISERV_DOMAIN/iserv/samlprovider/plogon" \
  --idp-singleLogoutService.url="https://$ISERV_DOMAIN/iserv/samlprovider/logoff/response" \
  --idp-singleLogoutService.responseUrl="https://$ISERV_DOMAIN/iserv/samlprovider/logoff/response"
