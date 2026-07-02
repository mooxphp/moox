// DOM Utilities
function isSafeAttributeUrl(attributeName, value) {
    if (!value || typeof value !== 'string') {
        return false;
    }

    const normalizedValue = value.trim().toLowerCase();

    if (normalizedValue.startsWith('javascript:')) {
        return false;
    }

    if (attributeName === 'href') {
        return (
            normalizedValue.startsWith('http://')
            || normalizedValue.startsWith('https://')
            || normalizedValue.startsWith('mailto:')
            || normalizedValue.startsWith('tel:')
            || normalizedValue.startsWith('/')
            || normalizedValue.startsWith('#')
        );
    }

    if (attributeName === 'src') {
        return (
            normalizedValue.startsWith('http://')
            || normalizedValue.startsWith('https://')
            || normalizedValue.startsWith('data:image/')
            || normalizedValue.startsWith('blob:')
            || normalizedValue.startsWith('/')
        );
    }

    return false;
}

export function sanitizeHtmlContent(content) {
    if (!content || typeof content !== 'string') {
        return '';
    }

    const allowedTags = new Set([
        'A', 'P', 'DIV', 'SPAN', 'BR', 'STRONG', 'EM', 'B', 'I', 'U', 'S',
        'BLOCKQUOTE', 'PRE', 'CODE', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6',
        'UL', 'OL', 'LI', 'HR', 'IMG', 'VIDEO', 'TABLE', 'THEAD', 'TBODY',
        'TFOOT', 'TR', 'TH', 'TD'
    ]);

    const allowedAttributes = new Set([
        'href', 'target', 'rel', 'title', 'src', 'alt', 'controls', 'poster',
        'colspan', 'rowspan'
    ]);

    const template = document.createElement('template');
    template.innerHTML = content;

    const sanitizeNode = (node) => {
        if (node.nodeType !== Node.ELEMENT_NODE) {
            return;
        }

        const element = node;
        const tagName = element.tagName;

        if (!allowedTags.has(tagName)) {
            const parent = element.parentNode;
            if (!parent) {
                return;
            }

            while (element.firstChild) {
                parent.insertBefore(element.firstChild, element);
            }
            parent.removeChild(element);
            return;
        }

        [...element.attributes].forEach((attribute) => {
            const name = attribute.name.toLowerCase();
            const value = attribute.value;

            if (name.startsWith('on')) {
                element.removeAttribute(attribute.name);
                return;
            }

            if (!allowedAttributes.has(name)) {
                element.removeAttribute(attribute.name);
                return;
            }

            if ((name === 'href' || name === 'src') && !isSafeAttributeUrl(name, value)) {
                element.removeAttribute(attribute.name);
                return;
            }
        });

        if (tagName === 'A' && element.getAttribute('target') === '_blank') {
            element.setAttribute('rel', 'noopener noreferrer');
        }

        [...element.childNodes].forEach(sanitizeNode);
    };

    [...template.content.childNodes].forEach(sanitizeNode);

    return template.innerHTML;
}

export function initBlockContent(element, block, isTextContent = false) {
    // Prüfe ob Element existiert
    if (!element || !block) return;
    
    // Für Link-Blöcke: Immer Inhalt setzen, wenn Werte vorhanden sind
    if (block.type === 'link') {
        // Bestimme den anzuzeigenden Text: linkText > content > linkUrl
        let content = block.linkText || block.content || block.linkUrl || '';
        
        if (content) {
            try {
                // Für Link-Blöcke immer textContent verwenden (kein HTML)
                element.textContent = content;
                // Aktualisiere auch block.content, falls es leer war
                if (!block.content || block.content.trim() === '') {
                    block.content = content;
                }
            } catch (error) {
                console.warn('Fehler beim Initialisieren des Link-Block-Inhalts:', error);
            }
        }
        return;
    }
    
    // Für andere Block-Typen: Setze Inhalt nur wenn Element leer ist und Block Inhalt hat
    if (!element.textContent) {
        let content = block.content;
        
        if (content) {
            try {
                if (isTextContent) {
                    element.textContent = content;
                } else {
                    element.innerHTML = sanitizeHtmlContent(content);
                }
            } catch (error) {
                console.warn('Fehler beim Initialisieren des Block-Inhalts:', error);
            }
        }
    }
}

