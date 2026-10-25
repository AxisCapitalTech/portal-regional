#!/bin/sh
set -eu
: "${STAGING_USER:?STAGING_USER obrigatorio}"
: "${STAGING_PASSWORD:?STAGING_PASSWORD obrigatorio}"
: "${WORDPRESS_DB_HOST:?WORDPRESS_DB_HOST obrigatorio}"
: "${WORDPRESS_DB_NAME:?WORDPRESS_DB_NAME obrigatorio}"
: "${WORDPRESS_DB_USER:?WORDPRESS_DB_USER obrigatorio}"
: "${WORDPRESS_DB_PASSWORD:?WORDPRESS_DB_PASSWORD obrigatorio}"
case "$STAGING_USER" in
  *[!a-zA-Z0-9._-]*|"") echo >&2 "STAGING_USER invalido"; exit 1 ;;
esac
if [ "$(printf '%s' "$STAGING_PASSWORD" | wc -c)" -lt 20 ]; then echo >&2 "STAGING_PASSWORD precisa de 20+ caracteres"; exit 1; fi
printf '%s\n' "$STAGING_PASSWORD" | htpasswd -i -B -c /etc/apache2/pr1-staging.htpasswd "$STAGING_USER" >/dev/null
chown root:www-data /etc/apache2/pr1-staging.htpasswd
chmod 0640 /etc/apache2/pr1-staging.htpasswd
mkdir -p /var/www/html/wp-content/uploads
chown -R www-data:www-data /var/www/html/wp-content/uploads
exec docker-entrypoint.sh "$@"
