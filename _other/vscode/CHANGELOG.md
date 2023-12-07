# Changelog

### Planned for next release

- Replace Livewire Language Support with [Laravel Blade Livewire LSP](https://marketplace.visualstudio.com/items?itemName=haringsbe-haringsrob.laravel-blade-livewire-lsp)
- Add [Devsense PHP Tools](https://marketplace.visualstudio.com/items?itemName=DEVSENSE.phptools-vscode), as replacement for other PHP Exts - buy a license?
- Replace Alpine.js IntelliSense by Adria Wilcynski with Alpine Intellisense by P. Christopher Bowers (https://marketplace.visualstudio.com/items?itemName=pcbowers.alpine-intellisense)
- Test PHPStan (swordev.phpstan) alternatives
  - https://marketplace.visualstudio.com/items?itemName=ddarkonen.phpstan-larastan
  - https://marketplace.visualstudio.com/items?itemName=SanderRonde.phpstan-vscode
  - https://marketplace.visualstudio.com/items?itemName=calsmurf2904.vscode-phpstan
  - https://marketplace.visualstudio.com/items?itemName=calsmurf2904.vscode-phpstan
- Test Laravel Pint (southeners.laravel-pint) alternatives
  - https://marketplace.visualstudio.com/items?itemName=msamgan.laravel-pint-vscode
  - https://marketplace.visualstudio.com/items?itemName=raullg97.laravel-pint-linter
- Improve docs and possibly split like, also add these to alternative packs
  - https://marketplace.visualstudio.com/items?itemName=onecentlin.laravel-extension-pack
  - https://marketplace.visualstudio.com/items?itemName=onecentlin.laravel5-snippets

## Version 1.0.7

- Remove phpfmt PHP formatter, deprecated as of 2019 and no longer useful with PHP 8
- Remove Psalm, we (as most Laravel codebases we know) moved to PHPStan and Larastan
- Remove PHP Symbols, it is now fully replaced by Intelephense
- Replace felixfbecker.php-debug with its successor xdebug.php-debug
- Add [PHPStan](https://marketplace.visualstudio.com/items?itemName=swordev.phpstan)
- Add [Livewire Switcher](https://marketplace.visualstudio.com/items?itemName=bebo925.livewire-switcher)
- Add [Laravel Pint](https://marketplace.visualstudio.com/items?itemName=open-southeners.laravel-pint)
- Add [Pest Snippets](https://marketplace.visualstudio.com/items?itemName=dansysanalyst.pest-snippets)
- Improve docs, update logo

### Todo

- Add laravel-goto-component extension?
- Add Laravel Blade formatter extension?
- Add Laravel Create View extension?
- Add Laravel Blade Wrapper extension?

## Version 1.0.6

- Removed Browser Preview (deprecated), officially replaced by https://marketplace.visualstudio.com/items?itemName=ms-vscode.live-server, but we decided not to add any replacement as we do not need this feature anymore using Vite (and hopefully soon Livewire 3)
- Docs

## Version 1.0.5

- Minor improvements, docs

## Version 1.0.4

- Improved docs
- Added Extensions

```
    + ahinkle.laravel-model-snippets
    + austenc.laravel-docs
    + wk-j.save-and-run
    + auchenberg.vscode-browser-preview
    + kamikillerto.vscode-colorize
    + esbenp.prettier-vscode
```

## Version 1.0.3

- Changed PHP Code Formatter

```
    - shevaua.phpcs (abandoned)
    - persoderlind.vscode-phpcbf (abandoned)
    + ValeryanM.vscode-phpsab
```

## Version 1.0.2

- First public version
