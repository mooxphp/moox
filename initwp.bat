@echo off

cd public/wp
move wp-config.php ..\wp-config.php
move wp-content ..\wp-content
cd ..
call composer install --no-interaction
move wp-config.php wp\wp-config.php
move wp-content wp\wp-content
cd wp/wp-content
move demo-uploads uploads
cd ..
