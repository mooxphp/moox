@php
    $banner = $viewModel->statusBanner();
    $bannerInnerClasses = match ($banner['color']) {
        'red' => 'bg-red-50 text-red-800 dark:bg-red-500/15 dark:text-red-300',
        'yellow' => 'bg-amber-50 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
        'blue' => 'bg-blue-50 text-blue-800 dark:bg-blue-500/15 dark:text-blue-300',
        'green' => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        default => 'bg-gray-50 text-gray-800 dark:bg-gray-500/15 dark:text-gray-300',
    };
@endphp
<div
    class="mb-6 flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800/40 sm:flex-row sm:items-center">
    <div
        class="flex min-w-0 flex-1 items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium {{ $bannerInnerClasses }}">
        <span class="min-w-0">{{ $banner['text'] }}</span>
    </div>
    <div class="flex shrink-0 items-center gap-2">
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('e-billing::fields.validation') }}</span>
        @include('e-billing::components.validation-score-ring-display', ['score' => $viewModel->validationScore()])
    </div>
</div>
