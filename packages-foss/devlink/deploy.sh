#!/bin/bash

if [ -f "composer.json-linked" ]; then
    find packages -type l -delete
    cp composer.json-linked composer.json
fi
