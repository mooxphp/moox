<?php

declare(strict_types=1);

namespace Moox\Core\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait AuthorInModel
{
    public function author(): ?BelongsTo
    {
        $authorModel = config('builder.author_model');
        if ($authorModel && class_exists($authorModel)) {
            return $this->belongsTo($authorModel, 'author_id');
        }

        return null;
    }
}
