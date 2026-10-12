<?php

declare(strict_types=1);

/**
 * Minimal, safe Markdown subset renderer for case-study content.
 *
 * Security: all text is escaped FIRST, then a small set of formatting rules is
 * applied. No raw HTML from the database is ever emitted, so admin-authored
 * content cannot introduce stored XSS.
 *
 * Supported: ## headings, **bold**, *italic*, - lists, blank-line paragraphs.
 */
function render_case_study(string $markdown): string
{
    $markdown = str_replace(["\r\n", "\r"], "\n", trim($markdown));
    if ($markdown === '') {
        return '';
    }

    $html    = '';
    $inList  = false;

    foreach (explode("\n", $markdown) as $line) {
        $escaped = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Inline formatting on already-escaped text.
        $escaped = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $escaped) ?? $escaped;

        // Headings.
        if (preg_match('/^(#{2,3})\s+(.*)$/', $escaped, $m)) {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
            $level = strlen($m[1]) === 2 ? 'h2' : 'h3';
            $html .= "<$level>" . trim($m[2]) . "</$level>\n";
            continue;
        }

        // List items.
        if (preg_match('/^[-*]\s+(.*)$/', $escaped, $m)) {
            if (!$inList) { $html .= "<ul>\n"; $inList = true; }
            $html .= '<li>' . trim($m[1]) . "</li>\n";
            continue;
        }

        if ($inList) { $html .= "</ul>\n"; $inList = false; }

        // Blank line.
        if (trim($escaped) === '') {
            continue;
        }

        // Paragraph.
        $html .= '<p>' . $escaped . "</p>\n";
    }

    if ($inList) {
        $html .= "</ul>\n";
    }

    return $html;
}
