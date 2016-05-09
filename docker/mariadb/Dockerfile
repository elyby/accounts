FROM mariadb:10.0

COPY mariadb.cnf /etc/mysql/conf.d

# Add script to create default users / vhosts
ADD init.sh /init.sh
ADD run.sh /run.sh

# Run rabbitmq, execute init configuration and then shutdown
RUN chmod +x /init.sh /run.sh \
 && /init.sh

ENTRYPOINT "/run.sh"
