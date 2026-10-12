<?php

declare(strict_types=1);

/**
 * Generates the brand image assets that browsers and social platforms request
 * but that cannot be authored by hand as text:
 *
 *   public/favicon.ico            16/32/48 multi-size icon (PNG-in-ICO)
 *   public/assets/images/og-image.png   1200x630 social sharing card
 *
 * Both are derived from the same palette and wordmark as
 * public/assets/images/favicon.svg, so the three stay visually consistent.
 *
 * Usage:  php tools/generate-brand-assets.php
 *
 * Requires the GD extension. Committed alongside the assets it produces so the
 * output is reproducible rather than an opaque binary in the repository.
 */

if (!extension_loaded('gd')) {
    fwrite(STDERR, "GD extension is required (php-gd).\n");
    exit(1);
}

$root = dirname(__DIR__);

// --- Palette (mirrors the :root tokens in public/assets/css/site.css) -------
const C_BG      = [0x08, 0x08, 0x08];
const C_BG_2    = [0x11, 0x11, 0x11];
const C_TEXT    = [0xF5, 0xF5, 0xF5];
const C_MUTED   = [0x99, 0x99, 0x99];
const C_MUTED_2 = [0x6B, 0x6B, 0x6B];
const C_ACCENT  = [0xC7, 0xFF, 0x35];

/** @param array{0:int,1:int,2:int} $rgb */
function rgb($im, array $rgb): int
{
    return imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
}

/** Locate a heavy display face for the wordmark, across macOS and Linux. */
function display_font(): ?string
{
    $candidates = [
        '/System/Library/Fonts/Supplemental/Arial Black.ttf',
        '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
        '/Library/Fonts/Arial Bold.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        '/usr/share/fonts/truetype/msttcorefonts/Arial_Bold.ttf',
    ];
    foreach ($candidates as $path) {
        if (is_readable($path)) {
            return $path;
        }
    }
    return null;
}

function body_font(): ?string
{
    $candidates = [
        '/System/Library/Fonts/Supplemental/Arial.ttf',
        '/System/Library/Fonts/Helvetica.ttc',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
    ];
    foreach ($candidates as $path) {
        if (is_readable($path)) {
            return $path;
        }
    }
    return null;
}

// ---------------------------------------------------------------------------
// favicon.ico — 16/32/48, rendered from the same geometry as favicon.svg
// ---------------------------------------------------------------------------

/**
 * Draws the NONX monogram: a white "N" stroke and an accent "X" cross,
 * matching the paths in favicon.svg scaled to an arbitrary square size.
 *
 * $zoom magnifies the mark within the canvas. At 1.0 the glyphs keep the
 * proportions of the 64-unit SVG viewBox (which includes generous padding); a
 * larger value tightens the crop, which is what an app icon wants.
 */
function draw_monogram($im, int $size, bool $with_background, float $zoom = 1.0): void
{
    $white  = rgb($im, C_TEXT);
    $accent = rgb($im, C_ACCENT);

    if ($with_background) {
        $bg = rgb($im, C_BG);
        imagefilledrectangle($im, 0, 0, $size, $size, $bg);
    } else {
        imagealphablending($im, false);
        imagesavealpha($im, true);
        $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefilledrectangle($im, 0, 0, $size, $size, $transparent);
        imagealphablending($im, true);
    }

    // The SVG uses a 64-unit viewBox. Map that space onto the canvas, centred
    // on the mark's own bounding-box centre (33, 32) so zoom stays symmetric.
    $s = ($size / 64) * $zoom;
    $w = max(1, (int) round(5 * $s));
    imagesetthickness($im, $w);

    $x = static fn(float $v): int => (int) round($size / 2 + ($v - 33) * $s);
    $y = static fn(float $v): int => (int) round($size / 2 + ($v - 32) * $s);

    // "N" — 18,46 -> 18,18 -> 32,36 -> 32,18
    imageline($im, $x(18), $y(46), $x(18), $y(18), $white);
    imageline($im, $x(18), $y(18), $x(32), $y(36), $white);
    imageline($im, $x(32), $y(36), $x(32), $y(18), $white);

    // "X" — 38,18 -> 48,46 and 48,18 -> 38,46
    imageline($im, $x(38), $y(18), $x(48), $y(46), $accent);
    imageline($im, $x(48), $y(18), $x(38), $y(46), $accent);
}

/** Pack one or more PNG blobs into a PNG-in-ICO container (Vista+). */
function build_ico(array $pngBlobs): string
{
    $count  = count($pngBlobs);
    $header = pack('vvv', 0, 1, $count); // reserved, type=icon, image count
    $dir    = '';
    $data   = '';
    $offset = 6 + (16 * $count);

    foreach ($pngBlobs as $size => $blob) {
        $dim    = $size >= 256 ? 0 : $size; // 256 is encoded as 0 in the directory
        $dir   .= pack('CCCCvvVV', $dim, $dim, 0, 0, 1, 32, strlen($blob), $offset);
        $data  .= $blob;
        $offset += strlen($blob);
    }

    return $header . $dir . $data;
}

$sizes = [16, 32, 48];
$blobs = [];

foreach ($sizes as $size) {
    $im = imagecreatetruecolor($size, $size);
    imageantialias($im, true);
    draw_monogram($im, $size, with_background: true);

    ob_start();
    imagepng($im);
    $blobs[$size] = (string) ob_get_clean();
}

$icoPath = $root . '/public/favicon.ico';
file_put_contents($icoPath, build_ico($blobs));
printf("wrote %s (%d bytes, sizes: %s)\n", $icoPath, filesize($icoPath), implode('/', $sizes));

