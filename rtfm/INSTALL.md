# Install guide

PictShare is written to be run on a linux server with PHP 7 and nginx. We tried to support Windows for some time but ever since we started integrating ffmpeg for MP4 hosting we ditched Windows.

- Make sure you have PHP7 GD libraries installed: ```apt-get install php-gd```
- Unpack the [PictShare zip](https://github.com/chrisiaut/pictshare/archive/master.zip)
- Rename /inc/example.config.inc.php to /inc/config.inc.php
- ```chmod +x bin/ffmpeg``` if you want to be able to use mp4 uploads
 - The provided ffmpeg binary (bin/ffmpeg) is from [here](http://johnvansickle.com/ffmpeg/) and it's a 64bit linux executable. If you need a different one, load yours and overwrite the one provided or if you have ffmpeg installed on the server you can use the config var ```FFMPEG_BINARY``` to tell PictShare where to look for the binary
- Since default upload sizes will be 2M in PHP you should edit your php.ini and change ```upload_max_filesize``` and ```post_max_size``` to a larger value
- (optional) You can and should put a [nginx](https://www.nginx.com/) proxy before the Apache server. That thing is just insanely fast with static content like images.
- (optional) To secure your traffic I'd highly recommend getting an [SSL Cert](https://letsencrypt.org/) for your server if you don't already have one.


## Upgrading
- Just re-download the [PictShare zip](https://github.com/chrisiaut/pictshare/archive/master.zip) file and extract and overwrite existing pictshare files. Uploads and config won't be affected.
- Check if your ```/inc/config.inc.php``` file has all settings required by the ```/inc/example.config.inc.php``` since new options might get added in new versions


```bash
# to be run from the directory where your pictshare directory sits in
git clone https://github.com/chrisiaut/pictshare.git temp
cp -r temp/* pictshare/.
rm -rf temp
```

## Configuring PictShare
[Documentation of config settings here](/rtfm/CONFIG.md)

## Nginx configuration
This is a simple config file that should make PictShare work on nginx


```
server {
        listen 80;
        server_name your.awesome.domain.name;

        client_max_body_size 50M; # Set the max file upload size. This needs to be equal or larger than the size you specified in your php.ini

        root /var/www/pictshare; # or where ever you put it
        index index.php;

    location / {
        try_files $uri $uri/ /index.php?url=$request_uri;
    }

    location ~ \.php {
        fastcgi_pass unix:/var/run/php/php7.3-fpm.sock; #may be slightly different depending on your php version
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_script_name;
    }

    location ~ /(data|tmp|bin|content-controllers|inc|interfaces|storage-controllers|templates|tools) {
       deny all;
       return 404;
    }

}
```