export function initAllBlockContents(blocks) {
    if (!blocks || !Array.isArray(blocks)) return;
    
    // Performance-Optimierung: Sammle alle IDs und führe Batch-Query durch
    const blockIds = new Set();
    const cellIds = new Set();
    
    // Sammle alle IDs
    blocks.forEach(block => {
        if (!block || !block.id) return;
        blockIds.add(block.id);
        
        // Sammle Tabellenzellen-IDs
        if (block.type === 'table' && block.tableData && block.tableData.cells) {
            block.tableData.cells.forEach(row => {
                if (!Array.isArray(row)) return;
                row.forEach(cell => {
                    if (cell && cell.id) {
                        cellIds.add(cell.id);
                    }
                });
            });
        }
        
        // Sammle Child-IDs
        if (block.children && Array.isArray(block.children)) {
            block.children.forEach(child => {
                if (child && child.id) {
                    blockIds.add(child.id);
                }
            });
        }
    });
    
    // Batch-Query für Block-Elemente (nur einmal querySelectorAll)
    const blockElementsMap = new Map();
    if (blockIds.size > 0) {
        const allBlockElements = document.querySelectorAll('[data-block-id]');
        allBlockElements.forEach(el => {
            const id = el.getAttribute('data-block-id');
            if (blockIds.has(id)) {
                blockElementsMap.set(id, el);
            }
        });
    }
    
    // Batch-Query für Zellen-Elemente
    const cellElementsMap = new Map();
    if (cellIds.size > 0) {
        const allCellElements = document.querySelectorAll('[data-cell-id]');
        allCellElements.forEach(el => {
            const id = el.getAttribute('data-cell-id');
            if (cellIds.has(id)) {
                cellElementsMap.set(id, el);
            }
        });
    }
    
    // Initialisiere Blöcke mit gecachten Elementen
    blocks.forEach(block => {
        if (!block || !block.id) return;
        
        try {
            const element = blockElementsMap.get(block.id);
            if (element && !element.textContent) {
                let content = block.content;
                
                if (content) {
                    if (block.type === 'code') {
                        element.textContent = content;
                    } else {
                        element.innerHTML = sanitizeHtmlContent(content);
                    }
                }
            }
            
            // Initialisiere Tabellenzellen mit gecachten Elementen
            if (block.type === 'table' && block.tableData && block.tableData.cells) {
                block.tableData.cells.forEach(row => {
                    if (!Array.isArray(row)) return;
                    row.forEach(cell => {
                        if (!cell || !cell.id) return;
                        if (cell.blocks && cell.blocks.length > 0) return;
                        try {
                            const cellElement = cellElementsMap.get(cell.id);
                            if (cellElement && !cellElement.textContent && cell.content) {
                                cellElement.innerHTML = sanitizeHtmlContent(cell.content);
                            }
                        } catch (error) {
                            console.warn('Fehler beim Initialisieren der Tabellenzelle:', error);
                        }
                    });
                });
            }
            
            // Initialisiere Children mit gecachten Elementen
            if (block.children && Array.isArray(block.children)) {
                block.children.forEach(child => {
                    if (!child || !child.id) return;
                    try {
                        const childElement = blockElementsMap.get(child.id);
                        if (childElement && !childElement.textContent) {
                            let content = child.content;
                            
                            if (content) {
                                if (child.type === 'code') {
                                    childElement.textContent = content;
                                } else {
                                    childElement.innerHTML = sanitizeHtmlContent(content);
                                }
                            }
                        }
                    } catch (error) {
                        console.warn('Fehler beim Initialisieren des Child-Blocks:', error);
                    }
                });
            }
        } catch (error) {
            console.warn('Fehler beim Initialisieren des Blocks:', error);
        }
    });
}
