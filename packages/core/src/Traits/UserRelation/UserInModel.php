<?php

declare(strict_types=1);

namespace Moox\Core\Traits\UserRelation;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait UserInModel
{
    public function user(): ?BelongsTo
    {
        $userModel = config('builder.user_model');
        if ($userModel && class_exists($userModel)) {
            return $this->belongsTo($userModel, 'user_id');
        }

        return null;
    }
}
