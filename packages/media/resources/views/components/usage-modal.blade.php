@php
    use Filament\Facades\Filament;

    $record = $getRecord();
    $usages = \DB::table('media_usables')
        ->where('media_id', $record->id)
        ->get();

    if ($usages->isEmpty()) {
        return;
    }

    $groupedUsages = $usages->groupBy('media_usable_type')
        ->map(function ($items, $type) {
            $typeName = class_basename($type);
            $baseUrl = Filament::getCurrentPanel()->getUrl();

            $links = $items->map(function ($item) use ($typeName, $baseUrl) {
                $type = Str::plural(strtolower($typeName));
                $url = $baseUrl . '/' . $type . '/' . $item->media_usable_id;

                return "
                                                    <div class=\"flex items-center gap-2 py-2 px-3 hover:bg-gray-100 rounded-md transition-colors\">
                                                        <svg class=\"w-4 h-4 text-gray-400\" xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\">
                                                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244\" />
                                                        </svg>
                                                        <a href=\"{$url}\" target=\"_blank\" class=\"text-primary-600 hover:text-primary-700 hover:underline break-all text-sm\">
                                                            {$url}
                                                        </a>
                                                    </div>
                                                ";
            })->join("\n");

            return "
                                                <div class=\"mb-6\">
                                                    <div class=\"flex items-center gap-2 mb-3\">
                                                        <h3 class=\"text-lg font-medium text-gray-900\">{$typeName}</h3>
                                                        <span class=\"px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 rounded-full\">
                                                            {$items->count()} " . trans_choice('media::fields.link|links', $items->count()) . "
                                                        </span>
                                                    </div>
                                                    <div class=\"bg-white border border-gray-200 rounded-lg divide-y divide-gray-100\">
                                                        {$links}
                                                    </div>
                                                </div>
                                            ";
        })->join("\n");
@endphp

<x-filament::modal id="usage-modal-{{ $record->id }}" width="4xl" :heading="__('media::fields.usage')">
    <div class="space-y-6">
        {!! $groupedUsages !!}
    </div>
</x-filament::modal>