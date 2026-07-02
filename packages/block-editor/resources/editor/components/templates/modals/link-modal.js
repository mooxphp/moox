/**
 * Link Modal Template
 */
export function getLinkModalTemplate() {
    return `
        <!-- Einheitliches Link-Modal -->
        <div x-show="showLinkModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            @click.self="closeLinkModal()">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full flex flex-col" @click.stop
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <!-- Modal Header -->
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900"
                        x-text="linkModal.type === 'edit' ? '🔗 Link bearbeiten' : linkModal.type === 'block' ? '🔗 Link-Einstellungen' : '🔗 Link hinzufügen'">
                    </h2>
                    <button type="button" @click="closeLinkModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        title="Schließen">
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="flex-1 overflow-y-auto p-6">
                    <div class="space-y-6">
                        <!-- Text-Anzeige (nur für selection und edit) -->
                        <div x-show="linkModal.type === 'selection' || linkModal.type === 'edit'">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Text:
                            </label>
                            <div class="p-3 bg-gray-50 rounded-lg text-sm text-gray-700 border border-gray-200"
                                x-text="linkModal.text || selectedText || ''"></div>
                        </div>

                        <!-- URL Eingabe -->
                        <div x-ref="linkUrlField">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                URL:
                            </label>
                            <input type="url" x-model="linkModal.url" @keydown.enter.prevent="saveLink()" @click.stop
                                @focus.stop placeholder="https://example.com"
                                class="w-full p-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            <p class="mt-1 text-xs text-gray-500">
                                Die URL, zu der der Link führen soll. Wenn kein Protokoll angegeben ist, wird automatisch "https://" ergänzt.
                            </p>
                            <p class="mt-1 text-xs text-gray-500" x-show="linkModal.url">
                                Erkannter Typ:
                                <span class="inline-flex items-center gap-1">
                                    <span x-text="getLinkTypeFromUrl(linkModal.url).icon" aria-hidden="true"></span>
                                    <span x-text="getLinkTypeFromUrl(linkModal.url).label"></span>
                                </span>
                            </p>
                        </div>

                        <!-- Link-Titel Eingabe (nur für block) -->
                        <div x-show="linkModal.type === 'block'">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Link-Titel <span class="text-gray-400 font-normal text-xs">(optional)</span>:
                            </label>
                            <input type="text" x-model="linkModal.linkText" @keydown.enter.prevent="saveLink()"
                                @click.stop @focus.stop placeholder="Z.B. 'Produktdatenblatt' oder leer lassen"
                                class="w-full p-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            <p class="mt-1 text-xs text-gray-500">
                                Optionaler Titel, der als Link-Text angezeigt wird. Wenn leer, wird die URL verwendet.
                            </p>
                        </div>

                        <!-- Target Auswahl (für block, edit und selection) -->
                        <div
                            x-show="linkModal.type === 'block' || linkModal.type === 'edit' || linkModal.type === 'selection'">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Link öffnen in:
                            </label>
                            <select x-model="linkModal.target" @click.stop @focus.stop
                                class="w-full p-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="_self">Gleichem Tab/Fenster</option>
                                <option value="_blank">Neuem Tab/Fenster</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">
                                Standard ist "Neuem Tab/Fenster"
                            </p>
                        </div>

                        <!-- Vorschau (nur für block) -->
                        <div x-show="linkModal.type === 'block'" class="pt-4 border-t border-gray-200">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Vorschau:
                            </label>
                            <div
                                class="border border-gray-300 rounded-lg p-4 bg-gray-50 min-h-[60px] flex items-center">
                                <a x-show="linkModal.url && linkModal.url.trim() !== ''" :href="linkModal.url"
                                    :target="linkModal.target || '_blank'"
                                    class="text-blue-600 hover:text-blue-800 hover:underline" @click.stop>
                                    <span
                                        x-text="(linkModal.linkText && linkModal.linkText.trim() !== '') ? linkModal.linkText : (linkModal.url || 'Keine URL eingegeben')"></span>
                                </a>
                                <span x-show="!linkModal.url || linkModal.url.trim() === ''" class="text-gray-400">
                                    Keine URL eingegeben
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-between items-center gap-3 p-6 border-t border-gray-200">
                    <button x-show="linkModal.type === 'edit'" type="button" @click="removeLink()"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Link entfernen
                    </button>
                    <div class="flex gap-3 ml-auto">
                        <button type="button" @click="closeLinkModal()"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            Abbrechen
                        </button>
                        <button type="button" @click="saveLink()"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                            x-text="linkModal.type === 'edit' ? 'Link aktualisieren' : linkModal.type === 'block' ? 'Speichern' : 'Link hinzufügen'"></button>
                    </div>
                </div>
            </div>
        </div>
    `;
}
