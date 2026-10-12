<?php
/** Flash messages. Rendered once per request. */
$flashes = \App\Core\Session::flashes();
foreach ($flashes as $flash):
    $type = in_array($flash['type'], ['success', 'error', 'info'], true) ? $flash['type'] : 'info';
    $iconName = $type === 'success' ? 'check' : ($type === 'error' ? 'alert' : 'info');
?>
<div class="alert alert--<?= e($type) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>">
    <?= icon($iconName, 18) ?>
    <span><?= e($flash['message']) ?></span>
</div>
<?php endforeach; ?>
