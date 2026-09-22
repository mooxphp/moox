@php
    $notes = $viewModel->notesGroup();
@endphp
@if($notes !== null)
    <details
        @if($notes['open']) open @endif
        class="group mb-3 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800/40">
        <summary
            class="cursor-pointer list-none px-4 py-3 [&::-webkit-details-marker]:hidden [&::marker]:hidden">
            @include('e-billing::filament.partials.invoice-details-summary', [
                'title' => $notes['title'],
                'subtitle' => $notes['subtitle'],
                'issueCount' => $notes['issue_count'],
                'issueLabel' => $notes['issue_label'],
            ])
        </summary>
        <div class="border-t border-gray-200 px-4 pt-2 pb-4 dark:border-gray-700">
            <dl class="m-0 flex flex-col gap-0">
                @foreach($notes['fields'] as $field)
                    @include('e-billing::filament.partials.invoice-field-row', ['field' => $field])
                @endforeach
            </dl>
        </div>
    </details>
@endif
