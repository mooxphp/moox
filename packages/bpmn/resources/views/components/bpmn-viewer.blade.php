{{-- ===== ASSETS ===== --}}
@vite('packageslocal/bpmn/resources/css/bpmn.css')
@vite('packageslocal/bpmn/resources/js/bpmn.js')



@props([
    'mediaId' => null,
    'wpMediaId' => null,
    'filePath' => null,
    'mode' => 'view',
    'height' => '500px',
    'showToolbar' => true,
    'class' => '',
    'bpmnSource' => ['type' => 'none', 'id' => null],
    'canEdit' => false,
    'canView' => false,
    'bpmnContent' => null,
])

@php
  // Normalize file source if only filePath is provided
if ($filePath && $bpmnSource['type'] === 'none') {
    $bpmnSource['type'] = 'file';
    $bpmnSource['path'] = str_replace('\\', '/', $filePath);
}
@endphp

<div
    class="bpmn-viewer {{ $class }}"
    data-bpmn-viewer
    data-mode="{{ $mode }}"
    data-source-type="{{ $bpmnSource['type'] }}"
    @if($bpmnSource['type'] === 'media') data-media-id="{{ $bpmnSource['id'] }}"
    @elseif($bpmnSource['type'] === 'wp-media') data-wp-media-id="{{ $bpmnSource['id'] }}"
    @elseif($bpmnSource['type'] === 'file') data-file-path="/{{ ltrim($bpmnSource['path'], '/') }}" @endif
    style=""
>
    {{-- Toolbar only if editing is allowed --}}
    @if($showToolbar && $canEdit)
    <div class="bpmn-toolbar flex justify-between items-center p-2 bg-gray-50 border-b">
        <div class="bpmn-info text-sm text-gray-600">
            @if($bpmnSource['type'] === 'media')
                BPMN Media ID: {{ $bpmnSource['id'] }}
            @elseif($bpmnSource['type'] === 'wp-media')
                BPMN WordPress Media ID: {{ $bpmnSource['id'] }}
            @elseif($bpmnSource['type'] === 'file')
                BPMN File: {{ basename($bpmnSource['path']) }}
            @endif
        </div>

        <div class="flex space-x-2 items-center mb-2">
            <!-- Save dropdown -->
            <div class="relative inline-block text-left" data-bpmn-save-menu>
                <button type="button" class="bpmn-save px-4 py-2 rounded bg-blue-500 text-white hover:bg-blue-600 flex items-center justify-between">
                    <span>Save ▾</span>
                    <svg class="animate-spin hidden h-4 w-4 text-white ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                </button>
                <div class="hidden absolute right-0 mt-2 w-44 bg-white border border-gray-200 rounded-md shadow-lg z-10 transform scale-95 opacity-0 transition-all duration-200 origin-top-right">
                    <button type="button" class="block w-full text-left px-4 py-2 hover:bg-gray-100" data-save-type="bpmn">💾 Save as BPMN</button>
                    <button type="button" class="block w-full text-left px-4 py-2 hover:bg-gray-100" data-save-type="svg">🖼️ Save as SVG</button>
                    <button type="button" class="block w-full text-left px-4 py-2 hover:bg-gray-100" data-save-type="png">📸 Save as PNG</button>
                </div>
            
            </div>


            <!-- Upload button -->
            <label class="px-4 py-2 rounded bg-green-500 text-white hover:bg-green-600 cursor-pointer">
                <span>Upload</span>
                <input type="file" accept=".bpmn,.xml" class="hidden" data-bpmn-upload />
            </label>
        </div>
    </div>
    @endif

    {{-- Single BPMN container --}}
    <div class="bpmn-container relative w-full h-full border border-gray-300 rounded">
        <div class="bpmn-loading absolute inset-0 flex items-center justify-center text-gray-500">
            Loading BPMN diagram...
        </div>
    </div>

    {{-- Pass BPMN content if present --}}
    @if($bpmnContent)
        <script type="application/json" data-bpmn-content>
            {!! json_encode($bpmnContent, JSON_UNESCAPED_SLASHES) !!}
        </script>
    @endif
</div>
@once
    @vite([
            'resources/js/app.js',
            ])
@endonce

@push('styles')
    @vite('packageslocal/bpmn/resources/css/bpmn.css')
@endpush

@push('scripts')
    @vite('packageslocal/bpmn/resources/js/bpmn.js')
@endpush