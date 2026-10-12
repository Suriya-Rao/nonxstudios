<?php
/**
 * Flash messages. Rendered once per request.
 *
 * The wrapper is positioned as an overlay just below the fixed site header:
 * rendered in normal flow these messages sit at the top of <main>, where the
 * header (z-index 1000) covers them and the visitor never sees the result of
 * the action they just took.
 */
$flashes = \App\Core\Session::flashes();
if ($flashes): ?>
<div class="flashes" role="region" aria-label="Notifications">
    <?php foreach ($flashes as $flash):
        $type = in_array($flash['type'], ['success', 'error', 'info'], true) ? $flash['type'] : 'info';
        $iconName = $type === 'success' ? 'check' : ($type === 'error' ? 'alert' : 'info');
    ?>
    <div class="alert alert--<?= e($type) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>">
        <?= icon($iconName, 18) ?>
        <span><?= e($flash['message']) ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
