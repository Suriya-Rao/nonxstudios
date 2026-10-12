<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Analytics integration — configuration and provider metadata.
 *
 * No provider is enabled by default, and no ID is invented: with
 * ANALYTICS_PROVIDER blank the site emits no analytics markup at all and the
 * Content-Security-Policy stays as tight as it was.
 *
 * This class is the single source of truth for three consumers, so the
 * provider's origins can never drift out of sync with the CSP that has to
 * allow them:
 *
 *   - app/Views/components/analytics.php   the loader tag
 *   - app/Core/Response.php                script-src / connect-src entries
 *   - app/Views/pages/privacy.php          the cookie/tracking disclosure
 *
 * Adding a provider means adding one entry to PROVIDERS and nothing else.
 */
final class Analytics
{
    /**
     * Providers we know how to load.
     *
     *   script        the vendor's loader
     *   script_origin origin to allow in CSP script-src
     *   connect       origins to allow in CSP connect-src (the beacon target)
     *   consent       whether consent is required before loading by default
     *   host_override whether ANALYTICS_HOST may replace the script host
     *   cookie        whether the provider sets cookies (drives the disclosure)
     *   note          shown in the privacy policy
     *   docs          link to the provider's privacy information
     */
    private const PROVIDERS = [
        'plausible' => [
            'label'         => 'Plausible',
            'script'        => 'https://plausible.io/js/script.js',
            'script_origin' => 'https://plausible.io',
            'connect'       => ['https://plausible.io'],
            'consent'       => false,
            'host_override' => true,
            'cookie'        => false,
            'note'          => 'Plausible measures page views without cookies and without collecting personal data.',
            'docs'          => 'https://plausible.io/data-policy',
        ],
        'fathom' => [
            'label'         => 'Fathom',
            'script'        => 'https://cdn.usefathom.com/script.js',
            'script_origin' => 'https://cdn.usefathom.com',
            'connect'       => ['https://cdn.usefathom.com'],
            'consent'       => false,
            'host_override' => false,
            'cookie'        => false,
            'note'          => 'Fathom measures page views without cookies and without collecting personal data.',
            'docs'          => 'https://usefathom.com/privacy',
        ],
        'umami' => [
            'label'         => 'Umami',
            'script'        => '/script.js',
            'script_origin' => '',
            'connect'       => [],
            'consent'       => false,
            'host_override' => true,
            'cookie'        => false,
            'note'          => 'Umami is self-hosted and measures page views without cookies.',
            'docs'          => 'https://umami.is/docs',
        ],
        'ga4' => [
            'label'         => 'Google Analytics 4',
            'script'        => 'https://www.googletagmanager.com/gtag/js',
            'script_origin' => 'https://www.googletagmanager.com',
            'connect'       => [
                'https://www.google-analytics.com',
                'https://analytics.google.com',
                'https://www.googletagmanager.com',
            ],
            'consent'       => true,
            'host_override' => false,
            'cookie'        => true,
            'note'          => 'Google Analytics sets cookies and processes usage data. It loads only after you accept.',
            'docs'          => 'https://policies.google.com/privacy',
        ],
    ];

    /** The configured provider key, or '' when analytics is off. */
    public static function provider(): string
    {
        $key = strtolower(trim((string) config('app.analytics.provider', '')));

        // An unknown provider is a configuration mistake, not a reason to
        // emit a half-built tag. Treat it as off and say so in the log.
        if ($key !== '' && !isset(self::PROVIDERS[$key])) {
            \App\Core\Logger::warning('Unknown ANALYTICS_PROVIDER; analytics disabled', ['provider' => $key]);
            return '';
        }

        return $key;
    }

    /**
     * True only when a known provider is selected AND an ID is present.
     * Everything downstream keys off this, so a half-configured provider
     * degrades to "off" rather than loading a script with an empty ID.
     */
    public static function enabled(): bool
    {
        return self::provider() !== '' && self::id() !== '';
    }

    public static function id(): string
    {
        return trim((string) config('app.analytics.id', ''));
    }

    /** Self-hosted base URL, e.g. https://stats.example.com (no trailing slash). */
    public static function host(): string
    {
        return rtrim(trim((string) config('app.analytics.host', '')), '/');
    }

    /** @return array<string,mixed>|null */
    private static function definition(): ?array
    {
        $key = self::provider();
        return $key === '' ? null : self::PROVIDERS[$key];
    }

    public static function label(): string
    {
        return (string) (self::definition()['label'] ?? '');
    }

    /** The vendor script URL, honouring ANALYTICS_HOST where the provider allows it. */
    public static function scriptUrl(): string
    {
        $def = self::definition();
        if ($def === null) {
            return '';
        }
        if ($def['host_override'] && self::host() !== '') {
            return self::host() . ($def['script'] === '/script.js' ? '/script.js' : '/js/script.js');
        }
        return (string) $def['script'];
    }

    /**
     * Origins the CSP must permit for this provider: script-src entries and
     * connect-src entries. Empty when analytics is off, so the policy is
     * byte-for-byte what it was before.
     *
     * @return array{script:list<string>,connect:list<string>}
     */
    public static function cspOrigins(): array
    {
        $def = self::definition();
        if ($def === null || !self::enabled()) {
            return ['script' => [], 'connect' => []];
        }

        $script = [];
        $connect = (array) $def['connect'];

        // Self-hosted: the loader and the beacon both come from ANALYTICS_HOST,
        // so that host must be allowed in script-src and connect-src. It is not
        // covered by 'self' — it is a different origin.
        if ($def['host_override'] && self::host() !== '') {
            $host = self::host();
            $script[] = $host;
            $connect = [$host];
        } elseif ($def['script_origin'] !== '') {
            $script[] = (string) $def['script_origin'];
        }

        return [
            'script'  => array_values(array_unique(array_filter($script))),
            'connect' => array_values(array_unique(array_filter($connect))),
        ];
    }

    /**
     * Whether the visitor must opt in before the provider loads.
     *
     * Defaults per provider — true for GA4, false for the cookieless ones —
     * but ANALYTICS_REQUIRE_CONSENT overrides in either direction, because
     * whether consent is legally required depends on jurisdiction, not on
     * which tool you picked.
     */
    public static function requiresConsent(): bool
    {
        $def = self::definition();
        if ($def === null) {
            return false;
        }
        $configured = config('app.analytics.require_consent', null);
        if ($configured === null || $configured === '') {
            return (bool) $def['consent'];
        }
        return filter_var($configured, FILTER_VALIDATE_BOOL);
    }

    /** Whether the provider sets cookies — drives the privacy-policy wording. */
    public static function usesCookies(): bool
    {
        return (bool) (self::definition()['cookie'] ?? false);
    }

    public static function note(): string
    {
        return (string) (self::definition()['note'] ?? '');
    }

    public static function docsUrl(): string
    {
        return (string) (self::definition()['docs'] ?? '');
    }
}
