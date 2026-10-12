<?php
/**
 * @var array $settings @var array $defaults @var array $errors
 */
$val = static function (string $key) use ($settings, $defaults): string {
    return (string) ($settings[$key] ?? $defaults[$key] ?? '');
};
$err = static fn(string $k): ?string => $errors[$k] ?? null;
$cls = static fn(string $k): string => isset($errors[$k]) ? ' has-error' : '';
?>
<form method="post" action="<?= e(url('/admin/settings')) ?>">
    <?= \App\Core\Csrf::field() ?>

    <div class="grid-2">
        <div style="display:grid;gap:1.5rem">
            <div class="panel">
                <div class="panel__head"><h2>Brand &amp; contact</h2></div>
                <div class="panel__body">
                    <div class="form-grid">
                        <div class="f<?= $cls('company_name') ?>">
                            <label for="company_name">Company name</label>
                            <input type="text" id="company_name" name="company_name" value="<?= e($val('company_name')) ?>" required maxlength="120">
                            <?php if ($err('company_name')): ?><span class="err"><?= e($err('company_name')) ?></span><?php endif; ?>
                        </div>

                        <div class="f<?= $cls('contact_email') ?>">
                            <label for="contact_email">Contact email <span class="hint">(shown in the footer and on the contact page)</span></label>
                            <input type="email" id="contact_email" name="contact_email" value="<?= e($val('contact_email')) ?>" maxlength="190">
                            <?php if ($err('contact_email')): ?><span class="err"><?= e($err('contact_email')) ?></span><?php endif; ?>
                        </div>

                        <div class="f">
                            <label for="footer_description">Footer description</label>
                            <textarea id="footer_description" name="footer_description" style="min-height:80px" maxlength="400"><?= e($val('footer_description')) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel__head"><h2>Homepage content</h2></div>
                <div class="panel__body">
                    <div class="form-grid">
                        <div class="f">
                            <label for="hero_headline">Hero headline <span class="hint">(one line per row)</span></label>
                            <textarea id="hero_headline" name="hero_headline" style="min-height:80px" maxlength="200"><?= e($val('hero_headline')) ?></textarea>
                        </div>
                        <div class="f">
                            <label for="hero_subtitle">Hero subtitle</label>
                            <textarea id="hero_subtitle" name="hero_subtitle" style="min-height:70px" maxlength="300"><?= e($val('hero_subtitle')) ?></textarea>
                        </div>
                        <div class="f">
                            <label for="about_description">About description</label>
                            <textarea id="about_description" name="about_description" style="min-height:130px" maxlength="800"><?= e($val('about_description')) ?></textarea>
                        </div>
                        <div class="f<?= $cls('cta_text') ?>">
                            <label for="cta_text">CTA button text</label>
                            <input type="text" id="cta_text" name="cta_text" value="<?= e($val('cta_text')) ?>" required maxlength="60">
                            <?php if ($err('cta_text')): ?><span class="err"><?= e($err('cta_text')) ?></span><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:grid;gap:1.5rem">
            <div class="panel">
                <div class="panel__head"><h2>Available services</h2></div>
                <div class="panel__body">
                    <div class="f">
                        <label for="services_list">Services <span class="hint">(one per line — shown in the footer and contact form)</span></label>
                        <textarea id="services_list" name="services_list" style="min-height:170px"><?= e($val('services_list')) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel__head"><h2>Social profiles</h2></div>
                <div class="panel__body">
                    <p class="small muted mb-3">Leave blank to hide a link. Only full <code>https://</code> URLs are accepted.</p>
                    <div class="form-grid">
                        <?php foreach ([
                            'social_instagram' => 'Instagram',
                            'social_behance'   => 'Behance',
                            'social_vimeo'     => 'Vimeo',
                            'social_youtube'   => 'YouTube',
                            'social_linkedin'  => 'LinkedIn',
                        ] as $key => $label): ?>
                            <div class="f<?= $cls($key) ?>">
                                <label for="<?= e($key) ?>"><?= e($label) ?></label>
                                <input type="url" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($val($key)) ?>" maxlength="300" placeholder="https://…">
                                <?php if ($err($key)): ?><span class="err"><?= e($err($key)) ?></span><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel__body">
                    <button type="submit" class="abtn abtn--primary abtn--block">Save settings</button>
                </div>
            </div>
        </div>
    </div>
</form>
