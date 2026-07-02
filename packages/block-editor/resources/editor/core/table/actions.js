export function addTableRowAction({
    tableManagement,
    blocks,
    blockId,
    blockIdCounter,
    position,
    onCounterUpdate,
    onInvalidateSettings
}) {
    const result = tableManagement.addTableRow(blocks, blockId, blockIdCounter, position);
    if (result) {
        onCounterUpdate(result.lastCellIdCounter + 1);
        onInvalidateSettings(blockId);
    }
}

export function removeTableRowAction({ tableManagement, blocks, blockId, rowIndex, onInvalidateSettings }) {
    tableManagement.removeTableRow(blocks, blockId, rowIndex);
    onInvalidateSettings(blockId);
}

export function addTableColumnAction({
    tableManagement,
    blocks,
    blockId,
    blockIdCounter,
    position,
    onCounterUpdate,
    onInvalidateSettings
}) {
    const result = tableManagement.addTableColumn(blocks, blockId, blockIdCounter, position);
    if (result) {
        onCounterUpdate(result.lastCellIdCounter + 1);
        onInvalidateSettings(blockId);
    }
}

export function removeTableColumnAction({ tableManagement, blocks, blockId, colIndex, onInvalidateSettings }) {
    tableManagement.removeTableColumn(blocks, blockId, colIndex);
    onInvalidateSettings(blockId);
}

export function mergeTableCellsAction({
    tableManagement,
    blocks,
    blockId,
    startRow,
    startCol,
    endRow,
    endCol,
    onInvalidateSettings
}) {
    tableManagement.mergeTableCells(blocks, blockId, startRow, startCol, endRow, endCol);
    onInvalidateSettings(blockId);
}

export function unmergeTableCellsAction({ tableManagement, blocks, blockId, row, col }) {
    tableManagement.unmergeTableCells(blocks, blockId, row, col);
}

export function toggleTableHeaderAction({ tableManagement, blocks, blockId, onInvalidateSettings }) {
    tableManagement.toggleTableHeader(blocks, blockId);
    onInvalidateSettings(blockId);
}

export function toggleTableFooterAction({ tableManagement, blocks, blockId, onInvalidateSettings }) {
    tableManagement.toggleTableFooter(blocks, blockId);
    onInvalidateSettings(blockId);
}
