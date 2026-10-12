<?php
/**
 * Homepage.
 * @var array $settings
 * @var array $projects
 * @var array $counts
 * @var array $services
 * @var array $process
 * @var array $software
 */
$headline = explode("\n", \App\Models\Setting::value('hero_headline'));
$subtitle = \App\Models\Setting::value('hero_subtitle');
$email    = $settings['contact_email'] ?? '';
?>
<?php require base_path('app/Views/components/flashes.php'); ?>

<!-- ===================== HERO ===================== -->
<section class="hero" aria-labelledby="hero-title">
    <div class="hero__glow" aria-hidden="true"></div>
    <div class="hero__canvas" data-hero-canvas aria-hidden="true"></div>

    <div class="container hero__inner">
        <p class="hero__brand" data-hero-fade><?= e($settings['company_name'] ?? 'NONXSTUDIOS') ?></p>

        <h1 class="hero__title" id="hero-title">
            <?php foreach ($headline as $i => $line): ?>
                <span class="line"><span><?= $i === count($headline) - 1 ? '<em>' . e(trim($line)) . '</em>' : e(trim($line)) ?></span></span>
            <?php endforeach; ?>
        </h1>

        <p class="hero__sub" data-hero-fade><?= e($subtitle) ?></p>

        <div class="hero__actions" data-hero-fade>
            <a class="btn btn--accent btn--lg" href="<?= e(url('/work')) ?>">
                Explore Our Work <?= icon('arrow', 18) ?>
            </a>
            <a class="btn btn--ghost btn--lg" href="<?= e(url('/contact')) ?>">Start a Project</a>
        </div>
    </div>

    <a class="hero__scroll" href="#selected-work" aria-label="Scroll to selected work">
        <span class="hero__scroll-line" aria-hidden="true"></span>
        Scroll
    </a>
</section>

<!-- ===================== SELECTED WORK ===================== -->
<section class="section" id="selected-work" aria-labelledby="work-heading">
    <div class="container">
        <div class="section-head">
            <p class="eyebrow" data-reveal>Portfolio</p>
            <h2 id="work-heading" data-reveal>Selected Work</h2>
            <p data-reveal>A collection of visuals, experiments, and worlds we've brought to life.</p>
        </div>

        <?php if ($projects === []): ?>
            <div class="empty">
                <h3>No projects published yet</h3>
                <p>Projects added in the studio dashboard will appear here.</p>
            </div>
        <?php else: ?>
            <div class="filters" data-filters role="group" aria-label="Filter projects by category">
                <button type="button" class="filter is-active" data-filter="all" aria-pressed="true">
                    All<span class="filter__count"><?= e((string) array_sum($counts)) ?></span>
                </button>
                <?php foreach (\App\Models\Project::CATEGORIES as $slug => $label): ?>
                    <button type="button" class="filter" data-filter="<?= e($slug) ?>" aria-pressed="false">
                        <?= e($label) ?><span class="filter__count"><?= e((string) ($counts[$slug] ?? 0)) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <p class="sr-only" data-filter-status aria-live="polite"></p>

            <div class="work-grid" data-work-grid data-stagger>
                <?php foreach ($projects as $i => $project): ?>
                    <?php require base_path('app/Views/components/project-card.php'); ?>
                <?php endforeach; ?>
            </div>

            <div class="btn-row" style="margin-top:3rem">
                <a class="btn btn--ghost" href="<?= e(url('/work')) ?>">View all work <?= icon('arrow', 16) ?></a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===================== SERVICES ===================== -->
