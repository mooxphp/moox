import { regenerateBlockIdsRecursive } from '../utils/block-ids.js';

/**
 * Theme-Blöcke klonen und alle IDs neu vergeben (keine Legacy-IDs behalten).
 */
export function cloneAndRemapThemeBlocks(parsedBlocks) {
    return regenerateBlockIdsRecursive(parsedBlocks);
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
