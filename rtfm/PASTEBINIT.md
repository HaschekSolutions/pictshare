# How to use Pictshare text upload with pastebinit

1. Install [pastebinit](https://help.ubuntu.com/community/Pastebinit)
2. Create a file in /usr/share/pastebinit.d/ called "pictshare.net.conf"
3. Paste the following lines in this new file:

```
[pastebin]
basename = pictshare.net
regexp = https://pictshare.net
https = true

[format]
content = api_paste_code
page = page
regexp = regexp

[defaults]
page = /api/pastebin.php
regexp = (.*)
```

Now you should be able to use pastebinit like this: ```echo "hello world" | pastebinit -b pictshare.net```

If you want to use pictshare as your default you can either make an alias or edit ```/usr/bin/pastebinit``` and set ```defaultPB = "pictshare.net"```