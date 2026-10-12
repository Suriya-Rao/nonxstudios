<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Setting;

abstract class BaseController
{
    /** Render a page inside the public layout and echo it. */
    protected function view(string $view, array $data = []): void
    {
        $data['settings'] = $data['settings'] ?? Setting::all();
        $data['nav']      = $data['nav'] ?? $this->nav();

        echo View::render($view, $data, 'layouts/main');
    }

    protected function nav(): array
    {
        return [
            ['label' => 'Home',     'href' => '/'],
            ['label' => 'Work',     'href' => '/work'],
            ['label' => 'Services', 'href' => '/services'],
            ['label' => 'About',    'href' => '/about'],
            ['label' => 'Contact',  'href' => '/contact'],
        ];
    }

    /** Standard SEO payload for a page. */
    protected function meta(string $title, string $description, ?string $canonical = null, ?string $ogImage = null): array
    {
        return [
            'title'           => $title,
            'metaDescription' => $description,
            'canonical'       => $canonical,
            'ogImage'         => $ogImage,
        ];
    }
}
