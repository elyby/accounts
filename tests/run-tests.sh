#!/usr/bin/env bash

cd "$(dirname "$0")"

docker-compose run --rm testphp ./tests/php.sh
docker-compose stop # docker не останавливает зависимые контейнеры после завершения работы главного процесса
