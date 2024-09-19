<?php

namespace Moox\Expiry\Actions;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;

class SetDateAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'setDateAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('core::expiry.set_date'))
            ->icon('gmdi-event-available')
            ->action(function ($record, array $data) {
                $postId = $record->item_id;

                if ($postId) {
                    $metaKey = 'gultig_bis';
                    $newValue = Carbon::createFromFormat('Y-m-d', $data['expired_at'])->format('Ymd');

                    DB::table(config('press.wordpress_prefix').'postmeta')
                        ->where('post_id', $postId)
                        ->where('meta_key', $metaKey)
                        ->update(['meta_value' => $newValue]);

                    Notification::make()
                        ->title(__('core::expiry.date_updated'))
                        ->success()
                        ->send();
                }
            })
            ->form(function ($record) {
                return [
                    Grid::make(2)
                        ->schema([

                            TextInput::make('title')
                                ->label(function ($record) {
                                    if ($record->expiry_job === 'Wiki Artikel') {
                                        return 'Titel des Artikels';
                                    } elseif ($record->category === 'Aufgabe') {
                                        return 'Titel der Aufgabe';
                                    }

                                    return 'Titel';
                                })
                                ->default($record->title)
                                ->disabled(),

                            TextInput::make('category')
                                ->label(__('core::core.category'))
                                ->default($record->category)
                                ->disabled(),

                            TextInput::make('cycle')
                                ->label(__('core::expiry.cycle'))
                                ->default($record->cycle)
                                ->disabled(),

                            DatePicker::make('previous_expired_at')
                                ->label(__('core::expiry.previous_expiry_date'))
                                ->default(($record->expired_at)->format('Y-m-d'))
                                ->disabled(),

                        ]),

                    DatePicker::make('expired_at')
                        ->label('Neues Ablaufdatum setzen basierend auf dem Turnus')
                        ->required()
                        ->rule('after:now')
                        ->default(function ($record) {
                            $now = Carbon::now();

                            $turnusDays = config('expiry.turnus_options.'.$record->cycle, 0);

                            return $now->addDays($turnusDays);
                        })
                        ->columnSpan('full')
                        ->helperText(config('expiry.helper_text')),

                ];
            })
            ->modalHeading(__('core::expiry.set_date'))
            ->modalSubmitActionLabel(__('core::expiry.save'))
            ->color('primary')
            ->visible(function ($record) {
                if (! config('expiry.set_date_action')) {
                    return false;
                }

                if ($record->expiry_job === 'Wiki Artikel') {
                    return true;
                }

                if ($record->expiry_job === 'Wiki Dokumente' && $record->category !== 'Download') {
                    return true;
                }

                return false;
            });
    }
}
