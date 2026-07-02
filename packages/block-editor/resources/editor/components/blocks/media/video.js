/**
 * Video Block Component
 * Enthält alle Informationen für Video-Blöcke an einem Ort
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

export const VideoBlock = {
    type: 'video',
    
    // Konfiguration direkt aus BLOCK_TYPES
    options: BLOCK_TYPES.video,
    
    // Datenstruktur-Definition
    structure: {
        id: '',
        type: 'video',
        videoUrl: '',
        videoPoster: '',
        videoTitle: '',
        style: '',
        classes: '',
        htmlId: '',
        createdAt: '',
        updatedAt: ''
    },
    
    // HTML-Template für Rendering
    renderHTML(block, context = {}) {
        return this.renderVideoHTML('block', block);
    },
    
    // Child-Version (für verschachtelte Blöcke)
    renderChildHTML(child, context = {}) {
        return this.renderVideoHTML('child', child);
    },
    
    renderVideoHTML(scope, data) {
        const videoUrl = (data.videoUrl || '').trim();
        const videoPoster = (data.videoPoster || '').trim();
        const videoTitle = data.videoTitle || '';
        const blockId = data.id || '';
        const htmlId = (data.htmlId || '').trim();
        const style = (data.style || '').trim();
        const customClasses = (data.classes || '').trim();
        const videoClasses = ['w-full rounded-lg cursor-pointer', customClasses].filter(Boolean).join(' ');
        const clickExpression = `handleVideoBlockClick('${escapeForSingleQuotedJs(blockId)}')`;
        
        // Wenn kein Video gesetzt ist, zeige Platzhalter
        if (!videoUrl) {
            return `
                <div class="relative w-full">
                    <div 
                        data-block-id="${escapeAttribute(blockId)}"
                        class="flex items-center justify-center border-2 border-dashed border-gray-300 rounded-lg p-8 bg-gray-50 min-h-[200px] cursor-pointer hover:bg-gray-100 transition-colors w-full"
                        @click.stop="${clickExpression}"
                    >
                        <div class="text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M4 6h12a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2z"></path>
                            </svg>
                            <p class="text-sm font-medium">Video auswählen</p>
                            <p class="text-xs text-gray-400 mt-1">Klicken Sie hier, um ein Video hinzuzufügen</p>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Wenn Video gesetzt ist, rendere das Video
        return `
            <div class="relative w-full">
                <video 
                    data-block-id="${escapeAttribute(blockId)}"
                    src="${escapeAttribute(videoUrl)}"
                    poster="${escapeAttribute(videoPoster)}"
                    title="${escapeAttribute(videoTitle)}"
                    controls
                    id="${escapeAttribute(htmlId)}"
                    style="${escapeAttribute(style)}"
                    class="${escapeAttribute(videoClasses)}"
                    @click.stop="${clickExpression}"
                ></video>
            </div>
        `;
    },
    
    // Initialisierung
    initialize(block, blockIdCounter) {
        if (!block.videoUrl) block.videoUrl = '';
        if (!block.videoPoster) block.videoPoster = '';
        if (!block.videoTitle) block.videoTitle = '';
        return block;
    },
    
    ensureInitialized(block, blockIdCounter) {
        if (block.videoUrl === undefined) block.videoUrl = '';
        if (block.videoPoster === undefined) block.videoPoster = '';
        if (block.videoTitle === undefined) block.videoTitle = '';
        return block;
    },
    
    cleanup(block) {
        delete block.videoUrl;
        delete block.videoPoster;
        delete block.videoTitle;
        return block;
    },
    
    // Fokus-Verhalten: Videos sind nicht direkt fokussierbar
    focusable: false,
    
    // Setzt den Fokus auf das Block-Element (für Videos: scrollIntoView)
    focus(element, block) {
        if (!element) return false;
        if (element.scrollIntoView) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return true;
    },
    
    getSettingsHTML(block, context = {}) {
        return `
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Video URL</label>
                    <input 
                        type="text"
                        x-model="block.videoUrl"
                        @input="updateVideoUrl(block.id, block.videoUrl)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                        placeholder="https://..."
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Poster URL</label>
                    <input 
                        type="text"
                        x-model="block.videoPoster"
                        @input="updateVideoPoster(block.id, block.videoPoster)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                        placeholder="https://..."
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input 
                        type="text"
                        x-model="block.videoTitle"
                        @input="updateVideoTitle(block.id, block.videoTitle)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                        placeholder="Titel..."
                    />
                </div>
            </div>
        `;
    }
};
