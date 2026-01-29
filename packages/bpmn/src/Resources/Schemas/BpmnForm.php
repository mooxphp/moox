<?php

namespace Moox\Bpmn\Resources\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Fieldset;
use Moox\Bpmn\Forms\Components\BpmnViewer;
use Moox\Media\Forms\Components\MediaPicker;
use Filament\Forms\Components\MarkdownEditor;


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

                        MediaPicker::make('media')
                            ->label('BPMN Media')
                            ->collection('media')
                            ->acceptedFileTypes(['application/xml', '.bpmn'])
                            ->reactive()
                            ->dehydrated(false)
                            ->filterByCollectionId('bpmn'),
                        

                        
                        BpmnViewer::make('bpmn')
                            ->label(__('BPMN Process'))
                            ->mediaIntegration('moox')
                            ->mode('full')
                            ->columnSpanFull()
                            ->required(),
                        
                        
                        

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
