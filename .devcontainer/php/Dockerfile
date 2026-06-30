ARG VARIANT=8.1-fpm
FROM php:${VARIANT}

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        curl \
        git \
        unzip \
    ; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/*

RUN curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/composer

COPY php.ini /usr/local/etc/php/conf.d/phpuploader.ini
COPY php-fpm-www.conf /usr/local/etc/php-fpm.d/zz-phpuploader.conf
