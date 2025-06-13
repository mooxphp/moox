# Filament v4 Upgrade Guide

Dieses Dokument enthält die notwendigen Schritte für das Upgrade von Filament v3 auf v4.

## Voraussetzungen

- PHP 8.2 oder höher
- Laravel v11.28 oder höher
- Tailwind CSS v4.0+ (nur wenn Sie Tailwind CSS v3.0 mit Filament verwenden)

## Upgrade-Schritte

### 1. Upgrade-Skript installieren und ausführen

```bash
composer require filament/upgrade:"^4.0" -W --dev

vendor/bin/filament-v4
```

Das Skript fragt nach den Verzeichnissen, in denen das Upgrade durchgeführt werden soll. In unserem Fall: `app` und `packages`.

### 2. Composer Dependencies anpassen

In diesem Schritt wurden alle Packages, die Filament als Abhängigkeit haben, angepasst oder temporär entfernt.

### 3. Login-System anpassen

Da wir eigene Login-Klassen für moox und press haben, die auf der Filament Login-Klasse basieren, mussten folgende Anpassungen vorgenommen werden:

- Integration der MFA-Methoden
- Anpassung der Verzeichnisstruktur: von `pages/auth/` zu `auth/pages/`
- Umstellung von Forms auf Schemas:

```php
// Vorher
public static function form(Form $form): Form
use Filament\Forms\Form;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;

// Nachher
public static function form(Schema $schema): Schema
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
```

