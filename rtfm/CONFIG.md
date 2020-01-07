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
| ALLOWED_SUBNET          | IP addr | If set, will only show the upload form and allow to upload via API if request is coming from this subnet |
| UPLOAD_QUOTA (NOT IMPLEMENTED)            | int     | Size in MB. If set, will only allow uploads if combined size of uploads on Server is smaller than this value. Does not account for ALT_FOLDER data and resized versions of original uploads won't be added to calculation |
| UPLOAD_CODE (NOT IMPLEMENTED             | string  | If set, all uploads require this code via GET or POST variable "uploadcode" or upload will fail |
| MAX_RESIZED_IMAGES (NOT IMPLEMENTED      | string  | If set, limits count of resized images/videos per file on server |

# Storage controllers

PictShare has an extention system that allows handling of multiple storage solutions or backends. You can configure them with the following settings

|Option | value type | What it does|
|---                      | ---     | ---|
|ENCRYPTION_KEY                      | base64 string     | The key used to encrypt/decrypt files stored in storage controllers. See [/rtfm/ENCRYPTION.md] for setup guide |
| ALT_FOLDER              | string  | All uploaded files will be copied to this location. This location can be a mounted network share (eg NFS or FTP, etc). If a file is not found in the normal upload direcotry, ALT_FOLDER will be checked. [more info about scaling PictShare](/rtfm/SCALING.md) |
|S3_BUCKET                      | string     | Name of your [S3 bucket](https://aws.amazon.com/s3/) |
|S3_ACCESS_KEY                      | string     | Access key for your bucket|
|S3_SECRET_KEY                      | string     | Secret key for your bucket
|S3_ENDPOINT                      | URL     | Server URL. If you're using S3 compatible software like [Minio](https://min.io/) you can enter the URL here |
