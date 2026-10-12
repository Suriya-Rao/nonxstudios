<?php
/**
 * Public site layout.
 *
 * @var string $content   Rendered page body (injected by View::render)
 * @var string $title     Page title
 * @var string $metaDescription
 * @var string $canonical
 * @var string $ogImage
 * @var string $bodyClass
 * @var array  $settings  Site settings
 * @var array  $nav       Navigation items
 */
$settings       = $settings ?? \App\Models\Setting::all();
$title          = $title ?? (($settings['company_name'] ?? 'NONXSTUDIOS') . ' — Creative Studio');
$metaDescription = $metaDescription ?? ($settings['footer_description'] ?? '');
$canonical      = $canonical ?? url(\App\Core\Request::path());
// Default social card. Pages that have their own artwork (project pages) pass
// an absolute URL through BaseController::meta()'s fourth argument.
$ogImage        = $ogImage ?? url('/assets/images/og-image.png');
$bodyClass      = $bodyClass ?? '';
$nav            = [
    ['label' => 'Home',     'href' => '/'],
    ['label' => 'Work',     'href' => '/work'],
    ['label' => 'Services', 'href' => '/services'],
    ['label' => 'About',    'href' => '/about'],
    ['label' => 'FAQ',      'href' => '/faq'],
    ['label' => 'Contact',  'href' => '/contact'],
];
$current = \App\Core\Request::path();
// Analytics consent is read server-side so the vendor script is only ever
// written into the page after the visitor has actually accepted.
$analyticsConsented = \App\Controllers\ConsentController::granted();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($metaDescription) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">

<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($settings['company_name'] ?? 'NONXSTUDIOS') ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($metaDescription) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="<?= e($settings['company_name'] ?? 'NONXSTUDIOS') ?> — creative studio">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($title) ?>">
<meta name="twitter:description" content="<?= e($metaDescription) ?>">
<meta name="twitter:image" content="<?= e($ogImage) ?>">

<meta name="theme-color" content="#080808">
<link rel="icon" href="<?= e(url('/favicon.ico')) ?>" sizes="any">
<link rel="icon" href="<?= e(asset('assets/images/favicon.svg')) ?>" type="image/svg+xml">
<link rel="apple-touch-icon" sizes="180x180" href="<?= e(asset('assets/images/apple-touch-icon.png')) ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('assets/css/site.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">

<a class="skip-link" href="#main">Skip to content</a>

<div class="grain" aria-hidden="true"></div>
<div class="cursor" aria-hidden="true"><span class="cursor__dot"></span></div>
<div class="loader" aria-hidden="true"><span class="loader__mark">NONX</span></div>

<header class="nav" id="siteNav" data-nav>
    <div class="nav__inner">
        <a class="nav__brand" href="<?= e(url('/')) ?>" aria-label="<?= e($settings['company_name'] ?? 'NONXSTUDIOS') ?> home">
            <span class="nav__brand-mark">NONX</span><span class="nav__brand-sub">STUDIOS</span>
        </a>

        <nav class="nav__links" aria-label="Primary">
            <?php foreach ($nav as $item): ?>
                <?php $active = $current === $item['href'] || ($item['href'] !== '/' && str_starts_with($current, $item['href'])); ?>
                <a href="<?= e(url($item['href'])) ?>" class="nav__link<?= $active ? ' is-active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?>>
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="nav__actions">
            <a class="btn btn--accent btn--sm" href="<?= e(url('/contact')) ?>">
                Start a Project
                <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>

        <button class="nav__toggle" type="button" aria-expanded="false" aria-controls="mobileMenu" data-menu-toggle>
            <span class="sr-only">Toggle menu</span>
            <span class="nav__toggle-bar"></span>
            <span class="nav__toggle-bar"></span>
        </button>
    </div>
</header>

<div class="menu" id="mobileMenu" data-menu hidden>
    <nav class="menu__inner" aria-label="Mobile">
        <?php foreach ($nav as $i => $item): ?>
            <a class="menu__link" href="<?= e(url($item['href'])) ?>" style="--i:<?= $i ?>">
                <span class="menu__num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                <?= e($item['label']) ?>
            </a>
        <?php endforeach; ?>
        <a class="btn btn--accent menu__cta" href="<?= e(url('/contact')) ?>">Start a Project</a>
        <div class="menu__meta">
            <a href="mailto:<?= e($settings['contact_email'] ?? '') ?>"><?= e($settings['contact_email'] ?? '') ?></a>
        </div>
    </nav>
</div>

<main id="main">
<?= $content ?>
</main>

<footer class="footer">
    <div class="footer__grid">
        <div class="footer__brand">
            <div class="footer__logo"><span>NONX</span>STUDIOS</div>
            <p class="footer__desc"><?= e($settings['footer_description'] ?? '') ?></p>
        </div>

        <div class="footer__col">
            <h3 class="footer__title">Navigate</h3>
            <ul class="footer__list">
                <?php foreach ($nav as $item): ?>
                    <li><a href="<?= e(url($item['href'])) ?>"><?= e($item['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="footer__col">
            <h3 class="footer__title">Services</h3>
            <ul class="footer__list">
                <?php foreach (array_filter(array_map('trim', explode("\n", (string) ($settings['services_list'] ?? '')))) as $svc): ?>
                    <li><a href="<?= e(url('/services')) ?>"><?= e($svc) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="footer__col">
            <h3 class="footer__title">Connect</h3>
            <ul class="footer__list">
                <li><a href="mailto:<?= e($settings['contact_email'] ?? '') ?>"><?= e($settings['contact_email'] ?? '') ?></a></li>
                <?php
                $socials = [
                    'social_instagram' => 'Instagram',
                    'social_behance'   => 'Behance',
                    'social_vimeo'     => 'Vimeo',
                    'social_youtube'   => 'YouTube',
                    'social_linkedin'  => 'LinkedIn',
                ];
                foreach ($socials as $key => $label):
                    if (empty($settings[$key])) { continue; } ?>
                    <li><a href="<?= e($settings[$key]) ?>" rel="noopener noreferrer" target="_blank"><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="footer__bottom">
        <p>&copy; <?= e(date('Y')) ?> <?= e($settings['company_name'] ?? 'NONXSTUDIOS') ?>. All rights reserved.</p>
        <div class="footer__legal">
            <a href="<?= e(url('/faq')) ?>">FAQ</a>
            <a href="<?= e(url('/privacy')) ?>">Privacy Policy</a>
            <a href="<?= e(url('/terms')) ?>">Terms of Service</a>
        </div>
    </div>
</footer>

<script src="<?= e(asset('assets/vendor/gsap.min.js')) ?>" defer></script>
<script src="<?= e(asset('assets/vendor/ScrollTrigger.min.js')) ?>" defer></script>
<?php if (!empty($loadHero3d)): ?>
<script src="<?= e(asset('assets/vendor/three.min.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/hero3d.js')) ?>" defer></script>
<?php endif; ?>
<script src="<?= e(asset('assets/js/site.js')) ?>" defer></script>
<?php require base_path('app/Views/components/analytics.php'); ?>
<?php require base_path('app/Views/components/consent.php'); ?>
</body>
</html>
