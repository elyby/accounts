#!/usr/bin/env bash
set -e

XDEBUG_EXTENSION_FILE="/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini"
XDEBUG_CONFIG_FILE="/usr/local/etc/php/conf.d/xdebug.ini"
PHP_PROD_INI="/usr/local/etc/php/conf.d/php.prod.ini"
PHP_DEV_INI="/usr/local/etc/php/conf.d/php.dev.ini"
YII_EXEC="/var/www/html/yii"

if [ "$YII_DEBUG" = "true" ] || [ "$YII_DEBUG" = "1" ] ; then
    echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > $XDEBUG_EXTENSION_FILE
    HOST_IP="$(ip route | awk '/default/ { print $3 }')"
    sed -i "/xdebug\.client_host/s/=.*/=${HOST_IP}/" $XDEBUG_CONFIG_FILE
    mv ${PHP_PROD_INI}{,.disabled} 2> /dev/null || true
    mv ${PHP_DEV_INI}{.disabled,} 2> /dev/null || true
else
    rm -f $XDEBUG_EXTENSION_FILE
    mv ${PHP_DEV_INI}{,.disabled} 2> /dev/null || true
    mv ${PHP_PROD_INI}{.disabled,} 2> /dev/null || true
fi

# Create all necessary folders
APP_DIRS=(
    "api/runtime"
    "console/runtime"
    "data/certs"
)
for path in ${APP_DIRS[*]}; do
    if [ ! -d "$path" ]; then
        echo "[bootstrap] Creating $path folder"
        mkdir -p "$path"
    fi

    if [ $(ls -ld $path | awk '{print $3}' | tail -1) != "www-data" ]; then
        echo "[bootstrap] Changing $path folder owner"
        chown -R www-data:www-data "$path"
    fi
done

# Fix permissions for cron tasks
chmod 644 /etc/cron.d/*

JWT_PRIVATE_PEM_LOCATION="${JWT_PRIVATE_PEM_LOCATION:-/var/www/html/data/certs/private.pem}"
JWT_PUBLIC_PEM_LOCATION="${JWT_PUBLIC_PEM_LOCATION:-/var/www/html/data/certs/public.pem}"

if [ ! -f "$JWT_PRIVATE_PEM_LOCATION" ] ; then
    echo "There is no private key. Generating the new one."
    openssl ecparam -name prime256v1 -genkey -noout -out "$JWT_PRIVATE_PEM_LOCATION"
    openssl ec -in "$JWT_PRIVATE_PEM_LOCATION" -pubout -out "$JWT_PUBLIC_PEM_LOCATION"
    chown www-data:www-data "$JWT_PRIVATE_PEM_LOCATION" "$JWT_PUBLIC_PEM_LOCATION"
fi

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

echo "Running database migrations"
wait-for-it "${DB_HOST:-db}:${DB_PORT:-3306}" -s -t 0 -- php $YII_EXEC migrate/up --interactive=0

echo "Launching"
exec "$@"
