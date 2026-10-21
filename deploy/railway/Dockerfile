FROM wordpress:php8.2-apache
RUN apt-get update && apt-get install -y --no-install-recommends apache2-utils && rm -rf /var/lib/apt/lists/*
COPY --chown=www-data:www-data wp-content/plugins/portal-regional-agentes/ /usr/src/wordpress/wp-content/plugins/portal-regional-agentes/
COPY --chown=www-data:www-data wp-content/themes/portal-regional-tema/ /usr/src/wordpress/wp-content/themes/portal-regional-tema/
COPY deploy/railway/auth.conf /etc/apache2/conf-available/pr1-staging-auth.conf
COPY deploy/railway/staging.php /etc/wordpress/staging.php
COPY deploy/railway/start.sh /usr/local/bin/pr1-staging-start
RUN chmod +x /usr/local/bin/pr1-staging-start && a2enconf pr1-staging-auth
ENV WORDPRESS_CONFIG_EXTRA="require_once '/etc/wordpress/staging.php';"
EXPOSE 80
ENTRYPOINT ["/usr/local/bin/pr1-staging-start"]
CMD ["apache2-foreground"]
