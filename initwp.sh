#!/bin/sh

cd public/wp
mv wp-config.php ../wp-config.php
mv wp-content ../wp-content
cd ..
composer install
mv wp-config.php wp/wp-config.php
mv wp-content wp/wp-content
cd wp/wp-content
mv demo-uploads uploads
cd ../../..
