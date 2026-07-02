export function resolveSelectionRange(preferredRange) {
    let selection = window.getSelection();
    let range = preferredRange;

    if (!range || !document.contains(range.commonAncestorContainer)) {
        if (selection && selection.rangeCount > 0) {
            range = selection.getRangeAt(0).cloneRange();
        }
    }

    return {
        selection,
        range
    };
}

export function restoreSelectionRange(selection, range) {
    if (!selection || !range) {
        return;
    }

    selection.removeAllRanges();
    selection.addRange(range);
}
