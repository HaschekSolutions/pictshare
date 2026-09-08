<?php
// tests/testconfig.php
define('URL', 'http://localhost/');
define('MAX_UPLOAD_SIZE', 100);
define('REDIS_CACHING', false);
define('JPEG_COMPRESSION', 90);
define('ALLOW_BLOATING', true);
// Enabled here so integration tests can exercise the real upload/serve path;
// the disabled-by-default logic itself is covered by HtmlControllerAuthTest's
// pure-function tests, since a defined constant can't be toggled mid-suite.
define('HTML_HOSTING_ENABLED', true);
define('HTML_UPLOAD_CODE', 'testcode');
