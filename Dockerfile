FROM php:7.4.15-fpm-alpine3.13 AS app

# bash needed to support wait-for-it script
RUN apk add --update --no-cache \
    git \
    bash \
    patch \
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
 && yes | pecl install xdebug-2.9.8 \
 && yes | pecl install imagick \
 && docker-php-ext-enable imagick \
 && apk del ".phpize-deps" \
 && rm -rf /usr/share/man \
 && rm -rf /tmp/* \
 # Create cron directory
 && mkdir -p /etc/cron.d \
 # Install wait-for-it script
 && curl "https://raw.githubusercontent.com/vishnubob/wait-for-it/81b1373f17855/wait-for-it.sh" -o /usr/local/bin/wait-for-it \
 && chmod a+x /usr/local/bin/wait-for-it \
 # Install composer and global dependencies
 && curl "https://getcomposer.org/download/2.0.9/composer.phar" -o /usr/bin/composer \
 && chmod a+x /usr/bin/composer
 # TODO: migrate to the build-pack secrets when they will implement compatibility with the docker-compose
 # Feature: https://docs.docker.com/develop/develop-images/build_enhancements/#new-docker-build-secret-information
 # Track issues: https://github.com/docker/compose/issues/6358, https://github.com/compose-spec/compose-spec/issues/81

COPY ./patches /var/www/html/patches/
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

FROM fholzer/nginx-brotli:v1.16.0 AS web

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

FROM bitnami/mariadb:10.3.20-debian-9-r4 AS db

USER 0

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

USER 1001

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/run.sh"]
