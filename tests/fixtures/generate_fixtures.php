<?php
// tests/fixtures/generate_fixtures.php
// Run once: docker compose -f docker-compose-dev.yml run --rm pictshare \
//           php /app/public/tests/fixtures/generate_fixtures.php
$dir = __DIR__;

function makeGradientImage(int $w = 200, int $h = 150): \GdImage
{
    $im = imagecreatetruecolor($w, $h);
    for ($x = 0; $x < $w; $x++) {
        $r = (int)(255 * $x / $w);
        $b = 255 - $r;
        $col = imagecolorallocate($im, $r, 100, $b);
        imageline($im, $x, 0, $x, $h - 1, $col);
    }
    return $im;
}

// JPEG
$im = makeGradientImage();
imagejpeg($im, $dir . '/test.jpg', 90);
imagedestroy($im);

// PNG with partial transparency
$im = imagecreatetruecolor(200, 150);
imagealphablending($im, false);
imagesavealpha($im, true);
imagefilledrectangle($im, 0, 0, 199, 149, imagecolorallocatealpha($im, 0, 0, 0, 127));
imagefilledrectangle($im, 20, 20, 180, 130, imagecolorallocate($im, 200, 50, 50));
imagepng($im, $dir . '/test.png');
imagedestroy($im);

// Static GIF (single frame)
$im = makeGradientImage();
imagegif($im, $dir . '/test.gif');
imagedestroy($im);

// Animated GIF: minimal valid 2-frame GIF89a (10x10, loops)
// Built from raw GIF binary to guarantee exactly 2 Graphic Control Extension blocks.
$gce   = "\x21\xF9\x04\x00\x0A\x00\x00\x00";  // GCE, 100ms delay
$desc  = "\x2C\x00\x00\x00\x00\x0A\x00\x0A\x00\x00"; // Image descriptor 10x10
// Minimal LZW-compressed solid-colour image data (pre-computed)
$imgR  = "\x02\x16\x8C\x2D\x99\x87\x2A\x1C\xDC\x33\xA0\x02\x75\xEC\x95\xFA\xA8\xDE\x60\x8C\x04\x91\x4C\x01\x00";
$imgB  = "\x02\x16\x8C\x2D\x99\x87\x2A\x1C\xDC\x33\xA0\x02\x75\xEC\x95\xFA\xA8\xDE\x60\x8C\x04\x91\x4C\x01\x00";
// Use pre-baked base64 blob for reliability
$animatedGif = base64_decode(
    'R0lGODlhCgAKAIABAP8AAP///yH/C05FVFNDQVBFMi4wAwEAAAAh+QQABAABACwAAAAA' .
    'CgAKAAACC1xmqYvtD6OctNqLAAAh+QQABAABACwAAAAACgAKAAACC2RmmYvtD6OctNqLAAA7'
);
// Verify it contains 2+ GCE blocks (animation detection relies on this)
assert(substr_count($animatedGif, "\x21\xF9\x04") > 1,
    'test_animated.gif must contain multiple GCE blocks for animation detection to work');
file_put_contents($dir . '/test_animated.gif', $animatedGif);

// WebP
$im = makeGradientImage();
imagewebp($im, $dir . '/test.webp', 80);
imagedestroy($im);

// BMP
$im = makeGradientImage();
imagebmp($im, $dir . '/test.bmp');
imagedestroy($im);

// Plain text
file_put_contents($dir . '/test.txt', "Hello, PictShare!\nLine two.");

echo "Fixtures generated in $dir\n";
