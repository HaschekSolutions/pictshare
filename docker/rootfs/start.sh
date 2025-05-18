#!/bin/bash

######### functions

_maxUploadSize() {
    echo "[i] Setting uploadsize to ${MAX_UPLOAD_SIZE}M"
	
	sed -i "/post_max_size/c\post_max_size=${MAX_UPLOAD_SIZE}M" /usr/local/etc/php/php.ini
	sed -i "/upload_max_filesize/c\upload_max_filesize=${MAX_UPLOAD_SIZE}M" /usr/local/etc/php/php.ini

    # set error reporting no notices, no warnings
    sed -i "/^error_reporting/c\error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING & ~E_NOTICE" /usr/local/etc/php/php.ini

    MAX_RAM=$((MAX_UPLOAD_SIZE + 30)) # 30megs more than the upload size
    echo "[i] Also changing memory limit of PHP to ${MAX_RAM}M"
    sed -i -e "s/128M/${MAX_RAM}M/g" /usr/local/etc/php/php.ini
	sed -i "/memory_limit/c\memory_limit=${MAX_RAM}M" /usr/local/etc/php/php.ini
}

_filePermissions() {
    echo "[i] Setting file permissions"
}

_buildConfig() {
    echo "<?php"
    echo "define('URL', '${URL:-}');"
    echo "define('TITLE', '${TITLE:-PictShare}');"
    echo "define('REDIS_CACHING', '${REDIS_CACHING:-true}');"
    echo "define('ALLOWED_SUBNET', '${ALLOWED_SUBNET:-}');"
    echo "define('CONTENTCONTROLLERS', '${CONTENTCONTROLLERS:-}');"
    echo "define('MASTER_DELETE_CODE', '${MASTER_DELETE_CODE:-}');"
    echo "define('MASTER_DELETE_IP', '${MASTER_DELETE_IP:-}');"
    echo "define('UPLOAD_FORM_LOCATION', '${UPLOAD_FORM_LOCATION:-}');"
    echo "define('UPLOAD_CODE', '${UPLOAD_CODE:-}');"
    echo "define('LOG_UPLOADER', ${LOG_UPLOADER:-false});"
    echo "define('MAX_RESIZED_IMAGES',${MAX_RESIZED_IMAGES:--1});"
    echo "define('ALLOW_BLOATING', ${ALLOW_BLOATING:-false});"
    echo "define('SHOW_ERRORS', ${SHOW_ERRORS:-false});"
    echo "define('JPEG_COMPRESSION', ${JPEG_COMPRESSION:-90});"
    echo "define('PNG_COMPRESSION', ${PNG_COMPRESSION:-6});"
    echo "define('ALT_FOLDER', '${ALT_FOLDER:-}');"
    echo "define('S3_BUCKET', '${S3_BUCKET:-}');"
    echo "define('S3_ACCESS_KEY', '${S3_ACCESS_KEY:-}');"
    echo "define('S3_SECRET_KEY', '${S3_SECRET_KEY:-}');"
    echo "define('S3_ENDPOINT', '${S3_ENDPOINT:-}');"
    echo "define('S3_REGION', '${S3_REGION:-}');"
    echo "define('FTP_SERVER', '${FTP_SERVER:-}');"
    echo "define('FTP_PORT', ${FTP_PORT:-21});"
    echo "define('FTP_USER', '${FTP_USER:-}');"
    echo "define('FTP_PASS', '${FTP_PASS:-}');"
    echo "define('FTP_PASSIVEMODE', ${FTP_PASSIVEMODE:-true});"
    echo "define('FTP_SSL', ${FTP_SSL:-false});"
    echo "define('FTP_BASEDIR', '${FTP_BASEDIR:-}');"
    echo "define('ENCRYPTION_KEY', '${ENCRYPTION_KEY:-}');"
    echo "define('FFMPEG_BINARY', '${FFMPEG_BINARY:-/usr/bin/ffmpeg}');"
    echo "define('ALWAYS_WEBP', ${ALWAYS_WEBP:-false});"
    echo "define('ALLOWED_DOMAINS', '${ALLOWED_DOMAINS:-}');"
    echo "define('SPLIT_DATA_DIR', ${SPLIT_DATA_DIR:-false});"
    echo "define('LOG_VIEWS', ${LOG_VIEWS:-false});"
    echo "define('REDIS_CACHING', ${REDIS_CACHING:-true});"
    echo "define('REDIS_SERVER', '${REDIS_SERVER:-localhost}');"
    echo "define('REDIS_PORT', ${REDIS_PORT:-6379});"
}



######### main

echo 'Starting Pictshare'

cd /app/public/

if [[ ${MAX_UPLOAD_SIZE:=100} =~ ^[0-9]+$ ]]; then
        _maxUploadSize
fi

# run _filePermissions function unless SKIP_FILEPERMISSIONS is set to true
if [[ ${SKIP_FILEPERMISSIONS:=false} != true ]]; then
        _filePermissions
fi

echo ' [+] Creating config'

_buildConfig > src/inc/config.inc.php

frankenphp php-server --listen ":80" --root /app/public/web