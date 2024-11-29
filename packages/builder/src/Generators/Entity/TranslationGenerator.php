<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity;

use Illuminate\Support\Str;
use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Services\File\FileManager;

class TranslationGenerator extends AbstractGenerator
{
    public function __construct(
        BuildContext $context,
        FileManager $fileManager,
        array $blocks = []
    ) {
        parent::__construct($context, $fileManager, $blocks);
    }

    public function generate(): void
    {
        $template = $this->loadStub($this->getTemplate());

        $variables = [
            'Entity' => $this->context->getEntityName(),
            'Entities' => $this->context->getPluralName(),
            'LowercaseEntity' => Str::kebab($this->context->getEntityName()),
            'LowercaseEntities' => Str::kebab($this->context->getPluralName()),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $path = $this->getTranslationPath();

        $this->writeFile($path, $content);
    }

    protected function getTranslationPath(): string
    {
        $basePath = match ($this->context->getContextType()) {
            'app' => lang_path('entities'),
            'preview' => lang_path('previews'),
            'package' => $this->context->getPath('translation'),
            default => throw new \InvalidArgumentException('Invalid context type: '.$this->context->getContextType()),
        };

        return $basePath.'/'.$this->formatFilename($this->context->getEntityName()).'.php';
    }

    protected function getGeneratorType(): string
    {
        return 'translation';
    }
}