// ---------------------------------------------------------------------------
// apple-touch-icon.png — 180x180, the size iOS uses for home-screen bookmarks
// ---------------------------------------------------------------------------
// iOS does not support SVG for this link, so a PNG is required. The mark is
// drawn without the rounded-square background: iOS applies its own mask.

$touchSize = 180;
$touch = imagecreatetruecolor($touchSize, $touchSize);
imageantialias($touch, true);
draw_monogram($touch, $touchSize, with_background: false, zoom: 1.55);

$touchPath = $root . '/public/assets/images/apple-touch-icon.png';
imagepng($touch, $touchPath, 9);
printf("wrote %s (%d bytes, %dx%d)\n", $touchPath, filesize($touchPath), $touchSize, $touchSize);

// ---------------------------------------------------------------------------
// og-image.png — 1200x630 social sharing card
// ---------------------------------------------------------------------------

$W = 1200;
$H = 630;

$im = imagecreatetruecolor($W, $H);
imageantialias($im, true);

$bg = rgb($im, C_BG);
imagefilledrectangle($im, 0, 0, $W, $H, $bg);

// Faint vertical rule structure for depth.
$line = imagecolorallocatealpha($im, 0xF5, 0xF5, 0xF5, 124);
imagesetthickness($im, 1);
for ($gx = 160; $gx < $W; $gx += 160) {
    imageline($im, $gx, 0, $gx, $H, $line);
}

// Outlined accent ring, echoing the circular motifs on the site. GD's
// imageellipse stroke is thin and uneven, so a filled ring is drawn instead by
// stroking an arc-free circle via many short line segments.
$accentRgb = rgb($im, C_ACCENT);
imagesetthickness($im, 3);
$ringCx = 985;
$ringCy = 300;
$ringR  = 210;
$segments = 180;
for ($s = 0; $s < $segments; $s++) {
    $a1 = ($s / $segments) * 2 * M_PI;
    $a2 = (($s + 1) / $segments) * 2 * M_PI;
    imageline(
        $im,
        (int) round($ringCx + $ringR * cos($a1)), (int) round($ringCy + $ringR * sin($a1)),
        (int) round($ringCx + $ringR * cos($a2)), (int) round($ringCy + $ringR * sin($a2)),
        $accentRgb
    );
}

$display = display_font();
$body    = body_font();

$padX = 96;
$markSize = 76;
draw_monogram_at($im, $padX, 76, $markSize);

// Wordmark: NONX in white, STUDIOS in muted, matching the site header.
$y = 322;
if ($display !== null) {
    imagettftext($im, 82, 0, $padX, $y, rgb($im, C_TEXT), $display, 'NONX');
    $nonxWidth = (int) round(ttf_bbox_width($display, 82, 'NONX'));
    imagettftext($im, 82, 0, $padX + $nonxWidth + 16, $y, rgb($im, C_MUTED), $display, 'STUDIOS');
} else {
    // Fallback when no TrueType face is installed.
    imagestring($im, 5, $padX, $y - 24, 'NONXSTUDIOS', rgb($im, C_TEXT));
}

// Accent rule under the wordmark.
imagefilledrectangle($im, $padX, $y + 44, $padX + 120, $y + 48, rgb($im, C_ACCENT));

// Tagline.
$tagline = 'Creative Studio — Motion, Design & 3D';
if ($body !== null) {
    imagettftext($im, 27, 0, $padX, $y + 122, rgb($im, C_MUTED), $body, $tagline);
} else {
    imagestring($im, 4, $padX, $y + 100, $tagline, rgb($im, C_MUTED));
}

// Footer strip.
imagefilledrectangle($im, 0, $H - 4, $W, $H, rgb($im, C_ACCENT));
$footerText = 'nonxstudios.com';
if ($body !== null) {
    imagettftext($im, 22, 0, $padX, $H - 52, rgb($im, C_MUTED_2), $body, $footerText);
} else {
    imagestring($im, 3, $padX, $H - 52, $footerText, rgb($im, C_MUTED_2));
}

$ogPath = $root . '/public/assets/images/og-image.png';
imagepng($im, $ogPath, 9);
printf("wrote %s (%d bytes, %dx%d)\n", $ogPath, filesize($ogPath), $W, $H);

// ---------------------------------------------------------------------------
// Helpers used by the OG card
// ---------------------------------------------------------------------------

/** Width of rendered TrueType text, without the leading bearing. */
function ttf_bbox_width(string $font, float $size, string $text): float
{
    $box = imagettfbbox($size, 0, $font, $text);
    return (float) (max($box[2], $box[4]) - min($box[0], $box[6]));
}

/** Draws the monogram at an arbitrary position and size on an existing canvas. */
function draw_monogram_at($im, int $x, int $y, int $size): void
{
    $white  = rgb($im, C_TEXT);
    $accent = rgb($im, C_ACCENT);
    $s      = $size / 64;
    imagesetthickness($im, max(2, (int) round(5 * $s)));

    $px = static fn(float $v): int => $x + (int) round($v * $s);
    $py = static fn(float $v): int => $y + (int) round($v * $s);

    imageline($im, $px(18), $py(46), $px(18), $py(18), $white);
    imageline($im, $px(18), $py(18), $px(32), $py(36), $white);
    imageline($im, $px(32), $py(36), $px(32), $py(18), $white);

    imageline($im, $px(38), $py(18), $px(48), $py(46), $accent);
    imageline($im, $px(48), $py(18), $px(38), $py(46), $accent);
}
