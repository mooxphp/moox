<?php

declare(strict_types=1);

namespace Moox\Bpmn;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class BpmnServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('bpmn')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();

        $mooxConfig = $this->getMooxConfig();

        $this->getMooxPackage()
            ->title($mooxConfig['title'])
            ->stability($mooxConfig['stability'])
            ->type($mooxConfig['type'])
            ->category($mooxConfig['category'])
            ->template($mooxConfig['template']);
    }

    private function getMooxConfig(): array
    {
        return json_decode(file_get_contents('composer.json'), true)['extra']['moox'];
    }

    /*
    After testing , move everything from here to the base MooxServiceProvider:

    public function configureFromComposer(): void
    {
        $mooxConfig = $this->getMooxConfig();
        $mooxPackage = $this->getMooxPackage();

        $configurableMethods = [
            'title',
            'stability',
            'type',
            'category',
            'template',
        ];

        foreach ($configurableMethods as $method) {
            if (isset($mooxConfig[$method])) {
                $mooxPackage->$method($mooxConfig[$method]);
            }
        }
    }
    */
}
