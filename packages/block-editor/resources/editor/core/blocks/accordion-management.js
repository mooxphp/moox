import { Utils } from '../utils/index.js';

export function createAccordionManagement({ createBlock }) {
    return {
        initializeAccordion(blockIdCounter, initialItems = 3) {
            const items = [];
            let itemIdCounter = blockIdCounter;

            for (let i = 0; i < initialItems; i++) {
                items.push({
                    id: Utils.generateId(itemIdCounter++),
                    question: `Frage ${i + 1}`,
                    answer: '',
                    expanded: i === 0,
                    children: []
                });
            }

            return {
                items,
                behavior: 'single',
                lastItemIdCounter: itemIdCounter - 1
            };
        },

        addAccordionItem(blocks, blockId, blockIdCounter) {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'accordion') {
                return null;
            }

            if (!block.accordionData) {
                block.accordionData = this.initializeAccordion(blockIdCounter, 0);
            }

            const nextIndex = (block.accordionData.items || []).length + 1;
            const newItem = {
                id: Utils.generateId(blockIdCounter),
                question: `Frage ${nextIndex}`,
                answer: '',
                expanded: true,
                children: []
            };

            if (!Array.isArray(block.accordionData.items)) {
                block.accordionData.items = [];
            }

            if (block.accordionData.behavior !== 'multiple') {
                block.accordionData.items.forEach((item) => {
                    item.expanded = false;
                });
            }

            block.accordionData.items.push(newItem);
            block.updatedAt = new Date().toISOString();

            return { lastItemIdCounter: blockIdCounter };
        },

        removeAccordionItem(blocks, blockId, itemId) {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'accordion' || !Array.isArray(block.accordionData?.items)) {
                return;
            }

            if (block.accordionData.items.length <= 1) {
                return;
            }

            const index = block.accordionData.items.findIndex((item) => item.id === itemId);
            if (index === -1) {
                return;
            }

            const removed = block.accordionData.items.splice(index, 1)[0];
            if (removed?.expanded && block.accordionData.items.length > 0) {
                block.accordionData.items[Math.max(0, index - 1)].expanded = true;
            }

            block.updatedAt = new Date().toISOString();
        },

        toggleAccordionItem(blocks, blockId, itemId) {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'accordion' || !Array.isArray(block.accordionData?.items)) {
                return;
            }

            const items = block.accordionData.items;
            const target = items.find((item) => item.id === itemId);
            if (!target) {
                return;
            }

            const behavior = block.accordionData.behavior || 'single';
            if (behavior === 'none') {
                return;
            }

            const nextExpanded = !target.expanded;
            if (behavior === 'single' && nextExpanded) {
                items.forEach((item) => {
                    item.expanded = false;
                });
            }

            target.expanded = nextExpanded;
            block.updatedAt = new Date().toISOString();
        },

        updateAccordionQuestion(blocks, blockId, itemId, question) {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'accordion' || !Array.isArray(block.accordionData?.items)) {
                return;
            }

            const item = block.accordionData.items.find((entry) => entry.id === itemId);
            if (!item) {
                return;
            }

            item.question = question;
        },

        updateAccordionAnswer(blocks, blockId, itemId, answer) {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'accordion' || !Array.isArray(block.accordionData?.items)) {
                return;
            }

            const item = block.accordionData.items.find((entry) => entry.id === itemId);
            if (!item) {
                return;
            }

            item.answer = answer;
        },

        moveAccordionItem(blocks, blockId, itemIndex, direction) {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'accordion' || !Array.isArray(block.accordionData?.items)) {
                return;
            }

            const items = block.accordionData.items;
            if (direction === 'up' && itemIndex > 0) {
                [items[itemIndex - 1], items[itemIndex]] = [items[itemIndex], items[itemIndex - 1]];
                block.updatedAt = new Date().toISOString();
            } else if (direction === 'down' && itemIndex < items.length - 1) {
                [items[itemIndex], items[itemIndex + 1]] = [items[itemIndex + 1], items[itemIndex]];
                block.updatedAt = new Date().toISOString();
            }
        },

        setAccordionBehavior(blocks, blockId, behavior) {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'accordion') {
                return;
            }

            if (!block.accordionData) {
                block.accordionData = this.initializeAccordion(0, 1);
            }

            const nextBehavior = ['single', 'multiple', 'none'].includes(behavior) ? behavior : 'single';
            block.accordionData.behavior = nextBehavior;

            if (Array.isArray(block.accordionData.items)) {
                if (nextBehavior === 'none') {
                    block.accordionData.items.forEach((item) => {
                        item.expanded = false;
                    });
                } else if (nextBehavior === 'single') {
                    let foundExpanded = false;
                    block.accordionData.items.forEach((item) => {
                        if (item.expanded && !foundExpanded) {
                            foundExpanded = true;
                            return;
                        }
                        item.expanded = false;
                    });
                }
            }

            block.updatedAt = new Date().toISOString();
        },

        addChildToAccordionItem(blocks, blockId, itemId, blockIdCounter, childType = 'paragraph') {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'accordion' || !Array.isArray(block.accordionData?.items)) {
                return null;
            }

            const item = block.accordionData.items.find((entry) => entry.id === itemId);
            if (!item) {
                return null;
            }

            if (!Array.isArray(item.children)) {
                item.children = [];
            }

            const childBlock = createBlock(blockIdCounter, childType, '');
            item.children.push(childBlock);
            item.expanded = true;
            block.updatedAt = new Date().toISOString();

            return childBlock;
        },

        moveAccordionChild(blocks, blockId, itemId, childIndex, direction) {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'accordion' || !Array.isArray(block.accordionData?.items)) {
                return;
            }

            const item = block.accordionData.items.find((entry) => entry.id === itemId);
            if (!item || !Array.isArray(item.children)) {
                return;
            }

            if (direction === 'up' && childIndex > 0) {
                [item.children[childIndex - 1], item.children[childIndex]] = [item.children[childIndex], item.children[childIndex - 1]];
                block.updatedAt = new Date().toISOString();
            } else if (direction === 'down' && childIndex < item.children.length - 1) {
                [item.children[childIndex], item.children[childIndex + 1]] = [item.children[childIndex + 1], item.children[childIndex]];
                block.updatedAt = new Date().toISOString();
            }
        }
    };
}
