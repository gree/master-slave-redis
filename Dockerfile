# syntax = docker/dockerfile:experimental
ARG PHP_VERSION=7.3.10
ARG COMPOSER_VERSION=1.9.0

# PHPの土台をつくるステージ
FROM php:${PHP_VERSION}-cli AS base

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

WORKDIR /var/www/html

RUN docker-php-source extract \
 && pecl install redis xdebug \
 && docker-php-ext-enable redis xdebug \
 && docker-php-source delete

# composerの準備をするステージ
FROM base as composer

RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
    git \
    ssh \
 && rm -rf /var/lib/apt/lists/*

# hadolint ignore=DL3022
COPY --from=composer:1.9.0 /usr/bin/composer /usr/bin/composer
#RUN composer config -g github-domains github.com git.gree-dev.net extra-ghe.dev.gree-dev.net

