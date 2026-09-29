import { BlockTypes } from '../../components/block-types.js';
import { createUniqueId } from './format.js';

function createGeneratedId(prefix = 'block') {
    return createUniqueId(prefix);
}

function cloneJsonSerializable(value) {
    return JSON.parse(JSON.stringify(value));
}

function assignGeneratedIdsToItems(items, prefix = 'item') {
    if (!Array.isArray(items)) {
        return items;
    }

    return items.map((item) => {
        if (!item || typeof item !== 'object') {
            return item;
        }

        return {
            ...item,
            id: createGeneratedId(prefix),
        };
    });
}

/**
 * Ersetzt alle Block-/Child-IDs kryptografisch sicher.
 * Verhindert XSS über attacker-kontrollierte IDs bei Theme-Load/Import.
 */
export function regenerateBlockIdsRecursive(rawBlocks) {
    if (!Array.isArray(rawBlocks)) {
        return [];
    }

    const blocks = cloneJsonSerializable(rawBlocks);

    const normalizeBlocks = (list) => list
        .map((entry) => {
            if (!entry || typeof entry !== 'object') {
                return null;
            }

            const block = entry;
            block.id = createGeneratedId('block');

            if (BlockTypes.isColumnLikeBlock(block.type)) {
                block.children = Array.isArray(block.children)
                    ? block.children.map((column) => {
                        if (!column || typeof column !== 'object') {
                            return {
                                id: createGeneratedId('col'),
                                type: 'column',
                                children: [],
                            };
                        }

                        const normalizedColumn = {
                            ...column,
                            id: createGeneratedId('col'),
                            type: 'column',
                        };

                        normalizedColumn.children = normalizeBlocks(
                            Array.isArray(normalizedColumn.children) ? normalizedColumn.children : []
                        );

                        return normalizedColumn;
                    })
                    : [];
            } else if (block.type === 'tabs' && block.tabsData && typeof block.tabsData === 'object') {
                const tabsData = block.tabsData;
                const previousActiveId = tabsData.activeTabId ?? null;
                const tabIdMap = new Map();

                tabsData.items = Array.isArray(tabsData.items)
                    ? tabsData.items
                        .map((item) => {
                            if (!item || typeof item !== 'object') {
                                return null;
                            }

                            const originalTabId = item.id ?? null;
                            const newTabId = createGeneratedId('tab');

                            if (originalTabId !== null && originalTabId !== undefined && originalTabId !== '') {
                                tabIdMap.set(String(originalTabId), newTabId);
                            }

                            const normalizedItem = {
                                ...item,
                                id: newTabId,
                            };

                            normalizedItem.children = normalizeBlocks(
                                Array.isArray(normalizedItem.children) ? normalizedItem.children : []
                            );

                            return normalizedItem;
                        })
                        .filter((item) => item !== null)
                    : [];

                if (previousActiveId !== null && previousActiveId !== undefined && tabIdMap.has(String(previousActiveId))) {
                    tabsData.activeTabId = tabIdMap.get(String(previousActiveId));
                } else {
                    tabsData.activeTabId = tabsData.items[0]?.id ?? null;
                }
            } else if (Array.isArray(block.children)) {
                block.children = normalizeBlocks(block.children);
            }

            if (block.tableData?.cells && Array.isArray(block.tableData.cells)) {
                block.tableData.cells = block.tableData.cells.map((row) => {
                    if (!Array.isArray(row)) {
                        return [];
                    }

                    return row.map((cell) => {
                        if (!cell || typeof cell !== 'object') {
                            return cell;
                        }

                        const normalizedCell = {
                            ...cell,
                            id: createGeneratedId('cell'),
                        };

                        if (Array.isArray(normalizedCell.blocks)) {
                            normalizedCell.blocks = normalizeBlocks(normalizedCell.blocks);
                        }

                        return normalizedCell;
                    });
                });
            }

            if (Array.isArray(block.checklistData?.items)) {
                block.checklistData.items = assignGeneratedIdsToItems(block.checklistData.items);
            }

            if (Array.isArray(block.listData?.items)) {
                block.listData.items = assignGeneratedIdsToItems(block.listData.items);
            }

            return block;
        })
        .filter((entry) => entry !== null);

    return normalizeBlocks(blocks);
}
