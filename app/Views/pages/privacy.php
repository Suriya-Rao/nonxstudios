<?php
$email = \App\Models\Setting::value('contact_email');
$analytics = \App\Services\Analytics::enabled() ? \App\Services\Analytics::class : null;
?>
<header class="page-head">
    <div class="container">
        <p class="eyebrow">Legal</p>
        <h1>Privacy Policy</h1>
        <p>How NONXSTUDIOS handles the information you share with us.</p>
    </div>
</header>

<section class="section">
    <div class="container">
        <div class="legal">
            <p><em>This is a template policy provided with the site. Review and adapt it to your jurisdiction and practices before publishing.</em></p>

            <h2>What we collect</h2>
            <p>When you submit the contact form we collect the information you provide: your name, email address, optional company name, selected service, budget and timeline preferences, your project description, and any preferred contact method.</p>

            <h2>Why we collect it</h2>
            <p>We use this information solely to respond to your enquiry and, if we work together, to deliver the project. We do not sell or rent your information.</p>

            <h2>Technical data</h2>
            <p>To protect the form against spam and abuse we record the IP address and user agent of submissions, and we rate-limit repeated submissions. This data is retained for security purposes only.</p>

            <h2>Retention</h2>
            <p>Enquiry records are retained for as long as needed to respond and maintain our working records, after which they are removed on request or on a periodic review.</p>

            <h2>Your rights</h2>
            <p>You may request access to, correction of, or deletion of the information we hold about you at any time by contacting us<?= $email !== '' ? ' at <a href="mailto:' . e($email) . '">' . e($email) . '</a>' : '' ?>.</p>

            <h2>Cookies</h2>
            <?php if ($analytics === null): ?>
                <p>The public website does not set advertising or tracking cookies. A session cookie is used for the administrator area and for security tokens on forms.</p>
            <?php else: ?>
                <p>The public website does not set advertising cookies. A session cookie is used for the administrator area and for security tokens on forms.</p>
                <p>
                    We also use <strong><?= e($analytics::label()) ?></strong> to understand how the site is used.
                    <?= e($analytics::note()) ?>
                    <?php if ($analytics::requiresConsent()): ?>
                        It loads only after you accept, and you can decline without losing access to anything on the site.
                    <?php endif; ?>
                    <?php if ($analytics::usesCookies()): ?>
                        Declining prevents the analytics cookies from being set at all.
                    <?php endif; ?>
                    <?php if ($analytics::docsUrl() !== ''): ?>
                        See the <a href="<?= e($analytics::docsUrl()) ?>" rel="noopener noreferrer" target="_blank">provider's privacy information</a>.
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>
