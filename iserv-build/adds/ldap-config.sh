#!/bin/bash

php /var/www/html/occ app:enable user_ldap

while [ "$(php /var/www/html/occ ldap:show-config | grep -c 's01')" -lt "1" ];
do
    php /var/www/html/occ ldap:create-empty-config
done

php /var/www/html/occ ldap:set-config s01 ldapAgentName "cn=cloudfiles,ou=ldap,$LDAP_BASE_DN"
php /var/www/html/occ ldap:set-config s01 ldapAgentPassword "$(cat /ldap_pass.txt)"
php /var/www/html/occ ldap:set-config s01 ldapHost "ldaps://$ISERV_DOMAIN"
php /var/www/html/occ ldap:set-config s01 ldapPort "10636"
php /var/www/html/occ ldap:set-config s01 ldapBase "$LDAP_BASE_DN"
php /var/www/html/occ ldap:set-config s01 ldapBaseGroups "ou=groups,$LDAP_BASE_DN"
php /var/www/html/occ ldap:set-config s01 ldapBaseUsers "ou=users,$LDAP_BASE_DN"
php /var/www/html/occ ldap:set-config s01 ldapConfigurationActive "1"
php /var/www/html/occ ldap:set-config s01 ldapExperiencedAdmin "1"
php /var/www/html/occ ldap:set-config s01 ldapUserFilter "(&(objectClass=uuidObject)(objectClass=person))"
php /var/www/html/occ ldap:set-config s01 ldapLoginFilter "(&(&(objectClass=uuidObject)(objectClass=person))(uid=%uid))"
php /var/www/html/occ ldap:set-config s01 ldapGroupFilter "(&(objectClass=uuidObject)(objectClass=posixGroup))"
php /var/www/html/occ ldap:set-config s01 ldapGroupMemberAssocAttr "member"
php /var/www/html/occ ldap:set-config s01 ldapEmailAttribute "mail"
php /var/www/html/occ ldap:set-config s01 ldapUserDisplayName "gecos"
php /var/www/html/occ ldap:set-config s01 ldapGroupDisplayName "cn" # Nextcloud doesn't allow spaces in group display names
php /var/www/html/occ ldap:set-config s01 ldapExpertUsernameAttr "uuid"
# See explanation in /doc/account_rename_postmortem.md
php /var/www/html/occ ldap:set-config s01 ldapExpertUUIDGroupAttr "uuid"
php /var/www/html/occ ldap:set-config s01 ldapExpertUUIDUserAttr "uuid"

