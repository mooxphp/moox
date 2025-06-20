# Idea

## Moox JSON

I have an idea for a new package called Moox Json (packages/json). It should provide following fields for Filament forms:

-   JsonRows - editable and sortable rows, supports 3 Levels
-   JsonFields - renders Filament Form fields like native fields, supports 3 Levels
-   JsonEditor - edit JSON in Filament seamlessly, a well formatted and linted editor
-   JsonViewer - view JSON in Filament seamlessly, just for fallbacks ...
-   JsonColumn - output JSON in your table

Some references

-   https://github.com/codebar-ag/filament-json-field - Codemirror
-   https://github.com/novadaemon/filament-pretty-json - simple
-   https://filamentphp.com/plugins/ahmedabdelaal-json-preview - [JSON editor](https://github.com/josdejong/jsoneditor)
-   https://filamentphp.com/plugins/valentin-morice-json-column - probably same
-   https://github.com/json-editor/json-editor - interesting, like Json Fields ;-)

JsonFields
->Levels(3)
->FallbackTo(JsonViewer) // If not renderable
->FailTo(JsonViewer) // Fallback but with Error
->ToggleTo(JsonViewer,JsonRows) // shows toggle action(s)
