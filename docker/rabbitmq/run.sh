#!/bin/bash

set -e
set -x

[ "$(ls -A /var/lib/rabbitmq/mnesia)" ] && echo "Running with existing rabbitmq in /var/lib/rabbitmq" || ( echo 'Populate initial rabbitmq'; tar xpzvf default_rabbitmq.tar.gz )

rabbitmq-server
