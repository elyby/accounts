#!/bin/bash

# Копипаста. Я не знаю, что тут происходит

set -e
set -x

mysql_install_db

# Start the MySQL daemon in the background.
/usr/sbin/mysqld &
mysql_pid=$!

until mysqladmin ping &>/dev/null; do
  echo -n "."; sleep 0.2
done

# Конец рандомной копипасты

# Устаналиваем беспарольный доступ для рута
mysql -e "GRANT ALL ON *.* TO root@'%' IDENTIFIED BY '' WITH GRANT OPTION"

# Создаём базу данных для приложения и для тестов
mysql -e "CREATE DATABASE IF NOT EXISTS ely_accounts CHARACTER SET utf8 COLLATE utf8_general_ci"
mysql -e "CREATE DATABASE IF NOT EXISTS ely_accounts_test CHARACTER SET utf8 COLLATE utf8_general_ci"

# Tell the MySQL daemon to shutdown.
mysqladmin shutdown

# Wait for the MySQL daemon to exit.
wait $mysql_pid

# Сохраняем состояние базы данных
tar czvf default_mysql.tar.gz /var/lib/mysql
