FROM dunglas/frankenphp:php8.4-alpine

RUN apk add --no-cache bash socat file ffmpeg redis

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer 
WORKDIR /app/public

RUN install-php-extensions redis exif gd

ADD docker/rootfs/Caddyfile /etc/caddy/Caddyfile

# Add php.ini
ADD configs/php.ini /usr/local/etc/php/php.ini

# Prepare app tree
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

ARG PICTSHARE_VERSION=git
ENV PICTSHARE_VERSION=${PICTSHARE_VERSION}

EXPOSE 80

# Security stopgap for GHSA-6wmf-4rv5-p3rp / GHSA-w8hw-w9fx-j79w (2026-10-21).
# Keep upload progress off even after patching; fatal_error_backtraces only exists in PHP 8.5+.
RUN for d in /usr/local/etc/php/conf.d /etc/php*/conf.d; do \
      [ -d "$d" ] && printf 'session.upload_progress.enabled = 0\nfatal_error_backtraces = 0\n' > "$d/zz-security.ini"; \
    done; true

ENTRYPOINT ["/etc/start.sh"]