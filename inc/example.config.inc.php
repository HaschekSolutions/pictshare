<?php 

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