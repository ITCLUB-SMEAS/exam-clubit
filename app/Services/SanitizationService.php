<?php

namespace App\Services;

class SanitizationService
{
    /**
     * Allowed HTML tags for rich text content (questions, options).
     * Includes HTML5 tags for multimedia and math content.
     */
    protected static array $allowedTags = [
        // Basic formatting
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike',
        'ul', 'ol', 'li',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'blockquote', 'pre', 'code',
        'sub', 'sup',
        'hr', 'span', 'div',
        // Tables
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'colgroup', 'col',
        // Media & Links
        'img', 'a', 'figure', 'figcaption',
        // HTML5 Media
        'audio', 'video', 'source', 'track',
        // HTML5 Canvas (for diagrams)
        'canvas',
        // Math (MathML & KaTeX)
        'math', 'mi', 'mn', 'mo', 'ms', 'mtext', 'mspace', 'mglyph',
        'mrow', 'mfrac', 'msqrt', 'mroot', 'mstyle', 'merror', 'mpadded', 'mphantom',
        'msub', 'msup', 'msubsup', 'munder', 'mover', 'munderover', 'mmultiscripts',
        'mtable', 'mtr', 'mtd', 'maligngroup', 'malignmark',
        'maction', 'menclose', 'semantics', 'annotation', 'annotation-xml',
        // KaTeX specific
        'katex', 'katex-mathml', 'katex-html',
        // Note: SVG removed for security - use <img> with .svg files instead
        // Details/Summary
        'details', 'summary',
        // Ruby (for annotations)
        'ruby', 'rt', 'rp',
        // Other semantic
        'abbr', 'cite', 'dfn', 'kbd', 'mark', 'q', 'samp', 'var', 'wbr', 'time',
    ];

