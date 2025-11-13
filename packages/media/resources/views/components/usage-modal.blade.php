@php
    use Filament\Facades\Filament;

    $record = $getRecord();
    $usages = \DB::table('media_usables')
        ->where('media_id', $record->id)
        ->get();

    if ($usages->isEmpty()) {
        return;
    }
@endphp

<style>
    .usage-modal .fi-modal-window {
        height: auto;
    }
</style>

<x-filament::modal id="usage-modal-{{ $record->id }}" width="4xl" :heading="__('media::fields.usage')"
    class="usage-modal">
    @foreach($usages->groupBy('media_usable_type') as $type => $items)
        @php
            $typeName = class_basename($type);
            $baseUrl = Filament::getCurrentPanel()->getUrl();
        @endphp

        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                <h3 class="fi-section-header-heading">{{ $typeName }}</h3>
                <x-filament::badge color="gray">
                    {{ $items->count() }} {{ trans_choice('media::fields.link|links', $items->count()) }}
                </x-filament::badge>
            </div>

            <div style="background-color: white; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
                @foreach($items as $item)
                    @php
                        $type = Str::plural(strtolower($typeName));
                        $url = $baseUrl . '/' . $type . '/' . $item->media_usable_id;
                    @endphp

                    <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; border-radius: 0.375rem; transition: background-color 0.2s;"
                        onmouseover="this.style.backgroundColor='#f3f4f6'" onmouseout="this.style.backgroundColor='white'">
                        <x-filament::icon icon="heroicon-m-link" />
                        <x-filament::link :href="$url" target="_blank">
                            {{ $url }}
                        </x-filament::link>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endforeach
</x-filament::modal>