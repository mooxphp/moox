<x-filament::modal id="mediaPickerModal" width="7xl" :heading="__('media::fields.upload_and_select_media')">
    <div>
        {{ $this->form }}
    </div>

    <div class="fi-sc fi-sc-has-gap fi-grid lg:fi-grid-cols" style="--cols-lg: 6.5fr 3.5fr; --cols-default: repeat(1, minmax(0, 1fr));">
        <div class="fi-grid-col">
            <x-filament::section>
                <div class="fi-sc fi-grid lg:fi-grid-cols" style="--cols-lg: 2fr 1fr 1fr 1fr 1fr; --cols-default: repeat(1, minmax(0, 1fr)); gap: 0.5rem;">
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model.live.debounce.500ms="searchQuery"
                            placeholder="{{ __('media::fields.search') }}" />
                    </x-filament::input.wrapper>

                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="fileTypeFilter">
                            <option value="">{{ __('media::fields.all_types') }}</option>
                            <option value="images">{{ __('media::fields.images') }}</option>
                            <option value="videos">{{ __('media::fields.videos') }}</option>
                            <option value="audios">{{ __('media::fields.audios') }}</option>
                            <option value="documents">{{ __('media::fields.documents') }}</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>

                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="dateFilter">
                            <option value="">{{ __('media::fields.all_periods') }}</option>
                            <option value="today">{{ __('media::fields.today') }}</option>
                            <option value="week">{{ __('media::fields.week') }}</option>
                            <option value="month">{{ __('media::fields.month') }}</option>
                            <option value="year">{{ __('media::fields.year') }}</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>

                    <x-filament::input.wrapper>
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

                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="collectionFilter">
                            <option value="">{{ __('media::fields.all_collections') }}</option>
                            @foreach($collectionOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name === 'default' ? __('media::fields.default_collection') : $name }}</option>
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

                <div class="fi-sc fi-grid lg:fi-grid-cols" style="--cols-lg: repeat(3, minmax(0, 1fr)); --cols-default: repeat(2, minmax(0, 1fr)); gap: 0.5rem; margin-top: 1rem;">
                    @if($mediaItems)
                        @foreach ($mediaItems as $item)
                            @php
                                $mimeType = $item['mime_type'];
                                $fileData = $mimeTypeLabels[$mimeType] ?? null;
                            @endphp

                            <div wire:click="toggleMediaSelection({{ $item['id'] }})"
                                style="position: relative; border-radius: 0.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); overflow: hidden; background-color: #f3f4f6; cursor: pointer; transition: all 0.2s ease; {{ $selectedMediaMeta['id'] == $item['id'] ? 'box-shadow: 0 0 0 4px #93c5fd, 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); background-color: #eff6ff; padding: 0.1rem;' : '' }}"
                                @if ($fileData)
                                    <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; width: 100%; height: 8rem; padding: 1rem; border-radius: 0.5rem;">
                                        <x-filament::icon icon="{{ $fileData['icon'] }}" style="width: 3rem; height: 3rem; margin-bottom: 0.5rem;" />
                                        <div style="font-size: 0.875rem; color: #374151; width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: center; font-weight: 500;">
                                            {{ $item['file_name'] }}
                                        </div>
                                    </div>
                                @else
                                    <div style="position: relative; width: 100%; height: 8rem; border-radius: 0.5rem; overflow: hidden;">
                                        <img src="{{ $item['original_url'] }}" style="object-fit: cover; width: 100%; height: 100%;" />
                                    </div>
                                @endif

                                @if(in_array($item['id'], $selectedMediaIds))
                                    <div style="position: absolute; top: 0.25rem; left: 0.25rem;">
                                        <x-filament::icon icon="heroicon-o-check-circle" class="fi-size-lg" fill="#3B82F6" />
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>

                <div style="margin-top: 1rem;">
                    <x-filament::pagination :paginator="$mediaItems" />
                </div>

            </x-filament::section>
        </div>

        <div class="fi-sc fi-sc-has-gap fi-grid lg:fi-grid-cols" style="--cols-lg: 1fr; --cols-default: repeat(1, minmax(0, 1fr)); gap: 0.5rem; align-self: start;">
            @if(!empty($selectedMediaMeta['id']))
                    <x-filament::section>
                        <div class="fi-sc fi-grid lg:fi-grid-cols" style="--cols-lg: repeat(2, minmax(0, 1fr)); --cols-default: repeat(1, minmax(0, 1fr)); gap: 1rem;">
                            <div>
                                <span class="fi-sc-text">{{ __('media::fields.file_name') }}</span>
                                <p>{{ $selectedMediaMeta['file_name'] }}</p>
                            </div>

                            <div>
                                <span class="fi-sc-text">{{ __('media::fields.file_type') }}</span>
                                <p>{{ $selectedMediaMeta['mime_type'] }}</p>
                            </div>

                            <div>
                                <span class="fi-sc-text">{{ __('media::fields.size') }}</span>
                                <p>{{ number_format($selectedMediaMeta['size'] / 1024, 2) }} KB</p>
                            </div>

                            <div>
                                <span class="fi-sc-text">{{ __('media::fields.dimensions') }}</span>
                                <p>{{ $selectedMediaMeta['dimensions']['width'] ?? '-' }} x {{ $selectedMediaMeta['dimensions']['height'] ?? '-' }}</p>
                            </div>

                            <div>
                                <span class="fi-sc-text">{{ __('media::fields.created_at') }}</span>
                                <p>{{ $selectedMediaMeta['created_at'] ? \Carbon\Carbon::parse($selectedMediaMeta['created_at'])->format('d.m.Y H:i') : '-' }}</p>
                            </div>

                            <div>
                                <span class="fi-sc-text">{{ __('media::fields.updated_at') }}</span>
                                <p>{{ $selectedMediaMeta['updated_at'] ? \Carbon\Carbon::parse($selectedMediaMeta['updated_at'])->format('d.m.Y H:i') : '-' }}</p>
                            </div>

                            <div>
                                <span class="fi-sc-text">{{ __('media::fields.uploaded_by') }}</span>
                                <p>{{ $selectedMediaMeta['uploader_name'] ?? '-' }}</p>
                            </div>

                            <div>
                                <span class="fi-sc-text">{{ __('media::fields.collection') }}</span>
                                <div>
                                    <x-filament::input.wrapper>
                                    <x-filament::input.select 
                                        wire:model.live="selectedMediaMeta.media_collection_id"
                                        :disabled="$selectedMediaMeta['write_protected']"
                                    >
                                        @foreach($collectionOptions as $id => $name)
                                            <option value="{{ $id }}">{{ $name === __('media::fields.uncategorized') ? __('media::fields.uncategorized') : $name }}</option>
                                            @endforeach
                                        </x-filament::input.select>
                                    </x-filament::input.wrapper>
                                </div>
                            </div>
                        </div>
                    </x-filament::section>

                    <x-filament::section class="mt-2" collapsible collapsed>
                        <x-slot name="heading">
                            <h2 class="fi-section-header-heading">{{ __('media::fields.metadata') }}</h2>
                        </x-slot>
                        <div class="fi-sc fi-grid lg:fi-grid-cols" style="--cols-lg: repeat(1, minmax(0, 1fr)); --cols-default: repeat(1, minmax(0, 1fr)); gap: 1rem;">

                            <div class="fi-grid-col" >
                                <span class="fi-fo-field-label-content">{{ __('media::fields.name') }}</span>
                                <x-filament::input.wrapper>
                                    <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.name" placeholder="{{ __('media::fields.name') }}"
                                        :disabled="(bool) $selectedMediaMeta['write_protected']" />
                                </x-filament::input.wrapper>
                            </div>

                            <div class="fi-grid-col">
                                <span class="fi-fo-field-label-content">{{ __('media::fields.title') }}</span>
                                <x-filament::input.wrapper>
                                    <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.title" placeholder="{{ __('media::fields.title') }}"
                                        :disabled="(bool) $selectedMediaMeta['write_protected']" />
                                </x-filament::input.wrapper>
                            </div>

                            <div class="fi-grid-col">
                                <span class="fi-fo-field-label-content">{{ __('media::fields.description') }}</span>
                                <x-filament::input.wrapper>
                                    <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.description"
                                        placeholder="{{ __('media::fields.description') }}" :disabled="$selectedMediaMeta['write_protected'] === true" />
                                </x-filament::input.wrapper>
                            </div>

                            <div class="fi-grid-col">
                                <span class="fi-fo-field-label-content">{{ __('media::fields.alt_text') }}</span>
                                <x-filament::input.wrapper>
                                    <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.alt" placeholder="{{ __('media::fields.alt_text') }}"
                                        :disabled="$selectedMediaMeta['write_protected'] === true" />
                                </x-filament::input.wrapper>
                            </div>
                        </div>
                    </x-filament::section>

                    <x-filament::section  collapsible collapsed>
                        <x-slot name="heading">
                            <h2 class="fi-section-header-heading">{{ __('media::fields.internal_note') }}</h2>
                        </x-slot>
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.internal_note"
                                placeholder="{{ __('media::fields.internal_note') }}" :disabled="$selectedMediaMeta['write_protected'] === true" />
                        </x-filament::input.wrapper>
                    </x-filament::section>
                
                    <div class="fi-ac fi-align-end">
                        <x-filament::button wire:click="$dispatch('close-modal', { id: 'mediaPickerModal' })">
                            {{ __('media::fields.close') }}
                        </x-filament::button>
                        <x-filament::button wire:click="applySelection" color="primary">
                            {{ __('media::fields.apply_selection') }}
                        </x-filament::button>
                        
                    </div>
               
                </div>
            @else
                <x-filament::section>
                    <p class="fi-sc-text fi-size-lg">{{ __('media::fields.no_media_selected') }}</p>
                </x-filament::section>
            @endif
        </div>
    </div>

    
</x-filament::modal>