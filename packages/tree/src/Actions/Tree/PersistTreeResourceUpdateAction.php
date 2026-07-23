<?php

declare(strict_types=1);

namespace Moox\Tree\Actions\Tree;

use Illuminate\Database\Eloquent\Model;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Support\TreeResourcePageExecutor;

final class PersistTreeResourceUpdateAction
{
    public function __construct(
        private readonly TreeResourcePageExecutor $executor,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(
        TreeIndexConfiguration $configuration,
        Model $record,
        array $data,
        object $host,
    ): Model {
        $editPageClass = $configuration->getInspectorPageClass();

        if ($editPageClass === null) {
            throw new \LogicException('Tree index configuration has no inspector page class.');
        }

        $page = $this->executor->makePage($editPageClass, $host);
        $this->executor->mountPageRecord($page, $record);

        $data = $this->executor->mutateFormDataBeforeSave($page, $data);

        $record = $this->executor->handleRecordUpdate($page, $record, $data);

        $this->executor->callAfterSave($page);

        return $record;
    }
}
