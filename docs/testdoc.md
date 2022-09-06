# Titel

Der H1-Titel sollte in der Navigation verwendet werden, nicht Dateiname.md.

## Subtitel

Und jetzt die Idee mit der Demo-Komponente:

[Component]: #

**Component demo**

```php
<x-input type="password" ...more parms>
```

[See component demo](https://tallui.io/docs/tallui-form-components/inputs/password)

[Component]: #

**Der Trick:**

- Wir schreiben eine vollständige Doku in Markdown, darin Code-Beispiele für Komponenten, die Doku ist auch in Github vollständig und nützlich, weil direkt unter den Code-Beispielen der Link zur gerenderten Doku ist.
- Die Komponenten, die gerendert werden sollen beginnen immer mit **Component demo**, dann kommt das Code-Beispiel, danach der Link
- Wir können mit preg_replace die Demo umschreiben, das Code-Beispiel verwenden und damit auch eine Komponente rendern, die ohne Parameter gar nicht funktioniert.

**Mögliche Probleme:**

- Potentielle Sicherheitslücke, da Code aus einer schwer kontrollierbaren Quelle ausgeführt wird:
  - Mögliche Lösung: sicherstellen, dass der Code lediglich eine Komponente aufruft, niemals plain PHP. Da wir aber unsere eigene Doku rendern und keine fremden Quellen, müssen wir das nicht übertreiben.
- Kleiner Schreibfehler und schon funktioniert das Rendern nicht mehr:
  - Mögliche Lösung: Klare Definition, wie Komponenten in die Doku einzubinden sind. Falls es trotzdem schiefgeht, landet das Markdown unverändert in der Doku. Auch nicht so schlimm.
- Beim erstmaligen Schreiben der Doku kennt man den Link zur gerenderten Doku noch gar nicht.
  - Mögliche Lösung: Die URL ist einfach abzuleiten, Beispiel oben (https://tallui.io/docs/package-name/folder/component)
- Mehraufwand bei der Pflege.
  - Hält sich in Grenzen. Das Code-Snippet hat man ohnehin, weil man seine Komponente mal testen muss. Der Link ist auch schnell gemacht.

## Conceptual code

Ungetestete Idee ...

```php
// first get the markdown file
...
$md = fread($handle, filesize($filename));
...
    
// then split into parts
$md_array = explode("[Component]: #", $md)
    
// then separade md from components
foreach ($md_array as $block) {
    if (str_starts_with($block, '**Component demo**')) {
		$component_array = explode("```", $block);
        // -> send the component code to component renderer
        component_renderer($block[1]);
	} else {
        // -> send to markdown renderer
    }
}

```



