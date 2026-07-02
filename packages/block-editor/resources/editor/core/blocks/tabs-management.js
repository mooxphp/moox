import { Utils } from '../utils/index.js';

export function createTabsManagement({ createBlock }) {
    return {
        initializeTabs(blockIdCounter, initialTabs = 2) {
            const items = [];
            let tabIdCounter = blockIdCounter;

            for (let i = 0; i < initialTabs; i++) {
                items.push({
                    id: Utils.generateId(tabIdCounter++),
                    title: `Tab ${i + 1}`,
                    content: '',
                    children: []
                });
            }

            const activeTabId = items[0]?.id || null;

            return {
                items,
                activeTabId,
                lastTabIdCounter: tabIdCounter - 1
            };
        },

        addTabItem(blocks, blockId, blockIdCounter) {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'tabs') {
                return null;
            }

            if (!block.tabsData) {
                block.tabsData = this.initializeTabs(blockIdCounter, 0);
            }

            const items = block.tabsData.items || [];
            const nextIndex = items.length + 1;
            const newItem = {
                id: Utils.generateId(blockIdCounter),
                title: `Tab ${nextIndex}`,
                content: '',
                children: []
            };

            items.push(newItem);
            block.tabsData.items = items;
            block.tabsData.activeTabId = newItem.id;
            block.updatedAt = new Date().toISOString();

            return { lastTabIdCounter: blockIdCounter };
        },

        removeTabItem(blocks, blockId, tabId) {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'tabs' || !block.tabsData?.items) {
                return;
            }

            const items = block.tabsData.items;
            if (items.length <= 1) {
                return;
            }

            const tabIndex = items.findIndex((item) => item.id === tabId);
            if (tabIndex === -1) {
                return;
            }

            items.splice(tabIndex, 1);

            if (block.tabsData.activeTabId === tabId) {
                const nextActive = items[Math.max(0, tabIndex - 1)] || items[0];
                block.tabsData.activeTabId = nextActive?.id || null;
            }

            block.updatedAt = new Date().toISOString();
        },

        setActiveTab(blocks, blockId, tabId) {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'tabs' || !block.tabsData?.items) {
                return;
            }

            const exists = block.tabsData.items.some((item) => item.id === tabId);
            if (!exists) {
                return;
            }

            block.tabsData.activeTabId = tabId;
            block.updatedAt = new Date().toISOString();
        },

        updateTabTitle(blocks, blockId, tabId, title) {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'tabs' || !block.tabsData?.items) {
                return;
            }

            const tab = block.tabsData.items.find((item) => item.id === tabId);
            if (!tab) {
                return;
            }

            tab.title = title;
        },

        updateTabContent(blocks, blockId, tabId, content) {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'tabs' || !block.tabsData?.items) {
                return;
            }

            const tab = block.tabsData.items.find((item) => item.id === tabId);
            if (!tab) {
                return;
            }

            tab.content = content;
        },

        addChildToTab(blocks, blockId, tabId, blockIdCounter, childType = 'paragraph') {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'tabs' || !block.tabsData?.items) {
                return null;
            }

            const tab = block.tabsData.items.find((item) => item.id === tabId);
            if (!tab) {
                return null;
            }

            if (!Array.isArray(tab.children)) {
                tab.children = [];
            }

            const childBlock = createBlock(blockIdCounter, childType, '');
            tab.children.push(childBlock);
            block.tabsData.activeTabId = tabId;
            block.updatedAt = new Date().toISOString();

            return childBlock;
        },

        moveTabChild(blocks, blockId, tabId, childIndex, direction) {
            const { block } = Utils.findBlockById(blocks, blockId);
            if (!block || block.type !== 'tabs' || !block.tabsData?.items) {
                return;
            }

            const tab = block.tabsData.items.find((item) => item.id === tabId);
            if (!tab || !Array.isArray(tab.children)) {
                return;
            }

            if (direction === 'up' && childIndex > 0) {
                [tab.children[childIndex - 1], tab.children[childIndex]] = [tab.children[childIndex], tab.children[childIndex - 1]];
                block.updatedAt = new Date().toISOString();
            } else if (direction === 'down' && childIndex < tab.children.length - 1) {
                [tab.children[childIndex], tab.children[childIndex + 1]] = [tab.children[childIndex + 1], tab.children[childIndex]];
                block.updatedAt = new Date().toISOString();
            }
        }
    };
}
