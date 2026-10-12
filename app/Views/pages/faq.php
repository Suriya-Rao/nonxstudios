<?php
/** @var array<int,array{q:string,a:string}> $faqs */
$email = \App\Models\Setting::value('contact_email');
?>
<header class="page-head">
    <div class="container">
        <p class="eyebrow">FAQ</p>
        <h1>Questions, answered.</h1>
        <p>The practical details most clients ask before we start — timelines, budgets, process and files.</p>
    </div>
</header>

<section class="section">
    <div class="container">
        <div class="faq">
            <?php foreach ($faqs as $i => $faq): ?>
                <details class="faq__item"<?= $i === 0 ? ' open' : '' ?>>
                    <summary class="faq__q">
                        <span><?= e($faq['q']) ?></span>
                        <?= icon('plus', 18) ?>
                    </summary>
                    <div class="faq__a">
                        <p><?= e($faq['a']) ?></p>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>

        <div class="faq__cta">
            <h2>Still have a question?</h2>
            <p>Tell us what you're planning and we'll come back to you with a clear answer.</p>
            <a class="btn btn--primary" href="<?= e(url('/contact')) ?>">Start a Project</a>
            <?php if ($email !== ''): ?>
                <p class="faq__email">Or email us at <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'FAQPage',
    'mainEntity' => array_map(static fn(array $f): array => [
        '@type' => 'Question',
        'name'  => $f['q'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
    ], $faqs),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>
