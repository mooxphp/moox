<?php

declare(strict_types=1);

namespace Moox\Tree\Actions\Tree;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class DeleteTreeNodeAction
{
    public function handle(Model $record): void
    {
        DB::transaction(fn (): ?bool => $record->delete());
    }
}
