<?php
/** @var array $projects @var array $counts @var string $active */
?>
<?php require base_path('app/Views/components/flashes.php'); ?>

<header class="page-head">
    <div class="container">
        <p class="eyebrow">Portfolio</p>
        <h1>Selected Work</h1>
        <p>A collection of visuals, experiments, and worlds we've brought to life.</p>
    </div>
</header>

<section class="section">
    <div class="container">
        <!-- Keeps the heading outline sequential: project cards use <h3>, so a
             level-2 heading must exist between the page <h1> and them. -->
        <h2 class="sr-only">Project gallery</h2>
        <?php if ($projects === []): ?>
            <div class="empty">
                <h3>Nothing here yet</h3>
                <p>No projects match this view. Try another category, or check back soon.</p>
                <div class="btn-row" style="justify-content:center;margin-top:1.5rem">
                    <a class="btn btn--ghost" href="<?= e(url('/work')) ?>">View all work</a>
                </div>
            </div>
        <?php else: ?>
            <div class="filters" data-filters role="group" aria-label="Filter projects by category">
                <button type="button" class="filter<?= $active === '' ? ' is-active' : '' ?>" data-filter="all" aria-pressed="<?= $active === '' ? 'true' : 'false' ?>">
                    All<span class="filter__count"><?= e((string) array_sum($counts)) ?></span>
                </button>
                <?php foreach (\App\Models\Project::CATEGORIES as $slug => $label): ?>
                    <button type="button" class="filter<?= $active === $slug ? ' is-active' : '' ?>" data-filter="<?= e($slug) ?>" aria-pressed="<?= $active === $slug ? 'true' : 'false' ?>">
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
        <?php endif; ?>
    </div>
</section>
