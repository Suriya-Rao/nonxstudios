<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\Setting;

final class SettingController extends AdminController
{
    /** Whitelist of editable keys — prevents arbitrary row creation. */
    private const EDITABLE = [
        'company_name', 'contact_email', 'hero_headline', 'hero_subtitle',
        'about_description', 'footer_description', 'cta_text', 'services_list',
        'social_instagram', 'social_behance', 'social_vimeo', 'social_youtube', 'social_linkedin',
    ];

    private const URL_KEYS = ['social_instagram', 'social_behance', 'social_vimeo', 'social_youtube', 'social_linkedin'];

    public function index(): void
    {
        $this->view('admin/settings', [
            'title'    => 'Site settings',
            'active'   => 'settings',
            'settings' => Setting::all(),
            'defaults' => Setting::defaults(),
            'errors'   => \App\Core\Session::pull('_errors', []),
        ]);
    }

    public function update(): void
    {
        $this->verifyPost();

        $errors = [];
        $values = [];

        foreach (self::EDITABLE as $key) {
            $values[$key] = Request::string($key);
        }

        // --- Validation ----------------------------------------------------
        if ($values['company_name'] === '') {
            $errors['company_name'] = 'Company name is required.';
        }
        if ($values['contact_email'] !== '' && !filter_var($values['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'] = 'Enter a valid email address.';
        }
        if ($values['cta_text'] === '') {
            $errors['cta_text'] = 'CTA text is required.';
        }

        foreach (self::URL_KEYS as $key) {
            $url = $values[$key];
            if ($url === '') {
                continue;
            }
            if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://')) {
                $errors[$key] = 'Enter a full https:// URL.';
            }
        }

        if ($errors !== []) {
            \App\Core\Session::put('_errors', $errors);
            $this->back('/admin/settings', 'error', 'Please correct the highlighted fields.');
        }

        Setting::setMany($values);
        $this->back('/admin/settings', 'success', 'Settings saved.');
    }
}
