<?php

return [

    'app' => [
        // Set title for your image and mp4 hosting service
        'title' => env('TITLE', 'PictShare'),

        // PNG level from 0 (largest file) to  9 (smallest file). Note that this
        // doesn't affect quality, only file size and CPU
        'png_compression' => env('PNG_COMPRESSION', 6),

        // JPG compression percenage from 0 (smallest file, worst quality)
        // to 100 (large file, best quality)
        'jpeg_compression' => env('JPEG_COMPRESSION', 90),

        // If set, can be added to any image URL to delete the image and all versions
        // of the image. Must be longer than 10 characters.
        // Usage example:
        // image: https://pictshare.net/b260e36b60.jpg
        // To delete it, access https://pictshare.net/delete_YOURMASTERDELETECODE/b260e36b60.jpg
        // Will render one last time, if refreshed won't be on the server anymore.
        'master_delete_code' => env('MASTER_DELETE_CODE', false),

        // If set, the IP, hostname or every device in the IP range (CIDR naming) will be
        // allowed to delete images by supplying the parameter "delete". Use multiple
        // ips/hostnames/ranges: semicolon seperated
        // Examples:
        // ======
        // ip: '8.8.8.8';
        // hostname: 'home.example.com';
        // ip range: '192.168.0.0/24'; // IPs from 192.168.0.0 to 192.168.0.255 can delete
        // multiple: '192.168.0.0/24;my.home.net;4.4.2.2';
        'master_delete_ip' => env('MASTER_DELETE_IP', false),

        // If set, upload form will only be shown on that location.
        // eg: 'secret/upload'; then the upload form will only be
        // visible from http://your.domain/secret/upload
        'upload_form_location' => env('UPLOAD_FORM_LOCATION', false),

        // If set to true, the only page that will be rendered is the upload form. If a
        // wrong link is provided, 404 will be shown instead of the error page. It's meant
        // to be used to hide the fact that you're using pictshare and your site just
        // looks like a content server use in combination with UPLOAD_FORM_LOCATION for
        // maximum sneakiness.
        'low_profile' => env('LOW_PROFILE', false),

        // If set to a string, this string must be provided before upload. You can set
        // multiple codes by separating them with semicolons. If set to false, everybody
        // can upload for API uploads, the GET Variable 'upload_code' must be provided.
        'upload_code' => env('UPLOAD_CODE', false),

        // If set to a string, this string must be provided in the URL to use any options
        // (filters, resizes, etc..); you can set multiple codes by separating them with
        // semicolons. If set to false, everybody can use options on all images. If image
        // change code is not provided but the requested image (with options) already
        // exists, it will render to the user just fine.
        'image_change_code' => env('IMAGE_CHANGE_CODE', false),

        // Shall we log all uploaders IP addresses?
        'log_uploader' => env('LOG_UPLOADER', true),

        // How many resizes may one image have?
        // -1 = infinite
        // 0  = none
        'max_resized_images' => env('MAX_RESIZED_IMAGES', 20),

        // When the user requests a resize. Can the resized image be bigger than the original?
        'allow_bloating' => env('ALLOW_BLOATING', false),

        // Force a specific domain for this server. If set to false, will autodetect.
        // Format: https://your.domain.name/
        'force_domain' => env('FORCE_DOMAIN', false),

        // Shall errors be displayed to the user?
        // For dev environments: true, in production: false
        'show_errors' => env('SHOW_ERRORS', false),

        // List of additionally supported file types (eg. pdf, docx, xls, etc.)
        // defined as comma separated value string
        'additional_file_types' => env('ADDITIONAL_FILE_TYPES', false),

        // Allows defining of directory for uploads as absolute path
        // to a directory other then the one inside the project, eg.
        // '/tmp/uploads/'
        'upload_dir' => env('UPLOAD_DIR', false),

        // If set to true it's possibile to define subdirectories for
        // files via API (as a request parameter 'subdir').
        // This option is enabled by default.
        'subdir_enable' => env('SUBDIR_ENABLE', true),

        // If set to true it forces the users to define subdirectories
        // for files via API (as a request parameter 'subdit')
        // This option is disabled by default.
        'subdir_force' => env('SUBDIR_FORCE', false),

        // If set to true it's possible to define subdirectories for
        // files via API (as a request parameter 'filename').
        // This option is enabled by default.
        'filename_enable' => env('FILENAME_ENABLE', true),

        // If set to true it forces the users to define filenames for
        // files via API (as a request parameter 'filename').
        // This option is disabled by default.
        'filename_force' => env('FILENAME_FORCE', false),

        // Path to the script which will try to find and return a resource
        // if it doesn't exist in out standard upload directory but
        // does exist somewhere (used in clustering systems).
        // This option is disabled by default.
        'fetch_script' => env('FETCH_SCRIPT', false)
    ],

    'session' => [
        // Defines which cache control HTTP headers are sent to the client.
        // Possible values: public, private_no_expire, private, nocache
        'cache_limiter' => env('SESSION_CACHE_LIMITER', 'public'),

        // Session cache expiry in days
        'cache_expire' => env('SESSION_CACHE_EXPIRE', 90)
    ],

    'view' => [
        'template_dir' => env('TEMPLATE_DIR', __DIR__.'/../resources/templates/')
    ]

];
