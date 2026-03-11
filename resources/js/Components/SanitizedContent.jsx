import React from 'react';
import DOMPurify from 'isomorphic-dompurify';

/**
 * Renders HTML content safely by sanitizing it first to prevent XSS attacks.
 * Uses isomorphic-dompurify which works on both client and server (SSR).
 * 
 * @param {string} html - The raw HTML string to sanitize and render
 * @param {string} className - Optional classes for the container
 * @param {string} as - The HTML tag to use for the container (default: 'div')
 */
const SanitizedContent = ({ html, className = '', as: Tag = 'div', ...props }) => {
    // Basic sanitization config
    // We allow basic formatting tags but strip scripts/iframes unless specifically allowed
    const sanitizedHtml = DOMPurify.sanitize(html, {
        ADD_TAGS: ['iframe'], // Allow iframes for embeds (e.g. YouTube), but be careful
        ADD_ATTR: ['allow', 'allowfullscreen', 'frameborder', 'scrolling', 'target'],
    });

    return (
        <Tag 
            className={className}
            dangerouslySetInnerHTML={{ __html: sanitizedHtml }}
            {...props}
        />
    );
};

export default SanitizedContent;
