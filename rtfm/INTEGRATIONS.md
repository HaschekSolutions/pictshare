# Integrating PictShare in other programs

- [Pastebinit](/rtfm/PASTEBINIT.md)
- Chrome Browser extension: https://chrome.google.com/webstore/detail/pictshare-1-click-imagesc/mgomffcdpnohakmlhhjmiemlolonpafc
  - Source: https://github.com/hascheksolutions/PictShare-Chrome-extension
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

# Screenshot to pictshare (windows)

This script will upload a screenshot from [Greenshot](https://getgreenshot.org/) to PictShare with the help of a PowerShell script.

Requirements:
- curl (choco install curl)
- [PowerShell 7](https://docs.microsoft.com/en-us/powershell/scripting/install/installing-powershell-on-windows)

Configure Greenshot:
- Settings -> Output
  - Storage location: C:\Temp\
  - Filename pattern: GreenShot
  - Image format: jpg
Create a new External command, under Settings -> Plugins -> Click on External command Plugin -> Configure -> New
- Name: what ever you want here
- Command: find and point to pwsh.exe
- Argument: -w Hidden -F Path\to\this\script -Address consto.com

Create a PowerShell script with the code below.

Feel free to change "C:\Temp\GreenShot.jpg" and "C:\Temp\pictshare_posts.json" to match your needs.

pictshare_posts.json is useful for logging and automating deleting old uploads.

```powershell
#Requires -Version 7

param (
    # Change the base url to match your Pictshare server
    [Parameter(Mandatory)][string]$Address,
    # Change path of where you expect the jpg file to be
    [string]$File = "C:\Temp\GreenShot.jpg",
    # Log file of all requests
    [string]$LogFile = "C:\Temp\pictshare_posts.json",
    # Use http and not https
    [switch]$IsNotHttps,
    # Do not save url to upload to the clipboard
    [switch]$NoSaveToClipboard
)
begin {
    $Protocol = if ($IsNotHttps){
        "http:"
    }else{
        "https:"
    }
}
process {
    # Upload screenshot
    $Response = $(curl -s -F "file=@$File" $Protocol//$Address/api/upload.php) | ConvertFrom-Json
    if ($Response.status -like "ok") {
        if ($NoSaveToClipboard){
            # Don't save url to clipboard
        }else{
            Set-Clipboard -Value $Response.url
        }
    }
    # Output response back from the pictshare server
    if($Response){
        $Response | ConvertTo-Json | Out-File -FilePath $LogFile -Append
    }
}
end {}


# PHP

```php
/*
* @param $path string Path to the file that should be uploaded
* @param $hash string Optional. File name we want on pictshare for the file
*/
function pictshareUploadImage($path,$hash=false)
{
    if(!file_exists($path)) return false;
    $request = curl_init('https://pictshare.net/api/upload.php');
    
    curl_setopt($request, CURLOPT_POST, true);
    curl_setopt(
        $request,
        CURLOPT_POSTFIELDS,
        array(
        'file' => curl_file_create($path),
        'hash'=>$hash
        ));

    // output the response
    curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
    $json = json_decode(curl_exec($request).PHP_EOL,true);

    // close the session
    curl_close($request);

    return $json;
}
```
