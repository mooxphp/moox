<?php

declare(strict_types=1);

namespace Moox\Support\Models;

trait MergesColumns
{
    /**
     * @param  list<string>  $fillable
     * @param  array<string, string>  $casts
     * @param  array<string, mixed>  $attributes
     */
    protected function mergeColumns(array $fillable, array $casts = [], array $attributes = []): void
    {
        $this->mergeFillable($fillable);

        if ($casts !== []) {
            $this->mergeCasts($casts);
        }

        foreach ($attributes as $key => $value) {
            if (! array_key_exists($key, $this->attributes)) {
                $this->attributes[$key] = $value;
            }
        }
    }
}
