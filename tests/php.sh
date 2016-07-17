#!/usr/bin/env bash

cd "$(dirname "$0")"

./../vendor/bin/codecept build

./../docker/wait-for-it.sh testdb:3306 testrabbit:5672 -- \
php codeception/bin/yii migrate/up  --interactive=0 && ./../vendor/bin/codecept run $*
