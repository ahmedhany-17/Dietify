@echo off
:: This script disables ModSecurity and restarts Apache
:: MUST BE RUN AS ADMINISTRATOR

echo Disabling ModSecurity...
powershell -Command "(Get-Content 'C:\xampp\apache\conf\modsecurity.conf') -replace 'SecRuleEngine On', 'SecRuleEngine Off' | Set-Content 'C:\xampp\apache\conf\modsecurity.conf'"

echo Restarting Apache...
C:\xampp\apache\bin\httpd.exe -k restart

echo.
echo ModSecurity is now OFF.
pause
