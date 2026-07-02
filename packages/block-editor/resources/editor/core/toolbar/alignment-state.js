function saveCurrentRanges(selection) {
    const savedRanges = [];
    for (let i = 0; i < selection.rangeCount; i++) {
        savedRanges.push(selection.getRangeAt(i).cloneRange());
    }
    return savedRanges;
}

function restoreRanges(selection, ranges) {
    selection.removeAllRanges();
    ranges.forEach((range) => selection.addRange(range));
}

function resolveNodeFromRange(range) {
    let node = range.commonAncestorContainer;
    if (node.nodeType === 3) {
        node = node.parentElement;
    }
    return node;
}

function isAlignmentActive(alignment, textAlign) {
    if (alignment === 'left') {
        return textAlign === 'left' || textAlign === 'start' || !textAlign;
    }
    if (alignment === 'center') {
        return textAlign === 'center';
    }
    if (alignment === 'right') {
        return textAlign === 'right' || textAlign === 'end';
    }
    return false;
}

export function getTextAlignmentStateFromSelection({
    alignment,
    selectedRange,
    selectedBlockId,
    getBlockElement
}) {
    if (!selectedRange || !selectedBlockId) {
        return 'off';
    }

    try {
        const element = getBlockElement(selectedBlockId);
        if (!element) {
            return 'off';
        }

        const currentSelection = window.getSelection();
        if (!currentSelection) {
            return 'off';
        }

        const savedRanges = saveCurrentRanges(currentSelection);

        currentSelection.removeAllRanges();
        currentSelection.addRange(selectedRange);

        const range = currentSelection.getRangeAt(0);
        const node = resolveNodeFromRange(range);
        const computedStyle = window.getComputedStyle(node);
        const textAlign = computedStyle.textAlign;

        restoreRanges(currentSelection, savedRanges);

        return isAlignmentActive(alignment, textAlign) ? 'on' : 'off';
    } catch (_error) {
        return 'off';
    }
}
