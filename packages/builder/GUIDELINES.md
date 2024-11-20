# Builder Coding Guidelines

This file contains special coding guidelines for Moox Builder.

We use the [Laravel coding standards](https://laravel.com/docs/11.x/contributions#code-style) and follow the [Pint rules](https://laravel.com/docs/11.x/pint#rules).

## Compatibility

-   Laravel 11
-   PHP 8.3
-   Filament 3.2
-   Livewire 3
-   Tailwind CSS 3
-   PHPStan 2
-   Pest 3

## Core Principles

1. Single Responsibility

-   Each service has ONE specific responsibility (see README.md#Services)
-   Generators ONLY generate content and collect files
-   File operations ONLY through FileManager service
-   Path handling ONLY through BuildContext

2. Service Usage

-   File Operations: Always use FileManager
-   Build Management: Always use BuildManager
-   Entity Operations: Always extend AbstractEntityService
-   Context Handling: Always use BuildContext for paths/namespaces

3. File Generation Flow
   ´´´php
   // CORRECT: Generator only collects files
   protected function writeFile(string $content, string $className): void {
    $path = $this->context->getPath('resource');
    $filePath = $path.'/'.$this->resourceName.'/Pages/'.$className.'.php';
    $this->generatedFiles[$this->getGeneratorType()] = [
   'path' => $filePath,
   'content' => $content
   ];
   }

// WRONG: Direct file operations
protected function writeFile(string $content): void
{
    file_put_contents($path, $content); // NEVER do this!
    mkdir($path, 0755); // NEVER do this!
}
´´´

4. Common Pitfalls

-   Never use direct file operations (file_put_contents, mkdir, etc)
-   Never handle paths manually (use BuildContext)
-   Never format files directly (use FileManager)
-   Never duplicate service functionality

5. Architecture References

-   See README.md lines 81-117 for service responsibilities
-   See BuildContext for path/namespace handling
-   See EntityGenerator lines 32-37 for execution flow
-   See AbstractGenerator lines 132-139 for file collection pattern

6. Never Assume, Always Know

-   Never assume method signatures or return types
-   Never assume property existence or types
-   Never assume service interfaces
-   When implementation details are unclear, ask for the relevant files
-   When referencing existing code, specify exact line numbers
-   When extending classes, verify the complete parent class first

## Configuration & Context Handling

### Context Types

-   `preview`: Files go to `app/Builder/`, namespace `App\Builder`
-   `package`: Files go to package path, namespace from package config
-   `default`: Files go to base path, namespace `App`

### Using BuildContext

```php
// CORRECT: Always use BuildContext for paths
$path = $this->context->getPath('resource');
$namespace = $this->context->getNamespace('model');

// WRONG: Never hardcode paths or namespaces
$path = base_path('Resources');  // NEVER do this!
$namespace = 'App\\Models';      // NEVER do this!
```

### Configuration Structure

```php
// config/builder.php
'preview' => [
    'base_path' => app_path('Builder'),
    'base_namespace' => 'App\\Builder',
    'classes' => [
        'resource' => [
            'path' => '%BasePath%/Resources',
            'namespace' => '%BaseNamespace%\\Resources',
        ],
        // ... other class configs
    ]
],
```

### Path Resolution

-   Always use `%BasePath%` placeholder
-   Always use `%BaseNamespace%` placeholder
-   Let BuildContext handle path normalization
-   Let BuildContext handle context-specific paths

### Common Configuration Mistakes

```php
// WRONG: Direct path concatenation
$path = app_path('Builder/Resources');

// WRONG: Manual namespace building
$namespace = 'App\\Builder\\Resources';

// CORRECT: Use BuildContext
$path = $this->context->getPath('resource');
$namespace = $this->context->getNamespace('resource');
```

### Context-Aware Services

-   All services should extend ContextAwareService or AbstractEntityService
-   Services must respect the current context type
-   Services must use BuildContext for all path/namespace operations
-   Configuration values should never be hardcoded

### Configuration Checklist

Before writing code:

1. Is the path in config/builder.php?
2. Which context types need to be supported?
3. Are placeholders being used correctly?
4. Is BuildContext being used for all paths/namespaces?
5. Are services context-aware?

## Development Checklist

Before submitting code:

1. Does it use appropriate services?
2. Are file operations delegated to FileManager?
3. Are paths handled by BuildContext?
4. Does it follow the established patterns in AbstractGenerator?
5. Does it maintain single responsibility?

### BuildContext Interface

BuildContext provides these methods:

-   getContextType(): string
-   getConfig(): array
-   getPath(string $type): string
-   getNamespace(string $type): string
-   getEntityName(): string
-   getBlocks(): array
-   getPluralName(): string
-   getTemplate(string $type): array

When extending BuildContext:

-   Always add new methods to GUIDELINES.md
-   Never remove existing methods
-   Never change method signatures
-   Document all dependencies in constructor

### Template Handling

1. BuildContext provides template configuration:

```php
// CORRECT: Get template configuration from BuildContext
$templates = $this->context->getTemplate('resource');
```

2. Generators handle template loading:

```php
// CORRECT: Generator loads template content
protected function getTemplate(): string
{
    $templates = $this->context->getTemplate($this->getGeneratorType());
    $templatePath = $templates['path'] ?? null;

    if (!$templatePath || !file_exists($templatePath)) {
        throw new RuntimeException(
            "Template file for {$this->getGeneratorType()} not found at {$templatePath}"
        );
    }

    return file_get_contents($templatePath);
}
```

3. Never load templates directly:

```php
// WRONG: Direct template loading
$template = file_get_contents($path);

// WRONG: Hardcoded template paths
$template = file_get_contents(__DIR__.'/templates/resource.stub');
```

### Code Changes & Refactoring

1. When modifying service classes:

-   Always show complete class implementation
-   Never remove existing public methods without explicit approval
-   Document method dependencies and side effects
-   Maintain existing method signatures
-   Test impact on dependent services

2. File Operations:

-   All file operations must go through FileManager
-   FileManager must maintain these core methods:
    -   deleteFiles(int $entityId, string $buildContext): void
    -   writeAndFormatFiles(array $files): void
    -   formatFiles(array $files): void
    -   findMigrationFiles(string $path): array
    -   cleanupBeforeRegeneration(int $entityId, string $buildContext): void
    -   removeEmptyDirectories(string $path): void

### Documentation Updates

When suggesting code changes:

1. Always suggest relevant GUIDELINES.md updates
2. Document new patterns and conventions
3. Include correct and incorrect usage examples
4. Reference existing code patterns
5. Update interface documentation when adding methods
6. Update README.md when adding new services
7. Update DEVLOG.md when finishing tasks

### File Operation Patterns

1. Entity File Deletion:

-   Always use FileManager::deleteFiles() for entity-related files
-   Never implement custom file deletion logic
-   Always clean up empty directories after deletion

2. File Generation:

-   Collect files in generators
-   Use FileManager::writeAndFormatFiles() for writing
-   Never write files directly from generators

### Migration File Handling

1. Finding Migrations:

-   Always use FileManager::findMigrationFiles() for listing migrations
-   Use MigrationFinder service for specific table migrations
-   Never implement direct glob or directory scanning

2. Migration Pattern Handling:

-   Use HandlesMigrationFiles trait for consistent patterns
-   Follow context-specific naming conventions:
    -   App: [timestamp]_create_[table]\_table.php
    -   Package: create\_[table]\_table.php.stub
    -   Preview: preview*[timestamp]\_create*[table]\_table.php

### State Management Patterns

1. Build State Handling:

-   Always use BuildStateManager for state transitions
-   Never modify build states directly in database
-   Track all state changes through proper services

2. Version Control:

-   Use VersionManager for all version-related operations
-   Follow semantic versioning for package versions
-   Maintain version history for rollback support

3. State Transitions:

-   Preview -> Production requires explicit conversion
-   Production states are exclusive per context
-   Always validate state transitions through managers
