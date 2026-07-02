// Drag and Drop Functions - als Objekt organisiert
import { BlockTypes } from '../../components/block-types.js';

function isDefined(value) {
    return value !== null && value !== undefined;
}

function resolveDragLocation(index, childIndex = null) {
    if (isDefined(childIndex)) {
        return { type: 'child', parentIndex: index, childIndex: childIndex };
    }

    return { type: 'main', index: index };
}

function resolveTargetColumn(columns, startColumn, dropChildIndex, targetColumnIndex) {
    if (isDefined(targetColumnIndex)) {
        return targetColumnIndex;
    }

    if (isDefined(dropChildIndex)) {
        return dropChildIndex % columns;
    }

    return startColumn;
}

function countItemsInColumn(children, columns, targetColumn) {
    let itemsInTargetColumn = 0;
    for (let i = 0; i < children.length; i += 1) {
        if (i % columns === targetColumn) {
            itemsInTargetColumn += 1;
        }
    }

    return itemsInTargetColumn;
}

function calculateTargetIndexAfterRemoval(children, start, dropChildIndex, columns, finalTargetColumn, startColumn) {
    let originalTargetPosition = 0;
    for (let i = 0; i < dropChildIndex; i += 1) {
        if (i % columns === finalTargetColumn) {
            originalTargetPosition += 1;
        }
    }

    let targetPosition = originalTargetPosition;
    if (startColumn === finalTargetColumn && start.childIndex > dropChildIndex) {
        targetPosition = Math.max(0, originalTargetPosition - 1);
    }

    let targetIndex;
    if (startColumn === finalTargetColumn && start.childIndex < dropChildIndex) {
        targetIndex = (targetPosition + 1) * columns + finalTargetColumn;
    } else {
        targetIndex = targetPosition * columns + finalTargetColumn;
    }

    if (targetIndex > children.length) {
        const itemsInTargetColumn = countItemsInColumn(children, columns, finalTargetColumn);
        targetIndex = itemsInTargetColumn * columns + finalTargetColumn;
    }

    return targetIndex;
}

function calculateColumnDropTargetIndex(parentChildren, start, dropChildIndex, targetColumnIndex, columns, finalTargetColumn, startColumn) {
    if (isDefined(dropChildIndex)) {
        return calculateTargetIndexAfterRemoval(parentChildren, start, dropChildIndex, columns, finalTargetColumn, startColumn);
    }

    if (isDefined(targetColumnIndex)) {
        const itemsInTargetColumn = countItemsInColumn(parentChildren, columns, targetColumnIndex);
        return itemsInTargetColumn * columns + targetColumnIndex;
    }

    return parentChildren.length;
}

function moveChildInColumnLayout(parent, start, dropChildIndex, targetColumnIndex) {
    const columns = BlockTypes.getColumnCount(parent.type);
    const draggedChild = parent.children[start.childIndex];
    const startColumn = start.childIndex % columns;
    const finalTargetColumn = resolveTargetColumn(columns, startColumn, dropChildIndex, targetColumnIndex);

    if (startColumn === finalTargetColumn && start.childIndex === dropChildIndex) {
        return;
    }

    parent.children.splice(start.childIndex, 1);

    let targetIndex = calculateColumnDropTargetIndex(
        parent.children,
        start,
        dropChildIndex,
        targetColumnIndex,
        columns,
        finalTargetColumn,
        startColumn
    );

    if (targetIndex < 0) {
        targetIndex = finalTargetColumn;
    }

    parent.children.splice(targetIndex, 0, draggedChild);
}

function moveChildInStandardLayout(parent, start, dropChildIndex) {
    if (!isDefined(dropChildIndex) || start.childIndex === dropChildIndex) {
        return;
    }

    const draggedChild = parent.children[start.childIndex];
    parent.children.splice(start.childIndex, 1);
    let newIndex = dropChildIndex;
    if (start.childIndex < dropChildIndex) {
        newIndex = dropChildIndex - 1;
    }
    parent.children.splice(newIndex, 0, draggedChild);
}

export const DragDrop = {
    handleDragStart(event, blocks, index, childIndex = null) {
        let draggingBlockId;

        const dragStartIndex = resolveDragLocation(index, childIndex);
        if (dragStartIndex.type === 'child') {
            // Child-Block wird gezogen
            draggingBlockId = blocks[index].children[childIndex].id;
        } else {
            // Haupt-Block wird gezogen
            draggingBlockId = blocks[index].id;
        }

        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/html', event.target.outerHTML);

        return { dragStartIndex, draggingBlockId };
    },

    handleDragOver(event, index, childIndex = null) {
        event.preventDefault();
        const dragOverIndex = resolveDragLocation(index, childIndex);

        event.dataTransfer.dropEffect = 'move';
        return dragOverIndex;
    },

    handleDrop(event, blocks, dragStartIndex, dropIndex, dropChildIndex = null, targetColumnIndex = null) {
        event.preventDefault();
        
        if (dragStartIndex === null) return;
        
        const start = dragStartIndex;
        
        if (start.type === 'main') {
            // Haupt-Block wird verschoben
            if (dropChildIndex === null) {
                // Zu einem anderen Haupt-Block
                if (start.index !== dropIndex) {
                    const draggedBlock = blocks[start.index];
                    blocks.splice(start.index, 1);
                    blocks.splice(dropIndex, 0, draggedBlock);
                }
            }
        } else if (start.type === 'child') {
            // Child-Block wird verschoben
            if (start.parentIndex === dropIndex) {
                const parent = blocks[start.parentIndex];
                
                // Prüfe ob es ein Spalten-Layout ist
                if (BlockTypes.isColumnLikeBlock(parent.type)) {
                    moveChildInColumnLayout(parent, start, dropChildIndex, targetColumnIndex);
                } else {
                    // Normales Child-Layout (nicht Spalten)
                    moveChildInStandardLayout(parent, start, dropChildIndex);
                }
            }
        }
    }
};
