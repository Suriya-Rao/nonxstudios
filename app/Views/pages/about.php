<?php
/** @var array $software @var array $process @var array $settings */
$about = \App\Models\Setting::value('about_description');
?>
<?php require base_path('app/Views/components/flashes.php'); ?>

<header class="page-head">
    <div class="container">
        <p class="eyebrow">Studio</p>
        <h1>We Turn Ideas Into<br>Visual Experiences.</h1>
        <p><?= e($about) ?></p>
    </div>
</header>

<section class="section">
    <div class="container">
        <div class="about-grid">
            <div>
                <p class="eyebrow" data-reveal>Approach</p>
                <h2 data-reveal>Creativity meets technical execution.</h2>
                <p data-reveal>NONXSTUDIOS works across design, motion, video, and 3D production. Rather than treating these as separate disciplines, we move between them fluidly — a single idea can become a graphic, a title sequence, a rendered product, or a real-time environment.</p>
                <p data-reveal>We care about craft at every scale: the easing curve on a transition, the falloff of a light, the weight of a headline. That attention is what makes work feel considered rather than produced.</p>
            </div>
            <div class="about-visual reveal-mask" data-parallax="0.06">
                <img src="<?= e(asset('assets/images/studio.svg')) ?>" alt="Abstract studio visual" width="1200" height="1500" loading="lazy" decoding="async">
                <span class="about-visual__tag">
                    <span>Independent Studio</span>
                    <span>Design · Motion · 3D</span>
                </span>
            </div>
        </div>
    </div>
</section>

<section class="section section--alt">
    <div class="container">
        <div class="section-head">
            <p class="eyebrow" data-reveal>Timeline</p>
            <h2 data-reveal>How a project moves</h2>
        </div>
        <div class="process">
            <?php foreach ($process as $step): ?>
                <div class="step">
                    <span class="step__num" aria-hidden="true"><?= e($step['num']) ?></span>
                    <div class="step__body">
                        <h3 class="step__title"><?= e($step['title']) ?></h3>
                        <p class="step__desc"><?= e($step['desc']) ?></p>
                    </div>
                    <span class="step__meta"><?= e($step['meta']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <p class="eyebrow" data-reveal>Toolkit</p>
            <h2 data-reveal>Software & Capabilities</h2>
        </div>
        <div class="software-grid" data-stagger>
            <?php foreach ($software as $sw): ?>
                <div class="software" data-reveal>
                    <span class="software__mark" aria-hidden="true"><?= e($sw['mark']) ?></span>
                    <span class="software__name"><?= e($sw['name']) ?></span>
                    <span class="software__use"><?= e($sw['use']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="field__hint" style="margin-top:1.5rem">Software names are the property of their respective owners and are shown to describe our working toolkit only.</p>
    </div>
</section>

<section class="cta-band">
    <div class="container">
        <h2 data-reveal>Let's build something.</h2>
        <p data-reveal>Tell us what you're imagining. We'll help bring it to life.</p>
        <div class="btn-row" style="justify-content:center" data-reveal>
            <a class="btn btn--accent btn--lg" href="<?= e(url('/contact')) ?>">Start Your Project <?= icon('arrow', 18) ?></a>
        </div>
    </div>
</section>
