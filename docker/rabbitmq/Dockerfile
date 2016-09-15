FROM rabbitmq:3.6

RUN rabbitmq-plugins enable rabbitmq_management \
 && rabbitmq-plugins enable rabbitmq_web_stomp \
 && rabbitmq-plugins enable rabbitmq_mqtt

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["rabbitmq-server"]
