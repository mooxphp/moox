<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

use Moox\Builder\Builder\Traits\HandlesNamespacing;

class PanelGenerator extends AbstractGenerator
{
    use HandlesNamespacing;

    public function generate(): void
    {
        $template = $this->loadStub('panel');

        $variables = [
            'namespace' => $this->getPanelNamespace(),
            'class_name' => $this->entityName,
            'id' => strtolower($this->entityName),
            'path' => strtolower($this->entityName),
            'plugin_namespace' => $this->getFilamentNamespace('Plugins'),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile($this->getPanelPath(), $content);
    }

    protected function getPanelNamespace(): string
    {
        if ($this->isPackageContext()) {
            return $this->entityNamespace.'\\Providers';
        }

        return 'App\\Providers';
    }

    protected function getPanelPath(): string
    {
        if ($this->isPackageContext()) {
            return $this->entityPath.'/Providers/'.$this->entityName.'PanelProvider.php';
        }

        return $this->entityPath.'/Providers/'.$this->entityName.'PanelProvider.php';
    }
}
