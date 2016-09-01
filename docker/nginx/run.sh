#!/usr/bin/env bash

envsubst '$AUTHSERVER_HOST:$PHP_LINK' < /etc/nginx/conf.d/account.ely.by.conf.template > /etc/nginx/conf.d/default.conf
nginx -g 'daemon off;'
