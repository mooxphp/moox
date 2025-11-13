@props([
    'mediaId' => null,
    'wpMediaId' => null,
    'filePath' => null,
    'mode' => 'view',
    'height' => '500px',
    'showToolbar' => true,
    'class' => '',
])

@php
    $bpmnSource = $this->getBpmnSource();
    $canEdit = $this->canEdit();
    $canView = $this->canView();
    $bpmnContent = $this->getBpmnContent();
@endphp

<div
    class="bpmn-viewer {{ $class }}"
    data-bpmn-viewer
    data-mode="{{ $mode }}"
    data-source-type="{{ $bpmnSource['type'] }}"
    @if($bpmnSource['type'] === 'media') data-media-id="{{ $bpmnSource['id'] }}" @endif
    @if($bpmnSource['type'] === 'wp-media') data-wp-media-id="{{ $bpmnSource['id'] }}" @endif
    @if($bpmnSource['type'] === 'file') data-file-path="{{ $bpmnSource['path'] }}" @endif
    style="height: {{ $height }};"
>
    @if($showToolbar && $canEdit)
        <div class="bpmn-toolbar">
            <div class="bpmn-info">
                @if($bpmnSource['type'] === 'media')
                    <span class="text-sm text-gray-600">
                        {{ __('BPMN Media ID: :id', ['id' => $bpmnSource['id']]) }}
                    </span>
                @elseif($bpmnSource['type'] === 'wp-media')
                    <span class="text-sm text-gray-600">
                        {{ __('BPMN WordPress Media ID: :id', ['id' => $bpmnSource['id']]) }}
                    </span>
                @elseif($bpmnSource['type'] === 'file')
                    <span class="text-sm text-gray-600">
                        {{ __('BPMN File: :path', ['path' => basename($bpmnSource['path'])]) }}
                    </span>
                @endif
            </div>
            <button
                type="button"
                class="bpmn-save"
                data-bpmn-save
            >
                {{ __('Save Changes') }}
            </button>
        </div>
    @endif

    <div class="bpmn-container">
        <div class="bpmn-loading">
            {{ __('Loading BPMN diagram...') }}
        </div>
    </div>

    @if($bpmnContent)
        <script type="application/json" data-bpmn-content>
            {!! json_encode($bpmnContent) !!}
        </script>
    @endif
</div>

@push('styles')
    @vite('resources/css/bpmn.css', 'bpmn')
@endpush

@push('scripts')
    @vite('resources/js/bpmn.js', 'bpmn')
@endpush
