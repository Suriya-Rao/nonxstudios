<?php
/**
 * Analytics loader. Emits nothing at all when no provider is configured.
 *
 * When the provider requires consent, the vendor script is not merely
 * deferred — it is never written into the page until the visitor opts in.
 * That keeps the promise the banner makes: no request leaves the browser
 * before acceptance, so there is nothing to undo if they decline.
 *
 * @var bool $analyticsConsented set by the layout
 */

use App\Services\Analytics;

if (!Analytics::enabled()) {
    return;
}

$provider  = Analytics::provider();
$id        = Analytics::id();
$src       = Analytics::scriptUrl();
$needsOptIn = Analytics::requiresConsent();
$consented  = (bool) ($analyticsConsented ?? false);

// With consent required and not yet given, the banner script (not the vendor)
// is what loads; it injects the provider tag once the visitor accepts.
if ($needsOptIn && !$consented) {
    return;
}
?>
<?php if ($provider === 'ga4'): ?>
<script async src="<?= e($src) ?>?id=<?= e($id) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', <?= json_encode($id, JSON_UNESCAPED_SLASHES) ?>, { anonymize_ip: true });
</script>
<?php elseif ($provider === 'plausible'): ?>
<script defer data-domain="<?= e($id) ?>" src="<?= e($src) ?>"></script>
<?php elseif ($provider === 'fathom'): ?>
<script src="<?= e($src) ?>" data-site="<?= e($id) ?>" defer></script>
<?php elseif ($provider === 'umami'): ?>
<script async data-website-id="<?= e($id) ?>" src="<?= e($src) ?>"></script>
<?php endif; ?>
