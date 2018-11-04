FROM phpmyadmin/phpmyadmin:4.7.9-1

RUN printf "\n\nrequire('./config.local.php');\n" >> /www/config.inc.php

COPY config.local.php /www/

ENTRYPOINT [ "/run.sh" ]
CMD ["phpmyadmin"]
