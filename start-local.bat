@echo off
cd /d "%~dp0"
echo Starting AquaFlow at http://127.0.0.1:8017
echo Keep this terminal open. Press Ctrl+C to stop.
php artisan serve --host=127.0.0.1 --port=8017
pause
