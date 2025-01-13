<?php

declare(strict_types=1);

namespace App\Locale\Presets;

use Moox\Builder\Blocks\Fields\Date;
use Moox\Builder\Blocks\Fields\Text;
use Moox\Builder\Blocks\Fields\Boolean;
use Moox\Builder\Blocks\Singles\Simple;
use Moox\Builder\Presets\AbstractPreset;

class StaticTimezonePreset extends AbstractPreset
{
    protected function initializePreset(): void
    {
        $this->blocks = [
            new Simple,
            new Text(
                name: 'name',
                label: 'name',
                description: 'Name of the language',
            ),
            new Text(
                name: 'offset_standard',
                label: 'Offset Standard',
                length: 6,
                description: 'Name of the language',
            ),
            new Boolean(
                name: 'dst',
                label: 'DST',
                default: false,
                description: 'Daylight Saving Time indicator',
            ),
            new Date(
                name: 'dst_start',
                label: 'DST Start',
                nullable: true,
                description: 'Start date of Daylight Saving Time',
            ),
            new Date(
                name: 'dst_end',
                label: 'DST End',
                nullable: true,
                description: 'End date of Daylight Saving Time',
            ),
        ];
    }
}
