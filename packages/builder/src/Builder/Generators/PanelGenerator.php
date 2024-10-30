<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

use Moox\Builder\Builder\Traits\HandlesNamespacing;

class PanelGenerator extends AbstractGenerator
{
    use HandlesNamespacing;

    protected array $authConfig;

    public function __construct(
        string $entityName,
        string $entityNamespace = '',
        string $entityPath = '',
        array $blocks = [],
        array $features = [],
        array $authConfig = []
    ) {
        parent::__construct($entityName, $entityNamespace, $entityPath, $blocks, $features);

        $this->authConfig = array_merge([
            'guard' => 'web',
            'broker' => 'users',
            'model' => 'App\\Models\\User',
            'login' => null,
            'passwordReset' => null,
            'resetPassword' => null,
        ], $authConfig);
    }

    public function generate(): void
    {
        $template = $this->loadStub('panel');

        $variables = [
            'namespace' => $this->getPanelNamespace(),
            'class_name' => $this->entityName,
            'id' => strtolower($this->entityName),
            'path' => strtolower($this->entityName),
            'plugin_namespace' => $this->getFilamentNamespace('Plugins'),
            'auth_imports' => $this->getAuthImports(),
            'auth_config' => $this->getAuthConfig(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile($this->getPanelPath(), $content);
    }

    protected function getAuthImports(): string
    {
        $imports = [];

        if ($this->authConfig['model']) {
            $imports[] = "use {$this->authConfig['model']};";
        }
        if ($this->authConfig['login']) {
            $imports[] = "use {$this->authConfig['login']};";
        }
        if ($this->authConfig['passwordReset']) {
            $imports[] = "use {$this->authConfig['passwordReset']};";
        }
        if ($this->authConfig['resetPassword']) {
            $imports[] = "use {$this->authConfig['resetPassword']};";
        }

        return implode("\n", $imports);
    }

    protected function getAuthConfig(): string
    {
        $config = [];

        $config[] = "->authGuard('{$this->authConfig['guard']}')";
        $config[] = "->authPasswordBroker('{$this->authConfig['broker']}')";

        if ($this->authConfig['model']) {
            $modelClass = class_basename($this->authConfig['model']);
            $config[] = "->authModel($modelClass::class)";
        }

        if ($this->authConfig['login']) {
            $loginClass = class_basename($this->authConfig['login']);
            $config[] = "->login($loginClass::class)";
        }

        return implode("\n", $config);
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

    protected function getModelNamespace(): string
    {
        if ($this->isPackageContext()) {
            return $this->entityNamespace;
        }

        return 'App\\Models';
    }
}
