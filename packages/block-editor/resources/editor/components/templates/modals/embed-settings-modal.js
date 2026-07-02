/**
 * Embed Settings Modal Template
 */
export function getEmbedSettingsModalTemplate() {
    return `
        <div x-show="showEmbedSettingsModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            @click.self="closeEmbedSettingsModal()">
            <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full flex flex-col" @click.stop
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-900">🌐 Embed-Einstellungen</h2>
                    <button type="button" @click="closeEmbedSettingsModal()"
                        class="p-2 hover:bg-gray-100 rounded-lg transition-colors" title="Schließen">
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Embed-URL</label>
                        <input type="text" x-model="embedSettingsUrl" @click.stop @focus.stop
                            placeholder="https://www.youtube.com/watch?v=..."
                            class="w-full p-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        <p class="mt-1 text-xs text-gray-500">
                            Erlaubt sind alle http/https URLs. Falls eine Seite iFrames blockiert, kann sie nicht angezeigt werden.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Titel (Optional)</label>
                        <input type="text" x-model="embedSettingsTitle" @click.stop @focus.stop
                            placeholder="Video / Inhaltstitel..."
                            class="w-full p-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Vorschau</label>
                        <div class="relative w-full overflow-hidden rounded-lg border border-gray-300 bg-gray-50 aspect-video">
                            <iframe x-show="embedSettingsUrl"
                                :src="getEmbedPreviewUrl()"
                                class="w-full h-full"
                                loading="lazy"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen
                                @error="embedSettingsUrl = ''"></iframe>
                            <div x-show="!getEmbedPreviewUrl()" class="h-full flex items-center justify-center text-gray-400 text-sm">
                                Keine Embed-URL gesetzt
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 p-6 border-t border-gray-200">
                    <button type="button" @click="closeEmbedSettingsModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Abbrechen
                    </button>
                    <button type="button" @click="saveEmbedSettings()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Speichern
                    </button>
                </div>
            </div>
        </div>
    `;
}
