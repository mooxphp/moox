function updateBlockIds(block, baseTime, state, parentNewId = null) {
    const isBlockId = /^block-\d+-\d+$/.test(block.id) || /^\d+$/.test(block.id);

    if (isBlockId) {
        const newId = String(baseTime + state.idCounter++);
        block.id = newId;
        if (block.children && Array.isArray(block.children)) {
            block.children.forEach((child) => updateBlockIds(child, baseTime, state, newId));
        }
        return;
    }

    if (block.id.startsWith('col-') && parentNewId !== null) {
        const parts = block.id.split('-');
        const columnIndex = parts[parts.length - 1];
        block.id = `col-${parentNewId}-${columnIndex}`;
        if (block.children && Array.isArray(block.children)) {
            block.children.forEach((child) => updateBlockIds(child, baseTime, state, parentNewId));
        }
        return;
    }

    if (block.children && Array.isArray(block.children)) {
        block.children.forEach((child) => updateBlockIds(child, baseTime, state, parentNewId));
    }
}

export function cloneAndRemapThemeBlocks(parsedBlocks, baseTime = Date.now()) {
    const state = { idCounter: 0 };

    return parsedBlocks.map((block) => {
        const newBlock = JSON.parse(JSON.stringify(block));
        updateBlockIds(newBlock, baseTime, state);
        return newBlock;
    });
}

export function getThemeBlocksByName(themes, themeName) {
    const theme = themes.find((entry) => entry.name.toLowerCase() === themeName.toLowerCase());

    if (!theme) {
        throw new Error(`Theme "${themeName}" nicht gefunden.`);
    }

    if (!theme.data || !Array.isArray(theme.data)) {
        throw new Error(`Theme-Daten für "${themeName}" nicht verfügbar.`);
    }

    return theme.data;
}

export function insertExtendedThemeBlocks(blocks, renderedBlocks, selectedBlockId, findBlockById) {
    if (!selectedBlockId) {
        blocks.push(...renderedBlocks);
        return;
    }

    const { block: selectedBlock, parent } = findBlockById(blocks, selectedBlockId);

    if (selectedBlock && parent && Array.isArray(parent.children)) {
        const childIndex = parent.children.findIndex((child) => child.id === selectedBlockId);
        if (childIndex !== -1) {
            parent.children.splice(childIndex + 1, 0, ...renderedBlocks);
            parent.updatedAt = new Date().toISOString();
            return;
        }
    }

    if (selectedBlock) {
        const index = blocks.findIndex((block) => block.id === selectedBlockId);
        if (index !== -1) {
            blocks.splice(index + 1, 0, ...renderedBlocks);
            return;
        }
    }

    blocks.push(...renderedBlocks);
}
