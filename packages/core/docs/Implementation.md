# Implementation

The implementation guide covers all the possible ways to implement the Moox Core package.

## Base Entities

Moox Core ships base entities that can be used to create new entities:

### Item Entity

The Item Entity is implemented in our Single Entity Package [Moox Item](https://github.com/mooxphp/item). You can use the `php artisan moox:build` command to create a new item entity. This is the manual way to implement the Item Entity:

#### Item Model

```php
use Moox\Core\Entities\Items\Item\MooxModel;

class Item extends MooxModel {}
```

### Item Resource

```php
use Moox\Core\Entities\BaseResource;

class ItemResource extends Resource
{
    // your resource
}
```

### Item Pages

```php
use Moox\Core\Entities\Items\Item\Pages\MooxCreatePage;
use Moox\Item\Moox\Entities\Items\ItemResource;

class CreatePage extends MooxCreatePage
{
    protected static string $resource = ItemResource::class;
}
```

```php
use Moox\Core\Entities\Items\Item\Pages\MooxEditPage;
use Moox\Item\Moox\Entities\Items\ItemResource;

class EditPage extends MooxEditPage
{
    protected static string $resource = ItemResource::class;
}
```

```php
use Moox\Core\Entities\Items\Item\Pages\MooxListPage;
use Moox\Item\Moox\Entities\Items\ItemResource;

class ListPage extends MooxListPage
{
    protected static string $resource = ItemResource::class;
}
```

```php
use Moox\Core\Entities\Items\Item\Pages\MooxViewPage;
use Moox\Item\Moox\Entities\Items\ItemResource;

class ViewPage extends MooxViewPage
{
    protected static string $resource = ItemResource::class;
}
```

## Core Traits

Moox Core ships with multiple traits that can be used to add functionality to entities:

### Has Tabs

### Has Taxonomy
