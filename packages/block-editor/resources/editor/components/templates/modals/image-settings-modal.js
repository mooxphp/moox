/**
 * Image Settings Modal Template
 */
export function getImageSettingsModalTemplate() {
    return `
        <!-- Image Settings Modal -->
        <div x-show="showImageSettingsModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            @click.self="closeImageSettingsModal()">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full flex flex-col" @click.stop
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <!-- Modal Header -->
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-900">🖼️ Bild-Einstellungen</h2>
                    <button type="button" @click="closeImageSettingsModal()"
                        class="p-2 hover:bg-gray-100 rounded-lg transition-colors" title="Schließen">
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200 px-6">
                    <div class="flex space-x-1">
                        <button type="button" @click="setImageSettingsTab('library')"
                            :class="imageSettingsActiveTab === 'library' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
                            class="px-4 py-3 text-sm font-medium transition-colors">
                            🗂️ Mediathek
                        </button>
                        <button type="button" @click="setImageSettingsTab('url')"
                            :class="imageSettingsActiveTab === 'url' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
                            class="px-4 py-3 text-sm font-medium transition-colors">
                            🔗 URL eingeben
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="flex-1 overflow-y-auto p-6">
                    <!-- Tab: Mediathek -->
                    <div x-show="imageSettingsActiveTab === 'library'" class="space-y-4">
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="flex-1 min-w-[220px]">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Suche:
                                </label>
                                <input
                                    type="text"
                                    x-model="mediaLibrarySearch"
                                    @input="queueMediaLibrarySearch()"
                                    placeholder="z. B. hero, teaser, banner..."
                                    class="w-full p-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                            <div class="min-w-[220px]">
                                <button type="button"
                                    @click="selectImageFile(imageSettingsBlockId)"
                                    :disabled="mediaUploadLoading"
                                    class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    Bild hochladen
                                </button>
                            </div>
                        </div>

                        <div x-show="mediaUploadLoading" class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
                            <div class="flex items-center justify-between gap-3">
                                <span x-text="mediaUploadProcessing ? 'Server verarbeitet Upload...' : 'Bild wird hochgeladen...'"></span>
                                <span class="font-semibold" x-text="mediaUploadProgressPercent + '%'"></span>
                            </div>
                            <div class="mt-1 text-xs text-blue-700" x-show="mediaUploadFileName">
                                <span x-text="mediaUploadFileName"></span>
                                <span x-show="mediaUploadFileSizeLabel" x-text="'(' + mediaUploadFileSizeLabel + ')'"></span>
                            </div>
                            <div class="mt-2 h-2 rounded bg-blue-100 overflow-hidden">
                                <div class="h-full bg-blue-600 transition-all duration-200"
                                    :style="'width:' + mediaUploadProgressPercent + '%'"></div>
                            </div>
                        </div>

                        <div x-show="!mediaUploadLoading && mediaUploadError"
                            class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
                            x-text="mediaUploadError">
                        </div>

                        <div x-show="!mediaUploadLoading && !mediaUploadError && mediaLibraryRecentlyUploadedUrl"
                            class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                            Upload erfolgreich. Das neue Bild ist in der Mediathek markiert.
                        </div>


                        <div x-show="mediaLibraryLoading"
                            class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
                            <svg class="h-4 w-4 animate-spin text-blue-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-90" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span>Mediathek wird geladen...</span>
                        </div>

                        <div x-show="!mediaLibraryLoading && mediaLibraryError"
                            class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
                            x-text="mediaLibraryError">
                        </div>

                        <div x-show="!mediaLibraryLoading && mediaLibraryItems.length > 0"
                            class="grid grid-cols-2 md:grid-cols-4 gap-3 max-h-[360px] overflow-y-auto p-1">
                            <template x-for="item in mediaLibraryItems" :key="item.id">
                                <button type="button" @click="selectMediaLibraryItem(item)"
                                    :class="isMediaLibraryItemSelected(item)
                                        ? 'border-blue-600 ring-2 ring-blue-200 shadow-sm'
                                        : (isRecentlyUploadedMediaLibraryItem(item)
                                            ? 'border-emerald-500 ring-2 ring-emerald-100 shadow-sm'
                                            : 'border-gray-200 hover:border-blue-400 hover:shadow-sm')"
                                    class="relative text-left border rounded-lg overflow-hidden transition-all">
                                    <div class="aspect-video bg-gray-100 flex items-center justify-center overflow-hidden">
                                        <img :src="item.previewUrl || item.url" :alt="item.title"
                                            class="w-full h-full object-cover" />
                                    </div>
                                    <div x-show="isRecentlyUploadedMediaLibraryItem(item) && !isMediaLibraryItemSelected(item)"
                                        class="absolute top-2 left-2 rounded bg-emerald-600 px-2 py-0.5 text-[10px] font-semibold uppercase text-white">
                                        Neu
                                    </div>
                                    <div x-show="isMediaLibraryItemSelected(item)"
                                        class="absolute top-2 right-2 h-6 w-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold">
                                        ✓
                                    </div>
                                    <div class="p-2">
                                        <p class="text-xs text-gray-700 truncate" x-text="item.title"></p>
                                    </div>
                                </button>
                            </template>
                        </div>

                        <div x-show="!mediaLibraryLoading && mediaLibraryItems.length > 0"
                            class="flex items-center justify-between pt-2">
                            <p class="text-xs text-gray-500">
                                Seite <span x-text="mediaLibraryPage"></span> von <span x-text="mediaLibraryTotalPages"></span>
                                (<span x-text="mediaLibraryTotalItems"></span> Treffer)
                            </p>
                            <div class="flex items-center gap-2">
                                <button type="button"
                                    @click="goToPreviousMediaLibraryPage()"
                                    :disabled="mediaLibraryPage <= 1 || mediaLibraryLoading"
                                    class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    Zurück
                                </button>
                                <button type="button"
                                    @click="goToNextMediaLibraryPage()"
                                    :disabled="mediaLibraryPage >= mediaLibraryTotalPages || mediaLibraryLoading"
                                    class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    Weiter
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: URL eingeben -->
                    <div x-show="imageSettingsActiveTab === 'url'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Linke Spalte: URL-Eingabe -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Bild-URL:
                                </label>
                                <input type="text" x-model="imageSettingsUrl" @click.stop @focus.stop
                                    placeholder="https://example.com/image.jpg"
                                    class="w-full p-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                <p class="mt-1 text-xs text-gray-500">
                                    Geben Sie eine URL zu einem Bild ein
                                </p>
                            </div>

                            <!-- Rechte Spalte: Vorschau -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Vorschau:
                                </label>
                                <div
                                    class="border border-gray-300 rounded-lg p-4 bg-gray-50 min-h-[200px] flex items-center justify-center">
                                    <img x-show="imageSettingsUrl" :src="imageSettingsUrl" alt="Vorschau"
                                        class="w-full max-h-[300px] h-auto rounded mx-auto"
                                        @error="imageSettingsUrl = ''" />
                                    <div x-show="!imageSettingsUrl" class="text-center text-gray-400">
                                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        <p class="text-sm">Kein Bild ausgewählt</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alt-Text und Titel -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                            <!-- Alt-Text -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Alt-Text (für Barrierefreiheit):
                                </label>
                                <input type="text" x-model="imageSettingsAlt" @click.stop @focus.stop
                                    placeholder="Beschreibung des Bildes..."
                                    class="w-full p-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                <p class="mt-1 text-xs text-gray-500">
                                    Beschreibung des Bildes für Screenreader
                                </p>
                            </div>

                            <!-- Titel -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Titel (Tooltip):
                                </label>
                                <input type="text" x-model="imageSettingsTitle" @click.stop @focus.stop
                                    placeholder="Titel des Bildes..."
                                    class="w-full p-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                <p class="mt-1 text-xs text-gray-500">
                                    Wird beim Hovern über das Bild angezeigt
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end gap-3 p-6 border-t border-gray-200">
                    <button type="button" @click="closeImageSettingsModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Abbrechen
                    </button>
                    <button type="button" @click="saveImageSettings()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Speichern
                    </button>
                </div>
            </div>
        </div>
    `;
}
