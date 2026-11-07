FROM php:8.3.36-fpm-alpine3.24 AS app

# bash needed to support wait-for-it script
RUN apk add --update --no-cache git bash patch openssh dcron \
 && curl -sSLf \
         -o /usr/local/bin/install-php-extensions \
         https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
 && chmod +x /usr/local/bin/install-php-extensions \
 && install-php-extensions @composer zip pdo_mysql intl pcntl opcache imagick xdebug \
 # Create cron directory
 && mkdir -p /etc/cron.d \
 # Install wait-for-it script
 && curl "https://raw.githubusercontent.com/vishnubob/wait-for-it/81b1373f17855/wait-for-it.sh" -o /usr/local/bin/wait-for-it \
 && chmod a+x /usr/local/bin/wait-for-it
 # TODO: migrate to the build-pack secrets when they will implement compatibility with the docker-compose
 # Feature: https://docs.docker.com/develop/develop-images/build_enhancements/#new-docker-build-secret-information
 # Track issues: https://github.com/docker/compose/issues/6358, https://github.com/compose-spec/compose-spec/issues/81

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

FROM fholzer/nginx-brotli:v1.31 AS web

ENV PHP_SERVERS php:9000

# Add headers-more-nginx-module
RUN apk add --update --no-cache --virtual ".nginx-module-build-deps" \
    gcc \
    make \
    libc-dev \
    g++ \
    openssl-dev \
    linux-headers \
    pcre-dev \
    zlib-dev \
    libtool \
    automake \
    autoconf \
    git \
 && cd /opt \
 && git clone --depth 1 -b v0.39 --single-branch https://github.com/openresty/headers-more-nginx-module.git \
 && cd /opt/headers-more-nginx-module \
 && git submodule update --init \
 && cd /opt \
 && wget -O - http://nginx.org/download/nginx-$(nginx -v 2>&1 | sed 's@.*/@@').tar.gz | tar zxfv - \
 && cd /opt/nginx* \
 && ./configure --with-compat --add-dynamic-module=/opt/headers-more-nginx-module \
 && make modules \
 && cp /opt/nginx*/objs/ngx_http_headers_more_filter_module.so /usr/lib/nginx/modules/ \
 && sed -i '1iload_module \/usr\/lib\/nginx\/modules\/ngx_http_headers_more_filter_module.so;' /etc/nginx/nginx.conf \
 && rm -rf /opt/* \
 && apk del ".nginx-module-build-deps" \
 # Prepare image for the application
 && rm /etc/nginx/conf.d/default.conf \
 && mkdir -p /data/nginx/cache \
 && mkdir -p /var/www/html

WORKDIR /var/www/html

COPY ./docker/nginx/docker-entrypoint.sh /
COPY ./docker/nginx/generate-upstream.sh /usr/bin/generate-upstream
COPY ./docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/nginx/account.ely.by.conf.template /etc/nginx/conf.d/

ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]
