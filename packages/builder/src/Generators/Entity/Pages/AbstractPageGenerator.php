<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity\Pages;

use Override;
use InvalidArgumentException;
use Illuminate\Support\Str;
use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Generators\Entity\AbstractGenerator;
use Moox\Builder\Services\File\FileManager;
use RuntimeException;

abstract class AbstractPageGenerator extends AbstractGenerator
{
    protected string $resourceName;

    public function __construct(
        BuildContext $context,
        FileManager $fileManager,
        array $blocks = []
    ) {
        parent::__construct($context, $fileManager, $blocks);
        $this->initializeProperties();
    }

    protected function initializeProperties(): void
    {
        $this->resourceName = $this->context->getEntityName().'Resource';
        $this->generatedFiles = [];
    }

    abstract protected function getPageType(): string;

    public function generate(): void
    {
        $template = $this->getTemplate();
        $className = $this->getClassName();

        $variables = [
            'namespace' => $this->getNamespace(),
            'model' => $this->context->getEntityName(),
            'model_plural' => $this->context->getPluralName(),
            'resource' => $this->context->formatNamespace('resource', true).'\\'.$this->resourceName,
            'use_statements' => $this->formatUseStatements(),
            'traits' => $this->formatTraits(),
            'methods' => $this->formatMethods(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile($this->getFilePath($className), $content);
    }

    protected function getClassName(): string
    {
        return $this->getPageType().$this->context->getEntityName();
    }

    protected function getNamespace(): string
    {
        return $this->context->formatNamespace('resource', false).'\\'.$this->resourceName.'\\Pages';
    }

    protected function getFilePath(string $className): string
    {
        $path = $this->context->getPath('resource');

        return $path.'/'.$this->resourceName.'/Pages/'.$className.'.php';
    }

    protected function getGeneratorType(): string
    {
        return 'page_'.$this->getPageType();
    }

    #[Override]
    protected function getTemplate(): string
    {
        $template = $this->loadStub($this->getTemplateFile());
        if ($template === '' || $template === '0') {
            throw new RuntimeException('Failed to load template: '.$this->getTemplateFile());
        }

        return $template;
    }

    protected function getTemplateFile(): string
    {
        $templatePath = $this->context->getConfig()['generators']['resource']['page_templates'][$this->getPageType()] ?? null;
        if (! $templatePath) {
            throw new RuntimeException('Template not found for page type: '.$this->getPageType());
        }

        return $templatePath;
    }

    #[Override]
    protected function formatUseStatements(): string
    {
        $statements = [];
        $pageType = strtolower($this->getPageType());

        foreach ($this->getBlocks() as $block) {
            $blockStatements = $block->getUseStatements('pages');
            if (isset($blockStatements[$pageType])) {
                $statements = array_merge($statements, $blockStatements[$pageType]);
            }

            $blockTraits = $block->getTraits('pages');
            if (isset($blockTraits[$pageType])) {
                foreach ($blockTraits[$pageType] as $trait) {
                    $statements[] = sprintf('use %s;', $trait);
                }
            }
        }

        return implode("\n", array_map(fn($statement): string => rtrim((string) $statement, ';').';', array_unique($statements)));
    }

    #[Override]
    protected function formatTraits(): string
    {
        $traits = [];
        $pageType = strtolower($this->getPageType());

        foreach ($this->getBlocks() as $block) {
            $blockTraits = $block->getTraits('pages');
            if (isset($blockTraits[$pageType])) {
                $shortTraits = array_map(function ($trait): string {
                    $parts = explode('\\', $trait);

                    return end($parts);
                }, $blockTraits[$pageType]);
                $traits = array_merge($traits, $shortTraits);
            }
        }

        if ($traits === []) {
            return '';
        }

        return 'use '.implode(', ', array_unique($traits)).';';
    }

    #[Override]
    protected function formatMethods(): string
    {
        $methods = [];
        $pageType = strtolower($this->getPageType());

        foreach ($this->getBlocks() as $block) {
            $blockMethods = $block->getMethods('pages');
            if (! empty($blockMethods[$pageType])) {
                foreach ($blockMethods[$pageType] as $methodName => $methodBody) {
                    $methodBody = str_replace(
                        ['{{ entityKey }}', '{{ entity }}', '{{ namespace }}'],
                        [
                            $this->getEntityConfigKey(),
                            $this->context->getEntityName(),
                            $this->context->getNamespace('model'),
                        ],
                        $methodBody
                    );

                    if ($methodName === 'mount') {
                        if (! isset($methods['mount'])) {
                            $methods['mount'] = [];
                        }

                        $methods['mount'][] = (string) $methodBody;
                    } else {
                        $methods[$methodName] = (string) $methodBody;
                    }
                }
            }
        }

        $formattedMethods = [];

        if (isset($methods['mount'])) {
            $mountBody = implode("\n        ", array_map('strval', array_unique($methods['mount'])));
            $formattedMethods[] = "public function mount(): void\n    {\n        parent::mount();\n        {$mountBody}\n    }";
            unset($methods['mount']);
        }

        foreach ($methods as $methodBody) {
            $formattedMethods[] = (string) $methodBody;
        }

        return implode("\n\n    ", array_map('strval', $formattedMethods));
    }

    protected function getEntityConfigKey(): string
    {
        $contextType = $this->context->getContextType();
        $entityName = Str::kebab($this->context->getEntityName());

        return match ($contextType) {
            'app' => 'entities.' . $entityName,
            'preview' => 'previews.' . $entityName,
            'package' => $this->context->getConfig()['package']['name'].('.entities.' . $entityName),
            default => throw new InvalidArgumentException('Invalid context type: '.$contextType),
        };
    }
}
