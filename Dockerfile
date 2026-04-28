FROM php:8.2-apache

# Extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql

# Activer mod_rewrite et mod_headers
RUN a2enmod rewrite headers expires deflate

# Copier la config Apache personnalisée
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Copier le script de démarrage
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Copier les fichiers du projet
COPY . /var/www/html/

# Permissions sur le dossier uploads
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/assets/images/uploads

# Exposer le port (Railway le surcharge via $PORT)
EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
