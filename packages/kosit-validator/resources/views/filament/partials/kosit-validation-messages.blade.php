@php
    use Moox\KositValidator\Support\KositValidationMessages;

    /** @var \Moox\KositValidator\Models\KositValidation $record */
    $messages = KositValidationMessages::normalized(is_array($record->errors) ? $record->errors : null);
    $byType = ['error' => [], 'warning' => [], 'info' => []];
    foreach ($messages as $m) {
        $t = $m['type'];
        if (isset($byType[$t])) {
            $byType[$t][] = $m;
        }
    }

    $kositSeverityChipClasses = static fn (string $type): string => match ($type) {
        'error' => 'inline-flex items-center rounded-md border border-red-500/10 bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 dark:border-red-500/15 dark:bg-red-500/10 dark:text-red-300',
        'warning' => 'inline-flex items-center rounded-md border border-amber-500/10 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 dark:border-amber-500/15 dark:bg-amber-500/10 dark:text-amber-300',
        'info' => 'inline-flex items-center rounded-md border border-gray-500/10 bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:border-gray-500/15 dark:bg-gray-500/10 dark:text-gray-300',
        default => throw new \UnexpectedValueException("Unknown KOSIT severity type: {$type}"),
    };
@endphp
@if ($messages === [])
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('kosit-validator::fields.no_validation_messages') }}</p>
@else
    <div class="flex flex-col gap-6">
        @foreach (['error', 'warning', 'info'] as $type)
            @if ($byType[$type] !== [])
                <div>
                    <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('kosit-validator::fields.' . $type . 's') }}
                    </h3>
                    <ul class="m-0 flex list-none flex-col gap-3 p-0">
                        @foreach ($byType[$type] as $msg)
                            <li class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <span class="{{ $kositSeverityChipClasses($type) }}">
                                    {{ __('kosit-validator::fields.severity_' . $type) }}
                                </span>
                                <p class="mt-2 text-sm text-gray-800 dark:text-gray-200">
                                    {{ $msg['text'] }}
                                </p>
                                @if (filled($msg['location']) || filled($msg['rule']))
                                    <p
                                        class="mt-1 break-words text-xs text-gray-500 dark:text-gray-400">
                                        @if (filled($msg['rule']))
                                            <span>{{ __('kosit-validator::fields.rule') }}: {{ $msg['rule'] }}</span>
                                        @endif
                                        @if (filled($msg['location']))
                                            <span
                                                class="{{ filled($msg['rule']) ? 'ml-2' : '' }}">{{ __('kosit-validator::fields.location') }}: {{ $msg['location'] }}</span>
                                        @endif
                                    </p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </div>
@endif