    /**
     * Allowed attributes for specific tags.
     */
    protected static array $allowedAttributes = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading', 'class'],
        'table' => ['border', 'cellpadding', 'cellspacing', 'class', 'style'],
        'td' => ['colspan', 'rowspan', 'class', 'style'],
        'th' => ['colspan', 'rowspan', 'class', 'style', 'scope'],
        'tr' => ['class', 'style'],
        'span' => ['style', 'class', 'data-*'],
        'div' => ['style', 'class', 'data-*'],
        'p' => ['style', 'class'],
        'pre' => ['class', 'style'],
        'code' => ['class', 'style'],
        // HTML5 Media
        'audio' => ['src', 'controls', 'autoplay', 'loop', 'muted', 'preload', 'class'],
        'video' => ['src', 'controls', 'autoplay', 'loop', 'muted', 'preload', 'width', 'height', 'poster', 'class'],
        'source' => ['src', 'type'],
        'track' => ['src', 'kind', 'srclang', 'label', 'default'],
        'canvas' => ['width', 'height', 'class', 'id'],
        // Note: SVG attributes removed for security
        // Math
        'math' => ['xmlns', 'display', 'class'],
        // Figure
        'figure' => ['class', 'style'],
        'figcaption' => ['class', 'style'],
    ];

    /**
     * Dangerous patterns to remove.
     * Note: on* event handlers are removed in cleanAttributes() to avoid breaking HTML structure
     */
    protected static array $dangerousPatterns = [
        // JavaScript protocol
        '/javascript\s*:/i',
        // Data protocol with base64 (can contain scripts)
        '/data\s*:[^,]*base64[^"\'>\\s]*/i',
        // VBScript
        '/vbscript\s*:/i',
        // Expression (IE CSS)
        '/expression\s*\(/i',
        // Script tags
        '/<script[^>]*>.*?<\/script>/is',
        // Style tags (can be used for CSS injection/data exfiltration)
        '/<style[^>]*>.*?<\/style>/is',
        // iframe, object, embed, applet
        '/<(iframe|object|embed|applet)[^>]*>.*?<\/\1>/is',
        '/<(iframe|object|embed|applet)[^>]*\/?>/i',
        // Meta refresh
        '/<meta[^>]*http-equiv[^>]*refresh/i',
        // SVG tags (can contain scripts and event handlers)
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
     * 
     * Properly handles <pre><code> blocks for programming questions.
     */
    public static function cleanRichText(?string $input): string
    {
        if ($input === null) {
            return '';
        }

        // Remove null bytes
        $input = str_replace("\0", '', $input);

        // Preserve code blocks by replacing them with placeholders
        $codeBlocks = [];
        $placeholder = '___CODE_BLOCK_PLACEHOLDER_%d___';
        
        // Handle nested <pre><code> blocks first (most common for code)
        $input = preg_replace_callback(
            '/<pre([^>]*)>\s*<code([^>]*)>(.*?)<\/code>\s*<\/pre>/is',
            function ($matches) use (&$codeBlocks, $placeholder) {
                $index = count($codeBlocks);
                $preAttrs = self::sanitizeCodeAttributes($matches[1]);
                $codeAttrs = self::sanitizeCodeAttributes($matches[2]);
                // Store the raw code content - will be HTML-escaped on output
                $codeBlocks[$index] = [
                    'type' => 'pre-code',
                    'preAttrs' => $preAttrs,
                    'codeAttrs' => $codeAttrs,
                    'content' => $matches[3], // Keep raw for proper escape later
                ];
                return sprintf($placeholder, $index);
            },
            $input
        );

        // Handle standalone <code> blocks (inline code)
        $input = preg_replace_callback(
            '/<code([^>]*)>(.*?)<\/code>/is',
            function ($matches) use (&$codeBlocks, $placeholder) {
                $index = count($codeBlocks);
                $attrs = self::sanitizeCodeAttributes($matches[1]);
                $codeBlocks[$index] = [
                    'type' => 'code',
                    'attrs' => $attrs,
                    'content' => $matches[2],
                ];
                return sprintf($placeholder, $index);
            },
            $input
        );

        // Handle standalone <pre> blocks
        $input = preg_replace_callback(
            '/<pre([^>]*)>(.*?)<\/pre>/is',
            function ($matches) use (&$codeBlocks, $placeholder) {
                $index = count($codeBlocks);
                $attrs = self::sanitizeCodeAttributes($matches[1]);
                $codeBlocks[$index] = [
                    'type' => 'pre',
                    'attrs' => $attrs,
                    'content' => $matches[2],
                ];
                return sprintf($placeholder, $index);
            },
            $input
        );

        // Remove dangerous patterns from the rest of the content
        foreach (self::$dangerousPatterns as $pattern) {
            $input = preg_replace($pattern, '', $input);
        }

        // Clean attributes on allowed tags
        $input = self::cleanAttributes($input);

        // Restore code blocks with proper HTML escaping
        foreach ($codeBlocks as $index => $block) {
            $searchPlaceholder = sprintf($placeholder, $index);
            
            if ($block['type'] === 'pre-code') {
                // For pre+code: escape content to display as code
                $escapedContent = htmlspecialchars($block['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $replacement = "<pre{$block['preAttrs']}><code{$block['codeAttrs']}>{$escapedContent}</code></pre>";
            } elseif ($block['type'] === 'code') {
                $escapedContent = htmlspecialchars($block['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $replacement = "<code{$block['attrs']}>{$escapedContent}</code>";
            } else { // pre
                $escapedContent = htmlspecialchars($block['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $replacement = "<pre{$block['attrs']}>{$escapedContent}</pre>";
            }
            
            $input = str_replace($searchPlaceholder, $replacement, $input);
        }

        return trim($input);
    }

    /**
     * Sanitize attributes on code/pre tags.
     * Only allow class and data-language attributes for syntax highlighting.
     */
    protected static function sanitizeCodeAttributes(string $attrs): string
    {
        $allowedAttrs = ['class', 'data-language', 'data-lang'];
        $cleanAttrs = [];
        
        preg_match_all('/(\w+(?:-\w+)?)\s*=\s*["\']([^"\']*)["\']/', $attrs, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $attrName = strtolower($match[1]);
            $attrValue = $match[2];
            
            if (in_array($attrName, $allowedAttrs)) {
                // Only allow safe class names (alphanumeric, dash, underscore)
                if ($attrName === 'class') {
                    $attrValue = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $attrValue);
                }
                $cleanAttrs[] = "{$attrName}=\"{$attrValue}\"";
            }
        }
        
        return !empty($cleanAttrs) ? ' ' . implode(' ', $cleanAttrs) : '';
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

    /**
     * Sanitize content for programming/code questions.
     * 
     * This method is specifically designed for Informatics questions where
     * actual HTML/JavaScript code needs to be displayed in the question.
     * It removes only truly dangerous executable patterns while preserving
     * code examples for educational purposes.
     * 
     * The approach:
     * 1. Remove script tags that would actually execute
     * 2. Remove event handlers (onclick, onerror, etc.)
     * 3. Keep the rest of the code intact for display
     * 4. Frontend should display via v-html with CSS that escapes execution
     */
    public static function cleanCodeQuestion(?string $input): string
    {
        if ($input === null) {
            return '';
        }

        // Remove null bytes
        $input = str_replace("\0", '', $input);

        // Only remove patterns that would actually execute in browser
        $executablePatterns = [
            // Event handlers that would execute JS
            '/\s+on\w+\s*=\s*["\'][^"\']*["\']/i',
            '/\s+on\w+\s*=\s*[^\s>]+/i',
            // JavaScript protocol in attributes
            '/\s+(href|src|action)\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i',
            // Actual script tags that would run (not code examples inside pre/code)
            '/(<script(?![^>]*type\s*=\s*["\']text\/template)[^>]*>)(.*?)(<\/script>)/is',
        ];

        foreach ($executablePatterns as $pattern) {
            $input = preg_replace($pattern, '', $input);
        }

        return trim($input);
    }

    /**
     * Prepare code content for safe storage.
     * 
     * Use this when storing raw code that should be displayed as-is.
     * The code will be base64 encoded to prevent any processing.
     */
    public static function encodeRawCode(string $code): string
    {
        return '<!--CODE:' . base64_encode($code) . ':CODE-->';
    }

    /**
     * Decode previously encoded raw code.
     */
    public static function decodeRawCode(string $encoded): string
    {
        if (preg_match('/<!--CODE:([A-Za-z0-9+\/=]+):CODE-->/', $encoded, $matches)) {
            return base64_decode($matches[1]);
        }
        return $encoded;
    }

    /**
     * Check if content contains encoded raw code.
     */
    public static function hasEncodedCode(string $content): bool
    {
        return str_contains($content, '<!--CODE:');
    }
}

