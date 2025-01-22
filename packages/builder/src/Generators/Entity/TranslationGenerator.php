<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity;

use InvalidArgumentException;
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

    protected function getGeneratorType(): string
    {
        return 'translation';
    }

    protected function formatEntityName(): string
    {
        return implode(' ', preg_split('/(?=[A-Z])/', $this->context->getEntityName(), -1, PREG_SPLIT_NO_EMPTY));
    }

    protected function formatPluralName(): string
    {
        return implode(' ', preg_split('/(?=[A-Z])/', $this->context->getPluralName(), -1, PREG_SPLIT_NO_EMPTY));
    }

    public function generate(): void
    {
        $template = $this->loadStub($this->getTemplate());

        $variables = [
            'Entity' => $this->formatEntityName(),
            'Entities' => $this->formatPluralName(),
            'LowercaseEntity' => Str::kebab($this->context->getEntityName()),
            'LowercaseEntities' => Str::kebab($this->context->getPluralName()),
            'Package' => $this->getPackageName(),
            'Path' => $this->getTranslationPath(false),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $path = $this->getTranslationPath(true);

        $this->writeFile($path, $content);
    }

    protected function getTranslationPath(bool $fullPath = true): string
    {
        $entityFile = $this->formatFilename($this->context->getEntityName());

        if (! $fullPath) {
            return match ($this->context->getContextType()) {
                'app' => 'entities',
                'preview' => 'previews',
                'package' => $this->getPackageName(),
                default => throw new InvalidArgumentException('Invalid context type: '.$this->context->getContextType()),
            };
        }

        return match ($this->context->getContextType()) {
            'app' => lang_path(config('app.locale', 'en').'/entities/'.$entityFile.'.php'),
            'preview' => lang_path(config('app.locale', 'en').'/previews/'.$entityFile.'.php'),
            'package' => $this->context->getPath('translation').'/'.$entityFile.'.php',
            default => throw new InvalidArgumentException('Invalid context type: '.$this->context->getContextType()),
        };
    }

    protected function getPackageName(): string
    {
        if ($this->context->getContextType() !== 'package') {
            return '';
        }

        $config = $this->context->getConfig();

        return $config['package']['name'] ?? '';
    }
}
