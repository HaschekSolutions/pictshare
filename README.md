# PictShare
**[Live Demo](https://www.pictshare.net)**
PictShare is an multi lingual, open source image hosting service with a simple resizing and upload API that you can host yourself.

![PictShare](https://www.pictshare.net/f9b5ea5579.gif)

UPDATES
========
- Nov. 07: Added 9 new (instagram-like) filters
- Nov. 06: Master delete code. One code to delete them all
- Nov. 01: [Restricted uploads and option-use](#restriction-settings)
- Oct. 30: [Rotations and filters](#smart-query-system)

## Why would I want to host my own images?
If you own a server (even an home server) you can host your own PictShare instance so you have full control over your content and can delete images hasslefree.

If you're an **app developer** or **sysadmin** you can use it for a centralized image hosting. With the simple upload API you can upload images to your PictShare instance and get a nice short URL

If you're a blogger like myself, you can use it as storage for your images so the images will still work even if you change blog providers or servers

## Features
- Uploads without logins or validation (that's a good thing, right?)
- Simple API to upload any image from remote servers to your instance [via URL](#upload-from-url) and [via Base64](#upload-from-base64-string)
- 100% file based - no database needed
- PictShare removes all exif data so you can upload photos from your phone and all GPS tags and camera model info get wiped
- Smart [resize, filter and rotation](#smart-query-system) features
- Duplicates don't take up space. If the exact same images is uploaded twice, the second upload will link to the first
- You can control who can upload images or use filters/resizes by defining an [upload-code](#restriction-settings)
- You can set a code in your ```/inc/config.inc.php``` (MASTER_DELETE_CODE) that, if appended to any URL of an Image, will delete the image and all cached versions of it from the server

## Smart query system
PictShare images can be changed after upload just by modifying the URL. It works like this:

<span style="color:blue">https://base.domain</span>/<span style="color:red">&lt;options&gt;</span>/<span style="color:green">&lt;image&gt;</span>

For example: https://pictshare.net/100x100/negative/b260e36b60.jpg will show you the uploaded Image ```b260e36b60.jpg``` but resize it to 100x100 pixels and apply the "negative" filter. The original image will stay untouched.

### Available options
Original URL: ```https://www.pictshare.net/b260e36b60.jpg```

Note: If an option needs a value it works like this: ```optionname_value```. Eg: ```pixelate_10```
If there is some option that's not recognized by PictShare it's simply ignored, so this will work: https://www.pictshare.net/pictshare-is-awesome/b260e36b60.jpg and also even this will work: https://www.pictshare.net/b260e36b60.jpg/how-can-this-still/work/

     Option    |      Paramter      |      Example URL       |      Result
-------------- | ------------------ | ---------------------- | ---------------
**Resizing**   |  |  | 
&lt;width&gt;**x**&lt;height&gt; | -none-			| https://pictshare.net/20x20/b260e36b60.jpg | ![Resized](https://pictshare.net/20x20/b260e36b60.jpg)
forcesize      | -none-				| https://pictshare.net/100x400/forcesize/b260e36b60.jpg | ![Forced size](https://pictshare.net/100x400/forcesize/b260e36b60.jpg)
**Rotating**   |  |  | 
left		   | -none-				| https://pictshare.net/left/b260e36b60.jpg | ![Rotated left](https://pictshare.net/200/left/b260e36b60.jpg)
right		   | -none-				| https://pictshare.net/right/b260e36b60.jpg | ![Rotated right](https://pictshare.net/200/right/b260e36b60.jpg)
upside		   | -none-				| https://pictshare.net/upside/b260e36b60.jpg | ![Upside down](https://pictshare.net/200/upside/b260e36b60.jpg)
**Filters**    |  |  | 
negative       | -none-              | https://pictshare.net/negative/b260e36b60.jpg         | ![Negative](https://pictshare.net/negative/200/b260e36b60.jpg)
grayscale      | -none-              | https://pictshare.net/grayscale/b260e36b60.jpg 		    | ![grayscale](https://pictshare.net/grayscale/200/b260e36b60.jpg)
brightness     | -255 to 255         | https://pictshare.net/brightness_100/b260e36b60.jpg 	| ![brightness](https://pictshare.net/brightness_100/200/b260e36b60.jpg)
edgedetect     | -none-              | https://pictshare.net/edgedetect/b260e36b60.jpg 		  | ![edgedetect](https://pictshare.net/edgedetect/200/b260e36b60.jpg)
smooth         | -10 to 2048         | https://pictshare.net/smooth_3/b260e36b60.jpg 		    | ![smooth](https://pictshare.net/smooth_3/200/b260e36b60.jpg)
contrast       | -100 to 100         | https://pictshare.net/contrast_40/b260e36b60.jpg     | ![contrast](https://pictshare.net/contrast_40/200/b260e36b60.jpg)
pixelate       | 0 to 100            | https://pictshare.net/pixelate_10/b260e36b60.jpg      | ![pixelate](https://pictshare.net/pixelate_10/200/b260e36b60.jpg)
blur           | -none- or 0 to 5    | https://pictshare.net/blur/b260e36b60.jpg      | ![pixelate](https://pictshare.net/blur/200/b260e36b60.jpg)
sepia			| -none-				| https://pictshare.net/sepia/b260e36b60.jpg	| ![instagram filter sepia](https://pictshare.net/200/sepia/b260e36b60.jpg)
sharpen			| -none-				| https://pictshare.net/sharpen/b260e36b60.jpg	| ![instagram filter sharpen](https://pictshare.net/200/sharpen/b260e36b60.jpg)
emboss			| -none-				| https://pictshare.net/emboss/b260e36b60.jpg	| ![instagram filter emboss](https://pictshare.net/200/emboss/b260e36b60.jpg)
cool			| -none-				| https://pictshare.net/cool/b260e36b60.jpg		| ![instagram filter cool](https://pictshare.net/200/cool/b260e36b60.jpg)	
light			| -none-				| https://pictshare.net/light/b260e36b60.jpg	| ![instagram filter light](https://pictshare.net/200/light/b260e36b60.jpg)
aqua			| -none-				| https://pictshare.net/aqua/b260e36b60.jpg		| ![instagram filter aqua](https://pictshare.net/200/aqua/b260e36b60.jpg)	
fuzzy			| -none-				| https://pictshare.net/fuzzy/b260e36b60.jpg	| ![instagram filter fuzzy](https://pictshare.net/200/fuzzy/b260e36b60.jpg)
boost			| -none-				| https://pictshare.net/boost/b260e36b60.jpg	| ![instagram filter boost](https://pictshare.net/200/boost/b260e36b60.jpg)
gray			| -none-				| https://pictshare.net/gray/b260e36b60.jpg		| ![instagram filter gray](https://pictshare.net/200/gray/b260e36b60.jpg)	

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

### Upload from base64 string

Just send a POST request to ```https://pictshare.net/backend.php``` and send your image in base64 as the variable name ```base64```

Server will automatically try to guess the file type (which should work in 90% of the cases) and if it can't figure it out it'll just upload it as png.

## Restriction settings
In your ```config.inc.php``` there are two values to be set: ```UPLOAD_CODE``` and ```IMAGE_CHANGE_CODE```

Both can be set to strings or multiple strings semi;colon;separated. If there is a semicolon in the string, any of the elements will work

### UPLOAD_CODE
If set, will show users a code field in the upload form. If it doesn't match your setting, files won't be uploaded.

If enabled, the Upload API will need the variable ```upload_code``` via GET (eg: ```https://pictshare.net/backend.php?getimage=https://www.0xf.at/css/imgs/logo.png&upload_code=YourUploadCodeHere```)

### IMAGE_CHANGE_CODE
If set,the [options](#available-options) will only work if the URL got the code in it. You can provide the code as option ```changecode_YourChangeCode```

For example: If enabled the image ```https://www.pictshare.net/negative/b260e36b60.jpg``` won't show the negative version but the original.
If you access the image with the code like this: ```https://www.pictshare.net/changecode_YourChangeCode/b260e36b60.jpg``` it gets cached on the server so the next time someone requests the link without providing the change-code, they'll see the inverted image (because you just created it before by accessing the image with the code)

## Security and privacy
- By hosting your own images you can delete them any time you want
- You can enable or disable upload logging. Don't want to know who uploaded stuff? Just change the setting in inc/config.inc.php
- No exif data is stored on the server, all jpegs get cleaned on upload
- You have full control over your data. PictShare doesn't need remote libaries or tracking crap

## Requirements
- Apache or Nginx Webserver with PHP
- PHP 5 GD library
- A domain or sub-domain since PictShare can't be run from a subfolder of some other domain

## Installation
- Make sure you have PHP5 GD libraries installed: ```apt-get install php5-gd```
- Unpack the [PictShare zip](https://github.com/chrisiaut/pictshare/archive/master.zip)
- Rename /inc/example.config.inc.php to /inc/config.inc.php
- (optional) You can and should put a [nginx](https://www.nginx.com/) proxy before the Apache server. That thing is just insanely fast with static content like images.
- (optional) To secure your traffic I'd highly recommend getting an [SSL Cert](https://letsencrypt.org/) for your server if you don't already have one.

### On nginx
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

    location ~ /(upload|tmp) {
       deny all;
       return 404;
    }

}
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
- Plugin to upload images with ShareX: https://github.com/ShareX/CustomUploaders/blob/master/pictshare.net.json

## Coming soon
- Delete codes for every uploaded image so users can delete images if no longer needed
- Albums
- Traffic analysis tool for server admins

![Traffic analysis tool](https://www.pictshare.net/102687fe65.gif)

---
Design (c) by [Bernhard Moser](mailto://bernhard.moser91@gmail.com)

This is a [HASCHEK SOLUTIONS](https://haschek.solutions) project

[![HS logo](https://pictshare.net/css/imgs/hs_logo.png)](https://haschek.solutions)
