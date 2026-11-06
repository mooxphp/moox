@php
    use Filament\Support\Enums\IconPosition;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
    use Filament\Widgets\View\Components\StatsOverviewWidgetComponent\StatComponent\DescriptionComponent;

    $descriptionColor = $getDescriptionColor() ?? 'gray';
    $descriptionIcon = $getDescriptionIcon();
    $descriptionIconPosition = $getDescriptionIconPosition();
    $iconColor = $getColor() ?? 'gray';
    $url = $getUrl();
    $tag = $url ? 'a' : 'div';
@endphp

<{!! $tag !!}
    @if ($url)
        {{ \Filament\Support\generate_href_html($url, $shouldOpenUrlInNewTab()) }}
    @endif
    {{
        $getExtraAttributeBag()
            ->class([
                'fi-wi-stats-overview-stat',
            ])
    }}
>
    <div class="fi-wi-stats-overview-stat-content">
        <div class="fi-wi-stats-overview-stat-label-ctn">
            <span class="fi-wi-stats-overview-stat-label">
                {{ $getLabel() }}
            </span>
        </div>

        <div
            class="fi-wi-stats-overview-stat-value"
            style="display: flex; align-items: center; gap: 0.75rem;"
        >
            @if ($getIcon())
                <span {{ (new FilamentComponentAttributeBag)->color(DescriptionComponent::class, $iconColor) }}>
                    {{ \Filament\Support\generate_icon_html(
                        $getIcon(),
                        attributes: (new FilamentComponentAttributeBag),
                        size: IconSize::TwoExtraLarge,
                    ) }}
                </span>
            @endif

            @if (filled($value = $getValue()))
                <span>{{ $value }}</span>
            @else
                <span class="fi-wi-stats-overview-stat-placeholder">
                    {{ $getPlaceholder() }}
                </span>
            @endif
        </div>

        @if ($description = $getDescription())
            <div
                {{ (new FilamentComponentAttributeBag)->color(DescriptionComponent::class, $descriptionColor)->class(['fi-wi-stats-overview-stat-description']) }}
            >
                @if ($descriptionIcon && in_array($descriptionIconPosition, [IconPosition::Before, 'before'], true))
                    {{ \Filament\Support\generate_icon_html($descriptionIcon, attributes: (new FilamentComponentAttributeBag)) }}
                @endif

                <span>
                    {{ $description }}
                </span>

                @if ($descriptionIcon && in_array($descriptionIconPosition, [IconPosition::After, 'after'], true))
                    {{ \Filament\Support\generate_icon_html($descriptionIcon, attributes: (new FilamentComponentAttributeBag)) }}
                @endif
            </div>
        @endif
    </div>
</{!! $tag !!}>
