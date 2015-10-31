# PictShare
**[Live Demo](https://www.pictshare.net)**
PictShare is an multi lingual, open source image hosting service with a simple resizing and upload API that you can host yourself.

![PictShare](https://www.pictshare.net/da6733407c.png)

The rotation and filter update is out!
========
[Check it out](#rotation)

## Why would I want to host my own images?
If you own a server (even an home server) you can host your own PictShare instance so you have full control over your content and can delete images hasslefree.

## Features
- Uploads without logins or validation (that's a good thing, right?)
- Simple API to upload any image from remote servers to your instance via URL and via Base64
- 100% file based - no database needed
- PictShare removes all exif data so you can upload photos from your phone and all GPS tags and camera model info get wiped
- Smart resize, filter and rotation features
- Duplicates don't take up space. If the exact same images is uploaded twice, the second upload will link to the first

## Smart query system
PictShare images can be changed after upload just by modifying the URL. It works like this:

<span style="color:blue">https://base.domain</span>/<span style="color:red">&lt;options&gt;</span>/<span style="color:green">&lt;image&gt;</span>

For example: <span style="color:blue">https://pictshare.net</span>/<span style="color:red">100x100/negative</span>/<span style="color:green">b260e36b60.jpg</span>

### Available options
Original URL: ```https://www.pictshare.net/b260e36b60.jpg```

Note: If an option needs a value it works like this: ```optionname_value```. Eg: ```pixelate_10```

     Option    |      Paramter      |      Example URL       |      Result
-------------- | ------------------ | ---------------------- | ---------------
**Resizing** |  |  | 
<span style="color:blue">&lt;width&gt;</span>x<span style="color:red">&lt;height&gt;</span> | -none-			| https://pictshare.net/50x50/b260e36b60.jpg | ![Resized](https://pictshare.net/50x50/b260e36b60.jpg)
**Rotating** |  |  | 
left		   | -none-				| https://pictshare.net/left/b260e36b60.jpg | ![Rotated left]()
right		   | -none-				| https://pictshare.net/right/b260e36b60.jpg | ![Rotated right]()
upside		   | -none-				| https://pictshare.net/upside/b260e36b60.jpg | ![Upside down]()
**Filters** |  |  | 
negative       | -none-              | https://pictshare.net/negative/b260e36b60.jpg         | ![Negative](https://pictshare.net/negative/200/b260e36b60.jpg)
grayscale      | -none-              | https://pictshare.net/grayscale/b260e36b60.jpg 		    | ![grayscale](https://pictshare.net/grayscale/200/b260e36b60.jpg)
brightness     | -255 to 255         | https://pictshare.net/brightness_100/b260e36b60.jpg 	| ![brightness](https://pictshare.net/brightness_100/200/b260e36b60.jpg)
edgedetect     | -none-              | https://pictshare.net/edgedetect/b260e36b60.jpg 		  | ![edgedetect](https://pictshare.net/edgedetect/200/b260e36b60.jpg)
smooth         | -10 to 2048         | https://pictshare.net/smooth_3/b260e36b60.jpg 		    | ![smooth](https://pictshare.net/smooth_3/200/b260e36b60.jpg)
contrast       | -100 to 100         | https://pictshare.net/contrast_40/b260e36b60.jpg     | ![contrast](https://pictshare.net/contrast_40/200/b260e36b60.jpg)
pixelate       | -100 to 100         | https://pictshare.net/pixelate_10/b260e36b60.jpg      | ![pixelate](https://pictshare.net/pixelate_10/200/b260e36b60.jpg)

You can also combine as many options as you want. Even multiple times! Want your image to be negative, resized, grayscale and with increased brightness? No problem: https://pictshare.net/500x500/grayscale/negative/brightness_100/b260e36b60.jpg

## How does the external-upload-API work?

### From URL
PictShare has a simple REST API to upload remote pictures. The API can be accessed via the backend.php file like this:

```https://pictshare.net/backend.php?getimage=<URL of the image you want to upload>```.

#### Example:

Request: ```https://pictshare.net/backend.php?getimage=https://www.0xf.at/css/imgs/logo.png```

The server will answer with the file name and the server path in JSON:

```
{"status":"OK","type":"png","hash":"10ba188162.png","url":"http:\/\/pictshare.net\/10ba188162.png"}
```

### From base64

Just send a POST request to ```https://pictshare.net/backend.php``` and send your image in base64 as the variable name ```base64```

Server will automatically try to guess the file type (which should work in 90% of the cases) and if it can't figure it out it'll just upload it as png.

## Security and privacy
- By hosting your own images you can delete them any time you want
- You can enable or disable upload logging. Don't want to know who uploaded stuff? Just change the setting in index.php
- No exif data is stored on the server, all jpegs get cleaned on upload
- You have full control over your data. PictShare doesn't need remote libaries or tracking crap

## Requirements
- Apache Webserver with PHP (Apache because of the included .htaccess files)
- PHP 5 GD library
- Some hostname or subdomain. Site might get messed up if it's not stored in the root directory of the webserver

## Installing PictShare
- Just unpack it on your webserver (remember, pictshare needs to be in a root directory) and it should work out of the box
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

## Browser extensions
- Chrome: https://chrome.google.com/webstore/detail/pictshare-1-click-imagesc/mgomffcdpnohakmlhhjmiemlolonpafc
  - Source: https://github.com/chrisiaut/PictShare-Chrome-extension

## Coming soon
- Restricted uploads so you can control who may upload on your instance
- Albums

---
Design (c) by [Bernhard Moser](mailto://bernhard.moser91@gmail.com)

This is a [HASCHEK SOLUTIONS](https://haschek.solutions) project

[![HS logo](https://pictshare.net/css/imgs/hs_logo.png)](https://haschek.solutions)
