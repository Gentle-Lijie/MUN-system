FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction

FROM php:8.3-apache
RUN docker-php-ext-install pdo pdo_mysql
WORKDIR /var/www/html
COPY --from=vendor /app/vendor /var/www/html/vendor
COPY . /var/www/html
RUN php database/migrate.php
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite \
    && mkdir -p /var/www/html/attachments /var/www/html/storage /var/www/html/bootstrap \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap /var/www/html/public /var/www/html/attachments
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 777 /var/www/html
EXPOSE 80
CMD ["apache2-foreground"]
