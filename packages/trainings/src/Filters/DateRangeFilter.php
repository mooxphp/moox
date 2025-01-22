<?php

namespace Moox\Training\Filters;

use Override;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class DateRangeFilter extends Filter
{
    #[Override]
    public static function make(?string $name = null): static
    {
        return parent::make($name)
            ->form([
                DatePicker::make($name . '_from'),
                DatePicker::make($name . '_until'),
            ])
            ->query(function (Builder $query, array $data) use (&$name): Builder {
                return $query
                    ->when(
                        $data[$name . '_from'],
                        fn (Builder $query, $date): Builder => $query->whereDate($name, '>=', $date),
                    )
                    ->when(
                        $data[$name . '_until'],
                        fn (Builder $query, $date): Builder => $query->whereDate($name, '<=', $date),
                    );
            });
    }
}
