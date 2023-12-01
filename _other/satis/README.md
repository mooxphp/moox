# TallUI Composer Satis Repository

The TallUI Composer Satis Repository enables us to provide access to private repositories (like heco and meinsolarstrom), public-but-not-yet-published repositories and commercial packages (in a possible future):

http://satis.moox.org/

## Devlog

Based on the Satis Readme (https://github.com/composer/satis) and Spatie's helpful article (https://alexvanderbist.com/2021/setting-up-and-securing-a-private-composer-repository/), this is how to set up our private repos:

Create and enter project folder: `mkdir tallui-satis && cd !$`

Create the JSON-config: `touch satis.json`:

```json
{
    "name": "mooxphp/moox-repository",
    "homepage": "https://satis.moox.org",
    "output-dir": "public",
    "repositories": [
        { "type": "vcs", "url": "https://github.com/adrolli/test-repo" }
    ],
    "archive": {
        "directory": "dist",
        "skip-dev": false
    },
    "require-all": true
}
```

Create project: `composer create-project composer/satis:dev-main`

Add Github access token: `touch ~/.composer/auth.json`

```json
{
    "github-oauth": {
        "github.com": "oauth_token_goes_here"
    }
}
```

otherwise Composer will ask for a Github Token (https://github.com/settings/tokens) while building the project.

Build project: `php satis/bin/satis build satis.json`

Now you can open private/index.html in your browser and voila!

## FAQs

-   How to style the output of Satis?
    -   Forget the generated index.html file and use only the underlying composer repos
-   How to secure the website?
    -   Forget it again, lock it with basic auth or even delete the whole index.html after building.
-   How to secure access to all/some/one repositories?
    -   Basic-Auth (all only)
    -   https://alexvanderbist.com/2021/private-satis-authentication-backed-by-laravel/
-   How to publish new releases?
    -   Build command cronjob, see https://composer.github.io/satis/using
    -   Hmm, zero downtime?
-   How to add new repositories?
    -   Add to json + rebuild (faster if only for this package, see https://composer.github.io/satis/using)
    -   http://ludofleury.github.io/satisfy/
-   How to build our own package registry and manager? Investigating ...
    -   https://t3planet.com/blog/satis-private-packages-composer/
    -   https://get.typo3.org/misc/composer/repository
    -   https://github.com/TYPO3/typo3/search?q=composer !
        -   https://github.com/TYPO3/typo3/tree/main/typo3/sysext/core/Classes/Package
        -   https://github.com/TYPO3/typo3/blob/main/typo3/sysext/extensionmanager/Classes/Utility/InstallUtility.php
    -   https://github.com/composer/composer/tree/main/src/Composer/Package
