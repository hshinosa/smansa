import DOMPurify from 'isomorphic-dompurify';

/**
 * Sanitize HTML content to prevent XSS attacks
 * 
 * @param {string} html - Raw HTML string
 * @param {object} options - DOMPurify options
 * @returns {string} - Sanitized HTML
 */
export function sanitizeHtml(html, options = {}) {
    if (!html) return '';
    
    const defaultOptions = {
        ALLOWED_TAGS: [
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'p', 'br', 'hr',
            'strong', 'em', 'u', 's', 'del', 'ins',
            'ul', 'ol', 'li',
            'a', 'img',
            'blockquote', 'pre', 'code',
            'table', 'thead', 'tbody', 'tr', 'th', 'td',
            'span', 'div',
        ],
        ALLOWED_ATTR: [
            'href', 'title', 'alt', 'src', 'class', 'id', 'style',
        ],
        ALLOW_DATA_ATTR: false,
        ADD_ATTR: ['target'],
    };
    
    const config = { ...defaultOptions, ...options };
    
    return DOMPurify.sanitize(html, config);
}

/**
 * Sanitize and render HTML in React safely
 * 
 * @param {string} html - Raw HTML string
 * @param {object} options - DOMPurify options
 * @returns {React.Element} - Sanitized HTML component
 */
export function SafeHtml({ html, options = {}, className = '' }) {
    const sanitizedHtml = sanitizeHtml(html, options);
    
    return (
        <div 
            className={className}
            dangerouslySetInnerHTML={{ __html: sanitizedHtml }}
        />
    );
}

/**
 * Check if a string contains potentially dangerous content
 * 
 * @param {string} content - Content to check
 * @returns {boolean} - True if content is safe
 */
export function isSafeContent(content) {
    if (!content) return true;
    
    const sanitized = sanitizeHtml(content);
    return sanitized === content;
}