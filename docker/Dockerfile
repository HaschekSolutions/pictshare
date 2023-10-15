FROM alpine:3.18

RUN apk add --no-cache bash socat wget curl nginx file ffmpeg unzip zlib redis \
        php82-fileinfo \
        php82-session \
        php \
        php-curl \
        php-openssl \
        php-mbstring \
        php-json \
        php-gd \
        php-dom \
        php-fpm \
        php82 \
        php82-pdo \
        php82-exif \
        php82-curl \
        php82-gd \
        php82-json \
        php82-phar \
        php82-fpm \
        php82-openssl \
        php82-ctype \
        php82-opcache \
        php82-mbstring \
        php82-sodium \
        php82-xml \
        php82-ftp \
        php82-simplexml \
        php82-session \
        php82-fileinfo \
        php82-pcntl \
        php82-pecl-redis

RUN ln -s /usr/bin/php82 /usr/bin/php

RUN curl -sS https://getcomposer.org/installer | /usr/bin/php -- --install-dir=/usr/bin --filename=composer 
RUN mkdir -p /var/www
WORKDIR /var/www

ADD . /var/www/.

ADD docker/rootfs/start.sh /etc/start.sh
RUN chmod +x /etc/start.sh

# Composer intall
WORKDIR /var/www/lib
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader

# nginx stuff
WORKDIR /var/www
ADD docker/rootfs/nginx.conf /etc/nginx/http.d/default.conf
RUN mkdir -p /run/nginx
RUN mkdir -p /var/log/nginx
RUN sed -i 's/nobody/nginx/g' /etc/php82/php-fpm.d/www.conf

# Since requests can trigger conversion, let's give the server enough time to respond
RUN sed -i "/max_execution_time/c\max_execution_time=3600" /etc/php82/php.ini
RUN sed -i "/max_input_time/c\max_input_time=3600" /etc/php82/php.ini

WORKDIR /var/www/

# Volumes to mount
#VOLUME /var/lib/influxdb
VOLUME /var/www/data

EXPOSE 80

ENTRYPOINT ["/etc/start.sh"]