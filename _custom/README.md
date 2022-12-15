# Custom

To develop your own packages, public or private, while contributing to TAllUI, we included a second composer.json. This composer-file can be edited without overwriting the main composer.json.


## Custom composer.json

```bash
cd _custom
cp composer.json-example composer.json
```

If you want to include custom packages you can clone one or more packages as subrepos into _custom and add them to _custom/composer.json like so:

```json
    "repositories": [
        {
            "type": "path",
            "url": "./_custom/package"
        }
    ],
    "require": {
        "custom/package": "dev-main"
    },
```

Do a ```composer update``` afterwards.


## Custom views and routes

Then you can use following environment variables in .env to create custom views and custom routes without touching existing blade views or routes/web.php:

```shell
CUSTOM_VIEWS="one, two"
CUSTOM_ROUTES="one, two"
```

The last step is to 

```bash
cd resources/views/custom/
cp example.blade.php one.blade.php
```

and / or

```bash
cd routes
cp custom_example.php custom_two.php
```

and use them as custom views or custom routes. You may route into the gitignored subfolders of ```/resources/views/custom``` or your custom package.


## Share custom repos

Keep all files together in "your-repo" (yep, you can call it whatever you want) and share it with other people that develop with TallUI while contributing to the Monorepo.

Execute 

```bash
_custom/publish.sh your_repo
```

to copy all

- php-files prefixed with ```custom_``` from ```/_custom/your_repo/custom/routes``` to ```/routes```
- blade-views from ```/_custom/your_repo/custom/views``` to ```/resources/views/custom```


## Reminder

Don't forget .env
