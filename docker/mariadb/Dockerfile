FROM mariadb:10.2.11

COPY custom.cnf /etc/mysql/conf.d/

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["mysqld"]
