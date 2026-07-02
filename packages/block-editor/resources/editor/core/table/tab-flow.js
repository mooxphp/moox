import { resolveNextTableTabTarget } from './tab-navigation.js';

export function handleTableCellTabFlow({
    event,
    tableBlockId,
    rowIndex,
    colIndex,
    section,
    getVisibleTableCellElementsInRow,
    commitTableCellContent,
    nextTick,
    focusTableCellContent
}) {
    if (!event || event.key !== 'Tab') {
        return;
    }

    if (event.ctrlKey || event.metaKey || event.altKey) {
        return;
    }

    const direction = event.shiftKey ? -1 : 1;
    const currentRow = Number(rowIndex);
    const currentCol = Number(colIndex);
    const currentCellEl = event.target?.closest?.('td,th') || null;
    const currentCellId = currentCellEl ? currentCellEl.getAttribute('data-cell-id') : null;

    const rowCells = getVisibleTableCellElementsInRow(tableBlockId, section, currentRow);
    if (!rowCells.length) {
        return;
    }

    const target = resolveNextTableTabTarget({
        rowCells,
        currentCellId,
        currentCol,
        direction
    });
    if (!target) {
        return;
    }

    const targetCellId = target.getAttribute('data-cell-id');

    event.preventDefault();
    event.stopPropagation();

    if (currentCellId) {
        commitTableCellContent(tableBlockId, currentCellId, event.target.innerHTML);
    }

    nextTick(() => {
        const tableEl = document.querySelector(`table[data-block-id="${tableBlockId}"]`);
        if (!tableEl || !targetCellId) {
            return;
        }

        const newTarget = tableEl.querySelector(`[data-cell-id="${targetCellId}"]`);
        if (!newTarget) {
            return;
        }

        const style = window.getComputedStyle(newTarget);
        if (style.display === 'none' || style.visibility === 'hidden') {
            return;
        }

        focusTableCellContent(newTarget);
    });
}
