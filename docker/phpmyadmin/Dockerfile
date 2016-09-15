FROM phpmyadmin/phpmyadmin

RUN printf "\n\nrequire('./config.local.php');\n" >> /www/config.inc.php

COPY config.local.php /www/

ENTRYPOINT [ "/run.sh" ]
CMD ["phpmyadmin"]
