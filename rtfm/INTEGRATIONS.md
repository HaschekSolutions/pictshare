# Integrating PictShare in other programs

- [Pastebinit](/rtfm/PASTEBINIT.md)
- Chrome Browser extension: https://chrome.google.com/webstore/detail/pictshare-1-click-imagesc/mgomffcdpnohakmlhhjmiemlolonpafc
  - Source: https://github.com/chrisiaut/PictShare-Chrome-extension
- Plugin to upload images with ShareX: https://github.com/ShareX/CustomUploaders/blob/master/pictshare.net.sxcu

# Upload from CLI

Requirements:
- curl (apt-get install curl)
- jq (apt-get install jq)

```bash
#!/bin/bash
# filename: pictshare.sh
# usage: ./pictshare.sh /path/to/image.jpg

result=$(curl -s -F "file=@${1}" https://pictshare.net/api/upload.php | jq -r .url)
echo $result
```

# Screenshot to pictshare (linux)

This script will create a screenshot (you can choose the area), uploads it to PictShare, copies the raw image to your clipborad and opens the image on PictShare in Chrome

Requirements:
- curl (apt-get install curl)
- jq (apt-get install jq)
- screenshooter (apt-get install xfce4-screenshooter)

```bash
#!/bin/bash
# filename: screenshot2pictshare.sh
# usage: ./screenshot2pictshare.sh

if [[ $# -eq 0 ]] ; then
    xfce4-screenshooter -r -o $0
    exit 0
fi

result=$(curl -s -F "file=@${1}" https://pictshare.net/api/upload.php | jq -r .url)

xclip -selection clipboard -t image/png -i $1
google-chrome $result
```