<?php

declare(strict_types=1);

namespace Moox\Migrate\Support;

use Carbon\CarbonImmutable;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

final class PendingMigrationSchema
{
    public static function callout(): Callout
    {
        return Callout::make(function (): string {
            $count = app(MigrationInventory::class)->pendingCount();

            return trans_choice('migrate::migrate.pending_heading', $count, ['count' => $count]);
        })
            ->warning()
            ->color(null)
            ->icon(Heroicon::OutlinedClock)
            ->description(__('migrate::migrate.pending_hint'))
            ->hidden(fn (): bool => app(MigrationInventory::class)->pendingCount() === 0)
            ->footer(fn (): array => [
                Group::make(self::items())
                    ->columns(1)
                    ->dense()
                    ->grow(),
            ])
            ->columnSpanFull();
    }

    public static function list(): Section
    {
        return Section::make()
            ->compact()
            ->divided()
            ->schema(self::items())
            ->columnSpanFull();
    }

    /**
     * @return list<Callout>
     */
    public static function items(): array
    {
        return array_map(
            fn (PendingMigration $item): Callout => self::item($item),
            app(MigrationInventory::class)->pending(),
        );
    }

    private static function item(PendingMigration $item): Callout
    {
        $footer = [
            Text::make($item->name)
                ->fontFamily(FontFamily::Mono)
                ->size(TextSize::ExtraSmall)
                ->color('gray')
                ->copyable()
                ->tooltip(__('migrate::migrate.copy_filename'))
                ->grow(),
            Text::make($item->origin)
                ->badge()
                ->color('warning')
                ->size(Size::Small)
                ->grow(false),
        ];

        if ($item->datedAt() instanceof CarbonImmutable) {
            $footer[] = Text::make($item->datedAt()->translatedFormat('d.m.Y'))
                ->badge()
                ->color('warning')
                ->size(Size::Small)
                ->grow(false);
        }

        return Callout::make($item->label())
            ->warning()
            ->icon(Heroicon::OutlinedDocumentText)
            ->footer($footer)
            ->key('pending-'.$item->name)
            ->grow();
    }
}
