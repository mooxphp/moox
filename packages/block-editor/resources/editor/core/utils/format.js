// Format Utilities
/** Erzeugt eine eindeutige ID (Timestamp + optionaler Offset für Mehrfachaufrufe in derselben ms). */
export function generateId(blockIdCounter) {
    const offset = Number(blockIdCounter) || 0;
    return String(Date.now() + offset);
}

/** Kryptografisch sichere eindeutige ID (kein Math.random — Sonar S2245). */
export function createUniqueId(prefix = 'id') {
    if (typeof crypto === 'undefined') {
        throw new Error('Secure random generator unavailable');
    }

    if (typeof crypto.randomUUID === 'function') {
        return `${prefix}-${crypto.randomUUID()}`;
    }

    if (typeof crypto.getRandomValues !== 'function') {
        throw new Error('Secure random generator unavailable');
    }

    const bytes = new Uint8Array(16);
    crypto.getRandomValues(bytes);

    return `${prefix}-${Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('')}`;
}

/** Escaping für HTML-Attribute (data-block-id, src, …). */
export function escapeHtmlAttribute(value) {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/'/g, '&#39;');
}

/** Escaping für single-quoted JS-Literale in Alpine-Handlern (auch in double-quoted HTML-Attributen). */
export function escapeJsSingleQuoted(value) {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value)
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/"/g, '\\x22')
        .replace(/\n/g, '\\n')
        .replace(/\r/g, '\\r')
        .replace(/\u2028/g, '\\u2028')
        .replace(/\u2029/g, '\\u2029');
}
