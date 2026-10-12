<?php
/**
 * Project case study.
 * @var array       $project
 * @var array       $images
 * @var array       $related
 * @var string|null $videoEmbed
 */
$cat    = \App\Models\Project::categoryLabel($project['category']);
$cover  = !empty($project['thumbnail']) ? url('/media/' . ltrim((string) $project['thumbnail'], '/')) : '';
$soft   = array_filter(array_map('trim', explode(',', (string) $project['software'])));
?>
<?php require base_path('app/Views/components/flashes.php'); ?>

<article>
    <header class="project-hero">
        <div class="container">
            <p class="eyebrow"><a href="<?= e(url('/work')) ?>" style="color:inherit">Work</a> / <?= e($cat) ?></p>

            <div class="project-hero__meta">
                <div>Category<strong><?= e($cat) ?></strong></div>
                <div>Year<strong><?= e((string) $project['year']) ?></strong></div>
                <?php if ($soft !== []): ?>
                    <div>Software<strong><?= e(implode(', ', array_slice($soft, 0, 3))) ?></strong></div>
                <?php endif; ?>
            </div>

            <h1><?= e($project['title']) ?></h1>
            <p class="project-hero__lede"><?= e($project['description']) ?></p>
        </div>

        <?php if ($cover !== ''): ?>
            <div class="container">
                <div class="project-cover reveal-mask">
                    <img src="<?= e($cover) ?>" alt="<?= e($project['title']) ?> — cover visual"
                         loading="eager" decoding="async" width="1600" height="1000">
                </div>
            </div>
        <?php endif; ?>
    </header>

    <section class="section">
        <div class="container">
            <div class="project-body">
                <div class="prose" data-reveal>
                    <?php if (!empty($project['case_study'])): ?>
                        <?= render_case_study((string) $project['case_study']) ?>
                    <?php else: ?>
                        <p><?= e($project['description']) ?></p>
                    <?php endif; ?>
                </div>

                <aside class="project-side" data-reveal>
                    <h3>Project details</h3>
                    <dl>
                        <dt>Category</dt><dd><?= e($cat) ?></dd>
                        <dt>Year</dt><dd><?= e((string) $project['year']) ?></dd>
                        <?php if ($soft !== []): ?>
                            <dt>Software</dt>
                            <dd>
                                <div class="tags">
                                    <?php foreach ($soft as $s): ?><span class="tag"><?= e($s) ?></span><?php endforeach; ?>
                                </div>
                            </dd>
                        <?php endif; ?>
                    </dl>
                    <div class="btn-row" style="margin-top:1.75rem">
                        <a class="btn btn--accent btn--sm btn--block" href="<?= e(url('/contact')) ?>">Start a project</a>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <?php if ($videoEmbed !== null): ?>
        <section class="section section--alt">
            <div class="container">
                <div class="section-head">
                    <p class="eyebrow" data-reveal>Watch</p>
                    <h2 data-reveal>Project film</h2>
                </div>
                <div class="video-embed" data-reveal>
                    <iframe src="<?= e($videoEmbed) ?>" title="<?= e($project['title']) ?> — project video"
                            loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($images !== []): ?>
        <section class="section section--alt">
            <div class="container">
                <div class="section-head">
                    <p class="eyebrow" data-reveal>Gallery</p>
                    <h2 data-reveal>Selected frames</h2>
                </div>
                <div class="gallery" data-stagger>
                    <?php foreach ($images as $img): ?>
                        <figure data-reveal>
                            <img src="<?= e(url('/media/' . ltrim((string) $img['path'], '/'))) ?>"
                                 alt="<?= e($img['caption'] ?: $project['title'] . ' — gallery image') ?>"
                                 loading="lazy" decoding="async" width="1600" height="1000">
                            <?php if (!empty($img['caption'])): ?>
                                <figcaption><?= e($img['caption']) ?></figcaption>
                            <?php endif; ?>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($related !== []): ?>
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <p class="eyebrow" data-reveal>More</p>
                    <h2 data-reveal>Related work</h2>
                </div>
                <div class="work-grid is-filtered" data-stagger>
                    <?php foreach ($related as $i => $project): ?>
                        <?php require base_path('app/Views/components/project-card.php'); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</article>
