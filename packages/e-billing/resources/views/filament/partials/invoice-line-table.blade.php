@php
    $lines = $viewModel->lines();
@endphp
@forelse($lines as $lineVm)
    @php
        $fields = $lineVm->relevantFields($editing ?? false);
        $lineState = $lineVm->collapsibleState($fields);
        $lineKey = 'line-'.($lineVm->position() ?? (string) $loop->index);
        $sectionOpen = $lineState['open'];
        if (isset($this) && method_exists($this, 'syncAndGetCollapsibleOpen')) {
            $sectionOpen = $this->syncAndGetCollapsibleOpen($lineKey, $lineState['open']);
        }
    @endphp
    @if($fields !== [])
    <x-e-billing::collapsible-details
        :wire-key="$lineKey"
        :open="$sectionOpen"
        class="group mb-3 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/40"
    >
        <summary
            class="cursor-pointer list-none px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100 [&::-webkit-details-marker]:hidden [&::marker]:hidden">
            @include('e-billing::filament.partials.invoice-details-summary', [
                'title' => __('e-billing::fields.line_item_position', ['position' => $lineVm->position() ?? '—']),
                'subtitle' => $lineVm->subtitle(),
                'issueCount' => $lineState['issue_count'],
                'issueLabel' => $lineState['issue_label'],
            ])
        </summary>
        <div class="border-t border-gray-200 px-4 pt-2 pb-4 dark:border-gray-700">
            <dl class="m-0 flex flex-col gap-0">
                @foreach($fields as $field)
                    @include('e-billing::filament.partials.invoice-field-row', ['field' => $field])
                @endforeach
            </dl>
        </div>
    </x-e-billing::collapsible-details>
    @endif
@empty
    <div class="px-8 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ __('e-billing::fields.empty_no_line_items') }}
    </div>
@endforelse
