# Etapa 1: instala las dependencias de PHP sin las de desarrollo
FROM composer:2 AS dependencias

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

# Etapa 2: la imagen que corre en Render
FROM php:8.3-apache

# Apache atiende varias peticiones a la vez: una evaluacion larga no bloquea a los demas
RUN docker-php-ext-install pdo_mysql opcache \
    && a2enmod rewrite

COPY docker/php.ini /usr/local/etc/php/conf.d/traineeiq.ini
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/mpm.conf /etc/apache2/conf-enabled/traineeiq-mpm.conf

WORKDIR /var/www/html
COPY --from=dependencias /app ./

# Render indica el puerto en la variable PORT
ENV PORT=10000
ENV LOG_CHANNEL=stderr

RUN sed -i 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
        storage/logs storage/app/public bootstrap/cache \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/iniciar.sh

EXPOSE 10000

CMD ["docker/iniciar.sh"]
