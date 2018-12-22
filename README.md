# PictShare
**[Live Demo](https://www.pictshare.net)**
PictShare is a selfhostable, open source image, video and text hosting as well as URL shortening service with a simple API.

---
[![Apache License](https://img.shields.io/badge/license-Apache-blue.svg?style=flat)](https://github.com/HaschekSolutions/pictshare/blob/master/LICENSE)

# This is the development branch for Version 2 do not use in production

## New Features in v2:

- Added text hosting (like pastebin)
- Added URL shortening
- Added WebP to images (and conversion from jpg,png to webp)
- Massive code rework. Actually we designed it from the ground up to be more modular and easier to debug

## Status

- [x] Duplicate detection
- [x] Write permission detection

### Image hosting
- [ ] Resizing
- [ ] Filters
- [ ] Gif to mp4 conversion
- [x] Upload of images

### Text file hosting
- [x] Upload of text files
- [x] Render template for text files
- [x] Raw data view
- [x] Downloadable

### URL shortening
- [ ] Upload of links to shorten

### MP4 hosting
- [ ] Resizing
- [x] Preview image generation
- [x] Upload of videos
- [x] Automatic conversion if not mobile friendly or wrong encoder used
- [x] Render template for videos