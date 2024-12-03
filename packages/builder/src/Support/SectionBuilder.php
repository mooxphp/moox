<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Illuminate\Support\Facades\Log;

class SectionBuilder
{
    private bool $isMeta = false;

    private array $fields = [];

    private array $sections;

    private string $name;

    private int $order;

    private bool $hideHeader = false;

    public function __construct(
        array &$sections,
        string $name,
        int $order = 0
    ) {
        $this->sections = &$sections;
        $this->name = $name;
        $this->order = $order;

        if (isset($this->sections[$name])) {
            $this->isMeta = $this->sections[$name]['isMeta'] ?? false;
            $this->fields = $this->sections[$name]['fields'] ?? [];
            $this->order = $this->sections[$name]['order'] ?? 0;
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
