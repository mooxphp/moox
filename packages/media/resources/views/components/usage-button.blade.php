<x-filament::button color="info" size="sm" icon="heroicon-m-link" wire:click="openUsageModal({{ $record->id }})">
    {{ $usages->count() }} {{ trans_choice('media::fields.link|links', $usages->count()) }}
</x-filament::button>