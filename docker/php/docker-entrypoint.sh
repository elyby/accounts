#!/usr/bin/env bash
set -e

XDEBUG_EXTENSION_FILE="/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini"
PHP_PROD_INI="/usr/local/etc/php/conf.d/php.prod.ini"
PHP_DEV_INI="/usr/local/etc/php/conf.d/php.dev.ini"

if [ "$YII_DEBUG" = "true" ] || [ "$YII_DEBUG" = "1" ] ; then
    echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > $XDEBUG_EXTENSION_FILE
    mv ${PHP_PROD_INI}{,.disabled} 2> /dev/null || true
    mv ${PHP_DEV_INI}{.disabled,} 2> /dev/null || true
else
    rm -f $XDEBUG_EXTENSION_FILE
    mv ${PHP_DEV_INI}{,.disabled} 2> /dev/null || true
    mv ${PHP_PROD_INI}{.disabled,} 2> /dev/null || true
fi

cd /var/www/html

# Create all necessary folders
mkdir -p api/runtime console/runtime
chown -R www-data:www-data api/runtime console/runtime

if [ "$YII_ENV" = "test" ]
then
    YII_EXEC="/var/www/html/tests/codeception/bin/yii"
else
    YII_EXEC="/var/www/html/yii"
fi

# Fix permissions for cron tasks
chmod 644 /etc/cron.d/*

if [ "$1" = "crond" ] ; then
    # see: https://github.com/dubiousjim/dcron/issues/13
    # ignore using `exec` for `dcron` to get another pid instead of `1`
    # exec "$@"
    "$@"
fi

if [ "$1" = "yii" ] ; then
    shift
    php $YII_EXEC "$@"
    exit 0
fi

if [ "$1" = "sh" ] || [ "$1" = "bash" ] || [ "$1" = "composer" ] || [ "$1" = "php" ] ; then
    exec "$@"
    exit 0
fi

echo "Generating RBAC rules"
php $YII_EXEC rbac/generate

if [ "$YII_ENV" != "test" ]
then
    wait-for-it "${DB_HOST:-db}:3306" -s -t 0 -- "php $YII_EXEC migrate/up --interactive=0"
else
    wait-for-it "${DB_HOST:-testdb}:3306" -s -t 0 -- "php $YII_EXEC migrate/up --interactive=0"
fi

exec "$@"
