import { Utils } from '../utils/index.js';

export function createListManagement() {
    return {
        getListItems(blocks, blockId) {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'list' || !block.listData) {
                return null;
            }

            return { block, items: block.listData.items };
        },

        ensureListItems(blocks, blockId, blockIdCounter, listStyle = 'unordered') {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'list') {
                return null;
            }

            if (!block.listData) {
                block.listData = this.initializeList(blockIdCounter, 0, listStyle);
            }

            return { block, items: block.listData.items };
        },

        // Initialize a list with default items
        initializeList(blockIdCounter, initialItems = 3, listStyle = 'unordered') {
            const items = [];
            let itemIdCounter = blockIdCounter;

            // Create initial items
            for (let i = 0; i < initialItems; i++) {
                items.push({
                    id: Utils.generateId(itemIdCounter++),
                    text: ''
                });
            }

            return {
                items,
                listStyle,
                lastItemIdCounter: itemIdCounter - 1
            };
        },

        // Add a list item
        addListItem(blocks, blockId, blockIdCounter, position = 'bottom') {
            const list = this.ensureListItems(blocks, blockId, blockIdCounter, 'unordered');
            if (!list) {
                return null;
            }

            const { block, items } = list;

            const newItem = {
                id: Utils.generateId(blockIdCounter),
                text: ''
            };

            if (position === 'top') {
                items.unshift(newItem);
            } else {
                items.push(newItem);
            }

            block.updatedAt = new Date().toISOString();

            return { lastItemIdCounter: blockIdCounter };
        },

        // Remove a list item
        removeListItem(blocks, blockId, itemIndex) {
            const list = this.getListItems(blocks, blockId);
            if (!list) return;

            const { block, items } = list;

            // Don't remove if it's the only item
            if (items.length <= 1) return;

            items.splice(itemIndex, 1);
            block.updatedAt = new Date().toISOString();
        },

        // Update item text
        updateListItemText(blocks, blockId, itemId, text) {
            const list = this.getListItems(blocks, blockId);
            if (!list) return;

            const { items } = list;
            const item = items.find(i => i.id === itemId);

            if (item) {
                item.text = text;
            }
        },

        // Move item up
        moveListItemUp(blocks, blockId, itemIndex) {
            const list = this.getListItems(blocks, blockId);
            if (!list) return;

            const { block, items } = list;
            if (itemIndex > 0 && itemIndex < items.length) {
                [items[itemIndex - 1], items[itemIndex]] = [items[itemIndex], items[itemIndex - 1]];
                block.updatedAt = new Date().toISOString();
            }
        },

        // Move item down
        moveListItemDown(blocks, blockId, itemIndex) {
            const list = this.getListItems(blocks, blockId);
            if (!list) return;

            const { block, items } = list;
            if (itemIndex >= 0 && itemIndex < items.length - 1) {
                [items[itemIndex], items[itemIndex + 1]] = [items[itemIndex + 1], items[itemIndex]];
                block.updatedAt = new Date().toISOString();
            }
        },

        // Update list style
        updateListStyle(blocks, blockId, listStyle) {
            const list = this.ensureListItems(blocks, blockId, 0, listStyle || 'unordered');
            if (!list) return;

            const { block } = list;

            block.listData.listStyle = listStyle || 'unordered';
            block.updatedAt = new Date().toISOString();
        }
    };
}
