
export function getDeveloperHeaderActionsTemplate() {
    return `
        <div x-show="developerJsonEnabled" class="flex flex-wrap gap-2">
            <button type="button"
                type="button"
                @click="showJsonStructure = !showJsonStructure"
                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors"
                x-text="showJsonStructure ? 'JSON ausblenden' : 'JSON anzeigen'"
            ></button>
            <button type="button"
                type="button"
                @click="importJSON()"
                x-show="jsonImportEnabled"
                class="px-4 py-2 border border-purple-300 text-purple-800 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors"
            >
                JSON importieren
            </button>
        </div>
    `;
}

export function getDeveloperJsonDisplayTemplate() {
    return `
        <div x-show="developerJsonEnabled && showJsonStructure" x-transition class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Live JSON-Struktur
            </label>
            <textarea
                readonly
                spellcheck="false"
                class="w-full h-80 p-3 border border-gray-300 rounded-lg font-mono text-xs bg-gray-50 focus:outline-none"
                x-effect="$el.value = getJSONDisplay()"
            ></textarea>
        </div>
    `;
}
