/**
 * Theme Save Modal Template
 */
export function getThemeSaveModalTemplate() {
    return `
        <!-- Save Theme Modal -->
        <div x-show="showSaveThemeModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            @click.self="closeSaveThemeModal()">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full" @click.stop
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <!-- Modal Header -->
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-900">Theme speichern</h2>
                    <button type="button" @click="closeSaveThemeModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
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
                            Theme-Name:
                        </label>
                        <input type="text" x-model="newThemeName" @keydown.enter="saveTheme()"
                            @keydown.escape="closeSaveThemeModal()" placeholder="z.B. Mein Theme"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            autofocus />
                        <div x-show="saveThemeError"
                            class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                            <span x-text="saveThemeError"></span>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600 mb-4">
                        <p>Das aktuelle Layout wird als Theme gespeichert.</p>
                        <p class="mt-1"><strong>Blöcke:</strong> <span x-text="blocks.length"></span></p>
                        <p class="mt-1 text-xs text-gray-500">Das Theme wird per API gespeichert.</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end gap-3 p-6 border-t border-gray-200">
                    <button type="button" @click="closeSaveThemeModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Abbrechen
                    </button>
                    <button type="button" @click="saveTheme()" :disabled="!newThemeName || !newThemeName.trim()"
                        :class="(!newThemeName || !newThemeName.trim()) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg transition-colors">
                        Speichern
                    </button>
                </div>
            </div>
        </div>
    `;
}
