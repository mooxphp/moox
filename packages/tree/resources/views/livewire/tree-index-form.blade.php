@php
    $labelColumn = $configuration->getLabelColumn();
    $parentColumn = $configuration->getParentColumn();
@endphp

<div
    class="fi-sc fi-sc-has-gap fi-grid lg:fi-grid-cols"
    style="--cols-lg: repeat(2, minmax(0, 1fr)); --cols-default: repeat(1, minmax(0, 1fr));"
>
    <x-filament::fieldset label="Bezeichnung" class="fi-grid-col">
        <x-filament::input.wrapper :valid="! $errors->has('form.'.$labelColumn)">
            <x-filament::input
                id="tree-item-label"
                type="text"
                wire:model.live.debounce.400ms="form.{{ $labelColumn }}"
            />
        </x-filament::input.wrapper>

        @error('form.'.$labelColumn)
            <p class="fi-fo-field-wrp-error-message">{{ $message }}</p>
        @enderror
    </x-filament::fieldset>

    <x-filament::fieldset label="Elterneintrag" class="fi-grid-col">
        <x-filament::input.wrapper
            :valid="! $errors->has('form.'.$parentColumn)"
            class="fi-fo-select fi-fo-select-native"
        >
            <x-filament::input.select id="tree-item-parent-id" wire:model="form.{{ $parentColumn }}">
                <option value="">Root-Ebene</option>
                @foreach ($parentOptions as $parentOptionId => $parentOptionLabel)
                    <option value="{{ $parentOptionId }}">{{ $parentOptionLabel }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>

        @error('form.'.$parentColumn)
            <p class="fi-fo-field-wrp-error-message">{{ $message }}</p>
        @enderror
    </x-filament::fieldset>
</div>
