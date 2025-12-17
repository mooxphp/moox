<?php

namespace Moox\Bpmn\Resources\Bpmns\Schemas;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Moox\Bpmn\Forms\Components\BpmnViewer;

class BpmnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Bpmn Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('BPMN Title')
                            ->maxLength(60)
                            ->required()
                            ->columnSpanFull(),

                        Select::make('bpmn_file')
                            ->label('BPMN File')
                            ->options(fn () => collect(Storage::disk('public')->allFiles('media'))
                                ->filter(fn ($file) => str_ends_with($file, '.bpmn'))
                                ->mapWithKeys(fn ($file) => [
                                    $file => basename($file),
                                ])
                                ->toArray()
                            )
                            ->searchable()
                            ->reactive()
                            ->required(),

                        BpmnViewer::make('bpmn_xml')
                            ->label('BPMN Process')
                            ->fileIntegration() // ✅ tells JS the state is raw XML
                            ->mode('full')
                            ->reactive()
                            ->columnSpanFull(),

                        MarkdownEditor::make('description')
                            ->label('BPMN Description')
                            ->required()
                            ->columnSpanFull(),

                        Fieldset::make('Status')
                            ->columns(2)
                            ->schema([
                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'published' => 'Published',
                                        'archived' => 'Archived',
                                        'on_hold' => 'On Hold',
                                    ])
                                    ->required()
                                    ->columnSpan(1),

                                Toggle::make('is_published')
                                    ->default(true)
                                    ->columnSpan(1),
                            ])
                            ->columnSpanFull(),

                    ]),
            ]);
    }
}
