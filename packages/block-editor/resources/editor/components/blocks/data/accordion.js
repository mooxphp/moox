/**
 * Accordion Block Component
 * FAQ-/Accordion-Block mit Frage/Antwort-Items.
 */
import { BLOCK_TYPES } from '../../block-types.js';
import { AccordionManagement } from '../../../core/blocks/management.js';

export const AccordionBlock = {
    type: 'accordion',

    options: BLOCK_TYPES.accordion,

    structure: {
        id: '',
        type: 'accordion',
        accordionData: null,
        style: '',
        classes: '',
        htmlId: '',
        createdAt: '',
        updatedAt: ''
    },

    renderHTML(block, context = {}) {
        return this.renderAccordionHTML('block', block);
    },

    renderChildHTML(child, context = {}) {
        return this.renderAccordionHTML('child', child);
    },

    renderAccordionHTML(scope, data) {
        const blockId = data.id || '';
        const itemsLength = data.accordionData?.items?.length || 0;

        return `
            <div x-show="${scope}.type === 'accordion'"
                 data-block-id="${blockId}"
                 :id="${scope}.htmlId || null"
                 :style="${scope}.style || ''"
                 :class="['border border-gray-300 rounded-lg', ${scope}.classes || '']">
                <template x-for="(item, itemIndex) in (${scope}.accordionData?.items || [])" :key="item.id">
                    <div class="border-b border-gray-200 last:border-b-0">
                        <button type="button"
                            @click.stop="toggleAccordionItem('${blockId}', item.id)"
                            class="w-full flex items-center justify-between gap-2 px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors text-left">
                            <span class="font-medium text-sm text-gray-900" x-text="(item.question || '').replace(/<[^>]*>/g, '').trim() || ('Frage ' + (itemIndex + 1))"></span>
                            <span class="text-gray-500" x-text="item.expanded ? '−' : '+'"></span>
                        </button>

                        <div x-show="item.expanded" class="p-4 space-y-2 bg-white">
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Frage</div>
                            <div
                                class="block-placeholder min-h-[1.5rem] text-sm p-2 border border-gray-200 rounded"
                                contenteditable="true"
                                data-placeholder="Frage eingeben..."
                                x-init="$nextTick(() => { $el.innerHTML = $sanitizeHtml(item.question || ''); })"
                                @paste.prevent="handlePlainTextPaste($event)"
                                @input="updateAccordionQuestion('${blockId}', item.id, $event.target.innerHTML)"
                                @blur="commitAccordionQuestion('${blockId}', item.id, $event.target.innerHTML)"
                                @focus="initBlockContent($event.target, { content: item.question || '' })"
                            ></div>

                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Antwort</div>
                            <div class="space-y-2 p-3 border border-gray-200 rounded-lg bg-white">
                                <template x-for="(child, childIndex) in (item.children || [])" :key="child.id">
                                    <div
                                        :class="{
                                            'ring-2 ring-blue-500': selectedBlockId === child.id,
                                            'opacity-50': draggingBlockId === child.id
                                        }"
                                        class="block-item group relative p-2 rounded-lg hover:bg-gray-50 transition-all"
                                        @click.stop="selectBlock(child.id)"
                                    >
                                        <div x-show="selectedBlockId === child.id" class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500 rounded-l"></div>
                                        <div :class="selectedBlockId === child.id ? 'opacity-100' : ''" class="absolute -left-16 top-2 flex items-center gap-2 opacity-0 transition-opacity z-10">
                                            <div class="relative" x-data="{ showAccordionChildMenu: false }">
                                                <button type="button" @click.stop="showAccordionChildMenu = !showAccordionChildMenu"
                                                    class="flex h-7 w-7 items-center justify-center bg-white rounded shadow hover:bg-gray-100 text-xs"
                                                    title="Block-Menü">
                                                    ⋯
                                                </button>
                                                <div x-show="showAccordionChildMenu" @click.outside="showAccordionChildMenu = false" x-transition
                                                    class="absolute left-1/2 top-full mt-2 w-40 -translate-x-1/2 rounded-lg border border-gray-200 bg-white p-1 shadow-lg">
                                                    <button type="button" @click.stop="openSidebar(child.id); showAccordionChildMenu = false"
                                                        class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm hover:bg-gray-100"
                                                        title="Einstellungen öffnen">
                                                        <span>⚙</span>
                                                        <span>Einstellungen</span>
                                                    </button>
                                                    <button type="button" @click.stop="deleteBlock(child.id); showAccordionChildMenu = false"
                                                        class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                                                        title="Löschen">
                                                        <span>×</span>
                                                        <span>Löschen</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div :class="selectedBlockId === child.id ? 'opacity-100' : ''" class="absolute -right-20 top-1/2 -translate-y-1/2 flex items-center gap-2 opacity-0 transition-opacity z-10">
                                            <button type="button" @click.stop="moveAccordionChild('${blockId}', item.id, childIndex, 'up')"
                                                :disabled="childIndex === 0"
                                                :class="childIndex === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                                                class="flex h-7 w-7 items-center justify-center bg-white rounded shadow hover:bg-gray-100" title="Nach oben">
                                                ↑
                                            </button>
                                            <button type="button" @click.stop="moveAccordionChild('${blockId}', item.id, childIndex, 'down')"
                                                :disabled="childIndex === ((item.children || []).length - 1)"
                                                :class="childIndex === ((item.children || []).length - 1) ? 'opacity-50 cursor-not-allowed' : ''"
                                                class="flex h-7 w-7 items-center justify-center bg-white rounded shadow hover:bg-gray-100" title="Nach unten">
                                                ↓
                                            </button>
                                        </div>
                                        <div class="block-content">
                                            <div
                                                x-html="renderChild(child, item, childIndex)"
                                                x-init="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
                                                x-effect="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
                                            ></div>
                                        </div>
                                    </div>
                                </template>
                                <select
                                    x-show="addComponentsEnabled"
                                    @change.stop="addChildToAccordionItem('${blockId}', item.id, $event.target.value); $event.target.value = ''"
                                    @click.stop
                                    class="px-3 py-1 text-sm border border-gray-300 rounded bg-white w-full"
                                >
                                    <option value="">+ Block hinzufügen</option>
                                    <template x-for="(config, blockType) in childBlockTypes" :key="blockType">
                                        <option :value="blockType" x-text="config.label || blockType"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="flex gap-2 pt-1">
                                <button type="button" @click.stop="moveAccordionItem('${blockId}', itemIndex, 'up')"
                                    :disabled="itemIndex === 0"
                                    :class="itemIndex === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                                    class="px-2 py-1 text-xs bg-gray-100 rounded hover:bg-gray-200">
                                    Nach oben
                                </button>
                                <button type="button" @click.stop="moveAccordionItem('${blockId}', itemIndex, 'down')"
                                    :disabled="itemIndex === ((${scope}.accordionData?.items || []).length - 1)"
                                    :class="itemIndex === ((${scope}.accordionData?.items || []).length - 1) ? 'opacity-50 cursor-not-allowed' : ''"
                                    class="px-2 py-1 text-xs bg-gray-100 rounded hover:bg-gray-200">
                                    Nach unten
                                </button>
                                <button type="button" @click.stop="removeAccordionItem('${blockId}', item.id)"
                                    :disabled="((${scope}.accordionData?.items || []).length <= 1)"
                                    :class="((${scope}.accordionData?.items || []).length <= 1) ? 'opacity-50 cursor-not-allowed' : ''"
                                    class="px-2 py-1 text-xs bg-red-50 text-red-600 rounded hover:bg-red-100">
                                    Löschen
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="p-3 bg-gray-50 border-t border-gray-200">
                    <button type="button" @click.stop="addAccordionItem('${blockId}')"
                        class="px-3 py-1.5 text-sm bg-white border border-gray-300 rounded hover:bg-gray-100">
                        + FAQ-Eintrag hinzufügen
                    </button>
                    <div x-show="${itemsLength === 0}" class="text-xs text-gray-500 mt-2">Keine Einträge vorhanden</div>
                </div>
            </div>
        `;
    },

    initialize(block, blockIdCounter) {
        const accordionData = AccordionManagement.initializeAccordion(blockIdCounter, 2);
        block.accordionData = accordionData;
        block.accordionData.lastItemIdCounter = accordionData.lastItemIdCounter;
        return block;
    },

    ensureInitialized(block, blockIdCounter) {
        if (!block.accordionData) {
            block.accordionData = AccordionManagement.initializeAccordion(blockIdCounter, 2);
        } else if (Array.isArray(block.accordionData.items)) {
            block.accordionData.items.forEach((item) => {
                if (!Array.isArray(item.children)) {
                    item.children = [];
                }
            });
        }
        return block;
    },

    cleanup(block) {
        if (block.accordionData) {
            delete block.accordionData;
        }
        return block;
    },

    focusable: false,

    getSettingsHTML(block, context = {}) {
        const itemsCount = block.accordionData?.items?.length || 0;
        const behavior = block.accordionData?.behavior || 'single';
        return `
            <div class="pt-4 border-t border-gray-200 space-y-3">
                <div class="text-sm text-gray-600">Einträge: ${itemsCount}</div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Verhalten</label>
                    <select
                        x-model="block.accordionData.behavior"
                        @change="setAccordionBehavior(block.id, $event.target.value)"
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="single" ${behavior === 'single' ? 'selected' : ''}>Nur ein Eintrag geöffnet</option>
                        <option value="multiple" ${behavior === 'multiple' ? 'selected' : ''}>Mehrere Einträge geöffnet</option>
                        <option value="none" ${behavior === 'none' ? 'selected' : ''}>Kein Eintrag ist geöffnet</option>
                    </select>
                </div>
                <button type="button" @click="addAccordionItem(block.id)"
                    class="px-3 py-1 bg-gray-100 rounded text-sm hover:bg-gray-200">
                    Eintrag hinzufügen
                </button>
            </div>
        `;
    }
};
