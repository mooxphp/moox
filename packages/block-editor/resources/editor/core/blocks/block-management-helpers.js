export function createBlockManagementHelpers({ utils, blockTypes, initializeBlock }) {
    function createChildBlock(blockIdCounter, childType) {
        const childBlock = {
            id: utils.generateId(blockIdCounter),
            type: childType,
            content: '',
            style: '',
            classes: '',
            createdAt: new Date().toISOString(),
        };

        // Dynamische Initialisierung über Block-Komponenten
        initializeBlock(childBlock, blockIdCounter);

        // Initialize children array ONLY for container blocks
        // Spalten werden später von ensureColumnStructure() erstellt
        if (blockTypes.isContainerBlock(childType) && !childBlock.children) {
            childBlock.children = [];
        }
        // Non-container blocks should NOT have children array

        return childBlock;
    }

    function touchBlock(block) {
        if (block) {
            block.updatedAt = new Date().toISOString();
        }
    }

    function ensureChildrenArray(block) {
        if (block && !Array.isArray(block.children)) {
            block.children = [];
        }

        return block?.children ?? null;
    }

    function ensureParentCanHaveChildren(parentBlock) {
        if (!parentBlock) {
            return false;
        }

        return blockTypes.canBlockHaveChildren(parentBlock.type);
    }

    return {
        createChildBlock,
        touchBlock,
        ensureChildrenArray,
        ensureParentCanHaveChildren,
    };
}
