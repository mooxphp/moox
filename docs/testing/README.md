# Testing

Some notes about testing:

## Ideas and todo

It is a great idea to test features and code quality on PHP-level automatically. That's why we do PHPStan and testing with Pest.

But what about the final output? Doing following tests will be a bit more time consuming but awwwesome:

### Some more tests to go

- [Laravel Dusk](https://laravel.com/docs/9.x/dusk)
- Coverage > 75%
- Scrutinizer
  - https://scrutinizer-ci.com/g/usetall/tallui/settings/build-config
  - https://scrutinizer-ci.com/g/usetall/tallui/inspections/new
- Larastan, Cypress and more testing
  - https://github.com/adrolli/test-repo/ (from actual builder) runs with coverage, components can be tested now
    - https://laravel-livewire.com/screencasts/s1-writing-tests
    - https://spatie.be/courses/testing-laravel-with-pest/testing-a-livewire-component
    - https://github.com/livewire/livewire/blob/master/tests/Browser/TestCase.php
    - Badges, Coverage, README
  - Fix TestCase in tallui-core, phpstan in app-components and tests for livewire in app-components / web-components
  - Use Pest main with all packages + coverage + min:75%
  - Create test output in app, test output in app AS WELL AS test plain component in package
  - https://dev.to/robertobutti/add-test-coverage-badge-for-php-and-pest-in-your-github-repository-37mo - easy but not enough, how to calc coverage over a bunch of test?
  - https://pestphp.com/docs/coverage
  - use test-directory, see https://github.com/pestphp/pest/pull/283
  - or run tests with testbench like inside the packages
  - use https://pestphp.com/docs/plugins/laravel and https://pestphp.com/docs/plugins/livewire
  - use Laravel Dusk https://laravel.com/docs/9.x/dusk
  - and/or Cypress for Browsertests (https://dashboard.cypress.io/projects/ch1wnj/runs // https://github.com/laracasts/cypress / https://laracasts.com/series/jeffreys-larabits/episodes/22)
  - maybe use https://github.com/danielpalme/ReportGenerator-GitHub-Action as Coverage UI oder codecov.io



### Dependencies

- https://www.mend.io/free-developer-tools/renovate/ instead of Dependabot?

### Codecov

- Currently testing: https://app.codecov.io/gh/usetall
- Hangs syncing repos ... waiting

### Code Climate

- Currently testing: https://codeclimate.com/github/usetall/tallui/
- Add coverage: https://codeclimate.com/repos/635fabadc092716647007384/settings/test_reporter

### Codacy

- Currently testing: https://app.codacy.com/gh/usetall/tallui/
- Add coverage: https://docs.codacy.com/coverage-reporter/#generating-coverage

### Scrutinizer

Is already running, showing some kind of Code Climate, but tests fail because

- MySQL fails to run: https://scrutinizer-ci.com/docs/build/services
- We need to run the tests in the packages, not only in app scope
- Coverage must be setup, after tests are fixed, see https://scrutinizer-ci.com/docs/build/code_coverage AND https://github.com/guastallaigor/laravel-backoffice-example/blob/master/.scrutinizer.yml
- See https://github.com/benrowe/laravel-config/blob/master/.scrutinizer.yml for an example .scrutinizer.yml. We currently have a file without dot, that is ignored by Scrutinizer but a good backup to the used config

### HTML, CSS, JS

Ensure code quality on users end.

- https://validator.w3.org/docs/api.html, checked for GH Action but ... hmm nope

### Browser, Responsiveness

Ensure that everything looks fine on every device.

- Surface tests - https://www.cypress.io/, extends perfectly with a11y and more
  - use Axe see https://www.freecodecamp.org/news/automating-accessibility-tests-with-cypress/ or https://www.webaccessibility.com/tools/ AND https://github.com/component-driven/cypress-axe
  - https://github.com/laracasts/cypress OR https://github.com/NoelDeMartin/laravel-cypress AND https://github.com/noeldemartin/cypress-laravel OR https://github.com/mammadataei/cypress-vite
  - More, like visual regression, 2-FA and social logins, see https://docs.cypress.io/plugins/directory AND https://www.freecodecamp.org/news/how-to-add-screenshot-testing-with-cypress-to-your-project/
  - https://github.com/marketplace/actions/cypress-io

### Accessibility

Check accessibility and compliance with standards like WCAG...

- https://www.webaccessibility.com/tools/ - Continuum by Level Access - seems free, for [Cypress](https://support.levelaccess.com/hc/en-us/articles/360044430131-JavaScript-Continuum-for-Cypress) - or use Axe see https://www.freecodecamp.org/news/automating-accessibility-tests-with-cypress/
- https://github.com/GoogleChrome/lighthouse - universal but includes somewhat a11y, use https://github.com/marketplace/actions/lighthouse-audit OR https://github.com/marketplace/actions/lighthouse-ci-action if that really does all the tests against a real website (not only scanning html-files)
- https://www.accessibilitychecker.org/pricing/ free for 5 pages / day
- https://a11ywatch.com/ starts free
- https://wave.webaim.org/api/ starts prepaid $10
- https://accessibe.com/ starts $490 / year
- https://www.siteimprove.com/ - Enterprise
- https://www.appmatics.de/ - Enterprise
- https://www.powermapper.com/products/sortsite/checks/accessibility-checks/ - Desktop-Software
- https://achecks.ca/ starts $99 month
- https://www.deque.com/axe/ - Enterprise, free Browser Exts and FOSS core project
- https://github.com/ffoodd/a11y.css just a CSS or Browser-Ext, free
- https://testproject.io/why-free/ - Free! and https://opensdk.testproject.io/
- https://www.tanaguru.com/en/open-source-tools-tanaguru/downloads/ - FOSS!
- https://github.com/Khan/tota11y - FOSS
- https://pa11y.org/ - FOSS
- https://www.tawdis.net/ - seems free
- https://accesslint.com/ - free Github App, needs rendered HTML
- https://color.a11y.com/?wc3 - just a contrast checker, there are many. But contrast check needs to be implemented, not only checked while testing.
- https://www.boia.org/w3c-tools-services-a11y - seems Enterprise

About Lighthouse, WAVE, PA11Y and AXE -> https://www.qed42.com/insights/coe/quality-assurance/4-opensource-accessibility-audit-tools-you-must-know

A loooooong list of tools: https://www.w3.org/WAI/ER/tools/