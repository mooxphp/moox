@php
    /** @var \Moox\Tree\Config\TreeIndexConfiguration $configuration */
    $showSave = $showSave ?? false;
    $showDelete = $showDelete ?? false;
@endphp

@if ($showSave || $showDelete)
    <x-slot name="footer">
        <div class="fi-sc fi-sc-has-gap fi-tree-footer">
            <div class="fi-tree-footer-actions">
                @if ($showSave)
                    <x-filament::button
                        type="submit"
                        form="tree-index-inspector-form"
                        icon="heroicon-m-check"
                    >
                        {{ $configuration->saveLabel() }}
                    </x-filament::button>
                @endif
            </div>

            @if ($showDelete)
                <x-filament::button
                    type="button"
                    wire:click="deleteSelectedRecord"
                    wire:confirm="{{ $configuration->deleteConfirmMessage() }}"
                    color="danger"
                    outlined
                    icon="heroicon-m-trash"
                >
                    Löschen
                </x-filament::button>
            @endif
        </div>
    </x-slot>
@endif
