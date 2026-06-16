<?php

declare(strict_types=1);

namespace Moox\Tree\Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesTreeNodesTable
{
    protected function createTreeNodesTable(): void
    {
        Schema::dropIfExists('tree_nodes');

        Schema::create('tree_nodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('tree_nodes')->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
}
