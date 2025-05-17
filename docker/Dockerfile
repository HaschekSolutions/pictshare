FROM dunglas/frankenphp:php8.4-alpine

RUN apk add --no-cache bash socat file ffmpeg 

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer 
WORKDIR /app/public

# Add php.ini
ADD configs/php.ini /usr/local/etc/php/php.ini

ADD . /app/public/.

ADD docker/rootfs/start.sh /etc/start.sh
RUN chmod +x /etc/start.sh

# Composer intall
WORKDIR /app/public/src/lib
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader


WORKDIR /app/public/

# Volumes to mount
#VOLUME /var/lib/influxdb
VOLUME /app/public/data
VOLUME /app/public/logs

EXPOSE 80

ENTRYPOINT ["/etc/start.sh"]