<?php 

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