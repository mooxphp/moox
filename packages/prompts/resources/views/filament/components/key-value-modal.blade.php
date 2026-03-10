<div class="space-y-4">
    <style>
        .key-value-modal-table {
            width: 100%;
            border-collapse: collapse;
        }

        .key-value-modal-table th,
        .key-value-modal-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .key-value-modal-table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }

        .key-value-modal-table td:first-child {
            font-weight: 500;
            color: #6b7280;
            width: 30%;
            vertical-align: top;
        }

        .key-value-modal-table td:last-child {
            word-break: break-word;
            white-space: pre-wrap;
            overflow-wrap: break-word;
            max-width: 0;
        }
    </style>
    <table class="key-value-modal-table">
        <thead>
            <tr>
                <th>{{ __('moox-prompts::prompts.ui.key') }}</th>
                <th>{{ __('moox-prompts::prompts.ui.value') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $key => $value)
                <tr>
                    <td>{{ $key }}</td>
                    <td>{{ $value }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="text-center text-gray-500 py-4">
                        {{ __('moox-prompts::prompts.ui.no_data') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>