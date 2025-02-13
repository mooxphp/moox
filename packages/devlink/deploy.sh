#!/bin/bash

if [ -f "composer.json-deploy" ]; then
    # Remove all symlinks from /packages
    find packages -type l -delete
    # Remove the packages folder if empty
    if [ -z "$(ls -A packages)" ]; then
        rm packages
    fi
    cp composer.json-deploy composer.json
    composer install
fi
