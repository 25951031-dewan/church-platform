import React from 'react';
import DOMPurify from 'dompurify';

interface Props {
    html: string;
    className?: string;
    style?: React.CSSProperties;
}

/**
 * Renders sanitized HTML content.
 * All user-generated HTML must go through this component.
 * Uses DOMPurify to strip XSS vectors before rendering.
 */
export default function SafeHtml({ html, className, style }: Props) {
    return (
        <div
            className={className}
            style={style}
            ref={(el) => {
                if (el) el.innerHTML = DOMPurify.sanitize(html);
            }}
        />
    );
}
