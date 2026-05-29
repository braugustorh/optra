@echo off
REM ================================================================
REM Script para regenerar TODA la cache de Laravel en el orden correcto
REM IMPORTANTE: icons:cache debe ir ANTES de view:cache
REM De lo contrario la compilacion de vistas tarda 7+ minutos
REM ================================================================
echo [1/6] Limpiando cache anterior...
php artisan optimize:clear

echo [2/6] Cacheando configuracion...
php artisan config:cache

echo [3/6] Cacheando rutas...
php artisan route:cache

echo [4/6] Cacheando eventos...
php artisan event:cache

echo [5/6] Cacheando iconos SVG (esto tarda ~4 minutos, es normal)...
php artisan icons:cache

echo [6/6] Compilando vistas Blade...
php artisan view:cache

echo ================================================================
echo Cache regenerada correctamente. La aplicacion deberia responder normal.
echo ================================================================
pause

