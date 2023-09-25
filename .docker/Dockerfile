FROM php:8-cli-alpine as php-base

# Install composer
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /var/.composer

COPY --from=composer/composer:2.2.21-bin /composer /usr/bin/composer

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    apk del .build-deps && \
    rm -Rf /tmp/*

CMD tail -f /dev/null

WORKDIR /var/app

COPY composer.json ./

RUN composer install --no-dev --no-progress -anq --no-scripts

FROM php-base AS php-dev-base

EXPOSE 8080

RUN apk add linux-headers && \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS && \
    pecl install pcov xdebug && \
    docker-php-ext-enable pcov xdebug && \
    apk del .build-deps && \
    rm -rf /tmp/*
