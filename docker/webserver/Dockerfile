ARG PHP_VERSION=8.4

FROM php:${PHP_VERSION}-rc-apache-bookworm AS base

FROM base AS development

WORKDIR /var/www/html

# Enable custom error reporting
COPY ./docker/webserver/conf.d/error.ini /usr/local/etc/php/conf.d/20-error.ini

# Install and enable the PHP memcached and intl extensions
RUN set -eux; \
    apt-get update && \
    apt-get install -y --no-install-recommends \
            libicu-dev \
            libmemcached-dev \
            libssl-dev \
            libzip-dev \
            libzip4 \
            zlib1g-dev && \
    docker-php-ext-install intl && \
    printf "" | pecl install -D 'with-libmemcached-dir="/usr"' memcached && \
    docker-php-ext-enable memcached && \
    rm -rf /var/lib/apt/lists/*

# Set up Composer
COPY --from=composer:2.2.7 /usr/bin/composer /usr/bin/

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
