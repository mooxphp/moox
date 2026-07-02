export function restoreSelectionAndRemoveFormatting(selectedRange) {
    let selection;

    try {
        selection = window.getSelection();
        if (!selection) {
            return { ok: false, reason: 'no-selection' };
        }

        selection.removeAllRanges();
        selection.addRange(selectedRange);
    } catch (_error) {
        return { ok: false, reason: 'restore-failed' };
    }

    try {
        document.execCommand('removeFormat', false, null);
        document.execCommand('unlink', false, null);
    } catch (_error) {
        return { ok: false, reason: 'remove-failed' };
    }

    return { ok: true, selection };
}
