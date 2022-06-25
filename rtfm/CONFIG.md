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
| UPLOAD_QUOTA (NOT IMPLEMENTED)            | int     | Size in MB. If set, will only allow uploads if combined size of uploads on Server is smaller than this value. Does not account for ALT_FOLDER data and resized versions of original uploads won't be added to calculation |
| UPLOAD_CODE (NOT IMPLEMENTED             | string  | If set, all uploads require this code via GET or POST variable "uploadcode" or upload will fail |
| MAX_RESIZED_IMAGES (NOT IMPLEMENTED      | string  | If set, limits count of resized images/videos per file on server |

# Content controllers
PictShare is not limited to handling just images. Various content types including txt,mp4 and even url shortenings are supported.
By default all of these are enabled but if you only need one or more, you can whitelist them and all others won't be accessible.

|Option | value type | What it does|
|---                      | ---     | ---|
| CONTENTCONTROLLERS             | CSV string | If set, will whitelist content controllers for your instance. Must be uppercase and can be comma separated. Example: Only Pictures: `IMAGE`, Pictures and Videos: `IMAGE,VIDEO` |

Available values for the `CONTENTCONTROLLERS` setting are:

- IMAGE
- TEXT
- VIDEO
- URL

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
