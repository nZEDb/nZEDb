:Top
echo off
cls
:MAINMENU choose option from menu
set /p userinp=Enter the full group name you would like to backfill, or to default to all groups, simply press enter:   
set userinp=%userinp%
if "%userinp%"=="" goto All

:SINGLE
CD..
php.exe backfill.php "%userinp%"

:ALL
CD..
php.exe backfill.php all

