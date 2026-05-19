<?php

declare(strict_types=1);

namespace Moox\Tree\Actions\Tree;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Moox\Tree\Config\TreeIndexConfiguration;

final class DeleteTreeNodeAction
{
    public function __construct(private readonly TreeIndexConfiguration $configuration) {}

    public function handle(Model $record): void
    {
        DB::transaction(fn (): ?bool => $record->delete());
    }
}
