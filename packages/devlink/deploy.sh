#!/bin/bash

if [ -f "composer.json-deploy" ]; then
    find packages -type l -delete
    cp composer.json-deploy composer.json
fi
