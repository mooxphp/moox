        @php
            $rows = $field->getRows() ?? 10;
            $statePath = $field->getStatePath();
            $state = $field->getState();
            $isDisabled = $isDisabled();

            if (is_array($state) || is_object($state)) {
                $state = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        @endphp
        <x-dynamic-component :component="$getFieldWrapperView()" :field="$field">

            <x-filament::input.wrapper :disabled="$isDisabled">


                @if ($isDisabled)
                    <div x-data="{ state: @js($state) }">
                        <textarea x-model="state" rows="{{ $rows }}" readonly
                            class= 'block h-full w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6'>
                        </textarea>
                    </div>
                @else
                    <div x-data="{ state: @js($state) }">
                        <textarea wire:model="{{ $statePath }}" x-model="state" rows="{{ $rows }}"
                            class= 'block h-full w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6'>
                        </textarea>
                    </div>
                @endif
            </x-filament::input.wrapper>
        </x-dynamic-component>
