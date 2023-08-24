# Docker
The fastest way to deploy PictShare is via the [official Docker package](https://github.com/HaschekSolutions/pictshare/pkgs/container/pictshare)

```bash
docker run -d -p 80:80 -e "TITLE=My own PictShare" -e "URL=http://localhost/" ghcr.io/hascheksolutions/pictshare
```

[![Docker setup](http://www.pictshare.net/b65dea2117.gif)](https://www.pictshare.net/8a1dec0973.mp4)

## Usage

### Building it
```bash
docker build -t pictshare -f docker/Dockerfile .
```

### Quick start
```bash
docker run -d -p 80:80 --name=pictshare ghcr.io/hascheksolutions/pictshare
```

### Persistent data
```bash
mkdir /data/pictshareuploads
chown 1000 -R /data/pictshareuploads
docker run -d -v /data/pictshareuploads:/var/www/data -p 80:80 --name=pictshare ghcr.io/hascheksolutions/pictshare
```

### Persistent data with increased max upload size
```bash
mkdir /data/pictshareuploads
chown 1000 -R /data/pictshareuploads
docker run -d -e "MAX_UPLOAD_SIZE=1024" -v /data/pictshareuploads:/var/www/data -p 80:80 --name=pictshare ghcr.io/hascheksolutions/pictshare
```

### Development
Using these commands it will mount the current directory in the docker container so you can develop locally without building after each change.

```bash
docker build -t pictshare -f docker/Dockerfile .
docker run -it --rm --name pictshare-dev -p 8080:80 -v $(pwd):/var/www -v $(pwd)/data:/var/www/data -e "URL=http://localhost:8080/" -e "SKIP_FILEPERMISSIONS=true" pictshare
```

## ENV Variables
There are some ENV variables that only apply to the Docker image
- MAX_UPLOAD_SIZE (int | size in MB that will be used for nginx. default 50)

Every other variable can be referenced against the [default PictShare configuration file](https://github.com/HaschekSolutions/pictshare/blob/master/inc/example.config.inc.php).
- TITLE (string | Title of the page)
- URL (string | URL that will be linked to new uploads)
- PNG_COMPRESSION (int | 0-9 how much compression is used. note that this never affects quality. default: 6)
- JPEG_COMPRESSION (int | 0-100 how high should the quality be? More is better. default: 90)
- MASTER_DELETE_CODE (string | code if added to any url, will delete the image)
- MASTER_DELETE_IP (string | ip which can delete any image)
- ALLOWED_SUBNET (CIDR IP range (can be comma separated) | IP subnets which are allowed to upload files)
- ALLOW_BLOATING (true/false | can images be bloated to higher resolutions than the originals)
- UPLOAD_CODE (string | code that has to be supplied to upload an image)
- UPLOAD_FORM_LOCATION (string | absolute path where upload gui will be shown)
- LOW_PROFILE (string | won't display error messages on failed uploads)
- IMAGE_CHANGE_CODE (string | code if provided, needs to be added to image to apply filter/rotation/etc)
- LOG_UPLOADER (true/false | log IPs of uploaders)
- MAX_RESIZED_IMAGES (int | how many versions of a single image may exist? -1 for infinite)
- SHOW_ERRORS (true/false | show upload/size/server errors?)
- SKIP_FILEPERMISSIONS (true/false | enables/disables fixing file permissions on start. default is false)
- ALWAYS_WEBP (true/false | Always tries to server JPGs as WEBp if the client supports it. Default is false)
- ALT_FOLDER (path to a folder where all hashes will be copied to and looked for offsite backup via nfs for example)
- S3_BUCKET (string | Name of your S3 bucket)
- S3_ACCESS_KEY (string | Access Key for your Bucket)
- S3_SECRET_KEY (string | Secrety Key)
- S3_REGION (string | S3 bucket region)
- S3_ENDPOINT (url | If you are using a selfhosted version of S3 like Minio, put your URL here)
- ENCRYPTION_KEY (string | If you want to use encryption for storage controllers, put your encryption key here. [Read more](https://github.com/HaschekSolutions/pictshare/blob/master/rtfm/ENCRYPTION.md))

- FTP_SERVER (string | IP or hostname of your FTP Server )
- FTP_PORT (int | Port of your FTP server (defaults to 21) )
- FTP_SSL (true/false | If FTP server supports SSL-FTP (not sFTP, thats not the same!))
- FTP_USER (string | FTP Username)
- FTP_PASS (string | FTP Password)
- FTP_BASEDIR (string | Base path where files will be stored. Must end with / eg `/web/pictshare/`)

- CONTENTCONTROLLERS (CSV string | If set, will whitelist content controllers for your instance. Must be uppercase and can be comma separated. Example: Only Pictures: `IMAGE`, Pictures and Videos: `IMAGE,VIDEO`)
