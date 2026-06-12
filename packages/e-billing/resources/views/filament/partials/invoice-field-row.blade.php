@php
    /** @var \Moox\EBilling\ViewModels\FieldViewData $field */
    $color = $field->badgeColor();
    $badgeSurface = match ($color) {
        'green' => 'border border-emerald-500/20 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-emerald-300',
        'blue' => 'border border-blue-500/20 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/15 dark:text-blue-300',
        'yellow' => 'border border-amber-500/20 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/15 dark:text-amber-300',
        'red' => 'border border-red-500/20 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/15 dark:text-red-300',
        default => 'border border-gray-500/20 bg-gray-100 text-gray-600 dark:border-gray-500/30 dark:bg-gray-500/15 dark:text-gray-300',
    };
@endphp
<div
    class="flex items-start justify-between gap-3 border-b border-gray-200 py-2 last:border-b-0 last:pb-0 dark:border-gray-700">
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-baseline gap-2">
            <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $field->label }}</span>
            @if($field->btNumber)
                <span class="text-[11px] text-gray-400 dark:text-gray-500">{{ $field->btNumber }}</span>
            @endif
        </div>
        <div
            class="mt-0.5 break-words text-sm whitespace-pre-line text-gray-800 dark:text-gray-200">
            @if(is_array($field->value) && isset($field->value[0]) && is_array($field->value[0]))
            <ul class="list-disc space-y-1 pl-5">
                @foreach($field->value as $row)
                    @if(is_array($row))
                        <li>{{ implode(' · ', array_filter([
                            $row['iban'] ?? null,
                            $row['bic'] ?? null,
                            $row['bank_name'] ?? null,
                        ], static fn (mixed $v): bool => is_string($v) && $v !== '')) }}</li>
                    @endif
                @endforeach
            </ul>
            @elseif(is_string($field->value) && str_contains($field->value, "\n"))
                <div
                    class="m-0 font-inherit text-sm whitespace-pre-wrap text-gray-800 dark:text-gray-200">{{ $field->value }}</div>
            @elseif($field->value !== null && $field->value !== '')
                {{ $field->value }}
            @else
                <span class="text-gray-400 dark:text-gray-500">—</span>
            @endif
        </div>
        @if($field->hint)
            <div class="mt-1 text-xs text-gray-500 italic dark:text-gray-400">
                {{ $field->hint }}
            </div>
        @endif
    </div>
    <div class="shrink-0">
        <span
            class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium {{ $badgeSurface }}">
            <span class="hidden sm:inline" aria-hidden="true">●</span>
            {{ $field->badgeLabel() }}
        </span>
    </div>
</div>
