FROM hyperf/hyperf:8.3-alpine-v3.19-swoole
LABEL maintainer="Vinicius Azevedo <viniciusdiazevedo@gmail.com>"

ARG env=dev

RUN set -ex \
    && apk update \
    && apk add --no-cache \
        git \
        openssh-client \
        inotify-tools \
        procps \
    && rm -rf /tmp/* /usr/share/man

RUN composer require --dev hyperf/watcher

WORKDIR /app

COPY ../ /app
RUN if [ "$env" = "dev" ]; then \
        composer install \
    ;else \
        composer install --no-dev -o && \
        composer dump-autoload -o \
    ;fi

# RUN php bin/hyperf.php && \
#     chmod +x /app/docker/entrypoint.sh

EXPOSE 9501

ENTRYPOINT ["/app/docker/entrypoint.sh"]