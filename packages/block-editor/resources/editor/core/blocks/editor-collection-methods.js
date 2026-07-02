import { ChecklistManagement, ListManagement, TabsManagement, AccordionManagement } from './management.js';

export const editorCollectionMethods = {
    // Checklist Management Functions
    addChecklistItem(blockId, position = 'bottom') {
        const result = ChecklistManagement.addChecklistItem(this.blocks, blockId, this.blockIdCounter, position);
        if (result) {
            this.blockIdCounter = result.lastItemIdCounter + 1;
        }
    },

    removeChecklistItem(blockId, itemIndex) {
        ChecklistManagement.removeChecklistItem(this.blocks, blockId, itemIndex);
    },

    toggleChecklistItem(blockId, itemIndex) {
        ChecklistManagement.toggleChecklistItem(this.blocks, blockId, itemIndex);
    },

    updateChecklistItemText(blockId, itemId, text) {
        const key = `checklist:${blockId}:${itemId}`;
        this.queueInlineContentUpdate(key, text, (latestText) => {
            ChecklistManagement.updateChecklistItemText(this.blocks, blockId, itemId, latestText);
        });
    },

    commitChecklistItemText(blockId, itemId, text) {
        const key = `checklist:${blockId}:${itemId}`;
        const pending = this.inlineContentBuffer.get(key);
        this.clearInlineContentUpdate(key);
        const resolvedText = (text !== null && text !== undefined)
            ? text
            : (pending ? pending.content : null);
        const { block } = this.findBlockById(blockId);
        if (block) {
            const currentText = block.checklistData?.items?.find(item => item.id === itemId)?.text ?? '';
            const nextText = (resolvedText !== null && resolvedText !== undefined)
                ? resolvedText
                : currentText;
            const hasChanged = nextText !== currentText;
            if (!hasChanged) {
                this.invalidateJSONDisplayCache();
                return;
            }

            ChecklistManagement.updateChecklistItemText(this.blocks, blockId, itemId, nextText);
            block.updatedAt = new Date().toISOString();
            this.invalidateRenderCache(blockId);
            this.invalidateJSONDisplayCache();
        }
    },

    moveChecklistItemUp(blockId, itemIndex) {
        ChecklistManagement.moveChecklistItemUp(this.blocks, blockId, itemIndex);
    },

    moveChecklistItemDown(blockId, itemIndex) {
        ChecklistManagement.moveChecklistItemDown(this.blocks, blockId, itemIndex);
    },

    // List Management Functions
    setListStyle(blockId, listStyle) {
        ListManagement.updateListStyle(this.blocks, blockId, listStyle);
        this.invalidateBlockSettingsCache(blockId);
    },

    addListItem(blockId, position = 'bottom') {
        const result = ListManagement.addListItem(this.blocks, blockId, this.blockIdCounter, position);
        if (result) {
            this.blockIdCounter = result.lastItemIdCounter + 1;
            this.invalidateBlockSettingsCache(blockId);
        }
    },

    removeListItem(blockId, itemIndex) {
        ListManagement.removeListItem(this.blocks, blockId, itemIndex);
        this.invalidateBlockSettingsCache(blockId);
    },

    updateListItemText(blockId, itemId, text) {
        const key = `list:${blockId}:${itemId}`;
        this.queueInlineContentUpdate(key, text, (latestText) => {
            ListManagement.updateListItemText(this.blocks, blockId, itemId, latestText);
        });
    },

    commitListItemText(blockId, itemId, text) {
        const key = `list:${blockId}:${itemId}`;
        const pending = this.inlineContentBuffer.get(key);
        this.clearInlineContentUpdate(key);
        const resolvedText = (text !== null && text !== undefined)
            ? text
            : (pending ? pending.content : null);
        const { block } = this.findBlockById(blockId);
        if (block) {
            const currentText = block.listData?.items?.find(item => item.id === itemId)?.text ?? '';
            const nextText = (resolvedText !== null && resolvedText !== undefined)
                ? resolvedText
                : currentText;
            const hasChanged = nextText !== currentText;
            if (!hasChanged) {
                this.invalidateJSONDisplayCache();
                return;
            }

            ListManagement.updateListItemText(this.blocks, blockId, itemId, nextText);
            block.updatedAt = new Date().toISOString();
            this.invalidateRenderCache(blockId);
            this.invalidateJSONDisplayCache();
        }
    },

    moveListItemUp(blockId, itemIndex) {
        ListManagement.moveListItemUp(this.blocks, blockId, itemIndex);
    },

    moveListItemDown(blockId, itemIndex) {
        ListManagement.moveListItemDown(this.blocks, blockId, itemIndex);
    },

    // Tabs Management Functions
    addTabItem(blockId) {
        this.blockIdCounter++;
        const result = TabsManagement.addTabItem(this.blocks, blockId, this.blockIdCounter);
        if (result) {
            this.blockIdCounter = result.lastTabIdCounter + 1;
            this.invalidateRenderCache(blockId);
            this.invalidateBlockSettingsCache(blockId);
            this.invalidateJSONDisplayCache();
        }
    },

    addChildToTab(blockId, tabId, childType) {
        if (!this.addComponentsEnabled) {
            return;
        }
        this.blockIdCounter++;
        const childBlock = TabsManagement.addChildToTab(this.blocks, blockId, tabId, this.blockIdCounter, childType);
        if (!childBlock) {
            this.blockIdCounter--;
            return;
        }
        this.selectedBlockId = childBlock.id;
        this.invalidateRenderCache(blockId);
        this.invalidateBlockSettingsCache(blockId);
        this.invalidateJSONDisplayCache();
        this.$nextTick(() => {
            this.focusBlockElement(childBlock.id);
        });
    },

    moveTabChild(blockId, tabId, childIndex, direction) {
        TabsManagement.moveTabChild(this.blocks, blockId, tabId, childIndex, direction);
        this.invalidateRenderCache(blockId);
        this.invalidateJSONDisplayCache();
    },

    removeTabItem(blockId, tabId) {
        TabsManagement.removeTabItem(this.blocks, blockId, tabId);
        this.invalidateRenderCache(blockId);
        this.invalidateBlockSettingsCache(blockId);
        this.invalidateJSONDisplayCache();
    },

    setActiveTab(blockId, tabId) {
        TabsManagement.setActiveTab(this.blocks, blockId, tabId);
        this.invalidateRenderCache(blockId);
        this.invalidateBlockSettingsCache(blockId);
        this.invalidateJSONDisplayCache();
    },

    updateTabTitle(blockId, tabId, title) {
        const key = `tabs:title:${blockId}:${tabId}`;
        this.queueInlineContentUpdate(key, title, (latestTitle) => {
            TabsManagement.updateTabTitle(this.blocks, blockId, tabId, latestTitle);
        }, 200);
    },

    commitTabTitle(blockId, tabId, title) {
        const key = `tabs:title:${blockId}:${tabId}`;
        const pending = this.inlineContentBuffer.get(key);
        this.clearInlineContentUpdate(key);
        const resolvedTitle = (title !== null && title !== undefined)
            ? title
            : (pending ? pending.content : null);
        const { block } = this.findBlockById(blockId);
        if (block) {
            const currentTitle = block.tabsData?.items?.find(item => item.id === tabId)?.title ?? '';
            const nextTitle = (resolvedTitle !== null && resolvedTitle !== undefined)
                ? resolvedTitle
                : currentTitle;
            const hasChanged = nextTitle !== currentTitle;
            if (!hasChanged) {
                this.invalidateJSONDisplayCache();
                return;
            }

            TabsManagement.updateTabTitle(this.blocks, blockId, tabId, nextTitle);
            block.updatedAt = new Date().toISOString();
            this.invalidateRenderCache(blockId);
            this.invalidateBlockSettingsCache(blockId);
            this.invalidateJSONDisplayCache();
        }
    },

    updateTabContent(blockId, tabId, content) {
        const key = `tabs:content:${blockId}:${tabId}`;
        this.queueInlineContentUpdate(key, content, (latestContent) => {
            TabsManagement.updateTabContent(this.blocks, blockId, tabId, latestContent);
        });
    },

    commitTabContent(blockId, tabId, content) {
        const key = `tabs:content:${blockId}:${tabId}`;
        const pending = this.inlineContentBuffer.get(key);
        this.clearInlineContentUpdate(key);
        const resolvedContent = (content !== null && content !== undefined)
            ? content
            : (pending ? pending.content : null);
        const { block } = this.findBlockById(blockId);
        if (block) {
            const currentContent = block.tabsData?.items?.find(item => item.id === tabId)?.content ?? '';
            const nextContent = (resolvedContent !== null && resolvedContent !== undefined)
                ? resolvedContent
                : currentContent;
            const hasChanged = nextContent !== currentContent;
            if (!hasChanged) {
                this.invalidateJSONDisplayCache();
                return;
            }

            TabsManagement.updateTabContent(this.blocks, blockId, tabId, nextContent);
            block.updatedAt = new Date().toISOString();
            this.invalidateRenderCache(blockId);
            this.invalidateJSONDisplayCache();
        }
    },

    // Accordion Management Functions
    addAccordionItem(blockId) {
        this.blockIdCounter++;
        const result = AccordionManagement.addAccordionItem(this.blocks, blockId, this.blockIdCounter);
        if (result) {
            this.blockIdCounter = result.lastItemIdCounter + 1;
            this.invalidateRenderCache(blockId);
            this.invalidateBlockSettingsCache(blockId);
            this.invalidateJSONDisplayCache();
        }
    },

    removeAccordionItem(blockId, itemId) {
        AccordionManagement.removeAccordionItem(this.blocks, blockId, itemId);
        this.invalidateRenderCache(blockId);
        this.invalidateBlockSettingsCache(blockId);
        this.invalidateJSONDisplayCache();
    },

    toggleAccordionItem(blockId, itemId) {
        AccordionManagement.toggleAccordionItem(this.blocks, blockId, itemId);
        this.invalidateRenderCache(blockId);
        this.invalidateJSONDisplayCache();
    },

    updateAccordionQuestion(blockId, itemId, question) {
        const key = `accordion:question:${blockId}:${itemId}`;
        this.queueInlineContentUpdate(key, question, (latestQuestion) => {
            AccordionManagement.updateAccordionQuestion(this.blocks, blockId, itemId, latestQuestion);
        });
    },

    commitAccordionQuestion(blockId, itemId, question) {
        const key = `accordion:question:${blockId}:${itemId}`;
        const pending = this.inlineContentBuffer.get(key);
        this.clearInlineContentUpdate(key);
        const resolvedQuestion = (question !== null && question !== undefined)
            ? question
            : (pending ? pending.content : null);
        const { block } = this.findBlockById(blockId);
        if (block) {
            const currentQuestion = block.accordionData?.items?.find(item => item.id === itemId)?.question ?? '';
            const nextQuestion = (resolvedQuestion !== null && resolvedQuestion !== undefined)
                ? resolvedQuestion
                : currentQuestion;
            const hasChanged = nextQuestion !== currentQuestion;
            if (!hasChanged) {
                this.invalidateJSONDisplayCache();
                return;
            }

            AccordionManagement.updateAccordionQuestion(this.blocks, blockId, itemId, nextQuestion);
            block.updatedAt = new Date().toISOString();
            this.invalidateRenderCache(blockId);
            this.invalidateJSONDisplayCache();
        }
    },

    updateAccordionAnswer(blockId, itemId, answer) {
        const key = `accordion:answer:${blockId}:${itemId}`;
        this.queueInlineContentUpdate(key, answer, (latestAnswer) => {
            AccordionManagement.updateAccordionAnswer(this.blocks, blockId, itemId, latestAnswer);
        });
    },

    commitAccordionAnswer(blockId, itemId, answer) {
        const key = `accordion:answer:${blockId}:${itemId}`;
        const pending = this.inlineContentBuffer.get(key);
        this.clearInlineContentUpdate(key);
        const resolvedAnswer = (answer !== null && answer !== undefined)
            ? answer
            : (pending ? pending.content : null);
        const { block } = this.findBlockById(blockId);
        if (block) {
            const currentAnswer = block.accordionData?.items?.find(item => item.id === itemId)?.answer ?? '';
            const nextAnswer = (resolvedAnswer !== null && resolvedAnswer !== undefined)
                ? resolvedAnswer
                : currentAnswer;
            const hasChanged = nextAnswer !== currentAnswer;
            if (!hasChanged) {
                this.invalidateJSONDisplayCache();
                return;
            }

            AccordionManagement.updateAccordionAnswer(this.blocks, blockId, itemId, nextAnswer);
            block.updatedAt = new Date().toISOString();
            this.invalidateRenderCache(blockId);
            this.invalidateJSONDisplayCache();
        }
    },

    moveAccordionItem(blockId, itemIndex, direction) {
        AccordionManagement.moveAccordionItem(this.blocks, blockId, itemIndex, direction);
        this.invalidateRenderCache(blockId);
        this.invalidateJSONDisplayCache();
    },

    setAccordionBehavior(blockId, behavior) {
        AccordionManagement.setAccordionBehavior(this.blocks, blockId, behavior);
        this.invalidateRenderCache(blockId);
        this.invalidateBlockSettingsCache(blockId);
        this.invalidateJSONDisplayCache();
    },

    addChildToAccordionItem(blockId, itemId, childType) {
        if (!this.addComponentsEnabled) {
            return;
        }
        this.blockIdCounter++;
        const childBlock = AccordionManagement.addChildToAccordionItem(this.blocks, blockId, itemId, this.blockIdCounter, childType);
        if (!childBlock) {
            this.blockIdCounter--;
            return;
        }
        this.selectedBlockId = childBlock.id;
        this.invalidateRenderCache(blockId);
        this.invalidateJSONDisplayCache();
        this.$nextTick(() => {
            this.focusBlockElement(childBlock.id);
        });
    },

    moveAccordionChild(blockId, itemId, childIndex, direction) {
        AccordionManagement.moveAccordionChild(this.blocks, blockId, itemId, childIndex, direction);
        this.invalidateRenderCache(blockId);
        this.invalidateJSONDisplayCache();
    },
};
