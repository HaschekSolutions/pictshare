<?php

class AlbumController implements ContentController
{
    public const ctype = 'static';

    public $mimes = [];

    public function getRegisteredExtensions(): array
    {
        return ['album'];
    }

    public function handleHash($hash, $url, $path = false)
    {
        $meta = getMetadataOfHash($hash);
        $hashes = $meta['hashes'] ?? [];

        $items = [];
        foreach ($hashes as $h) {
            if (!isExistingHash($h)) continue;
            $hmeta = getMetadataOfHash($h);
            $mime  = $hmeta['mime'] ?? 'application/octet-stream';
            $items[] = ['hash' => $h, 'mime' => $mime, 'url' => getURL() . $h];
        }

        return renderTemplate('album.html.php', [
            'album_hash' => $hash,
            'items'      => $items,
            'created'    => $meta['created'] ?? null,
        ]);
    }

    public function handleUpload($tmpfile, $hash = false, $passthrough = false): array
    {
        return ['status' => 'err', 'reason' => 'Albums are created via /api/album'];
    }
}
