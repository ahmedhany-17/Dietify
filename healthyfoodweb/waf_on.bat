@echo off
:: This script enables ModSecurity and restarts Apache
:: MUST BE RUN AS ADMINISTRATOR

echo Enabling ModSecurity...
powershell -Command "(Get-Content 'C:\xampp\apache\conf\modsecurity.conf') -replace 'SecRuleEngine Off', 'SecRuleEngine On' | Set-Content 'C:\xampp\apache\conf\modsecurity.conf'"

echo Restarting Apache...
C:\xampp\apache\bin\httpd.exe -k restart

echo.
echo ModSecurity is now ON.
pause
