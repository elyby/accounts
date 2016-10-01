#!/usr/bin/env sh

envsubst '$AUTHSERVER_HOST' < /etc/nginx/conf.d/account.ely.by.conf.template > /etc/nginx/conf.d/default.conf

exec "$@"
