import { TableManagement } from '../blocks/management.js';
import {
    focusTableCellContent as focusTableCellContentHelper,
    getLastVisibleTableRowIndex as getLastVisibleTableRowIndexHelper,
    getVisibleTableCellElement as getVisibleTableCellElementHelper,
    getVisibleTableCellElementsInRow as getVisibleTableCellElementsInRowHelper,
} from './navigation-dom.js';
import {
    addTableColumnAction,
    addTableRowAction,
    mergeTableCellsAction,
    removeTableColumnAction,
    removeTableRowAction,
    toggleTableFooterAction,
    toggleTableHeaderAction,
    unmergeTableCellsAction,
} from './actions.js';
import { commitTableCellContentAction } from './cell-commit.js';
import { queueTableCellContentUpdate } from './cell-update.js';
import { handleTableCellTabFlow } from './tab-flow.js';

export const editorTableMethods = {
    addBlockToTableCell(tableBlockId, cellId, type = 'paragraph', content = '') {
        if (!this.addComponentsEnabled) {
            return;
        }
        const tableBlock = this.blocks.find(b => b.id === tableBlockId);
        if (!tableBlock || tableBlock.type !== 'table') return;
        this.blockIdCounter++;
        const block = TableManagement.addBlockToTableCell(this.blocks, tableBlockId, cellId, this.blockIdCounter, type, content, -1);
        if (block) {
            this.selectedBlockId = block.id;
            this.closeBlockToolbar();
            this.invalidateJSONDisplayCache();
            this.invalidateBlockLookupCache();
            this.invalidateRenderCache(tableBlockId);
            this.$nextTick(() => this.focusBlockElement(block.id));
        }
    },

    addBlockInTableCellAfter(blockId, cellId, afterBlockIndex) {
        if (!this.addComponentsEnabled) {
            return;
        }
        const tableBlock = this.blocks.find(b => b.id === blockId);
        if (!tableBlock || tableBlock.type !== 'table') return;
        this.blockIdCounter++;
        const block = TableManagement.addBlockToTableCell(this.blocks, blockId, cellId, this.blockIdCounter, 'paragraph', '', afterBlockIndex);
        if (block) {
            this.selectedBlockId = block.id;
            this.invalidateJSONDisplayCache();
            this.invalidateBlockLookupCache();
            this.invalidateRenderCache(blockId);
            this.$nextTick(() => this.focusBlockElement(block.id));
        }
    },

    removeBlockFromTableCell(blockId, cellId, blockIndex) {
        const removed = TableManagement.removeBlockFromTableCell(this.blocks, blockId, cellId, blockIndex);
        if (removed) {
            this.invalidateBlockLookupCache();
            this.invalidateRenderCache(blockId);
            this.invalidateJSONDisplayCache();
            const tableBlock = this.blocks.find(b => b.id === blockId);
            if (tableBlock?.tableData?.cells) {
                const found = TableManagement.findTableCell(this.blocks, blockId, cellId);
                if (found && found.cell.blocks && found.cell.blocks.length > 0) {
                    this.selectedBlockId = found.cell.blocks[Math.min(blockIndex, found.cell.blocks.length - 1)].id;
                } else {
                    this.selectedBlockId = blockId;
                }
            }
        }
    },

    moveBlockInTableCell(blockId, cellId, blockIndex, direction) {
        TableManagement.moveBlockInTableCell(this.blocks, blockId, cellId, blockIndex, direction);
        this.invalidateRenderCache(blockId);
    },

    /**
     * Performance: Gibt gefilterte Header-Rows für Tabellen zurück
     * Wird gecacht, um .filter() nicht bei jedem Re-Render auszuführen
     * @param {object} block - Der Tabellen-Block
     * @returns {Array} Gefilterte Header-Rows
     */
    getTableHeaderRows(block) {
        if (!block || !block.tableData || !block.tableData.cells) return [];
        return block.tableData.cells.filter(r => r && r[0] && r[0].isHeader);
    },

    /**
     * Performance: Gibt gefilterte Body-Rows für Tabellen zurück
     * Wird gecacht, um .filter() nicht bei jedem Re-Render auszuführen
     * @param {object} block - Der Tabellen-Block
     * @returns {Array} Gefilterte Body-Rows
     */
    getTableBodyRows(block) {
        if (!block || !block.tableData || !block.tableData.cells) return [];
        return block.tableData.cells.filter(r => r && r[0] && !r[0].isHeader && !r[0].isFooter);
    },

    /**
     * Performance: Gibt gefilterte Footer-Rows für Tabellen zurück
     * Wird gecacht, um .filter() nicht bei jedem Re-Render auszuführen
     * @param {object} block - Der Tabellen-Block
     * @returns {Array} Gefilterte Footer-Rows
     */
    getTableFooterRows(block) {
        if (!block || !block.tableData || !block.tableData.cells) return [];
        return block.tableData.cells.filter(r => r && r[0] && r[0].isFooter);
    },

    /**
     * Tabulator-Navigation innerhalb von Tabellenzellen:
     * - Tab -> gleiche Zeile, nächste Spalte (rechts)
     * - Shift+Tab -> gleiche Zeile, vorherige Spalte (links)
     */
    handleTableCellTabNavigation(event, tableBlockId, rowIndex, colIndex, section) {
        handleTableCellTabFlow({
            event,
            tableBlockId,
            rowIndex,
            colIndex,
            section,
            getVisibleTableCellElementsInRow: this.getVisibleTableCellElementsInRow.bind(this),
            commitTableCellContent: this.commitTableCellContent.bind(this),
            nextTick: this.$nextTick.bind(this),
            focusTableCellContent: this.focusTableCellContent.bind(this),
        });
    },

    getVisibleTableCellElement(tableBlockId, section, rowIndex, colIndex) {
        return getVisibleTableCellElementHelper(tableBlockId, section, rowIndex, colIndex);
    },

    getLastVisibleTableRowIndex(tableBlockId, section, colIndex) {
        return getLastVisibleTableRowIndexHelper(tableBlockId, section, colIndex);
    },

    getVisibleTableCellElementsInRow(tableBlockId, section, rowIndex) {
        return getVisibleTableCellElementsInRowHelper(tableBlockId, section, rowIndex);
    },

    focusTableCellContent(cellEl) {
        focusTableCellContentHelper(cellEl);
    },

    addTableRow(blockId, position = 'bottom') {
        addTableRowAction({
            tableManagement: TableManagement,
            blocks: this.blocks,
            blockId,
            blockIdCounter: this.blockIdCounter,
            position,
            onCounterUpdate: (nextCounter) => {
                this.blockIdCounter = nextCounter;
            },
            onInvalidateSettings: this.invalidateBlockSettingsCache.bind(this),
        });
    },

    removeTableRow(blockId, rowIndex) {
        removeTableRowAction({
            tableManagement: TableManagement,
            blocks: this.blocks,
            blockId,
            rowIndex,
            onInvalidateSettings: this.invalidateBlockSettingsCache.bind(this),
        });
    },

    addTableColumn(blockId, position = 'right') {
        addTableColumnAction({
            tableManagement: TableManagement,
            blocks: this.blocks,
            blockId,
            blockIdCounter: this.blockIdCounter,
            position,
            onCounterUpdate: (nextCounter) => {
                this.blockIdCounter = nextCounter;
            },
            onInvalidateSettings: this.invalidateBlockSettingsCache.bind(this),
        });
    },

    removeTableColumn(blockId, colIndex) {
        removeTableColumnAction({
            tableManagement: TableManagement,
            blocks: this.blocks,
            blockId,
            colIndex,
            onInvalidateSettings: this.invalidateBlockSettingsCache.bind(this),
        });
    },

    mergeTableCells(blockId, startRow, startCol, endRow, endCol) {
        mergeTableCellsAction({
            tableManagement: TableManagement,
            blocks: this.blocks,
            blockId,
            startRow,
            startCol,
            endRow,
            endCol,
            onInvalidateSettings: this.invalidateBlockSettingsCache.bind(this),
        });
    },

    unmergeTableCells(blockId, row, col) {
        unmergeTableCellsAction({
            tableManagement: TableManagement,
            blocks: this.blocks,
            blockId,
            row,
            col,
        });
    },

    updateTableCellContent(blockId, cellId, content) {
        queueTableCellContentUpdate({
            blockId,
            cellId,
            content,
            queueInlineContentUpdate: this.queueInlineContentUpdate.bind(this),
            applyUpdate: (latestContent) => {
                TableManagement.updateTableCellContent(this.blocks, blockId, cellId, latestContent);
            },
        });
    },

    commitTableCellContent(blockId, cellId, content) {
        const key = `table:${blockId}:${cellId}`;
        const pending = this.inlineContentBuffer.get(key);
        this.clearInlineContentUpdate(key);
        commitTableCellContentAction({
            tableManagement: TableManagement,
            blocks: this.blocks,
            blockId,
            cellId,
            content,
            pendingContent: pending ? pending.content : null,
            findBlockById: this.findBlockById.bind(this),
            updateTableCellContent: TableManagement.updateTableCellContent,
            onBlockUpdated: (block) => {
                block.updatedAt = new Date().toISOString();
                this.invalidateRenderCache(blockId);
                this.invalidateJSONDisplayCache();
            },
            onNoChange: () => {
                this.invalidateJSONDisplayCache();
            },
        });
    },

    toggleTableHeader(blockId) {
        toggleTableHeaderAction({
            tableManagement: TableManagement,
            blocks: this.blocks,
            blockId,
            onInvalidateSettings: this.invalidateBlockSettingsCache.bind(this),
        });
    },

    toggleTableFooter(blockId) {
        toggleTableFooterAction({
            tableManagement: TableManagement,
            blocks: this.blocks,
            blockId,
            onInvalidateSettings: this.invalidateBlockSettingsCache.bind(this),
        });
    },
};
