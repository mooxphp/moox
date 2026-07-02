export function getSelectionBoundingRect(range) {
    try {
        return range.getBoundingClientRect();
    } catch (_error) {
        return null;
    }
}

export function buildSelectionSnapshot({ selectedText, range, blockId }) {
    try {
        return {
            selectedText,
            selectedRange: range.cloneRange(),
            selectedBlockId: blockId
        };
    } catch (_error) {
        return null;
    }
}
