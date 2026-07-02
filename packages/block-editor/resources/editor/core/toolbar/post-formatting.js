export function runPostFormattingUpdate({
    nextTick,
    updateContentAfterLinkChange,
    element,
    selection,
    refreshRange = false,
    onRangeUpdated
}) {
    nextTick(() => {
        updateContentAfterLinkChange(element, selection);

        if (!refreshRange) {
            return;
        }

        const newSelection = window.getSelection();
        if (!newSelection || newSelection.rangeCount <= 0) {
            return;
        }

        if (typeof onRangeUpdated === 'function') {
            onRangeUpdated(newSelection.getRangeAt(0).cloneRange());
        }
    });
}
