FROM ubuntu:14.04

COPY ./misc/docker/nginx /etc/nginx/sites-available/nZEDb
COPY ./misc/docker/init.sh /init.sh

RUN apt-get install -y software-properties-common && \
  add-apt-repository ppa:ondrej/php5-5.6 && \
  add-apt-repository ppa:nginx/stable && \
  apt-get update && \
  apt-get install -y --force-yes \
    software-properties-common \
    python-software-properties \
    git \
    php5 \
    php5-cli \
    php5-dev \
    php5-json \
    php-pear \
    php5-gd \
    php5-mysqlnd \
    php5-curl \
    php5-fpm \
    mariadb-client \
    libmysqlclient-dev \
    nginx \
    p7zip-full \
    unrar-free \
    lame \
    mediainfo \
  && unlink /etc/nginx/sites-enabled/default \
  && ln -s /etc/nginx/sites-available/nZEDb /etc/nginx/sites-enabled/nZEDb \
  && git clone https://github.com/nZEDb/nZEDb.git /var/www/nZEDb \
  && chmod -R 777 /var/lib/php5/sessions \
  && chmod -R 777 /var/www/nZEDb/libs/smarty/templates_c \
  && chmod -R 777 /var/www/nZEDb/resources \
  && mkdir -p /var/www/nZEDb/nzedb/config \
  && chmod -R 777 /var/www/nZEDb/nzedb/config \
  && chmod -R 777 /var/www/nZEDb/www \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

EXPOSE 80

ENTRYPOINT ["/init.sh"]
