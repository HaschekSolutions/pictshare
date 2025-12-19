FROM alpine:3.23

RUN apk add --no-cache bash socat wget curl nginx file ffmpeg unzip zlib redis \
        php84-fileinfo \
        php84-session \
        php \
        php-curl \
        php-openssl \
        php-mbstring \
        php-json \
        php-gd \
        php-dom \
        php-fpm \
        php84 \
        php84-pdo \
        php84-exif \
        php84-curl \
        php84-gd \
        php84-json \
        php84-phar \
        php84-fpm \
        php84-openssl \
        php84-ctype \
        php84-opcache \
        php84-mbstring \
        php84-sodium \
        php84-xml \
        php84-ftp \
        php84-simplexml \
        php84-session \
        php84-fileinfo \
        php84-pcntl \
        php84-pecl-redis

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
RUN mkdir -p /run/nginx /var/log/nginx && \
    sed -i 's/nobody/nginx/g' /etc/php84/php-fpm.d/www.conf && \
    sed -i "/max_execution_time/c\max_execution_time=3600" /etc/php84/php.ini && \
    sed -i "/max_input_time/c\max_input_time=3600" /etc/php84/php.ini 

# Volumes to mount
VOLUME /var/www/data

EXPOSE 80

ENTRYPOINT ["/etc/start.sh"]
