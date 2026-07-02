import { findLinkInSelection, isToolbarAllowedForBlock } from './selection-context.js';
import { buildSelectionSnapshot, getSelectionBoundingRect } from './selection-geometry.js';

export function resolveTextSelectionContext({
    selectionDetails,
    resolveEditableElementFromRange,
    findBlockById,
    getBlockElementByEditable
}) {
    if (!selectionDetails) {
        return null;
    }

    const { selection, range, selectedText } = selectionDetails;
    const editableElement = resolveEditableElementFromRange(range);
    if (!editableElement) {
        return null;
    }

    const blockElement = getBlockElementByEditable(editableElement);
    if (!blockElement) {
        return null;
    }

    const blockId = blockElement.getAttribute('data-block-id');
    const { block } = findBlockById(blockId);
    if (!isToolbarAllowedForBlock(block)) {
        return null;
    }

    const rect = getSelectionBoundingRect(range);
    if (!rect) {
        return null;
    }

    const snapshot = buildSelectionSnapshot({
        selectedText,
        range,
        blockId
    });
    if (!snapshot) {
        return null;
    }

    return {
        blockElement,
        linkInSelection: findLinkInSelection(selection, blockElement),
        rect,
        snapshot
    };
}
