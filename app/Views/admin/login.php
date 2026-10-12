<?php
/** @var array $old @var array $errors */
$email = (string) ($old['email'] ?? '');
?>
<h1 class="auth__title">Sign in</h1>
<p class="auth__sub">Administrator access to the NONXSTUDIOS studio dashboard.</p>

<form method="post" action="<?= e(url('/admin/login')) ?>" class="form-grid">
    <?= \App\Core\Csrf::field() ?>

    <div class="f<?= isset($errors['email']) ? ' has-error' : '' ?>">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required
               autocomplete="username" autofocus inputmode="email">
        <?php if (isset($errors['email'])): ?><span class="err"><?= e($errors['email']) ?></span><?php endif; ?>
    </div>

    <div class="f<?= isset($errors['password']) ? ' has-error' : '' ?>">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
        <?php if (isset($errors['password'])): ?><span class="err"><?= e($errors['password']) ?></span><?php endif; ?>
    </div>

    <button type="submit" class="abtn abtn--primary abtn--block">Sign in</button>
</form>
