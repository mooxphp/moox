<?php

declare(strict_types=1);

namespace Heco\FilamentTreeIndex\Actions\Tree;

use Heco\FilamentTreeIndex\Config\TreeIndexConfiguration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class DeleteTreeNodeAction
{
    public function __construct(private readonly TreeIndexConfiguration $configuration) {}

    public function handle(Model $record): void
    {
        DB::transaction(fn (): ?bool => $record->delete());
    }
}
