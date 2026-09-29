import { createUniqueId, escapeHtmlAttribute, escapeJsSingleQuoted, generateId } from './format.js';
import { findBlockById, getAllBlocks } from './json.js';
import { initBlockContent, initAllBlockContents, isSafeAttributeUrl, safeHrefUrl, sanitizeHtmlContent } from './dom.js';

export const Utils = {
    generateId,
    createUniqueId,
    escapeHtmlAttribute,
    escapeJsSingleQuoted,
    findBlockById,
    getAllBlocks,
    initBlockContent,
    initAllBlockContents,
    isSafeAttributeUrl,
    safeHrefUrl,
    sanitizeHtmlContent
};
