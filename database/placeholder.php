<?php

declare(strict_types=1);

/**
 * Procedural placeholder artwork generator (GD).
 *
 * Produces abstract, on-brand dark visuals for demo content so the site never
 * ships with broken image links. Demo imagery only — clearly not client work.
 */

/**
 * @return resource|\GdImage
 */
function nonx_gradient(int $w, int $h, array $from, array $to)
{
    $img = imagecreatetruecolor($w, $h);
    for ($y = 0; $y < $h; $y++) {
        $t = $h > 1 ? $y / ($h - 1) : 0;
        $r = (int) round($from[0] + ($to[0] - $from[0]) * $t);
        $g = (int) round($from[1] + ($to[1] - $from[1]) * $t);
        $b = (int) round($from[2] + ($to[2] - $from[2]) * $t);
        $c = imagecolorallocate($img, $r, $g, $b);
        imagefilledrectangle($img, 0, $y, $w, $y, $c);
    }
    return $img;
}

/**
 * Draw an abstract composition: soft radial glow, concentric rings, a grid and
 * scattered accent particles.
 */
function nonx_render(string $path, int $w, int $h, string $seed, array $accent = [199, 255, 53]): void
{
    mt_srand(crc32($seed));

    // Near-black base with a subtle vertical gradient.
    $img = nonx_gradient($w, $h, [14, 14, 16], [5, 5, 6]);

    // Radial glow.
    $cx = (int) ($w * (0.3 + (mt_rand(0, 40) / 100)));
    $cy = (int) ($h * (0.3 + (mt_rand(0, 40) / 100)));
    $maxR = (int) (max($w, $h) * 0.7);
    for ($r = $maxR; $r > 0; $r -= 3) {
        $t = 1 - ($r / $maxR);
        $alpha = (int) round(120 * $t * $t);
        if ($alpha <= 0) {
            continue;
        }
        $col = imagecolorallocatealpha(
            $img,
            (int) ($accent[0] * 0.25 + 12),
            (int) ($accent[1] * 0.25 + 12),
            (int) ($accent[2] * 0.25 + 12),
            127 - min(127, $alpha)
        );
        imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $col);
    }

    // Thin grid lines.
    $grid = imagecolorallocatealpha($img, 90, 90, 95, 100);
    $step = max(40, (int) ($w / 16));
    for ($x = 0; $x < $w; $x += $step) {
        imageline($img, $x, 0, $x, $h, $grid);
    }
    for ($y = 0; $y < $h; $y += $step) {
        imageline($img, 0, $y, $w, $y, $grid);
    }

    // Concentric rings (technical / futuristic motif).
    $ringCount = mt_rand(3, 6);
    for ($i = 0; $i < $ringCount; $i++) {
        $r = (int) (max($w, $h) * (0.08 + 0.09 * $i));
        $alpha = mt_rand(60, 105);
        $ring = imagecolorallocatealpha($img, 200, 200, 205, $alpha);
        imagesetthickness($img, mt_rand(1, 2));
        imageellipse($img, $cx, $cy, $r * 2, $r * 2, $ring);
    }

    // Accent arcs.
    imagesetthickness($img, 3);
    $arcCol = imagecolorallocatealpha($img, $accent[0], $accent[1], $accent[2], 40);
    $ar = (int) (max($w, $h) * 0.42);
    imagearc($img, $cx, $cy, $ar * 2, $ar * 2, mt_rand(0, 180), mt_rand(200, 340), $arcCol);

    // Particles.
    $particles = mt_rand(40, 90);
    for ($i = 0; $i < $particles; $i++) {
        $px = mt_rand(0, $w);
        $py = mt_rand(0, $h);
        $size = mt_rand(1, 3);
        $alpha = mt_rand(20, 90);
        $pc = imagecolorallocatealpha($img, 240, 240, 240, $alpha);
        imagefilledellipse($img, $px, $py, $size, $size, $pc);
    }

    // A few accent particles.
    for ($i = 0; $i < 8; $i++) {
        $px = mt_rand(0, $w);
        $py = mt_rand(0, $h);
        $size = mt_rand(2, 5);
        $pc = imagecolorallocatealpha($img, $accent[0], $accent[1], $accent[2], mt_rand(10, 60));
        imagefilledellipse($img, $px, $py, $size, $size, $pc);
    }

    // Vignette (darken edges).
    for ($i = 0; $i < 40; $i++) {
        $alpha = 100 - (int) ($i * 2.5);
        $vc = imagecolorallocatealpha($img, 0, 0, 0, max(0, $alpha));
        imagerectangle($img, $i, $i, $w - 1 - $i, $h - 1 - $i, $vc);
    }

    if (!is_dir(dirname($path))) {
        @mkdir(dirname($path), 0775, true);
    }
    imagejpeg($img, $path, 88);
}

/**
 * Generate the standard set of demo assets for a project.
 *
 * @return array{thumbnail:string,gallery:array<int,string>}
 */
function nonx_generate_project_assets(string $slug, string $dir, int $galleryCount = 3): array
{
    $accentSets = [
        [199, 255, 53], [120, 200, 255], [255, 120, 180], [255, 190, 90], [150, 130, 255],
    ];
    $accent = $accentSets[abs(crc32($slug)) % count($accentSets)];

    $thumb = "$dir/$slug-thumb.jpg";
    nonx_render($thumb, 1600, 1000, $slug . '-thumb', $accent);

    $gallery = [];
    for ($i = 1; $i <= $galleryCount; $i++) {
        $g = "$dir/$slug-$i.jpg";
        nonx_render($g, 1600, 1000, $slug . "-g$i", $accent);
        $gallery[] = basename($g);
    }

    return ['thumbnail' => basename($thumb), 'gallery' => $gallery];
}
