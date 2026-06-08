<?php

declare(strict_types=1);

namespace Moox\Tree\Support;

use Illuminate\Database\Eloquent\Model;
use Moox\Tree\Config\TreeIndexConfiguration;

final class TreeNodeLabelResolver
{
    public static function resolve(Model $record, TreeIndexConfiguration $configuration): string
    {
        $labelColumn = $configuration->getLabelColumn();
        $value = $record->getAttribute($labelColumn);

        if (filled($value)) {
            return (string) $value;
        }

        $fallbackColumn = $configuration->getLabelFallbackColumn();

        if ($fallbackColumn !== null) {
            $fallback = data_get($record, $fallbackColumn);

            if (filled($fallback)) {
                return (string) $fallback;
            }
        }

        return '';
    }
}
