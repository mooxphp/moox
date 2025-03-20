<div class="flex items-center">
    @if($availableLocales->isNotEmpty())
        <select wire:change="changeLocale($event.target.value)" wire:model="locale"
            class="block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @foreach ($availableLocales as $key => $label)
                <option value="{{ $label }}" {{ $label === app()->getLocale() ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    @endif

</div>