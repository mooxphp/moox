export function syncContentAfterLinkChange({
    selection,
    selectedBlockId,
    element,
    getTableCellElementFromSelection,
    nextTick,
    updateListItemText,
    updateChecklistItemText,
    updateTableCellContent,
    findBlockById,
    updateBlockContent
}) {
    const selectionElement = selection?.anchorNode
        ? (selection.anchorNode.nodeType === 3 ? selection.anchorNode.parentElement : selection.anchorNode)
        : null;
    const checklistItemElement = selectionElement ? selectionElement.closest('[data-item-id]') : null;
    const listItemElement = selectionElement ? selectionElement.closest('[data-list-item-id]') : null;
    const tableCellElement = getTableCellElementFromSelection(selection);

    nextTick(() => {
        if (listItemElement) {
            const itemId = listItemElement.getAttribute('data-list-item-id');
            const blockId = listItemElement.getAttribute('data-block-id');
            updateListItemText(blockId, itemId, listItemElement.innerHTML);
            return;
        }

        if (checklistItemElement) {
            const itemId = checklistItemElement.getAttribute('data-item-id');
            const blockId = checklistItemElement.getAttribute('data-block-id');
            updateChecklistItemText(blockId, itemId, checklistItemElement.innerHTML);
            return;
        }

        if (tableCellElement) {
            const cellId = tableCellElement.getAttribute('data-cell-id');
            const blockId = tableCellElement.getAttribute('data-block-id');
            if (cellId && blockId) {
                updateTableCellContent(blockId, cellId, tableCellElement.innerHTML);
            }
            return;
        }

        const { block } = findBlockById(selectedBlockId);
        if (block) {
            updateBlockContent(selectedBlockId, element.innerHTML);
        }
    });
}

export function removeLinkAndSync({
    linkElement,
    blockId,
    getBlockElement,
    nextTick,
    getTableCellElementFromNode,
    updateTableCellContent,
    findBlockById,
    updateBlockContent
}) {
    if (!linkElement || !linkElement.parentNode) {
        return false;
    }

    const linkText = linkElement.textContent;
    const textNode = document.createTextNode(linkText);
    linkElement.parentNode.replaceChild(textNode, linkElement);

    const blockElement = getBlockElement(blockId);
    if (!blockElement) {
        return true;
    }

    nextTick(() => {
        const cellElement = getTableCellElementFromNode(textNode);
        if (cellElement) {
            const cellId = cellElement.getAttribute('data-cell-id');
            const cellBlockId = cellElement.getAttribute('data-block-id');
            if (cellId && cellBlockId) {
                updateTableCellContent(cellBlockId, cellId, cellElement.innerHTML);
                return;
            }
        }

        const { block } = findBlockById(blockId);
        if (block) {
            updateBlockContent(blockId, blockElement.innerHTML);
        }
    });

    return true;
}

export function syncEditedLinkContent({
    linkElement,
    blockId,
    getBlockElement,
    nextTick,
    getTableCellElementFromNode,
    updateTableCellContent,
    findBlockById,
    updateBlockContent
}) {
    const blockElement = getBlockElement(blockId);
    if (!blockElement) {
        return;
    }

    nextTick(() => {
        const cellElement = getTableCellElementFromNode(linkElement);
        if (cellElement) {
            const cellId = cellElement.getAttribute('data-cell-id');
            const cellBlockId = cellElement.getAttribute('data-block-id');
            if (cellId && cellBlockId) {
                updateTableCellContent(cellBlockId, cellId, cellElement.innerHTML);
                return;
            }
        }

        const { block } = findBlockById(blockId);
        if (block) {
            updateBlockContent(blockId, blockElement.innerHTML);
        }
    });
}
