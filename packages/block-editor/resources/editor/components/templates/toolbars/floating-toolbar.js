/**
 * Floating Toolbar Template
 * Toolbar für Text-Formatierung (Bold, Italic, Underline, etc.)
 */
export function getFloatingToolbarTemplate() {
    return `
        <!-- Floating Toolbar für Text-Selektion -->
        <div x-show="showFloatingToolbar" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bn-toolbar bn-formatting-toolbar bg-white text-gray-900 flex h-fit gap-1 rounded-lg border border-gray-200 p-1 shadow-md fixed z-50"
            :style="'top: ' + floatingToolbarPosition.top + 'px; left: ' + floatingToolbarPosition.left + 'px; transform: translateX(-50%);'"
            @mousedown.prevent
            @click.stop>
            <!-- Bold -->
            <button type="button" @mousedown.prevent @click.stop="applyTextFormat('bold')" x-bind:data-state="getFormatState('bold')"
                class="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium hover:bg-gray-100 hover:text-gray-900 disabled:pointer-events-none disabled:opacity-50 data-[state=on]:bg-blue-100 data-[state=on]:text-blue-900 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg]:w-4 [&_svg]:h-4 focus-visible:border-blue-500 focus-visible:ring-blue-500/50 focus-visible:ring-[3px] outline-none transition-[color,box-shadow] whitespace-nowrap bg-transparent h-9 px-2 min-w-9 bn-button"
                aria-label="Bold" data-test="bold">
                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em"
                    width="1em" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M8 11H12.5C13.8807 11 15 9.88071 15 8.5C15 7.11929 13.8807 6 12.5 6H8V11ZM18 15.5C18 17.9853 15.9853 20 13.5 20H6V4H12.5C14.9853 4 17 6.01472 17 8.5C17 9.70431 16.5269 10.7981 15.7564 11.6058C17.0979 12.3847 18 13.837 18 15.5ZM8 13V18H13.5C14.8807 18 16 16.8807 16 15.5C16 14.1193 14.8807 13 13.5 13H8Z">
                    </path>
                </svg>
            </button>

            <!-- Italic -->
            <button type="button" @mousedown.prevent @click.stop="applyTextFormat('italic')" x-bind:data-state="getFormatState('italic')"
                class="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium hover:bg-gray-100 hover:text-gray-900 disabled:pointer-events-none disabled:opacity-50 data-[state=on]:bg-blue-100 data-[state=on]:text-blue-900 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg]:w-4 [&_svg]:h-4 focus-visible:border-blue-500 focus-visible:ring-blue-500/50 focus-visible:ring-[3px] outline-none transition-[color,box-shadow] whitespace-nowrap bg-transparent h-9 px-2 min-w-9 bn-button"
                aria-label="Italic" data-test="italic">
                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em"
                    width="1em" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 20H7V18H9.92661L12.0425 6H9V4H17V6H14.0734L11.9575 18H15V20Z"></path>
                </svg>
            </button>

            <!-- Underline -->
            <button type="button" @mousedown.prevent @click.stop="applyTextFormat('underline')"
                x-bind:data-state="getFormatState('underline')"
                class="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium hover:bg-gray-100 hover:text-gray-900 disabled:pointer-events-none disabled:opacity-50 data-[state=on]:bg-blue-100 data-[state=on]:text-blue-900 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg]:w-4 [&_svg]:h-4 focus-visible:border-blue-500 focus-visible:ring-blue-500/50 focus-visible:ring-[3px] outline-none transition-[color,box-shadow] whitespace-nowrap bg-transparent h-9 px-2 min-w-9 bn-button"
                aria-label="Underline" data-test="underline">
                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em"
                    width="1em" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M8 3V12C8 14.2091 9.79086 16 12 16C14.2091 16 16 14.2091 16 12V3H18V12C18 15.3137 15.3137 18 12 18C8.68629 18 6 15.3137 6 12V3H8ZM4 20H20V22H4V20Z">
                    </path>
                </svg>
            </button>

            <!-- Strike -->
            <button type="button" @mousedown.prevent @click.stop="applyTextFormat('strikeThrough')"
                x-bind:data-state="getFormatState('strikeThrough')"
                class="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium hover:bg-gray-100 hover:text-gray-900 disabled:pointer-events-none disabled:opacity-50 data-[state=on]:bg-blue-100 data-[state=on]:text-blue-900 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg]:w-4 [&_svg]:h-4 focus-visible:border-blue-500 focus-visible:ring-blue-500/50 focus-visible:ring-[3px] outline-none transition-[color,box-shadow] whitespace-nowrap bg-transparent h-9 px-2 min-w-9 bn-button"
                aria-label="Strike" data-test="strike">
                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em"
                    width="1em" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M17.1538 14C17.3846 14.5161 17.5 15.0893 17.5 15.7196C17.5 17.0625 16.9762 18.1116 15.9286 18.867C14.8809 19.6223 13.4335 20 11.5862 20C9.94674 20 8.32335 19.6185 6.71592 18.8555V16.6009C8.23538 17.4783 9.7908 17.917 11.3822 17.917C13.9333 17.917 15.2128 17.1846 15.2208 15.7196C15.2208 15.0939 15.0049 14.5598 14.5731 14.1173C14.5339 14.0772 14.4939 14.0381 14.4531 14H3V12H21V14H17.1538ZM13.076 11H7.62908C7.4566 10.8433 7.29616 10.6692 7.14776 10.4778C6.71592 9.92084 6.5 9.24559 6.5 8.45207C6.5 7.21602 6.96583 6.165 7.89749 5.299C8.82916 4.43299 10.2706 4 12.2219 4C13.6934 4 15.1009 4.32808 16.4444 4.98426V7.13591C15.2448 6.44921 13.9293 6.10587 12.4978 6.10587C10.0187 6.10587 8.77917 6.88793 8.77917 8.45207C8.77917 8.87172 8.99709 9.23796 9.43293 9.55079C9.86878 9.86362 10.4066 10.1135 11.0463 10.3004C11.6665 10.4816 12.3431 10.7148 13.076 11H13.076Z">
                    </path>
                </svg>
            </button>

            <!-- Text Alignment: Left -->
            <button type="button" @mousedown.prevent @click.stop="applyTextAlignmentToSelection('left')"
                x-bind:data-state="getTextAlignmentState('left')"
                class="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium hover:bg-gray-100 hover:text-gray-900 disabled:pointer-events-none disabled:opacity-50 data-[state=on]:bg-blue-100 data-[state=on]:text-blue-900 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg]:w-4 [&_svg]:h-4 focus-visible:border-blue-500 focus-visible:ring-blue-500/50 focus-visible:ring-[3px] outline-none transition-[color,box-shadow] whitespace-nowrap bg-transparent h-9 px-2 min-w-9 bn-button"
                aria-label="Align text left" data-test="alignTextLeft">
                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em"
                    width="1em" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 4H21V6H3V4ZM3 19H17V21H3V19ZM3 14H21V16H3V14ZM3 9H17V11H3V9Z"></path>
                </svg>
            </button>

            <!-- Text Alignment: Center -->
            <button type="button" @mousedown.prevent @click.stop="applyTextAlignmentToSelection('center')"
                x-bind:data-state="getTextAlignmentState('center')"
                class="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium hover:bg-gray-100 hover:text-gray-900 disabled:pointer-events-none disabled:opacity-50 data-[state=on]:bg-blue-100 data-[state=on]:text-blue-900 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg]:w-4 [&_svg]:h-4 focus-visible:border-blue-500 focus-visible:ring-blue-500/50 focus-visible:ring-[3px] outline-none transition-[color,box-shadow] whitespace-nowrap bg-transparent h-9 px-2 min-w-9 bn-button"
                aria-label="Align text center" data-test="alignTextCenter">
                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em"
                    width="1em" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 4H21V6H3V4ZM5 19H19V21H5V19ZM3 14H21V16H3V14ZM5 9H19V11H5V9Z"></path>
                </svg>
            </button>

            <!-- Text Alignment: Right -->
            <button type="button" @mousedown.prevent @click.stop="applyTextAlignmentToSelection('right')"
                x-bind:data-state="getTextAlignmentState('right')"
                class="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium hover:bg-gray-100 hover:text-gray-900 disabled:pointer-events-none disabled:opacity-50 data-[state=on]:bg-blue-100 data-[state=on]:text-blue-900 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg]:w-4 [&_svg]:h-4 focus-visible:border-blue-500 focus-visible:ring-blue-500/50 focus-visible:ring-[3px] outline-none transition-[color,box-shadow] whitespace-nowrap bg-transparent h-9 px-2 min-w-9 bn-button"
                aria-label="Align text right" data-test="alignTextRight">
                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em"
                    width="1em" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 4H21V6H3V4ZM7 19H21V21H7V19ZM3 14H21V16H3V14ZM7 9H21V11H7V9Z"></path>
                </svg>
            </button>

            <!-- Colors Dropdown -->
            <div class="relative" x-data="{ showColorPicker: false }">
                <button type="button" @mousedown.prevent @click.stop="showColorPicker = !showColorPicker"
                    class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg]:w-4 [&_svg]:h-4 outline-none focus-visible:border-blue-500 focus-visible:ring-blue-500/50 focus-visible:ring-[3px] hover:bg-gray-100 hover:text-gray-900 h-9 px-4 py-2 has-[>svg]:px-3 bn-button"
                    aria-label="Colors" data-test="colors">
                    <div class="bn-color-icon" data-background-color="default" data-text-color="default"
                        style="pointer-events: none; font-size: 15px; height: 20px; line-height: 20px; text-align: center; width: 20px;">
                        A</div>
                </button>

                <!-- Color Picker Dropdown -->
                <div x-show="showColorPicker" @click.away="showColorPicker = false" x-transition
                    class="absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-2 z-50 min-w-[200px]">
                    <div class="text-xs font-semibold text-gray-700 mb-2 px-2">Textfarbe</div>
                    <div class="grid grid-cols-8 gap-1 mb-3">
                        <template
                            x-for="color in ['#000000', '#FFFFFF', '#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF', '#00FFFF', '#808080', '#800000', '#008000', '#000080', '#808000', '#800080', '#008080', '#C0C0C0']">
                            <button type="button" @mousedown.prevent @click.stop="applyTextColor(color); showColorPicker = false"
                                class="w-6 h-6 rounded border border-gray-300 hover:scale-110 transition-transform"
                                x-bind:style="'background-color: ' + color" x-bind:title="color"></button>
                        </template>
                    </div>
                    <div class="text-xs font-semibold text-gray-700 mb-2 px-2">Hintergrundfarbe</div>
                    <div class="grid grid-cols-8 gap-1">
                        <template
                            x-for="color in ['#FFFFFF', '#000000', '#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF', '#00FFFF', '#808080', '#800000', '#008000', '#000080', '#808000', '#800080', '#008080', '#C0C0C0']">
                            <button type="button" @mousedown.prevent @click.stop="applyBackgroundColor(color); showColorPicker = false"
                                class="w-6 h-6 rounded border border-gray-300 hover:scale-110 transition-transform"
                                x-bind:style="'background-color: ' + color" x-bind:title="color"></button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Link -->
            <button type="button" @mousedown.prevent @click.stop="openLinkInputForSelection()"
                class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg]:w-4 [&_svg]:h-4 outline-none focus-visible:border-blue-500 focus-visible:ring-blue-500/50 focus-visible:ring-[3px] hover:bg-gray-100 hover:text-gray-900 h-9 px-4 py-2 has-[>svg]:px-3 bn-button"
                aria-label="Create link" data-test="createLink">
                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em"
                    width="1em" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M18.3638 15.5355L16.9496 14.1213L18.3638 12.7071C20.3164 10.7545 20.3164 7.58866 18.3638 5.63604C16.4112 3.68341 13.2453 3.68341 11.2927 5.63604L9.87849 7.05025L8.46428 5.63604L9.87849 4.22182C12.6122 1.48815 17.0443 1.48815 19.778 4.22182C22.5117 6.95549 22.5117 11.3876 19.778 14.1213L18.3638 15.5355ZM15.5353 18.364L14.1211 19.7782C11.3875 22.5118 6.95531 22.5118 4.22164 19.7782C1.48797 17.0445 1.48797 12.6123 4.22164 9.87868L5.63585 8.46446L7.05007 9.87868L5.63585 11.2929C3.68323 13.2455 3.68323 16.4113 5.63585 18.364C7.58847 20.3166 10.7543 20.3166 12.7069 18.364L14.1211 16.9497L15.5353 18.364ZM14.8282 7.75736L16.2425 9.17157L9.17139 16.2426L7.75717 14.8284L14.8282 7.75736Z">
                    </path>
                </svg>
            </button>
        </div>
    `;
}
