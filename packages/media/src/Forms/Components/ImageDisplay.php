<?php

namespace Moox\Media\Forms\Components;

use Filament\Forms\Components\Field;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

class ImageDisplay extends Field
{
    protected string $view = 'media::forms.components.image-display';

    protected array $mimeTypeIcons = [
        'application/pdf' => [
            'label' => 'PDF',
            'icon' => '/vendor/file-icons/pdf.svg'
        ],
        'application/msword' => [
            'label' => 'DOC',
            'icon' => '/vendor/file-icons/doc.svg'
        ],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => [
            'label' => 'DOCX',
            'icon' => '/vendor/file-icons/doc.svg'
        ],
        'application/vnd.ms-excel' => [
            'label' => 'XLS',
            'icon' => '/vendor/file-icons/xls.svg'
        ],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => [
            'label' => 'XLSX',
            'icon' => '/vendor/file-icons/xls.svg'
        ],
        'application/vnd.ms-powerpoint' => [
            'label' => 'PPT',
            'icon' => '/vendor/file-icons/ppt.svg'
        ],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => [
            'label' => 'PPTX',
            'icon' => '/vendor/file-icons/ppt.svg'
        ],
        'video/mp4' => [
            'label' => 'MP4',
            'icon' => '/vendor/file-icons/mp4.svg'
        ],
        'video/webm' => [
            'label' => 'WEBM',
            'icon' => '/vendor/file-icons/mp4.svg'
        ],
        'video/quicktime' => [
            'label' => 'MOV',
            'icon' => '/vendor/file-icons/mp4.svg'
        ],
        'audio/mpeg' => [
            'label' => 'MP3',
            'icon' => '/vendor/file-icons/mp3.svg'
        ],
        'audio/wav' => [
            'label' => 'WAV',
            'icon' => '/vendor/file-icons/mp3.svg'
        ],
        'audio/ogg' => [
            'label' => 'OGG',
            'icon' => '/vendor/file-icons/mp3.svg'
        ],
        'image/svg+xml' => [
            'label' => 'SVG',
            'icon' => '/vendor/file-icons/svg.svg'
        ],
        'application/zip' => [
            'label' => 'ZIP',
            'icon' => '/vendor/file-icons/zip.svg'
        ],
        'application/x-zip-compressed' => [
            'label' => 'ZIP',
            'icon' => '/vendor/file-icons/zip.svg'
        ],
        'text/plain' => [
            'label' => 'TXT',
            'icon' => '/vendor/file-icons/txt.svg'
        ],
        'text/csv' => [
            'label' => 'CSV',
            'icon' => '/vendor/file-icons/csv.svg'
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);
    }

    public function getState(): ?string
    {
        $record = $this->getRecord();

        if (!$record) {
            return null;
        }

        if (!$record instanceof SpatieMedia) {
            return null;
        }

        return $record->getUrl();
    }

    public function getMimeTypeIcon(): ?array
    {
        $record = $this->getRecord();

        if (!$record || !$record instanceof SpatieMedia) {
            return null;
        }

        return $this->mimeTypeIcons[$record->mime_type] ?? null;
    }
}
