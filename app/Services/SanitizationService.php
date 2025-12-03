<?php

namespace App\Services;

class SanitizationService
{
    /**
     * Allowed HTML tags for rich text content (questions, options).
     * These tags are safe and commonly needed for exam questions.
     */
    protected static array $allowedTags = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike',
        'ul', 'ol', 'li',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'blockquote', 'pre', 'code',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'img', 'a',
        'sub', 'sup',
        'hr', 'span', 'div',
    ];

    /**
     * Allowed attributes for specific tags.
     */
    protected static array $allowedAttributes = [
        'a' => ['href', 'title', 'target'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'table' => ['border', 'cellpadding', 'cellspacing'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
        'span' => ['style'],
        'div' => ['style'],
        'p' => ['style'],
    ];

    /**
     * Dangerous patterns to remove.
     */
    protected static array $dangerousPatterns = [
        // JavaScript events
        '/\bon\w+\s*=/i',
        // JavaScript protocol
        '/javascript\s*:/i',
        // Data protocol (can contain scripts)
        '/data\s*:[^,]*base64/i',
        // VBScript
        '/vbscript\s*:/i',
        // Expression (IE)
        '/expression\s*\(/i',
        // Script tags
        '/<script[^>]*>.*?<\/script>/is',
        // Style tags with expressions
        '/<style[^>]*>.*?<\/style>/is',
        // iframe, object, embed, applet (with closing tags)
        '/<(iframe|object|applet)[^>]*>.*?<\/\1>/is',
        // Self-closing dangerous tags
        '/<(iframe|object|embed|applet)[^>]*\/?>/i',
        // Meta refresh
        '/<meta[^>]*http-equiv[^>]*refresh/i',
        // SVG with scripts
        '/<svg[^>]*>.*?<\/svg>/is',
    ];

    /**
     * Sanitize a string - strip all HTML tags.
     * Use for plain text fields like names, titles.
     */
    public static function clean(?string $input): string
    {
        if ($input === null) {
            return '';
        }

        // Remove null bytes
        $input = str_replace("\0", '', $input);

        // Strip all HTML tags
        $input = strip_tags($input);

        // Convert special characters to HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Trim whitespace
        return trim($input);
    }

    /**
     * Sanitize rich text content - allow safe HTML tags.
     * Use for questions, options, descriptions that may contain formatting.
     */
    public static function cleanRichText(?string $input): string
    {
        if ($input === null) {
            return '';
        }

        // Remove null bytes
        $input = str_replace("\0", '', $input);

        // Handle nested <pre><code> first - preserve the structure
        $input = preg_replace_callback(
            '/<pre([^>]*)>\s*<code([^>]*)>(.*?)<\/code>\s*<\/pre>/is',
            function ($matches) {
                $preAttrs = $matches[1];
                $codeAttrs = $matches[2];
                $content = htmlspecialchars($matches[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return "<pre{$preAttrs}><code{$codeAttrs}>{$content}</code></pre>";
            },
            $input
        );

        // Then handle standalone <code> and <pre> tags
        $input = preg_replace_callback(
            '/<(code|pre)([^>]*)>([^<]*)<\/\1>/is',
            function ($matches) {
                $tag = $matches[1];
                $attrs = $matches[2];
                $content = htmlspecialchars($matches[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return "<{$tag}{$attrs}>{$content}</{$tag}>";
            },
            $input
        );

        // Remove dangerous patterns
        foreach (self::$dangerousPatterns as $pattern) {
            $input = preg_replace($pattern, '', $input);
        }

        // Don't use strip_tags - it removes unknown tags completely
        // Instead, just remove dangerous tags and keep everything else
        
        // Clean attributes on allowed tags
        $input = self::cleanAttributes($input);

        return trim($input);
    }

    /**
     * Clean HTML attributes, keeping only allowed ones.
     */
    protected static function cleanAttributes(string $html): string
    {
        // Match all tags with attributes
        return preg_replace_callback(
            '/<(\w+)([^>]*)>/i',
            function ($matches) {
                $tag = strtolower($matches[1]);
                $attributes = $matches[2];

                // If tag has no allowed attributes, remove all attributes
                if (!isset(self::$allowedAttributes[$tag])) {
                    return "<{$tag}>";
                }

                // Parse and filter attributes
                $allowedAttrs = self::$allowedAttributes[$tag];
                $cleanAttrs = [];

                // Match attribute="value" patterns
                preg_match_all('/(\w+)\s*=\s*["\']([^"\']*)["\']/', $attributes, $attrMatches, PREG_SET_ORDER);

                foreach ($attrMatches as $attr) {
                    $attrName = strtolower($attr[1]);
                    $attrValue = $attr[2];

                    if (in_array($attrName, $allowedAttrs)) {
                        // Additional cleaning for specific attributes
                        $attrValue = self::cleanAttributeValue($attrName, $attrValue);
                        if ($attrValue !== false) {
                            $cleanAttrs[] = "{$attrName}=\"{$attrValue}\"";
                        }
                    }
                }

                $attrStr = !empty($cleanAttrs) ? ' ' . implode(' ', $cleanAttrs) : '';
                return "<{$tag}{$attrStr}>";
            },
            $html
        );
    }

    /**
     * Clean individual attribute values.
     */
    protected static function cleanAttributeValue(string $name, string $value): string|false
    {
        // Clean href and src - remove javascript: and data:
        if (in_array($name, ['href', 'src'])) {
            if (preg_match('/^\s*(javascript|data|vbscript):/i', $value)) {
                return false;
            }
        }

        // Clean style - remove expression() and other dangerous CSS
        if ($name === 'style') {
            $value = preg_replace('/expression\s*\([^)]*\)/i', '', $value);
            $value = preg_replace('/javascript\s*:/i', '', $value);
            $value = preg_replace('/behavior\s*:/i', '', $value);
        }

        // Escape special characters
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize an array of inputs.
     */
    public static function cleanArray(array $input, bool $richText = false): array
    {
        $cleaned = [];
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $cleaned[$key] = self::cleanArray($value, $richText);
            } elseif (is_string($value)) {
                $cleaned[$key] = $richText ? self::cleanRichText($value) : self::clean($value);
            } else {
                $cleaned[$key] = $value;
            }
        }
        return $cleaned;
    }

    /**
     * Validate and sanitize a NISN (numbers only).
     */
    public static function cleanNisn(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        return preg_replace('/[^0-9]/', '', $input);
    }

    /**
     * Sanitize email address.
     */
    public static function cleanEmail(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        $input = trim($input);
        return filter_var($input, FILTER_SANITIZE_EMAIL) ?: '';
    }

    /**
     * Sanitize integer.
     */
    public static function cleanInt($input): int
    {
        return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize filename.
     */
    public static function cleanFilename(?string $filename): string
    {
        if ($filename === null) {
            return '';
        }

        // Remove path components
        $filename = basename($filename);

        // Remove null bytes and other dangerous characters
        $filename = preg_replace('/[\x00-\x1f\x7f]/', '', $filename);

        // Allow only safe characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        return $filename;
    }

    /**
     * Sanitize URL.
     */
    public static function cleanUrl(?string $url): string
    {
        if ($url === null) {
            return '';
        }

        $url = trim($url);

        // Check for dangerous protocols
        if (preg_match('/^(javascript|data|vbscript):/i', $url)) {
            return '';
        }

        return filter_var($url, FILTER_SANITIZE_URL) ?: '';
    }
}
