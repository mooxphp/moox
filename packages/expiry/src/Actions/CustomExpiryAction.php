<?php

namespace Moox\Expiry\Actions;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;

class CustomExpiryAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'setDateAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(config('expiry.expiry_action_name', 'Set Expiry Date'))
            ->icon('gmdi-event-available')
            ->action(function ($record, array $data) {
                $postId = $record->item_id;

                if ($postId) {
                    $newValue = Carbon::createFromFormat('Y-m-d', $data['expired_at'])->format('Ymd');

                    DB::table('expiries')
                        ->where('id', $record->id)
                        ->update(['expired_at' => $newValue]);

                    Notification::make()
                        ->title(__('core::expiry.date_updated'))
                        ->success()
                        ->send();
                }
            })
            ->form(function ($record) {

                $cycleOptions = collect(config('expiry.cycle_options'))->mapWithKeys(function ($value, $key) {
                    return [__('core::expiry.'.$key) => $value];
                });

                return [
                    Grid::make(2)
                        ->schema([
                            TextInput::make('title')
                                ->label(__('core::core.title'))
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
                        ->label(__('core::expiry.set_new_expiry_date'))
                        ->required()
                        ->rule('after:now')
                        ->validationMessages([
                            'after' => config('expiry.after_now'),
                        ])
                        ->default(function ($record) use ($cycleOptions) {
                            $now = Carbon::now();
                            $cycleDays = $cycleOptions[$record->cycle] ?? 0;

                            return $now->addDays($cycleDays);
                        })
                        ->columnSpan('full')
                        ->helperText(config('expiry.helper_text_datetime')),
                ];
            })
            ->modalHeading(__('core::expiry.set_date'))
            ->modalSubmitActionLabel(__('core::expiry.save'))
            ->color('primary')
            ->visible(function ($record) {
                return config('expiry.expiry_action_enable', true);
            });
    }
}
