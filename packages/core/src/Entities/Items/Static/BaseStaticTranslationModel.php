<?php

declare(strict_types=1);

namespace Moox\Core\Entities\Items\Static;

use Illuminate\Database\Eloquent\Model;

abstract class BaseStaticTranslationModel extends Model
{
    public $timestamps = true;

    /**
     * @return list<string>
     */
    protected function getBaseFillable(): array
    {
        return [
            'locale',
        ];
    }

    /**
     * @return list<string>
     */
    protected function getCustomFillable(): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    public function getFillable(): array
    {
        return array_merge($this->getBaseFillable(), $this->getCustomFillable());
    }

    /**
     * @return array<string, string>
     */
    protected function getCustomCasts(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), $this->getCustomCasts());
    }
}
