// JSON Renderer - Zentrale Funktion für das Rendering aller Elemente aus dem JSON
import { BlockComponents, getBlockComponent } from '../../components/blocks/index.js';
import { BlockManagement } from '../blocks/management.js';
import { BLOCK_TYPES, BlockTypes } from '../../components/block-types.js';

function createRendererId(prefix) {
    return `${prefix}-${Date.now()}-${Math.random()}`;
}

function renderChildBlock(child, blockIdCounter) {
    if (!child || typeof child !== 'object' || !child.id || !child.type) {
        return null;
    }

    if (BlockTypes.isColumnLikeBlock(child.type)) {
        BlockManagement.ensureColumnStructure([child]);
    }

    const childResult = renderJSONBlocks([child], blockIdCounter);
    return childResult.length > 0 ? childResult[0] : null;
}

function renderNestedChildren(children, blockIdCounter) {
    if (!Array.isArray(children)) {
        return [];
    }

    return children
        .map((child) => renderChildBlock(child, blockIdCounter))
        .filter((child) => child !== null);
}

function ensureBlockComponent(block, blockIdCounter) {
    const component = getBlockComponent(block.type);
    if (component) {
        return component.ensureInitialized(block, blockIdCounter);
    }

    return block;
}

function ensureSpecificComponent(block, blockIdCounter, componentKey, dataKey) {
    if (!block[dataKey]) {
        const component = BlockComponents[componentKey];
        if (component) {
            return component.ensureInitialized(block, blockIdCounter);
        }
    }

    return block;
}

function renderColumnLikeBlock(block, blockIdCounter) {
    if (!Array.isArray(block.children)) {
        block.children = [];
    }

    block.children = block.children.map((column) => {
        if (!column || typeof column !== 'object') {
            return {
                id: createRendererId('col'),
                type: 'column',
                children: [],
            };
        }

        if (!column.id) {
            column.id = createRendererId('col');
        }
        if (!column.type) {
            column.type = 'column';
        }
        if (!Array.isArray(column.children)) {
            column.children = [];
        }

        column.children = renderNestedChildren(column.children, blockIdCounter);

        return column;
    });

    return block;
}

function renderTableBlock(block, blockIdCounter) {
    block = ensureSpecificComponent(block, blockIdCounter, 'table', 'tableData');

    if (!block.tableData) {
        return block;
    }

    if (!Array.isArray(block.tableData.cells)) {
        block.tableData.cells = [];
    }

    block.tableData.cells = block.tableData.cells
        .map((row) => {
            if (!Array.isArray(row)) {
                return [];
            }

            return row
                .map((cell) => {
                    if (!cell || typeof cell !== 'object') {
                        return null;
                    }
                    if (!cell.id) {
                        cell.id = createRendererId('cell');
                    }
                    if (cell.content === undefined) {
                        cell.content = '';
                    }
                    if (!Array.isArray(cell.blocks)) {
                        cell.blocks = [];
                    }
                    if (cell.blocks.length > 0) {
                        cell.blocks = renderJSONBlocks(cell.blocks, blockIdCounter);
                    }
                    if (cell.merged === undefined) {
                        cell.merged = false;
                    }
                    if (cell.colspan === undefined) {
                        cell.colspan = 1;
                    }
                    if (cell.rowspan === undefined) {
                        cell.rowspan = 1;
                    }

                    return cell;
                })
                .filter((cell) => cell !== null);
        })
        .filter((row) => row.length > 0);

    if (block.tableData.hasHeader === undefined) {
        block.tableData.hasHeader = false;
    }
    if (block.tableData.hasFooter === undefined) {
        block.tableData.hasFooter = false;
    }

    return block;
}

function renderChecklistBlock(block, blockIdCounter) {
    block = ensureSpecificComponent(block, blockIdCounter, 'checklist', 'checklistData');

    if (!block.checklistData) {
        return block;
    }

    if (!Array.isArray(block.checklistData.items)) {
        block.checklistData.items = [];
    }

    block.checklistData.items = block.checklistData.items
        .map((item) => {
            if (!item || typeof item !== 'object') {
                return null;
            }
            if (!item.id) {
                item.id = createRendererId('item');
            }
            if (item.text === undefined) {
                item.text = '';
            }
            if (item.checked === undefined) {
                item.checked = false;
            }

            return item;
        })
        .filter((item) => item !== null);

    return block;
}

