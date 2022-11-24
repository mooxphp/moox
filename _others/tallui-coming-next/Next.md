# Coding docs

-	Declare(strict_types=1);
-	Kein Docblock zur Typisierung von Variablen
-	Bsp:  
public string $first_var = '';
protected static $assets = ['example'];


Errors and Solutions
Level 6
Property
return type has no value type specified in iterable type array.
=> /** @var array<mixed> */ or something */
Methode
return type has no value type specified in iterable type array.
=>   /** @return array<mixed> */
has parameter with no value type specified in iterable type array.
=>  /* @param array<mixed> $assets/
 has no return type specified.
=>:void, :bool, :int, :string etc.
Function
 has parameter with no type specified.
=> sting, int, bool, array...
Level 7
Methode
Call to an undefined method Pest\Expectation|Pest\Support\Extendable::toBeTrue().
Parameter
Parameter #3 $subject of function str_replace expects array|string, string|false given.
=> (string)
https://phpstan.org/user-guide/ignoring-errors#generate-an-ignoreerrors-entry
=> Links to generate errors


# Icons

- https://github.com/MohmmedAshraf/blade-flags


# Website contents

Some ideas to get our website up and running:

## Startpage

Short desc

### Features

- Developer-friendly
- SEO optimized
- Quality tested
- more to come

### TallUI in raw numbers

#### What's in the box

- Laravel packages
- Blade components
- Livewire components
- Laravel themes
- Admin themes
- Blade icons
- Languages

#### Development

- Contributors
- Translators
- Commits
- Pest tests
- Closed issues
- PRs merged

#### Todo

- Open issues
- Planned packages
- Open translations

### Contribute and sponsor

How to contribute and help to translate...

### Thanks to

Sponsors, Stack (and other as comma-list), Friends, Contributors


# Auth

- Username or Mail
- Passwort
- TOTP (Time-based One-Time-Password) / Token - 2-FA or MFA
- Login Link / Magic Link / Passwordless Login
- Password Quality features
- Password expiration
- Lock after XX tries (IP / User)
- Oauth and auth provider
- Risk, see https://www.onelogin.com/learn/what-is-mfa
- Questions, Birth date ...
- Captcha ... hmm
- https://www.gartner.com/reviews/market/access-management
- Certificate, see https://www.elster.de/eportal/login/softpse
- Blacklist und Risk-Score ...
- https://stackoverflow.blog/2022/11/16/biometric-authentication-for-web-devs/


## Trusted Devices

- Login alert (mail) if user logs in with an unknown device
- Allow to whitelist (remember me) or blacklist (block) devices
- E-Mail with 6-digit-code, when logging in from an unknown device
- Honeypot, alert or similar when logging in from an blocked device
- Request additional info (birtdate, zip-code) for unknown device logins



# Blog

## Translate your Open Source project

Localization for TallUI and Laravel packages

This is the starting post of our blog series about internationalization (I18n), localization (l10n) or translation of Open Source projects, specially Laravel packages. We include every part of translation issues, from building a language switcher to automatization with Git. Let's start.

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


- Decide which languages - Europe, Most popular, Laravel core languages, WordPress languages, All languages (depends on where you are and where your target users are)
  - https://www.loc.gov/standards/iso639-2/php/langcodes-search.php to search language codes
  - 80 Laravel core languages (~100%), see https://github.com/Laravel-Lang/lang/
  - https://www.google.com/search?q=most+spoken+languages
  - https://translate.wordpress.org/stats/ - WordPress translated into 87 languages (>75%)
- Languages vs. countries - Choosing the right flags and language settings
  -  for languages spoken in different countries like - see https://blog.esl-languages.com/blog/learn-languages/most-spoken-languages-world/ for total numbers
    - English (59)
      - https://en.wikipedia.org/wiki/Comparison_of_American_and_British_English - lot of differences between British English and American English even in writings
      - https://www.britishcouncilfoundation.id/en/english/articles/british-and-american-english
      - https://britishenglishonline.com/british-english-vs-american-english/british-english-vs-american-english/
    - French (29)
      - research
    - Arabic (26)
      - https://www.quora.com/Which-flag-represents-the-Arabic-language
      - https://de.wikipedia.org/wiki/Arabische_Liga
    - Spanish (21)
      - https://en.wikipedia.org/wiki/List_of_countries_where_Spanish_is_an_official_language
      - The main difference between Spain and Spanish language dialects spoken in other countries seems to be https://en.wikipedia.org/wiki/Voseo, see also https://en.wikipedia.org/wiki/Central_American_Spanish
      - https://lingvist.com/blog/spain-spanish-vs-mexican-spanish/ - some differences 
      - https://en.wikipedia.org/wiki/Colombian_Spanish - more of a dialect
    - Portuguese (9)
      - research
  - and vice versa, see https://ad-astrainc.com/2021/08/the-countries-with-the-most-official-languages-and-how-they-translate/
    - see above for stunning numbers
    - Switzerland - DE/FR/IT
      - https://studyinginswitzerland.com/swiss-german-vs-german-differences/ - Official: Swiss German is not a language, but rather an umbrella term for the collection of Alemannic dialects that are spoken in Switzerland.
      - https://en.wikipedia.org/wiki/Swiss_German
      - https://en.wikipedia.org/wiki/Swiss_French - a short list of differences
      -  https://en.wikipedia.org/wiki/Swiss_Italian - a very short list of differences
- What about formal and informal languages like
  - German
  - Netherlands 
- How to build a language switcher
  - Depends on your needs for Internationalization or just Translation ... first one means to really divide into countries and languages ... second one means only languages ... a third one may be only countries but that is only useful, if you have a few countries in mind, otherwise you may confuse
  - Layouts and a common icon for languages or countries
  - Using the native name (endonym) or translation (exonym) - see https://en.wikipedia.org/wiki/Endonym_and_exonym and https://omniglot.com/language/names.htm


## Code Quality for Open Source projects


## Monorepo


## Using package builder for your next Laravel package

