FROM php:7.0-fpm

ENV PATH $PATH:/root/.composer/vendor/bin

# PHP extensions come first, as they are less likely to change between Yii releases
RUN apt-get update \
 && apt-get -y install \
         git \
         g++ \
         libicu-dev \
         libmcrypt-dev \
         zlib1g-dev \
     --no-install-recommends \

 # Install PHP extensions
 && docker-php-ext-install intl \
 && docker-php-ext-install pdo_mysql \
 && docker-php-ext-install mbstring \
 && docker-php-ext-install mcrypt \
 && docker-php-ext-install zip \
 && docker-php-ext-install bcmath \

 && apt-get purge -y g++ \
 && apt-get autoremove -y \
 && rm -r /var/lib/apt/lists/* \

 # Don't clear our valuable environment vars in PHP
 && echo "\nclear_env = no" >> /usr/local/etc/php-fpm.conf \

 # Fix write permissions with shared folders
 && usermod -u 1000 www-data

# copy-paste from https://github.com/nodejs/docker-node/blob/910443c39c80291f0bf24712d8d94279cf15b7b5/6.5/wheezy/Dockerfile

# gpg keys listed at https://github.com/nodejs/node
RUN set -ex \
 && for key in \
        9554F04D7259F04124DE6B476D5A82AC7E37093B \
        94AE36675C464D64BAFA68DD7434390BDBE9B9C5 \
        0034A06D9D9B0064CE8ADF6BF1747F4AD2306D93 \
        FD3A5288F042B6850C66B31F09FE44734EB7990E \
        71DCFD284A79C3B38668286BC97EC7A07EDE3FC1 \
        DD8F2338BAE7501E3DD5AC78C273792F7D83545D \
        B9AE9905FFD7803F25714661B63B535A4C206CA9 \
        C4F0DFFF4E8C1A8236409D08E73BC641CC11F4C8 \
    ; do \
        gpg --keyserver ha.pool.sks-keyservers.net --recv-keys "$key"; \
    done

ENV NPM_CONFIG_LOGLEVEL info
ENV NODE_VERSION 6.5.0

RUN curl -SLO "https://nodejs.org/dist/v$NODE_VERSION/node-v$NODE_VERSION-linux-x64.tar.xz" \
  && curl -SLO "https://nodejs.org/dist/v$NODE_VERSION/SHASUMS256.txt.asc" \
  && gpg --batch --decrypt --output SHASUMS256.txt SHASUMS256.txt.asc \
  && grep " node-v$NODE_VERSION-linux-x64.tar.xz\$" SHASUMS256.txt | sha256sum -c - \
  && tar -xJf "node-v$NODE_VERSION-linux-x64.tar.xz" -C /usr/local --strip-components=1 \
  && rm "node-v$NODE_VERSION-linux-x64.tar.xz" SHASUMS256.txt.asc SHASUMS256.txt \
  && ln -s /usr/local/bin/node /usr/local/bin/nodejs

# Поставим xdebug отдельно, т.к. потом его потенциально придётся отсюда убирать
RUN yes | pecl install xdebug \
 && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
 && echo "xdebug.default_enable=1" >> /usr/local/etc/php/conf.d/xdebug.ini \
 && echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/xdebug.ini \
 && echo "xdebug.remote_handler=dbgp" >> /usr/local/etc/php/conf.d/xdebug.ini \
 && echo "xdebug.remote_mode=req" >> /usr/local/etc/php/conf.d/xdebug.ini \
 && echo "xdebug.remote_autostart=1" >> /usr/local/etc/php/conf.d/xdebug.ini \
 && echo "xdebug.remote_port=9000" >> /usr/local/etc/php/conf.d/xdebug.ini \
 && echo "xdebug.remote_connect_back=1" >> /usr/local/etc/php/conf.d/xdebug.ini \
 && echo "xdebug.cli_color=1" >> /usr/local/etc/php/conf.d/xdebug.ini \
 && echo "xdebug.var_display_max_depth=10" >> /usr/local/etc/php/conf.d/xdebug.ini

ENV COMPOSER_NO_INTERACTION 1

# Next composer and global composer package, as their versions may change from time to time
RUN curl -sS https://getcomposer.org/installer | php \
 && mv composer.phar /usr/local/bin/composer.phar \
 && echo '{"github-oauth": {"github.com": "***REMOVED***"}}' > ~/.composer/auth.json \
 && composer.phar global require --no-progress "hirak/prestissimo:>=0.3.1"

COPY ./docker/php/composer.sh /usr/local/bin/composer
RUN chmod a+x /usr/local/bin/composer

WORKDIR /var/www/html

# Custorm php configuration
COPY ./docker/php/php.ini /usr/local/etc/php/

# Copy the working dir to the image's web root
COPY . /var/www/html

# The following directories are .dockerignored to not pollute the docker images
# with local logs and published assets from development. So we need to create
# empty dirs and set right permissions inside the container.
RUN mkdir -p api/runtime api/web/assets console/runtime \
 && chown www-data:www-data api/runtime api/web/assets console/runtime

# Expose everything under /var/www (vendor + html)
VOLUME ["/var/www"]
