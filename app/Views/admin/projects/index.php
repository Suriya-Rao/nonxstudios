<?php
/** @var array $result @var string $search */
$items = $result['items'];
?>
<div class="panel">
    <div class="panel__head">
        <h2>All projects <span class="muted small">(<?= e((string) $result['total']) ?>)</span></h2>
        <form method="get" action="<?= e(url('/admin/projects')) ?>" class="row">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search title, slug or description…" aria-label="Search projects">
            <button type="submit" class="abtn abtn--sm"><?= icon('search', 15) ?> Search</button>
            <?php if ($search !== ''): ?>
                <a class="abtn abtn--sm" href="<?= e(url('/admin/projects')) ?>">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrap">
        <?php if ($items === []): ?>
            <div class="empty-state">
                <?= icon('cube', 40) ?>
                <h3><?= $search !== '' ? 'No matches' : 'No projects yet' ?></h3>
                <p><?= $search !== '' ? 'No projects match your search.' : 'Create your first project to populate the portfolio.' ?></p>
                <a class="abtn abtn--primary" href="<?= e(url('/admin/projects/create')) ?>"><?= icon('plus', 15) ?> New project</a>
            </div>
        <?php else: ?>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:70px">Image</th>
                        <th>Project</th>
                        <th>Category</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th style="width:70px">Order</th>
                        <th style="width:170px"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $p): ?>
                    <tr>
                        <td>
                            <?php if (!empty($p['thumbnail'])): ?>
                                <img class="thumb-sm" src="<?= e(url('/media/' . ltrim((string) $p['thumbnail'], '/'))) ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <span class="thumb-none">none</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="cell-title"><?= e($p['title']) ?></div>
                            <div class="cell-sub">/<?= e($p['slug']) ?></div>
                        </td>
                        <td><?= e(\App\Models\Project::categoryLabel($p['category'])) ?></td>
                        <td class="small"><?= e((string) $p['year']) ?></td>
                        <td>
                            <?php if ((int) $p['published'] === 1): ?>
                                <span class="badge badge--published">Published</span>
                            <?php else: ?>
                                <span class="badge badge--draft">Draft</span>
                            <?php endif; ?>
                            <?php if ((int) $p['featured'] === 1): ?>
                                <div class="mt-1"><span class="badge badge--featured">Featured</span></div>
                            <?php endif; ?>
                        </td>
                        <td class="small muted"><?= e((string) $p['sort_order']) ?></td>
                        <td>
                            <div class="actions">
                                <?php if ((int) $p['published'] === 1): ?>
                                    <a class="abtn abtn--sm" href="<?= e(url('/project/' . $p['slug'])) ?>" target="_blank" rel="noopener" title="View"><?= icon('eye', 14) ?></a>
                                <?php endif; ?>
                                <a class="abtn abtn--sm" href="<?= e(url('/admin/projects/' . $p['id'] . '/edit')) ?>"><?= icon('edit', 14) ?> Edit</a>
                                <button type="button" class="abtn abtn--sm abtn--danger"
                                        data-delete-project
                                        data-id="<?= e((string) $p['id']) ?>"
                                        data-title="<?= e($p['title']) ?>"
                                        data-action="<?= e(url('/admin/projects/' . $p['id'] . '/delete')) ?>">
                                    <?= icon('trash', 14) ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php if ($result['lastPage'] > 1): ?>
    <nav class="pagination" aria-label="Pagination">
        <?php
        $mk = static function (int $p, string $label, bool $disabled = false, bool $current = false) use ($search) {
            $q = http_build_query(array_filter(['page' => $p, 'q' => $search]));
            $cls = $current ? 'is-current' : ($disabled ? 'is-disabled' : '');
            if ($disabled || $current) {
                return '<span class="' . $cls . '">' . e($label) . '</span>';
            }
            return '<a class="' . $cls . '" href="' . e(url('/admin/projects?' . $q)) . '">' . e($label) . '</a>';
        };
        echo $mk($result['page'] - 1, '‹', $result['page'] <= 1);
        for ($p = 1; $p <= $result['lastPage']; $p++) {
            if ($p === 1 || $p === $result['lastPage'] || abs($p - $result['page']) <= 1) {
                echo $mk($p, (string) $p, false, $p === $result['page']);
            } elseif (abs($p - $result['page']) === 2) {
                echo '<span class="is-disabled">…</span>';
            }
        }
        echo $mk($result['page'] + 1, '›', $result['page'] >= $result['lastPage']);
        ?>
    </nav>
<?php endif; ?>

<!-- Delete confirmation dialog -->
<dialog id="deleteDialog" style="background:var(--panel);color:var(--text);border:1px solid var(--line-2);border-radius:8px;padding:0;max-width:440px;width:92%">
    <form method="post" id="deleteForm" style="padding:1.75rem">
        <?= \App\Core\Csrf::field() ?>
        <h3 style="font-size:1.1rem;margin-bottom:0.75rem">Delete project?</h3>
        <p class="muted small" style="margin:0 0 1.5rem">
            You are about to permanently delete <strong id="deleteTitle" style="color:var(--text)"></strong>
            and all of its gallery images. This cannot be undone.
        </p>
        <div class="form-actions">
            <button type="button" class="abtn" data-dialog-cancel>Cancel</button>
            <button type="submit" class="abtn abtn--danger"><?= icon('trash', 15) ?> Delete permanently</button>
        </div>
    </form>
</dialog>

<script>
(function () {
    var dialog = document.getElementById('deleteDialog');
    if (!dialog || typeof dialog.showModal !== 'function') {
        // Fallback for very old browsers: plain confirm().
        document.querySelectorAll('[data-delete-project]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (confirm('Delete "' + btn.dataset.title + '"? This cannot be undone.')) {
                    var f = document.createElement('form');
                    f.method = 'post'; f.action = btn.dataset.action;
                    f.innerHTML = document.querySelector('#deleteForm input[name=_token]').outerHTML;
                    document.body.appendChild(f); f.submit();
                }
            });
        });
        return;
    }

    var form = document.getElementById('deleteForm');
    var titleEl = document.getElementById('deleteTitle');

    document.querySelectorAll('[data-delete-project]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            form.action = btn.dataset.action;
            titleEl.textContent = btn.dataset.title;
            dialog.showModal();
        });
    });

    dialog.querySelector('[data-dialog-cancel]').addEventListener('click', function () { dialog.close(); });
})();
</script>
