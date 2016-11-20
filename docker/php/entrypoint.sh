#!/bin/bash

cd /var/www/html

if [ "$1" = "bash" ] || [ "$1" = "composer" ]
then
    exec "$@"
    exit 0
fi

# Переносим vendor, если его нету или он изменился (или затёрся силами volume)
if ! cmp -s ./../vendor/autoload.php ./vendor/autoload.php
then
    echo "vendor have diffs..."
    echo "removing exists vendor"
    rm -rf ./vendor
    echo "copying new one"
    cp -r ./../vendor ./vendor
fi

# Переносим dist, если его нету или он изменился (или затёрся силами volume)
if ! cmp -s ./../dist/index.html ./frontend/dist/index.html
then
    echo "frontend dist have diffs..."
    echo "removing exists dist"
    rm -rf ./frontend/dist
    echo "copying new one"
    cp -r ./../dist ./frontend/dist
fi

if [ "$YII_ENV" != "test" ]
then
    wait-for-it db:3306 -s -- "php /var/www/html/yii migrate/up --interactive=0"
else
    wait-for-it testdb:3306 -s -- "php /var/www/html/tests/codeception/bin/yii migrate/up --interactive=0"
fi

exec "$@"
