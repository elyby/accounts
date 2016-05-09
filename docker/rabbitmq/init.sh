#!/bin/sh

#( sleep 10 ; \
#
## Create users
#rabbitmqctl add_user ely-accounts-app app-password ; \
#rabbitmqctl add_user ely-accounts-tester tester-password ; \
#
## Set user rights
#rabbitmqctl set_user_tags ely-accounts-app administrator ; \
#rabbitmqctl set_user_tags ely-accounts-tester administrator ; \
#
## Create vhosts
#rabbitmqctl add_vhost /account.ely.by ; \
#rabbitmqctl add_vhost /account.ely.by/tests ; \
#
## Set vhost permissions
#rabbitmqctl set_permissions -p /account.ely.by ely-accounts-app ".*" ".*" ".*" ; \
#rabbitmqctl set_permissions -p /account.ely.by/tests ely-accounts-tester ".*" ".*" ".*" ; \
#) &
#rabbitmq-server $@

#service rabbitmq-server start

# Create users
rabbitmqctl add_user ely-accounts-app app-password
rabbitmqctl add_user ely-accounts-tester tester-password

# Set user rights
rabbitmqctl set_user_tags ely-accounts-app administrator
rabbitmqctl set_user_tags ely-accounts-tester administrator

# Create vhosts
rabbitmqctl add_vhost /account.ely.by
rabbitmqctl add_vhost /account.ely.by/tests

# Set vhost permissions
rabbitmqctl set_permissions -p /account.ely.by ely-accounts-app ".*" ".*" ".*"
rabbitmqctl set_permissions -p /account.ely.by/tests ely-accounts-tester ".*" ".*" ".*"

#service rabbitmq-server stop

# Сохраняем состояние рэбита
#tar czvf default_rabbitmq.tar.gz /var/lib/rabbitmq/mnesia
