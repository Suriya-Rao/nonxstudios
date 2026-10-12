<?php
/**
 * Project create/edit form.
 * @var array|null $project @var array $images @var array $categories @var array $errors @var array $old
 */
$isEdit = $project !== null;
$val = static function (string $key, string $default = '') use ($project, $old) {
    if (array_key_exists($key, $old)) { return (string) $old[$key]; }
    if ($project !== null && array_key_exists($key, $project)) { return (string) ($project[$key] ?? ''); }
    return $default;
};
$err = static fn(string $k): ?string => $errors[$k] ?? null;
$cls = static fn(string $k): string => isset($errors[$k]) ? ' has-error' : '';
$action = $isEdit ? url('/admin/projects/' . $project['id']) : url('/admin/projects');
?>
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
    <?= \App\Core\Csrf::field() ?>

    <div class="grid-2">
        <!-- ============ Main column ============ -->
        <div style="display:grid;gap:1.5rem">
            <div class="panel">
                <div class="panel__head"><h2>Project details</h2></div>
                <div class="panel__body">
                    <div class="form-grid">
                        <div class="f<?= $cls('title') ?>">
                            <label for="title">Title <span class="hint">(required)</span></label>
                            <input type="text" id="title" name="title" value="<?= e($val('title')) ?>" required maxlength="190">
                            <?php if ($err('title')): ?><span class="err"><?= e($err('title')) ?></span><?php endif; ?>
                        </div>

                        <div class="f<?= $cls('slug') ?>">
                            <label for="slug">Slug <span class="hint">(leave blank to generate from the title)</span></label>
                            <input type="text" id="slug" name="slug" value="<?= e($val('slug')) ?>" maxlength="190" pattern="[a-z0-9-]*" placeholder="my-project-name">
                            <?php if ($err('slug')): ?><span class="err"><?= e($err('slug')) ?></span><?php endif; ?>
                        </div>

                        <div class="form-grid form-grid--2">
                            <div class="f<?= $cls('category') ?>">
                                <label for="category">Category <span class="hint">(required)</span></label>
                                <select id="category" name="category" required>
                                    <option value="">Select…</option>
                                    <?php foreach ($categories as $slug => $label): ?>
                                        <option value="<?= e($slug) ?>"<?= $val('category') === $slug ? ' selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($err('category')): ?><span class="err"><?= e($err('category')) ?></span><?php endif; ?>
                            </div>

                            <div class="f<?= $cls('year') ?>">
                                <label for="year">Year <span class="hint">(required)</span></label>
                                <input type="number" id="year" name="year" value="<?= e($val('year', date('Y'))) ?>" min="1900" max="<?= e((string) (date('Y') + 5)) ?>" required>
                                <?php if ($err('year')): ?><span class="err"><?= e($err('year')) ?></span><?php endif; ?>
                            </div>
                        </div>

                        <div class="f<?= $cls('description') ?>">
                            <label for="description">Short description <span class="hint">(required — shown on cards, max 500 chars)</span></label>
                            <textarea id="description" name="description" required maxlength="500" style="min-height:90px"><?= e($val('description')) ?></textarea>
                            <?php if ($err('description')): ?><span class="err"><?= e($err('description')) ?></span><?php endif; ?>
                        </div>

                        <div class="f<?= $cls('case_study') ?>">
                            <label for="case_study">Case study <span class="hint">(optional — supports ## headings, **bold**, - lists)</span></label>
                            <textarea id="case_study" name="case_study" style="min-height:260px" placeholder="## Brief&#10;&#10;What was the challenge?&#10;&#10;## Approach&#10;&#10;How did you solve it?"><?= e($val('case_study')) ?></textarea>
                            <?php if ($err('case_study')): ?><span class="err"><?= e($err('case_study')) ?></span><?php endif; ?>
                        </div>

                        <div class="f<?= $cls('software') ?>">
                            <label for="software">Software used <span class="hint">(comma separated)</span></label>
                            <input type="text" id="software" name="software" value="<?= e($val('software')) ?>" maxlength="255" placeholder="Blender, After Effects, Premiere Pro">
                            <?php if ($err('software')): ?><span class="err"><?= e($err('software')) ?></span><?php endif; ?>
                        </div>

                        <div class="f<?= $cls('video_url') ?>">
                            <label for="video_url">Video URL <span class="hint">(optional — YouTube or Vimeo)</span></label>
                            <input type="url" id="video_url" name="video_url" value="<?= e($val('video_url')) ?>" maxlength="500" placeholder="https://vimeo.com/123456789">
                            <?php if ($err('video_url')): ?><span class="err"><?= e($err('video_url')) ?></span><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ Gallery ============ -->
            <div class="panel">
                <div class="panel__head">
                    <h2>Gallery images</h2>
                    <span class="small muted"><?= e((string) count($images)) ?> image<?= count($images) === 1 ? '' : 's' ?></span>
                </div>
                <div class="panel__body">
                    <div class="f mb-3">
                        <label for="gallery">Add images <span class="hint">(JPEG, PNG, WebP or AVIF — select multiple)</span></label>
                        <input type="file" id="gallery" name="gallery[]" accept="image/jpeg,image/png,image/webp,image/avif" multiple>
                    </div>

                    <?php if ($images !== []): ?>
                        <div class="gallery-grid">
                            <?php foreach ($images as $img): ?>
                                <div class="gallery-item">
                                    <img src="<?= e(url('/media/' . ltrim((string) $img['path'], '/'))) ?>" alt="" loading="lazy">
                                    <?php if ($isEdit): ?>
                                        <button type="submit" title="Remove this image"
                                                formaction="<?= e(url('/admin/projects/' . $project['id'] . '/images/' . $img['id'] . '/delete')) ?>"
                                                formnovalidate
                                                onclick="return confirm('Remove this image?')"><?= icon('trash', 13) ?></button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="small muted mt-2">Save the project after adding new images.</p>
                    <?php else: ?>
                        <p class="small muted">No gallery images yet. The project thumbnail is used as the cover image.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ============ Sidebar column ============ -->
        <div style="display:grid;gap:1.5rem;position:sticky;top:5rem;align-self:start">
            <div class="panel">
                <div class="panel__head"><h2>Publish</h2></div>
                <div class="panel__body">
                    <div class="form-grid">
                        <div class="f f--check">
                            <input type="checkbox" id="published" name="published" value="1"<?= $val('published', $isEdit ? '' : '1') === '1' ? ' checked' : '' ?>>
                            <label for="published">Published <span class="hint">(visible on the site)</span></label>
                        </div>
                        <div class="f f--check">
                            <input type="checkbox" id="featured" name="featured" value="1"<?= $val('featured') === '1' ? ' checked' : '' ?>>
                            <label for="featured">Featured <span class="hint">(highlight on the homepage)</span></label>
                        </div>
                        <div class="f">
                            <label for="sort_order">Display order <span class="hint">(lower shows first)</span></label>
                            <input type="number" id="sort_order" name="sort_order" value="<?= e($val('sort_order', '0')) ?>">
                        </div>
                    </div>

                    <div class="form-actions mt-3">
                        <button type="submit" class="abtn abtn--primary"><?= $isEdit ? 'Save changes' : 'Create project' ?></button>
                        <a class="abtn" href="<?= e(url('/admin/projects')) ?>">Cancel</a>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel__head"><h2>Thumbnail</h2></div>
                <div class="panel__body">
                    <?php if ($isEdit && !empty($project['thumbnail'])): ?>
                        <img src="<?= e(url('/media/' . ltrim((string) $project['thumbnail'], '/'))) ?>"
                             alt="Current thumbnail" style="width:100%;aspect-ratio:16/10;object-fit:cover;border-radius:6px;border:1px solid var(--line);margin-bottom:1rem">
                    <?php endif; ?>
                    <div class="f<?= $cls('thumbnail') ?>">
                        <label for="thumbnail"><?= $isEdit && !empty($project['thumbnail']) ? 'Replace thumbnail' : 'Upload thumbnail' ?></label>
                        <input type="file" id="thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/webp,image/avif">
                        <span class="hint">Recommended 1600×1000. Max <?= e(\App\Services\Uploader::humanBytes((int) config('app.uploads.max_bytes'))) ?>.</span>
                        <?php if ($err('thumbnail')): ?><span class="err"><?= e($err('thumbnail')) ?></span><?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($isEdit): ?>
                <div class="panel">
                    <div class="panel__head"><h2>Danger zone</h2></div>
                    <div class="panel__body">
                        <p class="small muted mb-2">Deleting removes the project and all of its images permanently.</p>
                        <button type="button" class="abtn abtn--danger abtn--block" data-delete-project
                                data-id="<?= e((string) $project['id']) ?>"
                                data-title="<?= e($project['title']) ?>"
                                data-action="<?= e(url('/admin/projects/' . $project['id'] . '/delete')) ?>">
                            <?= icon('trash', 15) ?> Delete project
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- Standalone forms for image deletion (kept outside the main form) -->
<?php if ($isEdit && $images !== []): ?>
    <dialog id="deleteDialog" style="background:var(--panel);color:var(--text);border:1px solid var(--line-2);border-radius:8px;padding:0;max-width:440px;width:92%">
        <form method="post" id="deleteForm" style="padding:1.75rem">
            <?= \App\Core\Csrf::field() ?>
            <h3 style="font-size:1.1rem;margin-bottom:.75rem">Delete project?</h3>
            <p class="muted small" style="margin:0 0 1.5rem">
                Permanently delete <strong id="deleteTitle" style="color:var(--text)"></strong> and all its images? This cannot be undone.
            </p>
            <div class="form-actions">
                <button type="button" class="abtn" data-dialog-cancel>Cancel</button>
                <button type="submit" class="abtn abtn--danger">Delete permanently</button>
            </div>
        </form>
    </dialog>
<?php endif; ?>

<script>
(function () {
    // Auto-fill the slug from the title until the user edits it manually.
    var title = document.getElementById('title');
    var slug = document.getElementById('slug');
    if (title && slug) {
        var manual = slug.value !== '';
        slug.addEventListener('input', function () { manual = true; });
        title.addEventListener('input', function () {
            if (manual) return;
            slug.value = title.value.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        });
    }

    var dialog = document.getElementById('deleteDialog');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    var form = document.getElementById('deleteForm');
    var titleEl = document.getElementById('deleteTitle');
    document.querySelectorAll('[data-delete-project]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            form.action = btn.dataset.action;
            titleEl.textContent = btn.dataset.title;
            dialog.showModal();
        });
    });
    var cancel = dialog.querySelector('[data-dialog-cancel]');
    if (cancel) cancel.addEventListener('click', function () { dialog.close(); });
})();
</script>
