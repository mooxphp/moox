export function applyTextColorToSelectionRange(selectedRange, color) {
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
        document.execCommand('foreColor', false, color);
        return { ok: true, selection };
    } catch (_error) {
        try {
            const range = selection.getRangeAt(0);
            const span = document.createElement('span');
            span.style.color = color;
            try {
                range.surroundContents(span);
            } catch (_error2) {
                const contents = range.extractContents();
                span.appendChild(contents);
                range.insertNode(span);
            }
            return { ok: true, selection };
        } catch (_error3) {
            return { ok: false, reason: 'apply-failed' };
        }
    }
}
