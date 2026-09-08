# Configuration

PictShare can be configured using a single file: `inc/config.inc.php`

In this file you can set the following options. For a simple working example config file check out [/inc/example.config.inc.php](/inc/example.config.inc.php)

# Config options

|Option | value type | What it does|
|---                      | ---     | ---|
| URL                     | string  | Sets the URL that will be shown to users for each upload. Must be set and must have tailing slash. eg: http://pictshare.local/ |
| LOG_UPLOADER            | bool    | If set to true, all IP addresses of uploaders will be stored in /data/uploads.csv |
| FFMPEG_BINARY           | string  | If you installed ffmpeg on your machine, you can set the binary path here. This allows devices like the Raspberry Pi to be used with PictShare although I wouldn't recommend it because of the sloooooow conversion speed |
| PNG_COMPRESSION         | int     | 0 (no compression) to 9 (best compression) Note that for PNGs the compression doesn't affect the quality of the image, just the en/decode speed and file size |
| JPEG_COMPRESSION        | int     | 0 (worst quality) to 100 (best quality) |
| WEBP_COMPRESSION        | int     | 0 (worst quality, smallest file) to 100 (best quality, biggest file) |
| MASTER_DELETE_CODE      | string  | If set, this code will be accepted to delete any image by adding "delete_yourmasterdeletecode" to any image |
| MASTER_DELETE_IP        | IP addr | If set, allows deletion of image no matter what delete code you provided if request is coming from this single IP |
| UPLOAD_FORM_LOCATION    | string  | If set, will only show the upload form if this url is requested. eg if you set it to /secret/upload then you only see the form if you go to http://your.pictshare.server/secret/upload but bare in mind that the uploads [via API](/rtfm/API.md) will still work for anyone|
| ALLOWED_SUBNET          | IPv4 or IPv6 CIDR | If set, will limit uploads to IPs that match this CIDR |
| ALWAYS_WEBP | bool | If set to `true`, JPGs will always be served as WebP, if the client supports it (if `image/webp` is in header `HTTP_ACCEPT`) | 
| CORS_ALLOW_ORIGIN | string | If set, sends this value as the `Access-Control-Allow-Origin` header on every response. Needed if you want to reference uploads cross-origin, eg as a `<canvas>`/WebGL texture. Example: `*` or `https://your.app` |
| UPLOAD_CODE | string  | If set, all uploads require this code via GET or POST variable "uploadcode" to succeed |
| REDIS_SERVER (NOT IMPLEMENTED) | IP | If you define a REDIS server IP here, it will enable you to use the FFMPEG Worker |
| UPLOAD_QUOTA (NOT IMPLEMENTED)            | int     | Size in MB. If set, will only allow uploads if combined size of uploads on Server is smaller than this value. Does not account for ALT_FOLDER data and resized versions of original uploads won't be added to calculation |
| MAX_RESIZED_IMAGES (NOT IMPLEMENTED )      | string  | If set, limits count of resized images/videos per file on server |
| LOG_VIEWS | bool | If set, will log all views into the ./logs/app.log. Pretty resource heavy, only use for debugging purposes |
| REDIS_CACHING | bool | true by default, Used to cache URL to file mapping. If set to false, PictShare will not use Redis for caching. This is not recommended as it will slow down the server significantly |
| REDIS_SERVER | string | IP address of the Redis server. If set, PictShare will use this server for caching. If not set, PictShare will use the default Redis server on localhost |


# Content controllers
PictShare is not limited to handling just images. Various content types including txt,mp4 and even url shortenings are supported.
By default all of these are enabled but if you only need one or more, you can whitelist them and all others won't be accessible.

|Option | value type | What it does|
|---                      | ---     | ---|
| CONTENTCONTROLLERS             | CSV string | If set, will whitelist content controllers for your instance. Must be uppercase and can be comma separated. Example: Only Pictures: `IMAGE`, Pictures and Videos: `IMAGE,VIDEO` |

Available values for the `CONTENTCONTROLLERS` setting are:

- IMAGE
- SVG
- TEXT
- VIDEO
- URL
- HTML (only does anything if `HTML_HOSTING_ENABLED` is also set - see below)

### HTML hosting (dangerous - disabled by default)

