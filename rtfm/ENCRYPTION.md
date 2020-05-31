# Encryption

As of Jan. 2020 you can set up an encryption key in your config which will encrypt all images stored on [external storage](/rtfm/CONFIG.md#storage-controllers)

The files on the PictShare server are not encrypted, only the ones on external storage providers, eg if you want to use S3 as storage.

## Dependencies

To be able to use encryption you'll need the following extensions:

- mb-string (`apt-get install php-mbstring`)
- libsodium (`apt-get install php-libsodium`)

Since only files on [storage controllers](/rtfm/CONFIG.md#storage-controllers) are encrypted, you'll need to configure at least one.

## Preparation

First you'll need to generate a key and encode it in base64.
The easiest way to get it would be to run this command:

`php -r "echo base64_encode(sodium_crypto_secretstream_xchacha20poly1305_keygen());"`

This will output something like `SSdoJvp10ZvOY5v+vAcprxQKjNX1AzD52cAnFwr6yXc=`

Now put this output in your /inc/config.inc.php like this:

`define('ENCRYPTION_KEY','SSdoJvp10ZvOY5v+vAcprxQKjNX1AzD52cAnFwr6yXc=');`

**Warning: If you change or lose the ENCRYPTION_KEY, all encrypted data will be unrecoverably lost**

# How it works

If you have everything running you can upload a new image and it will get encrypted and uploaded to your storage container(s). This means you could even host on untrusted servers/buckets since nobody without the key will be able to decrypt it.

If you have uploaded a few files and see them on your storage container (eg S3) you'll notice the file has the '.enc' extension.

When you now wipe your PictShare instances local data folder and request the file again via the URL, the storage controller will pull the encrypted file, decrypt it and save it locally (unencrypted)