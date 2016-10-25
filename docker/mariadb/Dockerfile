FROM mariadb:10.0

COPY custom.cnf /etc/mysql/conf.d/

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["mysqld"]
