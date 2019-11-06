FROM php:7.3.11-fpm-alpine3.10 AS app

# ENV variables for composer
ENV COMPOSER_NO_INTERACTION 1
ENV COMPOSER_ALLOW_SUPERUSER 1

# bash needed to support wait-for-it script
RUN apk add --update --no-cache \
    git \
    bash \
    openssh \
    dcron \
    zlib-dev \
    libzip-dev \
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
 && yes | pecl install xdebug-2.7.1 \
 && yes | pecl install imagick \
 && docker-php-ext-enable imagick \
 && apk del ".phpize-deps" \
 && rm -rf /usr/share/man \
 && rm -rf /tmp/* \
 && mkdir /etc/cron.d

COPY --from=composer:1.8.4 /usr/bin/composer /usr/bin/composer
COPY --from=node:11.13.0-alpine /usr/local/bin/node /usr/bin/
COPY --from=node:11.13.0-alpine /usr/lib/libgcc* /usr/lib/libstdc* /usr/lib/* /usr/lib/

RUN mkdir /root/.composer \
 && echo '{"github-oauth": {"github.com": "***REMOVED***"}}' > ~/.composer/auth.json \
 && composer global require --no-progress "hirak/prestissimo:^0.3.8" \
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

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

# ================================================================================

FROM nginx:1.15.10-alpine AS web

ENV PHP_SERVERS php:9000

RUN rm /etc/nginx/conf.d/default.conf \
 && mkdir -p /data/nginx/cache \
 && mkdir -p /var/www/html

WORKDIR /var/www/html

COPY ./docker/nginx/docker-entrypoint.sh /
COPY ./docker/nginx/generate-upstream.sh /usr/bin/generate-upstream
COPY ./docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/nginx/account.ely.by.conf.template /etc/nginx/conf.d/

ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]

# ================================================================================

FROM mariadb:10.3.14-bionic AS db

COPY ./docker/mariadb/config.cnf /etc/mysql/conf.d/

RUN set -ex \
 && fetchDeps='ca-certificates wget' \
 && apt-get update \
 && apt-get install -y --no-install-recommends $fetchDeps \
 && rm -rf /var/lib/apt/lists/* \
 && wget -O /mysql-sys.tar.gz 'https://github.com/mysql/mysql-sys/archive/1.5.1.tar.gz' \
 && mkdir /mysql-sys \
 && tar -zxf /mysql-sys.tar.gz -C /mysql-sys \
 && rm /mysql-sys.tar.gz \
 && cd /mysql-sys/*/ \
 && ./generate_sql_file.sh -v 56 -m \
 # Fix mysql-sys for MariaDB according to the https://www.fromdual.com/mysql-sys-schema-in-mariadb-10-2 notes
 # and https://mariadb.com/kb/en/library/system-variable-differences-between-mariadb-100-and-mysql-56/ reference
 && sed -i -e "s/@@global.server_uuid/@@global.server_id/g" gen/*.sql \
 && sed -i -e "s/@@master_info_repository/NULL/g" gen/*.sql \
 && sed -i -e "s/@@relay_log_info_repository/NULL/g" gen/*.sql \
 # Wrap each command, that contains more than one ; terminator into DELIMITER
 # and replace the last one to avoid mysql errors
 && sed -i -E 's/^(.+;.+);$/DELIMITER $$\n\n\1$$\n\nDELIMITER ;/g' gen/*.sql \
 && mv gen/*.sql /docker-entrypoint-initdb.d/ \
 && cd / \
 && rm -rf /mysql-sys \
 && apt-get purge -y --auto-remove $fetchDeps

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["mysqld"]
