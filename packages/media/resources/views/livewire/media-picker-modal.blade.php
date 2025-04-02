<x-filament::modal id="mediaPickerModal" width="7xl">
    <x-slot name="header">
        <h2 class="text-lg font-bold">{{ __('media::fields.upload_and_select_media') }}</h2>
    </x-slot>

    <div class="min-w-[1000px]">
        <div class="mb-4">
            {{ $this->form }}
        </div>

        <div class="mt-4">
        </div>
    </div>

    <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <x-filament::section>
                <div class="flex flex-row gap-4 mb-4">
                    <x-filament::input.wrapper class="">
                        <x-filament::input type="text" wire:model.live.debounce.500ms="searchQuery"
                            placeholder="{{ __('media::fields.search') }}"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                    </x-filament::input.wrapper>

                    <x-filament::input.wrapper class="w-1/6">
                        <x-filament::input.select wire:model.live="fileTypeFilter">
                            <option value="">{{ __('media::fields.all_types') }}</option>
                            <option value="images">{{ __('media::fields.images') }}</option>
                            <option value="videos">{{ __('media::fields.videos') }}</option>
                            <option value="audios">{{ __('media::fields.audios') }}</option>
                            <option value="documents">{{ __('media::fields.documents') }}</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>

                    <x-filament::input.wrapper class="w-1/6">
                        <x-filament::input.select wire:model.live="dateFilter">
                            <option value="">{{ __('media::fields.all_periods') }}</option>
                            <option value="today">{{ __('media::fields.today') }}</option>
                            <option value="week">{{ __('media::fields.week') }}</option>
                            <option value="month">{{ __('media::fields.month') }}</option>
                            <option value="year">{{ __('media::fields.year') }}</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>

                    <x-filament::input.wrapper class="w-1/6">
                        <x-filament::input.select wire:model.live="uploaderFilter">
                            <option value="">{{ __('media::fields.uploaded_by') }}</option>
                            @foreach($uploaderOptions as $type => $uploaders)
                                <optgroup label="{{ $type }}">
                                    @foreach($uploaders as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

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

                <x-filament::grid class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @if($mediaItems)
                                    @foreach ($mediaItems as $item)
                                                    @php
                                                        $mimeType = $item['mime_type'];
                                                        $fileData = $mimeTypeLabels[$mimeType] ?? null;
                                                    @endphp

                                                    <div wire:click="toggleMediaSelection({{ $item['id'] }})"
                                                        class="relative rounded-lg shadow-md overflow-hidden bg-gray-100 hover:shadow-lg transition cursor-pointer
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        {{ in_array($item['id'], $selectedMediaIds) ? 'ring-2 ring-blue-600' : 'border border-gray-200' }}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    {{ $selectedMediaMeta['id'] == $item['id'] ? 'ring-4 ring-blue-700 border-2 border-blue-700' : '' }}">
                                                        @if ($fileData)
                                                            <div class="flex flex-col justify-between items-center w-full h-32 mt-3">
                                                                <x-filament::icon icon="{{ $fileData['icon'] }}" class="w-16 h-16" />
                                                                <div
                                                                    class="text-xs text-gray-700 w-full mt-2 overflow-hidden text-ellipsis whitespace-normal break-words px-2">
                                                                    {{ $item['file_name'] }}
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div class="relative w-full h-32">
                                                                <img src="{{ $item['original_url'] }}" class="object-cover w-full h-full rounded-t-lg" />
                                                            </div>
                                                        @endif

                                                        @if(in_array($item['id'], $selectedMediaIds))
                                                            <div class="absolute top-1 right-1">
                                                                <x-filament::icon icon="heroicon-o-check-circle" class="w-6 h-6" fill="#3B82F6" />
                                                            </div>
                                                        @endif
                                                    </div>
                                    @endforeach
                    @endif
                </x-filament::grid>

                <div class="pt-4 mt-4">
                    <x-filament::pagination :paginator="$mediaItems" />
                </div>

            </x-filament::section>
        </div>

        <div class="w-full md:w-2/5 lg:w-1/3 max-w-md flex-shrink-0 border-l pl-4 flex flex-col h-full">
            @if(!empty($selectedMediaMeta['id']))
                <div class="flex-1">
                    <x-filament::section>
                        <div class="space-y-4">
                            <div>
                                <span class="text-sm font-medium text-gray-500">{{ __('media::fields.file_name') }}</span>
                                <p class="mt-1 text-sm text-gray-900">{{ $selectedMediaMeta['file_name'] }}</p>
                            </div>

                            <div>
                                <span class="text-sm font-medium text-gray-500">{{ __('media::fields.file_type') }}</span>
                                <p class="mt-1 text-sm text-gray-900">{{ $selectedMediaMeta['mime_type'] }}</p>
                            </div>

                            <div>
                                <span class="text-sm font-medium text-gray-500">{{ __('media::fields.size') }}</span>
                                <p class="mt-1 text-sm text-gray-900">{{ number_format($selectedMediaMeta['size'] / 1024, 2) }} KB</p>
                            </div>

                            <div>
                                <span class="text-sm font-medium text-gray-500">{{ __('media::fields.dimensions') }}</span>
                                <p class="mt-1 text-sm text-gray-900">{{ $selectedMediaMeta['dimensions']['width'] ?? '-' }} x {{ $selectedMediaMeta['dimensions']['height'] ?? '-' }}</p>
                            </div>

                            <div>
                                <span class="text-sm font-medium text-gray-500">{{ __('media::fields.created_at') }}</span>
                                <p class="mt-1 text-sm text-gray-900">{{ $selectedMediaMeta['created_at'] ? \Carbon\Carbon::parse($selectedMediaMeta['created_at'])->format('d.m.Y H:i') : '-' }}</p>
                            </div>

                            <div>
                                <span class="text-sm font-medium text-gray-500">{{ __('media::fields.updated_at') }}</span>
                                <p class="mt-1 text-sm text-gray-900">{{ $selectedMediaMeta['updated_at'] ? \Carbon\Carbon::parse($selectedMediaMeta['updated_at'])->format('d.m.Y H:i') : '-' }}</p>
                            </div>

                            <div>
                                <span class="text-sm font-medium text-gray-500">{{ __('media::fields.uploaded_by') }}</span>
                                <p class="mt-1 text-sm text-gray-900">{{ $selectedMediaMeta['uploader_name'] ?? '-' }}</p>
                            </div>
                        </div>
                    </x-filament::section>

                    <x-filament::section class="mt-2">
                        <h3 class="text-lg font-semibold mb-4">{{ __('media::fields.metadata') }}</h3>
                        <form wire:submit.prevent="saveMetadata">
                            <x-filament-forms::field-wrapper.label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('media::fields.name') }}
                            </x-filament-forms::field-wrapper.label>
                            <x-filament::input.wrapper class="mb-4">
                                <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.name" placeholder="{{ __('media::fields.name') }}"
                                    :disabled="(bool) $selectedMediaMeta['write_protected']"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                            </x-filament::input.wrapper>

                            <x-filament-forms::field-wrapper.label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('media::fields.title') }}
                            </x-filament-forms::field-wrapper.label>
                            <x-filament::input.wrapper class="mb-4">
                                <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.title" placeholder="{{ __('media::fields.title') }}"
                                    :disabled="(bool) $selectedMediaMeta['write_protected']"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                            </x-filament::input.wrapper>

                            <x-filament-forms::field-wrapper.label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('media::fields.description') }}
                            </x-filament-forms::field-wrapper.label>
                            <x-filament::input.wrapper class="mb-4">
                                <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.description"
                                    placeholder="{{ __('media::fields.description') }}" :disabled="$selectedMediaMeta['write_protected'] === true"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                            </x-filament::input.wrapper>

                            <x-filament-forms::field-wrapper.label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('media::fields.alt_text') }}
                            </x-filament-forms::field-wrapper.label>
                            <x-filament::input.wrapper class="mb-4">
                                <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.alt" placeholder="{{ __('media::fields.alt_text') }}"
                                    :disabled="$selectedMediaMeta['write_protected'] === true"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                            </x-filament::input.wrapper>
                        </form>
                    </x-filament::section>

                    <x-filament::section class="mt-2">
                        <h3 class="text-lg font-semibold mb-4">{{ __('media::fields.internal_note') }}</h3>
                        <form wire:submit.prevent="saveMetadata">
                            <x-filament::input.wrapper class="mb-4">
                                <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.internal_note"
                                    placeholder="{{ __('media::fields.internal_note') }}" :disabled="$selectedMediaMeta['write_protected'] === true"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                            </x-filament::input.wrapper>
                        </form>
                    </x-filament::section>
                </div>

                <div class="mt-2 flex gap-2 justify-end">
                    <x-filament::button wire:click="applySelection" color="primary">
                        {{ __('media::fields.apply_selection') }}
                    </x-filament::button>
                    <x-filament::button wire:click="$dispatch('close-modal', { id: 'mediaPickerModal' })">
                        {{ __('media::fields.close') }}
                    </x-filament::button>
                </div>
            @else
                <x-filament::section>
                    <p class="text-gray-500">{{ __('media::fields.no_media_selected') }}</p>
                </x-filament::section>
            @endif
        </div>
    </div>

    <x-slot name="footer">
    </x-slot>
</x-filament::modal>