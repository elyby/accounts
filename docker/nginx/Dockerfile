FROM nginx:1.11

COPY nginx.conf /etc/nginx/nginx.conf
COPY account.ely.by.conf.template /etc/nginx/conf.d/account.ely.by.conf.template
COPY run.sh /run.sh

RUN rm /etc/nginx/conf.d/default.conf \
 && chmod a+x /run.sh

CMD ["/run.sh"]
