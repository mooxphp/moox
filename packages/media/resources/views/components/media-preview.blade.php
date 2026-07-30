@php
    $record = $getRecord();
@endphp

@if ($record && ($record->mime_type ?? '') === 'application/pdf')
    <x-filament::section>
        <x-slot name="heading">
            {{ __('media::fields.preview') }}
        </x-slot>

        <iframe
            src="{{ $record->getUrl() }}"
            title="{{ $record->file_name }}"
            style="width: 100%; height: 36rem; border: 0; border-radius: 0.5rem;"
        ></iframe>
    </x-filament::section>
@endif
