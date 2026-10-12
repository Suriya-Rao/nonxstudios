<?php
/** Standalone error page (used for 403/404/405/429/500). No layout dependency. */
$status  = http_response_code() ?: 404;
$titles  = [403 => 'Forbidden', 404 => 'Page not found', 405 => 'Not allowed', 429 => 'Too many requests', 500 => 'Something went wrong'];
// $title may be supplied by Response::abort() to give friendlier copy.
$title ??= $titles[$status] ?? 'Error';
$message = $message ?? '';
$company = 'NONXSTUDIOS';
try {
    $company = \App\Models\Setting::value('company_name');
} catch (\Throwable) {
    // Database unavailable — fall back to the default wordmark.
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($status) ?> — <?= e($title) ?> | <?= e($company) ?></title>
<meta name="robots" content="noindex">
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@700;900&family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
:root{--bg:#080808;--bg2:#111;--text:#F5F5F5;--muted:#999;--accent:#C7FF35;--line:rgba(245,245,245,.12)}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Inter,sans-serif;min-height:100vh;display:grid;place-items:center;padding:2rem}
.wrap{max-width:640px;width:100%;text-align:center}
.brand{font-family:Archivo,sans-serif;font-weight:900;letter-spacing:.2em;font-size:.85rem;color:var(--muted);margin-bottom:3rem;text-transform:uppercase}
.brand span{color:var(--accent)}
.code{font-family:Archivo,sans-serif;font-weight:900;font-size:clamp(5rem,22vw,12rem);line-height:.85;color:transparent;-webkit-text-stroke:1px var(--line);margin:0}
h1{font-family:Archivo,sans-serif;font-size:clamp(1.5rem,5vw,2.5rem);text-transform:uppercase;letter-spacing:-.02em;margin:1rem 0}
p{color:var(--muted);max-width:44ch;margin:0 auto 2rem;line-height:1.6}
a.btn{display:inline-block;padding:1rem 2rem;background:var(--accent);color:#080808;border-radius:999px;text-decoration:none;font-weight:600;font-size:.9rem}
a.btn:hover{background:#d6ff5c}
.links{margin-top:2rem;font-size:.8rem;color:var(--muted)}
.links a{color:var(--muted);text-decoration:none;margin:0 .75rem}
.links a:hover{color:var(--accent)}
</style>
</head>
<body>
<div class="wrap">
    <div class="brand">NONX<span>STUDIOS</span></div>
    <p class="code"><?= e((string) $status) ?></p>
    <h1><?= e($title) ?></h1>
    <p><?= e($message !== '' ? $message : 'The page you are looking for may have moved, or never existed.') ?></p>
    <a class="btn" href="<?= e(url('/')) ?>">Back to home</a>
    <div class="links">
        <a href="<?= e(url('/work')) ?>">Work</a>
        <a href="<?= e(url('/services')) ?>">Services</a>
        <a href="<?= e(url('/contact')) ?>">Contact</a>
    </div>
</div>
</body>
</html>
