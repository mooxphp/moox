export function resolveNextTableTabTarget({
    rowCells,
    currentCellId,
    currentCol,
    direction
}) {
    if (!Array.isArray(rowCells) || rowCells.length === 0) {
        return null;
    }

    const sorted = rowCells
        .map((el) => ({
            el,
            col: Number(el.getAttribute('data-col-index'))
        }))
        .filter((item) => !Number.isNaN(item.col))
        .sort((a, b) => a.col - b.col);

    let currentIndex = -1;
    if (currentCellId) {
        currentIndex = sorted.findIndex((item) => item.el.getAttribute('data-cell-id') === currentCellId);
    }

    if (currentIndex === -1 && !Number.isNaN(currentCol)) {
        currentIndex = sorted.findIndex((item) => item.col === currentCol);
    }

    if (currentIndex === -1) {
        return null;
    }

    return sorted[currentIndex + direction]?.el || null;
}
