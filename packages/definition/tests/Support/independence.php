<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Moox\\Definition\\';

    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $path = dirname(__DIR__, 2).'/src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';

    if (is_file($path)) {
        require $path;
    }
});

use Moox\Definition\Definitions\Entity;
use Moox\Definition\Definitions\Field;
use Moox\Definition\Definitions\Package;
use Moox\Definition\Features\Identity\Identity;
use Moox\Definition\Resolution\Catalog;

$entity = Entity::named('article')
    ->table('articles')
    ->addFeature(new Identity)
    ->addField(Field::text('article_number', 32)->required());

$catalog = (new Catalog)->add((new Package('moox/article'))->addEntity($entity));
$resolved = $catalog->resolve('moox/article', 'article');

if ($resolved->field('uuid')?->attributes()->generator()->value() !== 'uuid') {
    fwrite(STDERR, "Identity generator was not resolved.\n");
    exit(1);
}

foreach (get_declared_classes() as $class) {
    if (str_starts_with($class, 'Illuminate\\') || str_starts_with($class, 'Filament\\') || str_starts_with($class, 'Livewire\\')) {
        fwrite(STDERR, "Framework class loaded: {$class}\n");
        exit(1);
    }
}

echo 'ok';
