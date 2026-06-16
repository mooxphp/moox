<div
    class="fi-sc fi-sc-has-gap fi-grid"
    style="--cols-default: repeat(1, minmax(0, 1fr));"
>
    @if ($isToolbarSearchEnabled)
        <x-filament::input.wrapper
            prefix-icon="heroicon-m-magnifying-glass"
            class="w-full"
        >
            <x-filament::input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Suchen..."
            />
        </x-filament::input.wrapper>
    @endif

    @if ($isToolbarLanguageSwitcherEnabled)
        <div class="w-full [&_.fi-dropdown]:!block [&_.fi-dropdown]:!w-full [&_.fi-dropdown-trigger]:!w-full [&_.fi-btn]:!w-full">
            @include('localization::lang-selector', ['fullWidth' => true])
        </div>
    @endif
</div>
