<?php

declare(strict_types=1);

namespace Moox\Static\Filament\Tables\Columns;

use Moox\Core\Entities\Items\Static\Tables\Columns\StaticTranslationColumn as CoreStaticTranslationColumn;

/** @deprecated Use {@see CoreStaticTranslationColumn} */
class_alias(CoreStaticTranslationColumn::class, StaticTranslationColumn::class);
