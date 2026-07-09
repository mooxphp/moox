import { getBlockComponent } from '../../components/blocks/index.js';
import { getChildrenSignature as buildChildrenSignature, getRenderSignature as buildRenderSignature } from './signature.js';

function escapeHtmlAttribute(value) {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/'/g, '&#39;');
}

function escapeJsSingleQuoted(value) {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value)
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'");
}

function normalizeDataBlockIdBindings(html, block = null) {
    if (typeof html !== 'string' || html.length === 0) {
        return html;
    }

    // Defensiver Fallback für ältere/zwischengespeicherte Templates:
    // Statische IDs dürfen nicht als Alpine-Expression mit ":" gebunden werden.
    let normalized = html.replace(/:data-block-id=(["'])(block-[^"']+)\1/g, 'data-block-id="$2"');

    if (!block || typeof block !== 'object') {
        return normalized;
    }

    const blockId = escapeHtmlAttribute(block.id ?? '');
    const escapedBlockIdForJs = escapeJsSingleQuoted(block.id ?? '');
    const htmlId = escapeHtmlAttribute(block.htmlId ?? '');
    const style = escapeHtmlAttribute(block.style ?? '');
    const classes = escapeHtmlAttribute(block.classes ?? '');
    const combinedClasses = escapeHtmlAttribute(
        ['max-w-full h-auto rounded-lg cursor-pointer', String(block.classes ?? '').trim()]
            .filter((entry) => entry !== '')
            .join(' ')
    );

    // Fallback für ältere Image-Templates, die `block.*` außerhalb des Scopes referenzieren.
    normalized = normalized
        .replace(/\s+x-show=(["'])block\.type === 'image'\1/g, '')
        .replace(/:data-block-id=(["'])(?:block|child)\.id\1/g, `data-block-id="${blockId}"`)
        .replace(/:id=(["'])(?:block|child)\.htmlId \|\| null\1/g, `id="${htmlId}"`)
        .replace(/:style=(["'])(?:block|child)\.style \|\| ''\1/g, `style="${style}"`)
        .replace(
            /:class=(["'])\['max-w-full h-auto rounded-lg cursor-pointer', (?:block|child)\.classes \|\| ''\]\1/g,
            `class="${combinedClasses}"`
        )
        .replace(/:class=(["'])(?:block|child)\.classes \|\| ''\1/g, `class="${classes}"`)
        .replace(
            /@click\.stop=(["'])handleImageBlockClick\((?:block|child)\.id\)\1/g,
            `@click.stop="handleImageBlockClick('${escapedBlockIdForJs}')"`
        );

    return normalized;
}

function withBlockScope(html, escapedBlockId, index = -1) {
    return `
        <div x-data="{
            get block() { return (findBlockById('${escapedBlockId}') || {}).block || {}; },
            get column() { return null; },
            index: ${index},
            childIndex: -1
        }">${html}</div>
    `;
}

function withChildScope(html, escapedChildId, escapedParentId, childIndex = -1) {
    return `
        <div x-data="{
            get child() { return (findBlockById('${escapedChildId}') || {}).block || {}; },
            get block() { return (findBlockById('${escapedParentId}') || {}).block || {}; },
            get column() { return (findBlockById('${escapedParentId}') || {}).block || {}; },
            childIndex: ${childIndex}
        }">${html}</div>
    `;
}

export const editorRenderMethods = {
    /**
     * Zentrale Rendering-Funktion für alle Block-Typen
     * @param {object} block - Der Block-Objekt
     * @returns {string} HTML-String für den Block
     */
    renderBlock(block, index = null) {
        if (!block || !block.type) {
            console.warn('renderBlock: Block ohne Typ gefunden', block);
            return '';
        }

        if (block.type === 'dynamicFeed') {
            // Alpine-Abhängigkeiten für Vorschau und Inline-Konfiguration
            void this.blockSettingsVersion;
            void this.dynamicFeedSourcesLoading;
            void this.dynamicFeedSources.length;
            void this.dynamicFeedPreviewLoading[block.id];
            void this.dynamicFeedPreviewError[block.id];
            void this.dynamicFeedPreviewByBlockId[block.id];
        }

        // Render-Signatur pro Block (nur bei Änderungen neu rendern)
        const signature = this.getRenderSignature(block);
        const cached = this.renderBlockCache.get(block.id);

        if (cached && cached.signature === signature) {
            return cached.html;
        }

        // Container-Blöcke werden jetzt auch über diese Funktion gerendert
        const component = getBlockComponent(block.type);
        if (component && component.renderHTML) {
            const resolvedIndex = Number.isInteger(index) ? index : -1;
            const escapedBlockIdForJs = escapeJsSingleQuoted(block.id ?? '');
            const html = normalizeDataBlockIdBindings(component.renderHTML(block, {
                childBlockTypes: this.childBlockTypes,
                addComponentsEnabled: this.addComponentsEnabled,
                index: resolvedIndex
            }), block);
            const scopedHtml = withBlockScope(html, escapedBlockIdForJs, resolvedIndex);
            // Speichere im Cache
            this.renderBlockCache.set(block.id, {
                html: scopedHtml,
                signature: signature
            });

            return scopedHtml;
        }
        return '';
    },

    /**
     * Erstellt einen Cache-Key für Block-Rendering
     * @param {object} block - Der Block-Objekt
     * @returns {string} Cache-Key
     */
    getRenderSignature(block) {
        return buildRenderSignature(block);
    },

    /**
     * Erstellt eine stabile Signatur für Children-Strukturen
     * @param {Array} children - Children Array
     * @returns {string} - Signatur-String
     */
    getChildrenSignature(children) {
        return buildChildrenSignature(children);
    },

    /**
     * Invalidiert den Render-Cache für einen Block oder alle
     * @param {string|null} blockId - Die Block-ID oder null für alle
     */
    invalidateRenderCache(blockId = null) {
        if (blockId) {
            this.renderBlockCache.delete(blockId);
            this.renderChildCache.delete(blockId);
        } else {
            this.renderBlockCache.clear();
            this.renderChildCache.clear();
        }
    },

    /**
     * Zentrale Rendering-Funktion für alle Child-Blöcke
     * @param {object} child - Der Child-Block-Objekt
     * @param {object} parentBlock - Der Parent-Block-Objekt
     * @param {number} childIndex - Der Index des Child-Blocks
     * @returns {string} HTML-String für den Child-Block
     */
    renderChild(child, parentBlock, childIndex) {
        if (!child || !child.type) {
            console.warn('renderChild: Child ohne Typ gefunden', child);
            return '';
        }

        const component = getBlockComponent(child.type);
        if (component && component.renderChildHTML) {
            const signature = this.getRenderSignature(child);
            const cached = this.renderChildCache.get(child.id);

            if (cached && cached.signature === signature) {
                return cached.html;
            }

            const escapedChildIdForJs = escapeJsSingleQuoted(child.id ?? '');
            const escapedParentIdForJs = escapeJsSingleQuoted(parentBlock?.id ?? '');
            const resolvedChildIndex = Number.isInteger(childIndex) ? childIndex : -1;
            const html = normalizeDataBlockIdBindings(component.renderChildHTML(child, {
                block: parentBlock,
                addComponentsEnabled: this.addComponentsEnabled,
                childIndex: childIndex
            }), child);
            const scopedHtml = withChildScope(html, escapedChildIdForJs, escapedParentIdForJs, resolvedChildIndex);
            this.renderChildCache.set(child.id, {
                html: scopedHtml,
                signature: signature
            });

            return scopedHtml;
        }
        return '';
    },
};
