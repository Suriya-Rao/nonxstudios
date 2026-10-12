<?php
/**
 * Standalone layout for the login screen.
 * @var string $content
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in — NONXSTUDIOS Admin</title>
<meta name="robots" content="noindex, nofollow">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body>
<div class="auth">
    <div class="auth__card">
        <div class="auth__brand">NONX<span>STUDIOS</span></div>
        <?php require base_path('app/Views/components/flashes.php'); ?>
        <?= $content ?>
        <div class="auth__foot">
            <a href="<?= e(url('/')) ?>">&larr; Back to website</a>
        </div>
    </div>
</div>
</body>
</html>
