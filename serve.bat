@echo off
rem Fast local server: the same as "php artisan serve" but with OPcache switched on,
rem which XAMPP leaves off by default. Pages respond several times faster.
rem Usage:  serve.bat          (port 8080)
rem         serve.bat 8000     (another port)
set PORT=%1
if "%PORT%"=="" set PORT=8080
cd /d "%~dp0public"
echo Store running at http://127.0.0.1:%PORT%   (Ctrl+C to stop)
php -d zend_extension=opcache -d opcache.enable=1 -d opcache.enable_cli=1 -d opcache.memory_consumption=192 -d opcache.max_accelerated_files=20000 -d opcache.revalidate_freq=0 -d realpath_cache_size=4096K -d realpath_cache_ttl=600 -S 127.0.0.1:%PORT% ..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php
