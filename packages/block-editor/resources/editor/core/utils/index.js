import { generateId } from './format.js';
import { findBlockById, getAllBlocks } from './json.js';
import {
    handlePlainTextPaste,
    initAllBlockContents,
    initBlockContent,
    sanitizeHtmlContent,
} from './dom.js';

export const Utils = {
    generateId,
    findBlockById,
    getAllBlocks,
    handlePlainTextPaste,
    initBlockContent,
    initAllBlockContents,
    sanitizeHtmlContent
};
