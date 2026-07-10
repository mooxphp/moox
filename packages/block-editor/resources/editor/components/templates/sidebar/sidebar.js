/**
 * Sidebar Component Template
 * Enthält Overlay, Sidebar und alle Block-Einstellungen
 */
export function getSidebarTemplate() {
    return `
        <!-- Overlay für Sidebar (schließt Sidebar beim Klick) -->
        <div x-show="showSidebar" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" @click="closeSidebar()"
            class="fixed inset-0 bg-black/50 z-40"></div>

        <!-- Sidebar für Block-Einstellungen -->
        <div x-show="showSidebar" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="-translate-x-full opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="-translate-x-full opacity-0"
            class="w-80 bg-white border-r border-gray-200 shadow-xl overflow-y-auto z-50 fixed left-0 top-0 h-screen"
            @click.stop>
            <template x-if="showSidebar">
                <div class="p-4" @click.stop>
                    <div class="flex justify-between items-center mb-4" @click.stop>
                        <h2 class="text-xl font-bold text-gray-900">Einstellungen</h2>
                        <button type="button" @click.stop="closeSidebar()" class="p-1 hover:bg-gray-100 rounded" title="Schließen">
                            ×
                        </button>
                    </div>

                    <!-- Block-Einstellungen -->
                    <div>
                        <div x-show="typeof selectedBlockId === 'undefined' || selectedBlockId === null" class="text-center text-gray-500 py-8">
                            <p>Kein Block ausgewählt</p>
                            <p class="text-sm mt-2">Wähle einen Block aus, um seine Einstellungen zu bearbeiten</p>
                        </div>

                        <template x-for="block in (typeof getAllBlocks === 'function' ? getAllBlocks() : [])" :key="block.id">
                            <div x-show="typeof selectedBlockId !== 'undefined' && block.id === selectedBlockId" class="space-y-4">
                                <!-- Block-Typ ändern -->
                                <div x-show="addComponentsEnabled" @click.stop>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Block-Typ:
                                    </label>
                                    <select x-model="block.type" @change="changeBlockType(block.id, block.type)"
                                        @click.stop
                                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option x-show="block.type && !childBlockTypes[block.type]" :value="block.type"
                                            :selected="block.type === block.type"
                                            x-text="block.type === 'column' ? 'Spalte' : block.type"></option>
                                        <template x-for="(config, blockType) in childBlockTypes" :key="blockType">
                                            <option :value="blockType"
                                                :selected="block.type === blockType"
                                                x-text="(config.icon ? config.icon + ' ' : '') + (config.label || blockType)">
                                            </option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Tailwind CSS Klassen Editor -->
                                <div @click.stop>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Tailwind CSS Klassen:
                                    </label>
                                    <input type="text" x-model="block.classes"
                                        @input="block.updatedAt = new Date().toISOString()" @click.stop @focus.stop
                                        placeholder="z.B. text-red-500 bg-yellow-200 p-4 rounded-lg"
                                        class="w-full p-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    <div class="mt-2 flex gap-2"
                                        x-show="block.classes && block.classes.trim().length > 0">
                                        <button type="button" @click.stop="clearBlockClasses(block.id)"
                                            class="px-3 py-1 bg-gray-500 text-white rounded text-sm hover:bg-gray-600">
                                            Zurücksetzen
                                        </button>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500">
                                        Beispiel: <code
                                            class="bg-gray-100 px-1 rounded">text-blue-600 bg-gray-100 p-4 rounded-lg shadow-md</code>
                                    </div>
                                </div>

                                <!-- HTML ID Editor -->
                                <div @click.stop class="pt-4 border-t border-gray-200">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        HTML ID (Optional):
                                    </label>
                                    <input type="text" x-model="block.htmlId"
                                        @input="block.updatedAt = new Date().toISOString()" @click.stop @focus.stop
                                        placeholder="z.B. my-custom-id"
                                        class="w-full p-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    <div class="mt-2 flex gap-2"
                                        x-show="block.htmlId && block.htmlId.trim().length > 0">
                                        <button type="button" @click.stop="clearBlockHtmlId(block.id)"
                                            class="px-3 py-1 bg-gray-500 text-white rounded text-sm hover:bg-gray-600">
                                            Zurücksetzen
                                        </button>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500">
                                        Beispiel: <code class="bg-gray-100 px-1 rounded">my-section-header</code>
                                    </div>
                                </div>

                                <!-- CSS Editor (Optional) -->
                                <div @click.stop class="pt-4 border-t border-gray-200">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Inline CSS (Optional):
                                    </label>
                                    <textarea x-model="block.style" @input="block.updatedAt = new Date().toISOString()"
                                        @click.stop @focus.stop
                                        placeholder="z.B. color: red; background-color: yellow; padding: 10px;"
                                        class="w-full p-2 border border-gray-300 rounded-lg font-mono text-sm h-32 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                                    <div class="mt-2 flex gap-2" x-show="block.style && block.style.trim().length > 0">
                                        <button type="button" @click.stop="clearBlockStyle(block.id)"
                                            class="px-3 py-1 bg-gray-500 text-white rounded text-sm hover:bg-gray-600">
                                            Zurücksetzen
                                        </button>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500">
                                        Beispiel: <code
                                            class="bg-gray-100 px-1 rounded">color: blue; font-size: 18px; margin: 10px;</code>
                                    </div>
                                </div>

                                <!-- Block-spezifische Einstellungen -->
                                <div
                                    x-html="getBlockSettingsHTML(block)"
                                    x-init="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
                                    x-effect="$nextTick(() => {
                                        if (window.Alpine) {
                                            window.Alpine.initTree($el);
                                        }
                                    })"
                                ></div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    `;
}
