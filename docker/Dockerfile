FROM php:8.1-fpm

WORKDIR /var/www/html

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        curl \
        git \
        libonig-dev \
        libsqlite3-dev \
        nginx \
        unzip \
    ; \
    docker-php-ext-install mbstring pdo_sqlite sqlite3; \
    apt-get purge -y --auto-remove libonig-dev; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /etc/nginx/sites-enabled/default

RUN set -eux; \
    php -m | grep -q '^mbstring$'; \
    php -m | grep -q '^PDO$'; \
    php -m | grep -q '^pdo_sqlite$'; \
    php -m | grep -q '^sqlite3$'

RUN curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/composer

COPY docker/php.ini /usr/local/etc/php/conf.d/phpuploader.ini
COPY docker/php-fpm-www.conf /usr/local/etc/php-fpm.d/zz-phpuploader.conf
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/entrypoint.sh /usr/local/bin/phpuploader-entrypoint
COPY . /var/www/html

RUN set -eux; \
    chmod +x /usr/local/bin/phpuploader-entrypoint; \
    mkdir -p /usr/local/share/phpUploader /run/php /var/lib/nginx /var/log/nginx; \
    cp /var/www/html/config/config.php.example /usr/local/share/phpUploader/config.php.example; \
    mkdir -p /var/www/html/data /var/www/html/db /var/www/html/logs; \
    chown -R www-data:www-data /var/www/html /run/php /var/lib/nginx /var/log/nginx

EXPOSE 80

VOLUME ["/var/www/html/config", "/var/www/html/data", "/var/www/html/db", "/var/www/html/logs"]

ENTRYPOINT ["phpuploader-entrypoint"]
CMD ["nginx"]
