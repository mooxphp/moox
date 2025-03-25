<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @php
        $mimeTypeLabels = [
            'application/pdf' => [
                'label' => 'PDF',
                'icon' => '/vendor/file-icons/pdf.svg'
            ],
            'application/msword' => [
                'label' => 'DOC',
                'icon' => '/vendor/file-icons/doc.svg'
            ],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => [
                'label' => 'DOCX',
                'icon' => '/vendor/file-icons/doc.svg'
            ],
            'application/vnd.ms-excel' => [
                'label' => 'XLS',
                'icon' => '/vendor/file-icons/xls.svg'
            ],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => [
                'label' => 'XLSX',
                'icon' => '/vendor/file-icons/xls.svg'
            ],
            'application/vnd.ms-powerpoint' => [
                'label' => 'PPT',
                'icon' => '/vendor/file-icons/ppt.svg'
            ],
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => [
                'label' => 'PPTX',
                'icon' => '/vendor/file-icons/ppt.svg'
            ],
            'video/mp4' => [
                'label' => 'MP4',
                'icon' => '/vendor/file-icons/mp4.svg'
            ],
            'video/webm' => [
                'label' => 'WEBM',
                'icon' => '/vendor/file-icons/mp4.svg'
            ],
            'video/quicktime' => [
                'label' => 'MOV',
                'icon' => '/vendor/file-icons/mp4.svg'
            ],
            'audio/mpeg' => [
                'label' => 'MP3',
                'icon' => '/vendor/file-icons/mp3.svg'
            ],
            'audio/wav' => [
                'label' => 'WAV',
                'icon' => '/vendor/file-icons/mp3.svg'
            ],
            'audio/ogg' => [
                'label' => 'OGG',
                'icon' => '/vendor/file-icons/mp3.svg'
            ],
            'image/svg+xml' => [
                'label' => 'SVG',
                'icon' => '/vendor/file-icons/svg.svg'
            ],
            'application/zip' => [
                'label' => 'ZIP',
                'icon' => '/vendor/file-icons/zip.svg'
            ],
            'application/x-zip-compressed' => [
                'label' => 'ZIP',
                'icon' => '/vendor/file-icons/zip.svg'
            ],
            'text/plain' => [
                'label' => 'TXT',
                'icon' => '/vendor/file-icons/txt.svg'
            ],
            'text/csv' => [
                'label' => 'CSV',
                'icon' => '/vendor/file-icons/csv.svg'
            ],
        ];
    @endphp
    <div x-data="{
        selectedMedia: {{ json_encode($getRecord()?->mediaThroughUsables()?->get()->map(function ($media) {
    return [
        'id' => $media->id,
        'url' => $media->getUrl(),
        'file_name' => $media->file_name,
        'name' => $media->name,
        'mime_type' => $media->mime_type
    ];
})->toArray() ?? [], JSON_UNESCAPED_UNICODE) }},
        mimeTypes: {{ Js::from($mimeTypeLabels) }},
        getIconForMimeType(type) {
            return this.mimeTypes[type]?.icon || this.mimeTypes['application/pdf'].icon;
        },
        state: @entangle($getStatePath()),
        isMultiple: {{ $field->isMultiple() ? 'true' : 'false' }},
        isAvatar: {{ $field->isAvatar() ? 'true' : 'false' }},
        
        isNestedArray: function(data) {
            return Array.isArray(data) && Array.isArray(data[0]);
        },
        
        initializeState: function() {
            if (this.isMultiple) {
                this.state = this.selectedMedia.map(media => media.id);
            } else {
                this.state = this.selectedMedia.length > 0 ? [this.selectedMedia[0].id] : [];
            }
        }
    }" x-init="
        window.addEventListener('mediaSelected', event => {
            if (isNestedArray(event.detail)) {
                selectedMedia = event.detail[0];
            } else {
                selectedMedia = event.detail;
            }
            initializeState();
        });
        initializeState();
    " class="space-y-4">

        @if ($this instanceof \Filament\Resources\Pages\EditRecord || $this instanceof \Filament\Resources\Pages\CreateRecord)
            <x-filament::button color="primary" size="sm" class="w-full flex items-center justify-center space-x-2"
                x-on:click="
                                                                    $dispatch('set-media-picker-model', {
                                                                    modelId: {{ $getRecord()?->id ?? 0 }},
                                                                 modelClass: '{{ $getRecord() ? addslashes($getRecord()::class) : addslashes($this->getResource()::getModel()) }}'
                                                                    });
                                                                    $dispatch('open-modal', { id: 'mediaPickerModal' });
                                                                    ">
                <span>{{ __('media::fields.select_media') }}</span>
            </x-filament::button>
        @endif

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4" x-show="selectedMedia.length > 0">
            <template x-for="(media, index) in selectedMedia" :key="media . id">
                <div
                    class="relative group bg-white rounded-lg shadow hover:shadow-md transition-shadow duration-300 overflow-hidden border border-gray-200">
                    <template x-if="isAvatar">
                        <x-filament::avatar x-bind:src="media.url" x-bind:alt="media.name" size="w-12 h-12" />
                    </template>

                    <template x-if="!isAvatar">
                        <div>
                            <template x-if="media.mime_type && media.mime_type.startsWith('image/')">
                                <img :src="media . url" :alt="media . name"
                                    class="w-full h-32 object-cover rounded-t-lg cursor-pointer"
                                    x-on:click="window.open(media.url, '_blank')" />
                            </template>

                            <template x-if="media.mime_type && !media.mime_type.startsWith('image/')">
                                <div class="flex flex-col justify-between items-center w-full h-32 mt-3 cursor-pointer"
                                    x-on:click="window.open(media.url, '_blank')">
                                    <img :src="$el . dataset . baseUrl + getIconForMimeType(media . mime_type)"
                                        data-base-url="{{ asset('') }}" class="w-16 h-16" />
                                    <div class="text-xs text-gray-700 w-full mt-2 overflow-hidden text-ellipsis whitespace-normal break-words px-2"
                                        x-text="media.file_name"></div>
                                </div>
                            </template>
                        </div>
                    </template>

                    @if ($this instanceof \Filament\Resources\Pages\EditRecord)
                        <div class="absolute top-0 right-0">
                            <x-filament::button color="danger" size="xs" icon="heroicon-o-x-mark"
                                x-on:click="selectedMedia.splice(index, 1); initializeState();">
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </template>
        </div>

        <div x-show="selectedMedia.length === 0"
            class="border border-dashed border-gray-300 rounded-lg p-4 bg-gray-50 flex items-center justify-center text-sm text-gray-500">
            {{ __('media::fields.no_media_selected') }}
        </div>

        <livewire:media-picker-modal id="media-picker-modal" :multiple="$field->isMultiple()"
            :upload-config="$field->getUploadConfig()"
            :model-class="$this->getRecord() ? get_class($this->getRecord()) : $this->getResource()::getModel()"
            :model-id="$this->getRecord()?->id" />
    </div>
</x-dynamic-component>