FROM php:8.2-apache

RUN apt-get update             && apt-get install -y --no-install-recommends                 libfreetype6-dev                 libjpeg62-turbo-dev                 libpng-dev                 libwebp-dev                 libzip-dev                 zlib1g-dev             && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp             && docker-php-ext-install -j$(nproc) gd mysqli zip             && a2enmod headers access_compat             && sed -ri 's!^Listen 80$!Listen 127.0.0.1:18084!' /etc/apache2/ports.conf             && sed -ri 's!<VirtualHost \*:80>!<VirtualHost 127.0.0.1:18084>!' /etc/apache2/sites-available/000-default.conf             && sed -ri 's!www-data!daemon!g' /etc/apache2/envvars             && rm -rf /var/lib/apt/lists/*

COPY apache-pagos-app.conf /etc/apache2/conf-enabled/apache-pagos-app.conf
COPY php-pagos-app.ini /usr/local/etc/php/conf.d/99-pagos-app.ini

WORKDIR /var/www/html

EXPOSE 18084
