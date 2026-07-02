export function createChildManagement({
    findBlockById,
    isColumnLikeBlock,
    ensureParentCanHaveChildren,
    ensureChildrenArray,
    createChildBlock,
    touchBlock
}) {
    return {
        addChild(blocks, parentBlockId, blockIdCounter, childType) {
            const { block: parentBlock } = findBlockById(blocks, parentBlockId);
            if (!ensureParentCanHaveChildren(parentBlock)) {
                return null;
            }

            const children = ensureChildrenArray(parentBlock);
            const childBlock = createChildBlock(blockIdCounter, childType);

            children.push(childBlock);
            touchBlock(parentBlock);

            return childBlock;
        },

        addChildAfter(blocks, parentBlockId, childIndex, blockIdCounter, childType) {
            const { block: parentBlock } = findBlockById(blocks, parentBlockId);
            if (!ensureParentCanHaveChildren(parentBlock) || !Array.isArray(parentBlock?.children)) {
                return null;
            }

            const childBlock = createChildBlock(blockIdCounter, childType);
            parentBlock.children.splice(childIndex + 1, 0, childBlock);
            touchBlock(parentBlock);

            return childBlock;
        },

        moveChildBlock(blocks, parentBlockId, childIndex, direction, columnIndex = null) {
            const { block: parentBlock } = findBlockById(blocks, parentBlockId);
            if (!parentBlock) return;

            let targetChildren = null;
            if (columnIndex !== null && columnIndex !== undefined) {
                if (parentBlock.type === 'column') {
                    targetChildren = parentBlock.children;
                } else if (parentBlock.children && parentBlock.children[columnIndex]) {
                    targetChildren = parentBlock.children[columnIndex].children;
                }
            } else {
                targetChildren = parentBlock.children;
            }

            if (!Array.isArray(targetChildren)) return;

            if (direction === 'up' && childIndex > 0) {
                [targetChildren[childIndex - 1], targetChildren[childIndex]] =
                [targetChildren[childIndex], targetChildren[childIndex - 1]];
            } else if (direction === 'down' && childIndex < targetChildren.length - 1) {
                [targetChildren[childIndex], targetChildren[childIndex + 1]] =
                [targetChildren[childIndex + 1], targetChildren[childIndex]];
            } else {
                return;
            }

            touchBlock(parentBlock);
        },

        addChildToColumn(blocks, parentBlockId, blockIdCounter, childType, columnIndex) {
            const { block: parentBlock } = findBlockById(blocks, parentBlockId);
            if (!parentBlock) {
                return null;
            }

            // If parent is already a column block, add child directly to it
            if (parentBlock.type === 'column') {
                // Column-Blöcke können immer Kinder haben, keine Prüfung nötig
                const children = ensureChildrenArray(parentBlock);

                // Create the new child block
                const childBlock = createChildBlock(blockIdCounter, childType);

                // Add the child block to the column's children
                children.push(childBlock);
                touchBlock(parentBlock);

                return childBlock;
            }

            // If parent is column-like container (e.g. twoColumn, threeColumn, group, callout), use existing logic
            if (isColumnLikeBlock(parentBlock.type)) {
                // Prüfe ob der Parent-Block Kinder haben darf
                if (!ensureParentCanHaveChildren(parentBlock)) {
                    return null;
                }

                // Ensure children array exists (Spalten werden später von ensureColumnStructure() erstellt)
                const parentChildren = ensureChildrenArray(parentBlock);

                // Find the column block at the specified index
                const columnBlock = parentChildren[columnIndex];
                if (!columnBlock) {
                    return null;
                }

                // Ensure column block has children array
                const columnChildren = ensureChildrenArray(columnBlock);

                // Create the new child block
                const childBlock = createChildBlock(blockIdCounter, childType);

                // Add the child block to the column's children
                columnChildren.push(childBlock);
                touchBlock(parentBlock);

                return childBlock;
            }

            return null;
        }
    };
}
