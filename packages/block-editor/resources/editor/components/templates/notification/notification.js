/**
 * Notification Component Template
 * Zeigt Benachrichtigungen an (Success, Error, Info, Warning)
 */
export function getNotificationTemplate() {
    return `
        <!-- Notification Component -->
        <div x-show="notification.show" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2" class="fixed top-20 right-4 z-50 max-w-sm w-full"
            @click="hideNotification()">
            <div :class="{
                    'bg-green-50 border-green-200 text-green-800': notification.type === 'success',
                    'bg-red-50 border-red-200 text-red-800': notification.type === 'error',
                    'bg-blue-50 border-blue-200 text-blue-800': notification.type === 'info',
                    'bg-yellow-50 border-yellow-200 text-yellow-800': notification.type === 'warning'
                }"
                class="border rounded-lg shadow-lg p-4 flex items-start gap-3 cursor-pointer hover:shadow-xl transition-shadow">
                <div class="flex-shrink-0">
                    <svg x-show="notification.type === 'success'" class="w-5 h-5 text-green-600" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <svg x-show="notification.type === 'error'" class="w-5 h-5 text-red-600" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <svg x-show="notification.type === 'info'" class="w-5 h-5 text-blue-600" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <svg x-show="notification.type === 'warning'" class="w-5 h-5 text-yellow-600" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                        </path>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium" x-html="notification.message"></p>
                </div>
                <button type="button" @click.stop="hideNotification()"
                    class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
        </div>
    `;
}
