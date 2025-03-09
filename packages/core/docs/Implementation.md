# Implementation

The implementation guide covers all the possible ways to implement the Moox Core package.

## Base Entities

Moox Core ships base entities that can be used to create new entities:

### Item Entity

The Item Entity is implemented in our Single Entity Package [Moox Item](https://github.com/mooxphp/item). You can use the `php artisan moox:build` command to create a new item entity. This is the manual way to implement the Item Entity:

#### Item Model

```php
use Moox\Core\Entities\Items\Item\ItemModel;

class Log extends ItemModel {
    // Your model
}
```

### Item Resource

```php
use Moox\Core\Entities\Items\Item\ItemResource;
use Illuminate\Database\Eloquent\Builder;

class LogResource extends ItemResource
{
    // Your resource
}
```

### Item Pages

```php
use Moox\Core\Entities\Items\Item\Pages\ItemCreatePage;
use App\Filament\Resources\LogResource;

class CreatePage extends ItemCreatePage
{
    // Your create page
}
```

```php
use Moox\Core\Entities\Items\Item\Pages\ItemEditPage;
use App\Filament\Resources\LogResource;

class EditPage extends ItemEditPage
{
    // Your edit page
}
```

```php
use Moox\Core\Entities\Items\Item\Pages\ItemListPage;
use App\Filament\Resources\LogResource;

class ListPage extends ItemListPage
{
    // Your list page
}
```

```php
use Moox\Core\Entities\Items\Item\Pages\ItemViewPage;
use App\Filament\Resources\LogResource;

class ViewPage extends ItemViewPage
{
    // Your view page
}
```

## Core Traits

Moox Core ships with multiple traits that can be used to add functionality to entities:

### Tabs

The `HasTabs` trait can be used to add configurable tabs to a resource.

### Taxonomy

The `HasTaxonomy` trait can be used to add one or more taxonomies to a resource.

```php
// TODO: remove this boilerplate from models
protected function getResourceName(): string
{
    return 'log';
}
```
