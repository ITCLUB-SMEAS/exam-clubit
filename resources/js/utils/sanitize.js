/**
 * HTML Sanitization Utility
 *
 * Provides safe HTML sanitization for user-generated content
 * to prevent XSS attacks when using v-html.
 *
 * Usage:
 *   import { sanitizeHtml, sanitizeQuestionHtml } from '@/utils/sanitize';
 *   const clean = sanitizeHtml(untrustedHtml);
 */

/**
 * Allowed HTML tags for different contexts
 */
const ALLOWED_TAGS = {
    // Basic formatting allowed in most places
    basic: ['p', 'br', 'b', 'strong', 'i', 'em', 'u', 'span'],

    // Extended formatting for rich content (questions, descriptions)
    rich: [
        'p', 'br', 'b', 'strong', 'i', 'em', 'u', 'span',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'blockquote', 'pre', 'code',
        'a', 'img',
        'sub', 'sup',
        'hr',
        'div',
    ],

    // Math content (for MathJax/KaTeX)
    math: [
        'math', 'mi', 'mn', 'mo', 'mrow', 'msup', 'msub', 'mfrac',
        'msqrt', 'mroot', 'mtext', 'mspace', 'mtable', 'mtr', 'mtd',
        'annotation', 'semantics',
    ],
};

/**
 * Allowed attributes for specific tags
 */
const ALLOWED_ATTRS = {
    a: ['href', 'title', 'target', 'rel'],
    img: ['src', 'alt', 'width', 'height', 'class'],
    span: ['class', 'style'],
    div: ['class', 'style'],
    table: ['class', 'border'],
    td: ['colspan', 'rowspan', 'class'],
    th: ['colspan', 'rowspan', 'class'],
    code: ['class'],
    pre: ['class'],
    p: ['class', 'style'],
    // Allow data attributes for math rendering
    '*': ['data-*'],
};

/**
 * Allowed URL protocols
 */
const ALLOWED_PROTOCOLS = ['http:', 'https:', 'mailto:', 'data:'];

/**
 * Dangerous patterns to remove
 */
