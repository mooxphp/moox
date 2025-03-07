<?php

declare(strict_types=1);

namespace Moox\Connect\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Moox\Connect\Models\ApiLog;

final class ApiLogResource extends Resource
{
    protected static ?string $model = ApiLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('api_connection_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('endpoint_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('trigger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_code')
                    ->sortable()
                    ->color(fn (string $state): string => match (true) {
                        str_starts_with($state, '2') => 'success',
                        str_starts_with($state, '3') => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('error_message')
                    ->wrap()
                    ->color('danger')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('trigger')
                    ->options([
                        'CRON' => 'CRON',
                        'USER' => 'USER',
                        'WEBHOOK' => 'WEBHOOK',
                        'SYSTEM' => 'SYSTEM',
                    ]),
                Tables\Filters\SelectFilter::make('status_code')
                    ->options([
                        '2xx' => 'Success (2xx)',
                        '3xx' => 'Redirect (3xx)',
                        '4xx' => 'Client Error (4xx)',
                        '5xx' => 'Server Error (5xx)',
                    ])
                    ->query(function ($query, array $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->where('status_code', 'LIKE', substr($data['value'], 0, 1).'%');
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->form([
                        Forms\Components\KeyValue::make('request_data')
                            ->readonly(),
                        Forms\Components\KeyValue::make('response_data')
                            ->readonly(),
                        Forms\Components\TextInput::make('error_message')
                            ->readonly(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ApiLogResource\Pages\ListApiLogs::route('/'),
            'view' => ApiLogResource\Pages\ViewApiLog::route('/{record}'),
        ];
    }
}
