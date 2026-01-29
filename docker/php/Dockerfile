FROM php:8.1-fpm

COPY php.ini /usr/local/etc/php/

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    default-mysql-client zlib1g-dev libzip-dev unzip curl ca-certificates \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

ARG UID=1000
ARG GID=1000

RUN set -eux; \
    if ! getent group "${GID}" >/dev/null; then groupadd -g "${GID}" app; fi; \
    if ! id -u app >/dev/null 2>&1; then useradd -m -u "${UID}" -g "${GID}" -s /bin/bash app; fi; \
    mkdir -p /home/app/.config /home/app/.cache /home/app/.local/share; \
    chown -R "${UID}:${GID}" /home/app; \
    mkdir -p /var/www && chown -R "${UID}:${GID}" /var/www

ENV HOME=/home/app \
    XDG_CONFIG_HOME=/home/app/.config \
    XDG_CACHE_HOME=/home/app/.cache \
    XDG_DATA_HOME=/home/app/.local/share

WORKDIR /var/www

USER app