function renderListBlock(block, blockIdCounter) {
    block = ensureSpecificComponent(block, blockIdCounter, 'list', 'listData');

    if (!block.listData) {
        return block;
    }

    if (!Array.isArray(block.listData.items)) {
        block.listData.items = [];
    }

    block.listData.items = block.listData.items
        .map((item) => {
            if (!item || typeof item !== 'object') {
                return null;
            }
            if (!item.id) {
                item.id = createRendererId('item');
            }
            if (item.text === undefined) {
                item.text = '';
            }

            return item;
        })
        .filter((item) => item !== null);

    if (!block.listData.listStyle) {
        block.listData.listStyle = 'unordered';
    }

    return block;
}

function renderTabsBlock(block, blockIdCounter) {
    block = ensureSpecificComponent(block, blockIdCounter, 'tabs', 'tabsData');

    if (!block.tabsData) {
        return block;
    }

    if (!Array.isArray(block.tabsData.items)) {
        block.tabsData.items = [];
    }

    block.tabsData.items = block.tabsData.items
        .map((item, index) => {
            if (!item || typeof item !== 'object') {
                return null;
            }
            if (!item.id) {
                item.id = createRendererId('tab');
            }
            if (item.title === undefined) {
                item.title = `Tab ${index + 1}`;
            }
            if (item.content === undefined) {
                item.content = '';
            }
            if (!Array.isArray(item.children)) {
                item.children = [];
            }
            if (item.children.length > 0) {
                item.children = renderJSONBlocks(item.children, blockIdCounter);
            }

            return item;
        })
        .filter((item) => item !== null);

    if (block.tabsData.items.length === 0) {
        block.tabsData.items.push({
            id: createRendererId('tab'),
            title: 'Tab 1',
            content: '',
            children: [],
        });
    }

    const activeExists = block.tabsData.items.some((item) => item.id === block.tabsData.activeTabId);
    if (!activeExists) {
        block.tabsData.activeTabId = block.tabsData.items[0]?.id || null;
    }

    return block;
}

function renderAccordionBlock(block, blockIdCounter) {
    block = ensureSpecificComponent(block, blockIdCounter, 'accordion', 'accordionData');

    if (!block.accordionData) {
        return block;
    }

    if (!Array.isArray(block.accordionData.items)) {
        block.accordionData.items = [];
    }

    block.accordionData.items = block.accordionData.items
        .map((item, index) => {
            if (!item || typeof item !== 'object') {
                return null;
            }
            if (!item.id) {
                item.id = createRendererId('accordion');
            }
            if (item.question === undefined) {
                item.question = `Frage ${index + 1}`;
            }
            if (item.answer === undefined) {
                item.answer = '';
            }
            if (item.expanded === undefined) {
                item.expanded = index === 0;
            }
            if (!Array.isArray(item.children)) {
                item.children = [];
            }
            if (item.children.length > 0) {
                item.children = renderJSONBlocks(item.children, blockIdCounter);
            }

            return item;
        })
        .filter((item) => item !== null);

    if (block.accordionData.items.length === 0) {
        block.accordionData.items.push({
            id: createRendererId('accordion'),
            question: 'Frage 1',
            answer: '',
            expanded: true,
            children: [],
        });
    }

    if (block.accordionData.singleOpen === undefined) {
        block.accordionData.singleOpen = true;
    }
    if (!block.accordionData.behavior) {
        block.accordionData.behavior = block.accordionData.singleOpen === false ? 'multiple' : 'single';
    }
    if (block.accordionData.behavior === 'none') {
        block.accordionData.items.forEach((item) => {
            item.expanded = false;
        });
    }

    return block;
}

