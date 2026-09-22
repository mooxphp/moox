<?php

declare(strict_types=1);

namespace Moox\MailTesting\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\MailTesting\Enums\Engine;
use Moox\MailTesting\Models\MailTestingMessage;
use Moox\MailTesting\Models\MailTestingRun;
use Moox\MailTesting\Resources\MailTestingMessageResource\Pages\ListMailTestingMessages;
use Moox\MailTesting\Resources\MailTestingMessageResource\Pages\ViewMailTestingMessage;
use Moox\MailTesting\Support\ByteFormat;
use Moox\MailTesting\Support\DurationFormat;
use Override;

class MailTestingMessageResource extends Resource
{
    protected static ?string $model = MailTestingMessage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 81;

    public static function getNavigationGroup(): ?string
    {
        return __('mail-testing::translations.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('mail-testing::translations.message_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mail-testing::translations.message_plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('mail-testing::translations.message_plural');
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mail_testing_run_id')
                    ->label(__('mail-testing::translations.run'))
                    ->sortable(),
                TextColumn::make('run.engine')
                    ->label(__('mail-testing::translations.engine'))
                    ->badge()
                    ->formatStateUsing(function (mixed $state): string {
                        if ($state instanceof Engine) {
                            return $state->label();
                        }

                        $engine = is_string($state) ? Engine::tryFrom($state) : null;

                        return $engine instanceof Engine ? $engine->label() : '';
                    })
                    ->color(function (mixed $state): string {
                        if ($state instanceof Engine) {
                            return $state->color();
                        }

                        $engine = is_string($state) ? Engine::tryFrom($state) : null;

                        return $engine instanceof Engine ? $engine->color() : 'gray';
                    })
                    ->sortable(),
                TextColumn::make('position')
                    ->label(__('mail-testing::translations.position'))
                    ->sortable(),
                TextColumn::make('html_hash')
                    ->label(__('mail-testing::translations.hash'))
                    ->limit(12)
                    ->copyable(),
                TextColumn::make('byte_length')
                    ->label(__('mail-testing::translations.bytes'))
                    ->tooltip(__('mail-testing::translations.bytes_help'))
                    ->extraHeaderAttributes([
                        'title' => __('mail-testing::translations.bytes_help'),
                    ])
                    ->formatStateUsing(fn (mixed $state): string => ByteFormat::bytes(is_numeric($state) ? (int) $state : 0))
                    ->sortable(),
                static::timingColumn('compose_ms'),
                static::timingColumn('convert_ms'),
                static::timingColumn('persist_ms'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('engine')
                    ->label(__('mail-testing::translations.engine'))
                    ->options(Engine::options())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (! is_string($value) || $value === '') {
                            return $query;
                        }

                        return $query->whereRelation('run', 'engine', $value);
                    }),
                SelectFilter::make('mail_testing_run_id')
                    ->label(__('mail-testing::translations.run'))
                    ->options(fn (): array => MailTestingRun::query()
                        ->orderByDesc('id')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (MailTestingRun $run): array => [
                            $run->getKey() => '#'.$run->getKey().' '.$run->engine->label().' ('.$run->count.')',
                        ])
                        ->all()),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label(__('mail-testing::translations.preview'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (MailTestingMessage $record): string => static::previewUrl($record))
                    ->openUrlInNewTab(),
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->chunkSelectedRecords(250),
            ]);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListMailTestingMessages::route('/'),
            'view' => ViewMailTestingMessage::route('/{record}'),
        ];
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('run');
    }

    public static function previewUrl(MailTestingMessage $record): string
    {
        $panel = filament()->getCurrentOrDefaultPanel() ?? filament()->getDefaultPanel();

        return $panel->route('mail-testing-messages.preview', [
            'mailTestingMessage' => $record,
        ]);
    }

    private static function timingColumn(string $column): TextColumn
    {
        $help = __('mail-testing::translations.'.$column.'_help');

        return TextColumn::make($column)
            ->label(__('mail-testing::translations.'.$column))
            ->tooltip($help)
            ->extraHeaderAttributes([
                'title' => $help,
            ])
            ->formatStateUsing(fn (mixed $state): string => DurationFormat::milliseconds(is_numeric($state) ? (int) $state : 0))
            ->sortable();
    }
}