PictShare can host raw, self-contained HTML pages (inline CSS/JS included) verbatim on your domain - handy for quickly sharing one-pager demos with clients. **This is arbitrary script execution on your own domain by design** - anyone who has the code below can serve phishing pages, or JS that rides any authenticated browser session (e.g. an open `/admin` session) against your own API. Treat the code exactly like an admin password, not like a normal upload code.

It is off unless you explicitly set both of the following:

|Option | value type | What it does|
|---                      | ---     | ---|
| HTML_HOSTING_ENABLED    | bool    | Must be `true` to enable HTML hosting at all |
| HTML_UPLOAD_CODE        | string  | Required, separate from `UPLOAD_CODE`. Must be sent as the `htmluploadcode` request field (or the `html_upload_code` argument on the MCP `upload_html` tool) to publish a page. Uploads without it are always rejected, even if your instance otherwise allows anonymous uploads |

If `HTML_HOSTING_ENABLED` is `true` but `HTML_UPLOAD_CODE` is empty, hosting stays disabled - the server logs a warning on every boot either way so this is never silent. If you've whitelisted `CONTENTCONTROLLERS`, you must also add `HTML` to that list.

# Storage controllers

PictShare has an extention system that allows handling of multiple storage solutions or backends. If a requested file is not found locally, PictShare will ask all configured storage controllers if they have it, then download and serve it to the user. 

If you want data on your external storage to be **encrypted**, you can set the following config setting. En/decryption is done automatically on up/download.

|Option | value type | What it does|
|---                      | ---     | ---|
|ENCRYPTION_KEY                      | base64 string     | The key used to encrypt/decrypt files stored in storage controllers. See [/rtfm/ENCRYPTION.md](/rtfm/ENCRYPTION.md) for setup guide |


### Alternative Folder

The ALT_FOLDER option will copy every uploaded file from PictShare to a local path of your choice. This can be used to allow two instances of PictShare to serve the same data. Eg. you can mount a NFS share on your server and configure the ALT_FOLDER variable to point to that folder. All images are then stored on the NFS as well as your PictShare server.

|Option | value type | What it does|
|---                      | ---     | ---|
| ALT_FOLDER              | string  | All uploaded files will be copied to this location. This location can be a mounted network share (eg NFS or FTP, etc). If a file is not found in the normal upload direcotry, ALT_FOLDER will be checked. [more info about scaling PictShare](/rtfm/SCALING.md) |


### S3 (compatible) storage

You can also store all uploaded files on S3 or S3 compatible storage like [Minio](https://min.io/). This can also be used to scale your PictShare instance and have multiple distributed servers to serve the same files.

|Option | value type | What it does|
|---                                | ---           | ---|
|S3_BUCKET                          | string        | Name of your [S3 bucket](https://aws.amazon.com/s3/) |
|S3_ACCESS_KEY                      | string        | Access key for your bucket|
|S3_SECRET_KEY                      | string        | Secret key for your bucket |
|S3_ENDPOINT                        | URL           | Server URL. If you're using S3 compatible software like [Minio](https://min.io/) you can enter the URL here |
|S3_REGION                          | string        | Region of your bucket |

### FTP

Oldschool, insecure and not that fast. But if you use it in combination with [Encryption](/rtfm/ENCRYPTION.md) this could be OK I guess. I don't judge.
This probably requires the php-ftp package but on some platforms it's included in the php-common package.

|Option | value type | What it does|
|---                      | ---         | ---|
|FTP_SERVER               | string      | IP or hostname of your FTP Server |
|FTP_PORT                 | int         | Port number of your FTP Server. Defaults to 21 |
|FTP_SSL                  | bool        | If your FTP server supports SSL-FTP (note: not sFTP! not the same), set it to true |
|FTP_USER                 | string      | FTP Username |
|FTP_PASS                 | string      | FTP Password |
|FTP_BASEDIR              | string      | Base path where files will be stored. Must end with / eg `/web/pictshare/` |
|FTP_PASSIVEMODE          | bool        | Wether to use passive mode or not. If you have troubles with uploading, switch this setting maybe |

# FFMPEG Worker

For faster video en/transcoding there is a php script called `/tools/ffmpeg_worker.php` which is a CLI application looping every second, checking the REDIS Queue for encoding tasks.

This way the rendering queue is detached from php-fpm since PHP can't have threaded workloads and it will make sure a encoding task like resizing of a video won't block the rest of the site from functioning.

For the FFMPEG Worker to be enabled, the config option `REDIS_SERVER` must be set to the IP Address of a redis server.