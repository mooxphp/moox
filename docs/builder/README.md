### Add a new package:

This is how to add a new package to develop within the TallUI Monorepo:

-   Create a new package from TallUI Package Builder or Icons Builder template
-   You may create it as public package in the Usetall Organization
-   Copy contents into `_custom`folder and require it from there, if you need time to develop it 

After the initial development is finished or when a package (e.g. icons) is made in a nutshell:

-   Copy contents into the appropriate `_subfolder` of the monorepo
-   Add the package to the appropriate monorepo-split-action
-   Add the package to composer.json, compose and test the package
-   Add the package to Weblate, if it has translations

And finally, when the package is tested and ready for production:

-   Add the package to the README.md so that others can find it
-   Add the package to Packagist if the package is stable
-   Require the package to `_app/*/composer.json` as see fit
