# Blog



## Localization for TallUI and Laravel packages

In Laravel there are two ways to localize packages: PHP-files or JSON-files, see https://laravel.com/docs/9.x/localization. Both have their advantages.

- PHP-files is the older approach. It is therefore used in most Laravel packages and projects. You'll have to invent a string name for each translation, but you can easily avoid collisions with similar translation strings. So the PHP variant is more consistent.
- JSON-files in Laravel are newer. As the source language string is directly used in the source code, this variant reads better. But you can't avoid collisions in similar languages. This is particularly problematic when dialects (such as German/Switzerland or Spanish/Mexico) are used.

Most of the translation platforms support PHP-files, only a few support pluralization. Before deciding which way to go, I may be a good idea to decide which platform to use.

- Weblate - see 
- Crowdin - see 
- Lokalise - see https://lokalise.com/blog/laravel-localization-step-by-step/
- Phrase - see https://phrase.com/blog/posts/laravel-i18n-frontend-best-practices/



Free for open source projects ...



Tanslating a Laravel package with Crowdin

Package builder includes crowdin.yml





## Code Quality for Open Source projects





## Monorepo





## Using package builder for your next Laravel package