<section class="section section--alt" id="services" aria-labelledby="services-heading">
    <div class="container">
        <div class="section-head">
            <p class="eyebrow" data-reveal>Capabilities</p>
            <h2 id="services-heading" data-reveal>What We Do</h2>
            <p data-reveal>From first concept to final frame — a full creative and production pipeline under one roof.</p>
        </div>

        <div class="svc-grid" data-stagger>
            <?php foreach ($services as $i => $svc): ?>
                <article class="svc" data-reveal>
                    <span class="svc__num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <span class="svc__icon"><?= icon($svc['icon'], 26) ?></span>
                    <h3 class="svc__title"><?= e($svc['title']) ?></h3>
                    <p class="svc__desc"><?= e($svc['desc']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="btn-row" style="margin-top:2.5rem">
            <a class="btn btn--ghost" href="<?= e(url('/services')) ?>">Explore services <?= icon('arrow', 16) ?></a>
        </div>
    </div>
</section>

<!-- ===================== PROCESS ===================== -->
<section class="section" aria-labelledby="process-heading">
    <div class="container">
        <div class="section-head">
            <p class="eyebrow" data-reveal>Process</p>
            <h2 id="process-heading" data-reveal>From Idea to Impact</h2>
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

<!-- ===================== ABOUT ===================== -->
<section class="section section--alt" aria-labelledby="about-heading">
    <div class="container">
        <div class="about-grid">
            <div>
                <p class="eyebrow" data-reveal>Studio</p>
                <h2 id="about-heading" data-reveal>We Turn Ideas Into Visual Experiences.</h2>
                <p data-reveal><?= e(\App\Models\Setting::value('about_description')) ?></p>
                <p data-reveal>Creativity, technical execution and visual storytelling are treated as one discipline — not separate stages.</p>

                <div class="btn-row" style="margin-top:2rem">
                    <a class="btn btn--ghost" href="<?= e(url('/about')) ?>">More about us <?= icon('arrow', 16) ?></a>
                </div>
            </div>

            <div class="about-visual reveal-mask" data-parallax="0.06">
                <?php if ($projects !== [] && !empty($projects[0]['thumbnail'])): ?>
                    <img src="<?= e(url('/media/' . ltrim((string) $projects[0]['thumbnail'], '/'))) ?>"
                         alt="Abstract rendered visual from the NONXSTUDIOS demo portfolio"
                         loading="lazy" decoding="async" width="1600" height="1000">
                <?php endif; ?>
                <span class="about-visual__tag">
                    <span>Creative Studio</span>
                    <span>Est. <?= e(date('Y')) ?></span>
                </span>
            </div>
        </div>

        <div class="stat-row" data-stagger>
            <div class="stat" data-reveal><div class="stat__val"><?= e((string) max(1, array_sum($counts))) ?>+</div><div class="stat__label">Demo projects</div></div>
            <div class="stat" data-reveal><div class="stat__val">06</div><div class="stat__label">Core services</div></div>
            <div class="stat" data-reveal><div class="stat__val">3D</div><div class="stat__label">Realtime & CGI</div></div>
            <div class="stat" data-reveal><div class="stat__val">∞</div><div class="stat__label">Ideas in progress</div></div>
        </div>
    </div>
</section>

<!-- ===================== SOFTWARE ===================== -->
<section class="section" aria-labelledby="software-heading">
    <div class="container">
        <div class="section-head">
            <p class="eyebrow" data-reveal>Toolkit</p>
            <h2 id="software-heading" data-reveal>Software & Capabilities</h2>
            <p data-reveal>The tools behind the work. We use industry-standard software across 3D, motion, video and design.</p>
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

<!-- ===================== CTA ===================== -->
<section class="cta-band" aria-labelledby="cta-heading">
    <div class="container">
        <h2 id="cta-heading" data-reveal>Have an idea? Let's make it real.</h2>
        <p data-reveal>Tell us what you're imagining. We'll help bring it to life.</p>
        <div class="btn-row" style="justify-content:center" data-reveal>
            <a class="btn btn--accent btn--lg" href="<?= e(url('/contact')) ?>">
                <?= e(\App\Models\Setting::value('cta_text')) ?> <?= icon('arrow', 18) ?>
            </a>
            <?php if ($email !== ''): ?>
                <a class="btn btn--ghost btn--lg" href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>
