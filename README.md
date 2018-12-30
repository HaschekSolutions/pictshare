<p align="center">
  <a href="" rel="noopener">
 <img height=200px src="https://pictshare.net/phhynj.png" alt="PictShare logo"></a>
</p>

<h1 align="center">PictShare</h1>

<h4 align="center">https://pictshare.net</h4>

<div align="center">
 
  
![](https://img.shields.io/php-eye/symfony/symfony.svg)
[![Apache License](https://img.shields.io/badge/license-Apache-blue.svg?style=flat)](https://github.com/HaschekSolutions/pictshare/blob/master/LICENSE)
[![HitCount](http://hits.dwyl.io/HaschekSolutions/pictshare.svg)](http://hits.dwyl.io/HaschekSolutions/pictshare)
[![](https://img.shields.io/github/stars/HaschekSolutions/pictshare.svg?label=Stars&style=social)](https://github.com/HaschekSolutions/pictshare)

#### Host your own `images` `gifs` `mp4s` `text bins` and stay in control

</div>

-----------------------------------------

Table of contents
=================
* [Installation](/rtfm/INSTALL.md)
* [Docker](/rtfm/DOCKER.md)
* [API](/rtfm/API.md)
* [Addons/Integration](/rtfm/INTEGRATIONS.md)
* [Development status](#development-status)
* [Features](#features)

---

## New Features in v2

- Added text hosting (like pastebin)
- Added URL shortening
- Added WebP to images (and conversion from jpg,png to webp)
- Massive code rework. Actually we designed it from the ground up to be more modular and easier to debug

## Breaking changes in v2

- [New API system](/rtfm/API.md). Only single file uploads now via /api/upload.php (POST var name is "file"). [read more..](/rtfm/API.md)
- Data directory renamed from ```upload``` to ```data```
- Backblaze support dropped for now because we didn't need it anymore as ALT_FOLDER is more flexible. If someone needs it, it can easily be implemented via adding a new storage controller. We're happy to accept pull requests
- Dropped support for legacy URLs (/thumbs/1024x768_d8c01b45a6.png cant be used anymore, should be /1024x768/d8c01b45a6.png)

# Features

- Selfhostable
- [Simple upload API](/rtfm/API.md)
- 100% file based - no database needed
- [Scalable](/rtfm/SCALING.md)
- Many [Filters](/rtfm/IMAGEFILTERS.md) for images
- GIF to MP4 conversion
- JPG, PNG to WEBP conversion
- MP4 resizing
- PictShare removes all exif data so you can upload photos from your phone and all GPS tags and camera model info get wiped
- Change and resize your uploads just by editing the URL
- Duplicates don't take up space. If the exact same file is uploaded twice, the second upload will link to the first
- Many [configuration options](/rtfm/CONFIG.md)
- Full control over your data. Delete images with individual and global delete codes


---

## Development roadmap

- [x] Duplicate detection
- [x] Write permission detection
- [x] Delete codes for every uploaded file
- [x] Upload via link/url
- [x] Upload via base64
- [ ] Autodestruct for every uploaded file

### Config options

Read [here](/rtfm/CONFIG.md) what those options do

- [x] ALT_FOLDER
- [x] URL (instead of FORCE_DOMAIN but mandatory)
- [x] LOG_UPLOADER
- [x] FFMPEG_BINARY
- [x] PNG_COMPRESSION
- [x] JPEG_COMPRESSION
- [x] WEBP_COMPRESSION
- [x] MASTER_DELETE_CODE
- [x] MASTER_DELETE_IP
- [x] UPLOAD_FORM_LOCATION
- [ ] UPLOAD_QUOTA
- [ ] UPLOAD_CODE
- [ ] LOW_PROFILE
- [ ] IMAGE_CHANGE_CODE
- [ ] MAX_RESIZED_IMAGES
- [ ] ALLOW_BLOATING
- [ ] BACKBLAZE

### Image hosting
- [X] Resizing
- [ ] Filters
- [x] Gif to mp4 conversion
- [x] Upload of images

### Text file hosting
- [x] Upload of text files
- [x] Render template for text files
- [x] Raw data view
- [x] Downloadable

### URL shortening
- [ ] Upload of links to shorten

### MP4 hosting
- [x] Resizing
- [x] Preview image generation
- [x] Upload of videos
- [x] Automatic conversion if not mobile friendly or wrong encoder used
- [x] Render template for videos


---

This is a [HASCHEK SOLUTIONS](https://haschek.solutions) project

[![HS logo](https://pictshare.net/css/imgs/hs_logo.png)](https://haschek.solutions)
