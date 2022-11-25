FROM composer:2.1.14

COPY . /srv/app
WORKDIR /srv/app

RUN apk add php postgresql-dev
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql
RUN composer update
RUN composer update --dev
ADD php.ini /etc/php7/php.ini

ENV URL "http://polybase-be.3d-model-shop.svc.cluster.local:8080"

CMD [ "php", "bin/console", "server:run" , "0.0.0.0:8080"]