<?php

declare(strict_types=1);

namespace Moox\MailTesting\Resources\MailTestingMessageResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\Width;
use Moox\MailTesting\Enums\Engine;
use Moox\MailTesting\Enums\RunStatus;
use Moox\MailTesting\Models\MailTestingMessage;
use Moox\MailTesting\Models\MailTestingRun;
use Moox\MailTesting\Resources\MailTestingMessageResource;
use Moox\MailTesting\Support\DurationFormat;
use Moox\MailTesting\Support\HtmlLength;
use Override;

class ViewMailTestingMessage extends ViewRecord
{
    public static string $resource = MailTestingMessageResource::class;

    /**
     * @var array{php: ?MailTestingMessage, node: ?MailTestingMessage}|null
     */
    private ?array $comparisonMessages = null;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    /**
     * @return array<Action|DeleteAction>
     */
    #[Override]
    protected function getHeaderActions(): array
    {
        $actions = [];

        foreach ([Engine::Php, Engine::Node] as $engine) {
            $message = $this->messageForEngine($engine);

            if (! $message instanceof MailTestingMessage) {
                continue;
            }

            $html = $message->resolvedHtml();

            if (! is_string($html) || $html === '') {
                continue;
            }

            $actions[] = Action::make('preview'.$engine->value)
                ->label($engine->label())
                ->icon('heroicon-o-eye')
                ->url(MailTestingMessageResource::previewUrl($message))
                ->openUrlInNewTab();
        }

        $actions[] = DeleteAction::make();

        return $actions;
    }

