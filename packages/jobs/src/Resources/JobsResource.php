<?php

namespace Moox\Jobs\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Jobs\Models\JobManager;
use Moox\Jobs\Resources\JobsResource\Pages\ListJobs;
use Moox\Jobs\Resources\JobsResource\Widgets\JobStatsOverview;
use Moox\Progress\Forms\Components\ProgressColumn;
use Override;

class JobsResource extends Resource
{
    use HasResourceTabs;

    protected static ?string $model = JobManager::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    #[Override]
    public static function getNavigationIcon(): string
    {
        if (self::$navigationIcon === null) {
            self::$navigationIcon = config('core.use_google_icons', true) ? 'gmdi-play-arrow' : 'heroicon-o-play';
        }

        return self::$navigationIcon;
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('job_id')
                    ->required()
                    ->maxLength(255)
                    ->label(__('jobs::translations.id')),
                TextInput::make('name')
                    ->maxLength(255)
                    ->label(__('jobs::translations.name')),
                TextInput::make('queue')
                    ->maxLength(255),
                DateTimePicker::make('started_at')
                    ->label(__('jobs::translations.started_at')),
                DateTimePicker::make('finished_at'),
                Toggle::make('failed')
                    ->required()
                    ->label(__('jobs::translations.failed_at')),
                TextInput::make('attempt')
                    ->required(),
                Textarea::make('exception_message')
                    ->maxLength(65535)
                    ->label(__('jobs::translations.exception_message')),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->deferLoading()
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->label(__('jobs::translations.status'))
                    ->formatStateUsing(fn (string $state): string => __('jobs::translations.'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        'running' => 'primary',
                        'succeeded' => 'success',
                        'failed' => 'danger',
                        default => 'secondary',
                    }),
                TextColumn::make('name')
                    ->label(__('jobs::translations.name'))
                    ->sortable(),
                TextColumn::make('queue')
                    ->label(__('jobs::translations.queue'))
                    ->sortable(),
                ProgressColumn::make('progress')
                    ->label(__('jobs::translations.progress'))
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->failed) {
                            return __('jobs::translations.failed');
                        }

                        return $state.'%';
                    })
                    ->color(function ($record) {
                        if ($record->failed) {
                            return 'rgb(239, 68, 68)';
                        }

                        $progress = $record->progress ?? 0;
                        $gray = [156, 163, 175];
                        $green = [34, 197, 94];
                        $f = $progress / 100;
                        $r = round($gray[0] + ($green[0] - $gray[0]) * $f);
                        $g = round($gray[1] + ($green[1] - $gray[1]) * $f);
                        $b = round($gray[2] + ($green[2] - $gray[2]) * $f);

                        return "rgb({$r}, {$g}, {$b})";
                    }),
                TextColumn::make('started_at')
                    ->label(__('jobs::translations.started_at'))
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return ['index' => ListJobs::route('/')];
    }

    #[Override]
    public static function getWidgets(): array
    {
        return [
            JobStatsOverview::class,
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return __('jobs::translations.jobs.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('jobs::translations.jobs.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return __('jobs::translations.jobs.navigation_label');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return __('jobs::translations.breadcrumb');
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return __('jobs::translations.navigation_group');
    }
}
