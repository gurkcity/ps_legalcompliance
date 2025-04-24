@echo off
set _PS_ROOT_DIR_=C:\xampp82\htdocs\prestashop_8
php vendor\bin\phpstan analyse -v --configuration=tests/phpstan/phpstan.neon
