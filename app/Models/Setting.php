<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Key/value site settings, cached for the lifetime of the request.
 */
final class Setting
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $rows = Database::select('SELECT setting_key, setting_value FROM settings');
        $out  = [];
        foreach ($rows as $row) {
            $out[$row['setting_key']] = $row['setting_value'];
        }
        self::$cache = $out;
        return $out;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        Database::execute(
            'INSERT INTO settings (setting_key, setting_value, updated_at)
             VALUES (:k, :v, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()',
            ['k' => $key, 'v' => $value]
        );
        self::$cache = null;
    }

    /** @param array<string,?string> $pairs */
    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            self::set($key, $value);
        }
    }

    /** Defaults used when the settings table is empty (fresh install). */
    public static function defaults(): array
    {
        return [
            'company_name'      => 'NONXSTUDIOS',
            'contact_email'     => 'studio@nonxstudios.example',
            'hero_headline'     => "WE CREATE\nWHAT DOESN'T EXIST.",
            'hero_subtitle'     => 'An independent creative studio crafting motion, visuals, and immersive 3D experiences.',
            'about_description' => 'NONXSTUDIOS is an independent creative studio working across design, motion, video, and real-time 3D production. We combine creative direction with technical execution to turn ideas into visual experiences.',
            'footer_description'=> 'An independent creative studio crafting motion, visuals, and immersive 3D experiences.',
            'cta_text'          => 'Start Your Project',
            'social_instagram'  => '',
            'social_behance'    => '',
            'social_vimeo'      => '',
            'social_youtube'    => '',
            'social_linkedin'   => '',
            'services_list'     => "Motion Graphics\nGraphic Design\nVideo Editing\n3D Design & CGI\nUnreal Engine\nCreative Direction",
        ];
    }

    public static function value(string $key): string
    {
        $value = self::get($key);
        if ($value === null || $value === '') {
            return (string) (self::defaults()[$key] ?? '');
        }
        return $value;
    }
}
