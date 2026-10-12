<?php
/**
 * Admin layout.
 * @var string $content
 * @var string $title
 * @var string $active  Sidebar section key
 * @var array  $admin
 */
$title  = $title ?? 'Dashboard';
$active = $active ?? '';
$admin  = $admin ?? \App\Core\Auth::user();
$newCount = \App\Models\Inquiry::countByStatus('new');
$navItems = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => '/admin',          'icon' => 'grid'],
    ['key' => 'projects',  'label' => 'Projects',  'href' => '/admin/projects', 'icon' => 'cube'],
    ['key' => 'inquiries', 'label' => 'Inquiries', 'href' => '/admin/inquiries','icon' => 'inbox', 'badge' => $newCount],
    ['key' => 'settings',  'label' => 'Settings',  'href' => '/admin/settings', 'icon' => 'settings'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> — NONXSTUDIOS Admin</title>
<meta name="robots" content="noindex, nofollow">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body>
<div class="shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar__brand">NONX<span>STUDIOS</span></div>

        <?php foreach ($navItems as $item): ?>
            <a class="sidebar__link<?= $active === $item['key'] ? ' is-active' : '' ?>"
               href="<?= e(url($item['href'])) ?>">
                <?= icon($item['icon'], 18) ?>
                <span><?= e($item['label']) ?></span>
                <?php if (!empty($item['badge'])): ?>
                    <span class="sidebar__badge"><?= e((string) $item['badge']) ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>

        <div class="sidebar__section">Site</div>
        <a class="sidebar__link" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">
            <?= icon('external', 18) ?><span>View website</span>
        </a>

        <div class="sidebar__spacer"></div>

        <div class="sidebar__user">
            <div class="sidebar__user-name"><?= e($admin['name'] ?? 'Admin') ?></div>
            <div class="sidebar__user-email"><?= e($admin['email'] ?? '') ?></div>
            <form method="post" action="<?= e(url('/admin/logout')) ?>" class="mt-2">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="abtn abtn--sm abtn--block"><?= icon('logout', 15) ?> Sign out</button>
            </form>
        </div>
    </aside>
    <div class="scrim" id="scrim"></div>

    <div class="main">
        <header class="topbar">
            <div class="row">
                <button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle navigation" aria-controls="sidebar">
                    <?= icon('menu', 20) ?>
                </button>
                <span class="topbar__title"><?= e($title) ?></span>
            </div>
            <div class="topbar__actions">
                <a class="abtn abtn--primary abtn--sm" href="<?= e(url('/admin/projects/create')) ?>">
                    <?= icon('plus', 15) ?> New project
                </a>
            </div>
        </header>

        <main class="content">
            <?php require base_path('app/Views/components/flashes.php'); ?>
            <?= $content ?>
        </main>
    </div>
</div>

<script>
(function () {
    var toggle = document.getElementById('menuToggle');
    var sidebar = document.getElementById('sidebar');
    var scrim = document.getElementById('scrim');
    if (!toggle || !sidebar) return;
    function close() { sidebar.classList.remove('is-open'); scrim.classList.remove('is-open'); }
    toggle.addEventListener('click', function () {
        sidebar.classList.toggle('is-open');
        scrim.classList.toggle('is-open');
    });
    scrim.addEventListener('click', close);
})();
</script>
</body>
</html>
