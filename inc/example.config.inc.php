<?php 

//Set title for your image and mp4 hosting service
define('TITLE', 'PictShare');

// PNG level from 0 (largest file) to 
// 9 (smallest file). Note that this doesn't affect
// quality, only file size and CPU
define('PNG_COMPRESSION', 6);

// JPG compression percenage from 0 (smallest file, worst quality) 
// to 100 (large file, best quality)
define('JPEG_COMPRESSION', 90);

//If set, can be added to any image URL to delete the image and all versions of the image
//Must be longer than 10 characters
//Usage example:
// image: https://pictshare.net/b260e36b60.jpg
// to delete it, access https://pictshare.net/delete_YOURMASTERDELETECODE/b260e36b60.jpg
// Will render one last time, if refreshed won't be on the server anymore
define('MASTER_DELETE_CODE', false);

//if set, the IP, hostname or every device in the IP range (CIDR naming) will be allowed to delete images
//by supplying the parameter "delete"
//use multiple ips/hostnames/ranges: semicolon seperated
//examples:
//======
//ip: define('MASTER_DELETE_IP', '8.8.8.8');
//hostname: define('MASTER_DELETE_IP', 'home.example.com');
//ip range: define('MASTER_DELETE_IP', '192.168.0.0/24'); //all IPs from 192.168.0.0 to 192.168.0.255 can delete
//multiple: define('MASTER_DELETE_IP', '192.168.0.0/24;my.home.net;4.4.2.2');
define('MASTER_DELETE_IP', false);

//If set, upload form will only be shown on that location
//eg: define('UPLOAD_FORM_LOCATION', 'secret/upload'); then the upload form will only be visible
//from http://your.domain/secret/upload
define('UPLOAD_FORM_LOCATION', false);

//If set to true, the only page that will be rendered is the upload form
//if a wrong link is provided, 404 will be shown instead of the error page
//It's meant to be used to hide the fact that you're using pictshare and your site just looks like a content server
//use in combination with UPLOAD_FORM_LOCATION for maximum sneakiness
define('LOW_PROFILE', false);

//if set to a string, this string must be provided before upload.
//you can set multiple codes by;separating;them;with;semicolons
//if set to false, everybody can upload
//for API uploads, the GET Variable 'upload_code' must be provided
define('UPLOAD_CODE', false);

//if set to a string, this string must be provided in the URL to use any options (filters, resizes, etc..)
//you can set multiple codes by;separating;them;with;semicolons
//if set to false, everybody can use options on all images
//if image change code is not provided but the requested image (with options) already exists, it will render to the user just fine
define('IMAGE_CHANGE_CODE', false);

// shall we log all uploaders IP addresses?
define('LOG_UPLOADER', true);

//how many resizes may one image have?
//-1 = infinite
//0 = none
define('MAX_RESIZED_IMAGES',20);

//when the user requests a resize. Can the resized image be bigger than the original?
define('ALLOW_BLOATING', false);

//Force a specific domain for this server. If set to false, will autodetect.
//Format: https://your.domain.name/
define('FORCE_DOMAIN', false);

//Shall errors be displayed to the user?
//For dev environments: true, in production: false
define('SHOW_ERRORS', false);


//for scalability reasons you might want to upload images to cloud providers
//remove comments to use

/* BACKBLAZE B2 */
/* You can find your info here: https://secure.backblaze.com/b2_buckets.htm */
//define('BACKBLAZE',true); //true=>use backblaze false=>don't
//define('BACKBLAZE_ID','');
//define('BACKBLAZE_KEY', '');
//define('BACKBLAZE_BUCKET_ID', '');
//define('BACKBLAZE_BUCKET_NAME', '');
//define('BACKBLAZE_AUTODOWNLOAD', true);   //if true, will download images from backblaze if not found local
//define('BACKBLAZE_AUTOUPLOAD', true);     //if true, will upload images to backblaze when they are uploaded to pictshare
//define('BACKBLAZE_AUTODELETE', true);     //if true, will delete images from backblaze if they are deleted from pictshare
