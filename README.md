# PictShare
**[Live Demo](https://www.pictshare.net)**
PictShare is a multi lingual, open source image hosting service with a simple resizing and upload API that you can host yourself.

---
[![Rocket.Chat](https://rocket.haschek.at/images/join-chat.svg)](https://rocket.haschek.at/channel/pictshare)
[![Apache License](https://img.shields.io/badge/license-Apache-blue.svg?style=flat)](https://github.com/chrisiaut/pictshare/blob/master/LICENSE)


![PictShare](https://www.pictshare.net/39928d8239.gif)

Table of contents
=================
* [Installation](#installation)
  * [Docker](#docker)
  * [Without Docker](#without-docker)
* [Why would I want to host my own images?](#why-would-i-want-to-host-my-own-images)
* [Features](#features)
* [Smart query system](#smart-query-system)
  * [Available options](#available-options)
* [How does the external-upload-API work?](#how-does-the-external-upload-api-work)
  * [Upload from external URL](#upload-from-external-url)
    * [Example:](#example)
  * [Upload via POST](#upload-via-post)
  * [Upload from base64 string](#upload-from-base64-string)
  * [Defining filenames](#defining-filenames)
* [Restriction settings](#restriction-settings)
  * [UPLOAD_CODE](#upload_code)
  * [IMAGE_CHANGE_CODE](#image_change_code)
  * [ADDITIONAL_FILE_TYPES](#additional_file_types)
* [Clustered systems](#clustered_systems)
  * [FETCH_SCRIPT](#fetch_script)
* [Security and privacy](#security-and-privacy)
* [Requirements](#requirements)
* [Upgrading](#upgrading)
* [Addons](#addons)
* [Traffic analysis](#traffic-analysis)
* [Coming soon](#coming-soon)


## Installation

### Docker
The fastest way to deploy PictShare is via the [official Docker repo](https://hub.docker.com/r/hascheksolutions/pictshare/)
- [Source code & more examples](https://github.com/HaschekSolutions/PictShare-Docker)

```bash
docker run -d -p 80:80 -e "TITLE=My own PictShare" hascheksolutions/pictshare
```

[![Docker setup](http://www.pictshare.net/b65dea2117.gif)](https://www.pictshare.net/8a1dec0973.mp4)

### Without Docker

- Make sure you have PHP5 GD libraries installed: ```apt-get install php5-gd```
- Unpack the [PictShare zip](https://github.com/chrisiaut/pictshare/archive/master.zip)
- Clone .env.example as .env and edit configuraiton options to your specific needs (NEW)
    - Rename /inc/example.config.inc.php to /inc/config.inc.php (OLD)
- From the root of the project run command ```composer install```
  - If you do not have Composer install check offical [documentation](https://getcomposer.org/)
- ```chmod +x bin/ffmpeg``` if you want to be able to use mp4 uploads
 - The provided ffmpeg binary (bin/ffmpeg) is from [here](http://johnvansickle.com/ffmpeg/) and it's a 64bit linux executable. If you need a different one, load yours and overwrite the one provided
- (optional) You can and should put a [nginx](https://www.nginx.com/) proxy before the Apache server. That thing is just insanely fast with static content like images.
- (optional) To secure your traffic I'd highly recommend getting an [SSL Cert](https://letsencrypt.org/) for your server if you don't already have one.


UPDATES
========
- Nov. 23: Added support for MP4 uploads and conversion from gif to MP4
- Nov. 12: Created new git project: [Pictshare stats](https://github.com/chrisiaut/pictshare_stats)
- Nov. 07: Added 9 new (instagram-like) filters
- Nov. 06: Master delete code. One code to delete them all
- Nov. 01: [Restricted uploads and option-use](#restriction-settings)
- Oct. 30: [Rotations and filters](#smart-query-system)
- Oct. 10: [Album functionality](#smart-query-system) finally ready

## Why would I want to host my own images?
If you own a server (even a home server) you can host your own PictShare instance so you have full control over your content and can delete images hasslefree.

If you're an **app developer** or **sysadmin** you can use it for a centralized image hosting. With the simple upload API you can upload images to your PictShare instance and get a nice short URL

If you're a blogger like myself, you can use it as storage for your images so the images will still work even if you change blog providers or servers

## Features
- Uploads without logins or validation (that's a good thing, right?)
- Simple API to upload any image from remote servers to your instance [via URL](#upload-from-url) and [via Base64](#upload-from-base64-string)
- 100% file based - no database needed
- Simple album functions with embedding support
- Converts gif to (much smaller) MP4
- MP4 resizing
- PictShare removes all exif data so you can upload photos from your phone and all GPS tags and camera model info get wiped
- Smart [resize, filter and rotation](#smart-query-system) features
- Duplicates don't take up space. If the exact same images is uploaded twice, the second upload will link to the first
- You can control who can upload images or use filters/resizes by defining an [upload-code](#restriction-settings)
- You can set a code in your ```.env``` or ```/inc/config.inc.php``` (MASTER_DELETE_CODE) that, if appended to any URL of an Image, will delete the image and all cached versions of it from the server
- Detailed traffic and view statistics of your images via [Pictshare stats](https://github.com/chrisiaut/pictshare_stats)

## Smart query system
PictShare images can be changed after upload just by modifying the URL. It works like this:

<span style="color:blue">https://base.domain</span>/<span style="color:red">&lt;options&gt;</span>/<span style="color:green">&lt;image&gt;</span>

For example: https://pictshare.net/100x100/negative/b260e36b60.jpg will show you the uploaded Image ```b260e36b60.jpg``` but resize it to 100x100 pixels and apply the "negative" filter. The original image will stay untouched.

### Available options
Original URL: ```https://www.pictshare.net/b260e36b60.jpg```

Note: If an option needs a value it works like this: ```optionname_value```. Eg: ```pixelate_10```
If there is some option that's not recognized by PictShare it's simply ignored, so this will work: https://www.pictshare.net/pictshare-is-awesome/b260e36b60.jpg and also even this will work: https://www.pictshare.net/b260e36b60.jpg/how-can-this-still/work/


|     Option    |      Parameter      |      Example URL       |      Result |
| ------------- | ------------------- | ---------------------- | ----------- |
**Resizing**  | | | |
&lt;width&gt;**x**&lt;height&gt; | -none-            | https://pictshare.net/20x20/b260e36b60.jpg | ![Resized](https://pictshare.net/20x20/b260e36b60.jpg) |
forcesize      | -none-                | https://pictshare.net/100x400/forcesize/b260e36b60.jpg | ![Forced size](https://pictshare.net/100x400/forcesize/b260e36b60.jpg) |
**Albums**   |  |  | |
just add multiple image hashes            | -none-             | https://www.pictshare.net/b260e36b60.jpg/32c9cf77c5.jpg/163484b6b1.jpg | Takes the **images** you put in the URL and makes an album out of them. All filters are supported!
embed        | -none-                     | https://www.pictshare.net/b260e36b60.jpg/32c9cf77c5.jpg/163484b6b1.jpg/embed | Renders the album without CSS and with transparent background so you can embed them easily
responsive        | -none-                | https://www.pictshare.net/b260e36b60.jpg/32c9cf77c5.jpg/163484b6b1.jpg/responsive | Renders all images responsive (max-width 100%) according to screen size
&lt;width&gt;**x**&lt;height&gt;          | -none-             | https://www.pictshare.net/b260e36b60.jpg/32c9cf77c5.jpg/163484b6b1.jpg/150x150 | Sets the size for the thumbnails in the album
forcesize        | -none-                 | https://www.pictshare.net/b260e36b60.jpg/32c9cf77c5.jpg/163484b6b1.jpg/100x300/forcesize | Forces thumbnail sizes to the values you provided
**GIF to mp4**   |  |  | 
mp4            | -none-             | https://www.pictshare.net/mp4/102687fe65.gif | Converts gif to mp4 and displays as that. Note that you can't include that mp4 in an img tag
raw            | -none-             | https://www.pictshare.net/mp4/raw/102687fe65.gif | Renders the converted mp4 directly. Use with /mp4/
preview        | -none-             | https://www.pictshare.net/mp4/preview/102687fe65.gif | Renders the first frame of generated MP4 as JPEG. Use with /mp4/
**MP4 options**   |  |  | 
-none-            | -none-             | https://www.pictshare.net/65714d22f0.mp4 | Renders the mp4 embedded in a simple HTML template. This link can't be embedded into video tags, use /raw/ instead if you want to embed
raw            | -none-             | https://www.pictshare.net/raw/65714d22f0.mp4 | Renders the mp4 video directly so you can link it
preview        | -none-             | https://www.pictshare.net/preview/65714d22f0.mp4 | Renders the first frame of the MP4 as an JPEG image
**Rotating**   |  |  | 
left           | -none-                | https://pictshare.net/left/b260e36b60.jpg | ![Rotated left](https://pictshare.net/200/left/b260e36b60.jpg)
right           | -none-                | https://pictshare.net/right/b260e36b60.jpg | ![Rotated right](https://pictshare.net/200/right/b260e36b60.jpg)
upside           | -none-                | https://pictshare.net/upside/b260e36b60.jpg | ![Upside down](https://pictshare.net/200/upside/b260e36b60.jpg)
**Filters**    |  |  | 
negative       | -none-              | https://pictshare.net/negative/b260e36b60.jpg         | ![Negative](https://pictshare.net/negative/200/b260e36b60.jpg)
grayscale      | -none-              | https://pictshare.net/grayscale/b260e36b60.jpg             | ![grayscale](https://pictshare.net/grayscale/200/b260e36b60.jpg)
brightness     | -255 to 255         | https://pictshare.net/brightness_100/b260e36b60.jpg     | ![brightness](https://pictshare.net/brightness_100/200/b260e36b60.jpg)
edgedetect     | -none-              | https://pictshare.net/edgedetect/b260e36b60.jpg           | ![edgedetect](https://pictshare.net/edgedetect/200/b260e36b60.jpg)
smooth         | -10 to 2048         | https://pictshare.net/smooth_3/b260e36b60.jpg             | ![smooth](https://pictshare.net/smooth_3/200/b260e36b60.jpg)
contrast       | -100 to 100         | https://pictshare.net/contrast_40/b260e36b60.jpg     | ![contrast](https://pictshare.net/contrast_40/200/b260e36b60.jpg)
pixelate       | 0 to 100            | https://pictshare.net/pixelate_10/b260e36b60.jpg      | ![pixelate](https://pictshare.net/pixelate_10/200/b260e36b60.jpg)
blur           | -none- or 0 to 5    | https://pictshare.net/blur/b260e36b60.jpg      | ![pixelate](https://pictshare.net/blur/200/b260e36b60.jpg)
sepia            | -none-                | https://pictshare.net/sepia/b260e36b60.jpg    | ![instagram filter sepia](https://pictshare.net/200/sepia/b260e36b60.jpg)
sharpen            | -none-                | https://pictshare.net/sharpen/b260e36b60.jpg    | ![instagram filter sharpen](https://pictshare.net/200/sharpen/b260e36b60.jpg)
emboss            | -none-                | https://pictshare.net/emboss/b260e36b60.jpg    | ![instagram filter emboss](https://pictshare.net/200/emboss/b260e36b60.jpg)
cool            | -none-                | https://pictshare.net/cool/b260e36b60.jpg        | ![instagram filter cool](https://pictshare.net/200/cool/b260e36b60.jpg)    
light            | -none-                | https://pictshare.net/light/b260e36b60.jpg    | ![instagram filter light](https://pictshare.net/200/light/b260e36b60.jpg)
aqua            | -none-                | https://pictshare.net/aqua/b260e36b60.jpg        | ![instagram filter aqua](https://pictshare.net/200/aqua/b260e36b60.jpg)    
fuzzy            | -none-                | https://pictshare.net/fuzzy/b260e36b60.jpg    | ![instagram filter fuzzy](https://pictshare.net/200/fuzzy/b260e36b60.jpg)
boost            | -none-                | https://pictshare.net/boost/b260e36b60.jpg    | ![instagram filter boost](https://pictshare.net/200/boost/b260e36b60.jpg)
gray            | -none-                | https://pictshare.net/gray/b260e36b60.jpg        | ![instagram filter gray](https://pictshare.net/200/gray/b260e36b60.jpg)    

You can also combine as many options as you want. Even multiple times! Want your image to be negative, resized, grayscale , with increased brightness and negate it again? No problem: https://pictshare.net/500x500/grayscale/negative/brightness_100/negative/b260e36b60.jpg

## How does the external-upload-API work?

### Upload from external URL
PictShare has a simple REST API to upload remote pictures. The API can be accessed via the backend.php file like this:

```https://pictshare.net/backend.php?getimage=<URL of the image you want to upload>```.

#### Example:

Request: ```https://pictshare.net/backend.php?getimage=https://www.0xf.at/css/imgs/logo.png```

The server will answer with the file name and the server path in JSON:

```json
{"status":"OK","type":"png","hash":"10ba188162.png","url":"https:\/\/pictshare.net\/10ba188162.png"}
```

### Upload via POST

Send a POST request to ```https://pictshare.net/backend.php``` and send the image in the variable ```postimage```.

Server will return JSON of uploaded data like this:

```json
{"status":"OK","type":"png","hash":"2f18a052c4.png","url":"https:\/\/pictshare.net\/2f18a052c4.png","domain":"https:\/\/pictshare.net\/"}
```
You may also want to upload other file types - which is possible if defined in configuration (check [Restriction settings](#restriction-setting)), by sending a POST request to ```https://pictshare.net/backend.php```and the file in variable ```postfile```.

By default, PictShare always stores the file under ```upload/``` directory. If you want to change this for file you are uploading you can specific a request parameter ```subdir``` (eg. subdir=foo/bar) and you file will be stored under that subdirectory (```upload/foo/bar```). In order to use this functionality make sure you have it configured (check [Restriction settings](#restriction-setting)). 

### Upload from base64 string

Just send a POST request to ```https://pictshare.net/backend.php``` and send your image in base64 as the variable name ```base64```

Server will automatically try to guess the file type (which should work in 90% of the cases) and if it can't figure it out it'll just upload it as png.

### Defining filenames

By default PictShare will store images under randomly generated hashkeys. If you wish to store an image under specific name you can do so by sending the value as request parameter ```filename```. In order to use this functionality make sure you have it configured (check [Restriction settings](#restriction-setting)).

Please not that even if you specify a name for the file it will still get an 8-character hash prepended to it. The reason is due to potential collision when trying to store files under same name into different subdirectories (as subdirectory info is not part of the hashkey).

## Restriction settings
In your ```.env``` or ```config.inc.php``` there are couple of values to be set: ```UPLOAD_CODE```, ```IMAGE_CHANGE_CODE```, ```ADDITIONAL_FILE_TYPES```, ```SUBDIR_ENABLE``` and ```FILENAME_ENABLE```

Some of the settings can be set to strings or multiple strings semi;colon;separated. If there is a semicolon in the string, any of the elements will work

### UPLOAD_CODE
If set, will show users a code field in the upload form. If it doesn't match your setting, files won't be uploaded. Supports multiple strings semi;colon;separated

If enabled, the Upload API will need the variable ```upload_code``` via GET (eg: ```https://pictshare.net/backend.php?getimage=https://www.0xf.at/css/imgs/logo.png&upload_code=YourUploadCodeHere```)

### IMAGE_CHANGE_CODE
If set, the [options](#available-options) will only work if the URL got the code in it. You can provide the code as option ```changecode_YourChangeCode```. Supports multiple strings semi;colon;separated

For example: If enabled the image ```https://www.pictshare.net/negative/b260e36b60.jpg``` won't show the negative version but the original.
If you access the image with the code like this: ```https://www.pictshare.net/changecode_YourChangeCode/b260e36b60.jpg``` it gets cached on the server so the next time someone requests the link without providing the change-code, they'll see the inverted image (because you just created it before by accessing the image with the code)

### ADDITIONAL_FILE_TYPES
If set, allows the files of set type(s) to be uploaded via REST API. Support multiple strings semi;colon;separated

When requested by the URL provided in response, these files (for additionally defined types) will be offered for download.

### SUBDIR_ENABLE
If set to true, it is possible to define subdirectories via REST API (request parameter ```subdir```) under which the file will be stored.

This setting is turned on by default.

### SUBDIR_FORCE
If set to true (combined with ```SUBDIR_ENABLE```), it forces the user to define subdirectory for file via REST API. If subdirectory is not provided it will result in an error.

This setting is turned off by default.

### FILENAME_ENABLE
If set to true, it is possible to define file name via REST API (request parameter ```filename```) under which the file will be stored. Limitations described in [defining filenames](#defining-filenames) apply.

This setting is turned on by default.

### FILENAME_FORCE
If set to true (combined with ```FILENAME_ENABLE```), it forces the user to define file name via REST API. If file name is not provided it will result in an error.

This setting is turned off by default.

## Clustered systems
Sometimes you may wish to have PictShare deployed in a clustered environment, but in such systems it could happen that directories where files are stored or archived is not the same as the upload directory where PictShare stores them.

### FETCH_SCRIPT
Through configurable option ```FETCH_SCRIPT``` it is possible to define absolute path to a bash script which will be called if file is not found within upload directory. This script should do the following:
- take a single parameter representing relative path to the file in upload directory (subdir + hash/name)
- check if file is already in upload directory and in such case return OK
- restore the file into upload directory and return OK (if it finds it somewhere in the system)
- return NOTFOUND if it cannot find it.

This setting is turned off by default.

Script example:
```
#!/bin/bash

BASEPATH=/absolute/path/to/pictshare/upload
FILESTORAGEPATH=/absolute/path/to/storage

set -o errexit
trap "echo ERROR" ERR

logger -t $0 -- "$@"

if [[ -z "$1" ]]; then
    # filename = (subdir/)hash/hash
    echo Usage: $0 filename
    exit 1
fi

if [[ -f "$BASEPATH/$1" ]]; then
   echo OK
elif [[ -f "$FILESTORAGEPATH/$(basename $1)" ]]; then
   cp "$FILESTORAGEPATH/$(basename $1)" "$BASEPATH/$1"
   echo OK
else
   echo NOTFOUND
fi
```

## Security and privacy
- By hosting your own images you can delete them any time you want
- You can enable or disable upload logging. Don't want to know who uploaded stuff? Just change the setting in inc/config.inc.php
- No exif data is stored on the server, all jpegs get cleaned on upload
- You have full control over your data. PictShare doesn't need remote libaries or tracking crap

## Requirements
- Apache or Nginx Webserver with PHP
- PHP 5 GD library
- A domain or sub-domain since PictShare can't be run from a subfolder of some other domain

## nginx config
This is a simple config file that should make PictShare work on nginx

- Install php fpm: ```apt-get install php5-fpm```
- Install php Graphics libraries: ```apt-get install php5-gd```

```
server {
    listen 80 default_server;
    server_name your.awesome.domain.name;

    root /var/www/pictshare; # or where ever you put it
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?url=$request_uri; # instead of htaccess mod_rewrite
    }

    location ~ \.php {
        fastcgi_pass unix:/var/run/php5-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_script_name;
    }

    location ~ /(upload|tmp|bin) {
       deny all;
       return 404;
    }
}
```

## Apache config
This is a simple vHost config that should make PictShare work on Apache2. 

- Install php5: ```apt-get install php5 libapache2-mod-php5```
- Install php Graphics libraries: ```apt-get install php5-gd```
- enable mod_rewrite

```
<VirtualHost *:80 >
    ServerAdmin webmaster@sub.domain.tld
    ServerName sub.domain.tld
    ServerAlias sub.domain.tld
    DocumentRoot /var/www/html

    <Directory /var/www/html/>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Order allow,deny
        Allow from All
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/error.log
    LogLevel warn
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```


## Upgrading
- Just re-download the [PictShare zip](https://github.com/chrisiaut/pictshare/archive/master.zip) file and extract and overwrite existing pictshare files. Uploads and config won't be affected.
- Check if your ```/inc/config.inc.php``` file has all settings required by the ```/inc/example.config.inc.php``` since new options might get added in new versions

Or use these commands:

```bash
# to be run from the directory where your pictshare directory sits in
git clone https://github.com/chrisiaut/pictshare.git temp
cp -r temp/* pictshare/.
rm -rf temp
```

## Addons
- Chrome Browser extension: https://chrome.google.com/webstore/detail/pictshare-1-click-imagesc/mgomffcdpnohakmlhhjmiemlolonpafc
  - Source: https://github.com/chrisiaut/PictShare-Chrome-extension
- Plugin to upload images with ShareX: https://github.com/ShareX/CustomUploaders/blob/master/pictshare.net.sxcu

## Traffic analysis
See [Pictshare stats](https://github.com/chrisiaut/pictshare_stats)

## Coming soon
- Delete codes for every uploaded image so users can delete images if no longer needed
- Albums

---
Design (c) by [Bernhard Moser](mailto://bernhard.moser91@gmail.com)

This is a [HASCHEK SOLUTIONS](https://haschek.solutions) project

[![HS logo](https://pictshare.net/css/imgs/hs_logo.png)](https://haschek.solutions)
