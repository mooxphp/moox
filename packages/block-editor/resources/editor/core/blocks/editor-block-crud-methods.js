import { BlockManagement, ChildManagement } from './management.js';
import { BlockTypes } from '../../components/block-types.js';

export const editorBlockCrudMethods = {
    addBlock(type, content = '') {
        if (!this.addComponentsEnabled) {
            return;
        }
        const result = this.findBlockById(this.selectedBlockId);
        const cellContext = result?.cellContext;
        if (cellContext && result?.parent?.id) {
            this.addBlockToTableCell(result.parent.id, cellContext.cellId, type, content);
            return;
        }
        this.blockIdCounter++;
        const block = BlockManagement.addBlock(this.blocks, this.selectedBlockId, this.blockIdCounter, type, content);
        this.selectedBlockId = block.id;
        this.closeBlockToolbar();

        BlockManagement.ensureColumnStructure(this.blocks);

        // Callout startet mit einem Paragraph-Child in der ersten Spalte,
        // statt einen zusätzlichen Paragraph als eigenen Block zu benötigen.
        if (type === 'callout') {
            const { block: insertedBlock } = this.findBlockById(block.id);
            const hasChildrenInFirstColumn = Boolean(insertedBlock?.children?.[0]?.children?.length);
            if (!hasChildrenInFirstColumn) {
                this.blockIdCounter++;
                const defaultChild = ChildManagement.addChildToColumn(this.blocks, block.id, this.blockIdCounter, 'paragraph', 0);
                if (defaultChild) {
                    this.selectedBlockId = defaultChild.id;
                } else {
                    this.blockIdCounter--;
                }
            }
        }

        this.invalidateJSONDisplayCache();
        this.invalidateBlockLookupCache();

        this.$nextTick(() => {
            this.focusBlockElement(this.selectedBlockId || block.id);
        });
    },

    addBlockAfter(blockId, type = 'paragraph') {
        if (!this.addComponentsEnabled) {
            return;
        }
        this.blockIdCounter++;
        const block = BlockManagement.addBlockAfter(this.blocks, blockId, this.blockIdCounter, type);
        this.selectedBlockId = block.id;

        // Focus auf den neuen Block
        this.$nextTick(() => {
            this.focusBlockElement(block.id);
        });
    },

    deleteBlock(blockId) {
        const found = this.findBlockById(blockId);
        if (found.cellContext && found.cellContext.blockIndex !== undefined && found.parent?.id) {
            this.removeBlockFromTableCell(found.parent.id, found.cellContext.cellId, found.cellContext.blockIndex);
            return;
        }
        const result = BlockManagement.deleteBlock(this.blocks, blockId);
        if (result.deleted) {
            this.invalidateBlockCache(blockId);
            this.invalidateRenderCache(blockId);
            this.invalidateJSONDisplayCache();
            this.invalidateBlockLookupCache();

            const nextSelectedId = result.newSelectedBlockId;
            this.selectedBlockId = nextSelectedId ?? null;

            if (!this.selectedBlockId) {
                if (this.blocks.length === 0 && this.addComponentsEnabled) {
                    this.addBlock('paragraph');
                }
            } else {
                this.$nextTick(() => this.focusBlockElement(this.selectedBlockId));
            }
        }
    },

    selectBlock(blockId) {
        this.selectedBlockId = blockId;

        if (this.shouldPreserveCurrentSelection(blockId)) {
            return;
        }

        // Setze Fokus auf den ausgewählten Block
        this.$nextTick(() => {
            this.focusBlockElement(blockId);
        });
    },

    moveBlock(index, direction) {
        BlockManagement.moveBlock(this.blocks, index, direction);
        // Invalidiere JSON-Display-Cache (Reihenfolge hat sich geändert)
        this.invalidateJSONDisplayCache();
    },

    changeBlockType(blockId, newType) {
        BlockManagement.changeBlockType(this.blocks, blockId, newType, this.blockIdCounter);
        // Increment blockIdCounter if columns were created
        const columnCount = BlockTypes.getColumnCount(newType);
        if (columnCount > 0) {
            this.blockIdCounter += columnCount;
        }

        // Stelle sicher, dass Column-Blöcke die richtige Anzahl von Spalten haben
        BlockManagement.ensureColumnStructure(this.blocks);

        // Invalidiere Cache für diesen Block (DOM-Struktur hat sich geändert)
        this.invalidateBlockCache(blockId);
        this.invalidateRenderCache(blockId);
        this.invalidateJSONDisplayCache();

        // Focus auf den Block nach Typ-Änderung
        this.$nextTick(() => {
            const element = this.getBlockElement(blockId);
            if (element) {
                element.focus();
            }
        });
    },

    updateBlockStyle(blockId, style) {
        BlockManagement.updateBlockStyle(this.blocks, blockId, style);
    },

    clearBlockStyle(blockId) {
        BlockManagement.clearBlockStyle(this.blocks, blockId);
    },

    updateBlockClasses(blockId, classes) {
        BlockManagement.updateBlockClasses(this.blocks, blockId, classes);
    },

    clearBlockClasses(blockId) {
        BlockManagement.clearBlockClasses(this.blocks, blockId);
    },

    updateBlockHtmlId(blockId, htmlId) {
        BlockManagement.updateBlockHtmlId(this.blocks, blockId, htmlId);
    },

    clearBlockHtmlId(blockId) {
        BlockManagement.clearBlockHtmlId(this.blocks, blockId);
    },

    addChild(parentBlockId, childType) {
        if (!this.addComponentsEnabled) {
            return;
        }
        this.blockIdCounter++;
        const childBlock = ChildManagement.addChild(this.blocks, parentBlockId, this.blockIdCounter, childType);
        if (childBlock) {
            // Stelle sicher, dass die Struktur korrekt ist (entfernt Children von nicht-Container-Blöcken)
            BlockManagement.ensureColumnStructure(this.blocks);

            // Invalidiere Render-Cache für Parent und JSON-Display
            this.invalidateRenderCache(parentBlockId);
            this.invalidateJSONDisplayCache();

            this.selectedBlockId = childBlock.id;

            // Focus auf den neuen Child-Block
            this.$nextTick(() => {
                this.focusBlockElement(childBlock.id);
            });
        }
    },

    addChildAfter(parentBlockId, childIndex, childType) {
        if (!this.addComponentsEnabled) {
            return;
        }
        this.blockIdCounter++;
        const childBlock = ChildManagement.addChildAfter(this.blocks, parentBlockId, childIndex, this.blockIdCounter, childType);
        if (childBlock) {
            // Stelle sicher, dass die Struktur korrekt ist (entfernt Children von nicht-Container-Blöcken)
            BlockManagement.ensureColumnStructure(this.blocks);

            this.invalidateRenderCache(parentBlockId);
            this.invalidateJSONDisplayCache();

            this.selectedBlockId = childBlock.id;

            // Focus auf den neuen Child-Block
            this.$nextTick(() => {
                this.focusBlockElement(childBlock.id);
            });
        }
    },

    toggleListExpanded(blockId) {
        const { block } = this.findBlockById(blockId);
        if (block && block.type === 'toggleList') {
            block.expanded = !block.expanded;
            block.updatedAt = new Date().toISOString();
            this.invalidateRenderCache(blockId);
            this.invalidateJSONDisplayCache();
        }
    },

    /**
     * Enter in Toggle-List-Überschrift: Bei Inhalt neue Toggle-Liste darunter, sonst Absatz.
     * Liest aktuellen Inhalt aus dem contenteditable (vor Commit).
     */
    handleToggleListHeadingEnter(blockId, event) {
        const el = event && event.target;
        if (!el || typeof el.innerHTML === 'undefined') return;
        const content = el.innerHTML || '';
        this.commitBlockContent(blockId, content);
        const hasText = (content.replace(/<[^>]*>/g, '').trim().length > 0);
        this.addBlockAfter(blockId, hasText ? 'toggleList' : 'paragraph');
    },

    /**
     * Enter in Toggle-List-Überschrift (als Kind): Bei Inhalt neue Toggle-Liste darunter, sonst Absatz.
     */
    handleToggleListHeadingEnterChild(parentBlockId, childIndex, childId, event) {
        const el = event && event.target;
        if (!el || typeof el.innerHTML === 'undefined') return;
        const content = el.innerHTML || '';
        this.commitBlockContent(childId, content);
        const hasText = (content.replace(/<[^>]*>/g, '').trim().length > 0);
        this.addChildAfter(parentBlockId, childIndex, hasText ? 'toggleList' : 'paragraph');
    },

    moveChildBlock(parentBlockId, childIndex, direction, columnIndex = null) {
        ChildManagement.moveChildBlock(this.blocks, parentBlockId, childIndex, direction, columnIndex);
        this.invalidateRenderCache(parentBlockId);
        this.invalidateJSONDisplayCache();
    },

    addChildToColumn(parentBlockId, childType, columnIndex) {
        if (!this.addComponentsEnabled) {
            return;
        }
        this.blockIdCounter++;
        const childBlock = ChildManagement.addChildToColumn(this.blocks, parentBlockId, this.blockIdCounter, childType, columnIndex);
        if (!childBlock) {
            this.blockIdCounter--; // Rollback if failed
            return;
        }
        if (childBlock) {
            // Stelle sicher, dass die Struktur korrekt ist (entfernt Children von nicht-Container-Blöcken)
            BlockManagement.ensureColumnStructure(this.blocks);

            this.selectedBlockId = childBlock.id;

            // Focus auf den neuen Child-Block
            this.$nextTick(() => {
                this.focusBlockElement(childBlock.id);
            });
        }
    },
};
