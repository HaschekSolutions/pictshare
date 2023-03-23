<p align="center">
  <a href="" rel="noopener">
 <img height=200px src="https://pictshare.net/phhynj.png" alt="PictShare logo"></a>
</p>

<h1 align="center">PictShare</h1>

<h4 align="center">https://pictshare.net</h4>

<div align="center">
 
  
![](https://img.shields.io/badge/php-7.1%2B-brightgreen.svg)
[![](https://img.shields.io/docker/pulls/hascheksolutions/pictshare?color=brightgreen)](https://hub.docker.com/r/hascheksolutions/pictshare)
[![](https://img.shields.io/docker/cloud/build/hascheksolutions/pictshare?color=brightgreen)](https://hub.docker.com/r/hascheksolutions/pictshare/builds)
[![Apache License](https://img.shields.io/badge/license-Apache-brightgreen.svg?style=flat)](https://github.com/HaschekSolutions/pictshare/blob/master/LICENSE)
![HitCount](https://visitor-badge.glitch.me/badge?page_id=pictshare)
[![](https://img.shields.io/github/stars/HaschekSolutions/pictshare.svg?label=Stars&style=social)](https://github.com/HaschekSolutions/pictshare)

#### Host your own `images` `gifs` `mp4s` `text bins` and stay in control

</div>

-----------------------------------------
<center>

<p align="center">
    <img src="https://www.pictshare.net/39928d8239.gif" alt="PictShare demo">
</p>

# [INFO] March '23
Since Docker Hub won't allow team Organizations anymore, we moved our images to GitHub Container Registry.
So if you want to use the latest version, please use the new image `ghcr.io/hascheksolutions/pictshare` instead of `hascheksolutions/pictshare`

Table of contents
=================
* [Quick Start](#quickstart)
* [Features](#features)
* [Installation](/rtfm/INSTALL.md)
* [Configuration](/rtfm/CONFIG.md)
* [Docker](/rtfm/DOCKER.md)
* [API](/rtfm/API.md)
* [Addons and integration](/rtfm/INTEGRATIONS.md)
* [Development roadmap](#development-roadmap)

---

## Quickstart

```bash
docker run -d -p 8080:80 --name=pictshare ghcr.io/hascheksolutions/pictshare
```

Then open http://localhost:8080 in your browser

## New Features in v2

- Added support for external storage
- [Encryption of files in external storage](/rtfm/ENCRYPTION.md)
- Added text hosting (like pastebin)
- Added URL shortening
- Added WebP to images (and conversion from jpg,png to webp)
- Massive code rework. Actually we designed it from the ground up to be more modular and easier to debug

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
- [x] Resizing
- [x] Filters
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
