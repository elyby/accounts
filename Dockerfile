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

# Next composer and global composer package, as their versions may change from time to time
RUN curl -sS https://getcomposer.org/installer | php \
 && mv composer.phar /usr/local/bin/composer.phar \
 && composer.phar global require --no-progress "fxp/composer-asset-plugin:~1.1.4" "hirak/prestissimo:~0.2.2"

COPY ./docker/php/composer.sh /usr/local/bin/composer
RUN chmod a+x /usr/local/bin/composer

WORKDIR /var/www/html

# Copy the working dir to the image's web root
COPY . /var/www/html

# The following directories are .dockerignored to not pollute the docker images
# with local logs and published assets from development. So we need to create
# empty dirs and set right permissions inside the container.
RUN mkdir -p api/runtime api/web/assets console/runtime \
 && chown www-data:www-data api/runtime api/web/assets console/runtime

# Expose everything under /var/www (vendor + html)
VOLUME ["/var/www"]
