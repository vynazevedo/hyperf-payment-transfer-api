FROM hyperf/hyperf:8.1-alpine-v3.16-swoole
LABEL maintainer="Vinicius Azevedo <viniciusdiazevedo@gmail.com>"

ARG env=prod

RUN set -ex \
    && apk update \
    && apk add --no-cache \
        libstdc++ \
        openssl \
        git \
        openssh-client \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        libmcrypt-dev \
        $PHPIZE_DEPS \
        inotify-tools \
        procps \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        sockets \
        zip \
        opcache \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');" \
    && apk del --no-network $PHPIZE_DEPS \
    && rm -rf /tmp/* /usr/share/man \
    && composer global require hyperf/devtool \
    && composer clear-cache

RUN composer require --dev hyperf/watcher

RUN if [ "$env" = "prod" ]; then \
        mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    ;else \
        mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    ;fi

COPY ./docker/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY ./docker/php/conf.d/swoole.ini /usr/local/etc/php/conf.d/swoole.ini

WORKDIR /app

COPY .. /app
RUN if [ "$env" = "dev" ]; then \
        composer install \
    ;else \
        composer install --no-dev -o && \
        composer dump-autoload -o \
    ;fi

RUN php bin/hyperf.php && \
    chmod +x /app/docker/entrypoint.sh

EXPOSE 9501

ENTRYPOINT ["/app/docker/entrypoint.sh"]