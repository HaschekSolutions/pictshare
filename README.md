# PictShare
**[Live Demo](https://www.pictshare.net)**
PictShare is an multi lingual, open source image hosting service with a simple resizing and upload API that you can host yourself.

![PictShare](https://www.pictshare.net/da6733407c.png)

## Why would I want to host my own images?
If you own a server (even an home server) you can host your own PictShare instance so you have full control over your content and can delete images hasslefree.

## Features
- Uploads without logins or validation (that's a good thing, right?)
- Simple API to upload any image from remote servers to your instance via URL and via Base64
- 100% file based - no database needed
- PictShare removes all exif data so you can upload photos from your phone and all GPS tags and camera model info get wiped
- Builtin and simple resizing and caching
- Duplicates don't take up space. If the exact same images is uploaded twice, the second upload will link to the first

## What's that about resizing?
Lets's say you have uploaded this image:
![Venus](https://www.pictshare.net/b260e36b60.jpg)

URL: ```https://www.pictshare.net/b260e36b60.jpg```

But you want to use it as your avatar in some forum that only allows **100x100** pixel images.
Instead of editing the picture and re-uploading it you just edit the URL and add "/100x100/ before the image name like this: ```https://www.pictshare.net/100x100/b260e36b60.jpg```

![Smaller Venus](https://www.pictshare.net/100x100/b260e36b60.jpg)

Just by editing the URL and adding the size (in width**x**height) the image gets resized and the resized version gets cached to the disk so it loads much faster on the next request.

You can limit the number of resizes per image in the ```index.php``` file

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