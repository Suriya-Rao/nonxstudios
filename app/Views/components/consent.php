<?php
/**
 * Cookie consent banner. Rendered only when the configured analytics provider
 * actually requires consent — a cookieless provider needs no banner, and
 * showing one anyway trains visitors to dismiss it unread.
 *
 * The server decides whether to render this at all (see ConsentController), so
 * it is visible without JavaScript: the controls are a plain POST form, and
 * the script below only upgrades that to a no-reload request. A no-JS visitor
 * can therefore still accept or decline, and because the vendor script is
 * emitted server-side, their choice takes effect either way.
 */

use App\Services\Analytics;

if (!Analytics::enabled() || !Analytics::requiresConsent()) {
    return;
}
if ((bool) ($analyticsConsented ?? false)) {
    return;
}

$returnTo = \App\Core\Request::path();
?>
<!-- A named <form> is already exposed as a landmark; an explicit role="region"
     here is not allowed on a form element and is flagged by auditors. -->
<form class="consent" id="consent" method="post" action="<?= e(url('/consent')) ?>"
      aria-labelledby="consent-title" aria-describedby="consent-desc"
      data-consent-form>
    <?= \App\Core\Csrf::field() ?>
    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">

    <div class="consent__inner">
        <div class="consent__text">
            <h2 class="consent__title" id="consent-title">Cookies</h2>
            <p class="consent__desc" id="consent-desc">
                We would like to use <?= e(Analytics::label()) ?> to understand how the site is used.
                <?= e(Analytics::usesCookies() ? 'It sets cookies.' : 'It does not set cookies.') ?>
                Nothing is loaded until you choose.
                <a href="<?= e(url('/privacy')) ?>">Privacy policy</a>
            </p>
        </div>
        <div class="consent__actions">
            <button type="submit" class="btn btn--ghost btn--sm" name="consent" value="decline">Decline</button>
            <button type="submit" class="btn btn--accent btn--sm" name="consent" value="accept">Accept</button>
        </div>
    </div>
</form>
