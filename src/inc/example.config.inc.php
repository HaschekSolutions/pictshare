<?php 
/**
 * All settings that are uncommented are mandatory
 * Others optional
 * 
 * For all possible config options, check https://github.com/HaschekSolutions/pictshare/blob/master/rtfm/CONFIG.md
 * or /rtfm/CONFIG.md in your installation
 */

//Use a specific domain for links presented to the user
//Format: https://your.domain.name/
// MUST HAVE TAILING /
define('URL','https://dev.pictshare.net/');

//define('JPEG_COMPRESSION', 90);
//define('FFMPEG_BINARY','');
//define('ALT_FOLDER','/ftp/pictshare');
//define('ALLOWED_SUBNET','192.168.0.0/24');
//define('CORS_ALLOW_ORIGIN','*'); //needed if you want to reference uploads from another origin, eg via canvas/WebGL

//HTML hosting - OFF by default and for good reason.
//Enabling this lets anyone holding HTML_UPLOAD_CODE publish arbitrary HTML+JS
//that runs on YOUR domain - equivalent to giving them admin/shell-level trust,
//since it can act as your logged-in browser session against /admin and the API.
//Only turn this on if you understand that risk. See "HTML hosting" in
//https://github.com/HaschekSolutions/pictshare/blob/master/rtfm/CONFIG.md
//define('HTML_HOSTING_ENABLED', true);
//define('HTML_UPLOAD_CODE','');

//S3 settings
//
//define('S3_BUCKET','bucketname');
//define('S3_ACCESS_KEY','');
//define('S3_SECRET_KEY','');
//define('S3_ENDPOINT','http://localhost:9000'); //optional, only if you're using S3 compatible storage like Minio