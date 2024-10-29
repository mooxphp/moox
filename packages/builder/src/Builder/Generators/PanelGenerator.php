<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

class PanelGenerator extends AbstractGenerator
{
    public function generate(): void
    {
        $template = $this->loadStub('panel');

        $variables = [
            'class_name' => $this->entityName,
            'id' => strtolower($this->entityName),
            'path' => strtolower($this->entityName),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile($this->getPanelPath(), $content);
    }

    protected function getPanelPath(): string
    {
        return $this->entityPath.'/Providers/'.$this->entityName.'PanelProvider.php';
    }
}
