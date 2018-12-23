# PictShare version 2
---
[![Apache License](https://img.shields.io/badge/license-Apache-blue.svg?style=flat)](https://github.com/HaschekSolutions/pictshare/blob/master/LICENSE)

# This is the development branch for Version 2 do not use in production
Test site: https://dev.pictshare.net/ (only sometimes on)

Table of contents
=================
* [Installation](/rtfm/INSTALL.md)
* [Docker](/rtfm/DOCKER.md)
* [API](/rtfm/API.md)
* [Addons/Integration](/rtfm/INTEGRATIONS.md)

---

## New Features in v2:

- Added text hosting (like pastebin)
- Added URL shortening
- Added WebP to images (and conversion from jpg,png to webp)
- Massive code rework. Actually we designed it from the ground up to be more modular and easier to debug

## Breaking changes

- New API system. Only single file uploads now via /api/upload.php (POST var name is "file"). [read more..](/rtfm/API.md)

## Status

- [x] Duplicate detection
- [x] Write permission detection
- [ ] Upload via link

### Config options

- [x] ALT_FOLDER
- [x] URL
- [x] LOG_UPLOADER
- [x] FFMPEG_BINARY
- [x] PNG_COMPRESSION
- [x] JPEG_COMPRESSION
- [x] WEBP_COMPRESSION
- [ ] MASTER_DELETE_CODE
- [ ] MASTER_DELETE_IP
- [ ] UPLOAD_CODE

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