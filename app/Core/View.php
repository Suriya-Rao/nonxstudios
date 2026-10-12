<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Plain-PHP view renderer with a shared layout. Views receive `$data` extracted
 * into local variables and may call `$this->e()` helpers via the global `e()`.
 */
final class View
{
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /** Render a view inside a layout and return the HTML string. */
    public static function render(string $view, array $data = [], string $layout = 'layouts/main'): string
    {
        $content = self::renderPartial($view, $data);
        return self::renderPartial($layout, array_merge($data, ['content' => $content]));
    }

    /** Render a view without a layout (used for layouts themselves and partials). */
    public static function renderPartial(string $view, array $data = []): string
    {
        $file = base_path('app/Views/' . $view . '.php');
        if (!is_readable($file)) {
            throw new \RuntimeException("View not found: $view");
        }

        $data = array_merge(self::$shared, $data);
        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    public static function exists(string $view): bool
    {
        return is_readable(base_path('app/Views/' . $view . '.php'));
    }
}
