/**
 * Table Block Component
 * Enthält alle Informationen für Table-Blöcke an einem Ort
 */
import { BLOCK_TYPES } from '../../block-types.js';
import { TableManagement } from '../../../core/blocks/management.js';

export const TableBlock = {
    type: 'table',
    
    // Konfiguration direkt aus BLOCK_TYPES
    options: BLOCK_TYPES.table,
    
    // Datenstruktur-Definition
    structure: {
        id: '',
        type: 'table',
        tableData: null,
        style: '',
        classes: '',
        htmlId: '',
        createdAt: '',
        updatedAt: ''
    },
    
    // HTML-Template für Rendering
    renderHTML(block, context = {}) {
        return this.renderTableHTML('block', block);
    },
    
    // Child-Version (für verschachtelte Tabellen)
    renderChildHTML(child, context = {}) {
        return this.renderTableHTML('child', child);
    },
    
    renderTableHTML(scope, data) {
        const blockId = data.id || '';
        
        return `
            <div x-show="${scope}.type === 'table'" class="overflow-x-auto">
                <table 
                    data-block-id="${blockId}"
                    :id="${scope}.htmlId || null"
                    :style="${scope}.style || ''"
                    :class="['w-full border-collapse border border-gray-300', ${scope}.classes || '']"
                >
                    <!-- Header -->
                    <thead x-show="${scope}.tableData && ${scope}.tableData.hasHeader">
                        <template x-for="(row, rowIndex) in getTableHeaderRows(${scope})" :key="rowIndex">
                            <tr>
                                <template x-for="(cell, colIndex) in row" :key="cell.id">
                                    <th 
                                        x-show="!cell.merged"
                                        :colspan="cell.colspan > 1 ? cell.colspan : null"
                                        :rowspan="cell.rowspan > 1 ? cell.rowspan : null"
                                        :data-cell-id="cell.id"
                                        data-block-id="${blockId}"
                                        :data-row-index="rowIndex"
                                        :data-col-index="colIndex"
                                        class="border border-gray-300 p-2 bg-gray-100 font-semibold text-left min-w-[100px] min-h-[40px] align-top"
                                    >
                                        <template x-if="(cell.blocks || []).length > 0">
                                            <div class="table-cell-blocks space-y-1 min-h-[36px]">
                                                <template x-for="(cellBlock, bi) in (cell.blocks || [])" :key="cellBlock.id">
                                                    <div class="relative group/cb rounded px-1"
                                                         :class="{ 'ring-1 ring-blue-500 bg-blue-50/50': selectedBlockId === cellBlock.id }"
                                                         @click.stop="selectBlock(cellBlock.id)">
                                                        <div class="cellblock-content" x-html="renderBlock(cellBlock)"
                                                             x-init="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"></div>
                                                        <div class="absolute right-0 top-0 opacity-0 group-hover/cb:opacity-100 flex gap-0.5 text-xs">
                                                            <button type="button" @click.stop="addBlockInTableCellAfter('${blockId}', cell.id, bi)" title="Block einfügen">+</button>
                                                            <button type="button" @click.stop="removeBlockFromTableCell('${blockId}', cell.id, bi)" title="Entfernen">×</button>
                                                            <button type="button" @click.stop="moveBlockInTableCell('${blockId}', cell.id, bi, 'up')" :disabled="bi === 0">↑</button>
                                                            <button type="button" @click.stop="moveBlockInTableCell('${blockId}', cell.id, bi, 'down')" :disabled="bi === (cell.blocks || []).length - 1">↓</button>
                                                        </div>
                                                    </div>
                                                </template>
                                                <button type="button" @click="addBlockToTableCell('${blockId}', cell.id, 'paragraph')" class="text-xs text-gray-500 hover:text-blue-600">+ Block</button>
                                            </div>
                                        </template>
                                        <template x-if="!(cell.blocks || []).length">
                                            <div contenteditable="true" class="min-h-[1.5rem] outline-none"
                                                x-init="$nextTick(() => { if (cell.content !== undefined && cell.content !== null) $el.innerHTML = $sanitizeHtml(cell.content || ''); })"
                                                x-effect="if (cell.content !== undefined && cell.content !== null && document.activeElement !== $el && $el.innerHTML !== $sanitizeHtml(cell.content || '')) { $el.innerHTML = $sanitizeHtml(cell.content || ''); }"
                                                @input="updateTableCellContent('${blockId}', cell.id, $event.target.innerHTML)"
                                                @blur="commitTableCellContent('${blockId}', cell.id, $event.target.innerHTML)"
                                                @focus="selectBlock('${blockId}'); initTableCellContent($event.target, cell)"
                                                @keydown="handleTableCellTabNavigation($event, '${blockId}', rowIndex, colIndex, 'header')"
                                            ></div>
                                        </template>
                                    </th>
                                </template>
                            </tr>
                        </template>
                    </thead>
                    
                    <!-- Body -->
                    <tbody>
                        <template x-for="(row, rowIndex) in getTableBodyRows(${scope})" :key="rowIndex">
                            <tr>
                                <template x-for="(cell, colIndex) in row" :key="cell.id">
                                    <td 
                                        x-show="!cell.merged"
                                        :colspan="cell.colspan > 1 ? cell.colspan : null"
                                        :rowspan="cell.rowspan > 1 ? cell.rowspan : null"
                                        :data-cell-id="cell.id"
                                        data-block-id="${blockId}"
                                        :data-row-index="rowIndex"
                                        :data-col-index="colIndex"
                                        class="border border-gray-300 p-2 min-w-[100px] min-h-[40px] align-top"
                                    >
                                        <template x-if="(cell.blocks || []).length > 0">
                                            <div class="table-cell-blocks space-y-1 min-h-[36px]">
                                                <template x-for="(cellBlock, bi) in (cell.blocks || [])" :key="cellBlock.id">
                                                    <div class="relative group/cb rounded px-1"
                                                         :class="{ 'ring-1 ring-blue-500 bg-blue-50/50': selectedBlockId === cellBlock.id }"
                                                         @click.stop="selectBlock(cellBlock.id)">
                                                        <div class="cellblock-content" x-html="renderBlock(cellBlock)"
                                                             x-init="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"></div>
                                                        <div class="absolute right-0 top-0 opacity-0 group-hover/cb:opacity-100 flex gap-0.5 text-xs">
                                                            <button type="button" @click.stop="addBlockInTableCellAfter('${blockId}', cell.id, bi)" title="Block einfügen">+</button>
                                                            <button type="button" @click.stop="removeBlockFromTableCell('${blockId}', cell.id, bi)" title="Entfernen">×</button>
                                                            <button type="button" @click.stop="moveBlockInTableCell('${blockId}', cell.id, bi, 'up')" :disabled="bi === 0">↑</button>
                                                            <button type="button" @click.stop="moveBlockInTableCell('${blockId}', cell.id, bi, 'down')" :disabled="bi === (cell.blocks || []).length - 1">↓</button>
                                                        </div>
                                                    </div>
                                                </template>
                                                <button type="button" @click="addBlockToTableCell('${blockId}', cell.id, 'paragraph')" class="text-xs text-gray-500 hover:text-blue-600">+ Block</button>
                                            </div>
                                        </template>
                                        <template x-if="!(cell.blocks || []).length">
                                            <div contenteditable="true" class="min-h-[1.5rem] outline-none"
                                                x-init="$nextTick(() => { if (cell.content !== undefined && cell.content !== null) $el.innerHTML = $sanitizeHtml(cell.content || ''); })"
                                                x-effect="if (cell.content !== undefined && cell.content !== null && document.activeElement !== $el && $el.innerHTML !== $sanitizeHtml(cell.content || '')) { $el.innerHTML = $sanitizeHtml(cell.content || ''); }"
                                                @input="updateTableCellContent('${blockId}', cell.id, $event.target.innerHTML)"
                                                @blur="commitTableCellContent('${blockId}', cell.id, $event.target.innerHTML)"
                                                @focus="selectBlock('${blockId}'); initTableCellContent($event.target, cell)"
                                                @keydown="handleTableCellTabNavigation($event, '${blockId}', rowIndex, colIndex, 'body')"
                                            ></div>
                                        </template>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                    
                    <!-- Footer -->
                    <tfoot x-show="${scope}.tableData && ${scope}.tableData.hasFooter">
                        <template x-for="(row, rowIndex) in getTableFooterRows(${scope})" :key="rowIndex">
                            <tr>
                                <template x-for="(cell, colIndex) in row" :key="cell.id">
                                    <td 
                                        x-show="!cell.merged"
                                        :colspan="cell.colspan > 1 ? cell.colspan : null"
                                        :rowspan="cell.rowspan > 1 ? cell.rowspan : null"
                                        :data-cell-id="cell.id"
                                        data-block-id="${blockId}"
                                        :data-row-index="rowIndex"
                                        :data-col-index="colIndex"
                                        class="border border-gray-300 p-2 bg-gray-50 font-semibold min-w-[100px] min-h-[40px] align-top"
                                    >
                                        <template x-if="(cell.blocks || []).length > 0">
                                            <div class="table-cell-blocks space-y-1 min-h-[36px]">
                                                <template x-for="(cellBlock, bi) in (cell.blocks || [])" :key="cellBlock.id">
                                                    <div class="relative group/cb rounded px-1"
                                                         :class="{ 'ring-1 ring-blue-500 bg-blue-50/50': selectedBlockId === cellBlock.id }"
                                                         @click.stop="selectBlock(cellBlock.id)">
                                                        <div class="cellblock-content" x-html="renderBlock(cellBlock)"
                                                             x-init="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"></div>
                                                        <div class="absolute right-0 top-0 opacity-0 group-hover/cb:opacity-100 flex gap-0.5 text-xs">
                                                            <button type="button" @click.stop="addBlockInTableCellAfter('${blockId}', cell.id, bi)" title="Block einfügen">+</button>
                                                            <button type="button" @click.stop="removeBlockFromTableCell('${blockId}', cell.id, bi)" title="Entfernen">×</button>
                                                            <button type="button" @click.stop="moveBlockInTableCell('${blockId}', cell.id, bi, 'up')" :disabled="bi === 0">↑</button>
                                                            <button type="button" @click.stop="moveBlockInTableCell('${blockId}', cell.id, bi, 'down')" :disabled="bi === (cell.blocks || []).length - 1">↓</button>
                                                        </div>
                                                    </div>
                                                </template>
                                                <button type="button" @click="addBlockToTableCell('${blockId}', cell.id, 'paragraph')" class="text-xs text-gray-500 hover:text-blue-600">+ Block</button>
                                            </div>
                                        </template>
                                        <template x-if="!(cell.blocks || []).length">
                                            <div contenteditable="true" class="min-h-[1.5rem] outline-none"
                                                x-init="$nextTick(() => { if (cell.content !== undefined && cell.content !== null) $el.innerHTML = $sanitizeHtml(cell.content || ''); })"
                                                x-effect="if (cell.content !== undefined && cell.content !== null && document.activeElement !== $el && $el.innerHTML !== $sanitizeHtml(cell.content || '')) { $el.innerHTML = $sanitizeHtml(cell.content || ''); }"
                                                @input="updateTableCellContent('${blockId}', cell.id, $event.target.innerHTML)"
                                                @blur="commitTableCellContent('${blockId}', cell.id, $event.target.innerHTML)"
                                                @focus="selectBlock('${blockId}'); initTableCellContent($event.target, cell)"
                                                @keydown="handleTableCellTabNavigation($event, '${blockId}', rowIndex, colIndex, 'footer')"
                                            ></div>
                                        </template>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tfoot>
                </table>
            </div>
        `;
    },
    
    initialize(block, blockIdCounter) {
        const tableData = TableManagement.initializeTable(blockIdCounter, 3, 3, true, false);
        block.tableData = tableData;
        block.tableData.lastCellIdCounter = tableData.lastCellIdCounter;
        return block;
    },
    
    ensureInitialized(block, blockIdCounter) {
        if (!block.tableData) {
            block.tableData = TableManagement.initializeTable(blockIdCounter, 3, 3, true, false);
        }
        return block;
    },
    
    cleanup(block) {
        // Entferne tableData beim Typ-Wechsel
        if (block.tableData) {
            delete block.tableData;
        }
        return block;
    },

    // Fokus-Verhalten: Tabellen sind nicht automatisch fokussierbar
    focusable: false,
    
    // Setzt den Fokus auf die erste verfügbare Tabellenzelle
    // @param {HTMLElement} element - Das DOM-Element des Blocks (div oder table)
    // @param {object} block - Der Block-Objekt
    focus(element, block) {
        if (!element) return false;
        
        // Finde das Table-Element
        const tableElement = element.tagName === 'TABLE' ? element : 
                             (element.querySelector ? element.querySelector('table') : null);
        
        if (!tableElement) return false;
        
        // Warte kurz, damit Alpine.js die Tabelle vollständig gerendert hat
        setTimeout(() => {
            // Suche nach der ersten sichtbaren, nicht-merged Zelle
            // Priorität: Header (th) > Body (td) > Footer (td)
            let firstCell = null;
            
            // Suche zuerst im Header
            const headerCells = tableElement.querySelectorAll('thead th[contenteditable="true"]');
            for (let cell of headerCells) {
                const style = window.getComputedStyle(cell);
                if (style.display !== 'none' && style.visibility !== 'hidden') {
                    firstCell = cell;
                    break;
                }
            }
            
            // Wenn keine Header-Zelle gefunden, suche im Body
            if (!firstCell) {
                const bodyCells = tableElement.querySelectorAll('tbody td[contenteditable="true"]');
                for (let cell of bodyCells) {
                    const style = window.getComputedStyle(cell);
                    if (style.display !== 'none' && style.visibility !== 'hidden') {
                        firstCell = cell;
                        break;
                    }
                }
            }
            
            // Wenn immer noch keine Zelle gefunden, suche im Footer
            if (!firstCell) {
                const footerCells = tableElement.querySelectorAll('tfoot td[contenteditable="true"]');
                for (let cell of footerCells) {
                    const style = window.getComputedStyle(cell);
                    if (style.display !== 'none' && style.visibility !== 'hidden') {
                        firstCell = cell;
                        break;
                    }
                }
            }
            
            // Fallback: Suche einfach nach der ersten contenteditable Zelle
            if (!firstCell) {
                firstCell = tableElement.querySelector('th[contenteditable="true"], td[contenteditable="true"]');
            }
            
            if (firstCell) {
                try {
                    firstCell.focus();
                    const range = document.createRange();
                    const sel = window.getSelection();
                    if (sel && firstCell) {
                        range.selectNodeContents(firstCell);
                        range.collapse(false);
                        sel.removeAllRanges();
                        sel.addRange(range);
                    }
                } catch (error) {
                    console.warn('Fehler beim Setzen des Cursors in Tabellenzelle:', error);
                }
            }
        }, 50);
        
        return true;
    },
    
    // Einstellungen für Sidebar
    getSettingsHTML(block, context = {}) {
        const rowCount = block.tableData?.cells?.length || 0;
        const colCount = block.tableData?.cells?.[0]?.length || 0;
        const hasHeader = !!block.tableData?.hasHeader;
        const hasFooter = !!block.tableData?.hasFooter;
        const safeRowCount = rowCount || 1;
        const safeColCount = colCount || 1;
        
        return `
            <div class="pt-4 border-t border-gray-200" x-data="{
                rowIndex: 1,
                colIndex: 1,
                mergeStartRow: 1,
                mergeStartCol: 1,
                mergeEndRow: 1,
                mergeEndCol: 1
            }">
                <div class="mb-3 text-sm text-gray-600">
                    Zeilen: ${rowCount} · Spalten: ${colCount}
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="addTableRow(block.id, 'top')"
                        class="px-3 py-1 bg-gray-100 rounded text-sm hover:bg-gray-200">
                        Zeile oben
                    </button>
                    <button type="button" @click="addTableRow(block.id, 'bottom')"
                        class="px-3 py-1 bg-gray-100 rounded text-sm hover:bg-gray-200">
                        Zeile unten
                    </button>
                    <button type="button" @click="addTableColumn(block.id, 'left')"
                        class="px-3 py-1 bg-gray-100 rounded text-sm hover:bg-gray-200">
                        Spalte links
                    </button>
                    <button type="button" @click="addTableColumn(block.id, 'right')"
                        class="px-3 py-1 bg-gray-100 rounded text-sm hover:bg-gray-200">
                        Spalte rechts
                    </button>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Zeile löschen (1-${safeRowCount})
                        </label>
                        <input type="number" min="1" max="${safeRowCount}" x-model.number="rowIndex"
                            class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        <button
                            @click="removeTableRow(block.id, Math.max(0, Math.min(${safeRowCount - 1}, (rowIndex || 1) - 1)))"
                            class="mt-2 w-full px-3 py-1 bg-gray-100 rounded text-sm hover:bg-gray-200">
                            Zeile entfernen
                        </button>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Spalte löschen (1-${safeColCount})
                        </label>
                        <input type="number" min="1" max="${safeColCount}" x-model.number="colIndex"
                            class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        <button
                            @click="removeTableColumn(block.id, Math.max(0, Math.min(${safeColCount - 1}, (colIndex || 1) - 1)))"
                            class="mt-2 w-full px-3 py-1 bg-gray-100 rounded text-sm hover:bg-gray-200">
                            Spalte entfernen
                        </button>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" @click="toggleTableHeader(block.id)"
                        class="px-3 py-1 ${hasHeader ? 'bg-blue-600 text-white' : 'bg-gray-100'} rounded text-sm hover:bg-blue-700 hover:text-white">
                        ${hasHeader ? 'Header entfernen' : 'Header hinzufügen'}
                    </button>
                    <button type="button" @click="toggleTableFooter(block.id)"
                        class="px-3 py-1 ${hasFooter ? 'bg-blue-600 text-white' : 'bg-gray-100'} rounded text-sm hover:bg-blue-700 hover:text-white">
                        ${hasFooter ? 'Footer entfernen' : 'Footer hinzufügen'}
                    </button>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="text-sm font-semibold text-gray-700 mb-2">Zellen verbinden</div>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" min="1" max="${safeRowCount}" x-model.number="mergeStartRow"
                            placeholder="Start-Zeile"
                            class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        <input type="number" min="1" max="${safeColCount}" x-model.number="mergeStartCol"
                            placeholder="Start-Spalte"
                            class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        <input type="number" min="1" max="${safeRowCount}" x-model.number="mergeEndRow"
                            placeholder="Ende-Zeile"
                            class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        <input type="number" min="1" max="${safeColCount}" x-model.number="mergeEndCol"
                            placeholder="Ende-Spalte"
                            class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="mt-2 flex gap-2">
                        <button
                            @click="mergeTableCells(block.id,
                                Math.max(0, Math.min(${safeRowCount - 1}, (mergeStartRow || 1) - 1)),
                                Math.max(0, Math.min(${safeColCount - 1}, (mergeStartCol || 1) - 1)),
                                Math.max(0, Math.min(${safeRowCount - 1}, (mergeEndRow || 1) - 1)),
                                Math.max(0, Math.min(${safeColCount - 1}, (mergeEndCol || 1) - 1))
                            )"
                            class="px-3 py-1 bg-gray-100 rounded text-sm hover:bg-gray-200">
                            Verbinden
                        </button>
                        <button
                            @click="unmergeTableCells(block.id,
                                Math.max(0, Math.min(${safeRowCount - 1}, (mergeStartRow || 1) - 1)),
                                Math.max(0, Math.min(${safeColCount - 1}, (mergeStartCol || 1) - 1))
                            )"
                            class="px-3 py-1 bg-gray-100 rounded text-sm hover:bg-gray-200">
                            Trennen
                        </button>
                    </div>
                    <div class="mt-2 text-xs text-gray-500">
                        Hinweis: Eingaben sind 1-basiert (erste Zeile/Spalte = 1).
                    </div>
                </div>
            </div>
        `;
    }
};
