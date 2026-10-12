<?php
/** @var array $admin @var int $projectTotal ... */
?>
<div class="stats">
    <div class="stat">
        <div class="stat__label">Projects</div>
        <div class="stat__value"><?= e((string) $projectTotal) ?></div>
        <div class="small muted"><?= e((string) $projectPublished) ?> published</div>
    </div>
    <div class="stat">
        <div class="stat__label">Total inquiries</div>
        <div class="stat__value"><?= e((string) $inquiryTotal) ?></div>
        <div class="small muted">All time</div>
    </div>
    <div class="stat">
        <div class="stat__label">New inquiries</div>
        <div class="stat__value stat__value--accent"><?= e((string) $inquiryNew) ?></div>
        <div class="small muted">Awaiting review</div>
    </div>
    <div class="stat">
        <div class="stat__label">In progress</div>
        <div class="stat__value"><?= e((string) $inquiryProgress) ?></div>
        <div class="small muted"><?= e((string) $inquiryDone) ?> completed</div>
    </div>
</div>

<?php if (!$mailEnabled): ?>
    <div class="alert alert--info">
        <?= icon('info', 18) ?>
        <div>
            <strong>Email notifications are off.</strong>
            Inquiries are still saved to the database. To receive an email for each new inquiry,
            set <code>MAIL_ENABLED=true</code> and your SMTP details in <code>.env</code>.
        </div>
    </div>
<?php endif; ?>

<div class="grid-2">
    <div class="panel">
        <div class="panel__head">
            <h2>Recent inquiries</h2>
            <a class="abtn abtn--sm" href="<?= e(url('/admin/inquiries')) ?>">View all</a>
        </div>
        <div class="table-wrap">
            <?php if ($recentInquiries === []): ?>
                <div class="empty-state">
                    <?= icon('inbox', 36) ?>
                    <h3>No inquiries yet</h3>
                    <p>Messages submitted through the contact form will appear here.</p>
                </div>
            <?php else: ?>
                <table class="data">
                    <thead><tr><th>From</th><th>Service</th><th>Status</th><th>Received</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentInquiries as $inq): ?>
                        <tr>
                            <td>
                                <a class="cell-title" href="<?= e(url('/admin/inquiries/' . $inq['id'])) ?>"><?= e($inq['name']) ?></a>
                                <div class="cell-sub"><?= e($inq['email']) ?></div>
                            </td>
                            <td><?= e((string) $inq['service']) ?></td>
                            <td><span class="badge badge--<?= e($inq['status']) ?>"><?= e(\App\Models\Inquiry::statusLabel($inq['status'])) ?></span></td>
                            <td class="small muted"><?= e(date('j M Y', strtotime((string) $inq['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <div class="panel__head">
            <h2>Projects</h2>
            <a class="abtn abtn--sm" href="<?= e(url('/admin/projects')) ?>">Manage</a>
        </div>
        <div class="table-wrap">
            <?php if ($recentProjects === []): ?>
                <div class="empty-state">
                    <?= icon('cube', 36) ?>
                    <h3>No projects yet</h3>
                    <p>Create your first project to populate the portfolio.</p>
                    <a class="abtn abtn--primary" href="<?= e(url('/admin/projects/create')) ?>">New project</a>
                </div>
            <?php else: ?>
                <table class="data">
                    <thead><tr><th>Title</th><th>Status</th><th>Year</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentProjects as $p): ?>
                        <tr>
                            <td>
                                <a class="cell-title" href="<?= e(url('/admin/projects/' . $p['id'] . '/edit')) ?>"><?= e($p['title']) ?></a>
                                <div class="cell-sub"><?= e(\App\Models\Project::categoryLabel($p['category'])) ?></div>
                            </td>
                            <td>
                                <?php if ((int) $p['published'] === 1): ?>
                                    <span class="badge badge--published">Published</span>
                                <?php else: ?>
                                    <span class="badge badge--draft">Draft</span>
                                <?php endif; ?>
                                <?php if ((int) $p['featured'] === 1): ?>
                                    <span class="badge badge--featured">Featured</span>
                                <?php endif; ?>
                            </td>
                            <td class="small muted"><?= e((string) $p['year']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
