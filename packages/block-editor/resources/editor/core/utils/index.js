import { generateId } from './format.js';
import { findBlockById, getAllBlocks } from './json.js';
import { initBlockContent, initAllBlockContents, sanitizeHtmlContent } from './dom.js';

export const Utils = {
    generateId,
    findBlockById,
    getAllBlocks,
    initBlockContent,
    initAllBlockContents,
    sanitizeHtmlContent
};
