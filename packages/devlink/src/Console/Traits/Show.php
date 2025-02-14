<?php

namespace Moox\Devlink\Console\Traits;

trait Show
{
    private function show(): void
    {
        $this->showReport();
    }

    private function getTableData(): array
    {
        $packages = $this->getPackagesConfig();
        $tableData = [];

        foreach ($packages as $name => $package) {
            $tableData[] = [
                $name,
                $package['type'],
                $package['active'] ? 'Yes' : 'No',
                $package['linked'] ? 'Yes' : 'No',
                $package['deploy'] ? 'Yes' : 'No',
                $package['path'],
            ];
        }

        return $tableData;
    }

    private function showReport(): void
    {
        // TODO: Shows config, needs to show real status, too

        if (! $this->readConfig()) {
            $this->error('Configuration file not found!');
        }

        if (! $this->checkPackagesPath()) {
            $this->error('Packages path not found!');
        }

        if (! $this->checkMooxBasePath()) {
            $this->error('Moox base path not found!');
        }

        if (! $this->checkMooxproBasePath()) {
            $this->error('Mooxpro base path not found!');
        }

        if (! $this->checkComposerJson()) {
            $this->error('composer.json not found!');
        }

        if (! $this->checkComposerOriginal()) {
            // means Devlink is not active or already deployed?
            $this->error('composer.json-original not found!');
        }

        $this->readConfig();
        $tableData = $this->getTableData();
        $this->table(['Package', 'Type', 'Active', 'Linked', 'Deploy', 'Path'], $tableData);
    }
}
