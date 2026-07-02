/**
 * JSON Import Modal Template
 */
export function getJsonImportModalTemplate() {
    return `
        <!-- JSON Import Modal -->
        <div x-show="showImportModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            @click.self="closeImportModal()">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col" @click.stop
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <!-- Modal Header -->
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-900">JSON Import</h2>
                    <button type="button" @click="closeImportModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        title="Schließen">
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="flex-1 overflow-hidden flex flex-col p-6">
                    <!-- JSON Input Area -->
                    <div class="flex-1 flex flex-col mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            JSON eingeben oder einfügen:
                        </label>
                        <textarea x-model="importJSONText" @input="validateImportJSON()"
                            class="flex-1 w-full p-4 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                            placeholder='[{"id": "1736505602000", "type": "paragraph", "content": "Beispiel..."}]'
                            spellcheck="false"></textarea>
                    </div>

                    <!-- Validation Status -->
                    <div class="mb-4">
                        <div x-show="importJSONError" class="p-3 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-red-800 mb-1">JSON Fehler:</p>
                                    <p class="text-sm text-red-700" x-text="importJSONError"></p>
                                </div>
                            </div>
                        </div>
                        <div x-show="!importJSONError && importJSONText && importJSONValid"
                            class="p-3 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <p class="text-sm font-semibold text-green-800">JSON ist gültig!</p>
                            </div>
                        </div>
                        <div x-show="!importJSONText" class="p-3 bg-gray-50 border border-gray-200 rounded-lg">
                            <p class="text-sm text-gray-600">Geben Sie JSON-Daten ein, um sie zu importieren.</p>
                        </div>
                    </div>

                    <!-- Preview Info -->
                    <div x-show="importJSONValid && importJSONPreview"
                        class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm font-semibold text-blue-800 mb-1">Vorschau:</p>
                        <p class="text-sm text-blue-700">
                            <span x-text="importJSONPreview?.blockCount || 0"></span> Block(s) werden importiert
                        </p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end gap-3 p-6 border-t border-gray-200">
                    <button type="button" @click="closeImportModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Abbrechen
                    </button>
                    <button type="button" @click="confirmImportJSON()" :disabled="!importJSONValid || !importJSONText"
                        :class="(!importJSONValid || !importJSONText) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-purple-700'"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg transition-colors">
                        Importieren
                    </button>
                </div>
            </div>
        </div>
    `;
}
