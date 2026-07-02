/**
 * Confirm Modal Template
 */
export function getConfirmModalTemplate() {
    return `
        <!-- Confirm Modal -->
        <div x-show="showConfirmModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            @click.self="closeConfirmModal()">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full flex flex-col" @click.stop
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <!-- Modal Header -->
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900" x-text="confirmModal.title"></h2>
                    <button type="button" @click="closeConfirmModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        title="Schließen">
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="flex-1 overflow-y-auto p-6">
                    <p class="text-gray-700" x-html="confirmModal.message"></p>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end gap-3 p-6 border-t border-gray-200">
                    <!-- Link-Follow Buttons -->
                    <template x-if="confirmModal.showLinkFollow">
                        <div class="flex gap-3 w-full">
                            <button type="button" @click="confirmModal.onCancel && confirmModal.onCancel()"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex-1">
                                ✏️ Bearbeiten
                            </button>
                            <button type="button" @click="confirmModal.onConfirm && confirmModal.onConfirm()"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex-1">
                                🔗 Link folgen
                            </button>
                        </div>
                    </template>

                    <!-- Standard Buttons (wenn nicht Link-Follow) -->
                    <template x-if="!confirmModal.showLinkFollow">
                        <div class="flex gap-3">
                            <button type="button" @click="closeConfirmModal()"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                                Abbrechen
                            </button>
                            <button x-show="confirmModal.showExtend"
                                type="button" @click="confirmModal.onExtend && confirmModal.onExtend()"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Erweitern
                            </button>
                            <button x-show="confirmModal.onConfirm && !confirmModal.showExtend" type="button" @click="confirmAction()"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                Bestätigen
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    `;
}
