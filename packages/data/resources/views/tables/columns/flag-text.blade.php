<div class="flex items-center gap-2">
    @if($flag)
        <x-dynamic-component :component="$flag" class="w-6 h-6" />
    @endif
    <span>{{ $text }}</span>
</div>
