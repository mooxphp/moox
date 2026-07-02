/**
 * Embed Block Component
 * URL-basierter Embed-Block (z. B. YouTube/Vimeo)
 */
import { BLOCK_TYPES } from '../../block-types.js';
import { normalizeEmbedUrl } from '../../../core/utils/embed-url.js';

const escapeAttribute = (value) => {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/'/g, '&#39;');
};

export const EmbedBlock = {
    type: 'embed',
    options: BLOCK_TYPES.embed,
    structure: {
        id: '',
        type: 'embed',
        embedUrl: '',
        embedTitle: '',
        style: '',
        classes: '',
        htmlId: '',
        createdAt: '',
        updatedAt: ''
    },

    renderHTML(block, context = {}) {
        return this.renderEmbedHTML('block', block);
    },

    renderChildHTML(child, context = {}) {
        return this.renderEmbedHTML('child', child);
    },

    renderEmbedHTML(scope, data) {
        const normalized = normalizeEmbedUrl(data.embedUrl || '');
        const embedUrl = normalized.ok ? normalized.value : '';
        const embedTitle = data.embedTitle || 'Embed';

        if (!embedUrl) {
            return `
                <div x-show="${scope}.type === 'embed'" class="relative w-full">
                    <div
                        :data-block-id="${scope}.id"
                        class="flex items-center justify-center border-2 border-dashed border-gray-300 rounded-lg p-8 bg-gray-50 min-h-[200px] cursor-pointer hover:bg-gray-100 transition-colors w-full"
                        @click.stop="handleEmbedBlockClick(${scope}.id)"
                    >
                        <div class="text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 000 5.656m-3.656-9.9a4 4 0 015.656 0M7.757 16.243a4 4 0 010-5.657m8.486 8.486a4 4 0 010-5.657M5 12h14"></path>
                            </svg>
                            <p class="text-sm font-medium">Embed hinzufügen</p>
                            <p class="text-xs text-gray-400 mt-1">Klicken Sie hier, um einen Embed-Link einzufügen</p>
                        </div>
                    </div>
                </div>
            `;
        }

        return `
            <div x-show="${scope}.type === 'embed'" class="relative w-full">
                <div class="relative w-full overflow-hidden rounded-lg border border-gray-200 bg-black aspect-video">
                    <iframe
                        :data-block-id="${scope}.id"
                        src="${escapeAttribute(embedUrl)}"
                        title="${escapeAttribute(embedTitle)}"
                        loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                        referrerpolicy="strict-origin-when-cross-origin"
                        :id="${scope}.htmlId || null"
                        :style="${scope}.style || ''"
                        :class="['h-full w-full cursor-pointer', ${scope}.classes || '']"
                        @click.stop="handleEmbedBlockClick(${scope}.id)"
                    ></iframe>
                </div>
            </div>
        `;
    },

    initialize(block, blockIdCounter) {
        if (!block.embedUrl) block.embedUrl = '';
        if (!block.embedTitle) block.embedTitle = '';
        return block;
    },

    ensureInitialized(block, blockIdCounter) {
        if (block.embedUrl === undefined) block.embedUrl = '';
        if (block.embedTitle === undefined) block.embedTitle = '';
        return block;
    },

    cleanup(block) {
        delete block.embedUrl;
        delete block.embedTitle;
        return block;
    },

    focusable: false,

    focus(element, block) {
        if (!element) return false;
        if (element.scrollIntoView) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return true;
    }
};
