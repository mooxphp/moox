# Moox Prompts

CLI-kompatible Prompts für Laravel Artisan Commands.

## Übersicht

Dieses Package bietet eine einfache Proxy-Implementierung für Laravel Prompts. Es ermöglicht es, die gleichen Helper-Funktionen wie Laravel Prompts zu verwenden, mit der Möglichkeit, später Web-Funktionalität hinzuzufügen.

## Features

- ✅ Alle Laravel Prompt-Typen unterstützt (`text`, `select`, `multiselect`, `confirm`, etc.)
- ✅ Identische API wie Laravel Prompts

## Installation

```bash
composer require moox/prompts
```

## Verwendung

### In Commands

Verwende die gleichen Helper-Funktionen wie in Laravel Prompts:

```php
use function Moox\Prompts\text;
use function Moox\Prompts\select;
use function Moox\Prompts\confirm;
use function Moox\Prompts\form;

public function handle()
{
    // Einzelne Prompts
    $name = text('What is your name?');
    $package = select('Which package?', ['moox/core', 'moox/user']);
    $confirm = confirm('Are you sure?');
    
    // FormBuilder
    $result = form()
        ->text('Name?')
        ->select('Package?', ['moox/core', 'moox/user'])
        ->submit();
    
    // Command-Logik...
}
```

## Architektur

Das Package besteht aus:

- **PromptRuntime**: Interface für Prompt-Implementierungen
- **CliPromptRuntime**: CLI-Implementierung (delegiert an Laravel Prompts)
- **functions.php**: Globale Helper-Funktionen
- **PromptsServiceProvider**: Registriert Services

## License

Siehe [LICENSE.md](LICENSE.md)
