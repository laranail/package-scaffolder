<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Processing\Stages;

use Closure;

/**
 * Default body stage: strips post bodies to an allow-list of HTML tags (a
 * dependency-free XSS baseline). Self-gated on config('modules.blog.security.sanitize_html'),
 * so disabling that makes the stage a no-op. Pair with a full purifier for rich input.
 */
class SanitizeHtmlStage
{
    public function handle(string $body, Closure $next): string
    {
        if (config('modules.blog.security.sanitize_html', true)) {
            $allowed = (string) config(
                'modules.blog.security.allowed_tags',
                '<p><br><a><strong><em><ul><ol><li><blockquote><h2><h3><h4><code><pre><img>',
            );

            $body = strip_tags($body, $allowed);
            $body = $this->stripDangerousAttributes($body);
        }

        return $next($body);
    }

    /**
     * strip_tags removes disallowed *tags* but keeps every attribute on the
     * allowed ones — so `<a>`/`<img>` could still carry `onerror=` handlers or
     * `javascript:` URLs. Re-parse each tag's attributes (so a value containing
     * "/online=" is never mistaken for an attribute), drop inline event handlers,
     * and blank dangerous URL schemes. (Still a baseline — use a full HTML purifier
     * as a stage for untrusted rich input.)
     */
    private function stripDangerousAttributes(string $html): string
    {
        // `\b[^>]*` captures attributes even when slash-separated from the tag name
        // (`<a/onclick=…>`), which is a real bypass of a whitespace-only filter.
        return preg_replace_callback('/<([a-z][a-z0-9]*)\b([^>]*)>/i', function (array $match): string {
            return '<'.$match[1].$this->cleanAttributes($match[2]).'>';
        }, $html) ?? $html;
    }

    /**
     * Tokenise a tag's attribute string into name[=value] pairs, drop any
     * event-handler (`on*`) attribute, and blank dangerous-scheme URL values.
     * Tokenising by attribute *name* is what prevents a URL value such as
     * `href="…/online=2"` from being misread as an `online` attribute.
     */
    private function cleanAttributes(string $attrs): string
    {
        preg_match_all(
            '/([a-z_:][a-z0-9_:.-]*)(?:\s*=\s*("[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+))?/i',
            $attrs,
            $matches,
            PREG_SET_ORDER,
        );

        $clean = '';

        foreach ($matches as $attr) {
            $name = strtolower($attr[1]);
            $value = $attr[2] ?? null;

            // Drop inline event handlers (onclick, onerror, onmouseover, …) entirely.
            if (str_starts_with($name, 'on')) {
                continue;
            }

            if ($value !== null && in_array($name, ['href', 'src', 'srcset'], true) && $this->hasDangerousScheme($value)) {
                $quote = ($value[0] === '"' || $value[0] === "'") ? $value[0] : '"';
                $clean .= ' '.$name.'='.$quote.$quote; // keep the attr, blank the value

                continue;
            }

            $clean .= ' '.$attr[0];
        }

        // Preserve a trailing self-closing slash if the original tag had one.
        if (preg_match('/\/\s*$/', $attrs) === 1) {
            $clean .= ' /';
        }

        return $clean;
    }

    /**
     * Whether a URL attribute value carries a dangerous scheme once it is
     * HTML-entity-decoded and stripped of whitespace/control chars (which browsers
     * ignore) — catching obfuscations like `java&#115;cript:` and `java&Tab;script:`.
     */
    private function hasDangerousScheme(string $value): bool
    {
        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            $value = substr($value, 1, -1);
        }

        $normalised = preg_replace(
            '/[\s\x00-\x1F]+/',
            '',
            html_entity_decode($value, ENT_QUOTES | ENT_HTML5),
        ) ?? '';

        return preg_match('/^(?:javascript|vbscript|data):/i', $normalised) === 1;
    }
}
