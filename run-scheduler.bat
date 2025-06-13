@echo off
cd C:\xampp\htdocs\PFE\pfe_backend
php artisan schedule:run >> NUL 2>&1 