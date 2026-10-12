<?php
/** @var array $inquiry */
$inq = $inquiry;
?>
<div class="row row--between mb-3">
    <a class="abtn abtn--sm" href="<?= e(url('/admin/inquiries')) ?>">&larr; All inquiries</a>
    <span class="badge badge--<?= e($inq['status']) ?>"><?= e(\App\Models\Inquiry::statusLabel($inq['status'])) ?></span>
</div>

<div class="grid-2">
    <div style="display:grid;gap:1.5rem">
        <div class="panel">
            <div class="panel__head"><h2>Inquiry #<?= e((string) $inq['id']) ?></h2></div>
            <div class="panel__body">
                <dl class="dl">
                    <dt>Name</dt><dd><?= e($inq['name']) ?></dd>
                    <dt>Email</dt><dd><a href="mailto:<?= e($inq['email']) ?>"><?= e($inq['email']) ?></a></dd>
                    <?php if (!empty($inq['company'])): ?><dt>Company</dt><dd><?= e($inq['company']) ?></dd><?php endif; ?>
                    <dt>Service</dt><dd><?= e((string) $inq['service']) ?></dd>
                    <dt>Budget</dt><dd><?= e((string) ($inq['budget'] ?: '—')) ?></dd>
                    <dt>Timeline</dt><dd><?= e((string) ($inq['timeline'] ?: '—')) ?></dd>
                    <dt>Preferred contact</dt><dd><?= e((string) ($inq['contact_method'] ?: '—')) ?></dd>
                    <dt>Received</dt><dd><?= e(date('j F Y, H:i', strtotime((string) $inq['created_at']))) ?></dd>
                    <dt>IP address</dt><dd class="muted small"><?= e((string) ($inq['ip'] ?: '—')) ?></dd>
                </dl>
            </div>
        </div>

        <div class="panel">
            <div class="panel__head"><h2>Project description</h2></div>
            <div class="panel__body">
                <p style="white-space:pre-wrap;margin:0"><?= e((string) $inq['description']) ?></p>
            </div>
        </div>

        <div class="panel">
            <div class="panel__head"><h2>Internal notes</h2></div>
            <div class="panel__body">
                <form method="post" action="<?= e(url('/admin/inquiries/' . $inq['id'] . '/notes')) ?>">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="f">
                        <label for="internal_notes">Notes <span class="hint">(private — never shown to the visitor)</span></label>
                        <textarea id="internal_notes" name="internal_notes" maxlength="5000"
                                  placeholder="Follow-up reminders, quoted scope, call notes…"><?= e((string) ($inq['internal_notes'] ?? '')) ?></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="abtn abtn--primary">Save notes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div style="display:grid;gap:1.5rem;align-self:start;position:sticky;top:5rem">
        <div class="panel">
            <div class="panel__head"><h2>Status</h2></div>
            <div class="panel__body">
                <form method="post" action="<?= e(url('/admin/inquiries/' . $inq['id'] . '/status')) ?>" class="form-grid">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="f">
                        <label for="status">Set status</label>
                        <select id="status" name="status">
                            <?php foreach (\App\Models\Inquiry::STATUSES as $s): ?>
                                <option value="<?= e($s) ?>"<?= $inq['status'] === $s ? ' selected' : '' ?>>
                                    <?= e(\App\Models\Inquiry::statusLabel($s)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="abtn abtn--primary abtn--block">Update status</button>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="panel__head"><h2>Quick actions</h2></div>
            <div class="panel__body" style="display:grid;gap:.6rem">
                <a class="abtn abtn--block" href="mailto:<?= e($inq['email']) ?>?subject=<?= e(rawurlencode('Re: your project enquiry — ' . (\App\Models\Setting::value('company_name')))) ?>">
                    <?= icon('mail', 15) ?> Reply by email
                </a>
            </div>
        </div>

        <div class="panel">
            <div class="panel__head"><h2>Danger zone</h2></div>
            <div class="panel__body">
                <p class="small muted mb-2">Permanently remove this inquiry and its notes.</p>
                <form method="post" action="<?= e(url('/admin/inquiries/' . $inq['id'] . '/delete')) ?>"
                      onsubmit="return confirm('Delete inquiry #<?= e((string) $inq['id']) ?> from <?= e($inq['name']) ?>? This cannot be undone.')">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="abtn abtn--danger abtn--block"><?= icon('trash', 15) ?> Delete inquiry</button>
                </form>
            </div>
        </div>
    </div>
</div>
