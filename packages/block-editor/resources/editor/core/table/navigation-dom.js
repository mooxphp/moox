function getCellSelector(section) {
    if (section === 'header') {
        return 'thead th';
    }

    if (section === 'footer') {
        return 'tfoot td';
    }

    return 'tbody td';
}

function isCellVisible(cellEl) {
    const style = window.getComputedStyle(cellEl);
    return style.display !== 'none' && style.visibility !== 'hidden';
}

export function getVisibleTableCellElement(tableBlockId, section, rowIndex, colIndex) {
    const tableEl = document.querySelector(`table[data-block-id="${tableBlockId}"]`);
    if (!tableEl) {
        return null;
    }

    const selector = getCellSelector(section);
    const cellEl = tableEl.querySelector(`${selector}[data-row-index="${rowIndex}"][data-col-index="${colIndex}"]`);
    if (!cellEl || !isCellVisible(cellEl)) {
        return null;
    }

    return cellEl;
}

export function getLastVisibleTableRowIndex(tableBlockId, section, colIndex) {
    const tableEl = document.querySelector(`table[data-block-id="${tableBlockId}"]`);
    if (!tableEl) {
        return 0;
    }

    const selector = getCellSelector(section);
    const candidates = tableEl.querySelectorAll(`${selector}[data-col-index="${colIndex}"]`);
    let best = null;

    for (const cellEl of candidates) {
        if (!isCellVisible(cellEl)) {
            continue;
        }

        const ri = Number(cellEl.getAttribute('data-row-index'));
        if (Number.isNaN(ri)) {
            continue;
        }

        best = best === null ? ri : Math.max(best, ri);
    }

    return best === null ? 0 : best;
}

export function getVisibleTableCellElementsInRow(tableBlockId, section, rowIndex) {
    const tableEl = document.querySelector(`table[data-block-id="${tableBlockId}"]`);
    if (!tableEl) {
        return [];
    }

    const selector = getCellSelector(section);
    const candidates = tableEl.querySelectorAll(`${selector}[data-row-index="${rowIndex}"]`);
    const visible = [];

    for (const cellEl of candidates) {
        if (isCellVisible(cellEl)) {
            visible.push(cellEl);
        }
    }

    return visible;
}

export function focusTableCellContent(cellEl) {
    if (!cellEl) {
        return;
    }

    const editable = cellEl.querySelector('[contenteditable="true"]');
    if (!editable || typeof editable.focus !== 'function') {
        return;
    }

    editable.focus({ preventScroll: true });
    try {
        const range = document.createRange();
        const sel = window.getSelection();
        range.selectNodeContents(editable);
        range.collapse(false);
        if (sel) {
            sel.removeAllRanges();
            sel.addRange(range);
        }
    } catch (_error) {
        // Cursor placement can fail depending on browser/DOM state.
    }

    try {
        cellEl.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    } catch (_error) {
        // Ignore scroll errors.
    }
}
