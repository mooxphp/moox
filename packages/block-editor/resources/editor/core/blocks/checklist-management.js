import { Utils } from '../utils/index.js';

export function createChecklistManagement() {
    return {
        getChecklistItems(blocks, blockId) {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'checklist' || !block.checklistData) {
                return null;
            }

            return { block, items: block.checklistData.items };
        },

        ensureChecklistItems(blocks, blockId, blockIdCounter) {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'checklist') {
                return null;
            }

            if (!block.checklistData) {
                block.checklistData = this.initializeChecklist(blockIdCounter, 0);
            }

            return { block, items: block.checklistData.items };
        },

        // Initialize a checklist with default items
        initializeChecklist(blockIdCounter, initialItems = 3) {
            const items = [];
            let itemIdCounter = blockIdCounter;

            // Create initial items
            for (let i = 0; i < initialItems; i++) {
                items.push({
                    id: Utils.generateId(itemIdCounter++),
                    text: '',
                    checked: false
                });
            }

            return {
                items,
                lastItemIdCounter: itemIdCounter - 1
            };
        },

        // Add a checklist item
        addChecklistItem(blocks, blockId, blockIdCounter, position = 'bottom') {
            const checklist = this.ensureChecklistItems(blocks, blockId, blockIdCounter);
            if (!checklist) {
                return null;
            }

            const { block, items } = checklist;

            const newItem = {
                id: Utils.generateId(blockIdCounter),
                text: '',
                checked: false
            };

            if (position === 'top') {
                items.unshift(newItem);
            } else {
                items.push(newItem);
            }

            block.updatedAt = new Date().toISOString();

            return { lastItemIdCounter: blockIdCounter };
        },

        // Remove a checklist item
        removeChecklistItem(blocks, blockId, itemIndex) {
            const checklist = this.getChecklistItems(blocks, blockId);
            if (!checklist) return;

            const { block, items } = checklist;

            // Don't remove if it's the only item
            if (items.length <= 1) return;

            items.splice(itemIndex, 1);
            block.updatedAt = new Date().toISOString();
        },

        // Toggle checked state of an item
        toggleChecklistItem(blocks, blockId, itemIndex) {
            const checklist = this.getChecklistItems(blocks, blockId);
            if (!checklist) return;

            const { block, items } = checklist;
            if (items[itemIndex]) {
                items[itemIndex].checked = !items[itemIndex].checked;
                block.updatedAt = new Date().toISOString();
            }
        },

        // Update item text
        updateChecklistItemText(blocks, blockId, itemId, text) {
            const checklist = this.getChecklistItems(blocks, blockId);
            if (!checklist) return;

            const { items } = checklist;
            const item = items.find(i => i.id === itemId);

            if (item) {
                item.text = text;
            }
        },

        // Move item up
        moveChecklistItemUp(blocks, blockId, itemIndex) {
            const checklist = this.getChecklistItems(blocks, blockId);
            if (!checklist) return;

            const { block, items } = checklist;
            if (itemIndex > 0 && itemIndex < items.length) {
                [items[itemIndex - 1], items[itemIndex]] = [items[itemIndex], items[itemIndex - 1]];
                block.updatedAt = new Date().toISOString();
            }
        },

        // Move item down
        moveChecklistItemDown(blocks, blockId, itemIndex) {
            const checklist = this.getChecklistItems(blocks, blockId);
            if (!checklist) return;

            const { block, items } = checklist;
            if (itemIndex >= 0 && itemIndex < items.length - 1) {
                [items[itemIndex], items[itemIndex + 1]] = [items[itemIndex + 1], items[itemIndex]];
                block.updatedAt = new Date().toISOString();
            }
        }
    };
}
