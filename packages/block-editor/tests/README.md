# Editor Package Tests

Diese Tests gehoeren ausschliesslich zum Package `moox/block-editor` und liegen unter:

- `packages/moox/block-editor/tests/Feature` (PHP Feature-Tests)
- `packages/moox/block-editor/tests/js` (JavaScript-Tests)

## Namenskonvention

- Jede Testdatei hat den gleichen Namen wie die getestete Datei/Klasse plus `Test`.
- Beispiele:
  - `TemplateController` -> `TemplateControllerTest.php`
  - `StoreTemplateRequest` -> `StoreTemplateRequestTest.php`
  - `BlockEditorField` -> `BlockEditorFieldTest.php`

## Ausfuehrung

- Nur Package-Tests ausfuehren:
  - `php artisan test --compact packages/moox/block-editor/tests/Feature`
- JS-Tests (falls Node-Testsetup aktiv):
  - `npm run test:moox-editor`
- Einzelne Datei ausfuehren:
  - `php artisan test --compact packages/moox/block-editor/tests/Feature/TemplateControllerTest.php`

## Scope

Diese Tests decken die Kernfunktionen des Editor-Packages ab:

- API-Flows (Create, Read, Update, Delete, Auth)
- Requests/Validierung
- Policies/Autorisierung
- Service-Provider-Registrierung
- Model-Verhalten (Casts/Fillable)
- Sanitizer-Logik
- Field/Livewire-Komponentenlogik
