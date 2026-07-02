export function getRequiredTrimmedUrl(value) {
    const trimmed = typeof value === 'string' ? value.trim() : '';

    if (!trimmed) {
        return {
            ok: false,
            value: ''
        };
    }

    return {
        ok: true,
        value: trimmed
    };
}
