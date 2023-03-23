# Install guide

PictShare is written to be run on a linux server with PHP 7 and nginx. We tried to support Windows for some time but ever since we started integrating ffmpeg for MP4 hosting we ditched Windows.

 It's highly recommended t hat you use [the official Docker container](https://github.com/HaschekSolutions/pictshare/pkgs/container/pictshare) so you don't have to do any manual setup. But if you know what you're doing, you can set it up yourself.

- Make sure you have all PHP7 libraries installed (note on some systems the packages are not called php7-* but just php-* also on some systems php7-mbstring is called php7-mb): ```apt-get install php7-exif php7-gd php7-json php7-openssl php7-fileinfo php7-mbstring php7-mcrypt```
- If you are not using windows, make sure your os have the ```file``` command working: ```apt-get install file```
- Unpack the [PictShare zip](https://github.com/hascheksolutions/pictshare/archive/master.zip)
- Rename /inc/example.config.inc.php to /inc/config.inc.php
- If you want to be able to use mp4 uploads you need to supply your own FFMPEG binary or use the installed one on your distro useing the config var ```FFMPEG_BINARY```. For example it should point to `/usr/bin/ffmpeg` if you installed ffmpeg through your package manager
- Since default upload sizes will be 2M in PHP you should edit your php.ini and change ```upload_max_filesize``` and ```post_max_size``` to a larger value
- (optional) You can and should use [nginx](https://www.nginx.com/) as your web server. Check [/docker/rootfs/nginx.conf] for an example on how the nginx config should look like
- (optional) To secure your traffic I'd highly recommend getting an [SSL Cert](https://letsencrypt.org/) for your server if you don't already have one.


## Upgrading
- On docker just `docker pull hascheksolutions/pictshare` and run the newer image
- Manual upgrade:
    - Just re-download the [PictShare zip](https://github.com/hascheksolutions/pictshare/archive/master.zip) file and extract and overwrite existing pictshare files. Uploads and config won't be affected.
    - Check if your ```/inc/config.inc.php``` file has all settings required by the ```/inc/example.config.inc.php``` since new options might get added in new versions


```bash
# to be run from the directory where your pictshare directory sits in
git clone https://github.com/hascheksolutions/pictshare.git temp
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
