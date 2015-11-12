<?php 

//If set, can be added to any image URL to delete the image and all versions of the image
//Must be longer than 10 characters
//Usage example:
// image: https://pictshare.net/b260e36b60.jpg
// to delete it, access https://pictshare.net/delete_YOURMASTERDELETECODE/b260e36b60.jpg
// Will render one last time, if refreshed won't be on the server anymore
define('MASTER_DELETE_CODE', false);

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