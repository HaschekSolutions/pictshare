# How to scale PictShare

If your library is huge then you might want to think about scaling your instances. Pictshare (v2+) was rebuilt with scaling in mind but instead of built-in scaling features we use a smarter system

# The "ALT_FOLDER" setting
You can set the config var ```ALT_FOLDER``` to point to a directory on the same server where pictshare will look for content and put new uploads.

This allows you to have a shared or even a mounted ftp/nfs folder that will act as the "database" of images across multiple PictShare instances.

The main site https://pictshare.net uses this technique to scale across many servers in multiple countries.

Using this method you can have multiple servers for the same domain (with a reverse proxy)