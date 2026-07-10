@php
    use Moox\BlockEditor\Support\BlockEditorLocale;
    use Moox\BlockEditor\Support\DynamicFeedEditorCatalog;

    $initialBlocksJson = '[]';
    $editorInstanceId = 'block-editor-'.str_replace('.', '-', $this->getId());
    $wrapperId = $editorInstanceId.'-wrapper';
    $rootId = $editorInstanceId.'-root';
    $hiddenInputId = $editorInstanceId.'-state-hidden';
    $positiveBlockJson = null;
    $negativeBlockJson = null;

    if (is_string($state) && trim($state) !== '') {
        $decodedState = json_decode($state, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $initialBlocksJson = json_encode($decodedState, JSON_UNESCAPED_UNICODE) ?: '[]';
        }
    } elseif (! blank($state)) {
        $initialBlocksJson = json_encode($state, JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    if (isset($allowedBlockTypes) && is_array($allowedBlockTypes) && ! empty($allowedBlockTypes)) {
        $positiveBlockJson = json_encode($allowedBlockTypes, JSON_UNESCAPED_UNICODE) ?: null;
    }

    if (isset($excludedBlockTypes) && is_array($excludedBlockTypes) && ! empty($excludedBlockTypes)) {
        $negativeBlockJson = json_encode($excludedBlockTypes, JSON_UNESCAPED_UNICODE) ?: null;
    }

    $editorVersionCandidates = array_filter([
        @filemtime(public_path('vendor/moox/block-editor/core/render/mount-editor.js')),
        @filemtime(public_path('vendor/moox/block-editor/components/blocks/media/image.js')),
        @filemtime(public_path('vendor/moox/block-editor/components/templates/index.js')),
        @filemtime(public_path('vendor/moox/block-editor/components/templates/sidebar/sidebar.js')),
    ]);
    $editorAssetVersion = (string) (! empty($editorVersionCandidates) ? max($editorVersionCandidates) : time());
    $editorCssUrl = asset('vendor/moox/block-editor/styles/editor.css').'?v='.$editorAssetVersion;
    $editorBrowserUrl = asset('vendor/moox/block-editor/browser@4.js').'?v='.$editorAssetVersion;
    $editorMountUrl = asset('vendor/moox/block-editor/core/render/mount-editor.js').'?v='.$editorAssetVersion;
    $configuredMediaUploadMaxFileSizeKb = (int) config('media.upload.resource.max_file_size', 10240);
    $phpMediaUploadMaxFileSizeKb = max(1, (int) floor(\Illuminate\Http\UploadedFile::getMaxFilesize() / 1024));
    $mediaUploadMaxFileSizeKb = min(
        $configuredMediaUploadMaxFileSizeKb > 0 ? $configuredMediaUploadMaxFileSizeKb : 10240,
        $phpMediaUploadMaxFileSizeKb
    );
@endphp

<div>
    <link rel="stylesheet" href="{{ $editorCssUrl }}" />
    <script src="{{ $editorBrowserUrl }}"></script>

    <div id="{{ $wrapperId }}" class="" wire:ignore>
        <div
            id="{{ $rootId }}"
            data-editor-instance="{{ $editorInstanceId }}"
            data-hidden-input-id="{{ $hiddenInputId }}"
            data-block-json="{{ e($initialBlocksJson) }}"
            @if($positiveBlockJson)
                data-positive-block="{{ e($positiveBlockJson) }}"
            @endif
            @if($negativeBlockJson)
                data-negative-block="{{ e($negativeBlockJson) }}"
            @endif
            data-developer-json="{{ $developerJsonEnabled ? '1' : '0' }}"
            data-add-components="{{ $addComponentsEnabled ? '1' : '0' }}"
            data-json-import="{{ $jsonImportEnabled ? '1' : '0' }}"
            data-tailwind-browser-url="{{ $editorBrowserUrl }}"
            data-mount-editor-url="{{ $editorMountUrl }}"
            data-editor-asset-version="{{ e($editorAssetVersion) }}"
            data-templates-api-url="{{ route('moox-editor.templates.index') }}"
            data-dynamic-feed-sources='@json(DynamicFeedEditorCatalog::sources(BlockEditorLocale::resolveActive(request())))'
            @if($mediaLibraryApiUrl)
                data-media-library-api-url="{{ e($mediaLibraryApiUrl) }}"
            @endif
            @if($mediaLibraryCollection)
                data-media-library-collection="{{ e($mediaLibraryCollection) }}"
            @endif
            @if($mediaUsableType)
                data-media-usable-type="{{ e($mediaUsableType) }}"
            @endif
            @if($mediaUsableId)
                data-media-usable-id="{{ e($mediaUsableId) }}"
            @endif
            data-media-upload-language="{{ e(BlockEditorLocale::resolveActive(request())) }}"
            data-media-upload-max-file-size-kb="{{ $mediaUploadMaxFileSizeKb }}"
            data-moox-theme-templates="{{ $themeTemplatesEnabled ? '1' : '0' }}"
            @if($templateSlug)
                data-template-slug="{{ e($templateSlug) }}"
            @endif
            class="min-h-[320px]"
        ></div>
    </div>

    <input type="hidden" id="{{ $hiddenInputId }}" wire:model="state" />

    <script type="module" src="{{ $editorMountUrl }}"></script>
</div>

