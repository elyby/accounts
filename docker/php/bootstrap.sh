#!/usr/bin/env bash

mkdir -p api/runtime api/web/assets console/runtime
chown www-data:www-data api/runtime api/web/assets console/runtime

if [ "$YII_ENV" = "test" ]
then
    YII_EXEC="/var/www/html/tests/codeception/bin/yii"
else
    YII_EXEC="/var/www/html/yii"
fi

if ! cmp -s ./../vendor/autoload.php ./vendor/autoload.php
then
    echo "Vendor have diffs"
    echo "Removing not bundled vendor..."
    rm -rf ./vendor
    echo "Copying new one..."
    cp -r ./../vendor ./vendor
fi

# Переносим dist, если его нету или он изменился (или затёрся силами volume)
if ! cmp -s ./../dist/index.html ./frontend/dist/index.html
then
    echo "Frontend dist have diffs"
    echo "Removing not bundled dist..."
    rm -rf ./frontend/dist
    echo "Copying new one..."
    cp -r ./../dist ./frontend/dist
fi

# Генерируем правила RBAC
echo "Generating RBAC rules"
php $YII_EXEC rbac/generate

if [ "$YII_ENV" != "test" ]
then
    wait-for-it "${DB_HOST:-db}:3306" -s -t 0 -- "php $YII_EXEC migrate/up --interactive=0"
else
    wait-for-it "${DB_HOST:-testdb}:3306" -s -t 0 -- "php $YII_EXEC migrate/up --interactive=0"
fi
