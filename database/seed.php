<?php

declare(strict_types=1);

/**
 * NONXSTUDIOS database installer + demo seeder.
 *
 * Usage:
 *   php database/seed.php                          # schema + settings + demo projects
 *   php database/seed.php --fresh                  # drop & recreate tables first
 *   php database/seed.php --admin-email=me@x.com --admin-password='Secret123!' --admin-name="Me"
 *   php database/seed.php --no-demo                # schema + settings only
 *
 * Requires .env to be configured. Safe to re-run: projects are upserted by slug.
 */

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/placeholder.php';

use App\Core\Database;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

// --- Parse CLI options -----------------------------------------------------
$opts = [];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $arg, $m)) {
        $opts[$m[1]] = $m[2] ?? true;
    }
}

$out = static fn(string $m) => fwrite(STDOUT, $m . "\n");

$out('NONXSTUDIOS — database setup');
$out('----------------------------------------');

// --- Connect ---------------------------------------------------------------
try {
    $pdo = Database::connection();
} catch (\Throwable $e) {
    fwrite(STDERR, "Could not connect to the database. Check your .env settings.\n");
    fwrite(STDERR, "DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS must point at a running MySQL/MariaDB server.\n");
    exit(1);
}
$out('Connected to ' . config('database.name'));

// --- Fresh drop ------------------------------------------------------------
if (isset($opts['fresh'])) {
    $out('Dropping existing tables (--fresh)...');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['project_images', 'projects', 'inquiries', 'settings', 'login_attempts', 'users'] as $t) {
        $pdo->exec("DROP TABLE IF EXISTS `$t`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

// --- Schema ----------------------------------------------------------------
$out('Applying schema...');
$sql = file_get_contents(__DIR__ . '/schema.sql');
if ($sql === false) {
    fwrite(STDERR, "Unable to read schema.sql\n");
    exit(1);
}
// Strip full-line comments first, then split on statement terminators.
$sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
foreach (preg_split('/;\s*(?:\n|$)/', $sql) ?: [] as $statement) {
    $statement = trim($statement);
    if ($statement === '') {
        continue;
    }
    $pdo->exec($statement);
}
$out('Schema ready.');

// --- Settings --------------------------------------------------------------
$out('Seeding site settings...');
foreach (Setting::defaults() as $key => $value) {
    $existing = Setting::get($key);
    if ($existing === null) {
        Setting::set($key, $value);
    }
}
$out('Settings seeded (' . count(Setting::defaults()) . ' keys).');

// --- Demo projects ---------------------------------------------------------
$uploadDir = storage_path('uploads');
$thumbDir  = $uploadDir . '/thumbnails';

$demoProjects = [
    [
        'title' => 'Kinetic Type — Identity Reel',
        'slug'  => 'kinetic-type-identity-reel',
        'category' => 'motion-graphics',
        'year' => 2025,
        'description' => 'An animated typographic system exploring rhythm, contrast and motion for a fictional brand launch.',
        'case_study' => "## Brief\n\nA self-initiated study in kinetic typography: how far can a single wordmark be pushed through motion alone?\n\n## Approach\n\nThe wordmark is broken into individual letterforms, then re-assembled through timed reveals, scale jumps and easing curves tuned frame by frame. A restrained palette keeps the focus on movement.\n\n## Result\n\nA 40-second reel demonstrating a reusable motion language that could be applied to title sequences, social cutdowns and broadcast idents.",
        'software' => 'After Effects, Illustrator, Cinema 4D',
        'video_url' => '',
        'featured' => 1,
        'published' => 1,
        'sort_order' => 1,
    ],
    [
        'title' => 'Obsidian — Product Visualization',
        'slug'  => 'obsidian-product-visualization',
        'category' => '3d-cgi',
        'year' => 2025,
        'description' => 'Photoreal CGI product renders studying material response, studio lighting and reflective surfaces.',
        'case_study' => "## Brief\n\nA product-visualization study built entirely in Blender: model, texture, light and render a premium object with no physical prototype.\n\n## Approach\n\nModelled to sub-millimetre tolerances, then lit with a three-point studio setup plus HDRI reflections. Cycles was used for final frames with a denoising pass.\n\n## Result\n\nA set of hero renders and turntable frames suitable for campaign and e-commerce use.",
        'software' => 'Blender, Cycles, Photoshop',
        'video_url' => '',
        'featured' => 1,
        'published' => 1,
        'sort_order' => 2,
    ],
    [
        'title' => 'Signal — Cinematic Trailer Cut',
        'slug'  => 'signal-cinematic-trailer-cut',
        'category' => 'video-editing',
        'year' => 2024,
        'description' => 'A trailer edit exploring pacing, sound-led storytelling and rhythmic cutdowns.',
        'case_study' => "## Brief\n\nBuild a trailer from raw footage that tells a story through pacing and sound rather than narration.\n\n## Approach\n\nSelects were cut against a scratch track, then re-timed to picture. Transitions are motivated by motion in-frame; colour is graded for a cool, high-contrast look.\n\n## Result\n\nA 90-second trailer with social cutdowns at 30s, 15s and 6s.",
        'software' => 'Premiere Pro, After Effects, DaVinci Resolve',
        'video_url' => '',
        'featured' => 1,
        'published' => 1,
        'sort_order' => 3,
    ],
    [
        'title' => 'Halftone — Editorial Poster Series',
        'slug'  => 'halftone-editorial-poster-series',
        'category' => 'graphic-design',
        'year' => 2024,
        'description' => 'A print-first poster series built on a strict grid, oversized type and halftone texture.',
        'case_study' => "## Brief\n\nA graphic-design exploration: how much can be communicated with type, grid and one accent colour?\n\n## Approach\n\nEach poster uses a 12-column grid, a single display typeface and a halftone treatment generated procedurally. Layouts were stress-tested at A0 and thumbnail scale.\n\n## Result\n\nA six-poster series, plus a set of adapted social assets.",
        'software' => 'Illustrator, Photoshop, InDesign',
        'video_url' => '',
        'featured' => 0,
        'published' => 1,
        'sort_order' => 4,
    ],
    [
        'title' => 'Nebula — Real-Time Environment',
        'slug'  => 'nebula-real-time-environment',
        'category' => 'unreal-engine',
        'year' => 2025,
        'description' => 'A real-time cinematic environment exploring volumetric lighting and atmospheric depth in Unreal Engine.',
        'case_study' => "## Brief\n\nCreate an explorable cinematic environment that runs in real time without pre-rendering.\n\n## Approach\n\nBuilt with Nanite and Lumen for geometry and global illumination, with volumetric fog and a custom post-process chain for the final look. Sequencer drives the camera for cinematic captures.\n\n## Result\n\nA real-time scene with captured cinematics and a virtual-production-ready set extension.",
        'software' => 'Unreal Engine 5, Blender, Substance',
        'video_url' => '',
        'featured' => 0,
        'published' => 1,
        'sort_order' => 5,
    ],
    [
        'title' => 'Monolith — Title Sequence Study',
        'slug'  => 'monolith-title-sequence-study',
        'category' => 'motion-graphics',
        'year' => 2026,
        'description' => 'A title-sequence concept combining 3D forms, particle systems and typographic reveals.',
        'case_study' => "## Brief\n\nDesign a title sequence that establishes tone in under 30 seconds.\n\n## Approach\n\nA monolithic 3D form is revealed through a particle transition, resolving into the title. Camera movement is slow and deliberate; the score drives every cut.\n\n## Result\n\nA self-contained title sequence with alternate versions for vertical formats.",
        'software' => 'Blender, After Effects, Houdini (study)',
        'video_url' => '',
        'featured' => 0,
        'published' => 1,
        'sort_order' => 6,
    ],
];

if (!isset($opts['no-demo'])) {
    $out('Generating demo artwork and seeding projects...');
    $created = 0;
    foreach ($demoProjects as $data) {
        $existing = Database::selectOne('SELECT id FROM projects WHERE slug = :s', ['s' => $data['slug']]);
        if ($existing !== null) {
            continue;
        }

        $assets = nonx_generate_project_assets($data['slug'], $thumbDir, 3);
        $data['thumbnail'] = 'thumbnails/' . $assets['thumbnail'];

        $id = Project::create($data);
        foreach ($assets['gallery'] as $i => $file) {
            Project::addImage($id, 'thumbnails/' . $file, 'Demo visual ' . ($i + 1) . ' — generated placeholder');
        }
        $created++;
    }
    $out("Projects seeded: $created new (demo content, clearly labelled as such).");
}

// --- Administrator ---------------------------------------------------------
$adminEmail = is_string($opts['admin-email'] ?? null) ? $opts['admin-email'] : null;
$adminPass  = is_string($opts['admin-password'] ?? null) ? $opts['admin-password'] : null;

if ($adminEmail !== null && $adminPass !== null) {
    if (strlen($adminPass) < 10) {
        fwrite(STDERR, "Admin password must be at least 10 characters.\n");
        exit(1);
    }
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        fwrite(STDERR, "Admin email is not valid.\n");
        exit(1);
    }
    if (User::findByEmail($adminEmail) !== null) {
        User::updatePassword((int) User::findByEmail($adminEmail)['id'], $adminPass);
        $out("Admin password updated for $adminEmail");
    } else {
        User::create(
            is_string($opts['admin-name'] ?? null) ? $opts['admin-name'] : 'Administrator',
            $adminEmail,
            $adminPass
        );
        $out("Administrator created: $adminEmail");
    }
} elseif (User::count() === 0) {
    $out('');
    $out('NOTE: no administrator exists yet. Create one with:');
    $out('  php database/seed.php --admin-email=you@studio.com --admin-password=\'YourStrongPassword\' --admin-name="Your Name"');
}

$out('----------------------------------------');
$out('Done. Start the site with:  php -S localhost:8000 -t public');
