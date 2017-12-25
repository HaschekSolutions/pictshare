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
//if image change code is not provided but the requested image (with options) already exists,
// it will render to the user just fine
define('IMAGE_CHANGE_CODE', false);

// shall we log all uploaders IP addresses?
define('LOG_UPLOADER', true);

//how many resizes may one image have?
//-1 = infinite
//0 = none
define('MAX_RESIZED_IMAGES', 20);

//when the user requests a resize. Can the resized image be bigger than the original?
define('ALLOW_BLOATING', false);

//Force a specific domain for this server. If set to false, will autodetect.
//Format: https://your.domain.name/
define('FORCE_DOMAIN', false);

//Shall errors be displayed to the user?
//For dev environments: true, in production: false
define('SHOW_ERRORS', false);

// List of additionally supported file types (eg. pdf, docx, xls, etc.)
// defined as comma separated value string
define('ADDITIONAL_FILE_TYPES', false);

// Allows defining of directory for uploads as absolute path
// to a directory other then the one inside the project, eg.
// '/tmp/uploads/'
define('UPLOAD_DIR', false);

// If set to true it's possibile to define subdirectories for
// files via API (as a request parameter 'subdir').
// This option is enabled by default.
define('SUBDIR_ENABLE', true);

// If set to true it's possible to define subdirectories for
// files via API (as a request parameter 'filename').
// This option is enabled by default.
define('FILENAME_ENABLE', true);

//Path to the script which will try to find and return a resource
// if it doesn't exist in out standard upload directory but
// does exist somewhere (used in clustering systems).
// This option is disabled by default.
define('FETCH_SCRIPT', false);
