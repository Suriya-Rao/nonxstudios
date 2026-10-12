<?php
/**
 * Contact page.
 * @var array $services @var array $budgets @var array $timelines @var array $methods
 * @var array $old @var array $errors @var array $settings
 */
$old    = $old ?? [];
$errors = $errors ?? [];
$val    = static fn(string $k, string $d = ''): string => (string) ($old[$k] ?? $d);
$err    = static fn(string $k): ?string => $errors[$k] ?? null;
$cls    = static fn(string $k): string => isset($errors[$k]) ? ' has-error' : '';
?>
<?php require base_path('app/Views/components/flashes.php'); ?>

<header class="page-head">
    <div class="container">
        <p class="eyebrow">Contact</p>
        <h1>Have an idea?<br>Let's make it real.</h1>
        <p>Tell us what you're imagining. We'll help bring it to life.</p>
    </div>
</header>

<section class="section">
    <div class="container">
        <div class="contact-grid">
            <div>
                <form class="form" method="post" action="<?= e(url('/contact')) ?>" novalidate>
                    <?= \App\Core\Csrf::field() ?>

                    <?php if ($errors !== []): ?>
                        <div class="alert alert--error" role="alert">
                            <?= icon('alert', 18) ?>
                            <div>
                                <strong>Please fix the following:</strong>
                                <ul>
                                    <?php foreach ($errors as $message): ?>
                                        <li><?= e($message) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Honeypot: hidden from humans, tempting to bots. -->
                    <div class="hp-field" aria-hidden="true">
                        <label for="website">Website</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="form-row">
                        <div class="field<?= $cls('name') ?>">
                            <label for="name">Name <span class="req" aria-hidden="true">*</span></label>
                            <input type="text" id="name" name="name" value="<?= e($val('name')) ?>"
                                   required maxlength="120" autocomplete="name"<?= $err('name') ? ' aria-invalid="true" aria-describedby="name-error"' : '' ?>>
                            <?php if ($err('name')): ?><span class="field__error" id="name-error"><?= e($err('name')) ?></span><?php endif; ?>
                        </div>

                        <div class="field<?= $cls('email') ?>">
                            <label for="email">Email <span class="req" aria-hidden="true">*</span></label>
                            <input type="email" id="email" name="email" value="<?= e($val('email')) ?>"
                                   required maxlength="190" autocomplete="email" inputmode="email"<?= $err('email') ? ' aria-invalid="true" aria-describedby="email-error"' : '' ?>>
                            <?php if ($err('email')): ?><span class="field__error" id="email-error"><?= e($err('email')) ?></span><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="field<?= $cls('company') ?>">
                            <label for="company">Company / Brand <span class="field__hint">(optional)</span></label>
                            <input type="text" id="company" name="company" value="<?= e($val('company')) ?>" maxlength="190" autocomplete="organization">
                        </div>

                        <div class="field<?= $cls('service') ?>">
                            <label for="service">Service required <span class="req" aria-hidden="true">*</span></label>
                            <select id="service" name="service" required<?= $err('service') ? ' aria-invalid="true"' : '' ?>>
                                <option value="">Select a service…</option>
                                <?php foreach ($services as $s): ?>
                                    <option value="<?= e($s) ?>"<?= $val('service') === $s ? ' selected' : '' ?>><?= e($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($err('service')): ?><span class="field__error"><?= e($err('service')) ?></span><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label for="budget">Estimated budget <span class="field__hint">(optional)</span></label>
                            <select id="budget" name="budget">
                                <option value="">Prefer not to say</option>
                                <?php foreach ($budgets as $b): ?>
                                    <option value="<?= e($b) ?>"<?= $val('budget') === $b ? ' selected' : '' ?>><?= e($b) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="timeline">Project timeline <span class="field__hint">(optional)</span></label>
                            <select id="timeline" name="timeline">
                                <option value="">Not sure yet</option>
                                <?php foreach ($timelines as $t): ?>
                                    <option value="<?= e($t) ?>"<?= $val('timeline') === $t ? ' selected' : '' ?>><?= e($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="field<?= $cls('description') ?>">
                        <label for="description">Project description <span class="req" aria-hidden="true">*</span></label>
                        <textarea id="description" name="description" required minlength="20" maxlength="5000"
                                  placeholder="What are you building, who is it for, and what does success look like?"
                                  <?= $err('description') ? ' aria-invalid="true" aria-describedby="description-error"' : '' ?>><?= e($val('description')) ?></textarea>
                        <?php if ($err('description')): ?>
                            <span class="field__error" id="description-error"><?= e($err('description')) ?></span>
                        <?php else: ?>
                            <span class="field__hint">At least 20 characters.</span>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="contact_method">Preferred contact method <span class="field__hint">(optional)</span></label>
                        <select id="contact_method" name="contact_method">
                            <option value="">Any</option>
                            <?php foreach ($methods as $m): ?>
                                <option value="<?= e($m) ?>"<?= $val('contact_method') === $m ? ' selected' : '' ?>><?= e($m) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="btn-row">
                        <button type="submit" class="btn btn--accent btn--lg">
                            Send inquiry <?= icon('arrow', 18) ?>
                        </button>
                    </div>
                    <p class="field__hint">We typically reply within one to two business days.</p>
                </form>
            </div>

            <aside class="contact-aside">
                <div>
                    <h3>Email</h3>
                    <?php if (!empty($settings['contact_email'])): ?>
                        <a href="mailto:<?= e($settings['contact_email']) ?>"><?= e($settings['contact_email']) ?></a>
                    <?php else: ?>
                        <p class="field__hint">Add a contact email in the studio settings.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <h3>Services</h3>
                    <ul class="footer__list">
                        <?php foreach ($services as $s): ?>
                            <li><span style="color:var(--muted);font-size:var(--fs-sm)"><?= e($s) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <?php
                $socials = array_filter([
                    'Instagram' => $settings['social_instagram'] ?? '',
                    'Behance'   => $settings['social_behance'] ?? '',
                    'Vimeo'     => $settings['social_vimeo'] ?? '',
                    'YouTube'   => $settings['social_youtube'] ?? '',
                    'LinkedIn'  => $settings['social_linkedin'] ?? '',
                ]);
                if ($socials !== []): ?>
                    <div>
                        <h3>Elsewhere</h3>
                        <ul class="footer__list">
                            <?php foreach ($socials as $label => $href): ?>
                                <li><a href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</section>
