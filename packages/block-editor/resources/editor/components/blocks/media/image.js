/**
 * Image Block Component
 * Enthält alle Informationen für Image-Blöcke an einem Ort
 * Ein Image-Block rendert ein Bild mit Platzhalter wenn kein Bild gesetzt ist
 */
import { BLOCK_TYPES } from '../../block-types.js';

const escapeAttribute = (value) => {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/'/g, '&#39;');
};

const escapeForSingleQuotedJs = (value) => {
    if (value === null || value === undefined) return '';
    return String(value).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
};

export const ImageBlock = {
    // Block-Typ Name
    type: 'image',
    
    // Konfiguration direkt aus BLOCK_TYPES
    options: BLOCK_TYPES.image,
    
    // Datenstruktur-Definition
    structure: {
        id: '',
        type: 'image',
        imageUrl: '',
        imageAlt: '',
        imageTitle: '',
        media_usables: [],
        style: '',
        classes: '',
        htmlId: '',
        createdAt: '',
        updatedAt: ''
    },
    
    // HTML-Template für Rendering
    // Diese Methode rendert den kompletten Image-Block-Container
    renderHTML(block, context = {}) {
        return this.renderImageHTML('block', block);
    },
    
    // Child-Version (für verschachtelte Blöcke)
    renderChildHTML(child, context = {}) {
        return this.renderImageHTML('child', child);
    },
    
    renderImageHTML(scope, data) {
        const imageUrl = (data.imageUrl || '').trim();
        const imageAlt = data.imageAlt || '';
        const imageTitle = data.imageTitle || '';
        const blockId = data.id || '';
        const htmlId = (data.htmlId || '').trim();
        const style = (data.style || '').trim();
        const customClasses = (data.classes || '').trim();
        const imageClasses = ['max-w-full h-auto rounded-lg cursor-pointer', customClasses].filter(Boolean).join(' ');
        const clickExpression = `handleImageBlockClick('${escapeForSingleQuotedJs(blockId)}')`;
        
        // Wenn kein Bild gesetzt ist, zeige Platzhalter
        if (!imageUrl) {
            return `
                <div class="relative w-full">
                    <div 
                        data-block-id="${escapeAttribute(blockId)}"
                        class="flex items-center justify-center border-2 border-dashed border-gray-300 rounded-lg p-8 bg-gray-50 min-h-[200px] cursor-pointer hover:bg-gray-100 transition-colors w-full"
                        @click.stop="${clickExpression}"
                    >
                        <div class="text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-sm font-medium">Bild auswählen</p>
                            <p class="text-xs text-gray-400 mt-1">Klicken Sie hier, um ein Bild hinzuzufügen</p>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Wenn Bild gesetzt ist, rendere das Bild
        return `
            <div class="relative w-full">
                <img 
                    data-block-id="${escapeAttribute(blockId)}"
                    src="${escapeAttribute(imageUrl)}"
                    alt="${escapeAttribute(imageAlt)}"
                    title="${escapeAttribute(imageTitle)}"
                    id="${escapeAttribute(htmlId)}"
                    style="${escapeAttribute(style)}"
                    class="${escapeAttribute(imageClasses)}"
                    @click.stop="${clickExpression}"
                />
            </div>
        `;
    },
    
    // Initialisierung
    initialize(block, blockIdCounter) {
        // Image-Block benötigt spezielle Felder
        if (!block.imageUrl) block.imageUrl = '';
        if (!block.imageAlt) block.imageAlt = '';
        if (!block.imageTitle) block.imageTitle = '';
        if (!Array.isArray(block.media_usables)) block.media_usables = [];
        return block;
    },
    
    // Sicherstellen dass Block initialisiert ist
    ensureInitialized(block, blockIdCounter) {
        if (block.imageUrl === undefined) block.imageUrl = '';
        if (block.imageAlt === undefined) block.imageAlt = '';
        if (block.imageTitle === undefined) block.imageTitle = '';
        if (!Array.isArray(block.media_usables)) block.media_usables = [];
        return block;
    },
    
    // Cleanup beim Typ-Wechsel
    cleanup(block) {
        delete block.imageUrl;
        delete block.imageAlt;
        delete block.imageTitle;
        delete block.media_usables;
        return block;
    },
    
    // Fokus-Verhalten: Bilder sind nicht direkt fokussierbar
    focusable: false,
    
    // Setzt den Fokus auf das Block-Element (für Bilder: scrollIntoView)
    // @param {HTMLElement} element - Das DOM-Element des Blocks
    // @param {object} block - Der Block-Objekt
    focus(element, block) {
        if (!element) return false;
        
        // Für Bilder: Scrolle zum Element
        if (element.scrollIntoView) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        return true;
    },
    
    // Einstellungen HTML für Sidebar
    getSettingsHTML(block, context = {}) {
        return `
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bild URL</label>
                    <input 
                        type="text"
                        x-model="block.imageUrl"
                        @input="updateImageUrl(block.id, block.imageUrl)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                        placeholder="https://..."
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alt Text</label>
                    <input 
                        type="text"
                        x-model="block.imageAlt"
                        @input="updateImageAlt(block.id, block.imageAlt)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                        placeholder="Beschreibung..."
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input 
                        type="text"
                        x-model="block.imageTitle"
                        @input="updateImageTitle(block.id, block.imageTitle)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                        placeholder="Titel..."
                    />
                </div>
            </div>
        `;
    }
};
