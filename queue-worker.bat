@echo off
rem Skrip Queue Worker untuk Sistem Peminjaman Mobil
rem Menjalankan antrean pekerjaan latar belakang dengan aman (PRD 13.1 & Prompt 8)

cd /d "%~dp0"
php artisan queue:work --stop-when-empty --tries=3 >> storage\logs\queue.log 2>&1
