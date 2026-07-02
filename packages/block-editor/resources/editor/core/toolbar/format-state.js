function saveCurrentRanges(selection) {
    const savedRanges = [];
    try {
        for (let i = 0; i < selection.rangeCount; i++) {
            savedRanges.push(selection.getRangeAt(i).cloneRange());
        }
    } catch (_error) {
        // Ignore save errors and keep empty fallback.
    }
    return savedRanges;
}

function restoreRanges(selection, ranges) {
    try {
        selection.removeAllRanges();
        ranges.forEach((range) => {
            try {
                selection.addRange(range);
            } catch (_error) {
                // Ignore restore errors for individual ranges.
            }
        });
    } catch (_error) {
        // Ignore restore errors.
    }
}

export function getFormatStateFromSelection({
    selectedRange,
    selectedBlockId,
    format,
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

        const selection = window.getSelection();
        if (!selection) {
            return 'off';
        }

        const savedRanges = saveCurrentRanges(selection);

        try {
            selection.removeAllRanges();
            selection.addRange(selectedRange);

            let isActive = false;
            try {
                isActive = document.queryCommandState(format);
            } catch (_error) {
                return 'off';
            }

            restoreRanges(selection, savedRanges);
            return isActive ? 'on' : 'off';
        } catch (_error) {
            restoreRanges(selection, savedRanges);
            return 'off';
        }
    } catch (_error) {
        return 'off';
    }
}
