## Für Alf 

- Im App context liegen migrationis und Seeder für Country und Language (für die einfachheit, dass ich es gleich ausprobieren kann).
- BuilderPanel ist gerade umgebogen auf ->discoverResources(in: app_path('Locale/Resources'), for: 'App\\Locale\\Resources')
- AdminPanel liegen auch die Plugins Language und Country (auch zum testen, dass es richtig generiert wird)
- Preview und App context verweisen gerade auf app/Local, damit alles da rein generiert wird 
- Country und Language Preset sind da, Locale sind noch wip 

FIX Plugin Generator


## Todos (Für Kim)
- Anpassen der views, Felder noch passender machen in List, Edit, View
- JSON Validation Block bauen
    - Felder hydration checken 
    - Validation einbauen 
    - abstract class???
- Relations
- Die restlichen Entitäten generieren/ aufbauen
- Changes in Builder!!! nach tinkern raus machen 

## Notes 
- Static Locales Name? -> braucht man dann dafür theoretisch exonyms?


## Lang switch 
- created Controller app\Http\Controllers\ChangeLanguageController.php
- created Middleware app\Http\Middleware\SetLocale.php

                 ->getStateUsing(function ($record) {
                        $locale = app()->getLocale();

                        return $record->exonyms[$locale] ?? $record->name;
                    }),
