<?php
/** @var array $result @var string $status @var string $service @var string $search @var array $services @var array $counts */
$items = $result['items'];

// Build a query string that preserves the current filters.
$qs = static function (array $overrides = []) use ($status, $service, $search): string {
    $params = array_filter([
        'status'  => $status,
        'service' => $service,
        'q'       => $search,
    ], static fn($v) => $v !== '' && $v !== null);
    return http_build_query(array_merge($params, $overrides));
};
?>
<div class="toolbar">
    <div class="row" role="group" aria-label="Filter by status">
        <?php
        $tabs = [
            ''            => ['All', $counts['all']],
            'new'         => ['New', $counts['new']],
            'in_progress' => ['In Progress', $counts['in_progress']],
            'completed'   => ['Completed', $counts['completed']],
            'archived'    => ['Archived', $counts['archived']],
        ];
        foreach ($tabs as $key => [$label, $count]):
            $isActive = $status === $key;
            $query = $qs(['status' => $key, 'page' => 1]); ?>
            <a class="abtn abtn--sm<?= $isActive ? ' abtn--primary' : '' ?>"
               href="<?= e(url('/admin/inquiries' . ($query !== '' ? '?' . $query : ''))) ?>">
                <?= e($label) ?> <span style="opacity:.6"><?= e((string) $count) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="panel">
    <div class="panel__head">
        <h2>Inquiries <span class="muted small">(<?= e((string) $result['total']) ?>)</span></h2>
        <form method="get" action="<?= e(url('/admin/inquiries')) ?>" class="row">
            <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
            <select name="service" aria-label="Filter by service">
                <option value="">All services</option>
                <?php foreach ($services as $s): ?>
                    <option value="<?= e($s) ?>"<?= $service === $s ? ' selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search name, email or company…" aria-label="Search inquiries">
            <button type="submit" class="abtn abtn--sm"><?= icon('search', 15) ?> Search</button>
            <?php if ($search !== '' || $service !== '' || $status !== ''): ?>
                <a class="abtn abtn--sm" href="<?= e(url('/admin/inquiries')) ?>">Clear all</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrap">
        <?php if ($items === []): ?>
            <div class="empty-state">
                <?= icon('inbox', 40) ?>
                <h3>No inquiries found</h3>
                <p>
                    <?= ($search !== '' || $service !== '' || $status !== '')
                        ? 'No inquiries match the current filters. Try clearing them.'
                        : 'Messages submitted through the contact form will appear here.' ?>
                </p>
                <?php if ($search !== '' || $service !== '' || $status !== ''): ?>
                    <a class="abtn" href="<?= e(url('/admin/inquiries')) ?>">Clear filters</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="data">
                <thead>
                    <tr>
                        <th>From</th>
                        <th>Service</th>
                        <th>Budget</th>
                        <th>Status</th>
                        <th>Received</th>
                        <th style="width:120px"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $inq): ?>
                    <tr>
                        <td>
                            <a class="cell-title" href="<?= e(url('/admin/inquiries/' . $inq['id'])) ?>"><?= e($inq['name']) ?></a>
                            <div class="cell-sub"><?= e($inq['email']) ?><?= !empty($inq['company']) ? ' · ' . e($inq['company']) : '' ?></div>
                        </td>
                        <td class="small"><?= e((string) $inq['service']) ?></td>
                        <td class="small muted"><?= e((string) ($inq['budget'] ?: '—')) ?></td>
                        <td><span class="badge badge--<?= e($inq['status']) ?>"><?= e(\App\Models\Inquiry::statusLabel($inq['status'])) ?></span></td>
                        <td class="small muted"><?= e(date('j M Y', strtotime((string) $inq['created_at']))) ?><br><?= e(date('H:i', strtotime((string) $inq['created_at']))) ?></td>
                        <td>
                            <div class="actions">
                                <a class="abtn abtn--sm" href="<?= e(url('/admin/inquiries/' . $inq['id'])) ?>"><?= icon('eye', 14) ?> Open</a>
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
        $mk = static function (int $p, string $label, bool $disabled = false, bool $current = false) use ($qs) {
            $cls = $current ? 'is-current' : ($disabled ? 'is-disabled' : '');
            if ($disabled || $current) {
                return '<span class="' . $cls . '">' . e($label) . '</span>';
            }
            return '<a class="' . $cls . '" href="' . e(url('/admin/inquiries?' . $qs(['page' => $p]))) . '">' . e($label) . '</a>';
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
