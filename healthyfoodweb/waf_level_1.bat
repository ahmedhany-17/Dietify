@echo off
:: This script sets ModSecurity Paranoia Level to 1 (Standard)
:: MUST BE RUN AS ADMINISTRATOR

echo Setting Paranoia Level to 1...
powershell -Command "$c = Get-Content 'C:\xampp\apache\modsecurity-crs\crs-setup.conf'; $c = $c -replace 'tx.blocking_paranoia_level=2', 'tx.blocking_paranoia_level=1'; $c = $c -replace 'tx.detection_paranoia_level=2', 'tx.detection_paranoia_level=1'; $c | Set-Content 'C:\xampp\apache\modsecurity-crs\crs-setup.conf'"

echo Restarting Apache...
C:\xampp\apache\bin\httpd.exe -k restart

echo.
echo Paranoia Level is now 1 (Standard).
pause
