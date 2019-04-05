FROM php:7.2.7-fpm-alpine3.7

# bash needed to support wait-for-it script
RUN apk add --update --no-cache \
    git \
    bash \
    openssh \
    dcron \
    zlib-dev \
    icu-dev \
    libintl \
    imagemagick-dev \
    imagemagick \
 && docker-php-ext-install \
    zip \
    pdo_mysql \
    intl \
    pcntl \
    opcache \
 && apk add --no-cache --virtual ".phpize-deps" $PHPIZE_DEPS \
 && yes | pecl install xdebug-2.6.0 \
 && yes | pecl install imagick \
 && docker-php-ext-enable imagick \
 && apk del ".phpize-deps" \
 && rm -rf /usr/share/man \
 && rm -rf /tmp/* \
 && mkdir /etc/cron.d

COPY --from=composer:1.6.5 /usr/bin/composer /usr/bin/composer
COPY --from=node:9.11.2-alpine /usr/local/bin/node /usr/bin/
COPY --from=node:9.11.2-alpine /usr/lib/libgcc* /usr/lib/libstdc* /usr/lib/* /usr/lib/

# ENV variables for composer
ENV COMPOSER_NO_INTERACTION 1
ENV COMPOSER_ALLOW_SUPERUSER 1

RUN mkdir /root/.composer \
 && echo '{"github-oauth": {"github.com": "***REMOVED***"}}' > ~/.composer/auth.json \
 && composer global require --no-progress "hirak/prestissimo:^0.3.7" \
 && composer clear-cache

COPY ./docker/php/wait-for-it.sh /usr/local/bin/wait-for-it

COPY ./composer.* /var/www/html/

ARG build_env=prod
ENV YII_ENV=$build_env

RUN if [ "$build_env" = "prod" ] ; then \
        composer install --no-interaction --no-suggest --no-dev --optimize-autoloader; \
    else \
        composer install --no-interaction --no-suggest; \
    fi \
 && composer clear-cache

COPY ./docker/php/*.ini /usr/local/etc/php/conf.d/
COPY ./docker/php/docker-entrypoint.sh /usr/local/bin/
COPY ./docker/cron/* /etc/cron.d/

COPY ./api /var/www/html/api/
COPY ./common /var/www/html/common/
COPY ./console /var/www/html/console/
COPY ./yii /var/www/html/yii

# Expose everything under /var/www/html to share it with nginx
VOLUME ["/var/www/html"]

WORKDIR /var/www/html
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
