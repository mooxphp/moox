function getAlignmentCommand(alignment) {
    if (alignment === 'center') {
        return 'justifyCenter';
    }

    if (alignment === 'right') {
        return 'justifyRight';
    }

    return 'justifyLeft';
}

export function applyTextAlignmentToSelectionRange({
    selectedRange,
    element,
    alignment
}) {
    const selection = window.getSelection();
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

    try {
        document.execCommand(getAlignmentCommand(alignment), false, null);
    } catch (_error) {
        return { ok: false, reason: 'command-failed' };
    }

    return { ok: true, selection };
}
