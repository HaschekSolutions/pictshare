# Folder structure
All uploads will be created as folders with the name of the file in the `./data` directory.

```
data/
data/<file_name>/
data/<file_name>/<file_name>  # the actual file eg abc1
data/<file_name>/meta.json    # file containing the metadata
```

## Metadata file
The metadata file will contain data like in the following example:

```json
{
    "mime": "image\/jpeg",
    "size": 504921,
    "size_human": "493.09 KB",
    "original_filename": "PXL_20250511_122019042-POP_OUT.jpg",
    "hash": "0gfmeo.jpg",
    "sha1": "7dc14ad3f0c273ce0188aeef19a6104e81ba67dd",
    "uploaded": 1747557347,
    "ip": "::1",
    "useragent": "Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/136.0.0.0 Safari\/537.36",
    "delete_code": "z94fd5tyto1u2bww23kec0f52irb7x49",
    "delete_url": "http:\/\/localhost:8080\/delete_z94fd5tyto1u2bww23kec0f52irb7x49\/0gfmeo.jpg",
    "remote_port": "35856"
}
```

# Redis index
If redis caching is enabled, this structure will be created on the fly:

- cache:byurl:<url> => <content controller>;<file_name> # cached response
- served:<file_name> => number of views # view count
