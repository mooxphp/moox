// Block/Child/Checklist/Table Management Functions - zentral zusammengeführt
import { Utils } from '../utils/index.js';
import { BlockTypes } from '../../components/block-types.js';
import { initializeBlock, ensureBlockInitialized, cleanupBlock } from '../../components/blocks/index.js';
import { createTableManagement } from './table-management.js';
import { createTabsManagement } from './tabs-management.js';
import { createAccordionManagement } from './accordion-management.js';
import { createChecklistManagement } from './checklist-management.js';
import { createListManagement } from './list-management.js';
import { createChildManagement } from './child-management.js';
import { createBlockManagement } from './block-management.js';
import { createBlockManagementHelpers } from './block-management-helpers.js';

const { createChildBlock, touchBlock, ensureChildrenArray, ensureParentCanHaveChildren } = createBlockManagementHelpers({
    utils: Utils,
    blockTypes: BlockTypes,
    initializeBlock,
});

export const BlockManagement = createBlockManagement({
    utils: Utils,
    blockTypes: BlockTypes,
    initializeBlock,
    ensureBlockInitialized,
    cleanupBlock
});

export const ChildManagement = createChildManagement({
    findBlockById: Utils.findBlockById,
    isColumnLikeBlock: BlockTypes.isColumnLikeBlock.bind(BlockTypes),
    ensureParentCanHaveChildren,
    ensureChildrenArray,
    createChildBlock,
    touchBlock
});

export const ChecklistManagement = createChecklistManagement();
export const ListManagement = createListManagement();

export const TabsManagement = createTabsManagement({
    createBlock: BlockManagement.createBlock.bind(BlockManagement)
});

export const AccordionManagement = createAccordionManagement({
    createBlock: BlockManagement.createBlock.bind(BlockManagement)
});

export const TableManagement = createTableManagement({
    createBlock: BlockManagement.createBlock.bind(BlockManagement)
});
