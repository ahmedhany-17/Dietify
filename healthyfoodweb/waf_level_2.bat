@echo off
:: This script sets ModSecurity Paranoia Level to 2 (Aggressive)
:: MUST BE RUN AS ADMINISTRATOR

echo Setting Paranoia Level to 2...
powershell -Command "$c = Get-Content 'C:\xampp\apache\modsecurity-crs\crs-setup.conf'; $c = $c -replace 'tx.blocking_paranoia_level=1', 'tx.blocking_paranoia_level=2'; $c = $c -replace 'tx.detection_paranoia_level=1', 'tx.detection_paranoia_level=2'; $c | Set-Content 'C:\xampp\apache\modsecurity-crs\crs-setup.conf'"

echo Restarting Apache...
C:\xampp\apache\bin\httpd.exe -k restart

echo.
echo Paranoia Level is now 2 (Aggressive).
pause
