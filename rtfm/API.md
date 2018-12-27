# API

## upload.php

- URL https://pictshare.net/api/upload.php
- Method: POST file
- Post var name: file
- Answer type: JSON

If the upload was successful answer will look like this

```
{
    "status":"ok",
    "hash":"y1b6hr.jpg",
    "url":"https://pictshare.net/y1b6hr.jpg"
}
```

If there is an error the server will answer with status:err and a reason

```
{
    "status":"err",
    "reason":"Unsupported filetype"
}
```

### Examples

1. Uploading a file called test.jpg via curl

```curl -F "file=@test.jpg" https://pictshare.net/api/upload.php```

Answer from the server:
```{"status":"ok","hash":"y1b6hr.jpg","url":"https://pictshare.net/y1b6hr.jpg"}```

2. Uploading a file called test.jpg via curl and requesting a custom hash

```curl -F "file=@test.jpg" -F "hash=helloworld.jpg" https://pictshare.net/api/upload.php```

Answer from the server:
```{"status":"ok","hash":"helloworld.jpg","url":"https://pictshare.net/helloworld.jpg"}```

---

## pasetebin.php
- URL https://pictshare.net/api/pastebin.php
- Method: POST/GET text
- Post var name: api_paste_code
- Answer: Plaintext URL to pasted bin

This API can be used to directly post text. Server responds with the URL to the bin or with an error message

### Example

Creating a new text bin that ready "Hello World"

```url -F "api_paste_code=Hello World" https://pictshare.net/api/pastebin.php```

Answer from the server:
```https://pictshare.net/vekjy4e5rr.txt```