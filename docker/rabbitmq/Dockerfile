FROM rabbitmq:3.6

RUN rabbitmq-plugins enable rabbitmq_management \
 && rabbitmq-plugins enable rabbitmq_web_stomp \
 && rabbitmq-plugins enable rabbitmq_mqtt

# Add script to create default users / vhosts
ADD init.sh /init.sh

RUN chmod +x /init.sh
