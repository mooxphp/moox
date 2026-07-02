export function initializeBlockLinkModalState(linkModal, blockId, block) {
    if (!block || block.type !== 'link') {
        return false;
    }

    linkModal.blockId = blockId;
    linkModal.url = block.linkUrl || '';
    linkModal.target = block.linkTarget || '_blank';
    linkModal.text = block.content || '';
    linkModal.linkText = block.linkText || '';
    return true;
}

export function initializeSelectionLinkModalState(linkModal, selectedRange, selectedText, placeSelectionMarkers) {
    if (!selectedRange || !selectedText) {
        return false;
    }

    linkModal.range = selectedRange.cloneRange();
    linkModal.text = selectedText;
    linkModal.markerIds = placeSelectionMarkers(linkModal.range);
    return true;
}

export function initializeEditLinkModalState(linkModal, options = {}) {
    if (!options.element || !options.blockElement) {
        return false;
    }

    const blockId = options.blockElement.getAttribute('data-block-id');
    linkModal.element = options.element;
    linkModal.blockId = blockId;
    linkModal.url = options.element.getAttribute('href') || '';
    linkModal.text = options.element.textContent || '';
    linkModal.target = options.element.getAttribute('target') || '_blank';
    return true;
}
