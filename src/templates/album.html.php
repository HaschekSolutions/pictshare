<?php
// $album_hash — the album's own hash string
// $items      — array of ['hash'=>string, 'mime'=>string, 'url'=>string]
// $created    — unix timestamp or null
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= defined('TITLE') ? htmlspecialchars(TITLE) : 'PictShare' ?> — Album</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/pictshare.css" rel="stylesheet">
    <style>
        .album-grid { display: flex; flex-wrap: wrap; gap: 8px; padding: 16px 0; }
        .album-item { position: relative; width: 200px; height: 200px; overflow: hidden; background: #111; border-radius: 4px; }
        .album-item a { display: block; width: 100%; height: 100%; }
        .album-item img { width: 100%; height: 100%; object-fit: cover; }
        .album-item .badge-type { position: absolute; bottom: 4px; right: 4px; font-size: 0.7em; }
        .album-item .file-link { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; color: #ccc; text-decoration: none; font-size: 0.85em; padding: 8px; text-align: center; word-break: break-all; }
    </style>
</head>
<body>
    <div class="container" id="headcontainer">
        <div class="row">
            <div class="col-md-8">
                <a href="/"><img src="/css/imgs/logo/horizontalv3.png" alt="PictShare" /></a>
            </div>
        </div>
    </div>
    <div id="main" class="container">
        <h2>Album</h2>
        <?php if ($created): ?>
            <p class="text-muted small">Created <?= date('Y-m-d H:i', $created) ?> &mdash; <?= count($items) ?> file(s)</p>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <div class="alert alert-warning">This album is empty or its files have been deleted.</div>
        <?php else: ?>
            <div class="album-grid">
                <?php foreach ($items as $item):
                    $h    = htmlspecialchars($item['hash'], ENT_QUOTES, 'UTF-8');
                    $url  = htmlspecialchars($item['url'],  ENT_QUOTES, 'UTF-8');
                    $mime = $item['mime'];
                    $isImage = str_starts_with($mime, 'image/');
                    $isVideo = str_starts_with($mime, 'video/');
                    $isAudio = str_starts_with($mime, 'audio/');
                ?>
                    <div class="album-item">
                        <?php if ($isImage): ?>
                            <a href="<?= $url ?>" target="_blank">
                                <img src="<?= htmlspecialchars(getURL() . '200x200/forcesize/' . $item['hash'], ENT_QUOTES, 'UTF-8') ?>"
                                     alt="<?= $h ?>" loading="lazy" />
                            </a>
                            <span class="badge bg-secondary badge-type">IMG</span>
                        <?php elseif ($isVideo): ?>
                            <a href="<?= $url ?>" target="_blank" class="file-link">
                                &#127916; <?= $h ?>
                            </a>
                            <span class="badge bg-primary badge-type">VID</span>
                        <?php elseif ($isAudio): ?>
                            <a href="<?= $url ?>" target="_blank" class="file-link">
                                &#127925; <?= $h ?>
                            </a>
                            <span class="badge bg-info badge-type">AUD</span>
                        <?php else: ?>
                            <a href="<?= $url ?>" target="_blank" class="file-link">
                                &#128196; <?= $h ?>
                            </a>
                            <span class="badge bg-dark badge-type">FILE</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="mt-3">
                <strong>Album URL:</strong>
                <code><?= htmlspecialchars(getURL() . $album_hash, ENT_QUOTES, 'UTF-8') ?></code>
            </p>
        <?php endif; ?>
    </div>

    <div class="footer">
        <div class="container text-center">
            <p>created by <a href="https://haschek.solutions" target="_blank"><img height="30" src="/css/imgs/hs_logo.png" alt="Haschek Solutions" /></a></p>
        </div>
    </div>

    <script src="/js/jquery.min.js"></script>
    <script src="/js/bootstrap.bundle.min.js"></script>
</body>
</html>
