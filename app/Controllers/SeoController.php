<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Project;

final class SeoController
{
    /** /sitemap.xml */
    public function sitemap(): void
    {
        $urls = [
            ['loc' => url('/'),         'priority' => '1.0', 'freq' => 'weekly'],
            ['loc' => url('/work'),     'priority' => '0.9', 'freq' => 'weekly'],
            ['loc' => url('/services'), 'priority' => '0.8', 'freq' => 'monthly'],
            ['loc' => url('/about'),    'priority' => '0.7', 'freq' => 'monthly'],
            ['loc' => url('/contact'),  'priority' => '0.8', 'freq' => 'monthly'],
            ['loc' => url('/privacy'),  'priority' => '0.3', 'freq' => 'yearly'],
            ['loc' => url('/terms'),    'priority' => '0.3', 'freq' => 'yearly'],
        ];

        foreach (Project::published() as $project) {
            $urls[] = [
                'loc'      => url('/project/' . $project['slug']),
                'priority' => '0.7',
                'freq'     => 'monthly',
                'lastmod'  => date('Y-m-d', strtotime((string) $project['updated_at'])),
            ];
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            echo "  <url>\n";
            echo '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
            echo '    <lastmod>' . ($u['lastmod'] ?? date('Y-m-d')) . "</lastmod>\n";
            echo '    <changefreq>' . $u['freq'] . "</changefreq>\n";
            echo '    <priority>' . $u['priority'] . "</priority>\n";
            echo "  </url>\n";
        }
        echo '</urlset>' . "\n";
        exit;
    }

    /** /robots.txt */
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: /admin\n";
        echo "Disallow: /admin/\n";
        echo "\n";
        echo 'Sitemap: ' . url('/sitemap.xml') . "\n";
        exit;
    }
}
