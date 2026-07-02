export function commitTableCellContentAction({
    tableManagement,
    blocks,
    blockId,
    cellId,
    content,
    pendingContent,
    findBlockById,
    updateTableCellContent,
    onBlockUpdated,
    onNoChange
}) {
    const resolvedContent = (content !== null && content !== undefined)
        ? content
        : (pendingContent !== null && pendingContent !== undefined ? pendingContent : null);

    const foundCell = tableManagement.findTableCell(blocks, blockId, cellId);
    if (!foundCell) {
        return;
    }

    const currentContent = foundCell.cell?.content ?? '';
    const nextContent = (resolvedContent !== null && resolvedContent !== undefined)
        ? resolvedContent
        : currentContent;
    const hasChanged = nextContent !== currentContent;

    if (!hasChanged) {
        onNoChange();
        return;
    }

    updateTableCellContent(blocks, blockId, cellId, nextContent);

    const { block } = findBlockById(blockId);
    if (block) {
        onBlockUpdated(block);
    }
}
