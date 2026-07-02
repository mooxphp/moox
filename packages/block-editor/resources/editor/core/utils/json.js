// Data/JSON Utilities
export function findBlockById(blocks, blockId) {
    // Suche in Haupt-Blöcken
    let block = blocks.find(b => b.id === blockId);
    if (block) return { block, parent: null, cellContext: null };
    
    // Rekursive Suche in verschachtelten Strukturen (Children)
    function searchInChildren(children, parent) {
        if (!children) return null;
        
        for (let child of children) {
            if (child.id === blockId) {
                return { block: child, parent: parent, cellContext: null };
            }
            
            if (child.children) {
                const result = searchInChildren(child.children, child);
                if (result) return result;
            }
        }
        return null;
    }
    
    // Suche in Table-Zellen (cell.blocks); cellContext bleibt für alle gefundenen Blöcke in dieser Zelle erhalten
    function searchInTableCellBlocks(cellBlocks, tableBlock, cell) {
        if (!cellBlocks || !Array.isArray(cellBlocks)) return null;
        for (let i = 0; i < cellBlocks.length; i++) {
            const b = cellBlocks[i];
            if (!b) continue;
            if (b.id === blockId) {
                return {
                    block: b,
                    parent: tableBlock,
                    cellContext: { cell, cellId: cell.id, blockIndex: i }
                };
            }
            if (b.children && b.children.length) {
                const result = searchInChildren(b.children, b);
                if (result) {
                    return { ...result, cellContext: { cell, cellId: cell.id } };
                }
            }
        }
        return null;
    }
    function searchInTableCells(tableBlock) {
        const cells = tableBlock?.tableData?.cells;
        if (!cells || !Array.isArray(cells)) return null;
        for (let rowIndex = 0; rowIndex < cells.length; rowIndex++) {
            const row = cells[rowIndex];
            if (!Array.isArray(row)) continue;
            for (let colIndex = 0; colIndex < row.length; colIndex++) {
                const cell = row[colIndex];
                if (!cell) continue;
                const result = searchInTableCellBlocks(cell.blocks, tableBlock, cell);
                if (result) return result;
            }
        }
        return null;
    }

    function searchInTabsItems(tabsBlock) {
        const tabs = tabsBlock?.tabsData?.items;
        if (!Array.isArray(tabs)) return null;
        for (const tab of tabs) {
            if (!tab || !Array.isArray(tab.children)) continue;
            const result = searchInChildren(tab.children, tab);
            if (result) return result;
        }
        return null;
    }
    function searchInAccordionItems(accordionBlock) {
        const items = accordionBlock?.accordionData?.items;
        if (!Array.isArray(items)) return null;
        for (const item of items) {
            if (!item || !Array.isArray(item.children)) continue;
            const result = searchInChildren(item.children, item);
            if (result) return result;
        }
        return null;
    }
    
    for (let parentBlock of blocks) {
        if (parentBlock.children) {
            const result = searchInChildren(parentBlock.children, parentBlock);
            if (result) return result;
        }
        if (parentBlock.type === 'table') {
            const result = searchInTableCells(parentBlock);
            if (result) return result;
        }
        if (parentBlock.type === 'tabs') {
            const result = searchInTabsItems(parentBlock);
            if (result) return result;
        }
        if (parentBlock.type === 'accordion') {
            const result = searchInAccordionItems(parentBlock);
            if (result) return result;
        }
    }
    
    return { block: null, parent: null, cellContext: null };
}

export function getAllBlocks(blocks) {
    const allBlocks = [];
    const addBlockRecursive = (block) => {
        if (!block) return;
        allBlocks.push(block);
        if (block.children && Array.isArray(block.children)) {
            block.children.forEach(child => addBlockRecursive(child));
        }
    };
    const addTableCellBlocks = (tableBlock) => {
        const cells = tableBlock?.tableData?.cells;
        if (!cells || !Array.isArray(cells)) return;
        cells.forEach(row => {
            if (!Array.isArray(row)) return;
            row.forEach(cell => {
                if (cell?.blocks && Array.isArray(cell.blocks)) {
                    cell.blocks.forEach(b => addBlockRecursive(b));
                }
            });
        });
    };
    const addTabsChildren = (tabsBlock) => {
        const tabs = tabsBlock?.tabsData?.items;
        if (!Array.isArray(tabs)) return;
        tabs.forEach(tab => {
            if (Array.isArray(tab?.children)) {
                tab.children.forEach(child => addBlockRecursive(child));
            }
        });
    };
    const addAccordionChildren = (accordionBlock) => {
        const items = accordionBlock?.accordionData?.items;
        if (!Array.isArray(items)) return;
        items.forEach(item => {
            if (Array.isArray(item?.children)) {
                item.children.forEach(child => addBlockRecursive(child));
            }
        });
    };
    if (Array.isArray(blocks)) {
        blocks.forEach(block => {
            addBlockRecursive(block);
            if (block.type === 'table') addTableCellBlocks(block);
            if (block.type === 'tabs') addTabsChildren(block);
            if (block.type === 'accordion') addAccordionChildren(block);
        });
    }
    return allBlocks;
}