function renderDefaultBlock(block, blockIdCounter) {
    if (Array.isArray(block.children)) {
        block.children = renderNestedChildren(block.children, blockIdCounter);
    }

    return block;
}

const BLOCK_RENDERERS = {
    table: renderTableBlock,
    checklist: renderChecklistBlock,
    list: renderListBlock,
    tabs: renderTabsBlock,
    accordion: renderAccordionBlock,
};

/**
 * Zentrale Funktion zum Rendering aller Elemente aus dem JSON
 * Diese Funktion stellt sicher, dass alle Block-Typen und verschachtelten Strukturen korrekt initialisiert sind
 * Es gibt nur diese EINE Rendering-Funktion - alle Logik ist hier integriert
 * 
 * @param {Array} blocks - Array von Block-Objekten aus dem JSON
 * @param {number} blockIdCounter - Aktueller Block-ID Counter
 * @returns {Array} - Bereinigte und initialisierte Blöcke
 */
export function renderJSONBlocks(blocks, blockIdCounter = 0) {
    if (!Array.isArray(blocks)) {
        console.error('renderJSONBlocks: blocks muss ein Array sein');
        return [];
    }

    // Rendere alle Blöcke rekursiv
    const renderedBlocks = blocks.map((block) => {
        // Validiere Block
        if (!block || typeof block !== 'object') {
            console.warn('renderJSONBlocks: Ungültiger Block gefunden');
            return null;
        }

        if (!block.id) {
            console.warn('renderJSONBlocks: Block ohne ID gefunden');
            return null;
        }

        if (!block.type) {
            console.warn('renderJSONBlocks: Block ohne Typ gefunden', block.id);
            return null;
        }

        // Validiere Block-Typ
        if (!BLOCK_TYPES[block.type]) {
            console.warn(`renderJSONBlocks: Unbekannter Block-Typ "${block.type}"`, block.id);
            block.type = 'paragraph';
        }

        // Initialisiere Block-Komponente
        block = ensureBlockComponent(block, blockIdCounter);

        // Stelle sicher, dass Column-Struktur korrekt ist (BEVOR rekursive Verarbeitung)
        if (BlockTypes.isColumnLikeBlock(block.type)) {
            BlockManagement.ensureColumnStructure([block]);
        }

        // Rendere verschachtelte Strukturen basierend auf Block-Typ
        if (BlockTypes.isColumnLikeBlock(block.type)) {
            block = renderColumnLikeBlock(block, blockIdCounter);
        } else {
            const renderer = BLOCK_RENDERERS[block.type] ?? renderDefaultBlock;
            block = renderer(block, blockIdCounter);
        }

        return block;
    }).filter((block) => block !== null);
    
    // Stelle sicher, dass alle Column-Strukturen korrekt sind (auch verschachtelte)
    BlockManagement.ensureColumnStructure(renderedBlocks);

    return renderedBlocks;
}

/**
 * Validiert die Struktur eines Blocks
 * 
 * @param {Object} block - Block-Objekt
 * @returns {boolean} - true wenn Block gültig ist
 */
export function validateBlock(block) {
    if (!block || typeof block !== 'object') {
        return false;
    }

    if (!block.id || typeof block.id !== 'string') {
        return false;
    }

    if (!block.type || typeof block.type !== 'string') {
        return false;
    }

    // Prüfe ob Block-Typ existiert
    if (!BLOCK_TYPES[block.type]) {
        return false;
    }

    return true;
}

/**
 * Validiert die Struktur aller Blöcke in einem Array
 * 
 * @param {Array} blocks - Array von Block-Objekten
 * @returns {Object} - { valid: boolean, errors: Array }
 */
export function validateBlocks(blocks) {
    if (!Array.isArray(blocks)) {
        return {
            valid: false,
            errors: ['blocks muss ein Array sein']
        };
    }

    const errors = [];
    
    blocks.forEach((block, index) => {
        if (!validateBlock(block)) {
            errors.push(`Block ${index + 1} ist ungültig: fehlende oder ungültige id/type`);
        }
    });

    return {
        valid: errors.length === 0,
        errors: errors
    };
}
