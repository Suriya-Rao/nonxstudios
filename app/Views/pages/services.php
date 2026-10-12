<?php
/** @var array $services */
?>
<?php require base_path('app/Views/components/flashes.php'); ?>

<header class="page-head">
    <div class="container">
        <p class="eyebrow">Capabilities</p>
        <h1>What We Do</h1>
        <p>From first concept to final frame — a complete creative and production pipeline across motion, design, video and 3D.</p>
    </div>
</header>

<section class="section">
    <div class="container">
        <div class="svc-grid" data-stagger>
            <?php foreach ($services as $i => $svc): ?>
                <article class="svc" data-reveal>
                    <span class="svc__num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <span class="svc__icon"><?= icon($svc['icon'], 26) ?></span>
                    <h2 class="svc__title"><?= e($svc['title']) ?></h2>
                    <p class="svc__desc"><?= e($svc['desc']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--alt">
    <div class="container">
        <div class="section-head">
            <p class="eyebrow" data-reveal>How we work</p>
            <h2 data-reveal>From Idea to Impact</h2>
            <p data-reveal>Every project moves through the same four stages — clear, collaborative, and built to deliver.</p>
        </div>
        <div class="process">
            <?php foreach ([
                ['01', 'Discover', 'Research', 'Understand the project, goals, and creative direction.'],
                ['02', 'Design', 'Concepts', 'Develop concepts, references, and visual treatments.'],
                ['03', 'Create', 'Production', 'Produce, animate, render, edit, and refine the work.'],
                ['04', 'Deliver', 'Handoff', 'Prepare final assets for the intended platforms and formats.'],
            ] as $step): ?>
                <div class="step">
                    <span class="step__num" aria-hidden="true"><?= e($step[0]) ?></span>
                    <div class="step__body">
                        <h3 class="step__title"><?= e($step[1]) ?></h3>
                        <p class="step__desc"><?= e($step[3]) ?></p>
                    </div>
                    <span class="step__meta"><?= e($step[2]) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="cta-band">
    <div class="container">
        <h2 data-reveal>Have an idea? Let's make it real.</h2>
        <p data-reveal>Tell us what you're imagining. We'll help bring it to life.</p>
        <div class="btn-row" style="justify-content:center" data-reveal>
            <a class="btn btn--accent btn--lg" href="<?= e(url('/contact')) ?>">Start Your Project <?= icon('arrow', 18) ?></a>
        </div>
    </div>
</section>
