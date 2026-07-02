/**
 * Theme Import Modal Template
 */
export function getThemeImportModalTemplate() {
    return `
        <!-- Import Theme Modal -->
        <div x-show="showImportThemeModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            @click.self="closeImportThemeModal()">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full" @click.stop
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <!-- Modal Header -->
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-900">Theme importieren</h2>
                    <button type="button" @click="closeImportThemeModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        title="Schließen">
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            JSON-Datei auswählen:
                        </label>
                        <input type="file" accept=".json" @change="handleThemeFileImport($event)"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" />
                    </div>
                    <div class="text-sm text-gray-600 mb-4">
                        <p>Wähle eine JSON-Datei aus, um ein Theme zu importieren.</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end gap-3 p-6 border-t border-gray-200">
                    <button type="button" @click="closeImportThemeModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Schließen
                    </button>
                </div>
            </div>
        </div>
    `;
}