const DANGEROUS_PATTERNS = [
    /javascript:/gi,
    /vbscript:/gi,
    /on\w+\s*=/gi,  // onclick, onerror, etc.
    /<script\b[^>]*>[\s\S]*?<\/script>/gi,
    /<style\b[^>]*>[\s\S]*?<\/style>/gi,
    /expression\s*\(/gi,
    /url\s*\(\s*["']?\s*javascript:/gi,
];

/**
 * Create a DOM parser for sanitization
 */
const createParser = () => {
    if (typeof DOMParser !== 'undefined') {
        return new DOMParser();
    }
    return null;
};

/**
 * Check if a URL is safe
 */
const isSafeUrl = (url) => {
    if (!url) return true;

    try {
        const parsed = new URL(url, window.location.origin);
        return ALLOWED_PROTOCOLS.includes(parsed.protocol);
    } catch {
        // Relative URLs are generally safe
        return !url.toLowerCase().trim().startsWith('javascript:');
    }
};

/**
 * Check if an attribute is allowed for a tag
 */
const isAllowedAttr = (tagName, attrName) => {
    const tagLower = tagName.toLowerCase();
    const attrLower = attrName.toLowerCase();

    // Check tag-specific allowed attributes
    if (ALLOWED_ATTRS[tagLower]?.includes(attrLower)) {
        return true;
    }

    // Check wildcard data-* attributes
    if (attrLower.startsWith('data-')) {
        return ALLOWED_ATTRS['*']?.includes('data-*') ?? false;
    }

    // Common safe attributes
    const commonSafe = ['class', 'id', 'title'];
    return commonSafe.includes(attrLower);
};

/**
 * Sanitize a single element
 */
const sanitizeElement = (element, allowedTags) => {
    const tagName = element.tagName.toLowerCase();

    // Check if tag is allowed
    if (!allowedTags.includes(tagName)) {
        // Replace with text content if not allowed
        const text = document.createTextNode(element.textContent || '');
        element.parentNode?.replaceChild(text, element);
        return;
    }

    // Remove dangerous attributes
    const attrs = Array.from(element.attributes);
    for (const attr of attrs) {
        const attrName = attr.name.toLowerCase();

        // Remove event handlers
        if (attrName.startsWith('on')) {
            element.removeAttribute(attr.name);
            continue;
        }

        // Check if attribute is allowed
        if (!isAllowedAttr(tagName, attrName)) {
            element.removeAttribute(attr.name);
            continue;
        }

        // Sanitize URLs in href and src
        if (['href', 'src'].includes(attrName)) {
            if (!isSafeUrl(attr.value)) {
                element.removeAttribute(attr.name);
            }
        }

        // Sanitize style attribute (remove dangerous patterns)
        if (attrName === 'style') {
            let style = attr.value;
            for (const pattern of DANGEROUS_PATTERNS) {
                style = style.replace(pattern, '');
            }
            element.setAttribute('style', style);
        }
    }

    // Add security attributes to links
    if (tagName === 'a') {
        element.setAttribute('rel', 'noopener noreferrer');
        if (element.getAttribute('target') === '_blank') {
            // Already has rel from above
        }
    }

    // Recursively sanitize children
    const children = Array.from(element.children);
    for (const child of children) {
        sanitizeElement(child, allowedTags);
    }
};

/**
 * Main sanitization function
 *
 * @param {string} html - Untrusted HTML string
 * @param {string} context - Context: 'basic', 'rich', or 'math'
 * @returns {string} - Sanitized HTML
 */
export const sanitizeHtml = (html, context = 'rich') => {
    if (!html || typeof html !== 'string') {
        return '';
    }

    // Quick removal of dangerous patterns
    let cleaned = html;
    for (const pattern of DANGEROUS_PATTERNS) {
        cleaned = cleaned.replace(pattern, '');
    }

    // Parse and sanitize DOM
    const parser = createParser();
    if (!parser) {
        // Fallback: strip all tags if DOMParser not available
        return cleaned.replace(/<[^>]*>/g, '');
    }

    try {
        const doc = parser.parseFromString(`<div>${cleaned}</div>`, 'text/html');
        const container = doc.body.firstChild;

        if (!container) {
            return '';
        }

        // Get allowed tags for context
        const allowedTags = [
            ...ALLOWED_TAGS[context] || ALLOWED_TAGS.basic,
            ...(context === 'rich' ? ALLOWED_TAGS.math : []),
        ];

        // Sanitize all elements
        const elements = Array.from(container.getElementsByTagName('*'));
        for (const element of elements) {
            sanitizeElement(element, allowedTags);
        }

        return container.innerHTML;
    } catch (e) {
        console.error('HTML sanitization error:', e);
        // Fallback: strip all tags
        return cleaned.replace(/<[^>]*>/g, '');
    }
};

/**
 * Sanitize question/exam content (rich formatting + math)
 */
export const sanitizeQuestionHtml = (html) => {
    return sanitizeHtml(html, 'rich');
};

/**
 * Sanitize basic text content
 */
export const sanitizeBasicHtml = (html) => {
    return sanitizeHtml(html, 'basic');
};

/**
 * Escape HTML completely (for displaying as text)
 */
export const escapeHtml = (text) => {
    if (!text || typeof text !== 'string') {
        return '';
    }

    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    };

    return text.replace(/[&<>"']/g, char => map[char]);
};

/**
 * Vue directive for safe v-html alternative
 *
 * Usage in component:
 *   import { vSafeHtml } from '@/utils/sanitize';
 *   <div v-safe-html="untrustedContent"></div>
 */
export const vSafeHtml = {
    mounted(el, binding) {
        el.innerHTML = sanitizeHtml(binding.value, binding.arg || 'rich');
    },
    updated(el, binding) {
        if (binding.value !== binding.oldValue) {
            el.innerHTML = sanitizeHtml(binding.value, binding.arg || 'rich');
        }
    },
};

export default {
    sanitizeHtml,
    sanitizeQuestionHtml,
    sanitizeBasicHtml,
    escapeHtml,
    vSafeHtml,
};
