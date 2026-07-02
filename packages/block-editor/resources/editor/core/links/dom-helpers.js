export function applyLinkUnderline(linkElement) {
    if (!linkElement || linkElement.tagName !== 'A') {
        return;
    }

    linkElement.classList.add('underline');
    linkElement.style.textDecoration = 'underline';
}

export function applyLinkTarget(linkElement, target) {
    if (!linkElement || linkElement.tagName !== 'A') {
        return;
    }

    if (target && target !== '_self') {
        linkElement.setAttribute('target', target);
    } else {
        linkElement.removeAttribute('target');
    }
}

export function placeSelectionMarkers(range) {
    if (!range || range.collapsed) {
        return null;
    }

    const markerId = `link-marker-${Date.now()}-${Math.random().toString(36).slice(2)}`;
    const startMarker = document.createElement('span');
    const endMarker = document.createElement('span');

    startMarker.setAttribute('data-link-marker', 'start');
    endMarker.setAttribute('data-link-marker', 'end');
    startMarker.setAttribute('data-link-marker-id', markerId);
    endMarker.setAttribute('data-link-marker-id', markerId);
    startMarker.style.display = 'none';
    endMarker.style.display = 'none';

    const endRange = range.cloneRange();
    endRange.collapse(false);
    endRange.insertNode(endMarker);

    const startRange = range.cloneRange();
    startRange.collapse(true);
    startRange.insertNode(startMarker);

    return { id: markerId };
}

export function getRangeFromMarkers(markerIds) {
    if (!markerIds || !markerIds.id) {
        return null;
    }

    const startMarker = document.querySelector(`[data-link-marker="start"][data-link-marker-id="${markerIds.id}"]`);
    const endMarker = document.querySelector(`[data-link-marker="end"][data-link-marker-id="${markerIds.id}"]`);
    if (!startMarker || !endMarker) {
        return null;
    }

    const range = document.createRange();
    range.setStartAfter(startMarker);
    range.setEndBefore(endMarker);
    return range;
}

export function clearLinkMarkersById(markerId) {
    if (!markerId) {
        return;
    }

    const startMarker = document.querySelector(`[data-link-marker="start"][data-link-marker-id="${markerId}"]`);
    const endMarker = document.querySelector(`[data-link-marker="end"][data-link-marker-id="${markerId}"]`);

    if (startMarker && startMarker.parentNode) {
        startMarker.parentNode.removeChild(startMarker);
    }

    if (endMarker && endMarker.parentNode) {
        endMarker.parentNode.removeChild(endMarker);
    }
}

export function getLinkElementFromRange(range) {
    if (!range) {
        return null;
    }

    const container = range.commonAncestorContainer;
    const element = container.nodeType === 3 ? container.parentElement : container;
    return element ? element.closest('a[href]') : null;
}

export function createLinkFromRange(range, url, target) {
    if (!range || range.collapsed) {
        return null;
    }

    const link = document.createElement('a');
    link.setAttribute('href', url);
    if (target && target !== '_self') {
        link.setAttribute('target', target);
    }

    const fragment = range.extractContents();
    link.appendChild(fragment);
    range.insertNode(link);

    try {
        const selection = window.getSelection();
        if (selection) {
            selection.removeAllRanges();
            const newRange = document.createRange();
            newRange.selectNodeContents(link);
            selection.addRange(newRange);
        }
    } catch (_e) {
        // Ignore selection restoration errors.
    }

    return link;
}

export function getTableCellElementFromNode(node) {
    if (!node) {
        return null;
    }

    const element = node.nodeType === 3 ? node.parentElement : node;
    return element ? element.closest('[data-cell-id]') : null;
}

export function getTableCellElementFromSelection(selection) {
    if (!selection) {
        return null;
    }

    const node = selection.anchorNode || selection.focusNode;
    return getTableCellElementFromNode(node);
}
