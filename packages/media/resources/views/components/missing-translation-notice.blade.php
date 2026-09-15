@php
    $locale = $locale ?? '';
@endphp

<div class="rounded-lg border border-amber-300/80 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-200">
    {{ __('media::fields.no_translation_yet', ['locale' => $locale]) }}
</div>
