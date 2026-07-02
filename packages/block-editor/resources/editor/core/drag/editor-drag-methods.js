import { DragDrop } from './drag-drop.js';

export const editorDragMethods = {
    handleDragStart(event, index, columnIndex = null, childIndex = null) {
        // If columnIndex is provided, we're dealing with column children
        if (columnIndex !== null && columnIndex !== undefined) {
            const block = this.blocks[index];
            if (block && block.children && block.children[columnIndex]) {
                const columnBlock = block.children[columnIndex];
                if (columnBlock.children && columnBlock.children[childIndex]) {
                    const childBlock = columnBlock.children[childIndex];
                    this.dragStartIndex = {
                        type: 'columnChild',
                        parentIndex: index,
                        columnIndex: columnIndex,
                        childIndex: childIndex,
                    };
                    this.draggingBlockId = childBlock.id;
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/html', event.target.outerHTML);
                    return;
                }
            }
        }
        // Fallback to original logic
        const result = DragDrop.handleDragStart(event, this.blocks, index, childIndex);
        this.dragStartIndex = result.dragStartIndex;
        this.draggingBlockId = result.draggingBlockId;
    },

    handleDragOver(event, index, columnIndex = null, childIndex = null) {
        // If columnIndex is provided, we're dealing with column children
        if (columnIndex !== null && columnIndex !== undefined) {
            this.dragOverIndex = {
                type: 'columnChild',
                parentIndex: index,
                columnIndex: columnIndex,
                childIndex: childIndex,
            };
            event.dataTransfer.dropEffect = 'move';
            return;
        }
        // Fallback to original logic
        this.dragOverIndex = DragDrop.handleDragOver(event, index, childIndex);
    },

    handleColumnChildDragStart(event, parentBlockId, columnIndex, childIndex) {
        const { block: parentBlock } = this.findBlockById(parentBlockId);
        if (!parentBlock || !parentBlock.children || !parentBlock.children[columnIndex]) return;

        const columnBlock = parentBlock.children[columnIndex];
        if (!columnBlock.children || !columnBlock.children[childIndex]) return;

        const childBlock = columnBlock.children[childIndex];
        this.dragStartIndex = {
            type: 'columnChild',
            parentBlockId: parentBlockId,
            columnIndex: columnIndex,
            childIndex: childIndex,
        };
        this.draggingBlockId = childBlock.id;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/html', event.target.outerHTML);
    },

    handleColumnChildDragOver(event, parentBlockId, columnIndex, childIndex) {
        this.dragOverIndex = {
            type: 'columnChild',
            parentBlockId: parentBlockId,
            columnIndex: columnIndex,
            childIndex: childIndex,
        };
        event.dataTransfer.dropEffect = 'move';
    },

    handleColumnChildDrop(event, parentBlockId, dropColumnIndex = null, dropChildIndex = null) {
        event.preventDefault();

        if (!this.dragStartIndex || this.dragStartIndex.type !== 'columnChild') return;

        if (this.dragStartIndex.parentBlockId && this.dragStartIndex.parentBlockId !== parentBlockId) {
            return;
        }

        this.handleDrop(event, null, dropColumnIndex, dropChildIndex);
    },

    handleDrop(event, dropIndex, dropColumnIndex = null, dropChildIndex = null) {
        event.preventDefault();

        if (this.dragStartIndex === null) return;

        // Handle column child drag & drop
        if (this.dragStartIndex.type === 'columnChild') {
            const start = this.dragStartIndex;
            let parentBlock = null;
            if (start.parentBlockId) {
                parentBlock = this.findBlockById(start.parentBlockId).block;
            } else if (start.parentIndex !== null && start.parentIndex !== undefined) {
                parentBlock = this.blocks[start.parentIndex];
            }

            if (parentBlock && parentBlock.children) {
                const startColumn = parentBlock.children[start.columnIndex];
                const finalDropColumnIndex = dropColumnIndex !== null && dropColumnIndex !== undefined
                    ? dropColumnIndex
                    : start.columnIndex;
                const dropColumn = parentBlock.children[finalDropColumnIndex];

                if (startColumn && startColumn.children && dropColumn && dropColumn.children) {
                    const draggedChild = startColumn.children[start.childIndex];

                    // Remove from start position
                    startColumn.children.splice(start.childIndex, 1);

                    // Insert at drop position
                    if (dropChildIndex !== null && dropChildIndex !== undefined) {
                        let insertIndex = dropChildIndex;
                        // Adjust index if moving within same column and moving down
                        if (start.columnIndex === finalDropColumnIndex && start.childIndex < dropChildIndex) {
                            insertIndex = dropChildIndex - 1;
                        }
                        dropColumn.children.splice(insertIndex, 0, draggedChild);
                    } else {
                        // Append to end
                        dropColumn.children.push(draggedChild);
                    }

                    parentBlock.updatedAt = new Date().toISOString();
                    this.invalidateRenderCache(parentBlock.id);
                    this.invalidateJSONDisplayCache();
                }
            }
        } else {
            // Fallback to original logic
            DragDrop.handleDrop(event, this.blocks, this.dragStartIndex, dropIndex, dropChildIndex, dropColumnIndex, null);
        }

        this.dragStartIndex = null;
        this.dragOverIndex = null;
    },

    handleDragEnd() {
        this.draggingBlockId = null;
        this.dragStartIndex = null;
        this.dragOverIndex = null;
    },
};
