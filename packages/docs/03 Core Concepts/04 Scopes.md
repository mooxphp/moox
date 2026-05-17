1. Datenbank bleibt simpel

In allen Entities:

```
$table->string('scope')->index();
```

Beispiele:

- career:media:default:public - Media hat public images für Jobanzeigen, die nur im career-bereich verwendet werden sollen
- career:media:default:restricted - Der Rest, Lebensläufe etc. ist restricted
- core:media:web:public - Ein Standard Scope, damit wären public media files auch im allgemeinen web pool

Keine Foreign Keys, keine Joins.

⸻

2. Scope wird im Code ein Objekt

Statt überall

```
[$origin, $target, $context, $mode] = explode(':', $scope);
```

macht ihr:

```
$scope = Scope::parse($model->scope);
```

Dann:

```
$scope->origin()
$scope->target()
$scope->context()
$scope->mode()
```

⸻

3. origin und target werden Models

Das ist der wichtige Schritt.

Beispiel:

```
$scope->originModel();
$scope->targetModel();
```

Dann bekommt ihr z. B.:

```
Media::class
Career::class
Inbox::class
Billing::class
```

⸻

4. Mapping Tabelle / Registry

Dafür braucht ihr eine kleine Registry.

Zum Beispiel:

```
return [

    'origins' => [
        'media' => \Moox\Media\Models\Media::class,
        'inbox' => \Moox\Inbox\Models\InboxItem::class,
        'outbox' => \Moox\Outbox\Models\OutboxItem::class,
    ],

    'targets' => [
        'career' => \Moox\Career\Models\Career::class,
        'billing' => \Moox\Billing\Models\Billing::class,
        'global' => null,
    ],

];
```

Dann kann Scope automatisch auflösen. Diese Tabelle sitzt im Core, das Moox Scope package hat eine Entity dafür.

⸻

5. Beispiel Implementation

Scope Object

```
class Scope
{
    public function __construct(
        public string $origin,
        public string $target,
        public string $context,
        public string $mode
    ) {}

    public static function parse(string $scope): static
    {
        [$origin, $target, $context, $mode] = explode(':', $scope);

        return new static($origin, $target, $context, $mode);
    }

    public function originModel(): ?string
    {
        return config("moox.scopes.origins.{$this->origin}");
    }

    public function targetModel(): ?string
    {
        return config("moox.scopes.targets.{$this->target}");
    }
}
```

⸻

6. Verwendung im Code

Dann könnt ihr z. B.:

```
$scope = Scope::parse($media->scope);

if ($scope->targetModel() === Career::class) {
    // Career Kontext
}
```

Oder:

```
if ($scope->mode === 'public') {
    // öffentlich ausspielbar
}
```

⸻

7. Das wird besonders stark bei Filament

Beispiel Media Picker im Career Modul:

```
$scope = Scope::parse($record->scope);

Media::query()
    ->where('scope', 'like', "media:career:{$scope->context}:%");
```

Oder automatisch.

⸻

8. Vorteil gegenüber reinem String System

Ihr bekommt:

weiterhin

✔ einfache DB
✔ einfache Queries
✔ kein Join
✔ gute Logs

zusätzlich

✔ typed objects
✔ model resolution
✔ weniger String-Fehler
✔ bessere IDE-Unterstützung

⸻

9. Bonus: Scope als Cast

Ihr könnt das sogar als Laravel Cast bauen.

```
protected $casts = [
    'scope' => ScopeCast::class,
];
```

Dann:

```
$model->scope->origin
$model->scope->target
$model->scope->context
$model->scope->mode
```

Ohne parse().

Das wäre sehr elegant.

⸻

Fazit

Dein Ansatz ist genau richtig:

- Scope bleibt ein einfacher String
- origin und target werden im Code zu Models
- Scope wird als Value Object behandelt

Dann bekommt ihr:

die Einfachheit von Strings + die Stärke von echten Objekten.

⸻

Wenn du willst, kann ich dir noch zeigen, warum dieses Scope-System für Media + Inbox + Outbox gleichzeitig perfekt funktioniert, weil sich damit automatisch kontextsichere Relation Picker bauen lassen.

Media Scopen = unterschiedliche filepaths?
Default scopes von packages werden in der DB angezeigt, sollten aber nicht gelöscht oder editiert werden (extra Warnung?)
