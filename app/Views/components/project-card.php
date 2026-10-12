<?php
/**
 * Project card.
 * @var array $project
 * @var int   $index    position in the grid (drives wide/variant layout)
 */
$index   = $index ?? 0;
$wide    = in_array($index % 6, [4, 5], true); // two full-width cards per 6
$thumb   = $project['thumbnail'] ?? '';
$imgUrl  = $thumb !== '' ? url('/media/' . ltrim((string) $thumb, '/')) : '';
$cat     = \App\Models\Project::categoryLabel($project['category']);
$year    = (int) $project['year'];
?>
<article class="card<?= $wide ? ' card--wide' : '' ?>" data-category="<?= e($project['category']) ?>" data-reveal>
    <a class="card__link" href="<?= e(url('/project/' . $project['slug'])) ?>" aria-label="<?= e($project['title']) ?> — <?= e($cat) ?>">
        <div class="card__media reveal-mask">
            <?php if ($imgUrl !== ''): ?>
                <img src="<?= e($imgUrl) ?>"
                     alt="<?= e($project['title']) ?> — <?= e($cat) ?> project visual"
                     loading="lazy" decoding="async" width="1600" height="1000">
            <?php endif; ?>
            <span class="card__badge"><?= e($cat) ?></span>
        </div>

        <div class="card__body">
            <div>
                <h3 class="card__title"><?= e($project['title']) ?></h3>
                <p class="card__desc"><?= e($project['description']) ?></p>
            </div>
            <span class="card__year"><?= e((string) $year) ?></span>
        </div>

        <span class="card__arrow" aria-hidden="true"><?= icon('arrow', 20) ?></span>
    </a>
</article>
