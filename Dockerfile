# Use the PHP 8.2 FPM image
FROM php:8.2-fpm-buster

# Install necessary packages and clean up after installation
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        libzip-dev \
        unzip \
        && docker-php-ext-install zip \
        && docker-php-ext-install pdo pdo_mysql \
        && apt-get clean \
        && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
        && rm -rf /var/cache/apt/archives/*
