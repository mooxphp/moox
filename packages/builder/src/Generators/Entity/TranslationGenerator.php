<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity;

class TranslationGenerator extends AbstractGenerator
{
    public function generate(): void
    {
        $template = $this->loadStub($this->getTemplate());

        $variables = [
            'Entity' => $this->context->getEntityName(),
            'Entities' => $this->context->getPluralModelName(),
            'LowercaseEntity' => strtolower($this->context->getEntityName()),
            'LowercaseEntities' => strtolower($this->context->getPluralModelName()),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $path = $this->getTranslationPath();

        $this->writeFile($path, $content);
        $this->formatGeneratedFiles();
    }

    protected function getTranslationPath(): string
    {
        $basePath = match ($this->context->getContextType()) {
            'app' => lang_path('entities'),
            'preview' => lang_path('previews'),
            'package' => $this->context->getPath('translation'),
            default => throw new \InvalidArgumentException('Invalid context type: '.$this->context->getContextType()),
        };

        return $basePath.'/'.$this->context->getEntityName().'.php';
    }

    protected function getGeneratorType(): string
    {
        return 'translation';
    }
}