    #[Override]
    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Fieldset::make(__('mail-testing::translations.fieldset_mail'))
                            ->columns(['default' => 2, 'md' => 3, 'xl' => 6])
                            ->schema([
                                TextEntry::make('mail_testing_run_id')
                                    ->label(__('mail-testing::translations.run'))
                                    ->icon('heroicon-m-queue-list')
                                    ->numeric(),
                                TextEntry::make('position')
                                    ->label(__('mail-testing::translations.position'))
                                    ->icon('heroicon-m-hashtag')
                                    ->numeric(),
                                TextEntry::make('run.engine')
                                    ->label(__('mail-testing::translations.engine'))
                                    ->icon('heroicon-m-cpu-chip')
                                    ->badge()
                                    ->formatStateUsing(fn (mixed $state): string => $this->engineLabel($state))
                                    ->color(fn (mixed $state): string => $this->engineColor($state)),
                                TextEntry::make('html_hash')
                                    ->label(__('mail-testing::translations.hash'))
                                    ->icon('heroicon-m-finger-print')
                                    ->fontFamily(FontFamily::Mono)
                                    ->limit(12)
                                    ->copyable()
                                    ->tooltip(fn (mixed $state): ?string => is_string($state) ? $state : null),
                                TextEntry::make('byte_length')
                                    ->label(__('mail-testing::translations.bytes'))
                                    ->icon('heroicon-m-document')
                                    ->tooltip(__('mail-testing::translations.bytes_help'))
                                    ->hintIcon('heroicon-m-question-mark-circle')
                                    ->hintIconTooltip(__('mail-testing::translations.bytes_help'))
                                    ->formatStateUsing(fn (mixed $state): string => HtmlLength::formatBytes(is_numeric($state) ? (int) $state : 0)),
                                TextEntry::make('character_length')
                                    ->label(__('mail-testing::translations.characters'))
                                    ->icon('heroicon-m-language')
                                    ->tooltip(__('mail-testing::translations.characters_help'))
                                    ->hintIcon('heroicon-m-question-mark-circle')
                                    ->hintIconTooltip(__('mail-testing::translations.characters_help'))
                                    ->state(fn (): string => $this->characterLengthLabel()),
                            ]),
                        Fieldset::make(__('mail-testing::translations.fieldset_timings'))
                            ->columns(3)
                            ->schema([
                                TextEntry::make('compose_ms')
                                    ->label(__('mail-testing::translations.compose_ms'))
                                    ->icon('heroicon-m-pencil')
                                    ->tooltip(__('mail-testing::translations.compose_ms_help'))
                                    ->hintIcon('heroicon-m-question-mark-circle')
                                    ->hintIconTooltip(__('mail-testing::translations.compose_ms_help'))
                                    ->formatStateUsing(fn (mixed $state): string => DurationFormat::milliseconds(is_numeric($state) ? (int) $state : 0)),
                                TextEntry::make('convert_ms')
                                    ->label(__('mail-testing::translations.convert_ms'))
                                    ->icon('heroicon-m-arrow-path')
                                    ->tooltip(__('mail-testing::translations.convert_ms_help'))
                                    ->hintIcon('heroicon-m-question-mark-circle')
                                    ->hintIconTooltip(__('mail-testing::translations.convert_ms_help'))
                                    ->formatStateUsing(fn (mixed $state): string => DurationFormat::milliseconds(is_numeric($state) ? (int) $state : 0)),
                                TextEntry::make('persist_ms')
                                    ->label(__('mail-testing::translations.persist_ms'))
                                    ->icon('heroicon-m-circle-stack')
                                    ->tooltip(__('mail-testing::translations.persist_ms_help'))
                                    ->hintIcon('heroicon-m-question-mark-circle')
                                    ->hintIconTooltip(__('mail-testing::translations.persist_ms_help'))
                                    ->formatStateUsing(fn (mixed $state): string => DurationFormat::milliseconds(is_numeric($state) ? (int) $state : 0)),
                            ]),
                    ])
                    ->columnSpanFull(),
                Section::make(fn (): string => $this->hasPeerPreview()
                    ? __('mail-testing::translations.comparison')
                    : __('mail-testing::translations.preview'))
                    ->schema([
                        View::make('mail-testing::filament.partials.mail-preview-comparison')
                            ->viewData(fn (): array => [
                                'previews' => $this->comparisonPreviewData(),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private function engineLabel(mixed $state): string
    {
        if ($state instanceof Engine) {
            return $state->label();
        }

        if (! is_string($state) || $state === '') {
            return '';
        }

        $engine = Engine::tryFrom($state);

        return $engine instanceof Engine ? $engine->label() : $state;
    }

    private function engineColor(mixed $state): string
    {
        if ($state === 'PHP' || $state === Engine::Php || $state === Engine::Php->value) {
            return 'info';
        }

        if ($state === 'Node' || $state === Engine::Node || $state === Engine::Node->value) {
            return 'warning';
        }

        return 'gray';
    }

    /**
     * @return list<array{engine: string, previewUrl: string|null, byteLength: int, characterLength: int, byteLabel: string, characterLabel: string}>
     */
    private function comparisonPreviewData(): array
    {
        $previews = [];

        foreach ([Engine::Php, Engine::Node] as $engine) {
            $message = $this->messageForEngine($engine);

            if (! $message instanceof MailTestingMessage) {
                continue;
            }

            $html = $message->resolvedHtml();
            $length = HtmlLength::of(is_string($html) ? $html : null);

            $previews[] = [
                'engine' => $engine->label(),
                'previewUrl' => is_string($html) && $html !== ''
                    ? MailTestingMessageResource::previewUrl($message)
                    : null,
                'byteLength' => $length['bytes'],
                'characterLength' => $length['characters'],
                'byteLabel' => HtmlLength::formatBytes($length['bytes']),
                'characterLabel' => HtmlLength::formatCharacters($length['characters']),
            ];
        }

        return $previews;
    }

    private function characterLengthLabel(): string
    {
        $record = $this->getRecord();

        if (! $record instanceof MailTestingMessage) {
            return HtmlLength::formatCharacters(0);
        }

        return HtmlLength::formatCharacters(HtmlLength::of($record->resolvedHtml())['characters']);
    }

    private function hasPeerPreview(): bool
    {
        return $this->messageForEngine(Engine::Php) instanceof MailTestingMessage
            && $this->messageForEngine(Engine::Node) instanceof MailTestingMessage;
    }

    private function messageForEngine(Engine $engine): ?MailTestingMessage
    {
        return $this->comparisonMessages()[$engine === Engine::Php ? 'php' : 'node'];
    }

    /**
     * @return array{php: ?MailTestingMessage, node: ?MailTestingMessage}
     */
    private function comparisonMessages(): array
    {
        if ($this->comparisonMessages !== null) {
            return $this->comparisonMessages;
        }

        $php = null;
        $node = null;
        $record = $this->getRecord();

        if ($record instanceof MailTestingMessage) {
            $record->loadMissing('run');
            $this->assignMessage($record, $php, $node);
            $this->assignMessage($this->peerMessage($record), $php, $node);
        }

        return $this->comparisonMessages = [
            'php' => $php,
            'node' => $node,
        ];
    }

    private function assignMessage(?MailTestingMessage $message, ?MailTestingMessage &$php, ?MailTestingMessage &$node): void
    {
        if (! $message instanceof MailTestingMessage) {
            return;
        }

        $message->loadMissing('run');
        $engine = $message->run?->engine;

        if ($engine === Engine::Php) {
            $php = $message;
        }

        if ($engine === Engine::Node) {
            $node = $message;
        }
    }

    private function peerMessage(MailTestingMessage $record): ?MailTestingMessage
    {
        $run = $record->run;

        if (! $run instanceof MailTestingRun) {
            return null;
        }

        $otherEngine = $run->engine === Engine::Php ? Engine::Node : Engine::Php;

        $peerRun = MailTestingRun::query()
            ->where('engine', $otherEngine)
            ->where('status', RunStatus::Completed)
            ->where('options_fingerprint', $run->options_fingerprint)
            ->where('count', $run->count)
            ->latest('id')
            ->first();

        if (! $peerRun instanceof MailTestingRun) {
            return null;
        }

        $peer = MailTestingMessage::query()
            ->where('mail_testing_run_id', $peerRun->getKey())
            ->where('position', $record->position)
            ->first();

        return $peer instanceof MailTestingMessage ? $peer : null;
    }
}
