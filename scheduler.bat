@echo off
rem Skrip Windows Task Scheduler untuk Sistem Peminjaman Mobil
rem Dijalankan setiap 1 menit melalui Task Scheduler Windows di PC kantor (PRD 13.1 & Prompt 8)

cd /d "%~dp0"
php artisan schedule:run >> storage\logs\scheduler.log 2>&1
