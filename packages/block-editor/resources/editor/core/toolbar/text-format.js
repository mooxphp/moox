export function applyTextFormatToSelectionRange({
    selectedRange,
    element,
    format
}) {
    let selection;

    try {
        selection = window.getSelection();
        if (!selection) {
            return { ok: false, reason: 'no-selection' };
        }

        const rangeTarget = selectedRange ? selectedRange.commonAncestorContainer : null;
        const focusTarget = rangeTarget
            ? (rangeTarget.nodeType === 3 ? rangeTarget.parentElement : rangeTarget)
            : element;

        if (focusTarget && typeof focusTarget.focus === 'function') {
            focusTarget.focus();
        } else if (element && typeof element.focus === 'function') {
            element.focus();
        }

        selection.removeAllRanges();
        selection.addRange(selectedRange);
    } catch (_error) {
        return { ok: false, reason: 'restore-failed' };
    }

    try {
        // Keep parity with previous flow where state was probed before command.
        try {
            document.queryCommandState(format);
        } catch (_error) {
            // Ignore state probe errors.
        }

        document.execCommand(format, false, null);
    } catch (_error) {
        return { ok: false, reason: 'apply-failed' };
    }

    return { ok: true, selection };
}
