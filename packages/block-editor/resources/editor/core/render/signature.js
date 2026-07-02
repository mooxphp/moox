import { BlockTypes } from '../../components/block-types.js';

/** Erhöhen, wenn sich Block-HTML-Templates ändern (invalidiert renderBlockCache). */
export const EDITOR_RENDER_TEMPLATE_REVISION = 1;

export function getRenderSignature(block) {
    if (!block || !block.id) {
        return '';
    }

    // Signature based on type + timestamps when available.
    const version = block.updatedAt || block.createdAt || '';
    let key = `rt${EDITOR_RENDER_TEMPLATE_REVISION}_${block.type}_${version}`;

    // Fallback when no timestamp exists.
    if (!version) {
        const contentHash = block.content ? `${block.content.length}_${block.content.substring(0, 20)}` : 'empty';
        key += `_${contentHash}`;
    }

    if (block.type === 'table' && block.tableData) {
        const rowCount = block.tableData.cells?.length || 0;
        const colCount = block.tableData.cells?.[0]?.length || 0;
        key += `_t${rowCount}x${colCount}_${block.tableData.hasHeader ? 'h1' : 'h0'}_${block.tableData.hasFooter ? 'f1' : 'f0'}`;
        if (block.tableData.cells) {
            const cellBlocksSig = block.tableData.cells.flatMap((row) => (row || []).map((cell) => (cell?.blocks?.length || 0)));
            key += '_cb' + cellBlocksSig.join(',');
        }
    }

    if (block.type === 'checklist' && block.checklistData) {
        const itemsCount = block.checklistData.items?.length || 0;
        key += `_c${itemsCount}`;
    }

    if (block.type === 'list' && block.listData) {
        const itemsCount = block.listData.items?.length || 0;
        const listStyle = block.listData.listStyle || 'unordered';
        key += `_l${itemsCount}_${listStyle}`;
    }

    if (block.type === 'image') {
        key += `_${block.imageUrl || ''}_${block.imageAlt || ''}_${block.imageTitle || ''}`;
    }

    if (block.type === 'video') {
        key += `_${block.videoUrl || ''}_${block.videoPoster || ''}_${block.videoTitle || ''}`;
    }

    if (block.type === 'link') {
        key += `_${block.linkUrl || ''}_${block.linkText || ''}_${block.linkTarget || ''}`;
    }

    if (BlockTypes.isColumnLikeBlock(block.type) && block.children) {
        key += `_cols${block.children.length}`;
    }

    if (Array.isArray(block.children) && block.children.length > 0) {
        key += `_ch${getChildrenSignature(block.children)}`;
    }

    return key;
}

export function getChildrenSignature(children) {
    if (!Array.isArray(children) || children.length === 0) {
        return 'none';
    }

    return children
        .map((child) => {
            if (!child || !child.id || !child.type) {
                return 'invalid';
            }
            return getRenderSignature(child);
        })
        .join('|');
}
