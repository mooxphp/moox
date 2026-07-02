export function isToolbarAllowedForBlock(block) {
    return Boolean(block) && block.type !== 'code' && block.type !== 'divider';
}

export function findLinkInSelection(selection, blockElement) {
    try {
        const anchorNode = selection?.anchorNode;
        if (!anchorNode || !blockElement) {
            return null;
        }

        let parent = anchorNode.nodeType === 3 ? anchorNode.parentElement : anchorNode;
        while (parent && parent !== blockElement) {
            if (parent.tagName === 'A' && parent.hasAttribute('href')) {
                return parent;
            }
            parent = parent.parentElement;
        }
    } catch (_error) {
        // Ignore selection traversal errors and fallback to no-link behavior.
    }

    return null;
}
