# How to scale PictShare

If your library is huge then you might want to think about scaling your instances. Pictshare (v2+) was rebuilt with scaling in mind but instead of built-in scaling features we rely on OS level solutions.

# The "ALT_FOLDER" setting
You can set the config var ```ALT_FOLDER``` to point to a directory on the same server where pictshare will look for content and put new uploads.

This allows you to have a shared or even a mounted ftp/nfs folder that will act as the "database" of images across multiple PictShare instances.

The main site https://pictshare.net uses this technique to scale across many servers in multiple countries.

Using this method you can have multiple servers for the same domain (with a reverse proxy)

# Fast, read only instances
PictShare needs strong hardware for video conversion but using smart Nginx configurations you can host an instance on a weak but fast server and relay all uploads to another server with stronger hardware running PictShare

**Example Nginx config for a small VPS that relay all uploads to a faster but private server**

```
server {
        listen 80;
        server_name your.awesome.domain.name;

        client_max_body_size 50M; # Set the max file upload size. This needs to be equal or larger than the size you specified in your php.ini

        root /var/www/pictshare; # or where ever you put it
        index index.php;

    location / {
        try_files $uri $uri/ /index.php?url=$request_uri;
    }
    
    # Magic begins here. Since all uploads have to be made via some script in the /api/ folder we can just redirect
    # these requests to another server that doesn't need to be public facing
    location ^~ /api/ {
        proxy_pass          http://10.12.0.3/; #set to the hostname or ip or url of the powerful server
        include /etc/nginx/proxy_params;
    }

    location ~ \.php {
        fastcgi_pass unix:/var/run/php/php7.3-fpm.sock; #may be slightly different depending on your php version
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_script_name;
    }

    location ~ /(data|tmp|bin|content-controllers|inc|interfaces|storage-controllers|templates|tools) {
       deny all;
       return 404;
    }

}
```
