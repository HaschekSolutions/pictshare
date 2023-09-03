FROM alpine:3.14.2

RUN apk add --no-cache bash socat wget curl nginx file ffmpeg unzip \
        php7-fileinfo \
        php7-session \
        zlib \
        php \
        php-curl \
        php-openssl \
        php-mbstring \
        php-json \
        php-gd \
        php-dom \
        php-fpm \
        php7 \
        php7-pdo \
        php7-exif \
        php7-mcrypt \
        php7-curl \
        php7-gd \
        php7-json \
        php7-phar \
        php7-fpm \
        php7-openssl \
        php7-ctype \
        php7-opcache \
        php7-mbstring \
        php7-sodium \
        php7-xml \
        php7-ftp \
        php7-simplexml \
        php7-session \
        php7-fileinfo \
        php7-pcntl

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
RUN sed -i 's/nobody/nginx/g' /etc/php7/php-fpm.d/www.conf

# Since requests can trigger conversion, let's give the server enough time to respond
RUN sed -i "/max_execution_time/c\max_execution_time=3600" /etc/php7/php.ini
RUN sed -i "/max_input_time/c\max_input_time=3600" /etc/php7/php.ini

WORKDIR /var/www/

# Volumes to mount
#VOLUME /var/lib/influxdb
VOLUME /var/www/data

EXPOSE 80

ENTRYPOINT ["/etc/start.sh"]