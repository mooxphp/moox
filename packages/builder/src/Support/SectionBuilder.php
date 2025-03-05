<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Illuminate\Support\Facades\Log;

class SectionBuilder
{
    private bool $isMeta = false;

    private array $fields = [];

    private array $sections;

    private bool $hideHeader = false;

    public function __construct(
        array &$sections,
        private readonly string $name,
        private int $order = 0
    ) {
        $this->sections = &$sections;

        if (isset($this->sections[$this->name])) {
            $this->isMeta = $this->sections[$this->name]['isMeta'] ?? false;
            $this->fields = $this->sections[$this->name]['fields'] ?? [];
            $this->order = $this->sections[$this->name]['order'] ?? 0;
        }
    }

    public function asMeta(): self
    {
        $this->isMeta = true;

        return $this;
    }

    public function hideHeader(): self
    {
        $this->hideHeader = true;

        return $this;
    }

    public function withFields(array $fields): self
    {
        Log::info('Adding fields to section', [
            'name' => $this->name,
            'fields' => $fields,
        ]);

        $this->fields = $fields;

        $this->sections[$this->name] = [
            'name' => $this->name,
            'isMeta' => $this->isMeta,
            'fields' => $this->fields,
            'order' => $this->order,
            'hideHeader' => $this->hideHeader,
        ];

        Log::info('Section structure', ['section' => $this->sections[$this->name]]);

        return $this;
    }
}
