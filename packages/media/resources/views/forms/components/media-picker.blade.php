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
    " wire:ignore>

        @if ($this instanceof \Filament\Resources\Pages\EditRecord || $this instanceof \Filament\Resources\Pages\CreateRecord)
            <x-filament::button color="primary" size="sm" icon="heroicon-o-photo" style="width: 100%;" x-on:click="
                                                        $dispatch('set-media-picker-model', {
                                                        modelId: {{ $getRecord()?->id ?? 0 }},
                                                        modelClass: '{{ $getRecord() ? addslashes($getRecord()::class) : addslashes($this->getResource()::getModel()) }}'
                                                        });
                                                         $dispatch('open-modal', { id: 'mediaPickerModal' });
                                                        ">
                <span>{{ __('media::fields.select_media') }}</span>
            </x-filament::button>
        @endif

        <div class="fi-sc fi-grid lg:fi-grid-cols"
            style="--cols-lg: repeat(3, minmax(0, 1fr)); --cols-default: repeat(2, minmax(0, 1fr)); gap: 0.5rem; margin-top: 1rem;"
            x-show="selectedMedia.length > 0">
            <template x-for="(media, index) in selectedMedia" :key="media . id">
                <div
                    style="position: relative; background-color: #ffffff; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); overflow: hidden; border: 1px solid #e5e7eb; transition: box-shadow 0.3s ease;">
                    <template x-if="isAvatar">
                        <x-filament::avatar x-bind:src="media.url" x-bind:alt="media.name" size="w-12 h-12" />
                    </template>

                    <template x-if="!isAvatar">
                        <div>
                            <template x-if="media.mime_type && media.mime_type.startsWith('image/')">
                                <img :src="media . url" :alt="media . name"
                                    style="width: 100%; height: 12rem; object-fit: cover; border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem; cursor: pointer;"
                                    x-on:click="window.open(media.url, '_blank')" />
                            </template>

                            <template x-if="media.mime_type && !media.mime_type.startsWith('image/')">
                                <div style="display: flex; flex-direction: column; justify-content: space-between; align-items: center; width: 100%; height: 12rem; margin-top: 1.5rem; cursor: pointer;"
                                    x-on:click="window.open(media.url, '_blank')">
                                    <img :src="$el . dataset . baseUrl + getIconForMimeType(media . mime_type)"
                                        data-base-url="{{ asset('') }}" style="width: 4rem; height: 4rem;" />
                                    <div style="font-size: 0.75rem; color: #374151; width: 100%; margin-top: 0.5rem; overflow: hidden; text-overflow: ellipsis; white-space: normal; word-break: break-words; padding: 0 0.5rem;"
                                        x-text="media.file_name"></div>
                                </div>
                            </template>
                        </div>
                    </template>

                    @if ($this instanceof \Filament\Resources\Pages\EditRecord)
                        <div style="position: absolute; top: 0; left: 0;">
                            <x-filament::button color="danger" size="xs" icon="heroicon-o-x-mark"
                                x-on:click="selectedMedia.splice(index, 1); initializeState();">
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </template>
        </div>

        <div x-show="selectedMedia.length === 0"
            style="border: 1px dashed #e5e7eb; border-radius: 0.5rem; padding: 1rem; background-color: #f9fafb; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; color: #6b7280; margin-top: 1rem;">
            {{ __('media::fields.no_media_selected') }}
        </div>

        <livewire:media-picker-modal id="media-picker-modal" :multiple="$field->isMultiple()"
            wire:key="media-picker-modal-{{ $field->getStatePath() }}-{{ $getRecord()?->id ?? 'new' }}"
            :upload-config="$field->getUploadConfig()"
            :model-class="$this->getRecord() ? get_class($this->getRecord()) : $this->getResource()::getModel()"
            :model-id="$this->getRecord()?->id" />
    </div>
</x-dynamic-component>