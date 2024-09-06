<?php

namespace Moox\Sync\Transformer;

use Illuminate\Database\Eloquent\Builder;

abstract class AbstractTransformer
{
    protected $query;

    protected $model;

    public function __construct(Builder $query)
    {
        $this->query = $query;
        $this->model = $query->getModel();
    }

    public function transform(): array
    {
        $data = $this->model->getAttributes();

        return $this->transformCustomFields($data);
    }

    abstract protected function transformCustomFields(array $data): array;

    protected function getMetaFields(): array
    {
        return [];
    }

    protected function getMetaValue(string $key)
    {
        if (method_exists($this->model, 'getMeta')) {
            return $this->model->getMeta($key);
        }

        return null;
    }

    public function getDelay(): int
    {
        return 0; // seconds
    }
}
