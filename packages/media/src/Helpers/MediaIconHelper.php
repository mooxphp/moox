<?php

declare(strict_types=1);

namespace Moox\Media\Helpers;

class MediaIconHelper
{
    /**
     * Get the icon path for a given MIME type
     */
    public static function getIconPath(string $mimeType): string
    {
        return self::getIconMap()[$mimeType] ?? '/vendor/file-icons/unknown.svg';
    }

    /**
     * Get the icon label for a given MIME type
     */
    public static function getIconLabel(string $mimeType): ?string
    {
        return self::getIconLabels()[$mimeType] ?? null;
    }

    /**
     * Get icon path and label for a given MIME type
     */
    public static function getIconData(string $mimeType): array
    {
        return [
            'icon' => self::getIconPath($mimeType),
            'label' => self::getIconLabel($mimeType) ?? 'File',
        ];
    }

    /**
     * Get the complete icon map with paths
     */
    public static function getIconMap(): array
    {
        return [
            'application/pdf' => '/vendor/file-icons/pdf.svg',
            'application/msword' => '/vendor/file-icons/doc.svg',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '/vendor/file-icons/doc.svg',
            'application/vnd.ms-excel' => '/vendor/file-icons/xls.svg',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '/vendor/file-icons/xls.svg',
            'application/vnd.ms-powerpoint' => '/vendor/file-icons/ppt.svg',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '/vendor/file-icons/ppt.svg',
            'video/mp4' => '/vendor/file-icons/mp4.svg',
            'video/webm' => '/vendor/file-icons/mp4.svg',
            'video/quicktime' => '/vendor/file-icons/mp4.svg',
            'audio/mpeg' => '/vendor/file-icons/mp3.svg',
            'audio/wav' => '/vendor/file-icons/mp3.svg',
            'audio/ogg' => '/vendor/file-icons/mp3.svg',
            'audio/mp3' => '/vendor/file-icons/mp3.svg',
            'image/svg+xml' => '/vendor/file-icons/svg.svg',
            'image/jpeg' => '/vendor/file-icons/jpg.svg',
            'image/png' => '/vendor/file-icons/png.svg',
            'image/vnd.adobe.photoshop' => '/vendor/file-icons/psd.svg',
            'application/illustrator' => '/vendor/file-icons/ai.svg',
            'application/eps' => '/vendor/file-icons/eps.svg',
            'application/postscript' => '/vendor/file-icons/eps.svg',
            'application/acad' => '/vendor/file-icons/cad.svg',
            'application/dwg' => '/vendor/file-icons/cad.svg',
            'application/dxf' => '/vendor/file-icons/cad.svg',
            'application/x-dwg' => '/vendor/file-icons/dwg.svg',
            'message/rfc822' => '/vendor/file-icons/eml.svg',
            'application/vnd.ms-outlook' => '/vendor/file-icons/oft.svg',
            'application/onenote' => '/vendor/file-icons/one.svg',
            'application/zip' => '/vendor/file-icons/zip.svg',
            'application/x-zip-compressed' => '/vendor/file-icons/zip.svg',
            'application/x-rar-compressed' => '/vendor/file-icons/zip.svg',
            'application/x-acrobat' => '/vendor/file-icons/acrobat.svg',
            'application/after-effects' => '/vendor/file-icons/ae.svg',
            'video/x-msvideo' => '/vendor/file-icons/avi.svg',
            'text/css' => '/vendor/file-icons/css.svg',
            'text/csv' => '/vendor/file-icons/csv.svg',
            'application/x-folder' => '/vendor/file-icons/folder.svg',
            'text/html' => '/vendor/file-icons/html.svg',
            'application/x-indesign' => '/vendor/file-icons/indd.svg',
            'application/javascript' => '/vendor/file-icons/js.svg',
            'application/x-onedrive' => '/vendor/file-icons/onedrive.svg',
            'application/x-outlook' => '/vendor/file-icons/outlook.svg',
            'application/x-ppj' => '/vendor/file-icons/ppj.svg',
            'text/plain' => '/vendor/file-icons/txt.svg',
            'application/xml' => '/vendor/file-icons/xml.svg',
            'model/step' => '/vendor/file-icons/unknown.svg',
            'model/stp' => '/vendor/file-icons/unknown.svg',
            'model/gltf+json' => '/vendor/file-icons/unknown.svg',
            'model/gltf-binary' => '/vendor/file-icons/unknown.svg',
        ];
    }

    /**
     * Get the icon labels map
     */
    public static function getIconLabels(): array
    {
        return [
            'application/pdf' => 'PDF',
            'application/msword' => 'DOC',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
            'application/vnd.ms-excel' => 'XLS',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XLSX',
            'application/vnd.ms-powerpoint' => 'PPT',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PPTX',
            'video/mp4' => 'MP4',
            'video/webm' => 'WEBM',
            'video/quicktime' => 'MOV',
            'audio/mpeg' => 'MP3',
            'audio/wav' => 'WAV',
            'audio/ogg' => 'OGG',
            'audio/mp3' => 'MP3',
            'image/svg+xml' => 'SVG',
            'application/zip' => 'ZIP',
            'application/x-zip-compressed' => 'ZIP',
            'text/plain' => 'TXT',
            'text/csv' => 'CSV',
            'model/step' => 'STEP',
            'model/stp' => 'STP',
            'model/gltf+json' => 'GLTF',
            'model/gltf-binary' => 'GLB',
        ];
    }

    /**
     * Get the complete icon map with labels (for Blade views)
     */
    public static function getIconMapWithLabels(): array
    {
        $map = [];
        $iconMap = self::getIconMap();
        $labels = self::getIconLabels();

        foreach ($iconMap as $mimeType => $iconPath) {
            $map[$mimeType] = [
                'icon' => $iconPath,
                'label' => $labels[$mimeType] ?? 'File',
            ];
        }

        return $map;
    }
}
